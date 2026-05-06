<?php
require_once 'includes/config.php';

// --- SEGURIDAD CENTRALIZADA ---
if (!esAdmin()) {
    header("Location: index.php"); 
    exit; 
}


include 'includes/header.php'; 
?>

<style>
/* ── BASE ── */
.contactos-wrap {
    padding: 40px 20px;
    min-height: 80vh;
    max-width: 1100px;
    margin: 0 auto;
}

/* ── CABECERA ── */
.contactos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 28px;
    border-bottom: 2px solid #eee;
    padding-bottom: 22px;
    flex-wrap: wrap;
}
.contactos-header h1 { color:#000; margin:0 0 4px; font-weight:900; font-size:2.2rem; }
.contactos-header p  { color:#888; margin:0; font-size:14px; }

/* ── STATS ── */
.stats-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    color: white; padding: 24px 22px;
    border-radius: 20px; position: relative; overflow: hidden;
}
.stat-card .bg-icon {
    position:absolute; right:16px; bottom:6px;
    font-size:70px; opacity:.1;
}
.stat-card h4 {
    margin:0 0 6px; opacity:.75;
    text-transform:uppercase; font-size:11px; letter-spacing:2px;
}
.stat-card .num { font-size:38px; font-weight:900; line-height:1; }

/* ── DESKTOP: dos columnas lado a lado ── */
.desktop-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 24px;
}
.panel-card {
    background: white;
    border-radius: 22px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    overflow: hidden;
}
.panel-card-header {
    padding: 20px 22px 16px;
    border-bottom: 2px solid #f5f5f5;
    display: flex; align-items: center; gap: 10px;
    font-weight: 800; font-size: 15px; color: #000;
}



