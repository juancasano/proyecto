<?php
/**
 * ARCHIVO: recuperar.php
 * FUNCIÓN: Diseño visual del formulario de recuperación.
 */
require_once 'includes/config.php';

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Recuperar Contraseña";
include 'includes/header.php';
?>

<div class="login-container" style="padding: 60px 20px;">
    <div class="login-card" style="max-width: 450px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        
        <div class="login-header" style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 50px; margin-bottom: 10px;">🔐</div>
            <h1 style="font-size: 24px; font-weight: 900; color: #111;">¿Olvidaste tu clave?</h1>
            <p style="color: #666;">Introduce tu email y te enviaremos un enlace mágico para entrar.</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sent'): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb;">
                <strong>✅ ¡Correo enviado!</strong><br>
                Revisa tu bandeja de entrada y la carpeta de Spam.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb;">
                <strong>❌ Error:</strong> 
                <?php 
                    echo ($_GET['error'] === 'captcha') ? 'Verifica que no eres un robot.' : 
                         (($_GET['error'] === 'invalid_email') ? 'Email no válido.' : 'Inténtalo de nuevo.');
                ?>
            </div>
        <?php endif; ?>

        <form action="procesar_recuperar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="ejemplo@correo.com" 
                       style="width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 10px;">
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: center;">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" style="width: 100%; padding: 15px; background: #2c3e50; color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer;">
                ENVIAR INSTRUCCIONES
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px;">
            <a href="login.php" style="color: #666; text-decoration: none; font-size: 14px;">← Volver al login</a>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php include 'includes/footer.php'; ?>