<?php

require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. VALIDACIÓN CSRF
    $token_recibido = $_POST['csrf_token'] ?? '';
    if (empty($token_recibido) || $token_recibido !== ($_SESSION['csrf_token'] ?? '')) {
        error_log("FALLO DE SEGURIDAD CSRF en Registro.");
        header("Location: registro.php?error=csrf");
        exit;
    }

    $redirect = htmlspecialchars($_POST['redirect'] ?? '');
    $suffix = !empty($redirect) ? "&redirect=$redirect" : "";

    // RATE LIMIT: Máximo 5 registros por hora por IP
    $ip_registro = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    try {
        $stmt_rl = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE ip = ? AND action = 'REGISTER' AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt_rl->execute([$ip_registro]);
        if ((int)$stmt_rl->fetchColumn() >= 5) {
            error_log("Rate limit registro: IP $ip_registro excedió 5 intentos/hora.");
            header("Location: registro.php?error=rate_limit" . $suffix);
            exit;
        }
        $pdo->prepare("INSERT INTO audit_log (user_id, action, details, ip) VALUES (0, 'REGISTER', 'Intento de registro', ?)")->execute([$ip_registro]);
    } catch (Exception $e) { /* Si audit_log falla, no bloquear */ }

    // 2. VALIDACIÓN RECAPTCHA CON CURL
    $captchaResponse = $_POST['g-recaptcha-response'] ?? null;

    if (!$captchaResponse || !validarRecaptcha($captchaResponse)) {
        header("Location: registro.php?error=captcha" . $suffix);
        exit;
    }

    // 3. SANEAMIENTO Y VALIDACIÓN DE DATOS
    $nombre     = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $email      = trim($_POST['email'] ?? '');
    $pass_plano = $_POST['password'] ?? '';

    if (empty($nombre) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: registro.php?error=fields" . $suffix);
        exit;
    }
    
    if (strlen($pass_plano) < 8) {
        header("Location: registro.php?error=pass_short" . $suffix);
        exit;
    }

    if ($pass_plano !== ($_POST['password_confirm'] ?? '')) {
        header("Location: registro.php?error=pass_mismatch" . $suffix);
        exit;
    }

    try {
        // 4. ESCUDO ANTI-DUPLICADOS
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        
        if ($stmtCheck->rowCount() > 0) {
            header("Location: registro.php?error=email_existe" . $suffix);
            exit;
        }

        // 5. INSERCIÓN SEGURA
        $pass_hash = password_hash($pass_plano, PASSWORD_BCRYPT);
        
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, fecha_registro) VALUES (?, ?, ?, 'cliente', NOW())");
        $stmt->execute([$nombre, $email, $pass_hash]);
        $nuevo_id = $pdo->lastInsertId();

        // 6. AUTO-LOGIN UX PREMIUM
        session_regenerate_id(true);
        
        $_SESSION['user_id']     = (int)$nuevo_id;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email']  = $email;
        $_SESSION['rol']    = 'cliente';
        $pdo->commit();

        // ========================================================
        // --- 7. EMAIL DE BIENVENIDA AL CLIENTE ---
        // ========================================================
        $asunto = "✨ ¡Bienvenido a la familia Camiglobo, $nombre!";
        // $cupon = "BIENVENIDA10"; // Desactivado temporalmente por petición.

        $message = "
        <div style='text-align: center;'>
            <div style='font-size: 50px; margin-bottom: 10px;'>🎈🎊🎈</div>
            
            <h2 style='color: #111; font-size: 26px; margin: 0 0 10px 0;'>¡Felicidades, $nombre!</h2>
            <p style='font-size: 16px; color: #555; line-height: 1.6;'>
                Ya eres parte de <strong>Camiglobo Barcelona</strong>. Estamos encantados de tenerte con nosotros y listos para dar vida a tus ideas.
            </p>

            <!-- Bloque del cupón desactivado temporalmente
            <div style='margin: 30px 0; padding: 30px; border: 2px dashed #e74c3c; border-radius: 15px; background-color: #fffaf9;'>
                <p style='margin: 0; color: #e74c3c; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 13px;'>Regalo de Bienvenida</p>
                <h3 style='margin: 10px 0; font-size: 32px; color: #111; font-weight: 900;'>$cupon</h3>
                <p style='margin: 0; color: #666; font-size: 14px;'>Usa este código en tu primer pedido y llévate un <strong>10% DE DESCUENTO</strong> inmediato.</p>
            </div>
            -->

            <div style='background: #f9f9f9; padding: 25px; border-radius: 12px; margin-bottom: 30px; text-align: left;'>
                <p style='margin: 0 0 10px 0; font-weight: bold; color: #111;'>🚀 ¿Por dónde empezamos?</p>
                <ul style='font-size: 14px; color: #666; padding-left: 20px; line-height: 1.8;'>
                    <li>Personalizar tus propias prendas con nuestro editor.</li>
                    <li>Gestionar tus pedidos y direcciones fácilmente.</li>
                    <li>Recibir ofertas exclusivas antes que nadie.</li>
                </ul>
            </div>

            <div style='margin-top: 35px;'>
                <a href='https://www.camiglobo.com/personalizar.php' style='background-color: #e74c3c; color: white; padding: 18px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);'>¡EMPEZAR A DISEÑAR!</a>
            </div>
        </div>";

        try { enviarEmail($email, $asunto, $message, '#e74c3c'); } catch (Exception $e) { error_log("Error email registro cliente: " . $e->getMessage()); }

        // --- NUEVO: AVISO PARA EL VENDEDOR (TI) ---
        // ========================================================

        $asunto_admin = "👤 Nuevo cliente registrado: $nombre";
        $mensaje_admin = "
        <div style='text-align: center;'>
            <h2 style='color: #2c3e50;'>¡Tienes un nuevo cliente!</h2>
            <p style='font-size: 16px; color: #555;'>Se ha registrado un nuevo usuario en la web de Camiglobo.</p>

            <div style='background: #f9f9f9; padding: 20px; border-radius: 10px; display: inline-block; text-align: left; margin-top: 20px; border: 1px solid #eee;'>
                <p style='margin: 5px 0;'><strong>Nombre:</strong> $nombre</p>
                <p style='margin: 5px 0;'><strong>Email:</strong> $email</p>
                <p style='margin: 5px 0;'><strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
            </div>

            <p style='margin-top: 30px; font-size: 13px; color: #999;'>Aviso automático de Camiglobo Barcelona.</p>
        </div>";

        try { enviarEmail(ADMIN_EMAIL, $asunto_admin, $mensaje_admin); } catch (Exception $e) { error_log("Error email registro admin: " . $e->getMessage()); }

        // ========================================================
        // --- 8. REDIRECCIÓN FINAL ---
        // ========================================================
        unset($_SESSION['csrf_token']);

        if ($redirect === 'checkout') {
            header("Location: checkout.php?msg=reg_success");
        } else {
            header("Location: productos.php?msg=reg_success");
        }
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("FALLO CRÍTICO REGISTRO: " . $e->getMessage());
        header("Location: registro.php?error=db" . $suffix);
        exit;
    }
} else {
    header("Location: registro.php");
    exit;
}
?>