<?php
header("Permissions-Policy: interest-cohort=(), attribution-reporting=(), run-ad-auction=(), join-ad-interest-group=(), browsing-topics=(), shared-storage=(), shared-storage-select-url=(), otp-credentials=(), private-state-token-issuance=(), private-state-token-redemption=()");
/**
 * ARCHIVO DE CONFIGURACIÓN CRÍTICO - CAMIGLOBO BARCELONA
 * CORRECCIONES: DB_HOST fallback + función CURL para reCAPTCHA + Plantilla Maestra
 */

// 1. --- CARGA DE VARIABLES DE ENTORNO ---
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (empty(trim($line)) || strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"");
            $_ENV[$key] = $value;
        }
    }
} else {
    error_log("CRÍTICO: Archivo .env no encontrado.");
    die("Error interno de configuración. Contacte con el administrador.");
}

// 2. --- CONFIGURACIÓN DE ENTORNO ---
date_default_timezone_set('Europe/Madrid');

// --- 2.1 CARGA DE LIBRERÍAS (PHPMailer) ---
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 3. --- SEGURIDAD DE SESIONES ---
$session_lifetime = 86400;
$inactive_timeout = 7200;

ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

$is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'domain' => '', 
    'secure' => $is_secure, 
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_timeout)) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();

// TOKEN CSRF GLOBAL
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. --- CABECERAS DE SEGURIDAD ---
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: ".
    "default-src 'self'; ".
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' ".
        "https://cdnjs.cloudflare.com ".
        "https://cdn.jsdelivr.net ".
        "https://www.google.com ".
        "https://www.gstatic.com ".
        "https://www.paypal.com ".
        "https://www.paypalobjects.com; ".
    "style-src 'self' 'unsafe-inline' ".
        "https://cdnjs.cloudflare.com ".
        "https://cdn.jsdelivr.net ".
        "https://fonts.googleapis.com; ".
    "font-src 'self' ".
        "https://cdnjs.cloudflare.com ".
        "https://fonts.gstatic.com; ".
    "img-src 'self' data: blob: ".
        "https://www.camiglobo.com ".
        "https://www.svgrepo.com ".
        "https://www.google.com ".
        "https://maps.gstatic.com ".
        "https://*.googleapis.com ".
        "https://*.ggpht.com; ".
    "connect-src 'self' ".
        "https://www.google.com ".
        "https://api-m.paypal.com ".
        "https://oauth2.googleapis.com ".
        "https://www.googleapis.com ".
        "https://accounts.google.com; ".
    "frame-src 'self' ".
        "https://www.google.com ".
        "https://www.tiktok.com ".
        "https://www.paypal.com; ".
    "frame-ancestors 'self'; ".
    "base-uri 'self'; ".
    "form-action 'self' ".
        "https://accounts.google.com ".
        "https://wa.me; ".
    "object-src 'none'; ".
    "upgrade-insecure-requests"
);

// Cambia esto (para que los hackers no vean tus rutas si algo falla):
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0); 
error_reporting(0); 

// 6. --- BASE DE DATOS ---
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? ''); 
define('DB_USER', $_ENV['DB_USER'] ?? ''); 
define('DB_PASS', $_ENV['DB_PASS'] ?? ''); 
define('SITE_NAME', 'Camiglobo Barcelona');

// 7. --- CONEXIÓN PDO ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Fallo de conexión DB: " . $e->getMessage());
    die("<div style='font-family:sans-serif; text-align:center; padding:100px;'>
            <h2 style='color:#e74c3c;'>Estamos mejorando el taller</h2>
            <p>Vuelve en unos minutos.</p>
         </div>");
}

