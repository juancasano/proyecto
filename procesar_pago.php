<?php
/**
 * ARCHIVO: procesar_pago.php
 * FUNCIÓN: Registro definitivo de pagos PayPal/Tarjeta con validación de integridad SERVER-SIDE.
 * ACTUALIZACIÓN: Implementación de Precios Dinámicos (obtenerPrecioBase) y Anti-Fraude.
 * ESTADO: BLINDADO 100%.
 */

require_once 'includes/config.php';
include 'includes/colors.php';
require_once __DIR__ . '/includes/pricing.php';

header('Content-Type: application/json');

// Capturamos el flujo de datos JSON de PayPal
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['orderID']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Payload inválido o sesión expirada.']);
    exit;
}

// Validar formato de PayPal Order ID (alfanumérico, 10-30 chars)
if (!preg_match('/^[A-Za-z0-9]{10,30}$/', $data['orderID'])) {
    echo json_encode(['success' => false, 'error' => 'ID de pago inválido.']);
    exit;
}

// 1. RECOGIDA DE DATOS BÁSICOS
$id_pago         = trim($data['orderID']);
$user_id         = (int)$_SESSION['user_id'];
// ELIMINAMOS ESTA LÍNEA PELIGROSA: $total_recibido  = (float)$data['total'];
$direccion_final = h(trim($data['direccion'] ?? 'No facilitada'));
$email_cliente   = filter_var($_SESSION['email'] ?? '', FILTER_VALIDATE_EMAIL);

// =========================================================================
// 🔒 NUEVO BLINDAJE: VERIFICACIÓN SERVER-TO-SERVER CON LA API DE PAYPAL
// =========================================================================
$base_url = PAYPAL_SANDBOX ? "https://api-m.sandbox.paypal.com" : "https://api-m.paypal.com";

// A. Obtener Token de Acceso
$ch = curl_init($base_url . "/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
// IMPORTANTE: Asegúrate de que las constantes coinciden con tu .env
curl_setopt($ch, CURLOPT_USERPWD, $_ENV['PAYPAL_CLIENT_ID'] . ":" . $_ENV['PAYPAL_SECRET']);
$res_token = curl_exec($ch);
$auth = json_decode($res_token, true);
curl_close($ch);

if (empty($auth['access_token'])) {
    error_log("CRÍTICO: Fallo de autenticación S2S con PayPal.");
    echo json_encode(['success' => false, 'error' => 'Error de seguridad con la pasarela.']);
    exit;
}

// B. Preguntar a PayPal la verdad sobre ese ID de pago
$ch_order = curl_init($base_url . "/v2/checkout/orders/$id_pago");
curl_setopt($ch_order, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_order, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $auth['access_token']]);
$res_order = curl_exec($ch_order);
$datos_paypal = json_decode($res_order, true);
curl_close($ch_order);

$estado_paypal = $datos_paypal['status'] ?? 'INVALID';

// C. Extraer el dinero REAL que ha entrado en tu cuenta
if ($estado_paypal === 'COMPLETED' || $estado_paypal === 'APPROVED') {
    // ¡Aquí está la magia! Aplastamos la mentira del JS con la verdad del banco
    $total_recibido = (float)($datos_paypal['purchase_units'][0]['amount']['value'] ?? 0);
} else {
    error_log("Intento de inyectar un ID de pago no completado: $id_pago");
    echo json_encode(['success' => false, 'error' => 'El pago no figura como completado en PayPal.']);
    exit;
}
// =========================================================================

