<?php 
/**
 * ARCHIVO: registro.php
 * FUNCIÓN: Registro blindado de nuevos usuarios con validación de seguridad.
 * ACTUALIZACIÓN: Alineación de iconos corregida, protección CSRF y flujo HTTPS.
 */

require_once 'includes/config.php';

// --- 1. SEGURIDAD: Si el usuario ya tiene sesión, fuera de aquí ---
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Generamos token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Capturamos destino
$redirect = htmlspecialchars($_GET['redirect'] ?? '');

include 'includes/header.php'; 
?>

<style>
    .register-container {
        max-width: 500px;
        margin: 60px auto;
        padding: 20px;
        min-height: 75vh;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .register-card {
        background: white;
        padding: 45px;
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.07);
        border: 1px solid #f0f0f0;
    }

    .icon-circle {
        background: #e6fcf5;
        width: 80px;
        height: 80px;
        border-radius: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: #27ae60;
        font-size: 32px;
        transform: rotate(5deg);
        transition: 0.3s;
    }
    .register-card:hover .icon-circle { transform: rotate(0deg) scale(1.1); }

    /* CONTENEDOR DE INPUT CORREGIDO */
    .field-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-group { margin-bottom: 22px; }
    
    .input-group label {
        display: block; 
        margin-bottom: 8px; 
        color: #2c3e50; 
        font-weight: 800; 
        font-size: 12px; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
    }

    .input-icon { 
        position: absolute; 
        left: 18px; 
        color: #adb5bd; 
        transition: 0.3s;
        font-size: 16px;
        z-index: 10;
    }
    
    .reg-input { 
        width: 100%; 
        padding: 16px 16px 16px 50px; 
        border: 2px solid #f3f3f3; 
        border-radius: 15px; 
        outline: none; 
        transition: 0.3s; 
        font-size: 15px; 
        background: #fafafa; 
        font-weight: 600;
        font-family: inherit;
    }
    
    .reg-input:focus { 
        border-color: #27ae60; 
        background: #fff; 
        box-shadow: 0 10px 20px rgba(39, 174, 96, 0.05); 
    }
    
    /* Cambio de color del icono al enfocar */
    .reg-input:focus + .input-icon,
    .reg-input:focus ~ .input-icon { 
        color: #27ae60; 
    }

    .pass-strength { 
        height: 4px; 
        width: 0%; 
        background: #ddd; 
        margin-top: 8px; 
        border-radius: 2px; 
        transition: 0.4s; 
    }

    .btn-reg-main { 
        width: 100%; 
        background: #27ae60; 
        color: white; 
        padding: 18px; 
        border: none; 
        border-radius: 50px; 
        font-weight: 900; 
        cursor: pointer; 
        transition: 0.4s; 
        font-size: 16px; 
        letter-spacing: 1px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 10px; 
        box-shadow: 0 10px 25px rgba(39, 174, 96, 0.2);
    }
    .btn-reg-main:hover { background: #219150; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(39, 174, 96, 0.3); }

    .toggle-pass {
        position: absolute;
        right: 18px;
        color: #adb5bd;
        cursor: pointer;
        font-size: 18px;
        z-index: 10;
        transition: 0.2s;
    }
    .toggle-pass:hover { color: #27ae60; }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="register-container">
    <div class="register-card">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <div class="icon-circle">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <h2 style="color: #2c3e50; margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -1px;">Crea tu Cuenta</h2>
            <p style="color: #95a5a6; margin-top: 5px; font-size: 15px; font-weight: 500;">Únete a la familia Camiglobo Barcelona</p>
        </div>
        <div style="text-align: center; margin-top: 20px;">
    

        <?php if(isset($_GET['error'])): ?>
            <div style="background: #fff5f5; color: #e03131; padding: 15px; border-radius: 15px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #ffa8a8; font-weight: 700;">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                    if($_GET['error'] == 'email_existe') echo 'Ese correo ya está registrado.';
                    elseif($_GET['error'] == 'captcha') echo 'La verificación humana ha fallado.';
                    elseif($_GET['error'] == 'pass_short') echo 'La contraseña debe tener al menos 8 caracteres.';
                    elseif($_GET['error'] == 'pass_mismatch') echo 'Las contraseñas no coinciden.';
                    else echo 'Error al crear la cuenta. Inténtalo de nuevo.';
                ?>
            </div>
        <?php endif; ?>

        <form action="procesar_registro.php" method="POST" id="regForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

            <div class="input-group">
                <label>Nombre Completo</label>
                <div class="field-wrapper">
                    <i class="far fa-user input-icon"></i>
                    <input type="text" name="nombre" placeholder="Ej: Juan Pérez" required class="reg-input" autofocus>
                </div>
            </div>

            <div class="input-group">
                <label>Correo Electrónico</label>
                <div class="field-wrapper">
                    <i class="far fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="tu@email.com" required class="reg-input">
                </div>
            </div>

            <div class="input-group" style="margin-bottom: 10px;">
                <label>Contraseña</label>
                <div class="field-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="pass-field" name="password" placeholder="Mínimo 8 caracteres" required class="reg-input" oninput="checkStrength(this.value)">
                    <i class="far fa-eye toggle-pass" id="toggle-1" onclick="togglePassword('pass-field', 'toggle-1')"></i>
                </div>
                <div class="pass-strength" id="strength-bar"></div>
            </div>

<div class="input-group" style="margin-bottom: 30px;">
                <label>Repetir Contraseña</label>
                <div class="field-wrapper">
                    <i class="fas fa-check-double input-icon"></i>
                    <input type="password" id="pass-conf-field" name="password_confirm" placeholder="Confirma tu clave" required class="reg-input">
                    <i class="far fa-eye toggle-pass" id="toggle-2" onclick="togglePassword('pass-conf-field', 'toggle-2')"></i>
                </div>
            </div>

            <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 25px; text-align: left;">
                <input type="checkbox" name="acepta_privacidad" id="acepta_privacidad" required style="margin-top: 3px; transform: scale(1.2); accent-color: #27ae60;">
                <label for="acepta_privacidad" style="font-size: 12px; color: #666; font-weight: 500; text-transform: none; letter-spacing: 0;">
                    He leído y acepto la <a href="politica-privacidad.php" target="_blank" style="color: #2c3e50; font-weight: 800; text-decoration: underline;">Política de Privacidad</a> para la gestión de mi cuenta.
                </label>
            </div>
            <div style="display: flex; justify-content: center; margin-bottom: 35px; transform: scale(0.9);">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>
            
            

            <button type="submit" id="submit-btn" class="btn-reg-main">
                <span id="btn-text">CREAR MI CUENTA</span>
                <i class="fas fa-arrow-right" id="btn-icon"></i>
            </button>
        </form>
        <br>
        <a href="procesar_google.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; background: white; color: #333; padding: 14px; border: 1px solid #ddd; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 14px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20">
        CONTINUAR CON GOOGLE
    </a>
</div>
        <div style="text-align: center; margin-top: 35px; padding-top: 25px; border-top: 2px solid #f8f9fa;">
            <p style="color: #95a5a6; font-size: 14px; font-weight: 500;">
                ¿Ya tienes una cuenta? <br>
                <a href="login.php" style="color: #2c3e50; font-weight: 900; text-decoration: none; display: inline-block; margin-top: 8px; font-size: 15px;">Inicia sesión aquí</a>
            </p>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    /**
     * MOSTRAR/OCULTAR CONTRASEÑAS
     */
    function togglePassword(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);
        if (field.type === "password") {
            field.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
            icon.style.color = "#27ae60";
        } else {
            field.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
            icon.style.color = "#adb5bd";
        }
    }

    /**
     * FUERZA DE CONTRASEÑA
     */
    function checkStrength(val) {
        const bar = document.getElementById('strength-bar');
        let width = 0;
        if(val.length >= 4) width = 33;
        if(val.length >= 8) width = 66;
        if(val.length >= 10 && /[A-Z]/.test(val) && /[0-9]/.test(val)) width = 100;
        
        bar.style.width = width + '%';
        bar.style.background = width < 66 ? '#ff6b6b' : (width < 100 ? '#f39c12' : '#27ae60');
    }

    /**
     * VALIDACIÓN Y CARGA
     */
    document.getElementById('regForm').addEventListener('submit', function(e) {
        const pass = document.getElementById('pass-field').value;
        const conf = document.getElementById('pass-conf-field').value;
        
        if (pass !== conf) {
            e.preventDefault();
            alert("❌ Las contraseñas no coinciden. Por favor, revísalas.");
            return;
        }

        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        document.getElementById('btn-text').innerText = "CREANDO CUENTA...";
        document.getElementById('btn-icon').className = "fas fa-spinner fa-spin";
    });
</script>

<?php include 'includes/footer.php'; ?>