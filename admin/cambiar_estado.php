<?php
/**
 * ARCHIVO: admin/cambiar_estado.php
 * FUNCIÓN: Procesador de cambios de estado SECURIZADO.
 */

require_once '../includes/config.php';

// --- 1. EL CANDADO DE SEGURIDAD (CSRF) ---
// Si no trae la llave, no dejamos pasar a nadie.
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de seguridad: Token CSRF inválido. Vuelve atrás y recarga.");
}

// --- 2. VERIFICAR ADMIN ---
if (!esAdmin()) {
    header("Location: ../login.php");
    exit;
}

// --- 3. RECOGER DATOS (Corregido para coincidir con tu formulario) ---
// En el formulario pusimos name="id", así que aquí recogemos $_POST['id']
$pedido_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nuevo_estado = $_POST['nuevo_estado'] ?? '';
$tracking_url_input = $_POST['tracking_url'] ?? '';

// Validar y forzar HTTPS en tracking URL
if (!empty($tracking_url_input)) {
    $tracking_url_input = filter_var($tracking_url_input, FILTER_VALIDATE_URL);
    if ($tracking_url_input && str_starts_with($tracking_url_input, 'http://')) {
        $tracking_url_input = str_replace('http://', 'https://', $tracking_url_input);
    }
    if (!$tracking_url_input) {
        $tracking_url_input = '';
    }
}

if ($pedido_id <= 0 || empty($nuevo_estado)) {
    die("Error: Faltan datos del pedido.");
}

$estados_permitidos = ['Pendiente', 'En Taller', 'Enviado', 'Entregado', 'Cancelado', 'Pendiente Pago', 'Pagado', 'Revisión Fraudulenta', 'Revision Fraudulenta'];
if (!in_array($nuevo_estado, $estados_permitidos, true)) {
    die("Estado no válido.");
}