// --- NUEVO: GUARDAR DIRECCIÓN EN LA LIBRETA (PAYPAL) ---
if (isset($data['guardar_dir']) && $data['guardar_dir'] === '1' && $user_id > 0) {
    try {
        $stmt_save_dir = $pdo->prepare("INSERT INTO user_direcciones (user_id, alias, nombre, direccion, ciudad, cp, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_save_dir->execute([
            $user_id,
            h($data['dir_alias'] ?? 'Mi Dirección'),
            h($data['dir_nombre'] ?? ''),
            h($data['dir_calle'] ?? ''),
            h($data['dir_ciudad'] ?? ''),
            h($data['dir_cp'] ?? ''),
            h($data['dir_tel'] ?? '')
        ]);
    } catch(Exception $e) {
        error_log("Error guardando nueva dirección: " . $e->getMessage());
    }
}
// 2. RECÁLCULO DE SEGURIDAD (ANTI-FRAUDE)
$total_calculado = 0;
$resumen_html_email = "";
$resumen_texto_admin = "";

if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad = (int)$item['cantidad'];
        $talla = h($item['talla'] ?? 'Única');
        // Color de la prenda para el email (color_producto si existe)
        $colorParaEmail = h($item['color_producto'] ?? $item['color'] ?? 'Estándar');
        $hexParaEmail = $colores_hex[$item['color_producto'] ?? $item['color'] ?? ''] ?? null;
        $swatchEmail = $hexParaEmail ? "<span style='display:inline-block;width:12px;height:12px;border-radius:50%;background:".$hexParaEmail.";border:1px solid rgba(0,0,0,0.15);vertical-align:middle;margin-right:4px;'></span>" : '';
        
        if (isset($item['id']) && $item['id'] === 'CUSTOM_PROD') {
            // Personalizado: recálculo de precio con la regla de front/back
            $tipo_base = strtolower($item['tipo_base'] ?? 'camiseta');
            $talla_base = strtolower($item['talla'] ?? 'camiseta');
            $extras_descripcion = $item['extras_descripcion'] ?? [];
            $front_present = !empty($item['imagen_personalizada']);
            $back_present  = !empty($item['imagen_espalda']);
            $hasDobleCara = false;
            if (is_array($extras_descripcion)) {
                foreach ($extras_descripcion as $txt) {
                    if (stripos($txt, 'Doble cara') !== false) { $hasDobleCara = true; break; }
                }
            } elseif (is_string($extras_descripcion) && stripos($extras_descripcion, 'Doble cara') !== false) {
                $hasDobleCara = true;
            }
            // Detección robusta de mangas/nuca (case-insensitive, soporta claves variadas)
            $logos_extras = $item['logos_extras'] ?? [];
            $nuca_present = false;
            $mangaDer_present = false;
            $mangaIzq_present = false;
            if (is_array($logos_extras)) {
                foreach ($logos_extras as $k => $v) {
                    $kk = strtolower((string)$k);
                    if ($kk === 'nuca') { $nuca_present = true; }
                    if (strpos($kk, 'manga') !== false) {
                        if (strpos($kk, 'der') !== false || strpos($kk, 'derecha') !== false) $mangaDer_present = true;
                        if (strpos($kk, 'izq') !== false || strpos($kk, 'izquierda') !== false) $mangaIzq_present = true;
                    }
                }
            } elseif (is_string($logos_extras)) {
                $l = strtolower($logos_extras);
                if (strpos($l, 'nuca') !== false) $nuca_present = true;
                if (strpos($l, 'manga') !== false) {
                    if (strpos($l, 'der') !== false || strpos($l, 'derecha') !== false) $mangaDer_present = true;
                    if (strpos($l, 'izq') !== false || strpos($l, 'izquierda') !== false) $mangaIzq_present = true;
                }
            }
            $doble_cara = ($front_present && $back_present && $hasDobleCara);
            $extras = [
                'doble_cara' => $doble_cara,
                'nuca' => $nuca_present,
                'manga_der' => $mangaDer_present,
                'manga_izq' => $mangaIzq_present
            ];
            if (function_exists('calcularPrecioPersonalizado')) {
                $precio_u = calcularPrecioPersonalizado($pdo, $tipo_base, $talla_base, $extras);
            } else {
                $precio_u = obtenerPrecioBase($tipo_base);
                if ($extras['doble_cara']) $precio_u += 10.0;
                if ($extras['nuca']) $precio_u += 3.0;
                if ($extras['manga_der']) $precio_u += 3.0;
                if ($extras['manga_izq']) $precio_u += 3.0;
            }
            $nombre = h($item['nombre']);
        } else {
            // Productos normales: Consultamos DB por ID
            $stmtP = $pdo->prepare("SELECT nombre, categoria FROM productos WHERE id = ?");
            $stmtP->execute([$item['id']]);
            $p_db = $stmtP->fetch();
            $nombre   = $p_db ? h($p_db['nombre']) : 'Producto desconocido';
            $categoria_p = strtolower($p_db['categoria'] ?? '');
            $talla_p = strtolower($item['talla'] ?? 'M');
            $precio_u = (float)($item['precio'] ?? 0);
            if (function_exists('calcularPrecioPersonalizado') && !empty($categoria_p)) {
                $precio_u = calcularPrecioPersonalizado($pdo, $categoria_p, $talla_p);
            }
        }

