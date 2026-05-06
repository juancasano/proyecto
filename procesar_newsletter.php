<?php
/**
 * procesar_newsletter.php
 * Maneja el alta y baja de la newsletter con reCAPTCHA v3
 * (Solo POST con CSRF protection)
 */

// 1. INCLUIR CONFIGURACIÓN (Carga conexión PDO, funciones de Mailer y reCAPTCHA)
require_once 'includes/config.php';

// 2. DETECTOR DE MÉTODO INTELIGENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    // 3. VERIFICAR HONEYPOT (Solo en POST para frenar spam invisible)
    if (!empty($_POST['bot_check'])) {
        error_log("Bot detectado (Honeypot) desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        header("Location: index.php?error=bot_detected#footer");
        exit;
    }

    // CSRF para TODAS las acciones POST
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de seguridad: Token CSRF inválido.");
    }

    // 4. VALIDAR RECAPTCHA (Solo para suscripciones nuevas)
    if ($accion === 'alta') {
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_token) || !validarRecaptcha($recaptcha_token)) {
            header("Location: index.php?error=captcha#footer");
            exit;
        }
    }
} else {
    // Solo POST permitido
    header("Location: index.php");
    exit;
}

// 5. VALIDACIÓN FINAL DE DATOS (Seguridad Extra antes de BD)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=email_invalid#footer");
    exit;
}

if (!in_array($accion, ['alta', 'baja'])) {
    header("Location: index.php?error=db#footer");
    exit;
}

// 6. EJECUTAR ACCIÓN EN BASE DE DATOS
try {
    if ($accion === 'alta') {
        // --- PROCESAR ALTA ---
        
        // Comprobar si ya existe
        $check = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            header("Location: index.php?error=news_exists#footer");
            exit;
        }
        // Generar token opaco para enlace de baja (no expone email en URL)
        $token_baja = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("INSERT INTO newsletter (email, fecha_registro, token_baja) VALUES (?, NOW(), ?)");
        $stmt->execute([$email, $token_baja]);        
        // Enviar email de bienvenida premium
        $asunto = "✨ ¡Ya eres parte de Camiglobo Barcelona!";
        $mensaje = "
            <div style='text-align: center; margin-bottom: 25px;'>
                <div style='font-size: 45px; margin-bottom: 8px;'>✨</div>
                <h1 style='color: #111; margin: 0 0 10px 0; font-size: 28px; font-weight: 900;'>¡Bienvenido al Club Camiglobo!</h1>
                <p style='color: #555; font-size: 15px; line-height: 1.7; margin: 0;'>Gracias por unirte a nuestra comunidad. Somos expertos en personalización premium en Barcelona. Aquí podrás diseñar productos únicos que hablan por ti.</p>
            </div>

            <div style='background: #fff5f5; border-radius: 12px; padding: 20px; margin-bottom: 20px; text-align: center; border: 1px solid #fde8e8;'>
                <p style='margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #111;'>🎁 ¿Qué recibirás como suscriptor?</p>
                <p style='margin: 0 0 6px 0; color: #555; font-size: 14px; line-height: 1.7;'>
                    <span style='background: #27ae60; color: white; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; vertical-align: middle; margin-right: 8px;'>✓</span>
                    <strong>Descuentos exclusivos</strong> solo para suscriptores
                </p>
                <p style='margin: 0 0 6px 0; color: #555; font-size: 14px; line-height: 1.7;'>
                    <span style='background: #27ae60; color: white; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; vertical-align: middle; margin-right: 8px;'>✓</span>
                    <strong>Productos de edición limitada</strong> con acceso prioritario
                </p>
                <p style='margin: 0; color: #555; font-size: 14px; line-height: 1.7;'>
                    <span style='background: #27ae60; color: white; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; vertical-align: middle; margin-right: 8px;'>✓</span>
                    <strong>Novedades y ofertas flash</strong> antes que nadie
                </p>
            </div>

            <div style='background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 10px; text-align: center;'>
                <p style='margin: 0 0 8px 0; font-size: 16px; font-weight: 800; color: #111;'>💡 Tu diseño, tu estilo</p>
                <p style='margin: 0; color: #555; font-size: 14px; line-height: 1.7;'>En Camiglobo no hay límites. Sube tu diseño, elige la prenda que quieras y nosotros nos encargamos del resto. <strong>¿Has visto un diseño que te guste? Dínoslo y lo ponemos en cualquier prenda.</strong> Calidad premium, envío rápido y atención personalizada.</p>
            </div>";
        
        // Enviar el correo usando la función central (token opaco para enlace de baja)
        try { enviarEmail($email, $asunto, $mensaje, '#e74c3c', $token_baja); } catch (Exception $e) { error_log("Error email newsletter alta: " . $e->getMessage()); }

        // Notificar al admin
        try { enviarEmail(ADMIN_EMAIL, "Nuevo suscriptor newsletter: $email", "<p>Se ha suscrito al newsletter: <strong>$email</strong></p>", '#27ae60'); } catch (Exception $e) {}

        // Redirigir con éxito al ancla del footer
        header("Location: index.php?msg=news_sub#footer");
        exit;

    } else if ($accion === 'baja') {
        // --- PROCESAR BAJA ---
        $stmt = $pdo->prepare("DELETE FROM newsletter WHERE email = ?");
        $stmt->execute([$email]);
        
        // Enviar confirmación de baja
        $asunto = "👋 Te has dado de baja de Camiglobo";
        $mensaje = "
            <div style='text-align: center;'>
                <div style='font-size: 50px; margin-bottom: 15px;'>👋</div>
                <h2 style='color: #111; margin: 0 0 15px 0; font-size: 24px;'>Has sido dado de baja correctamente</h2>
                <p style='color: #555; font-size: 15px; line-height: 1.7;'>Lamentamos verte partir. A partir de ahora no recibirás más correos promocionales nuestros.</p>
                <p style='color: #777; font-size: 14px; line-height: 1.7;'>Si fue un error, puedes volver a suscribirte cuando quieras visitando nuestra web.</p>
                <div style='margin: 30px 0 10px;'>
                    <a href='https://camiglobo.com' style='background: linear-gradient(90deg, #111 0%, #333 100%); color: white; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 14px; display: inline-block; letter-spacing: 0.5px;'>Volver a Camiglobo</a>
                </div>
            </div>";

        try { enviarEmail($email, $asunto, $mensaje, '#95a5a6'); } catch (Exception $e) { error_log("Error email newsletter baja: " . $e->getMessage()); }

        header("Location: index.php?msg=news_unsub#footer");
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Error BD procesar_newsletter: " . $e->getMessage());
    header("Location: index.php?error=db#footer");
    exit;
} catch (Exception $e) {
    error_log("Error General procesar_newsletter: " . $e->getMessage());
    header("Location: index.php?error=db#footer");
    exit;
}