// 4. Obtener datos para el email
$stmt = $pdo->prepare("SELECT p.*, u.email, u.nombre FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

if ($pedido) {
    // 4.5. REGISTRAR EN HISTORIAL
    $estado_anterior = $pedido['estado'];
    $admin_id = $_SESSION['user_id'] ?? NULL;
    
    try {
        $sqlHistorial = "INSERT INTO pedido_historial (pedido_id, estado_anterior, estado_nuevo, cambiado_por, fecha) 
                         VALUES (?, ?, ?, ?, NOW())";
        $stmtHistorial = $pdo->prepare($sqlHistorial);
        $stmtHistorial->execute([$pedido_id, $estado_anterior, $nuevo_estado, $admin_id]);
    } catch (Exception $e) {
        error_log("Error insertando historial: " . $e->getMessage());
    }
    
    // 4.6. ACTUALIZAR ESTADO PAGO SI SE CONFIRMA PAGO BIZUM
    if (($nuevo_estado === 'Pagado' || $nuevo_estado === 'En Taller') && strpos($pedido['id_pago'], 'BZ-') === 0) {
        try {
            $pdo->prepare("UPDATE pagos SET status = 'COMPLETED', captured_at = NOW() WHERE pedido_id = ? AND payment_method = 'BIZUM'")->execute([$pedido_id]);
        } catch (Exception $e) {
            error_log("Error actualizando pago Bizum: " . $e->getMessage());
        }
    }
    
    // 5. Actualizar en Base de Datos
    if (!empty($tracking_url_input)) {
        $update = $pdo->prepare("UPDATE pedidos SET estado = ?, tracking_url = ? WHERE id = ?");
        $update->execute([$nuevo_estado, $tracking_url_input, $pedido_id]);
    } else {
        $update = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $update->execute([$nuevo_estado, $pedido_id]);
    }

    // 6. PREPARAR EMAIL
    $email_cliente = $pedido['email'];
    $nombre_cliente = htmlspecialchars($pedido['nombre'] ?? 'Cliente');
    $ref = $pedido['id_pago'];
    // Usamos el tracking nuevo si lo hay, o el que ya tenía la base de datos
    $tracking_final = !empty($tracking_url_input) ? htmlspecialchars($tracking_url_input) : htmlspecialchars($pedido['tracking_url'] ?? '#');

    $enviar_mail = false;
    $asunto = "";
    $mensaje_cuerpo = "";
    $color_email = '#e74c3c';

    switch ($nuevo_estado) {
        case 'En Taller': // (O 'Pagado' según tu flujo)
            $enviar_mail = true;
            $color_email = '#f39c12';
            $asunto = "🔨 Tu pedido #$ref ha entrado en taller";
            $mensaje_cuerpo = "
                <div style='text-align: center;'>
                    <div style='font-size: 50px; margin-bottom: 15px;'>🔨</div>
                    <h1 style='color: #f39c12; margin: 0 0 10px 0; font-size: 26px;'>¡Manos a la obra!</h1>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Hola <strong>$nombre_cliente</strong>, hemos confirmado tu pedido. Ya estamos trabajando en tus diseños.</p>
                </div>";
            break;

        case 'Enviado': // (O 'En camino')
            $enviar_mail = true;
            $color_email = '#3498db';
            $asunto = "🚀 ¡Pedido #$ref enviado! Localízalo ahora";
            $mensaje_cuerpo = "
                <div style='text-align: center;'>
                    <div style='font-size: 50px; margin-bottom: 15px;'>🚚</div>
                    <h1 style='color: #3498db; margin: 0 0 10px 0; font-size: 26px;'>¡Va de camino!</h1>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Hola <strong>$nombre_cliente</strong>, tu paquete ya ha salido del taller.</p>
                    <div style='margin: 30px 0;'>
                        <a href='$tracking_final' style='background-color: #3498db; color: white; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 14px; display: inline-block;'>LOCALIZAR MI PAQUETE</a>
                    </div>
                </div>";
            break;

        case 'Entregado':
            $enviar_mail = true;
            $color_email = '#27ae60';
            $asunto = "🎁 ¡Pedido entregado! - Camiglobo";
            $mensaje_cuerpo = "
                <div style='text-align: center;'>
                    <div style='font-size: 50px; margin-bottom: 15px;'>🎉</div>
                    <h1 style='color: #27ae60; margin: 0 0 10px 0; font-size: 26px;'>¡Entregado!</h1>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>¡Hola <strong>$nombre_cliente</strong>! Tu pedido figura como entregado.</p>
                    <p style='color: #777; font-size: 14px;'>Si te gusta, ¡etiquétanos en Instagram <strong>@camiglobo</strong>!</p>
                </div>";
            break;
            
        case 'Cancelado':
            $enviar_mail = true;
            $color_email = '#e74c3c';
            $asunto = "⚠️ Pedido #$ref Cancelado";
            $mensaje_cuerpo = "
                <div style='text-align: center;'>
                    <div style='font-size: 50px; margin-bottom: 15px;'>⚠️</div>
                    <h1 style='color: #e74c3c; margin: 0 0 10px 0; font-size: 26px;'>Pedido Cancelado</h1>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Tu pedido <b>#$ref</b> ha sido cancelado.</p>
                    <p style='color: #777; font-size: 14px; line-height: 1.7;'>Si ha sido un error, contacta con nosotros respondiendo a este email.</p>
                    <div style='margin-top:25px;'>
                        <a href='https://www.camiglobo.com' style='background:#111; color:white; padding:14px 28px; text-decoration:none; border-radius:50px; font-weight:800; font-size:14px; display:inline-block;'>VOLVER A LA TIENDA</a>
                    </div>
                </div>";
            break;
    }

    // 7. ENVIAR EL CORREO
    if ($enviar_mail && !empty($email_cliente)) {
        try {
            // Función genérica que usa PHPMailer si está disponible (definida en config.php o admin_pedidos)
            if (function_exists('enviarEmail')) {
                enviarEmail($email_cliente, $asunto, $mensaje_cuerpo, $color_email);
            }
        } catch (Exception $e) {
            error_log("Error email cambiar_estado: " . $e->getMessage());
        }
    }
}

// Vuelta a la ficha con mensaje de éxito
header("Location: ver_pedido.php?id=$pedido_id&msg=updated");
exit;
?>