$subtotal = $precio_u * $cantidad;
        $total_calculado += $subtotal;
        
        // 👈 AÑADE ESTA LÍNEA AQUÍ PARA EL RESUMEN
        $texto_notas = !empty($item['notas']) ? " | 📝 NOTAS: " . h($item['notas']) : "";

        $resumen_texto_admin .= "• $cantidad x $nombre (Talla: $talla, Color: $colorParaEmail)$texto_notas - " . formatPrecio($subtotal) . "\n";      
        // 1. Fila normal del producto (ACTUALIZADA CON NOTA)
        $resumen_html_email .= "<tr>
            <td style='padding:10px; border-bottom:1px solid #eee;'>
                <strong>$nombre</strong><br>
                <span style='font-size:12px; color:#7f8c8d;'>Talla: $talla &nbsp;|&nbsp; Color: $swatchEmail$colorParaEmail</span>
                " . (!empty($item['notas']) ? "<br><i style='font-size:11px; color:#e67e22;'>📝 Nota: " . h($item['notas']) . "</i>" : "") . "
                <br><span style='font-size:12px; color:#27ae60; font-weight:bold;'>" . formatPrecio($precio_u) . "/ud</span>
            </td>
            <td style='padding:10px; border-bottom:1px solid #eee; text-align:center;'>x$cantidad</td>
            <td style='padding:10px; border-bottom:1px solid #eee; text-align:right; font-weight:bold;'>" . formatPrecio($subtotal) . "</td>
        </tr>";
        // 2. NUEVO: Fila extra con las fotos y detalles del diseño
        if (isset($item['id']) && $item['id'] === 'CUSTOM_PROD') {
            $img_front = $item['imagen_personalizada'] ?? '';
            $img_back  = $item['imagen_espalda'] ?? '';
            $extras_arr = $item['extras_descripcion'] ?? '';
            
            // Convertimos los extras en texto si vienen como array
            $extras_str = is_array($extras_arr) ? implode(', ', $extras_arr) : $extras_arr;

            if ($img_front || $img_back || $extras_str) {
                // Aseguramos que la ruta de la imagen sea absoluta para que se vea dentro de Gmail/Outlook
                $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host  = $_SERVER['HTTP_HOST'] ?? 'www.camiglobo.com';
                $src_front = (strpos($img_front, 'http') === 0 || strpos($img_front, 'data:image') === 0) ? $img_front : $proto . $host . '/' . ltrim($img_front, '/');
                $src_back  = (strpos($img_back, 'http') === 0 || strpos($img_back, 'data:image') === 0) ? $img_back : $proto . $host . '/' . ltrim($img_back, '/');

                $src_front = htmlspecialchars($src_front, ENT_QUOTES, 'UTF-8');
                $src_back  = htmlspecialchars($src_back, ENT_QUOTES, 'UTF-8');
                $extras_str = htmlspecialchars($extras_str, ENT_QUOTES, 'UTF-8');

                $resumen_html_email .= "<tr><td colspan='3' style='padding:15px 10px; border-bottom:2px solid #ddd; background:#fafafa;'>";
                
                if ($extras_str) {
                    $resumen_html_email .= "<p style='margin:0 0 15px 0; font-size:13px; color:#e74c3c;'><strong>Extras seleccionados:</strong> $extras_str</p>";
                }
                
                if ($img_front) {
                    $resumen_html_email .= "<div style='display:inline-block; margin-right:20px; text-align:center; vertical-align:top;'>
                        <strong style='font-size:11px; color:#95a5a6; display:block; margin-bottom:5px;'>DISEÑO FRENTE</strong>
                        <img src='$src_front' style='max-width:180px; border:1px solid #ccc; border-radius:8px; background:white; padding:5px;'>
                    </div>";
                }
                
                if ($img_back) {
                    $resumen_html_email .= "<div style='display:inline-block; text-align:center; vertical-align:top;'>
                        <strong style='font-size:11px; color:#95a5a6; display:block; margin-bottom:5px;'>DISEÑO ESPALDA</strong>
                        <img src='$src_back' style='max-width:180px; border:1px solid #ccc; border-radius:8px; background:white; padding:5px;'>
                    </div>";
                }
                
                $resumen_html_email .= "</td></tr>";
            }
        }

