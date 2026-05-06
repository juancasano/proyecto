<?php
/**
 * ARCHIVO: procesar_google.php
 * FUNCIÓN: Gestión de login/registro automático mediante Google OAuth 2.0.
 * ACTUALIZACIÓN: Blindaje CSRF con parámetro 'state' añadido.
 */

require_once 'includes/config.php';

// 1. CARGAMOS CREDENCIALES ESTRICTAMENTE DESDE EL .ENV
$client_id     = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$client_secret = $_ENV['GOOGLE_SECRET']    ?? '';

// Si las credenciales están vacías, abortamos por seguridad
if (empty($client_id) || empty($client_secret)) {
    error_log("CRÍTICO: Faltan las credenciales de Google OAuth en el archivo .env");
    die("Error de configuración de seguridad. Contacte con el administrador.");
}
$redirect_uri  = "https://www.camiglobo.com/procesar_google.php";

// 2. ¿VIENE EL CÓDIGO DE GOOGLE?
if (!isset($_GET['code'])) {
    // --- NUEVO BLINDAJE CSRF: Generamos un token de estado aleatorio ---
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

    // 👇 AÑADIMOS ESTO: Guardamos en la memoria de la sesión el destino
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $_SESSION['redirect_url'] = $_GET['redirect'];
    }

    $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online',
        'state'         => $_SESSION['oauth_state'] // Enviamos el estado a Google
    ]);
    header("Location: $url");
    exit;
}

// --- NUEVO BLINDAJE CSRF: Validamos el token de estado al volver de Google ---
if (empty($_GET['state']) || ($_GET['state'] !== ($_SESSION['oauth_state'] ?? ''))) {
    // Si el estado no coincide, es un intento de ataque. Limpiamos y echamos al usuario.
    unset($_SESSION['oauth_state']);
    error_log("Ataque CSRF en Login Google bloqueado. IP: " . $_SERVER['REMOTE_ADDR']);
    header("Location: login.php?error=security");
    exit;
}
// Si todo está bien, destruimos el token para que no se pueda reusar
unset($_SESSION['oauth_state']);


// 3. INTERCAMBIAR CÓDIGO POR TOKEN DE ACCESO
$token_url = "https://oauth2.googleapis.com/token";
$post_data = [
    'code'          => $_GET['code'],
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$data = json_decode($response, true);

if (isset($data['access_token'])) {
    // 4. OBTENER DATOS DEL USUARIO (Usando cURL en lugar de file_get_contents)
    $user_info_url = "https://www.googleapis.com/oauth2/v3/userinfo";
    
    $ch_info = curl_init($user_info_url);
    curl_setopt($ch_info, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_info, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch_info, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $data['access_token']]);
    $response_info = curl_exec($ch_info);
    curl_close($ch_info);
    
    $user_info = json_decode($response_info, true);
    $google_id = $user_info['sub'];
    $email     = $user_info['email'];
    $nombre    = $user_info['name'];

    // 5. BUSCAR O CREAR USUARIO (Alineado con tu tabla 'usuarios')
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol, carrito_guardado, google_id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (empty($user['google_id'])) {
            $pdo->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
        }
        $user_id = $user['id'];
        $rol = $user['rol'];
        $carrito_db = $user['carrito_guardado'];
    } else {
        // Generamos una contraseña hiper-segura y aleatoria para rellenar el hueco
        $clave_aleatoria = bin2hex(random_bytes(32)); // Genera un texto de 64 caracteres al azar
        $clave_hash = password_hash($clave_aleatoria, PASSWORD_BCRYPT);

        // IMPORTANTE: Tu DB usa 'cliente', no 'user'
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (google_id, nombre, email, rol, fecha_registro, password) VALUES (?, ?, ?, 'cliente', NOW(), ?)");
        $stmtInsert->execute([$google_id, $nombre, $email, $clave_hash]);
        
        $user_id = $pdo->lastInsertId();
        $rol = 'cliente';
        $carrito_db = null;
    }

    // 6. INICIAR SESIÓN
    // BLINDAJE: Cambiamos la "matrícula" de la sesión al autenticar
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['email']  = $email;
    $_SESSION['rol'] = $rol;
    $_SESSION['last_activity'] = time();

    // 7. FUSIÓN INTELIGENTE DE CARRITO (Reutilizado de procesar_login.php)
    if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    if (!empty($carrito_db)) {
        $db_cart = json_decode($carrito_db, true);
        if (is_array($db_cart)) {
            foreach ($db_cart as $clave_bd => $item_bd) {
                $fusionado = false;
                foreach ($_SESSION['carrito'] as $clave_sess => &$item_sess) {
                    $mismo_id = ($item_sess['id'] === $item_bd['id']);
                    $misma_talla = (($item_sess['talla'] ?? '') === ($item_bd['talla'] ?? ''));

                    // Sumamos cantidad si es el mismo producto base
                    if ($mismo_id && $misma_talla && $item_sess['id'] !== 'CUSTOM_PROD') {
                        $item_sess['cantidad'] += (int)$item_bd['cantidad'];
                        $fusionado = true;
                        break;
                    }
                }
                unset($item_sess); // Romper referencia en memoria

                // Añadir si no existía en la sesión actual
                if (!$fusionado) {
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

    // Persistencia inmediata tras la fusión de Google
    if (!empty($_SESSION['carrito'])) {
        $stmt_save = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
        $stmt_save->execute([json_encode($_SESSION['carrito']), $user_id]);
    }

    // --- 8. REDIRECCIÓN INTELIGENTE (WHITELIST ESTRITA, SIN OPEN REDIRECT) ---
    $allowed_redirects = [
        'checkout' => 'checkout.php',
        'perfil' => 'perfil.php',
        'productos' => 'productos.php',
        'carrito' => 'carrito.php'
    ];

    $destino = "index.php"; // Destino por defecto

    if (isset($_SESSION['redirect_url']) && array_key_exists($_SESSION['redirect_url'], $allowed_redirects)) {
        $destino = $allowed_redirects[$_SESSION['redirect_url']];
    } elseif ($rol === 'admin') {
        $destino = "admin_pedidos.php";
    }

    // Borramos el recuerdo para que no interfiera en futuros logins
    if (isset($_SESSION['redirect_url'])) {
        unset($_SESSION['redirect_url']);
    }

    // Calculamos si usar '?' o '&' para la variable msg
    $conector = (strpos($destino, '?') !== false) ? '&' : '?';
    
    header("Location: " . $destino . $conector . "msg=login_success");
    exit;
} else {
    header("Location: login.php?error=google_fail");
    exit;
}