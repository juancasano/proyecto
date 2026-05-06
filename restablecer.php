<?php 
/**
 * ARCHIVO: restablecer.php
 * FUNCIÓN: Interfaz final para el cambio de contraseña tras recuperación.
 * ACTUALIZACIÓN: Validación de token, confirmación de clave y blindaje CSRF.
 */

require_once 'includes/config.php';

$token = $_GET['token'] ?? '';

// --- 1. VALIDACIÓN PREVIA DEL TOKEN ---
if (empty($token)) {
    header("Location: recuperar.php");
    exit;
}

// Verificamos si el token es válido y no ha expirado (1 hora de margen)
$stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE reset_token = ? AND 
reset_expires > NOW()");$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php'; 
?>

<style>
    .reset-container {
        max-width: 480px;
        margin: 100px auto;
        padding: 20px;
        min-height: 60vh;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .reset-card {
        background: white;
        padding: 50px 40px;
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
        text-align: center;
    }

    .input-group { position: relative; margin-bottom: 25px; text-align: left; }
    .input-icon { position: absolute; left: 18px; top: 46px; color: #adb5bd; z-index: 2; }
    
    .reset-input { 
        width: 100%; padding: 16px 16px 16px 50px; border: 2px solid #f3f3f3; 
        border-radius: 15px; outline: none; transition: 0.3s; font-size: 15px; 
        background: #fafafa; font-weight: 600;
    }
    .reset-input:focus { border-color: #27ae60; background: #fff; }

    .strength-meter { height: 4px; width: 0%; background: #ddd; margin-top: 8px; border-radius: 2px; transition: 0.4s; }

    .btn-reset { 
        width: 100%; background: #27ae60; color: white; padding: 18px; border: none; 
        border-radius: 50px; font-weight: 900; cursor: pointer; transition: 0.3s; 
        font-size: 15px; text-transform: uppercase; letter-spacing: 1px;
        box-shadow: 0 10px 25px rgba(39, 174, 96, 0.2);
    }
    .btn-reset:hover { background: #219150; transform: translateY(-3px); }
    .btn-reset:disabled { background: #ccc; cursor: not-allowed; transform: none; }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="reset-container">
    <?php if (!$user): ?>
        <div class="reset-card" style="border-top: 6px solid #e74c3c;">
            <div style="background: #fff5f5; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-calendar-times" style="font-size: 35px; color: #e74c3c;"></i>
            </div>
            <h2 style="color: #2c3e50; font-weight: 900;">Enlace Expirado</h2>
            <p style="color: #95a5a6; margin: 15px 0 35px; line-height: 1.6;">Lo sentimos, este enlace de recuperación ya no es válido o ha caducado por seguridad.</p>
            <a href="recuperar.php" class="btn-reset" style="text-decoration: none; display: block; background: #2c3e50;">SOLICITAR NUEVO ENLACE</a>
        </div>

    <?php else: ?>
        <div class="reset-card">
            <div style="background: #e6fcf5; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-user-shield" style="font-size: 35px; color: #27ae60;"></i>
            </div>
            
            <h2 style="color: #2c3e50; margin: 0; font-size: 28px; font-weight: 900;">Nueva Contraseña</h2>
            <p style="color: #95a5a6; margin: 10px 0 35px; font-size: 15px;">Hola <b><?php echo htmlspecialchars(explode(' ', $user['nombre'])[0]); ?></b>, elige tu nueva clave de acceso.</p>

            <form action="procesar_nueva_clave.php" method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="input-group">
                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 800; font-size: 12px; text-transform: uppercase;">Nueva Contraseña</label>
                    <input type="password" id="pass1" name="nueva_pass" placeholder="Mínimo 8 caracteres" required class="reset-input" oninput="checkPass()">
                    <i class="fas fa-lock input-icon"></i>
                    <i class="far fa-eye" style="position: absolute; right: 18px; top: 43px; color: #adb5bd; cursor: pointer; z-index: 3;" onclick="togglePass('pass1', this)"></i>
                    <div class="strength-meter" id="strength-bar"></div>
                </div>

                <div class="input-group" style="margin-bottom: 35px;">
                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 800; font-size: 12px; text-transform: uppercase;">Repite la Contraseña</label>
                    <input type="password" id="pass2" placeholder="Confirma tu nueva clave" required class="reset-input" oninput="checkPass()">
                    <i class="fas fa-check-double input-icon"></i>
                    <i class="far fa-eye" style="position: absolute; right: 18px; top: 43px; color: #adb5bd; cursor: pointer; z-index: 3;" onclick="togglePass('pass2', this)"></i>
                </div>

                <div id="match-msg" style="display: none; font-size: 13px; font-weight: 700; margin-bottom: 25px; padding: 10px; border-radius: 10px;"></div>

                <button type="submit" id="submit-btn" class="btn-reset" disabled>
                    <span id="btn-text">ACTUALIZAR CONTRASEÑA</span>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    /**
     * LÓGICA MODO DIOS: Mostrar/Ocultar
     */
    function togglePass(id, icon) {
        const field = document.getElementById(id);
        if (field.type === "password") {
            field.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    /**
     * LÓGICA MODO DIOS: Validación en tiempo real
     */
    function checkPass() {
        const p1 = document.getElementById('pass1').value;
        const p2 = document.getElementById('pass2').value;
        const bar = document.getElementById('strength-bar');
        const msg = document.getElementById('match-msg');
        const btn = document.getElementById('submit-btn');

        // Fuerza visual
        let width = 0;
        if(p1.length >= 4) width = 33;
        if(p1.length >= 8) width = 66;
        if(p1.length >= 10 && /[A-Z]/.test(p1) && /[0-9]/.test(p1)) width = 100;
        bar.style.width = width + '%';
        bar.style.background = width < 66 ? '#ff6b6b' : (width < 100 ? '#f39c12' : '#27ae60');

        // Comparación
        if (p2.length > 0) {
            msg.style.display = 'block';
            if (p1 === p2 && p1.length >= 8) {
                msg.innerHTML = '<i class="fas fa-check-circle"></i> Las contraseñas coinciden perfectamente.';
                msg.style.color = '#27ae60';
                msg.style.background = '#e6fcf5';
                btn.disabled = false;
            } else {
                msg.innerHTML = '<i class="fas fa-times-circle"></i> ' + (p1.length < 8 ? 'Mínimo 8 caracteres.' : 'Las contraseñas no coinciden.');
                msg.style.color = '#e03131';
                msg.style.background = '#fff5f5';
                btn.disabled = true;
            }
        } else {
            msg.style.display = 'none';
            btn.disabled = true;
        }
    }

    /**
     * Feedback de carga
     */
    document.getElementById('resetForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        document.getElementById('btn-text').innerText = "PROCESANDO CAMBIO...";
        btn.style.opacity = "0.7";
    });
</script>

<?php include 'includes/footer.php'; ?>