// 3. CÁLCULO DE ENVÍO USANDO CONSTANTES
$costo_envio = ($total_calculado >= ENVIO_GRATIS_UMBRAL) ? 0 : ENVIO_COSTE;
$total_real_servidor = $total_calculado + $costo_envio;

// VERIFICACIÓN: ningún producto a 0€
$estado_pedido = 'Pagado';
if ($total_calculado <= 0 && !empty($_SESSION['carrito'])) {
    $estado_pedido = 'Revision Fraudulenta';
    $mensaje_alerta = "PRECIO CERO PAYPAL: ID $id_pago tiene total calculado 0€. Revisar productos.";
    error_log($mensaje_alerta);
    if (function_exists('auditLog')) {
        auditLog('ALERTA_PRECIO', $mensaje_alerta);
    }
}

// 4. VERIFICACIÓN DE FRAUDE
// Permitimos una diferencia de 1 céntimo por redondeos
if (abs($total_real_servidor - $total_recibido) > 0.01) {
    $estado_pedido = 'Revision Fraudulenta';
    $mensaje_alerta = "FRAUDE DETECTADO: ID $id_pago pagó " . formatPrecio($total_recibido) . " pero debía ser " . formatPrecio($total_real_servidor);
    error_log($mensaje_alerta);
    
    if (function_exists('auditLog')) {
        auditLog('ALERTA_FRAUDE', $mensaje_alerta);
    }
}

