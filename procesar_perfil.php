<?php
/**
 * ARCHIVO: procesar_perfil.php
 * FUNCIÓN: Procesamiento blindado de datos, contraseñas y gestión de biblioteca.
 * ACTUALIZACIÓN: Validación de servidor para CP (5 dígitos) y protección atómica.
 */

require_once 'includes/config.php';

// --- 1. SEGURIDAD DE ACCESO: SOLO USUARIOS LOGUEADOS ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// --- 2. VALIDACIÓN DE TOKEN CSRF (Antihack: Previene cambios no autorizados) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        error_log("POSIBLE ATAQUE CSRF DETECTADO: Usuario ID $user_id");
        header("Location: perfil.php?error=security_token");
        exit;
    }
}

// --- ACCIÓN 1: ACTUALIZAR DATOS DE ENVÍO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'datos') {
    
    // Saneamiento básico
    $nombre    = h(trim($_POST['nombre'] ?? ''));
    $direccion = h(trim($_POST['direccion'] ?? ''));
    $ciudad    = h(trim($_POST['ciudad'] ?? ''));
    $cp        = trim($_POST['cp'] ?? '');

    // Unificamos el prefijo (ej: 34) y el número (ej: 666...)
    $prefijo = preg_replace('/[^0-9]/', '', $_POST['prefijo'] ?? '34');
    $numero  = preg_replace('/[^0-9]/', '', $_POST['telefono_num'] ?? '');
    $telefono_final = $prefijo . $numero;

    // 1. VALIDACIÓN DE CAMPOS OBLIGATORIOS (Ahora comprobamos $telefono_final)
    if (empty($nombre) || empty($direccion) || empty($ciudad) || empty($cp) || empty($numero)) {
        header("Location: perfil.php?error=fields_missing#tab-datos");
        exit;
    }

    // 2. BLINDAJE DE CÓDIGO POSTAL (Mantenemos tu escudo atómico)
    if (!preg_match('/^[0-9]{5}$/', $cp)) {
        header("Location: perfil.php?error=cp_invalid#tab-datos");
        exit;
    }

    try {
        // Usamos $telefono_final en el UPDATE
        $sql = "UPDATE usuarios SET nombre = ?, direccion = ?, ciudad = ?, cp = ?, telefono = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $direccion, $ciudad, $cp, $telefono_final, $user_id]);

        // Sincronización inmediata de sesión
        $_SESSION['nombre'] = $nombre;

        header("Location: perfil.php?msg=updated#tab-datos");
        exit;
    } catch (PDOException $e) {
        error_log("ERROR DB Camiglobo (Update Perfil): " . $e->getMessage());
        header("Location: perfil.php?error=db#tab-datos");
        exit;
    }
}

// --- ACCIÓN 2: CAMBIAR CONTRASEÑA (BÚNKER DE SEGURIDAD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'password') {
    
    // 1. Verificación de reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response) || !validarRecaptcha($recaptcha_response)) {
        header("Location: perfil.php?error=captcha#tab-pass");
        exit;
    }

    $pass_actual    = $_POST['pass_actual'] ?? '';
    $nueva_pass      = $_POST['nueva_pass'] ?? '';
    $nueva_pass_conf = $_POST['nueva_pass_conf'] ?? '';

    // 2. Validaciones de fortaleza
    if ($nueva_pass !== $nueva_pass_conf) {
        header("Location: perfil.php?error=pass_mismatch#tab-pass");
        exit;
    }

    if (strlen($nueva_pass) < 8) {
        header("Location: perfil.php?error=pass_short#tab-pass");
        exit;
    }

    // 3. Verificación de identidad y actualización
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_db = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_db && password_verify($pass_actual, $user_db['password'])) {
        
        $pass_hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmtUpdate->execute([$pass_hash, $user_id]);

        // Seguridad extrema: regeneramos ID de sesión tras cambio de credenciales
        session_regenerate_id(true);

        header("Location: perfil.php?msg=pass_ok#tab-pass");
    } else {
        header("Location: perfil.php?error=pass_wrong#tab-pass");
    }
    exit;
}

// --- ACCIÓN: PONER DIRECCIÓN COMO PREDETERMINADA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'predeterminada_direccion') {
    $id_dir = (int)$_POST['id_dir'];
    try {
        // Primero quitamos la predeterminada a todas las del usuario
        $pdo->prepare("UPDATE user_direcciones SET predeterminada = 0 WHERE user_id = ?")->execute([$user_id]);
        // Luego marcamos la elegida (solo si pertenece al usuario)
        $pdo->prepare("UPDATE user_direcciones SET predeterminada = 1 WHERE id = ? AND user_id = ?")->execute([$id_dir, $user_id]);
        header("Location: perfil.php?msg=updated#tab-datos");
    } catch(Exception $e) {
        error_log("Error predeterminada dirección: " . $e->getMessage());
        header("Location: perfil.php?error=db#tab-datos");
    }
    exit;
}

