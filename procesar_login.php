<?php
/**
 * ARCHIVO: procesar_login.php
 * FUNCIÓN: Motor de autenticación blindado con fusión inteligente de carritos.
 * ACTUALIZACIÓN: Validación CSRF, Rate Limiting (SQL por IP), Open Redirect seguro y fusión de carrito persistente.
 */

require_once 'includes/config.php';

// --- 1. SEGURIDAD DE ENTRADA: SOLO PETICIONES POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Variables para el escudo anti-fuerza bruta
$ip_usuario = $_SERVER['REMOTE_ADDR'];
$limite_intentos = 5;
$minutos_bloqueo = 15;

// VERIFICACIÓN DE BLOQUEO EN BASE DE DATOS ANTES DE HACER NADA
$stmt_rl = $pdo->prepare("SELECT intentos, UNIX_TIMESTAMP(ultima_falla) as ts_falla FROM login_intentos WHERE ip = ?");
$stmt_rl->execute([$ip_usuario]);
$registro_rl = $stmt_rl->fetch(PDO::FETCH_ASSOC);

if ($registro_rl && $registro_rl['intentos'] >= $limite_intentos) {
    $tiempo_transcurrido = time() - $registro_rl['ts_falla'];
    if ($tiempo_transcurrido < ($minutos_bloqueo * 60)) {
        // Sigue bloqueado, lo devolvemos a login al instante sin gastar recursos
        header("Location: login.php?error=1");
        exit;
    } else {
        // Ya cumplió su castigo de 15 minutos, limpiamos su historial
        $pdo->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip_usuario]);
    }
}
// --- 2. VALIDACIÓN CSRF (Blindaje contra ataques de falsificación) ---
$token_recibido = $_POST['csrf_token'] ?? '';
if (empty($token_recibido) || $token_recibido !== ($_SESSION['csrf_token'] ?? '')) {
    if(function_exists('auditLog')) auditLog('CSRF_FAIL', "Intento de login sin CSRF válido.");
    header("Location: login.php?error=security");
    exit;
}

// --- 3. SANEAMIENTO Y CAPTURA ---
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$redirect = htmlspecialchars($_POST['redirect'] ?? '', ENT_QUOTES, 'UTF-8');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    header("Location: login.php?error=1");
    exit;
}