try {
    $pdo->beginTransaction();

    // A. EVITAR DUPLICADOS
    $check = $pdo->prepare("SELECT id FROM pedidos WHERE id_pago = ?");
    $check->execute([$id_pago]);
    if ($check->fetch()) {
        echo json_encode(['success' => true]); 
        exit;
    }

  // B. INSERTAR CABECERA CON EL ESTADO
    $fecha_actual = date('Y-m-d H:i:s'); // Forzamos la hora de España
    $sqlP = "INSERT INTO pedidos (id_pago, user_id, productos, total, direccion_completa, estado, fecha)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtP = $pdo->prepare($sqlP);
    $stmtP->execute([$id_pago, $user_id, $resumen_texto_admin, $total_real_servidor, $direccion_final, $estado_pedido, $fecha_actual]);
    $pedido_id_real = $pdo->lastInsertId();

    // B1. EXTRAER DATOS DEL PAYER DE PAYPAL (para auditoría y chargebacks)
    $payer_id = $datos_paypal['payer']['payer_id'] ?? NULL;
    $payer_email = $datos_paypal['payer']['email_address'] ?? NULL;
    $payer_name = isset($datos_paypal['payer']['name'])
        ? ($datos_paypal['payer']['name']['given_name'] ?? '') . ' ' . ($datos_paypal['payer']['name']['surname'] ?? '')
        : NULL;

    // B2. INSERTAR EN TABLA PAGOS (auditoría de pagos)
    $sqlPago = "INSERT INTO pagos (pedido_id, paypal_order_id, payer_id, payer_email, payer_name, amount, currency_code, status, payment_method, captured_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'COMPLETED', 'PAYPAL', NOW())";
    $stmtPago = $pdo->prepare($sqlPago);
    $stmtPago->execute([
        $pedido_id_real,
        $id_pago,
        $payer_id,
        $payer_email,
        $payer_name,
        $total_recibido,
        'EUR'
    ]);
    $pago_id = $pdo->lastInsertId();

    // B3. ACTUALIZAR PEDIDO CON REFERENCIA AL PAGO
    $pdo->prepare("UPDATE pedidos SET payment_id = ? WHERE id = ?")->execute([$pago_id, $pedido_id_real]);

// C. INSERCIÓN EN DETALLES
// --- ACTUALIZACIÓN: Añadida columna notas ---
    $sqlD = "INSERT INTO pedidos_detalle (pedido_id, producto_id, nombre, talla, color, imagen_custom, imagen_espalda, logos_extras, extras_descripcion, notas, cantidad, precio_unitario) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtD = $pdo->prepare($sqlD);
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad = (int)$item['cantidad'];
        $talla    = h($item['talla'] ?? 'Única');
        $color    = h($item['color_producto'] ?? $item['color'] ?? 'Estándar');
        
        if (isset($item['id']) && $item['id'] === 'CUSTOM_PROD') {
            // Datos para productos creados en el Personalizador
            $p_id       = 'CUSTOM';
            $nombre_p   = h($item['nombre']);
            
            // ✅ RE-VERIFICACIÓN DE PRECIO DB PARA EL INSERT (con extras)
            $tipo_base_det = strtolower($item['tipo_base'] ?? 'camiseta');
            $extras_det = [
                'doble_cara' => !empty($item['precio']) && ($item['precio'] > 0) && !empty($item['extras_descripcion']) ? (is_array($item['extras_descripcion']) ? (in_array('Doble cara (+10€)', $item['extras_descripcion']) || in_array('Doble Cara (+10€)', $item['extras_descripcion'])) : (stripos($item['extras_descripcion'], 'Doble cara') !== false)) : false,
                'nuca' => false,
                'manga_der' => false,
                'manga_izq' => false,
            ];
            // Detectar mangas/nuca desde extras_descripcion
            $extras_desc_det = $item['extras_descripcion'] ?? [];
            if (is_array($extras_desc_det)) {
                foreach ($extras_desc_det as $ed) {
                    $edl = strtolower($ed);
                    if (strpos($edl, 'nuca') !== false) $extras_det['nuca'] = true;
                    if (strpos($edl, 'manga derecha') !== false || strpos($edl, 'manga der') !== false) $extras_det['manga_der'] = true;
                    if (strpos($edl, 'manga izquierda') !== false || strpos($edl, 'manga izq') !== false) $extras_det['manga_izq'] = true;
                }
            } elseif (is_string($extras_desc_det)) {
                $edl = strtolower($extras_desc_det);
                if (strpos($edl, 'nuca') !== false) $extras_det['nuca'] = true;
                if (strpos($edl, 'manga derecha') !== false || strpos($edl, 'manga der') !== false) $extras_det['manga_der'] = true;
                if (strpos($edl, 'manga izquierda') !== false || strpos($edl, 'manga izq') !== false) $extras_det['manga_izq'] = true;
            }
            // Doble cara: detectar desde extras_descripcion
            if (is_array($extras_desc_det)) {
                foreach ($extras_desc_det as $ed) {
                    if (stripos($ed, 'Doble cara') !== false) { $extras_det['doble_cara'] = true; break; }
                }
            } elseif (is_string($extras_desc_det) && stripos($extras_desc_det, 'Doble cara') !== false) {
                $extras_det['doble_cara'] = true;
            }
            $talla_det = strtolower($item['talla'] ?? 'Única');
            if (function_exists('calcularPrecioPersonalizado')) {
                $precio_u = calcularPrecioPersonalizado($pdo, $tipo_base_det, $talla_det, $extras_det);
            } else {
                $precio_u = obtenerPrecioBase($tipo_base_det);
                if ($precio_u <= 0) $precio_u = obtenerPrecioBase('camiseta'); // fallback 26€
                if ($extras_det['doble_cara']) $precio_u += 10.0;
                if ($extras_det['nuca']) $precio_u += 3.0;
                if ($extras_det['manga_der']) $precio_u += 3.0;
                if ($extras_det['manga_izq']) $precio_u += 3.0;
            }

            $img_custom = $item['imagen_personalizada'] ?? NULL;
            
            // --- NUEVO: CAPTURA DE EXTRAS DEL DISEÑO ---
            $img_espalda = $item['imagen_espalda'] ?? NULL;
            // Si los logos vienen en formato array, los pasamos a JSON para guardarlos en la celda de texto
            $logos_ext   = isset($item['logos_extras']) ? (is_array($item['logos_extras']) ? json_encode($item['logos_extras']) : $item['logos_extras']) : NULL;
// Convertimos el array de extras en un texto separado por comas para que MySQL lo acepte
            $extras_desc = isset($item['extras_descripcion']) ? 
                           (is_array($item['extras_descripcion']) ? implode(', ', $item['extras_descripcion']) : $item['extras_descripcion']) 
                           : NULL;
        } else {
            // Datos para productos estándar
            $stmtP2 = $pdo->prepare("SELECT nombre, categoria FROM productos WHERE id = ?");
            $stmtP2->execute([$item['id']]);
            $p_db2 = $stmtP2->fetch();
            
            $p_id       = $item['id'];
            $nombre_p   = $p_db2 ? h($p_db2['nombre']) : 'Producto desconocido';
            $cat_p2     = strtolower($p_db2['categoria'] ?? '');
            $talla_p2   = strtolower($item['talla'] ?? 'M');
            $precio_u   = (float)($item['precio'] ?? 0);
            if (function_exists('calcularPrecioPersonalizado') && !empty($cat_p2)) {
                $precio_u = calcularPrecioPersonalizado($pdo, $cat_p2, $talla_p2);
            }
            $img_custom = NULL;
            
            // --- NUEVO: VACIAR VARIABLES PARA PRODUCTOS NORMALES ---
            $img_espalda = NULL;
            $logos_ext   = NULL;
            $extras_desc = NULL;
        }

        // Ejecución de la inserción con las nuevas variables
        $stmtD->execute([
            $pedido_id_real, 
            $p_id, 
            $nombre_p, 
            $talla, 
            $color, 
            $img_custom, 
            $img_espalda,       
            $logos_ext,         
            $extras_desc,       
            $item['notas'] ?? NULL, // 👈 AÑADIMOS LA NOTA AQUÍ
            $cantidad, 
            $precio_u
        ]);
    }

    // --- AQUÍ CERRAMOS EL PEDIDO EN LA BASE DE DATOS DEFINITIVAMENTE ---
    $pdo->commit();
    // D. ENVÍO DE EMAIL PREMIUM AL CLIENTE Y AVISO AL VENDEDOR
    if (!empty($email_cliente) && $estado_pedido === 'Pagado') {
        
        // --- 1. EMAIL AL CLIENTE ---
        $asunto = "🛍️ Confirmación de Pedido #$id_pago - Camiglobo";
        $cuerpo = "
        <div style='text-align: center; margin-bottom: 25px;'>
            <div style='font-size: 45px; margin-bottom: 5px;'>✅</div>
            <h1 style='color:#27ae60; margin: 0; font-size: 26px;'>¡Pago Confirmado!</h1>
            <p style='color:#7f8c8d; font-size: 15px; margin: 8px 0 0 0;'>Tu pedido ya está en nuestro taller. Aquí tienes el detalle:</p>
        </div>
        
                <table style='width:100%; border-collapse:collapse; margin-top:20px;'>
                    <thead>
                        <tr style='background:#f9f9f9;'>
                            <th style='text-align:left; padding:10px;'>Producto</th>
                            <th style='padding:10px; text-align:center;'>Cant.</th>
                            <th style='text-align:right; padding:10px;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>$resumen_html_email</tbody>
                </table>
                <div style='margin-top:30px; text-align:right; border-top:2px dashed #eee; padding-top:20px;'>
                    <p style='font-size:16px; margin:5px 0;'>Subtotal: <b>" . formatPrecio($total_calculado) . "</b></p>
                    <p style='font-size:16px; margin:5px 0;'>Envío: <b>" . ($costo_envio == 0 ? 'GRATIS' : formatPrecio($costo_envio)) . "</b></p>
                    <p style='color:#e74c3c; font-size:24px; font-weight:900; margin:15px 0 0 0;'>TOTAL: " . formatPrecio($total_real_servidor) . "</p>
                </div>
                <div style='margin-top:30px; padding:20px; background:#f8f9fa; border-radius:10px;'>
                    <p style='margin:5px 0; color:#2c3e50;'><strong>📍 Dirección de envío:</strong><br>" . nl2br($direccion_final) . "</p>
                </div>
                <p style='text-align:center; margin-top:30px; color:#95a5a6; font-size:12px;'>
                    ID de Pedido: <b>#$id_pago</b><br>
                    Si tienes alguna duda, responde a este email.
                </p>";
        
        try { enviarEmail($email_cliente, $asunto, $cuerpo, '#27ae60'); } catch (Exception $e) { error_log("Error email: " . $e->getMessage()); }

        // --- 2. AVISO POR EMAIL AL VENDEDOR (ADMIN) ---
        $email_admin = "camigloboshop@gmail.com";
        $asunto_admin = "🚨 NUEVO PEDIDO PAGADO (#$id_pago) - " . formatPrecio($total_real_servidor);
        $cuerpo_admin = "
        <div style='font-family: Arial; padding: 20px;'>
            <h2 style='color: #27ae60;'>¡Has hecho una venta por PayPal/Tarjeta! 💸</h2>
            <p><strong>Referencia:</strong> $id_pago</p>
            <p><strong>Email del cliente:</strong> $email_cliente</p>
            <p><strong>Dirección de entrega:</strong><br>" . nl2br($direccion_final) . "</p>
            <hr>
            <h3>¿Qué ha comprado?</h3>
            <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 600px;'>
                <tr style='background: #eee;'><th>Producto</th><th>Cant.</th><th>Total</th></tr>
                $resumen_html_email
            </table>
            <h3 style='color: #e74c3c;'>Total Ingresado: " . formatPrecio($total_real_servidor) . "</h3>
            <p>Entra a tu panel de administrador de Camiglobo para ver la imagen personalizada si la hay.</p>
        </div>";
        
        try { enviarEmail($email_admin, $asunto_admin, $cuerpo_admin); } catch (Exception $e) { error_log("Error aviso admin: " . $e->getMessage()); }
    }

    // E. LIMPIEZA DEL CARRITO
    // Lo hacemos aquí porque si llegamos a este punto, el pedido ya es real en la DB
    unset($_SESSION['carrito']);
    if ($user_id > 0) {
        $pdo->prepare("UPDATE usuarios SET carrito_guardado = NULL WHERE id = ?")->execute([$user_id]);
    }

    // RESPUESTA DE ÉXITO PARA EL NAVEGADOR
    echo json_encode(['success' => true, 'pedido_id' => $pedido_id_real]);

} catch (Exception $e) {
    // PROTECCIÓN CRÍTICA: Si el error ocurre ANTES del commit, deshacemos todo.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("FALLO CRÍTICO EN PROCESAR_PAGO: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error al registrar el pedido. Los datos de pago están a salvo, contacta con soporte.'
    ]);
}
?>
