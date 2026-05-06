<?php
/**
 * ARCHIVO: procesar_pedido.php
 * FINALIDAD: Registro blindado de pedidos Bizum/Transferencia con recálculo de precios.
 * ACTUALIZACIÓN: Inserción COMPLETA en pedidos_detalle (con espaldas y logos) y correos con fotos.
 */

require_once 'includes/config.php';
include 'includes/colors.php';
require_once __DIR__ . '/includes/pricing.php';

// 1. SEGURIDAD: Solo procesamos si el método es POST, hay carrito, hay sesión activa y CSRF válido
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['carrito']) || empty($_SESSION['user_id'])) {
    header("Location: carrito.php");
    exit;
}

// Validación CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("Error de seguridad: Token CSRF inválido.");
}

$user_id = (int)$_SESSION['user_id'];
$direccion_final = h($_POST['direccion'] ?? 'No especificada');
$total_recibido = (float)($_POST['total'] ?? 0);

// --- NUEVO: GUARDAR DIRECCIÓN EN LA LIBRETA (BIZUM) ---
if (isset($_POST['guardar_dir']) && $_POST['guardar_dir'] === '1' && $user_id > 0) {
    try {
        $stmt_save_dir = $pdo->prepare("INSERT INTO user_direcciones (user_id, alias, nombre, direccion, ciudad, cp, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_save_dir->execute([
            $user_id,
            h($_POST['dir_alias'] ?? 'Mi Dirección'),
            h($_POST['dir_nombre'] ?? ''),
            h($_POST['dir_calle'] ?? ''),
            h($_POST['dir_ciudad'] ?? ''),
            h($_POST['dir_cp'] ?? ''),
            h($_POST['dir_tel'] ?? '')
        ]);
    } catch(Exception $e) {
        error_log("Error guardando nueva dirección: " . $e->getMessage());
    }
}
// Genera un ID criptográficamente seguro de 8 caracteres (Ej: BZ-A1B2C3D4)
$id_pago = "BZ-" . strtoupper(bin2hex(random_bytes(4)));

// Variables para cálculos
$total_calculado = 0;
$resumen_db = "";
$resumen_html_email = "";
$resumen_wa = "👕 *NUEVO PEDIDO CAMIGLOBO* (Ref: $id_pago)\n"; 
$resumen_wa .= "---------------------------\n";

// Array temporal para guardar los datos y no hacer doble consulta a la DB
$detalles_para_insertar = [];

