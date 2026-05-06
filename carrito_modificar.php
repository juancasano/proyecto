<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $token_recibido = $_POST['csrf_token'] ?? '';
    
    if (empty($token_recibido) || $token_recibido !== $_SESSION['csrf_token']) {
        header("Location: carrito.php?error=security");
        exit;
    }

    // ✅ IMPORTANTE: Hacer urldecode a la key
    $key_raw = $_POST['key'] ?? '';
    $key = urldecode($key_raw);  // "Producto%20A" → "Producto A"
    
    $accion = $_POST['accion'] ?? '';

    // ✅ Ahora la key decodificada coincide con la del carrito
    if (!empty($key) && isset($_SESSION['carrito'][$key])) {
        switch ($accion) {
            case 'mas':
                if ($_SESSION['carrito'][$key]['cantidad'] < 500) {
                    $_SESSION['carrito'][$key]['cantidad']++;
                }
                break;
            case 'menos':
                if ($_SESSION['carrito'][$key]['cantidad'] > 1) {
                    $_SESSION['carrito'][$key]['cantidad']--;
                } else {
                    unset($_SESSION['carrito'][$key]);
                }
                break;
            case 'eliminar':
                unset($_SESSION['carrito'][$key]);
                break;
        }
        
        // --- SEGURIDAD: SINCRONIZACIÓN ATÓMICA CORRECTA ---
        if (isset($_SESSION['user_id'])) {
            $uid = $_SESSION['user_id'];
            
            // 1. Leemos lo que hay ahora mismo en la DB para proteger compras en otras pestañas
            $stmt_read = $pdo->prepare("SELECT carrito_guardado FROM usuarios WHERE id = ?");
            $stmt_read->execute([$uid]);
            $db_cart_json = $stmt_read->fetchColumn();
            $db_cart = $db_cart_json ? json_decode($db_cart_json, true) : [];

            // 2. REPLICAMOS LA ACCIÓN EN EL CARRITO DE LA DB ANTES DE FUSIONAR
            if (isset($db_cart[$key])) {
                switch ($accion) {
                    case 'mas':
                        if ($db_cart[$key]['cantidad'] < 500) {
                            $db_cart[$key]['cantidad']++;
                        }
                        break;
                    case 'menos':
                        if ($db_cart[$key]['cantidad'] > 1) {
                            $db_cart[$key]['cantidad']--;
                        } else {
                            unset($db_cart[$key]); // ¡Matamos al zombie en la DB!
                        }
                        break;
                    case 'eliminar':
                        unset($db_cart[$key]); // ¡Matamos al zombie en la DB!
                        break;
                }
            }

            // 3. Ahora sí, fusionamos. Como el zombie ya no está ni en sesión ni en DB, desaparece para siempre.
            $carrito_fusionado = array_merge($db_cart, $_SESSION['carrito']);
            
            // 4. Actualizamos la sesión con la fusión perfecta
            $_SESSION['carrito'] = $carrito_fusionado;
            
            // 5. Guardamos el resultado final en la base de datos
            $final_json = empty($carrito_fusionado) ? NULL : json_encode($carrito_fusionado);
            $stmt_save = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
            $stmt_save->execute([$final_json, $uid]);
        }
    }

    header("Location: carrito.php");
    exit;
}
?>