// --- NUEVA ACCIÓN: AÑADIR DIRECCIÓN DESDE EL PERFIL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'nueva_direccion') {
    $alias = h(trim($_POST['alias'] ?? 'Mi Dirección'));
    $tipos_validos = ['casa', 'trabajo', 'otra'];
    $tipo_dir = in_array($_POST['tipo_dir'] ?? '', $tipos_validos) ? $_POST['tipo_dir'] : 'otra';
    $nombre_dir = h(trim($_POST['nombre_dir'] ?? ''));
    $direccion_dir = h(trim($_POST['direccion_dir'] ?? ''));
    $ciudad_dir = h(trim($_POST['ciudad_dir'] ?? ''));
    $cp_dir = h(trim($_POST['cp_dir'] ?? ''));

    // Juntamos el prefijo (limpio) con el número (limpio) y le ponemos el "+"
    $prefijo_dir = preg_replace('/[^0-9]/', '', $_POST['tel_prefijo'] ?? '34');
    $numero_dir  = preg_replace('/[^0-9]/', '', $_POST['tel_dir'] ?? '');
    $tel_dir     = '+' . $prefijo_dir . $numero_dir;

    try {
        $stmt = $pdo->prepare("INSERT INTO user_direcciones (user_id, alias, nombre, direccion, ciudad, cp, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $alias, $nombre_dir, $direccion_dir, $ciudad_dir, $cp_dir, $tel_dir]);
        header("Location: perfil.php?msg=updated#tab-datos");
    } catch(Exception $e) {
        error_log("Error añadiendo dirección: " . $e->getMessage());
        header("Location: perfil.php?error=security#tab-datos");
    }
    exit;
}

// --- NUEVA ACCIÓN: BORRAR DIRECCIÓN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'borrar_direccion') {
    $id_dir = (int)$_POST['id_dir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM user_direcciones WHERE id = ? AND user_id = ?");
        $stmt->execute([$id_dir, $user_id]);
        header("Location: perfil.php?msg=updated#tab-datos");
    } catch(Exception $e) {
        header("Location: perfil.php?error=security#tab-datos");
    }
    exit;
}

// --- ACCIÓN: EDITAR DIRECCIÓN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'editar_direccion') {
    $id_dir = (int)$_POST['id_dir'];
    $alias = h(trim($_POST['alias'] ?? 'Mi Dirección'));
    $nombre_dir = h(trim($_POST['nombre_dir'] ?? ''));
    $direccion_dir = h(trim($_POST['direccion_dir'] ?? ''));
    $ciudad_dir = h(trim($_POST['ciudad_dir'] ?? ''));
    $cp_dir = h(trim($_POST['cp_dir'] ?? ''));

    $prefijo_dir = preg_replace('/[^0-9]/', '', $_POST['tel_prefijo'] ?? '34');
    $numero_dir = preg_replace('/[^0-9]/', '', $_POST['tel_dir'] ?? '');
    $tel_dir = '+' . $prefijo_dir . $numero_dir;

    try {
        $stmt = $pdo->prepare("UPDATE user_direcciones SET alias = ?, nombre = ?, direccion = ?, ciudad = ?, cp = ?, telefono = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$alias, $nombre_dir, $direccion_dir, $ciudad_dir, $cp_dir, $tel_dir, $id_dir, $user_id]);
        header("Location: perfil.php?msg=updated#tab-datos");
    } catch(Exception $e) {
        error_log("Error editando dirección: " . $e->getMessage());
        header("Location: perfil.php?error=security#tab-datos");
    }
    exit;
}

// --- ACCIÓN 3: BORRAR RECURSO (CON FILTRO DE PROPIEDAD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'borrar_recurso') {
    
    $id_recurso = (int)$_POST['id_recurso'];

    try {
        // Solo permitimos borrar si el recurso pertenece al usuario logueado
        $stmt = $pdo->prepare("SELECT id, ruta_imagen FROM biblioteca_recursos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id_recurso, $user_id]);
        $recurso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recurso) {
            // Evitamos Path Traversal usando basename
            $nombre_archivo = basename($recurso['ruta_imagen']);
            $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . '/uploads/recursos/' . $nombre_archivo;

            // Eliminación física si existe
            if (file_exists($ruta_fisica)) {
                @unlink($ruta_fisica);
            }

            // Eliminación de registro en base de datos
            $stmtDel = $pdo->prepare("DELETE FROM biblioteca_recursos WHERE id = ?");
            $stmtDel->execute([$id_recurso]);

            header("Location: perfil.php?msg=deleted#tab-imagenes");
        } else {
            header("Location: perfil.php?error=security#tab-imagenes");
        }
        exit;
    } catch (Exception $e) {
        error_log("FALLO CRÍTICO (Borrar Recurso): " . $e->getMessage());
        header("Location: perfil.php?error=db");
        exit;
    }
}

// Cierre de seguridad: retorno al perfil
header("Location: perfil.php");
exit;