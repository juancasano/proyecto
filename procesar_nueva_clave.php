<?php
/**
 * ARCHIVO: procesar_nueva_clave.php
 * FUNCIÓN: Finalización del cambio de contraseña y destrucción de tokens.
 * ACTUALIZACIÓN: Validación CSRF, comprobación de longitud y limpieza de seguridad.
 */

require_once 'includes/config.php';

// --- 1. SEGURIDAD DE ENTRADA: SOLO PETICIONES POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// --- 2. VALIDACIÓN CSRF (Blindaje contra falsificación de peticiones) ---
$token_csrf = $_POST['csrf_token'] ?? '';
if (empty($token_csrf) || $token_csrf !== ($_SESSION['csrf_token'] ?? '')) {
    error_log("INTENTO DE CAMBIO DE CLAVE SIN TOKEN CSRF: IP " . $_SERVER['REMOTE_ADDR']);
    header("Location: recuperar.php?error=csrf");
    exit;
}

// --- 3. CAPTURA Y VALIDACIÓN DE DATOS ---
$token_reset = $_POST['token'] ?? '';
$pass_nueva  = $_POST['nueva_pass'] ?? '';

if (empty($token_reset) || empty($pass_nueva)) {
    header("Location: recuperar.php?error=missing_data");
    exit;
}

// Doble validación de longitud (Cinturón de seguridad extra)
if (strlen($pass_nueva) < 8) {
    header("Location: restablecer.php?token=" . urlencode($token_reset) . "&error=pass_short");
    exit;
}

try {
    // --- 4. ACTUALIZACIÓN BÚNKER (Hash y Limpieza en un solo paso) ---
    // Hasheamos con BCRYPT (El estándar de oro actual)
    $pass_hash = password_hash($pass_nueva, PASSWORD_BCRYPT);

    // Ejecutamos el UPDATE solo si el token es correcto y no ha expirado
    $sql = "UPDATE usuarios 
            SET password = ?, reset_token = NULL, reset_expires = NULL 
            WHERE reset_token = ? AND reset_expires > NOW()";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pass_hash, $token_reset]);

    // Comprobamos si realmente se actualizó alguna fila
    if ($stmt->rowCount() > 0) {
        
        // --- 5. ÉXITO: LIMPIEZA DE SESIÓN Y REDIRECCIÓN ---
        // Destruimos el token CSRF para que no se use más
        unset($_SESSION['csrf_token']);
        
        // Redirigimos al login informando del éxito
        header("Location: login.php?msg=success");
        exit;
        
    } else {
        // El token era inválido o caducó justo antes del envío
        error_log("INTENTO DE RESET CON TOKEN INVÁLIDO O EXPIRADO: $token_reset");
        header("Location: recuperar.php?error=invalid_token");
        exit;
    }

} catch (Exception $e) {
    // Registro de error técnico para el Administrador
    error_log("FALLO CRÍTICO EN ACTUALIZACIÓN DE CLAVE: " . $e->getMessage());
    header("Location: recuperar.php?error=system");
    exit;
}