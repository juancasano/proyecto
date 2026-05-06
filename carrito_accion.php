<?php
require_once __DIR__ . '/includes/pricing.php';
/**
 * ARCHIVO: carrito_accion.php
 * FUNCIÓN: Gestión de adición/eliminación al carrito con validación de integridad y CSRF.
 * ACTUALIZACIÓN: Protección CSRF obligatoria (Auditoría #5), validación estricta y paso a POST total.
 */

require_once 'includes/config.php';
include 'includes/colors.php';

// --- 1. LÓGICA DE AÑADIR AL CARRITO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    
    // 🛡️ BLINDAJE CSRF: Verificamos el token de seguridad
    $token_recibido = $_POST['csrf_token'] ?? '';
    if (empty($token_recibido) || $token_recibido !== ($_SESSION['csrf_token'] ?? '')) {
        if(function_exists('auditLog')) auditLog('CSRF_FAIL', "Intento de añadir al carrito sin token válido. IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: productos.php?error=security");
        exit;
    }

    // 1.1 SANEAMIENTO Y CAPTURA
    $id = trim($_POST['id']); // Capturamos el ID tal cual (alfanumérico)
    $cant = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
    if ($cant === false || $cant < 1) $cant = 1; 
    
    $talla = htmlspecialchars(trim($_POST['talla'] ?? 'Única'), ENT_QUOTES, 'UTF-8');
    $color = htmlspecialchars(trim($_POST['color'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Mapa hex centralizado (includes/colors.php)
    $color_hex = $colores_hex[$color] ?? '#cccccc';

    // 1.2 VERIFICACIÓN CRÍTICA EN DB
    $stmt = $pdo->prepare("SELECT id, nombre, precio, imagen_url, categoria FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if ($p) {
        // 1.3 GENERACIÓN DE CLAVE ÚNICA (Evita colisiones de tallas)
        // Usamos urlencode en lugar de str_replace para manejar espacios y caracteres raros de forma segura
        $cart_key = $p['id'] . "_" . urlencode($talla) . "_" . urlencode($color);

        // Precio recalculado 100% server-side (ignorar cualquier valor del cliente)
        $precio_final = (float)$p['precio'];
        if (function_exists('calcularPrecioPersonalizado') && isset($p['categoria']) && !empty($p['categoria'])) {
            $precio_final = calcularPrecioPersonalizado($pdo, strtolower($p['categoria']), strtolower($talla));
        }


        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (isset($_SESSION['carrito'][$cart_key])) {
            // Límite de seguridad: no dejar que pidan 1 millón de camisetas de golpe
            if (($_SESSION['carrito'][$cart_key]['cantidad'] + $cant) > 500) {
                $_SESSION['carrito'][$cart_key]['cantidad'] = 500;
            } else {
                $_SESSION['carrito'][$cart_key]['cantidad'] += $cant;
            }
        } else {
            $_SESSION['carrito'][$cart_key] = [
                'id'        => $p['id'], 
                'nombre'    => $p['nombre'],
                'precio'    => $precio_final,
                'imagen'    => $p['imagen_url'],
                'cantidad'  => $cant,
                'talla'     => $talla,
                'color'     => $color,
                'color_hex' => $color_hex,
                // Campos de diseño personalizado (para que el carrito refleje el precio correcto)
                'imagen_personalizada' => $_POST['imagen_personalizada'] ?? '',
                'imagen_espalda' => $_POST['imagen_espalda'] ?? '',
                'extras_descripcion' => $_POST['extras_descripcion'] ?? [],
                'logos_extras' => $_POST['logos_extras'] ?? [],
                'tipo_base' => $_POST['tipo_base'] ?? '',
            ];
        }

     // ---  SINCRONIZACIÓN ATÓMICA Y FUSIÓN (Hallazgo 1 y 2) ---
        if (isset($_SESSION['user_id'])) {
            $uid = $_SESSION['user_id'];
            
            // 1. Leemos la "Verdad" de la Base de Datos para no machacar otras pestañas
            $stmt_read = $pdo->prepare("SELECT carrito_guardado FROM usuarios WHERE id = ?");
            $stmt_read->execute([$uid]);
            $db_cart_json = $stmt_read->fetchColumn();
            $db_cart = json_decode($db_cart_json ?: '[]', true);

            // 2. FUSIÓN INTELIGENTE: array_replace prioriza los cambios de la sesión actual
            // pero mantiene lo que se haya añadido en otras pestañas.
            $_SESSION['carrito'] = array_replace($db_cart, $_SESSION['carrito']);

            // 3. Guardamos el resultado final en la DB
            $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?")
                ->execute([json_encode($_SESSION['carrito']), $uid]);
        }

        header("Location: carrito.php?msg=added");
        exit; // 🛡️ Detenemos ejecución tras redirigir
    } else {
        // Error si el ID de producto no existe en la DB
        error_log("FALLO DE INTEGRIDAD: ID inexistente: " . $id);
        header("Location: productos.php?error=invalid_id");
        exit;
    }
} // <--- Cierre del bloque POST principal

// --- 2. LÓGICA DE BORRADO DE CARRITO (POST ESTRICTO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    
    // 🛡️ BLINDAJE CSRF: Verificamos token de seguridad por POST
    $token_recibido = $_POST['csrf_token'] ?? '';
    if (empty($token_recibido) || $token_recibido !== ($_SESSION['csrf_token'] ?? '')) {
        if(function_exists('auditLog')) auditLog('CSRF_FAIL_CART', "Intento de borrar carrito sin token válido.");
        header("Location: carrito.php?error=security");
        exit;
    }

    // Saneamiento de la clave (Recibida por POST)
    $key = htmlspecialchars(trim($_POST['eliminar']), ENT_QUOTES, 'UTF-8');
    
    if (isset($_SESSION['carrito'][$key])) {
        // Borramos de la sesión actual
        unset($_SESSION['carrito'][$key]);

        // --- PERSISTENCIA DE BORRADO (Hallazgo 2) ---
        if (isset($_SESSION['user_id'])) {
            $uid = $_SESSION['user_id'];
            
            // Leemos DB para sincronizar el borrado atómico
            $stmt_read = $pdo->prepare("SELECT carrito_guardado FROM usuarios WHERE id = ?");
            $stmt_read->execute([$uid]);
            $db_cart = json_decode($stmt_read->fetchColumn() ?: '[]', true);

            // Borramos el item del array de la DB también
            if (isset($db_cart[$key])) {
                unset($db_cart[$key]);
            }

            // Sobrescribimos la DB con el carrito ya limpio de ese item
            $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?")
                ->execute([json_encode($db_cart), $uid]);
        }
    }
    
    header("Location: carrito.php?msg=removed");
    exit;
}

// 🛡️ Cierre de seguridad: Si no es un POST válido (añadir o eliminar), fuera
header("Location: productos.php");
exit;