// --- NUEVO: SINCRONIZACIÓN FORZADA DEL CARRITO (Para navegadores múltiples) ---
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        // Buscamos el carrito más reciente en la Base de Datos
        $stmtCart = $pdo->prepare("SELECT carrito_guardado FROM usuarios WHERE id = ?");
        $stmtCart->execute([$_SESSION['user_id']]);
        $db_cart_data = $stmtCart->fetchColumn();
        
        // Sobreescribimos la memoria rápida (Sesión) con la verdad de la Base de Datos
        if ($db_cart_data) {
            $_SESSION['carrito'] = json_decode($db_cart_data, true) ?: [];
        } else {
            $_SESSION['carrito'] = [];
        }
    } catch (Exception $e) {
        error_log("Error sincronizando carrito: " . $e->getMessage());
    }
}
// --- FIN SINCRONIZACIÓN ---

// 8. --- CREDENCIALES DE SERVICIOS ---
define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? '');
define('PAYPAL_SANDBOX', false);

define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

// 9. --- SMTP ---
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com');
define('SMTP_USER', $_ENV['SMTP_USER'] ?? ''); 
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');            
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 465);

// 10. --- ADMIN EMAIL ---
define('ADMIN_EMAIL', 'camigloboshop@gmail.com');

// 12. --- LOGÍSTICA Y ENVÍOS ---
define('ENVIO_GRATIS_UMBRAL', 45.00);
define('ENVIO_COSTE', 4.95);

// 11. --- FUNCIONES GLOBALES ---

function esAdmin() {
    return (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
}

function formatPrecio($numero) {
    return number_format((float)$numero, 2, ',', '.') . ' €';
}

function h($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function auditLog($action, $details = '') {  
    global $pdo;  
    $user_id = $_SESSION['user_id'] ?? 0;  
    $ip = getClientIP() ?: '0.0.0.0';
      
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, details, ip, timestamp) VALUES (?, ?, ?, ?, NOW())");  
        $stmt->execute([$user_id, $action, $details, $ip]);  
    } catch(PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * VALIDACIÓN DE reCAPTCHA CON CURL (Compatible con Hostinger)
 */
/**
 * Devuelve la IP real del cliente.
 * REMOTE_ADDR viene del socket TCP, es inmune a spoofing.
 */
function getClientIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

/**
 * Detecta si el usuario está en móvil.
 */
function esMovil() {
    return preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|Opera Mini|IEMobile/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

function validarRecaptcha($token) {
    if (empty($token)) {
        error_log("reCAPTCHA validation failed: empty token");
        return false;
    }
    
    $secret = RECAPTCHA_SECRET_KEY;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    // Get the correct client IP address for validation
    $clientIP = getClientIP();
    
    $data = [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $clientIP
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Follow redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error CURL reCAPTCHA: " . $error . 
                 " | URL: $url | HTTP Code: " . ($httpCode ?: 'N/A') . 
                 " | Client IP: " . $clientIP);
        return false;
    }
    
    // Check HTTP status code
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("reCAPTCHA validation failed: HTTP error " . $httpCode .
                 " | URL: $url | Response: " . substr($response, 0, 500) .
                 " | Client IP: " . $clientIP);
        return false;
    }
    
    // Try to parse JSON response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("reCAPTCHA validation failed: Invalid JSON response" .
                 " | URL: $url | HTTP Code: $httpCode | Response: " . 
                 substr($response, 0, 500) . " | Client IP: " . $clientIP);
        return false;
    }
    
    // Check if we got a success response
    if (!isset($result['success'])) {
        error_log("reCAPTCHA validation failed: Missing 'success' field in response" .
                 " | URL: $url | HTTP Code: $httpCode | Response: " . 
                 print_r($result, true) . " | Client IP: " . $clientIP);
        return false;
    }
    
    if ($result['success'] === true) {
        return true;
    } else {
        // Log why it failed according to Google
        $errorCodes = isset($result['error-codes']) ? $result['error-codes'] : [];
        error_log("reCAPTCHA validation failed: Token rejected by Google" .
                 " | URL: $url | HTTP Code: $httpCode | Error Codes: " . 
                 implode(', ', $errorCodes) . 
                 " | Response: " . print_r($result, true) . 
                 " | Client IP: " . $clientIP);
        return false;
    }
}

/**
 * ENVÍO DE EMAIL CON PHPMAILER (PLANTILLA MAESTRA)
 */
