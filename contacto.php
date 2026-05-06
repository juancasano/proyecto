<?php
// ─────────────────────────────────────────────
// PROCESAMIENTO DEL FORMULARIO (ANTES DEL HEADER)
// ─────────────────────────────────────────────
require_once 'includes/config.php';

$form_success = false;
$form_error   = '';
$form_data    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contacto_submit'])) {

    // ── 1. HONEYPOT ──────────────────────────────────────────
    if (!empty($_POST['website_url'])) {
        header('Location: contacto.php');
        exit;
    }

    // ── 2. CSRF ──────────────────────────────────────────────
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $form_error = 'Petición inválida. Recarga la página e inténtalo de nuevo.';
    }

    // ── 3. RATE LIMITING (1 envío cada 5 minutos por sesión) ─
    if (!$form_error) {
        $now = time();
        if (isset($_SESSION['contacto_last_send']) && ($now - $_SESSION['contacto_last_send']) < 300) {
            $wait = ceil((300 - ($now - $_SESSION['contacto_last_send'])) / 60);
            $form_error = "Has enviado un mensaje muy recientemente. Espera {$wait} minuto(s) e inténtalo de nuevo.";
        }
    }

    // ── 4. reCAPTCHA ─────────────────────────────────────────
    if (!$form_error) {
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        if (!validarRecaptcha($recaptcha_token)) {
            $form_error = 'La verificación reCAPTCHA ha fallado. Por favor, inténtalo de nuevo.';
        }
    }

    // ── 5. SANEADO Y VALIDACIÓN DE CAMPOS ────────────────────
    if (!$form_error) {
        $nombre   = trim(filter_input(INPUT_POST, 'nombre',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $email    = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '');
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $asunto   = trim(filter_input(INPUT_POST, 'asunto',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $mensaje  = trim(filter_input(INPUT_POST, 'mensaje',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $politica = isset($_POST['politica']) ? true : false;

        $form_data = compact('nombre', 'email', 'telefono', 'asunto', 'mensaje');

        $asuntos_validos = [
            'presupuesto' => 'Solicitud de presupuesto personalizado',
            'pedido'      => 'Consulta sobre pedido existente',
            'producto'    => 'Duda sobre productos o técnicas',
            'laboral'     => 'Ropa laboral para empresa',
            'despedida'   => 'Despedida de soltero / eventos',
            'peluche'     => 'Peluches natalicio',
            'otro'        => 'Otro',
        ];

        if (empty($nombre) || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
            $form_error = 'El nombre debe tener entre 2 y 80 caracteres.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
            $form_error = 'El correo electrónico no es válido.';
        } elseif (!empty($telefono) && !preg_match('/^[+\d\s\-().]{6,20}$/', $telefono)) {
            $form_error = 'El formato del teléfono no es válido.';
        } elseif (!array_key_exists($asunto, $asuntos_validos)) {
            $form_error = 'Selecciona un asunto válido.';
        } elseif (empty($mensaje) || mb_strlen($mensaje) < 10) {
            $form_error = 'El mensaje debe tener al menos 10 caracteres.';
        } elseif (mb_strlen($mensaje) > 1500) {
            $form_error = 'El mensaje no puede superar los 1500 caracteres.';
        } elseif (!$politica) {
            $form_error = 'Debes aceptar la política de privacidad para continuar.';
        }
    }

    // ── 6. ENVÍO DE EMAILS ───────────────────────────────────
    if (!$form_error) {
        $asunto_texto = $asuntos_validos[$asunto];
        $ip_cliente   = getClientIP();
        $fecha_envio  = date('d/m/Y H:i:s');
        $tel_html     = !empty($telefono) ? h($telefono) : '<em style="color:#aaa;">No facilitado</em>';

        // Email al equipo
        $body_admin = "
            <h2 style='color:#1a1a2e; font-size:22px; margin:0 0 20px 0; border-bottom:3px solid #E8281A; padding-bottom:12px;'>
                📩 Nuevo mensaje de contacto
            </h2>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='border-collapse:collapse; margin-bottom:24px;'>
                <tr><td style='padding:10px 14px; background:#f9f7f4; border-radius:8px 8px 0 0; border-bottom:1px solid #eee;'>
                    <span style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700;'>Nombre</span><br>
                    <strong style='font-size:16px; color:#1a1a2e;'>" . h($nombre) . "</strong>
                </td></tr>
                <tr><td style='padding:10px 14px; background:#fff; border-bottom:1px solid #eee;'>
                    <span style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700;'>Email</span><br>
                    <a href='mailto:" . h($email) . "' style='font-size:16px; color:#E8281A; text-decoration:none; font-weight:600;'>" . h($email) . "</a>
                </td></tr>
                <tr><td style='padding:10px 14px; background:#f9f7f4; border-bottom:1px solid #eee;'>
                    <span style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700;'>Teléfono</span><br>
                    <span style='font-size:16px; color:#1a1a2e;'>{$tel_html}</span>
                </td></tr>
                <tr><td style='padding:10px 14px; background:#fff; border-radius:0 0 8px 8px;'>
                    <span style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700;'>Asunto</span><br>
                    <span style='font-size:16px; color:#1a1a2e; font-weight:600;'>" . h($asunto_texto) . "</span>
                </td></tr>
            </table>
            <div style='background:#f0f7ff; border-left:4px solid #3498db; border-radius:8px; padding:18px 20px; margin-bottom:20px;'>
                <p style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700; margin:0 0 8px 0;'>Mensaje</p>
                <p style='font-size:15px; color:#1a1a2e; line-height:1.75; margin:0; white-space:pre-wrap;'>" . nl2br(h($mensaje)) . "</p>
            </div>
            <p style='font-size:12px; color:#aaa; margin:0;'>📅 {$fecha_envio} &nbsp;·&nbsp; 🌐 IP: {$ip_cliente}</p>
            <div style='margin-top:20px;'>
                <a href='mailto:" . h($email) . "?subject=Re: " . rawurlencode($asunto_texto) . "' style='background:#E8281A; color:#fff; padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:700; font-size:13px; display:inline-block;'>
                    ↩ Responder a " . h($nombre) . "
                </a>
            </div>
        ";

        // Acuse de recibo al cliente
        $body_cliente = "
            <h2 style='color:#1a1a2e; font-size:22px; margin:0 0 6px 0;'>
                ¡Hola, " . h($nombre) . "! 👋
            </h2>
            <p style='color:#555; font-size:16px; line-height:1.7; margin:0 0 20px 0;'>
                Hemos recibido tu mensaje perfectamente. Nuestro equipo lo revisará y te
                <strong>responderemos en menos de 24 horas</strong> (normalmente mucho antes).
            </p>
            <div style='background:#f9f7f4; border-radius:12px; padding:18px 20px; margin-bottom:24px; border:1px solid #e8e4de;'>
                <p style='font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; font-weight:700; margin:0 0 6px 0;'>Tu mensaje</p>
                <p style='font-size:14px; color:#555; line-height:1.7; margin:0; white-space:pre-wrap;'>" . nl2br(h($mensaje)) . "</p>
            </div>
            <p style='color:#555; font-size:14px; line-height:1.7; margin:0 0 20px 0;'>
                Mientras tanto, si tu consulta es urgente, escríbenos directamente por WhatsApp:
            </p>
            <a href='https://wa.me/34653851786?text=¡Hola! Acabo de enviar un formulario de contacto.'
               style='display:inline-flex; align-items:center; gap:10px; background:#25D366; color:#fff; padding:14px 26px; border-radius:50px; text-decoration:none; font-weight:700; font-size:14px;'>
                💬 Abrir WhatsApp
            </a>
        ";

        try {
            enviarEmail(ADMIN_EMAIL, "📩 Nuevo contacto web: {$asunto_texto}", $body_admin, '#E8281A');
            enviarEmail($email, "Hemos recibido tu mensaje — Camiglobo Barcelona", $body_cliente, '#E8281A');

            $_SESSION['contacto_last_send'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            auditLog('contacto_form_enviado', "Asunto: {$asunto} | Email: {$email}");

            $form_success = true;
            $form_data    = [];

        } catch (Exception $e) {
            error_log("Error enviando email de contacto: " . $e->getMessage());
            $form_error = 'No se pudo enviar el mensaje. Por favor, inténtalo de nuevo o contáctanos por WhatsApp.';
        }
    }
}

$meta_title = "Contacto | Camiglobo Barcelona";
require_once 'includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap');

:root {
  --red: #E8281A;
  --ink: #0e0d0c;
  --paper: #f5f3ef;
  --stone: #e8e4de;
  --mist: #9a9590;
  --wa: #25D366;
}

/* ─── CURSOR ─── */
.cg-cursor {
  width: 10px; height: 10px;
  background: var(--red);
  border-radius: 50%;
  position: fixed;
  pointer-events: none;
  z-index: 9999;
  transform: translate(-50%, -50%);
  transition: width 0.3s ease, height 0.3s ease, opacity 0.3s;
  mix-blend-mode: multiply;
}
.cg-cursor.expand { width: 38px; height: 38px; opacity: 0.35; }

/* ─── HERO ─── */
.cg-hero { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; overflow: hidden; }
.cg-hero-left { background: var(--paper); padding: 130px 60px 80px; display: flex; flex-direction: column; justify-content: space-between; }
.cg-eyebrow { display: flex; align-items: center; gap: 12px; font-family: 'DM Sans', sans-serif; font-size: 0.72rem; font-weight: 500; letter-spacing: 3px; text-transform: uppercase; color: var(--mist); }
.cg-eyebrow::before { content:''; width:28px; height:1px; background:var(--mist); }
.cg-headline { font-family: 'Instrument Serif', serif; font-size: clamp(3.8rem, 6.5vw, 6.8rem); font-weight: 400; line-height: 1.0; color: var(--ink); letter-spacing: -2px; margin: auto 0; }
.cg-headline .cg-italic { font-style: italic; color: var(--red); }
.cg-hero-bottom { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; }
.cg-hero-desc { max-width: 340px; font-size: 0.95rem; color: var(--mist); line-height: 1.75; font-weight: 300; }
.cg-hero-desc b { color: var(--ink); font-weight: 500; }
.cg-scroll-hint { display: flex; flex-direction: column; align-items: center; gap: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.72rem; letter-spacing: 2px; text-transform: uppercase; color: var(--mist); animation: cg-bounce 2s ease-in-out infinite; flex-shrink: 0; }
@keyframes cg-bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(6px); } }
.cg-hero-right { background: var(--ink); display: flex; flex-direction: column; justify-content: center; align-items: flex-start; padding: 130px 60px 80px; position: relative; overflow: hidden; }
.cg-deco { position: absolute; border-radius: 50%; pointer-events: none; top: 50%; left: 50%; transform: translate(-50%, -50%); }
.cg-deco-1 { width:500px; height:500px; border:1px solid rgba(255,255,255,0.04); }
.cg-deco-2 { width:340px; height:340px; border:1px solid rgba(255,255,255,0.07); }
.cg-deco-3 { width:170px; height:170px; border:1px solid rgba(232,40,26,0.2); }
.cg-wa-icon { position: relative; z-index: 1; width: 72px; height: 72px; background: rgba(37,211,102,0.1); border: 1px solid rgba(37,211,102,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 34px; color: var(--wa); margin-bottom: 36px; animation: cg-breathe 4s ease-in-out infinite; }
@keyframes cg-breathe { 0%,100% { box-shadow: 0 0 0 0 rgba(37,211,102,0.2); } 50% { box-shadow: 0 0 0 18px rgba(37,211,102,0); } }
.cg-cta-headline { position: relative; z-index: 1; font-family: 'Instrument Serif', serif; font-size: 2.6rem; font-weight: 400; color: #fff; line-height: 1.15; letter-spacing: -0.5px; margin-bottom: 16px; }
.cg-cta-headline em { font-style: italic; color: var(--wa); }
.cg-cta-text { position: relative; z-index: 1; color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif; font-size: 0.93rem; line-height: 1.75; font-weight: 300; margin-bottom: 40px; max-width: 320px; }
.cg-cta-text b { color: rgba(255,255,255,0.75); font-weight: 400; }
.cg-btn-wa { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 10px; background: var(--wa); color: #fff; padding: 16px 28px; border-radius: 100px; text-decoration: none; font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.3px; transition: all 0.3s ease; }
.cg-btn-wa:hover { transform: scale(1.04); box-shadow: 0 16px 40px rgba(37,211,102,0.3); color: #fff; text-decoration: none; }
.cg-hours { position: relative; z-index: 1; margin-top: 28px; font-family: 'DM Sans', sans-serif; font-size: 0.78rem; color: rgba(255,255,255,0.2); letter-spacing: 0.5px; }

/* ─── CONTACT METHODS ─── */
.cg-methods { background: var(--paper); padding: 0 60px 100px; }
.cg-section-label { padding: 70px 0 40px; font-family: 'DM Sans', sans-serif; font-size: 0.72rem; letter-spacing: 3px; text-transform: uppercase; color: var(--mist); display: flex; align-items: center; gap: 16px; }
.cg-section-label::after { content:''; flex:1; height:1px; background:var(--stone); }
.cg-methods-grid { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--stone); border-radius: 20px; overflow: hidden; }
.cg-card { padding: 44px 38px; text-decoration: none; color: inherit; border-right: 1px solid var(--stone); background: #fff; display: flex; flex-direction: column; gap: 20px; position: relative; overflow: hidden; }
.cg-card:last-child { border-right: none; }
.cg-card::after { content: ''; position: absolute; inset: 0; background: var(--ink); transform: translateY(100%); transition: transform 0.42s cubic-bezier(0.76, 0, 0.24, 1); z-index: 0; }
.cg-card:hover::after { transform: translateY(0); }
.cg-card > * { position: relative; z-index: 1; }
.cg-card-icon { width: 48px; height: 48px; border-radius: 14px; background: var(--paper); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--red); transition: background 0.35s, color 0.35s; }
.cg-card.cg-wa .cg-card-icon { color: var(--wa); }
.cg-card:hover .cg-card-icon { background: rgba(255,255,255,0.07); color: #fff; }
.cg-card-type { font-family: 'DM Sans', sans-serif; font-size: 0.72rem; letter-spacing: 2px; text-transform: uppercase; color: var(--mist); font-weight: 500; transition: color 0.35s; }
.cg-card:hover .cg-card-type { color: rgba(255,255,255,0.3); }
.cg-card-value { font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--ink); line-height: 1.25; transition: color 0.35s; }
.cg-card:hover .cg-card-value { color: #fff; }
.cg-card-desc { font-family: 'DM Sans', sans-serif; font-size: 0.85rem; color: var(--mist); line-height: 1.65; font-weight: 300; transition: color 0.35s; }
.cg-card:hover .cg-card-desc { color: rgba(255,255,255,0.4); }
.cg-card-arrow { margin-top: auto; font-family: 'DM Sans', sans-serif; font-size: 0.72rem; font-weight: 500; color: var(--stone); display: flex; align-items: center; gap: 6px; letter-spacing: 1px; text-transform: uppercase; transition: color 0.35s; }
.cg-card:hover .cg-card-arrow { color: rgba(255,255,255,0.25); }

/* ══════════════════════════════════════
   SECCIÓN UNIFICADA: SOCIAL + FORMULARIO
══════════════════════════════════════ */
.cg-social-form {
  background: var(--ink);
  display: grid;
  grid-template-columns: 1fr 1fr;
}

/* ── Columna social (izquierda) ── */
.cg-sf-left {
  padding: 90px 64px;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(255,255,255,0.07);
  position: relative;
  overflow: hidden;
}
.cg-sf-left::before {
  content: '';
  position: absolute;
  width: 480px; height: 480px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.04);
  top: -140px; left: -140px;
  pointer-events: none;
}
.cg-sf-left::after {
  content: '';
  position: absolute;
  width: 220px; height: 220px;
  border-radius: 50%;
  border: 1px solid rgba(232,40,26,0.12);
  bottom: 80px; right: -60px;
  pointer-events: none;
}

.cg-sf-eyebrow {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.68rem;
  font-weight: 500;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.22);
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
  position: relative; z-index: 1;
}
.cg-sf-eyebrow::before { content:''; width:20px; height:1px; background:rgba(255,255,255,0.18); }

.cg-sf-heading {
  font-family: 'Instrument Serif', serif;
  font-size: clamp(2.4rem, 3.5vw, 3.8rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.1;
  letter-spacing: -1.5px;
  margin-bottom: 16px;
  position: relative; z-index: 1;
}
.cg-sf-heading em { font-style: italic; color: var(--red); }

.cg-sf-desc {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.92rem;
  color: rgba(255,255,255,0.38);
  line-height: 1.8;
  font-weight: 300;
  margin-bottom: 36px;
  position: relative; z-index: 1;
}

.cg-sf-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 44px;
  position: relative; z-index: 1;
}
.cg-sf-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 20px;
  border-radius: 100px;
  text-decoration: none;
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 0.85rem;
  transition: all 0.28s ease;
  border: 1px solid transparent;
}
.cg-sf-pill:hover { transform: translateY(-3px); text-decoration: none; }
.cg-sf-pill.tiktok  { background: rgba(255,255,255,0.07); color: #fff; border-color: rgba(255,255,255,0.1); }
.cg-sf-pill.tiktok:hover  { background: #fff; color: var(--ink); }
.cg-sf-pill.insta { background: linear-gradient(135deg,rgba(240,148,51,0.25),rgba(220,39,67,0.25),rgba(188,24,136,0.25)); color: #fff; border-color: rgba(255,255,255,0.08); }
.cg-sf-pill.insta:hover { background: linear-gradient(135deg,#f09433,#dc2743,#bc1888); border-color: transparent; }
.cg-sf-pill.threads { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.75); border-color: rgba(255,255,255,0.08); }
.cg-sf-pill.threads:hover { background: rgba(255,255,255,0.12); color: #fff; }

.cg-sf-video {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(0,0,0,0.55);
  position: relative; z-index: 1;
  margin-top: auto;
}
.cg-sf-video video {
  width: 100%;
  display: block;
  height: auto;
}

/* ── Columna formulario (derecha) ── */
.cg-sf-right {
  padding: 90px 64px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.cg-sf-form-tag {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.68rem;
  font-weight: 500;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--red);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.cg-sf-form-title {
  font-family: 'Instrument Serif', serif;
  font-size: clamp(1.7rem, 2.4vw, 2.6rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.15;
  letter-spacing: -0.8px;
  margin-bottom: 28px;
}
.cg-sf-form-title em { font-style: italic; color: rgba(255,255,255,0.4); }

/* ── Campos de formulario (tema oscuro) ── */
.cg-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cg-form-grid .cg-full,
.cg-checkbox-wrap,
.cg-recaptcha-wrap,
.cg-submit-row,
.cg-alert { grid-column: 1 / -1; }

.cg-field { display: flex; flex-direction: column; gap: 7px; }
.cg-label {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.38);
}
.cg-label .optional { font-weight: 300; color: rgba(255,255,255,0.2); letter-spacing: 0; text-transform: none; font-size: 0.65rem; margin-left: 5px; }

.cg-input, .cg-select, .cg-textarea {
  font-family: 'DM Sans', sans-serif; font-size: 0.93rem; font-weight: 400;
  color: #fff;
  background: rgba(255,255,255,0.06);
  border: 1.5px solid rgba(255,255,255,0.1);
  border-radius: 11px;
  padding: 13px 16px;
  outline: none;
  transition: border-color 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
  width: 100%; box-sizing: border-box; appearance: none; -webkit-appearance: none;
}
.cg-input::placeholder, .cg-textarea::placeholder { color: rgba(255,255,255,0.2); }
.cg-input:focus, .cg-select:focus, .cg-textarea:focus {
  border-color: rgba(255,255,255,0.32);
  background: rgba(255,255,255,0.09);
  box-shadow: 0 0 0 4px rgba(255,255,255,0.04);
}
.cg-input.error, .cg-select.error, .cg-textarea.error {
  border-color: rgba(232,40,26,0.65);
  box-shadow: 0 0 0 4px rgba(232,40,26,0.1);
}
.cg-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:38px; cursor:pointer; }
.cg-select option { background: #1a1918; color: #fff; }

.cg-textarea { resize: vertical; min-height: 120px; line-height: 1.65; }

.cg-char-count { font-family: 'DM Sans', sans-serif; font-size: 0.68rem; color: rgba(255,255,255,0.2); text-align: right; margin-top: -2px; transition: color 0.2s; }
.cg-char-count.warn { color: #f39c12; }
.cg-char-count.over  { color: var(--red); }

.cg-checkbox-wrap {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px;
  background: rgba(255,255,255,0.04);
  border: 1.5px solid rgba(255,255,255,0.09);
  border-radius: 11px;
  cursor: pointer;
  transition: border-color 0.22s;
}
.cg-checkbox-wrap:hover { border-color: rgba(255,255,255,0.2); }
.cg-checkbox-wrap input[type="checkbox"] { width: 17px; height: 17px; flex-shrink: 0; margin-top: 2px; accent-color: var(--red); cursor: pointer; }
.cg-checkbox-label { font-family: 'DM Sans', sans-serif; font-size: 0.82rem; color: rgba(255,255,255,0.32); line-height: 1.55; }
.cg-checkbox-label a { color: rgba(255,255,255,0.6); font-weight: 500; }
.cg-checkbox-label a:hover { color: #fff; }

.cg-recaptcha-wrap { display: flex; justify-content: flex-start; }

.cg-submit-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.cg-btn-submit {
  display: inline-flex; align-items: center; gap: 12px;
  background: #fff; color: var(--ink);
  padding: 16px 32px; border: none; border-radius: 100px;
  font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.5px;
  cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden;
}
.cg-btn-submit::after { content: ''; position: absolute; inset: 0; background: var(--red); transform: translateX(-100%); transition: transform 0.4s cubic-bezier(0.76, 0, 0.24, 1); }
.cg-btn-submit:hover::after { transform: translateX(0); }
.cg-btn-submit span, .cg-btn-submit i { position: relative; z-index: 1; }
.cg-btn-submit:hover { color: #fff; box-shadow: 0 14px 36px rgba(232,40,26,0.32); transform: translateY(-2px); }
.cg-btn-submit i { transition: transform 0.3s; }
.cg-btn-submit:hover i { transform: translateX(4px); }
.cg-submit-note { font-family: 'DM Sans', sans-serif; font-size: 0.75rem; color: rgba(255,255,255,0.22); line-height: 1.55; }
.cg-submit-note i { color: var(--red); margin-right: 4px; }

.cg-alert { padding: 14px 18px; border-radius: 11px; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; line-height: 1.6; display: flex; align-items: flex-start; gap: 12px; }
.cg-alert-error { background: rgba(232,40,26,0.12); border: 1.5px solid rgba(232,40,26,0.28); color: #ffb3ae; }
.cg-alert-success { background: rgba(37,211,102,0.08); border: 1.5px solid rgba(37,211,102,0.25); color: #7effc0; }
.cg-alert-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

/* Honeypot oculto */
.cg-honey { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }

/* ─── ABOUT ─── */
.cg-about { background: var(--ink); padding: 90px 60px; display: grid; grid-template-columns: 1fr 1.8fr; gap: 80px; align-items: start; }
.cg-about-title { font-family: 'Instrument Serif', serif; font-size: 2.8rem; font-weight: 400; color: #fff; line-height: 1.1; letter-spacing: -1px; }
.cg-about-title em { font-style: italic; color: rgba(255,255,255,0.3); }
.cg-about-right { display: flex; flex-direction: column; gap: 28px; }
.cg-about-right p { font-family: 'DM Sans', sans-serif; font-size: 1.02rem; color: rgba(255,255,255,0.45); line-height: 1.85; font-weight: 300; }
.cg-about-right b { color: rgba(255,255,255,0.82); font-weight: 400; }
.cg-stats { display: flex; gap: 50px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.07); flex-wrap: wrap; }
.cg-stat-num { font-family: 'Syne', sans-serif; font-size: 2.1rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 5px; }
.cg-stat-num span { color: var(--red); }
.cg-stat-label { font-family: 'DM Sans', sans-serif; font-size: 0.78rem; color: rgba(255,255,255,0.22); letter-spacing: 1px; text-transform: uppercase; }

/* ─── REVIEWS ─── */
.cg-reviews { padding: 80px 24px; background: #fff; border-top: 1px solid var(--stone); }
.cg-reviews-inner { max-width: 1100px; margin: 0 auto; }
.cg-reviews-inner h2 { text-align: center; color: var(--ink); margin-bottom: 8px; font-weight: 800; font-size: 1.8rem; font-family: 'Syne', sans-serif; }
.cg-reviews-inner p.sub { text-align:center; color:var(--mist); margin-bottom:40px; font-size:1rem; font-family:'DM Sans',sans-serif; }
.cg-reviews-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
.cg-review-card { background: #f9f9f9; border-radius: 20px; padding: 25px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
.cg-review-stars { color: #f1c40f; font-size: 18px; margin-bottom: 12px; }
.cg-review-text { color: #444; font-size: 0.95rem; line-height: 1.7; margin-bottom: 16px; font-family: 'DM Sans', sans-serif; }
.cg-reviewer { display: flex; align-items: center; gap: 10px; }
.cg-reviewer-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 15px; flex-shrink: 0; }
.cg-reviewer-name { font-weight: 800; font-size: 13px; color: #2c3e50; font-family: 'Syne', sans-serif; }
.cg-reviewer-city { font-size: 11px; color: #aaa; font-family: 'DM Sans', sans-serif; }

/* ─── SOCIAL (antiguo — ya no se usa directamente) ─── */

/* ─── MAP ─── */
.cg-map-section { position: relative; }
.cg-map-card { position: absolute; top: 40px; left: 60px; z-index: 10; background: #fff; border-radius: 16px; padding: 24px 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.13); max-width: 280px; }
.cg-map-card h4 { font-family: 'Syne', sans-serif; font-size: 0.82rem; font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.cg-map-card p { font-family: 'DM Sans', sans-serif; font-size: 0.85rem; color: var(--mist); line-height: 1.55; margin-bottom: 16px; }
.cg-map-link { display: inline-flex; align-items: center; gap: 7px; font-family: 'Syne', sans-serif; font-size: 0.78rem; font-weight: 600; color: var(--red); text-decoration: none; letter-spacing: 0.5px; text-transform: uppercase; transition: gap 0.2s; }
.cg-map-link:hover { gap: 12px; color: var(--red); text-decoration: none; }
.cg-map-wrap { width: 100%; height: 480px; overflow: hidden; }
.cg-map-wrap iframe { width: 100%; height: 100%; border: 0; display: block; filter: saturate(0.6) contrast(1.1); }

/* ─── REVEAL ─── */
.cg-reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.7s ease, transform 0.7s ease; }
.cg-reveal.visible { opacity: 1; transform: translateY(0); }

/* ─── RESPONSIVE ─── */
@media (max-width: 1060px) {
  .cg-social-form { grid-template-columns: 1fr; }
  .cg-sf-left { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.07); padding: 70px 40px; }
  .cg-sf-right { padding: 60px 40px; }
}
@media (max-width: 960px) {
  .cg-hero { grid-template-columns: 1fr; min-height: unset; }
  .cg-hero-left { padding: 100px 32px 60px; }
  .cg-hero-right { padding: 60px 32px; }
  .cg-methods { padding: 0 24px 70px; }
  .cg-methods-grid { grid-template-columns: 1fr; border-radius: 16px; }
  .cg-card { border-right: none; border-bottom: 1px solid var(--stone); }
  .cg-card:last-child { border-bottom: none; }
  .cg-form-grid { grid-template-columns: 1fr; }
  .cg-about { grid-template-columns: 1fr; gap: 40px; padding: 60px 30px; }
  .cg-map-card { left: 20px; top: 20px; }
  .cg-map-wrap { height: 400px; }
}
@media (max-width: 600px) {
  .cg-sf-left { padding: 60px 24px; }
  .cg-sf-right { padding: 50px 24px; }
  .cg-sf-pills { gap: 8px; }
  .cg-sf-pill { padding: 10px 16px; font-size: 0.8rem; }
  .cg-about { padding: 60px 20px; }
}

  .cg-social { flex-direction: column; align-items: flex-start; padding: 60px 30px; }
  .cg-social-pills { flex-wrap: wrap; }
  .cg-map-card { left: 20px; top: 20px; }
  .cg-map-wrap { height: 400px; }
}
@media (max-width: 500px) {
  .cg-form-wrap { padding: 28px 20px; }
}
</style>

<!-- ─── HERO ─── -->
<section class="cg-hero">
  <div class="cg-hero-left">
    <div class="cg-eyebrow">Contacto &amp; Soporte</div>
    <h1 class="cg-headline">
      Haz<br>realidad<br>tu <span class="cg-italic">idea.</span>
    </h1>
    <div class="cg-hero-bottom">
      <p class="cg-hero-desc">
        Somos un estudio creativo en el corazón de Barcelona.
        <b>Camisetas, sudaderas, tazas, cuadros, peluches natalicio y ropa laboral</b> —
        todo con calidad premium y el trato directo que nos caracteriza.
      </p>
      <div class="cg-scroll-hint">
        <span>Scroll</span>
        <i class="fas fa-chevron-down"></i>
      </div>
    </div>
  </div>
  <div class="cg-hero-right">
    <div class="cg-deco cg-deco-1"></div>
    <div class="cg-deco cg-deco-2"></div>
    <div class="cg-deco cg-deco-3"></div>
    <div class="cg-wa-icon"><i class="fab fa-whatsapp"></i></div>
    <h2 class="cg-cta-headline">¿Tienes<br>una <em>idea?</em></h2>
    <p class="cg-cta-text">
      La forma más rápida de hablar con nosotros. Cuéntanos qué necesitas y
      <b>te respondemos en pocas horas</b>. Sin formularios, sin esperas.
    </p>
    <a href="https://wa.me/34653851786?text=¡Hola Camiglobo! Vengo de la web y tengo una consulta sobre un diseño..." target="_blank" class="cg-btn-wa">
      <i class="fab fa-whatsapp"></i> Escribir por WhatsApp
    </a>
    <p class="cg-hours">Lunes a Viernes &nbsp;·&nbsp; 10:00 – 19:00 h &nbsp;·&nbsp; Barcelona</p>
  </div>
</section>

<!-- ─── CONTACT METHODS ─── -->
<section class="cg-methods">
  <div class="cg-section-label">Otras formas de contacto</div>
  <div class="cg-methods-grid cg-reveal">
    <a href="https://wa.me/34653851786" target="_blank" class="cg-card cg-wa">
      <div class="cg-card-icon"><i class="fab fa-whatsapp"></i></div>
      <div class="cg-card-type">WhatsApp Directo</div>
      <div class="cg-card-value">+34 653 851 786</div>
      <p class="cg-card-desc">Mándanos tu foto, logo o idea y te asesoramos al momento sobre técnicas, tallas y colores.</p>
      <div class="cg-card-arrow"><i class="fas fa-arrow-up-right"></i>&nbsp; Abrir chat</div>
    </a>
    <a href="mailto:camigloboshop@gmail.com" class="cg-card">
      <div class="cg-card-icon"><i class="fas fa-envelope"></i></div>
      <div class="cg-card-type">Correo Electrónico</div>
      <div class="cg-card-value">camigloboshop@gmail.com</div>
      <p class="cg-card-desc">Ideal para presupuestos de empresa, pedidos grandes o enviarnos diseños en alta resolución.</p>
      <div class="cg-card-arrow"><i class="fas fa-arrow-up-right"></i>&nbsp; Enviar email</div>
    </a>
    <a href="https://maps.google.com/?q=C/+Doctor+Bové+115,+Barcelona" target="_blank" class="cg-card">
      <div class="cg-card-icon"><i class="fas fa-location-dot"></i></div>
      <div class="cg-card-type">Nuestro Taller</div>
      <div class="cg-card-value">C/ Doctor Bové 115, BCN</div>
      <p class="cg-card-desc">Ven a tocar las calidades, ver el muestrario completo o recoger tu pedido en persona.</p>
      <div class="cg-card-arrow"><i class="fas fa-arrow-up-right"></i>&nbsp; Cómo llegar</div>
    </a>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     SECCIÓN UNIFICADA: SOCIAL + FORMULARIO
════════════════════════════════════════════════════════════ -->
<section class="cg-social-form cg-reveal" id="formulario">

  <!-- ── Columna izquierda: Social ── -->
  <div class="cg-sf-left">
    <div class="cg-sf-eyebrow">Síguenos</div>

    <h2 class="cg-sf-heading">
      Síguenos y mira<br>nuestro <em>trabajo.</em>
    </h2>
    <p class="cg-sf-desc">
      Camisetas, sudaderas, tazas y cuadros — todo en nuestras redes. Inspírate antes de pedir.
    </p>

    <div class="cg-sf-pills">
      <a href="https://www.tiktok.com/@camiglobocamiglobo" target="_blank" class="cg-sf-pill tiktok">
        <i class="fab fa-tiktok"></i> TikTok
      </a>
      <a href="https://www.instagram.com/camiglobo/" target="_blank" class="cg-sf-pill insta">
        <i class="fab fa-instagram"></i> Instagram
      </a>
      <a href="https://www.threads.net/@camiglobo" target="_blank" class="cg-sf-pill threads">
        <i class="fa-brands fa-threads"></i> Threads
      </a>
    </div>

    <div class="cg-sf-video">
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
        <div>
          <video controls muted autoplay loop playsinline style="width:100%; border-radius:12px;">
            <source src="uploads/video_35680617cf358360_1773949204.mp4" type="video/mp4">
          </video>
          <p style="text-align:center; color:rgba(255,255,255,0.6); margin-top:8px; font-size:0.82rem;">Nuestro proceso creativo</p>
        </div>
        <div>
          <video controls muted autoplay loop playsinline style="width:100%; border-radius:12px;">
            <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768094939.mp4" type="video/mp4">
          </video>
          <p style="text-align:center; color:rgba(255,255,255,0.6); margin-top:8px; font-size:0.82rem;">Personalización para la Guardia Urbana de Barcelona</p>
        </div>
        <div>
          <video controls muted autoplay loop playsinline style="width:100%; border-radius:12px;">
            <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768415046.mp4" type="video/mp4">
          </video>
          <p style="text-align:center; color:rgba(255,255,255,0.6); margin-top:8px; font-size:0.82rem;">Camiseta Stranger Things — viral en TikTok</p>
        </div>
        <div>
          <video controls muted autoplay loop playsinline style="width:100%; border-radius:12px;">
            <source src="uploads/ssstik.io_1775768996758.mp4" type="video/mp4">
          </video>
          <p style="text-align:center; color:rgba(255,255,255,0.6); margin-top:8px; font-size:0.82rem;">Vinilo textil con plotter Siser Romeo</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Columna derecha: Formulario ── -->
  <div class="cg-sf-right">

    <div class="cg-sf-form-tag">
      <i class="fas fa-paper-plane"></i> Formulario de contacto
    </div>
    <h2 class="cg-sf-form-title">
      Escríbenos<br>tu <em>consulta.</em>
    </h2>

    <?php if ($form_success): ?>
    <!-- ── ÉXITO ── -->
    <div style="padding:32px 0; text-align:center;">
      <div style="font-size:3rem; margin-bottom:18px;">✅</div>
      <h3 style="font-family:'Instrument Serif',serif; font-size:2rem; font-weight:400; color:#fff; margin:0 0 12px; letter-spacing:-0.5px;">
        ¡Mensaje <em style="font-style:italic; color:var(--red);">recibido!</em>
      </h3>
      <p style="font-family:'DM Sans',sans-serif; font-size:0.95rem; color:rgba(255,255,255,0.4); line-height:1.7; max-width:380px; margin:0 auto 28px;">
        Te hemos enviado un acuse de recibo a tu correo. Te responderemos antes de 24 h.
      </p>
      <a href="contacto.php#formulario" style="display:inline-flex; align-items:center; gap:10px; background:rgba(255,255,255,0.1); color:#fff; padding:13px 26px; border-radius:100px; text-decoration:none; font-family:'Syne',sans-serif; font-size:0.85rem; font-weight:700; border:1px solid rgba(255,255,255,0.15); transition:all 0.25s;">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
    </div>

    <?php else: ?>
    <!-- ── FORMULARIO ── -->
    <form
      method="POST"
      action="contacto.php#formulario"
      novalidate
      id="cg-contact-form"
    >
      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="contacto_submit" value="1">

      <div class="cg-honey" aria-hidden="true">
        <label for="website_url">No rellenar</label>
        <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
      </div>

      <div class="cg-form-grid">

        <?php if ($form_error): ?>
        <div class="cg-alert cg-alert-error">
          <span class="cg-alert-icon">⚠️</span>
          <span><?= h($form_error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Nombre + Email -->
        <div class="cg-field">
          <label class="cg-label" for="nombre">
            Nombre completo <span style="color:var(--red);">*</span>
          </label>
          <input
            type="text" id="nombre" name="nombre"
            class="cg-input<?= (!empty($form_error) && empty($form_data['nombre'])) ? ' error' : '' ?>"
            placeholder="Tu nombre y apellido"
            value="<?= h($form_data['nombre'] ?? '') ?>"
            maxlength="80" autocomplete="name" required
          >
        </div>

        <div class="cg-field">
          <label class="cg-label" for="email">
            Correo electrónico <span style="color:var(--red);">*</span>
          </label>
          <input
            type="email" id="email" name="email"
            class="cg-input<?= (!empty($form_error) && empty($form_data['email'])) ? ' error' : '' ?>"
            placeholder="tu@email.com"
            value="<?= h($form_data['email'] ?? '') ?>"
            maxlength="120" autocomplete="email" required
          >
        </div>

        <!-- Teléfono + Asunto -->
        <div class="cg-field">
          <label class="cg-label" for="telefono">
            Teléfono <span class="optional">(opcional)</span>
          </label>
          <input
            type="tel" id="telefono" name="telefono"
            class="cg-input"
            placeholder="+34 600 000 000"
            value="<?= h($form_data['telefono'] ?? '') ?>"
            maxlength="20" autocomplete="tel"
          >
        </div>

        <div class="cg-field">
          <label class="cg-label" for="asunto">
            Asunto <span style="color:var(--red);">*</span>
          </label>
          <select
            id="asunto" name="asunto"
            class="cg-select<?= (!empty($form_error) && empty($form_data['asunto'])) ? ' error' : '' ?>"
            required
          >
            <option value="" disabled <?= empty($form_data['asunto']) ? 'selected' : '' ?>>Elige un motivo…</option>
            <?php
            $opciones = [
              'presupuesto' => '🎨 Presupuesto personalizado',
              'pedido'      => '📦 Consulta sobre pedido',
              'producto'    => '❓ Duda sobre productos',
              'laboral'     => '👔 Ropa laboral para empresa',
              'despedida'   => '🎉 Despedida / eventos',
              'peluche'     => '🧸 Peluches natalicio',
              'otro'        => '💬 Otro',
            ];
            foreach ($opciones as $val => $label):
              $sel = (isset($form_data['asunto']) && $form_data['asunto'] === $val) ? 'selected' : '';
            ?>
            <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Mensaje -->
        <div class="cg-field cg-full">
          <label class="cg-label" for="mensaje">
            Mensaje <span style="color:var(--red);">*</span>
          </label>
          <textarea
            id="mensaje" name="mensaje"
            class="cg-textarea<?= (!empty($form_error) && (empty($form_data['mensaje']) || mb_strlen($form_data['mensaje'] ?? '') < 10)) ? ' error' : '' ?>"
            placeholder="Cuéntanos qué necesitas: tipo de prenda, cantidad, plazo, diseño…"
            maxlength="1500" required
          ><?= h($form_data['mensaje'] ?? '') ?></textarea>
          <div class="cg-char-count" id="cg-char-count">0 / 1500 caracteres</div>
        </div>

        <!-- RGPD -->
        <label class="cg-checkbox-wrap" for="politica">
          <input
            type="checkbox" id="politica" name="politica"
            <?= (!empty($form_data) && isset($_POST['politica'])) ? 'checked' : '' ?>
            required
          >
          <span class="cg-checkbox-label">
            He leído y acepto la
            <a href="politica-privacidad.php" target="_blank">Política de privacidad</a>.
            Mis datos se usarán exclusivamente para responder a esta consulta.
          </span>
        </label>

        <!-- reCAPTCHA -->
        <div class="cg-recaptcha-wrap">
          <div class="g-recaptcha" data-sitekey="<?= h(RECAPTCHA_SITE_KEY) ?>"></div>
        </div>

        <!-- Submit -->
        <div class="cg-submit-row">
          <button type="submit" class="cg-btn-submit">
            <span>Enviar mensaje</span>
            <i class="fas fa-arrow-right"></i>
          </button>
          <p class="cg-submit-note">
            <i class="fas fa-lock"></i>
            Cifrado &nbsp;·&nbsp; Sin spam &nbsp;·&nbsp; &lt;24 h
          </p>
        </div>

      </div><!-- /.cg-form-grid -->
    </form>
    <?php endif; ?>

  </div><!-- /.cg-sf-right -->
</section>

<!-- ─── ABOUT ─── -->
<section class="cg-about cg-reveal">
  <div class="cg-about-title">
    Más que una tienda.<br><em>Un estudio creativo.</em>
  </div>
  <div class="cg-about-right">
    <p>
      En <b>Camiglobo</b> trabajamos con técnicas de impresión de última generación — <b>DTF, sublimación, SubliFlock y vinilo textil</b> — para adaptarnos exactamente a lo que necesitas. Desde esa camiseta única para un regalo que no se olvida, pasando por nuestros famosos <b>peluches natalicio</b> (contáctanos para presupuesto), tazas personalizadas y equipaciones para despedidas de soltero, hasta toda la ropa laboral para que tu empresa dé la mejor imagen.
    </p>
    <p>
      Revisamos tus diseños para que queden de diez y te damos ese <b>trato directo y personal</b> que nos caracteriza. Confianza de clientes particulares, equipos deportivos y organismos como la <b>Guardia Urbana de Barcelona</b>. ¡Tú pon la idea y nosotros ponemos la magia!
    </p>
    <div class="cg-stats">
      <div>
        <div class="cg-stat-num">+10<span>K</span></div>
        <div class="cg-stat-label">Prendas entregadas</div>
      </div>
      <div>
        <div class="cg-stat-num">100<span>%</span></div>
        <div class="cg-stat-label">Calidad garantizada</div>
      </div>
      <div>
        <div class="cg-stat-num">Pocas<span>h</span></div>
        <div class="cg-stat-label">Respuesta WhatsApp</div>
      </div>
    </div>
  </div>
</section>

<!-- ─── OPINIONES ─── -->
<section class="cg-reviews cg-reveal">
  <div class="cg-reviews-inner">
    <h2>Lo que dicen nuestros clientes</h2>
    <p class="sub">Más de 10.000 prendas entregadas y más de 500 pedidos personalizados y contando ⭐</p>
    <div class="cg-reviews-grid">
      <div class="cg-review-card">
        <div class="cg-review-stars">★★★★★</div>
        <p class="cg-review-text">"Encargué una camiseta con la foto de mi perro y quedó espectacular. La calidad de impresión es brutal, los colores muy vivos. Repetiré seguro."</p>
        <div class="cg-reviewer">
          <div class="cg-reviewer-avatar" style="background:linear-gradient(135deg,#e74c3c,#ff6b6b);">M</div>
          <div><div class="cg-reviewer-name">María G.</div><div class="cg-reviewer-city">Barcelona</div></div>
        </div>
      </div>
      <div class="cg-review-card">
        <div class="cg-review-stars">★★★★★</div>
        <p class="cg-review-text">"Pedí 10 sudaderas para el equipo con nuestro logo. Trato muy cercano, me asesoraron en todo y el resultado fue perfecto."</p>
        <div class="cg-reviewer">
          <div class="cg-reviewer-avatar" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">J</div>
          <div><div class="cg-reviewer-name">Jordi P.</div><div class="cg-reviewer-city">Sabadell</div></div>
        </div>
      </div>
      <div class="cg-review-card">
        <div class="cg-review-stars">★★★★★</div>
        <p class="cg-review-text">"El editor online es súper fácil de usar, lo hice todo desde el móvil en 10 minutos. En 3 días tenía la camiseta en casa."</p>
        <div class="cg-reviewer">
          <div class="cg-reviewer-avatar" style="background:linear-gradient(135deg,#3498db,#74b9ff);">L</div>
          <div><div class="cg-reviewer-name">Laura S.</div><div class="cg-reviewer-city">Madrid</div></div>
        </div>
      </div>
      <div class="cg-review-card">
        <div class="cg-review-stars">★★★★★</div>
        <p class="cg-review-text">"Compré una taza personalizada como regalo de cumple y mi madre flipó. Muy buena calidad, el diseño se ve nítido y no se borra al lavar."</p>
        <div class="cg-reviewer">
          <div class="cg-reviewer-avatar" style="background:linear-gradient(135deg,#9b59b6,#8e44ad);">A</div>
          <div><div class="cg-reviewer-name">Andrés R.</div><div class="cg-reviewer-city">Badalona</div></div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ─── MAP ─── -->
<section class="cg-map-section">
  <div class="cg-map-wrap">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2992.8!2d2.1734!3d41.3951!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12a4a2f8b9e8e0a9%3A0x1a2b3c4d5e6f7a8b!2sCalle%20Doctor%20Bov%C3%A9%20115%2C%20Barcelona!5e0!3m2!1ses!2ses!4v1700000000000!5m2!1ses!2ses"
      allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>
</section>

<!-- reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
(function () {
  /* ── Scroll reveal ── */
  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.12 });
  document.querySelectorAll('.cg-reveal').forEach(function (el) { obs.observe(el); });

  /* ── Contador de caracteres ── */
  var textarea  = document.getElementById('mensaje');
  var charCount = document.getElementById('cg-char-count');
  if (textarea && charCount) {
    function updateCount() {
      var len = textarea.value.length;
      charCount.textContent = len + ' / 1500 caracteres';
      charCount.className = 'cg-char-count' + (len > 1400 ? ' warn' : '') + (len >= 1500 ? ' over' : '');
    }
    textarea.addEventListener('input', updateCount);
    updateCount();
  }

  /* ── Validación client-side ── */
  var form = document.getElementById('cg-contact-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (form.nombre.value.trim().length < 2) {
        e.preventDefault(); alert('Introduce tu nombre (mínimo 2 caracteres).'); form.nombre.focus(); return;
      }
      if (!emailRe.test(form.email.value.trim())) {
        e.preventDefault(); alert('Introduce un correo electrónico válido.'); form.email.focus(); return;
      }
      if (!form.asunto.value) {
        e.preventDefault(); alert('Selecciona un asunto.'); form.asunto.focus(); return;
      }
      if (form.mensaje.value.trim().length < 10) {
        e.preventDefault(); alert('El mensaje debe tener al menos 10 caracteres.'); form.mensaje.focus(); return;
      }
      if (!form.politica.checked) {
        e.preventDefault(); alert('Debes aceptar la política de privacidad.'); form.politica.focus(); return;
      }
    });
  }

  /* ── Custom cursor ── */
  var dot = document.createElement('div');
  dot.className = 'cg-cursor';
  document.body.appendChild(dot);
  document.addEventListener('mousemove', function (e) {
    dot.style.left = e.clientX + 'px';
    dot.style.top  = e.clientY + 'px';
  });
  document.querySelectorAll('a, button, label, .cg-card, .cg-pill, .cg-btn-submit').forEach(function (el) {
    el.addEventListener('mouseenter', function () { dot.classList.add('expand'); });
    el.addEventListener('mouseleave', function () { dot.classList.remove('expand'); });
  });
})();
</script>

<?php require_once 'includes/footer.php'; ?>