// =================================================================================
// 🛡️ INICIO DEL ESCUDO TRY...CATCH (CUBRE TODO EL TRÁFICO SQL)
// =================================================================================
try {
    // --- 4. SEGURIDAD ESTRICTA: ESCUDO ANTI-FUERZA BRUTA (SQL) ---
    // Comprobamos si la IP ya está bloqueada antes de procesar la contraseña
    $stmt_check = $pdo->prepare("SELECT intentos, ultima_falla FROM login_intentos WHERE ip = ?");
    $stmt_check->execute([$ip_usuario]);
    $bloqueo = $stmt_check->fetch();

    if ($bloqueo && $bloqueo['intentos'] >= $limite_intentos) {
        $tiempo_transcurrido = time() - strtotime($bloqueo['ultima_falla']);
        if ($tiempo_transcurrido < ($minutos_bloqueo * 60)) {
            if(function_exists('auditLog')) auditLog('LOGIN_BLOCKED', "IP bloqueada temporalmente: " . $ip_usuario);
            header("Location: login.php"); // login.php ya se encarga de mostrar el escudo rojo
            exit;
        } else {
            // Expiró el castigo, reseteamos para darle otra oportunidad
            $pdo->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip_usuario]);
        }
    }

    // --- 5. BÚSQUEDA DEL USUARIO (Consulta Preparada) ---
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol, carrito_guardado FROM usuarios WHERE email = ?");    
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- 6. VERIFICACIÓN DE CREDENCIALES ---
    if ($user && password_verify($password, $user['password'])) {
        
        // --- LOGIN EXITOSO: RESETEAMOS CONTADORES DE FALLO DE ESTA IP ---
        $pdo->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip_usuario]);

        // --- SEGURIDAD: REGENERACIÓN DE ID DE SESIÓN ---
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        
        // Marcador para compatibilidad Admin
        if ($user['rol'] === 'admin') {
            $_SESSION['admin'] = true;
            if(function_exists('auditLog')) auditLog('ADMIN_LOGIN', "El administrador {$user['email']} inició sesión.");
        }

        // --- 7. MAGIA: FUSIÓN INTELIGENTE DE CARRITO (SEGURO Y PERSISTENTE) ---
        // Escudo 1: Aseguramos que la sesión del carrito sea siempre un array válido
        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (!empty($user['carrito_guardado'])) {
            $carrito_bd = json_decode($user['carrito_guardado'], true);
            
            if (is_array($carrito_bd)) {
                foreach ($carrito_bd as $clave_bd => $item_bd) {
                    $fusionado = false;

                    // Buscamos si ya tiene el mismo producto en el carrito actual de invitado
                    foreach ($_SESSION['carrito'] as $clave_sess => &$item_sess) {
                        $mismo_id = ($item_sess['id'] === $item_bd['id']);
                        $misma_talla = (($item_sess['talla'] ?? '') === ($item_bd['talla'] ?? ''));

                        // Si es producto de catálogo, sumamos cantidades
                        if ($mismo_id && $misma_talla && $item_sess['id'] !== 'CUSTOM_PROD') {
                            $item_sess['cantidad'] += (int)$item_bd['cantidad'];
                            $fusionado = true;
                            break;
                        }
                    }
                    unset($item_sess); // Romper la referencia

                    // Si no se fusionó (es nuevo o personalizado), lo añadimos
                    if (!$fusionado) {
                        // Respetamos la clave de la BD para que funcione el borrado después
                        if (is_string($clave_bd) && $clave_bd !== '') {
                            $_SESSION['carrito'][$clave_bd] = $item_bd;
                        } else {
                            $nueva_clave = $item_bd['id'] . '_' . uniqid();
                            $_SESSION['carrito'][$nueva_clave] = $item_bd;
                        }
                    }
                }
            }
        }

        // --- ACTUALIZACIÓN DB: PERSISTENCIA TOTAL ---
        // Guardamos el carrito fusionado inmediatamente en lugar de vaciarlo.
        if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
            $stmt_save = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
            $stmt_save->execute([json_encode($_SESSION['carrito']), $user['id']]);
        }

        // --- 8. LÓGICA DE REDIRECCIÓN (OPEN REDIRECT SEGURO) ---
        $allowed_redirects = [
            'checkout' => 'checkout.php',
            'perfil' => 'perfil.php',
            'productos' => 'productos.php',
            'carrito' => 'carrito.php'
        ];

        $destino = "index.php"; // Destino por defecto

        if (array_key_exists($redirect, $allowed_redirects)) {
            $destino = $allowed_redirects[$redirect];
        } elseif ($user['rol'] === 'admin') {
            $destino = "admin_pedidos.php";
        }

        // Limpiamos el token CSRF tras el uso exitoso
        unset($_SESSION['csrf_token']);

        // Limpiamos el historial de fallos de esta IP porque el usuario legítimo logró entrar
$pdo->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip_usuario]);

        $conector = (strpos($destino, '?') !== false) ? '&' : '?';
        header("Location: " . $destino . $conector . "msg=login_success");
        exit;

    } else {
        // --- ERROR DE CREDENCIALES ---
        $sql_error = "INSERT INTO login_intentos (ip, intentos) VALUES (?, 1) 
                      ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultima_falla = NOW()";
        $pdo->prepare($sql_error)->execute([$ip_usuario]);
        
        $stmt_count = $pdo->prepare("SELECT intentos FROM login_intentos WHERE ip = ?");
        $stmt_count->execute([$ip_usuario]);
        $intentos_actuales = $stmt_count->fetchColumn();

        if(function_exists('auditLog')) auditLog('LOGIN_FAIL', "Intento fallido para email: " . $email . " | Intento " . $intentos_actuales);

        $url_fail = "login.php?error=1";
        if ($redirect === 'checkout') {
            $url_fail .= "&redirect=checkout";
        }
        header("Location: " . $url_fail);
        exit;
    }

} catch (Exception $e) {
    error_log("ERROR CRÍTICO LOGIN (BD): " . $e->getMessage());
    header("Location: login.php?error=1");
    exit;
}