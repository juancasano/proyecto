<?php 
require_once 'includes/config.php';

// --- NUEVO: GUARDADO PERSISTENTE DEL CARRITO ANTES DE SALIR ---
// Verificamos si el usuario está logueado para poder vincular su carrito a su perfil
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['carrito'])) {
        // Convertimos el carrito a formato texto (JSON) para guardarlo en la base de datos
        $carrito_json = json_encode($_SESSION['carrito']);
        $stmt = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
        $stmt->execute([$carrito_json, $_SESSION['user_id']]);
    } else {
        // Si el carrito está vacío al salir, limpiamos también la base de datos para no cargar datos viejos luego
        $stmt = $pdo->prepare("UPDATE usuarios SET carrito_guardado = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}
// ---------------------------------------------------------------

// 1. Vaciamos el array de sesión por completo
$_SESSION = array();

// 2. LUPA: Borramos la cookie de sesión del navegador de forma agresiva
// Esto asegura que el identificador de sesión expire físicamente en el ordenador del cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos la sesión en el servidor
session_destroy();

// 4. LUPA: Redirección limpia
// Enviamos al usuario a la home con un aviso para que sepa que ha salido correctamente
header("Location: index.php?msg=logged_out");
exit;