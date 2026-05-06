<?php
/**
 * ARCHIVO: procesar_recuperar.php
 * FUNCIÓN: Procesamiento blindado de solicitudes de recuperación.
 * ACTUALIZACIÓN: Diseño unificado. El marco lo pone config.php.
 */

require_once 'includes/config.php';

// 1. SEGURIDAD: SOLO POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recuperar.php");
    exit;
}

// 2. VALIDACIÓN CSRF
$token_csrf = $_POST['csrf_token'] ?? '';
if (empty($token_csrf) || $token_csrf !== ($_SESSION['csrf_token'] ?? '')) {
    error_log("FALLO CSRF: Intento de recuperación sospechoso.");
    header("Location: recuperar.php?error=csrf");
    exit;
}

// RATE LIMIT: Máximo 3 recuperaciones por hora por IP
$ip_recuperar = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
try {
    $stmt_rl = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE ip = ? AND action = 'RECOVER_PASS' AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt_rl->execute([$ip_recuperar]);
    if ((int)$stmt_rl->fetchColumn() >= 3) {
        error_log("Rate limit recuperar: IP $ip_recuperar excedió 3 intentos/hora.");
        header("Location: recuperar.php?error=rate_limit");
        exit;
    }
    $pdo->prepare("INSERT INTO audit_log (user_id, action, details, ip) VALUES (0, 'RECOVER_PASS', 'Solicitud recuperación', ?)")->execute([$ip_recuperar]);
} catch (Exception $e) { /* Si audit_log falla, no bloquear */ }

// 3. VALIDACIÓN RECAPTCHA CON CURL
$captcha_response = $_POST['g-recaptcha-response'] ?? null;
if (!$captcha_response || !validarRecaptcha($captcha_response)) {
    header("Location: recuperar.php?error=captcha");
    exit;
}

// 4. SANEAMIENTO
$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: recuperar.php?error=invalid");
    exit;
}

try {
    // Buscamos al usuario
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Solo procesamos si el usuario existe (pero no revelamos información al atacante)
    if ($user) {
        $token_reset = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardamos el token (Confirmado: tu tabla usa reset_expires)
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmtUpdate->execute([$token_reset, $expira, $email]);

        // Construcción de URL
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $dominio = $_SERVER['HTTP_HOST'];
        $enlace = $protocolo . $dominio . "/restablecer.php?token=" . $token_reset;
        
        $nombre = h($user['nombre']);

        // --- 📧 CONTENIDO DEL EMAIL (SOLO EL CENTRO) ---
        $asunto = "🔐 Restablecer contraseña - Camiglobo Barcelona";
        
        $message = "
        <div style='text-align: center;'>
            <div style='font-size: 50px; margin-bottom: 20px;'>🛡️</div>
            <h2 style='color: #2c3e50; font-size: 22px; margin-bottom: 10px;'>¿Has solicitado un cambio de clave?</h2>
            <p style='color: #555; font-size: 16px; line-height: 1.6;'>
                Hola <strong>$nombre</strong>, hemos recibido una solicitud para restablecer la contraseña de tu cuenta.
            </p>
            
            <div style='margin: 40px 0;'>
                <a href='$enlace' style='background: #2c3e50; color: white; padding: 18px 35px; text-decoration: none; border-radius: 50px; font-weight: 900; font-size: 14px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>ESTABLECER NUEVA CONTRASEÑA</a>
            </div>

            <p style='font-size: 13px; color: #999; line-height: 1.6;'>
                Este enlace es de un solo uso y caducará automáticamente en <strong>1 hora</strong> por razones de seguridad.<br>
                Si no has solicitado este cambio, puedes ignorar este mensaje; tu cuenta sigue segura.
            </p>

            <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>
            
            <p style='font-size: 11px; color: #bbb;'>
                Si tienes problemas con el botón, copia y pega esto en tu navegador:<br>
                <span style='color: #e74c3c;'>$enlace</span>
            </p>
        </div>";

        try { enviarEmail($email, $asunto, $message, '#8e44ad'); } catch (Exception $e) { error_log("Error email recuperar: " . $e->getMessage()); }
    }

    // 5. RESPUESTA VISUAL (UX)
    unset($_SESSION['csrf_token']);
    include 'includes/header.php';
    ?>
    <div class="container" style="max-width: 500px; margin: 100px auto; text-align: center; padding: 50px; background: white; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.05);">
        <div style="background: #e8f5e8; width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
            <i class="fas fa-paper-plane" style="font-size: 40px; color: #27ae60;"></i>
        </div>
        <h2 style="color: #2c3e50; font-weight: 900;">Solicitud Procesada</h2>
        <p style="color: #7f8c8d; line-height: 1.8; font-size: 15px;">
            Hemos enviado las instrucciones a <b><?php echo h($email); ?></b>.<br>
            Revisa tu bandeja de entrada (y la carpeta de spam) en unos minutos.
        </p>
        <div style="margin-top: 40px;">
            <a href="login.php" style="display:inline-block; background:#2c3e50; color:white; padding:15px 40px; border-radius:50px; text-decoration:none; font-weight:900; font-size: 13px; transition: 0.3s;">VOLVER AL ACCESO</a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';

} catch (Exception $e) {
    error_log("ERROR CRÍTICO RECUPERACIÓN ($email): " . $e->getMessage());
    header("Location: recuperar.php?error=system");
    exit;
}