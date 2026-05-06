<?php 
require_once 'includes/config.php';

// --- CONFIGURACIÓN SEO Y SEGURIDAD ---
$meta_title = "¡Pedido Confirmado!";
$noindex = true; 

include 'includes/header.php'; 

// Recogemos el ID con seguridad
$id_pago = isset($_GET['id']) ? h($_GET['id']) : null;


// --- VALIDACIÓN DE INTEGRIDAD ---
// Comprobamos si el pedido existe realmente en la base de datos
$existe = false;
if ($id_pago) {
    $stmtCheck = $pdo->prepare("SELECT id FROM pedidos WHERE id_pago = ? AND user_id = ? LIMIT 1");
    $stmtCheck->execute([$id_pago, $_SESSION['user_id'] ?? 0]);
    if ($stmtCheck->fetch()) {
        $existe = true;
    }
}

// Si no existe o no hay ID, mostramos error elegante
if (!$id_pago || !$existe) {
    echo "<div class='container' style='text-align:center; padding:100px 20px;'>
            <h2 style='font-weight:900;'>Ups, no encontramos los detalles...</h2>
            <p>Si acabas de realizar una compra, revisa tu email de confirmación o entra en tu perfil.</p>
            <a href='index.php' class='btn-final-outline' style='margin: 20px auto;'>VOLVER A TIENDA</a>
          </div>";
    include 'includes/footer.php';
    exit;
}

$es_bizum = (strpos($id_pago, 'BZ-') === 0);
$msj_wa = rawurlencode("¡Hola Camiglobo! 👋 Aquí tienes el comprobante de mi pedido #$id_pago");
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

<div class="container" style="max-width: 850px; margin: 60px auto; padding: 0 20px; text-align: center; min-height: 75vh;">
    
    <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 40px; animation: fadeInDown 0.8s ease;">
        <img src="images/camiglobofavicon.jpg" style="height: 50px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
        <span style="font-weight: 900; font-size: 32px; letter-spacing: -1.5px; color: #000;">CAMI<span style="color:#e74c3c;">GLOBO</span></span>
    </div>

    <div class="success-icon-container">
        <i class="fas fa-check"></i>
    </div>

    <h1 class="main-title">
        <?php echo $es_bizum ? "¡Pedido Registrado!" : "¡Pago Confirmado!"; ?>
    </h1>
    
    <p class="sub-text">
        <i class="fas fa-star" style="color:#f1c40f;"></i> Tu diseño ya está en manos de nuestros artesanos <i class="fas fa-star" style="color:#f1c40f;"></i>
    </p>

    <div class="ticket-card">
        <div class="ticket-stripe"></div>
        
        <p class="ticket-label">NÚMERO DE REFERENCIA</p>
        <span class="ticket-id"><?php echo $id_pago; ?></span>

        <div class="order-timeline">
            <?php if($es_bizum): ?>
                <div class="step active"><i class="fas fa-credit-card"></i><br>Pago</div>
                <div class="step"><i class="fas fa-hammer"></i><br>Taller</div>
                <div class="step"><i class="fas fa-truck"></i><br>Envío</div>
            <?php else: ?>
                <div class="step completed"><i class="fas fa-credit-card"></i><br>Pago</div>
                <div class="step active"><i class="fas fa-hammer"></i><br>Taller</div>
                <div class="step"><i class="fas fa-truck"></i><br>Envío</div>
            <?php endif; ?>
        </div>

        <div class="status-grid">
            <div class="status-item">
                <small>Estado:</small>
                <p>
                    <?php if($es_bizum): ?>
                        <i class="fas fa-clock" style="color: #f39c12;"></i> Esperando Pago
                    <?php else: ?>
                        <i class="fas fa-magic" style="color: #3498db;"></i> En Producción
                    <?php endif; ?>
                </p>
            </div>
            <div class="status-item">
                <small>Entrega:</small>
                <p><i class="fas fa-truck-fast" style="color: #27ae60;"></i> <?php echo $es_bizum ? 'Tras confirmar pago' : '24 - 48h'; ?></p>
            </div>
        </div>

        <div class="trust-footer">
            <i class="fas fa-shield-check"></i> Transacción Protegida por Camiglobo Barcelona
        </div>
    </div>

    <?php if($es_bizum): ?>
        <div class="bizum-section">
            <p style="font-weight: 800; color: #2c3e50; margin-bottom: 15px;">Para agilizar tu envío, mándanos el pantallazo:</p>
            <a href="https://wa.me/34653851786?text=<?php echo $msj_wa; ?>" target="_blank" class="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> ENVIAR COMPROBANTE AHORA
            </a>
        </div>
    <?php endif; ?>

    <div class="nav-actions">
        <a href="perfil.php" class="btn-final-outline">
            <i class="fas fa-box-open"></i> VER MIS PEDIDOS
        </a>
        <a href="javascript:window.print()" class="btn-final-outline" style="border-color: #eee; color: #aaa;">
            <i class="fas fa-print"></i> IMPRIMIR TICKET
        </a>
        <a href="index.php" class="btn-final-outline" style="border-color: #ddd; color: #999;">
            <i class="fas fa-shopping-cart"></i> VOLVER A LA TIENDA
        </a>
    </div>