// 2. RECÁLCULO Y CONSTRUCCIÓN DE RESÚMENES 
foreach($_SESSION['carrito'] as $item) {
    $cantidad = (int)$item['cantidad'];
    $talla = h($item['talla'] ?? 'M');
$color = h($item['color_producto'] ?? $item['color'] ?? 'Estándar');
    $hexEmail = $colores_hex[$item['color_producto'] ?? $item['color'] ?? ''] ?? null;
    $swatchEmail = $hexEmail ? "<span style='display:inline-block;width:12px;height:12px;border-radius:50%;background:".$hexEmail.";border:1px solid rgba(0,0,0,0.15);vertical-align:middle;margin-right:4px;'></span>" : '';
    // Variables por defecto
    $img_custom = NULL;
    $img_espalda = NULL;
    $logos_ext = NULL;
    $extras_desc = NULL;
    $producto_id = $item['id'] ?? '0';

    if (isset($item['id']) && $item['id'] === 'CUSTOM_PROD') {
        // --- PRODUCTO PERSONALIZADO ---
        $tipo_base = strtolower($item['tipo_base'] ?? 'camiseta');
        $talla_base = strtolower($item['talla'] ?? 'M');
        // Extras desde carrito (con validación de front/back para doble cara)
        $front_present = !empty($item['imagen_personalizada']);
        $back_present  = !empty($item['imagen_espalda']);
        $extras_descripcion = $item['extras_descripcion'] ?? [];
        $hasDobleCara = false;
        if (is_array($extras_descripcion)) {
            foreach ($extras_descripcion as $txt) {
                if (stripos($txt, 'Doble cara') !== false) { $hasDobleCara = true; break; }
            }
        } elseif (is_string($extras_descripcion) && stripos($extras_descripcion, 'Doble cara') !== false) {
            $hasDobleCara = true;
        }
        $doble_cara = ($front_present && $back_present && $hasDobleCara);
        $extras = [
            'doble_cara' => $doble_cara,
            'nuca' => false,
            'manga_der' => false,
            'manga_izq' => false,
        ];
        // Detección robusta de mangas/nuca desde logos_extras
        $logos_extras = $item['logos_extras'] ?? [];
        if (is_array($logos_extras)) {
            foreach ($logos_extras as $k => $v) {
                $kk = strtolower((string)$k);
                if ($kk === 'nuca') { $extras['nuca'] = true; }
                if (strpos($kk, 'manga') !== false) {
                    if (strpos($kk, 'der') !== false || strpos($kk, 'derecha') !== false) $extras['manga_der'] = true;
                    if (strpos($kk, 'izq') !== false || strpos($kk, 'izquierda') !== false) $extras['manga_izq'] = true;
                }
            }
        } elseif (is_string($logos_extras)) {
            $l = strtolower($logos_extras);
            if (strpos($l, 'nuca') !== false) $extras['nuca'] = true;
            if (strpos($l, 'manga') !== false) {
                if (strpos($l, 'der') !== false || strpos($l, 'derecha') !== false) $extras['manga_der'] = true;
                if (strpos($l, 'izq') !== false || strpos($l, 'izquierda') !== false) $extras['manga_izq'] = true;
            }
        }

        if (function_exists('calcularPrecioPersonalizado')) {
            $precio_u = calcularPrecioPersonalizado($pdo, $tipo_base, $talla_base, $extras);
        } else {
            $precio_base = obtenerPrecioBase($tipo_base);
            $precio_u = $precio_base;
            if ($extras['doble_cara']) $precio_u += 10.0;
            if ($extras['nuca']) $precio_u += 3.0;
            if ($extras['manga_der']) $precio_u += 3.0;
            if ($extras['manga_izq']) $precio_u += 3.0;
        }
        $nombre   = h($item['nombre']);
        $img_custom = $item['imagen_personalizada'] ?? NULL;
        $img_espalda = $item['imagen_espalda'] ?? NULL;
        $logos_ext = isset($item['logos_extras']) ? (is_array($item['logos_extras']) ? json_encode($item['logos_extras']) : $item['logos_extras']) : NULL;
        $extras_desc = isset($item['extras_descripcion']) ? (is_array($extras_descripcion) ? implode(', ', $extras_descripcion) : $extras_descripcion) : NULL;
        $producto_id = 'CUSTOM';
        
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $url_foto  = $protocolo . $_SERVER['HTTP_HOST'] . "/" . ltrim($item['imagen_personalizada'] ?? '', '/');
        $resumen_wa .= "🎨 *{$cantidad}x PERSONALIZADO*\n   Base: ".ucfirst($tipo_base)." | Talla: $talla | Color: $color\n   🖼️ Diseño: $url_foto\n";
    } else {
        // --- PRODUCTO NORMAL DEL CATÁLOGO ---
        $stmt = $pdo->prepare("SELECT nombre, precio, categoria FROM productos WHERE id = ?");
        $stmt->execute([$item['id']]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        $precio_u = $p ? (float)$p['precio'] : 0;
        $nombre   = $p ? h($p['nombre']) : 'Producto desconocido';
        $cat_p    = strtolower($p['categoria'] ?? '');
        $talla_p  = strtolower($item['talla'] ?? 'M');
        if (function_exists('calcularPrecioPersonalizado') && !empty($cat_p)) {
            $precio_u = calcularPrecioPersonalizado($pdo, $cat_p, $talla_p);
        }
        $resumen_wa .= "✅ *{$cantidad}x* $nombre\n   📏 Talla: $talla | 🎨 Color: $color\n";
    }

    // --- CÁLCULOS Y NOTAS (Ahora que ya tenemos $nombre y $precio_u) ---
    $subtotal = $precio_u * $cantidad;
    $total_calculado += $subtotal;
    $texto_notas = !empty($item['notas']) ? " | 📝 NOTAS: " . h($item['notas']) : "";

    // Guardamos en el array para la base de datos
    $detalles_para_insertar[] = [
        'producto_id' => $producto_id,
        'nombre'      => $nombre,
        'talla'       => $talla,
        'color'       => $color,
        'img_custom'  => $img_custom,
        'img_espalda' => $img_espalda,
        'logos_ext'   => $logos_ext,
        'extras_desc' => $extras_desc,
        'notas'       => $item['notas'] ?? NULL, // 👈 ¡Faltaba añadir esta línea!
        'cantidad'    => $cantidad,
        'precio_u'    => $precio_u
    ];

    // Resumen texto (Admin)
    $resumen_db .= "• $cantidad x $nombre (Talla: $talla | Color: $color)$texto_notas - " . formatPrecio($subtotal) . "\n";
    
    // Resumen HTML (Email)
    $resumen_html_email .= "<tr>
        <td style='padding:10px; border-bottom:1px solid #eee;'>
            <strong>$nombre</strong><br>
            <span style='font-size:12px; color:#666;'>📏 Talla: <b>$talla</b> &nbsp;|&nbsp; 🎨 Color: $swatchEmail<b>$color</b></span>
            " . (!empty($item['notas']) ? "<br><i style='font-size:11px; color:#e67e22;'>📝 Nota: " . h($item['notas']) . "</i>" : "") . "
        </td>
        <td style='padding:10px; border-bottom:1px solid #eee; text-align:center;'>x$cantidad</td>
        <td style='padding:10px; border-bottom:1px solid #eee; text-align:right; font-weight:bold;'>" . formatPrecio($subtotal) . "</td>
    </tr>";

    // Fila de fotos en el Email (Solo si es personalizado)
    if ($producto_id === 'CUSTOM' && ($img_custom || $img_espalda || $extras_desc)) {
        $src_front = ($img_custom && (strpos($img_custom, 'http') === 0)) ? $img_custom : 'https://www.camiglobo.com/' . ltrim($img_custom ?? '', '/');
        $src_back  = ($img_espalda && (strpos($img_espalda, 'http') === 0)) ? $img_espalda : 'https://www.camiglobo.com/' . ltrim($img_espalda ?? '', '/');
        $src_front = htmlspecialchars($src_front, ENT_QUOTES, 'UTF-8');
        $src_back  = htmlspecialchars($src_back, ENT_QUOTES, 'UTF-8');

        $resumen_html_email .= "<tr><td colspan='3' style='padding:15px 10px; border-bottom:2px solid #ddd; background:#fafafa;'>";
        if ($extras_desc) { $resumen_html_email .= "<p style='margin:0 0 10px 0; font-size:12px; color:#e74c3c;'><strong>Extras:</strong> " . h($extras_desc) . "</p>"; }
        if ($img_custom) {
            $resumen_html_email .= "<div style='display:inline-block; margin-right:15px; text-align:center;'><strong style='font-size:9px; color:#999; display:block;'>FRENTE</strong><img src='$src_front' style='max-width:130px; border:1px solid #ccc; border-radius:8px; background:white;'></div>";
        }
        if ($img_espalda) {
            $resumen_html_email .= "<div style='display:inline-block; text-align:center;'><strong style='font-size:9px; color:#999; display:block;'>ESPALDA</strong><img src='$src_back' style='max-width:130px; border:1px solid #ccc; border-radius:8px; background:white;'></div>";
        }
        $resumen_html_email .= "</td></tr>";
    }
} // FIN DEL FOREACH

// VERIFICACIÓN: ningún producto a 0€ → rechazar pedido
$estado_pedido = 'Pendiente Pago';
if ($total_calculado <= 0 && !empty($_SESSION['carrito'])) {
    if (function_exists('auditLog')) {
        auditLog('ALERTA_PRECIO', "Intento de pedido a 0€ por usuario " . $_SESSION['user_id']);
    }
    header("Location: checkout.php?error=zero_price");
    exit;
}

// 3. CÁLCULO DE ENVÍO USANDO CONSTANTES GLOBALES
$costo_envio = ($total_calculado >= ENVIO_GRATIS_UMBRAL) ? 0 : ENVIO_COSTE;
$total_final_servidor = $total_calculado + $costo_envio;

// VERIFICACIÓN DE FRAUDE (comparar total del cliente vs servidor)
$total_recibido_cliente = (float)($_POST['total'] ?? 0);
if ($total_recibido_cliente > 0 && abs($total_final_servidor - $total_recibido_cliente) > 0.01) {
    $estado_pedido = 'Revisión Fraudulenta';
    $mensaje_alerta = "FRAUDE BIZUM: Ref $id_pago envió " . formatPrecio($total_recibido_cliente) . " pero debía ser " . formatPrecio($total_final_servidor);
    error_log($mensaje_alerta);
    if (function_exists('auditLog')) {
        auditLog('ALERTA_FRAUDE', $mensaje_alerta);
    }
}

// --- AÑADIR FILA DE ENVÍO A LOS RESÚMENES ---
$txt_envio = ($costo_envio > 0) ? formatPrecio($costo_envio) : "GRATIS";
$resumen_db .= "• Gastos de Envío: $txt_envio\n";
$resumen_html_email .= "<tr>
    <td style='padding:10px; border-bottom:1px solid #eee; color:#888; font-style:italic;'>Gastos de Envío</td>
    <td style='padding:10px; border-bottom:1px solid #eee; text-align:center; color:#888;'>-</td>
    <td style='padding:10px; border-bottom:1px solid #eee; text-align:right; color:#888;'>$txt_envio</td>
</tr>";

// Finalización del WhatsApp TOTALMENTE ACTUALIZADO
$resumen_wa .= "---------------------------\n";
$resumen_wa .= "💰 *TOTAL: " . formatPrecio($total_final_servidor) . "*\n";
if($costo_envio > 0) $resumen_wa .= "🚚 (Incluye " . formatPrecio($costo_envio) . " envío)\n";
else $resumen_wa .= "🚚 *¡Envío GRATIS aplicado!*\n";
$resumen_wa .= "📍 *ENVÍO:* " . $direccion_final . "\n";
$resumen_wa .= "🆔 *REFERENCIA:* " . $id_pago . "\n\n";
$resumen_wa .= "¡Hola! Acabo de confirmar este pedido en la web. Realizo el Bizum ahora mismo con mi referencia. 💸";

try {
    $pdo->beginTransaction();

   // A. INSERTAR CABECERA
    $fecha_actual = date('Y-m-d H:i:s'); // Esto coge la hora exacta de España que configuraste
    $sqlP = "INSERT INTO pedidos (id_pago, user_id, productos, total, direccion_completa, estado, fecha) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtP = $pdo->prepare($sqlP);
    $stmtP->execute([$id_pago, $user_id, $resumen_db, $total_final_servidor, $direccion_final, $estado_pedido, $fecha_actual]);
    $pedido_id_real = $pdo->lastInsertId();

    // B. INSERCIÓN EN DETALLES COMPLETA (AHORA SÍ CON NOTAS)
    $sqlD = "INSERT INTO pedidos_detalle (pedido_id, producto_id, nombre, talla, color, imagen_custom, imagen_espalda, logos_extras, extras_descripcion, notas, cantidad, precio_unitario) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtD = $pdo->prepare($sqlD);

    foreach($detalles_para_insertar as $det) {
        $stmtD->execute([
            $pedido_id_real, 
            $det['producto_id'], 
            $det['nombre'], 
            $det['talla'], 
            $det['color'], 
            $det['img_custom'], 
            $det['img_espalda'], 
            $det['logos_ext'], 
            $det['extras_desc'], 
            $det['notas'], // 👈 Ahora sí usamos $det en lugar de $item
            $det['cantidad'], 
            $det['precio_u']
        ]);
    }

    // C. REGISTRO EN TABLA PAGOS (BIZUM)
    $nombre_cliente_pago = h($_SESSION['nombre'] ?? 'Cliente');
    $email_cliente_pago = filter_var($_SESSION['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $sqlBizumPago = "INSERT INTO pagos (pedido_id, paypal_order_id, payer_id, payer_email, payer_name, amount, currency_code, status, payment_method, captured_at)
                     VALUES (?, ?, NULL, ?, ?, ?, 'EUR', 'CREATED', 'BIZUM', NULL)";
    $stmtBizumPago = $pdo->prepare($sqlBizumPago);
    $stmtBizumPago->execute([
        $pedido_id_real,
        $id_pago,
        $email_cliente_pago,
        $nombre_cliente_pago,
        $total_final_servidor
    ]);
    $pago_id_bizum = $pdo->lastInsertId();
    
    // Actualizar pedido con referencia al pago
    $pdo->prepare("UPDATE pedidos SET payment_id = ? WHERE id = ?")->execute([$pago_id_bizum, $pedido_id_real]);

    // D. LIMPIEZA CARRITO
    unset($_SESSION['carrito']); 
    if ($user_id > 0) {
        $pdo->prepare("UPDATE usuarios SET carrito_guardado = NULL WHERE id = ?")->execute([$user_id]);
    }

    $pdo->commit();

    // D. ENVÍO DE EMAIL PREMIUM AL CLIENTE Y AL VENDEDOR
    $email_cliente = filter_var($_SESSION['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!empty($email_cliente)) {
        
        // --- 1. EMAIL AL CLIENTE ---
        $asunto = "📝 Pedido Solicitado (Ref: $id_pago) - Camiglobo";

        // PROCESAMIENTO INTELIGENTE PARA GOOGLE MAPS
        $partes_dir = explode('|', $direccion_final);
        $solo_calle_y_ciudad = (isset($partes_dir[1])) ? trim($partes_dir[1]) . ', ' . trim($partes_dir[2] ?? '') : $direccion_final;
        $url_maps = "https://maps.google.com/?q=" . urlencode($solo_calle_y_ciudad); // 👈 URL oficial corregida
        $cuerpo = "
        <h1 style='color:#111; text-align:center; font-size: 26px; margin-bottom: 10px;'>¡Pedido Recibido! 🛍️</h1>
        <p style='text-align:center; color:#666; font-size:16px;'>Has elegido pagar mediante <b>Bizum / Transferencia</b>.</p>
        
        <div style='background:#fff5f5; border-left:5px solid #e74c3c; padding:25px; margin:30px 0; border-radius:15px;'>
            <p style='margin:0; color:#c0392b; font-size:16px;'><strong>⚠️ ÚLTIMO PASO:</strong> Realiza el Bizum por valor de <b style='font-size:18px;'>" . formatPrecio($total_final_servidor) . "</b> a nuestro número:</p>
            <p style='margin:20px 0; font-size:32px; font-weight: 900; color:#27ae60; text-align:center; letter-spacing: 2px; background: #fff; padding: 15px; border-radius: 12px; border: 2px dashed #27ae60;'>653 851 786</p>
            <p style='margin:0; color:#c0392b; text-align:center; font-size: 14px;'>Es <b>OBLIGATORIO</b> poner esta referencia en el concepto:</p>
            <p style='margin:10px 0 0 0; font-size:26px; font-weight:900; color:#111; text-align:center;'>$id_pago</p>
        </div>

        <table style='width:100%; border-collapse:collapse; margin-top:15px;'>
            <thead>
                <tr style='background:#f9f9f9; color:#888;'>
                    <th style='text-align:left; padding:12px; font-size: 12px; text-transform:uppercase;'>Producto</th>
                    <th style='padding:12px; text-align:center; font-size: 12px; text-transform:uppercase;'>Cant.</th>
                    <th style='text-align:right; padding:12px; font-size: 12px; text-transform:uppercase;'>Total</th>
                </tr>
            </thead>
            <tbody>$resumen_html_email</tbody>
        </table>

        <div style='margin-top:20px; text-align:right; border-top:2px solid #f1f1f1; padding-top:20px;'>
            <p style='color:#e74c3c; font-size:24px; font-weight: 900; margin:0;'>TOTAL: " . formatPrecio($total_final_servidor) . "</p>
        </div>

        <div style='margin-top:30px; padding:20px; background:#f9f9f9; border-radius:15px; border: 1px solid #eee;'>
            <p style='margin:0; color:#111; font-size: 15px;'><strong>📍 Dirección de entrega:</strong></p>
            <p style='margin:10px 0 0 0;'>
                <a href='$url_maps' target='_blank' style='color: #e74c3c; text-decoration: none; font-weight: bold; line-height: 1.5;'>
                    " . nl2br($direccion_final) . " <br>
                    <span style='font-size: 12px; color: #7f8c8d;'>(Toca aquí para ver en Google Maps)</span>
                </a>
            </p>
        </div>";
        
        try { enviarEmail($email_cliente, $asunto, $cuerpo, '#e67e22'); } catch (Exception $e) { error_log("Error email cliente: " . $e->getMessage()); }

        // --- 2. EMAIL AL VENDEDOR (ADMIN) ---
        $email_admin = "camigloboshop@gmail.com"; 
        $asunto_admin = "⭐ NUEVA VENTA BIZUM - Ref: $id_pago";
        
        $cuerpo_admin = "
        <h1 style='color:#111; text-align:center; font-size: 24px; margin-bottom: 10px;'>¡Has vendido algo! 💰</h1>
        <p style='text-align:center; color:#666; font-size:16px;'>Referencia Bizum: <b>$id_pago</b></p>
        
        <div style='background:#fdf7f2; border-left:5px solid #e67e22; padding:20px; margin:30px 0; border-radius:12px;'>
            <p style='margin:0; color:#d35400;'><strong>⚠️ ACCIÓN NECESARIA:</strong> Verifica el ingreso de <b style='font-size:18px;'>" . formatPrecio($total_final_servidor) . "</b> en tu cuenta antes de fabricar.</p>
        </div>

        <table style='width:100%; border-collapse:collapse; margin-top:15px;'>
            <thead>
                <tr style='background:#f9f9f9; color:#888;'>
                    <th style='text-align:left; padding:12px; font-size: 12px;'>PRODUCTO</th>
                    <th style='padding:12px; text-align:center; font-size: 12px;'>CANT.</th>
                    <th style='text-align:right; padding:12px; font-size: 12px;'>TOTAL</th>
                </tr>
            </thead>
            <tbody>$resumen_html_email</tbody>
        </table>

        <div style='margin-top:20px; text-align:right; border-top:2px solid #f1f1f1; padding-top:20px;'>
            <p style='color:#e74c3c; font-size:22px; font-weight: 900; margin:0;'>COBRAR: " . formatPrecio($total_final_servidor) . "</p>
        </div>

        <div style='margin-top:30px; padding:20px; background:#f9f9f9; border-radius:15px; border: 1px solid #eee;'>
            <p style='margin:0; color:#111;'><strong>👤 Cliente:</strong> $email_cliente</p>
            <p style='margin:10px 0 0 0;'><strong>📍 Dirección de entrega (GPS):</strong></p>
            <a href='$url_maps' target='_blank' style='color: #e67e22; text-decoration: none; font-weight: bold;'>
                " . nl2br($direccion_final) . "
            </a>
        </div>
        
        <div style='text-align: center; margin-top: 30px;'>
            <a href='https://www.camiglobo.com/admin_pedidos.php' style='display:inline-block; background:#e74c3c; color:white; padding:12px 25px; text-decoration:none; border-radius:50px; font-weight:bold; font-size:14px;'>Ir al Panel de Administración →</a>
        </div>";
        
        try { enviarEmail($email_admin, $asunto_admin, $cuerpo_admin); } catch (Exception $e) { error_log("Error email admin: " . $e->getMessage()); }
    }

    // E. REDIRECCIÓN FINAL A PÁGINA DE AGRADECIMIENTO
    header("Location: gracias.php?id=" . $id_pago);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error crítico Bizum Camiglobo: " . $e->getMessage());
    die("Lo sentimos, hubo un error técnico. Por favor, contacta con nosotros por WhatsApp.");
}
?>
