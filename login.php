<?php 
/**
 * ARCHIVO: login.php
 * FUNCIÓN: Puerta de acceso blindada para usuarios y administradores.
 * ACTUALIZACIÓN: Alineación de iconos, protección CSRF, redirección y RATE LIMITING.
 */

require_once 'includes/config.php';

// --- 1. SEGURIDAD: Si ya está logueado, fuera de aquí ---
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// --- 2. SEGURIDAD: RATE LIMITING BASADO EN IP (Base de Datos) ---
$bloqueado = false;
$minutos_restantes = 0;
$ip_usuario = $_SERVER['REMOTE_ADDR'];

try {
    // Consultamos directamente el historial de la IP
    $stmt_rl = $pdo->prepare("SELECT intentos, UNIX_TIMESTAMP(ultima_falla) as ts_falla FROM login_intentos WHERE ip = ?");
    $stmt_rl->execute([$ip_usuario]);
    $registro_rl = $stmt_rl->fetch(PDO::FETCH_ASSOC);

    if ($registro_rl && $registro_rl['intentos'] >= 5) {
        $tiempo_transcurrido = time() - $registro_rl['ts_falla'];
        $tiempo_bloqueo = 900; // 15 minutos (900 segundos)

        if ($tiempo_transcurrido < $tiempo_bloqueo) {
            $bloqueado = true;
            $minutos_restantes = ceil(($tiempo_bloqueo - $tiempo_transcurrido) / 60);
        } else {
            // Han pasado los 15 minutos, perdonamos a la IP limpiando la base de datos
            $pdo->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip_usuario]);
        }
    }
} catch (Exception $e) {
    error_log("Error en Rate Limiting (login.php): " . $e->getMessage());
}

// Generamos token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Limpiamos el redirect para evitar XSS (Auditoría #4)
$redirect = htmlspecialchars($_GET['redirect'] ?? '', ENT_QUOTES, 'UTF-8');
include 'includes/header.php'; 
?>