/**
 * ENVÍO DE EMAIL CON PHPMAILER 
 */
function enviarEmail($to, $subject, $body, $color = '#e74c3c', $unsubToken = '') {
    try {
        // Al usar "use" arriba del archivo, aquí solo ponemos PHPMailer(true)
        $mail = new PHPMailer(true); 
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;

        // Configuración para Hostinger (puerto 465 = SSL, 587 = TLS)
        if (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
        }

        $mail->Port = SMTP_PORT;

        // Configuración adicional para Hostinger
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom(SMTP_USER, 'Camiglobo Barcelona');
        $mail->Sender = SMTP_USER;
        $mail->addReplyTo('ventas@camiglobo.com', 'Soporte Camiglobo'); 
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // --- 🎨 DISEÑO MAESTRO CON BARRA DE COLOR TEMÁTICA ---
        $header = "
        <div style='background-color: #f8f9fa; padding: 30px 10px; font-family: \"Segoe UI\", Helvetica, Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eeeeee;'>
                <div style='background-color: #ffffff; padding: 28px; text-align: center;'>
                <a href='https://www.camiglobo.com' style='text-decoration: none; display: block;'>
                    <img src='https://www.camiglobo.com/images/camiglobofavicon.jpg' alt='Camiglobo' style='max-width: 80px; height: auto; border-radius: 14px; vertical-align: middle; margin-right: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);'>
                    <span style='font-size: 28px; font-weight: 900; vertical-align: middle; letter-spacing: 1px;'><span style='color: #000;'>CAM</span><span style='color: #e74c3c;'>IGL</span><span style='color: #27ae60;'>OBO</span></span>
                </a>
                </div>
                <div style='height: 6px; background: linear-gradient(90deg, {$color}, {$color}aa);'></div>
                <div style='padding: 35px 30px; color: #333333; line-height: 1.6;'>";

        $footer = "
                </div>
                <div style='padding: 30px; border-top: 2px solid #f0f0f0;'>
                    <h2 style='color: #111; font-size: 20px; margin: 0 0 12px 0; text-align: center;'>📸 Nuestros diseños</h2>
                    <div style='background: #f0f7ff; border-left: 4px solid #3498db; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; text-align: center;'>
                        <p style='color: #1a5276; font-size: 15px; font-weight: 700; margin: 0; line-height: 1.5;'>⬇️ Descarga cualquier diseño que te guste y ponlo en tu prenda favorita.</p>
                    </div>
                    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='border-collapse: collapse; margin-bottom: 25px;'>
                        <tr>
                            <td width='48%' style='padding: 4px;'><img src='https://www.camiglobo.com/uploads/prod_d956e77773b4e25f_1773919167.jpg' alt='Producto Camiglobo' style='width: 100%; height: 260px; object-fit: cover; border-radius: 10px; display: block;'></td>
                            <td width='4%'></td>
                            <td width='48%' style='padding: 4px;'><img src='https://www.camiglobo.com/uploads/prod_6199b0fe5bfe87a3_1773919141.jpg' alt='Producto Camiglobo' style='width: 100%; height: 260px; object-fit: cover; border-radius: 10px; display: block;'></td>
                        </tr>
                        <tr><td colspan='3' style='height: 8px;'></td></tr>
                        <tr>
                            <td width='48%' style='padding: 4px;'><img src='https://www.camiglobo.com/uploads/prod_864dbfe5ac4f9c84_1773951653.jpg' alt='Producto Camiglobo' style='width: 100%; height: 260px; object-fit: cover; border-radius: 10px; display: block;'></td>
                            <td width='4%'></td>
                            <td width='48%' style='padding: 4px;'><img src='https://www.camiglobo.com/uploads/prod_383d05929b54d514_1773759059.jpg' alt='Producto Camiglobo' style='width: 100%; height: 260px; object-fit: cover; border-radius: 10px; display: block;'></td>
                        </tr>
                    </table>
                    <h2 style='color: #111; font-size: 20px; margin: 0 0 18px 0; text-align: center;'>🔥 ¿Qué puedes crear con Camiglobo?</h2>
                    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td width='33%' style='padding: 4px; vertical-align: top;'>
                                <div style='background: #fff5f5; border-radius: 10px; padding: 16px 12px; text-align: center; height: 100%;'>
                                    <div style='font-size: 28px; margin-bottom: 8px;'>🎨</div>
                                    <p style='margin: 0 0 6px 0; font-size: 13px; font-weight: 800; color: #111;'>CAMISETAS Y SUDADERAS</p>
                                    <p style='margin: 0; color: #666; font-size: 12px; line-height: 1.5;'>Diseña tu propia prenda. <strong>Cualquier diseño en cualquier prenda</strong>.</p>
                                </div>
                            </td>
                            <td width='33%' style='padding: 4px; vertical-align: top;'>
                                <div style='background: #f5f8ff; border-radius: 10px; padding: 16px 12px; text-align: center; height: 100%;'>
                                    <div style='font-size: 28px; margin-bottom: 8px;'>☕</div>
                                    <p style='margin: 0 0 6px 0; font-size: 13px; font-weight: 800; color: #111;'>TAZAS PERSONALIZADAS</p>
                                    <p style='margin: 0; color: #666; font-size: 12px; line-height: 1.5;'>Crea una taza única con tus fotos o diseños.</p>
                                </div>
                            </td>
                            <td width='33%' style='padding: 4px; vertical-align: top;'>
                                <div style='background: #f5fff5; border-radius: 10px; padding: 16px 12px; text-align: center; height: 100%;'>
                                    <div style='font-size: 28px; margin-bottom: 8px;'>🖼️</div>
                                    <p style='margin: 0 0 6px 0; font-size: 13px; font-weight: 800; color: #111;'>CUADROS PERSONALIZADOS</p>
                                    <p style='margin: 0; color: #666; font-size: 12px; line-height: 1.5;'>Transforma tus fotos en arte para tu pared.</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <a href='https://www.camiglobo.com/personalizar.php' style='background: linear-gradient(90deg, #000 0%, #e74c3c 100%); color: white; padding: 14px 35px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; display: inline-block; box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);'>🔥 EMPEZAR A DISEÑAR</a>
                    </div>
                    <p style='text-align: center; color: #999; font-size: 12px; margin: 0;'>
                        ¿Dudas? Responde a este email o escríbenos a <a href='mailto:camigloboshop@gmail.com' style='color: #e74c3c; text-decoration: none;'>camigloboshop@gmail.com</a>
                    </p>
                </div>
                <div style='height: 1px; background: #f0f0f0;'></div>
                <div style='background: #1a1a2e; padding: 30px 25px; text-align: center;'>
                    <div style='margin-bottom: 18px;'>
                        <img src='https://www.camiglobo.com/images/camiglobofavicon.jpg' alt='Camiglobo' style='width: 35px; height: 35px; border-radius: 8px; vertical-align: middle; margin-right: 8px;'>
                        <span style='font-size: 16px; font-weight: 900; color: #fff; vertical-align: middle; letter-spacing: 1px;'>CAMI<span style='color: #e74c3c;'>GLOBO</span></span>
                    </div>
                    <div style='margin-bottom: 18px;'>
                        <p style='margin: 0 0 6px 0; font-size: 12px; color: #b0b0c0;'>
                            <a href='mailto:camigloboshop@gmail.com' style='color: #d0d0e0; text-decoration: none;'>📧 camigloboshop@gmail.com</a>
                        </p>
                        <p style='margin: 0 0 6px 0; font-size: 12px; color: #b0b0c0;'>
                            <a href='https://maps.google.com/?q=Calle+Doctor+Bove+115+Barcelona' style='color: #d0d0e0; text-decoration: none;'>📍 C/ Doctor Bové 115, Barcelona</a>
                        </p>
                        <p style='margin: 0 0 6px 0; font-size: 12px; color: #b0b0c0;'>
                            <a href='tel:+34653851786' style='color: #d0d0e0; text-decoration: none;'>📞 +34 653 851 786</a>
                        </p>
                        <p style='margin: 0; font-size: 12px; color: #9090a0;'>🚚 Envíos a toda España</p>
                    </div>
                    <div style='margin-bottom: 18px;'>
                        <a href='https://www.instagram.com/camiglobo/' style='display: inline-block; background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); width: 32px; height: 32px; line-height: 32px; border-radius: 50%; text-decoration: none; font-size: 0; margin: 0 4px;' title='Instagram'><img src='https://cdn.simpleicons.org/instagram/ffffff' width='16' height='16' alt='Instagram' style='vertical-align: middle;'></a>
                        <a href='https://www.tiktok.com/@camiglobocamiglobo' style='display: inline-block; background: #222; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; text-decoration: none; font-size: 0; margin: 0 4px; border: 1px solid #444;' title='TikTok'><img src='https://cdn.simpleicons.org/tiktok/ffffff' width='16' height='16' alt='TikTok' style='vertical-align: middle;'></a>
                        <a href='https://wa.me/34653851786' style='display: inline-block; background: #25D366; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; text-decoration: none; font-size: 0; margin: 0 4px;' title='WhatsApp'><img src='https://cdn.simpleicons.org/whatsapp/ffffff' width='16' height='16' alt='WhatsApp' style='vertical-align: middle;'></a>
                    </div>
                    <div style='border-top: 1px solid #2a2a40; padding-top: 15px;'>
                        <p style='margin: 0 0 8px 0; font-size: 11px;'>
                            <a href='https://www.camiglobo.com/terminos-condiciones.php' style='color: #9090a0; text-decoration: none;'>Términos</a>
                            <span style='color: #404050;'> &bull; </span>
                            <a href='https://www.camiglobo.com/politica-privacidad.php' style='color: #9090a0; text-decoration: none;'>Privacidad</a>
                            <span style='color: #404050;'> &bull; </span>
                            <a href='https://www.camiglobo.com/aviso-legal.php' style='color: #9090a0; text-decoration: none;'>Aviso Legal</a>
                        </p>
                        <p style='margin: 0; font-size: 10px; color: #606070; line-height: 1.6;'>
                            © " . date('Y') . " Camiglobo Barcelona. Todos los derechos reservados.
                        </p>" . (!empty($unsubToken) ? "
                        <p style='margin: 8px 0 0 0; font-size: 10px;'><a href='https://www.camiglobo.com/baja.php?token=" . urlencode($unsubToken) . "' style='color: #9090a0; text-decoration: underline;'>Darse de baja</a></p>" : "") . "
                    </div>
                </div>
            </div>
        </div>";

        $mail->Body = $header . $body . $footer;

        // Debug (solo en desarrollo - quitar en producción)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        if (!$mail->send()) {
            error_log("PHPMailer error a $to: " . $mail->ErrorInfo);
            throw new Exception($mail->ErrorInfo);
        }

        return true;

    } catch (Exception $e) {
        error_log("Fallo crítico PHPMailer: " . $e->getMessage());
        throw $e; // Re-lanzar para que el caller pueda manejarlo
    }
}
/**
 * --- FUNCIÓN MAESTRA DE PRECIOS DINÁMICOS ---
 * Busca el precio base en la DB para evitar arrays fijos en el código.
 */
function obtenerPrecioBase($categoria) {
    global $pdo; 
    
    // Limpiamos la categoría por seguridad
    $cat = strtolower(trim($categoria));
    
    // Buscamos el precio del producto que represente esa categoría.
    // Usamos LIMIT 1 para coger el precio base.
    try {
        $stmt = $pdo->prepare("SELECT precio FROM productos WHERE categoria = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$cat]);
        $precio = $stmt->fetchColumn();
        
        // Si encontramos precio, lo devolvemos. Si no, devolvemos 0.
        return ($precio !== false) ? (float)$precio : 0.00;
    } catch (Exception $e) {
        // Si falla la DB, devolvemos 0 para no romper la ejecución
        error_log("Error obteniendo precio base para $cat: " . $e->getMessage());
        return 0.00;
    }
}
?>