/* ── TABLA ── */
.tabla-contactos { width:100%; border-collapse:collapse; }
.tabla-contactos thead tr {
    text-align:left; color:#c0c0c0;
    font-size:10px; text-transform:uppercase; letter-spacing:1.5px;
    border-bottom: 2px solid #f0f0f0;
}
.tabla-contactos th { padding: 12px 18px; }
.tabla-contactos tbody tr {
    border-bottom: 1px solid #f7f7f7;
    transition: background 0.15s;
}
.tabla-contactos tbody tr:hover { background: #fafafa; }
.tabla-contactos td { padding: 12px 18px; vertical-align: middle; }

/* ── CONTENIDO FILA ── */
.nombre-cliente { font-weight:800; color:#111; font-size:14px; }
.badge-reg {
    display:inline-block; margin-top:3px;
    font-size:10px; background:#f0f0f0;
    padding:2px 8px; border-radius:8px; color:#888;
}
.contacto-email { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.email-link {
    font-size:13px; color:#e74c3c; font-weight:600;
    text-decoration:none; word-break:break-all;
}
.email-link:hover { text-decoration:underline; }
.contacto-tel { display:flex; align-items:center; gap:7px; margin-top:6px; }
.tel-link {
    font-size:13px; color:#555; font-weight:600;
    text-decoration:none;
}
.tel-link:hover { color:#000; }

.btn-icon {
    border:none; background:#f2f2f2; color:#666;
    padding:5px 9px; border-radius:7px;
    cursor:pointer; font-size:11px; flex-shrink:0;
    transition:background 0.15s;
    text-decoration:none; display:inline-flex; align-items:center;
}
.btn-icon:hover  { background:#e5e5e5; }
.btn-icon.wa     { background:#e8f8f0; color:#25D366; }
.btn-icon.wa:hover { background:#d2f0e1; }

/* suscriptores */
.news-email-link {
    font-weight:600; font-size:14px; color:#333;
    text-decoration:none; word-break:break-all;
}
.news-email-link:hover { color:#e74c3c; }

/* ── BOTÓN ENVIAR MASIVO ── */
.btn-enviar-masivo {
    background: #e74c3c;
    color: white; border: none;
    padding: 8px 16px; border-radius: 50px;
    font-weight: 800; font-size: 12px;
    cursor: pointer; display: flex; align-items: center; gap: 6px;
    transition: opacity 0.2s, transform 0.15s;
    white-space: nowrap;
}
.btn-enviar-masivo:hover { opacity: 0.88; transform: scale(1.03); }

.mobile-send-bar {
    padding: 12px 0 4px;
}

/* ── BOTÓN ENVÍO INDIVIDUAL ── */
.btn-send-individual { color: #3498db !important; border-color: #3498db !important; }
.btn-send-individual:hover { background: #eaf2ff !important; color: #2980b9 !important; }

/* ── TOAST ── */
.toast-copy {
    position:fixed; bottom:30px; left:50%;
    transform:translateX(-50%) translateY(20px);
    background:#111; color:white;
    padding:10px 24px; border-radius:30px;
    font-size:13px; font-weight:700;
    opacity:0; pointer-events:none;
    transition:opacity .3s, transform .3s;
    z-index:9999;
}
.toast-copy.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* ── MOBILE: activa tabs, oculta grid
   ══════════════════════════════════ */
.tabs-nav  { display: none; }
.tab-panel { display: none; }

@media (max-width: 700px) {
    .contactos-header h1 { font-size:1.5rem; }
    .stat-card .num      { font-size:28px; }

    /* Ocultar layout desktop */
    .desktop-grid { display:none !important; }

    /* Mostrar tabs */
    .tabs-nav {
        display: flex;
        border-bottom: 2px solid #eee;
        margin-bottom: 0;
    }
    .tab-btn {
        flex:1; padding:12px 10px;
        border:none; background:none;
        font-weight:800; font-size:13px; color:#aaa;
        cursor:pointer;
        border-bottom:3px solid transparent;
        margin-bottom:-2px;
        display:flex; align-items:center; justify-content:center; gap:6px;
        transition:0.2s;
    }
    .tab-btn.active { color:#000; border-bottom-color:#e74c3c; }
    .tab-btn .badge {
        background:#eee; color:#777;
        font-size:10px; padding:2px 7px;
        border-radius:20px; font-weight:700;
    }
    .tab-btn.active .badge { background:#e74c3c; color:white; }

    .tab-panel {
        display: block;
        background:white;
        border-radius:0 0 18px 18px;
        box-shadow:0 8px 24px rgba(0,0,0,0.06);
        border:1px solid #f0f0f0; border-top:none;
        overflow:hidden;
    }
    .tab-panel:not(.active) { display: none; }
    .tabla-contactos th,
    .tabla-contactos td { padding:11px 13px; }
}
</style>

<div class="contactos-wrap">

    <div class="contactos-header">
        <div>
            <h1>Gestión de <span style="color:#e74c3c;">Contactos</span></h1>
            <p>Administra tu base de datos y exporta para campañas de marketing.</p>
        </div>
    </div>

    <?php
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $totalNews  = 0;
        try { $totalNews = $pdo->query("SELECT COUNT(*) FROM newsletter")->fetchColumn(); } catch(Exception $e){}
    ?>

    <div class="stats-grid">
        <div class="stat-card" style="background:#111;">
            <i class="fas fa-user-check bg-icon"></i>
            <h4>Clientes Web</h4>
            <div class="num"><?php echo $totalUsers; ?></div>
        </div>
        <div class="stat-card" style="background:#e74c3c;">
            <i class="fas fa-envelope-open-text bg-icon"></i>
            <h4>Suscriptores Newsletter</h4>
            <div class="num"><?php echo $totalNews; ?></div>
        </div>
    </div>

    <?php
    /* ── Queries compartidos ─────────────────────── */
    $stmt  = $pdo->query("SELECT nombre, email, telefono FROM usuarios ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $newsRows = [];
    try {
        $stmtN    = $pdo->query("SELECT email FROM newsletter ORDER BY id DESC");
        $newsRows = $stmtN->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}

    /* ── Helper: bloque de fila cliente ── */
    function renderClientRow($user) { ?>
        <tr>
            <td>
                <div class="nombre-cliente"><?php echo htmlspecialchars($user['nombre']); ?></div>
                <span class="badge-reg">Registrado</span>
            </td>
            <td>
                <div class="contacto-email">
                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="email-link" title="Enviar email">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </a>
                    <button onclick="copiar('<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', 'Email copiado')" class="btn-icon" title="Copiar email">
                        <i class="far fa-copy"></i>
                    </button>
                    <button onclick="abrirModal('clientes', ['<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>'])" class="btn-icon btn-send-individual" title="Enviar email">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <?php if(!empty($user['telefono'])): ?>
                    <div class="contacto-tel">
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $user['telefono']); ?>" class="tel-link" title="Llamar">
                            <?php echo htmlspecialchars($user['telefono']); ?>
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['telefono']); ?>" target="_blank" class="btn-icon wa" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <button onclick="copiar('<?php echo htmlspecialchars($user['telefono'], ENT_QUOTES); ?>', 'Teléfono copiado')" class="btn-icon" title="Copiar teléfono">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php }

    function renderNewsRow($news) { ?>
        <tr>
            <td>
                <a href="mailto:<?php echo htmlspecialchars($news['email']); ?>" class="news-email-link" title="Enviar email">
                    <?php echo htmlspecialchars($news['email']); ?>
                </a>
            </td>
            <td style="text-align:right;">
                <button onclick="copiar('<?php echo htmlspecialchars($news['email'], ENT_QUOTES); ?>', 'Email copiado')" class="btn-icon" title="Copiar email">
                        <i class="far fa-copy"></i>
                    </button>
                    <button onclick="abrirModal('newsletter', ['<?php echo htmlspecialchars($news['email'], ENT_QUOTES); ?>'])" class="btn-icon btn-send-individual" title="Enviar email">
                        <i class="fas fa-paper-plane"></i>
                    </button>
            </td>
        </tr>
    <?php }
    ?>

    <!-- ══ DESKTOP: dos columnas ══ -->
    <div class="desktop-grid">

        <div class="panel-card">
            <div class="panel-card-header" style="justify-content:space-between;">
                <span><i class="fas fa-address-book" style="color:#27ae60;"></i> Agenda de Clientes</span>
                <button onclick="abrirModal('clientes')" class="btn-enviar-masivo">
                    <i class="fas fa-paper-plane"></i> Enviar a todos
                </button>
            </div>
            <table class="tabla-contactos">
                <thead><tr><th>Usuario</th><th>Contacto</th></tr></thead>
                <tbody>
                    <?php foreach($users as $user) renderClientRow($user); ?>
                </tbody>
            </table>
        </div>

        <div class="panel-card">
            <div class="panel-card-header" style="justify-content:space-between;">
                <span><i class="fas fa-paper-plane" style="color:#e74c3c;"></i> Suscriptores</span>
                <button onclick="abrirModal('newsletter')" class="btn-enviar-masivo">
                    <i class="fas fa-paper-plane"></i> Enviar a todos
                </button>
            </div>
            <table class="tabla-contactos">
                <thead><tr><th>Email</th><th></th></tr></thead>
                <tbody>
                    <?php if($newsRows): ?>
                        <?php foreach($newsRows as $news) renderNewsRow($news); ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="padding:24px;text-align:center;color:#bbb;">No hay suscriptores aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ══ MOBILE: tabs ══ -->
    <div class="tabs-nav">
        <button class="tab-btn active" onclick="switchTab('agenda', this)">
            <i class="fas fa-address-book"></i> Agenda
            <span class="badge"><?php echo $totalUsers; ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('suscriptores', this)">
            <i class="fas fa-paper-plane"></i> Suscriptores
            <span class="badge"><?php echo $totalNews; ?></span>
        </button>
    </div>

    <!-- MOBILE: botones envío masivo arriba -->
    <div id="mobile-btn-agenda" class="mobile-send-bar" style="display:none; margin-top:12px;">
        <button onclick="abrirModal('clientes')" class="btn-enviar-masivo" style="width:100%;">
            <i class="fas fa-paper-plane"></i> Enviar email a todos los clientes
        </button>
    </div>
    <div id="mobile-btn-newsletter" class="mobile-send-bar" style="display:none; margin-top:12px;">
        <button onclick="abrirModal('newsletter')" class="btn-enviar-masivo" style="width:100%;">
            <i class="fas fa-paper-plane"></i> Enviar email a todos los suscriptores
        </button>
    </div>

    <div id="tab-agenda" class="tab-panel active">
        <table class="tabla-contactos">
            <thead><tr><th>Usuario</th><th>Contacto</th></tr></thead>
            <tbody>
                <?php foreach($users as $user) renderClientRow($user); ?>
            </tbody>
        </table>
    </div>

    <div id="tab-suscriptores" class="tab-panel">
        <table class="tabla-contactos">
            <thead><tr><th>Email</th><th></th></tr></thead>
            <tbody>
                <?php if($newsRows): ?>
                    <?php foreach($newsRows as $news) renderNewsRow($news); ?>
                <?php else: ?>
                    <tr><td colspan="2" style="padding:24px;text-align:center;color:#bbb;">No hay suscriptores aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /.contactos-wrap -->

<!-- ══ MODAL ENVÍO MASIVO ══ -->
<div id="modal-envio" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:10000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:white; border-radius:24px; max-width:560px; width:100%; padding:36px; box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative; max-height:90vh; overflow-y:auto;">
        <button onclick="cerrarModal()" style="position:absolute; top:16px; right:20px; background:none; border:none; font-size:22px; color:#aaa; cursor:pointer; line-height:1;">✕</button>

        <h2 style="margin:0 0 4px; font-weight:900; font-size:1.4rem;">Enviar campaña</h2>
        <p id="modal-subtitulo" style="color:#888; font-size:13px; margin:0 0 24px;"></p>

        <input type="hidden" id="modal-tipo">

        <div style="margin-bottom:16px;">
            <label style="font-weight:700; font-size:13px; display:block; margin-bottom:6px;">Asunto del email</label>
            <input id="modal-asunto" type="text" placeholder="Ej: ¡Novedades de Camiglobo! 🎈"
                style="width:100%; box-sizing:border-box; border:1.5px solid #e8e8e8; border-radius:10px; padding:12px 14px; font-size:14px; outline:none; transition:border 0.2s;"
                onfocus="this.style.borderColor='#e74c3c'" onblur="this.style.borderColor='#e8e8e8'">
        </div>

        <div style="margin-bottom:24px;">
            <label style="font-weight:700; font-size:13px; display:block; margin-bottom:6px;">Mensaje</label>
            <textarea id="modal-mensaje" rows="7" placeholder="Escribe aquí el cuerpo del email. Puedes usar saltos de línea."
                style="width:100%; box-sizing:border-box; border:1.5px solid #e8e8e8; border-radius:10px; padding:12px 14px; font-size:14px; resize:vertical; outline:none; font-family:inherit; transition:border 0.2s;"
                onfocus="this.style.borderColor='#e74c3c'" onblur="this.style.borderColor='#e8e8e8'"></textarea>
            <p style="font-size:11px; color:#bbb; margin:6px 0 0;">El mensaje se enviará con la plantilla de Camiglobo (logo, pie, enlace de baja).</p>
        </div>

        <div id="modal-resultado" style="display:none; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;"></div>

        <div style="display:flex; gap:12px; justify-content:flex-end; flex-wrap:wrap;">
            <button onclick="cerrarModal()" style="border:1.5px solid #eee; background:white; padding:11px 22px; border-radius:50px; font-weight:700; cursor:pointer; font-size:13px;">Cancelar</button>
            <button id="btn-confirmar-envio" onclick="confirmarEnvio()"
                style="background:#e74c3c; color:white; border:none; padding:11px 26px; border-radius:50px; font-weight:800; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:8px; transition:opacity 0.2s;">
                <i class="fas fa-paper-plane"></i> <span id="btn-envio-txt">Enviar ahora</span>
            </button>
        </div>
    </div>
</div>

<div class="toast-copy" id="toast">✓ Copiado</div>

<script>
/* ── TABS MOBILE ── */
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.mobile-send-bar').forEach(b => b.style.display = 'none');
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
    var barId = tab === 'agenda' ? 'mobile-btn-agenda' : 'mobile-btn-newsletter';
    document.getElementById(barId).style.display = 'block';
}

var csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

/* ── COPIAR ── */
function copiar(texto, msg) {
    var mostrar = function() {
        var t = document.getElementById('toast');
        t.textContent = '✓ ' + msg;
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 2000);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(mostrar).catch(function() { fallbackCopiar(texto); mostrar(); });
    } else { fallbackCopiar(texto); mostrar(); }
}
function fallbackCopiar(texto) {
    var ta = document.createElement('textarea');
    ta.value = texto;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
}

/* ── MODAL ── */
var subtitulos = {
    clientes:   'Se enviará a todos los clientes registrados.',
    newsletter: 'Se enviará a todos los suscriptores del newsletter.'
};
var emailsSeleccionados = [];

function abrirModal(tipo, emails) {
    emailsSeleccionados = emails || [];
    var cant = emailsSeleccionados.length;
    document.getElementById('modal-tipo').value = tipo;
    document.getElementById('modal-subtitulo').textContent = cant > 0
        ? 'Se enviará a ' + cant + ' destinatario' + (cant > 1 ? 's' : '') + ' seleccionado' + (cant > 1 ? 's' : '') + '.'
        : subtitulos[tipo] || subtitulos.newsletter;
    document.getElementById('modal-asunto').value = '';
    document.getElementById('modal-mensaje').value = '';
    document.getElementById('modal-resultado').style.display = 'none';
    document.getElementById('btn-envio-txt').textContent = 'Enviar ahora';
    document.getElementById('btn-confirmar-envio').disabled = false;
    document.getElementById('modal-envio').style.display = 'flex';
    setTimeout(function() { document.getElementById('modal-asunto').focus(); }, 100);
}
function cerrarModal() {
    document.getElementById('modal-envio').style.display = 'none';
}
document.getElementById('modal-envio').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

function confirmarEnvio() {
    var tipo    = document.getElementById('modal-tipo').value;
    var asunto  = document.getElementById('modal-asunto').value.trim();
    var mensaje = document.getElementById('modal-mensaje').value.trim();
    var res     = document.getElementById('modal-resultado');

    if (!asunto || !mensaje) {
        res.innerHTML = '⚠️ Rellena el asunto y el mensaje.';
        res.style.cssText = 'display:block; background:#fff8e1; color:#856404; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;';
        return;
    }

    var btn = document.getElementById('btn-confirmar-envio');
    btn.disabled = true;
    document.getElementById('btn-envio-txt').textContent = 'Enviando…';
    res.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando emails, no cierres esta ventana…';
    res.style.cssText = 'display:block; background:#f0f7ff; color:#1a5276; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;';

    var fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('tipo', tipo);
    fd.append('asunto', asunto);
    fd.append('mensaje', mensaje);

    if (emailsSeleccionados.length > 0) {
        fd.append('destinatarios', JSON.stringify(emailsSeleccionados));
    }

    fetch('enviar_masivo.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                res.innerHTML = data.msg;
                res.style.cssText = 'display:block; background:#eafaf1; color:#1e6a3e; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;';
                document.getElementById('btn-envio-txt').textContent = 'Enviado ✓';
                setTimeout(function() { cerrarModal(); }, 1000);
            } else {
                res.innerHTML = '✗ ' + data.msg;
                res.style.cssText = 'display:block; background:#fdecea; color:#922b21; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;';
                btn.disabled = false;
                document.getElementById('btn-envio-txt').textContent = 'Reintentar';
            }
        })
        .catch(function() {
            res.innerHTML = '✗ Error de conexión. Inténtalo de nuevo.';
            res.style.cssText = 'display:block; background:#fdecea; color:#922b21; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;';
            btn.disabled = false;
            document.getElementById('btn-envio-txt').textContent = 'Reintentar';
        });
}

/* Inicializar barra móvil activa por defecto */
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 700) {
        document.getElementById('mobile-btn-agenda').style.display = 'block';
    }
});
</script>

<?php include 'includes/footer.php'; ?>