<style>
    .login-container {
        max-width: 450px;
        margin: 60px auto;
        padding: 20px;
        min-height: 70vh;
        animation: fadeIn 0.6s ease-out;
    }
    
    .login-card {
        background: white;
        padding: 45px;
        border-radius: 30px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
    }

    .login-icon-box {
        background: #fff5f5;
        width: 80px;
        height: 80px;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        transform: rotate(-5deg);
        transition: 0.3s;
    }
    .login-card:hover .login-icon-box { transform: rotate(0deg) scale(1.1); }

    /* CONTENEDOR DE INPUT CORREGIDO */
    .field-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-group { margin-bottom: 25px; }
    
    .input-group label {
        display: block; 
        margin-bottom: 10px; 
        color: #2c3e50; 
        font-weight: 800; 
        font-size: 13px; 
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
    
    .login-input { 
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
    
    .login-input:focus { 
        border-color: #ff6b6b; 
        background: #fff; 
        box-shadow: 0 10px 20px rgba(255, 107, 107, 0.05); 
    }
    
    /* Cambio de color del icono al enfocar el input */
    .login-input:focus + .input-icon,
    .login-input:focus ~ .input-icon { 
        color: #ff6b6b; 
    }

    .btn-login-main { 
        width: 100%; 
        background: #2c3e50; 
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
        box-shadow: 0 10px 25px rgba(44, 62, 80, 0.2);
    }
    .btn-login-main:hover:not(:disabled) { background: #000; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
    .btn-login-main:disabled { background: #95a5a6; cursor: not-allowed; box-shadow: none; transform: none; }

    body { background-color: #f0f0f0; }
    .btn-google {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: white; color: #333; padding: 14px; border: 1px solid #ddd;
        border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 14px;
        transition: 0.3s; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 20px;
    }
    .btn-google:hover { background: #f5f5f5; transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.15); border-color: #bbb; }

    .toggle-pass {
        position: absolute;
        right: 18px;
        color: #adb5bd;
        cursor: pointer;
        font-size: 18px;
        z-index: 10;
        transition: 0.2s;
    }
    .toggle-pass:hover { color: #ff6b6b; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
</style>

<div class="login-container">
    <div class="login-card">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <div class="login-icon-box">
                <i class="fas fa-fingerprint" style="font-size: 35px; color: #ff6b6b;"></i>
            </div>
            <h2 style="color: #2c3e50; margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -1px;">¡Hola de nuevo!</h2>
            <p style="color: #95a5a6; margin-top: 5px; font-size: 15px; font-weight: 500;">Accede a tu cuenta de Camiglobo</p>
        </div>

        <?php if($bloqueado): ?>
            <div style="background: #fff5f5; color: #c0392b; padding: 20px; border-radius: 15px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #ffb8b8; font-weight: 700; animation: shake 0.4s ease;">
                <i class="fas fa-shield-alt" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Demasiados intentos fallidos.<br>Por seguridad, espera <?php echo $minutos_restantes; ?> minutos.
            </div>
        <?php elseif(isset($_GET['error'])): ?>
            <div style="background: #fff5f5; color: #e03131; padding: 15px; border-radius: 15px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #ffa8a8; font-weight: 700; animation: shake 0.4s ease;">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                switch($_GET['error']) {
                    case 'security':
                        echo "Error de seguridad. Recarga la página e inténtalo de nuevo.";
                        break;
                    case 'google_fail':
                        echo "No se pudo conectar con Google. Inténtalo de nuevo.";
                        break;
                    default:
                        echo "Email o contraseña incorrectos.";
                }
                ?>
            </div>
        <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'timeout'): ?>
             <div style="background: #e8f8f5; color: #27ae60; padding: 15px; border-radius: 15px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #a9dfbf; font-weight: 700;">
                <i class="fas fa-clock"></i> 
                Tu sesión expiró por inactividad. Vuelve a entrar.
            </div>
        <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
             <div style="background: #e8f8f5; color: #27ae60; padding: 15px; border-radius: 15px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #a9dfbf; font-weight: 700;">
                <i class="fas fa-check-circle"></i> 
                Contraseña cambiada correctamente. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php 
        $google_enlace = "procesar_google.php";
        if (!empty($redirect)) {
            $google_enlace .= "?redirect=" . urlencode($redirect);
        }
        ?>
        <a href="<?php echo $google_enlace; ?>" class="btn-google">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20">
            CONTINUAR CON GOOGLE
        </a>

        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
            <div style="flex: 1; height: 1px; background: #eee;"></div>
            <span style="color: #aaa; font-size: 13px; font-weight: 600;">o con tu email</span>
            <div style="flex: 1; height: 1px; background: #eee;"></div>
        </div>

        <form action="procesar_login.php" method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

            <div class="input-group">
                <label>Correo Electrónico</label>
                <div class="field-wrapper">
                    <i class="far fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="ejemplo@correo.com" required class="login-input" <?php echo $bloqueado ? 'disabled' : 'autofocus'; ?>>
                </div>
            </div>

            <div class="input-group" style="margin-bottom: 10px;">
                <label>Tu Contraseña</label>
                <div class="field-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="pass-field" name="password" placeholder="••••••••" required class="login-input" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <i class="far fa-eye toggle-pass" id="toggle-icon" onclick="togglePassword()"></i>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 30px;">
                <a href="recuperar.php" style="color: #ff6b6b; text-decoration: none; font-size: 13px; font-weight: 700;">
                    ¿Has olvidado tu clave?
                </a>
            </div>

            <button type="submit" id="submit-btn" class="btn-login-main" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                <span id="btn-text"><?php echo $bloqueado ? 'ACCESO BLOQUEADO' : 'ENTRAR AL BÚNKER'; ?></span>
                <i class="fas <?php echo $bloqueado ? 'fa-lock' : 'fa-chevron-right'; ?>" id="btn-icon"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 35px; padding-top: 25px; border-top: 2px solid #f8f9fa;">
            <p style="color: #95a5a6; font-size: 14px; font-weight: 500;">
                ¿Aún no tienes cuenta? <br>
                <a href="registro.php" style="color: #ff6b6b; font-weight: 900; text-decoration: none; display: inline-block; margin-top: 8px; font-size: 15px;">Regístrate en Camiglobo</a>
            </p>
        </div>
    </div>
</div>

<script>
    /**
     * PASSWORD TOGGLE - Alineado y funcional
     */
    function togglePassword() {
        const field = document.getElementById('pass-field');
        const icon = document.getElementById('toggle-icon');
        
        // Si está bloqueado, no dejamos hacer nada
        if(field.disabled) return;

        if (field.type === "password") {
            field.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
            icon.style.color = "#ff6b6b";
        } else {
            field.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
            icon.style.color = "#adb5bd";
        }
    }

    /**
     * FEEDBACK DE CARGA
     */
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        // Evitar múltiples envíos
        const btn = document.getElementById('submit-btn');
        if (btn.disabled) {
            e.preventDefault();
            return;
        }

        const btnText = document.getElementById('btn-text');
        const btnIcon = document.getElementById('btn-icon');

        btn.disabled = true;
        btnText.innerText = "VERIFICANDO...";
        btnIcon.className = "fas fa-circle-notch fa-spin";
        btn.style.opacity = "0.8";
    });
</script>

<?php include 'includes/footer.php'; ?>