</div>

<style>
    /* VARIABLES Y RESET */
    .main-title { color: #2c3e50; font-weight: 900; font-size: clamp(32px, 5vw, 46px); margin-bottom: 10px; letter-spacing: -2px; }
    .sub-text { color: #27ae60; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 45px; }

    /* ICONO ÉXITO */
    .success-icon-container {
        width: 110px; height: 110px; background: #27ae60; color: white;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 55px; margin: 0 auto 30px; box-shadow: 0 20px 40px rgba(39, 174, 96, 0.25);
        animation: rotateIn 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
    }

    /* TARJETA TICKET */
    .ticket-card { background: white; border-radius: 40px; padding: clamp(25px, 5vw, 50px); border: 1px solid #f0f0f0; box-shadow: 0 40px 80px rgba(0,0,0,0.06); margin-bottom: 40px; position: relative; overflow: hidden; }
    .ticket-stripe { position: absolute; top: 0; left: 0; width: 100%; height: 10px; background: linear-gradient(90deg, #000, #e74c3c, #27ae60); }
    .ticket-label { text-transform: uppercase; font-size: 11px; font-weight: 900; color: #bbb; margin-bottom: 15px; letter-spacing: 4px; }
    .ticket-id { font-family: 'Courier New', monospace; font-size: clamp(24px, 4vw, 32px); font-weight: 900; color: #000; background: #f9f9f9; padding: 15px 30px; border-radius: 20px; border: 2px dashed #ccc; display: inline-block; margin-bottom: 35px; }

    /* TIMELINE */
    .order-timeline { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; max-width: 400px; margin-left: auto; margin-right: auto; }
    .step { font-size: 10px; font-weight: 900; color: #ddd; text-transform: uppercase; flex: 1; }
    .step.completed { color: #27ae60; }
    .step.active { color: #3498db; }
    .step i { font-size: 18px; margin-bottom: 5px; }

    /* GRID ESTADO */
    .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; text-align: left; background: #fcfcfc; padding: 25px; border-radius: 25px; border: 1px solid #f5f5f5; }
    .status-item small { color: #999; font-weight: 800; text-transform: uppercase; font-size: 9px; display: block; }
    .status-item p { margin: 0; color: #2c3e50; font-weight: 900; font-size: 16px; }

    .trust-footer { margin-top: 30px; display: flex; align-items: center; justify-content: center; gap: 8px; color: #95a5a6; font-size: 10px; font-weight: 800; text-transform: uppercase; }

    /* BOTONES */
    .bizum-section { margin-bottom: 50px; animation: pulse 2s infinite; }
    .btn-whatsapp { background: #27ae60; color: white; text-decoration: none; padding: 20px 45px; border-radius: 60px; font-weight: 900; display: inline-flex; align-items: center; gap: 15px; font-size: 18px; box-shadow: 0 20px 40px rgba(39, 174, 96, 0.3); transition: 0.3s; }
    .btn-whatsapp:hover { transform: scale(1.05); }

    .nav-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
    .btn-final-outline { text-decoration: none; color: #000; border: 2.5px solid #000; padding: 15px 35px; border-radius: 60px; font-weight: 900; font-size: 12px; transition: 0.3s; display: flex; align-items: center; gap: 10px; }
    .btn-final-outline:hover { background: #000; color: white; transform: translateY(-5px); }

    @keyframes rotateIn { 0% { transform: scale(0) rotate(-180deg); opacity: 0; } 100% { transform: scale(1) rotate(0); opacity: 1; } }
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
    
    @media print { .nav-actions, .bizum-section, header, footer { display: none !important; } }
</style>

<script>
    window.onload = function() {
        const duration = 4 * 1000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

        function randomInRange(min, max) { return Math.random() * (max - min) + min; }

        const interval = setInterval(function() {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            const particleCount = 40 * (timeLeft / duration);
            
            confetti(Object.assign({}, defaults, { 
                particleCount, 
                origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                colors: ['#e74c3c', '#27ae60', '#000000'] 
            }));
            confetti(Object.assign({}, defaults, { 
                particleCount, 
                origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                colors: ['#e74c3c', '#27ae60', '#000000'] 
            }));
        }, 250);
    };
</script>

<?php include 'includes/footer.php'; ?>
