<?php 
/**
 * ARCHIVO: perfil.php
 * FUNCIÓN: Centro de control del usuario con ecosistema cerrado (Pedidos, Galería, Datos).
 * ACTUALIZACIÓN: Limpieza de sistema de reparto y consolidación de Tracking Profesional.
 */

require_once 'includes/config.php';

// 1. SEGURIDAD: Solo usuarios registrados (Validación previa al renderizado)
if (!isset($_SESSION['user_id'])) { 
    header("Location: https://www.camiglobo.com/login.php");
    exit; 
}

// Generamos el token CSRF si no existe (Protección Pentesting)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = (int)$_SESSION['user_id'];

// OBTENER DATOS ACTUALES (Sincronizado con base de datos u867490154_camiglobo)
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // OBTENER HISTORIAL DE PEDIDOS (Con tracking_url del Paso 1) - Con filtro de mes
    $mes_seleccionado = $_GET['mes'] ?? '';
    $where = "WHERE user_id = ?";
    $params = [$user_id];

    if ($mes_seleccionado) {
        $ultimo_dia = date('t', strtotime($mes_seleccionado . '-15'));
        $where .= " AND fecha >= ? AND fecha <= ?";
        $params[] = $mes_seleccionado . '-01 00:00:00';
        $params[] = $mes_seleccionado . '-' . $ultimo_dia . ' 23:59:59';
    }

    $stmtP = $pdo->prepare("SELECT * FROM pedidos $where ORDER BY fecha DESC");
    $stmtP->execute($params);
    $pedidos = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // OBTENER DETALLES DE CADA PEDIDO (productos, imágenes, extras)
    $detalles_por_pedido = [];
    if (!empty($pedidos)) {
        $pedido_ids = array_column($pedidos, 'id');
        $placeholders = implode(',', array_fill(0, count($pedido_ids), '?'));
        $stmtDet = $pdo->prepare("
            SELECT pd.*, p.imagen_url as producto_imagen, p.nombre as producto_nombre
            FROM pedidos_detalle pd
            LEFT JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id IN ($placeholders)
            ORDER BY pd.id ASC
        ");
        $stmtDet->execute($pedido_ids);
        $detalles_raw = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        foreach ($detalles_raw as $d) {
            $detalles_por_pedido[$d['pedido_id']][] = $d;
        }
    }

    // OBTENER RECURSOS (Biblioteca del usuario para integración con Paso 3)
    $stmtR = $pdo->prepare("SELECT * FROM biblioteca_recursos WHERE user_id = ? ORDER BY fecha DESC");
    $stmtR->execute([$user_id]);
    $recursos = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    
    // OBTENER DIRECCIONES GUARDADAS
    $stmtD = $pdo->prepare("SELECT * FROM user_direcciones WHERE user_id = ? ORDER BY predeterminada DESC, id DESC");
    $stmtD->execute([$user_id]);
    $direcciones = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error crítico en perfil.php: " . $e->getMessage());
}

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

<style>
    /* intl-tel-input: solo para el modal de dirección */
    #modal-nueva-dir .iti { width: 100%; display: block; }
    #modal-nueva-dir .iti__flag-container { border-radius: 12px 0 0 12px; }
    #modal-nueva-dir .iti--separate-dial-code .iti__selected-dial-code { 
        font-weight: 700; color: #333; font-size: 14px; margin-left: 8px;
    }
    #modal-nueva-dir #tel_dir { padding-left: 90px !important; }
    #modal-nueva-dir .iti__country-list { 
        border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #eee;
    }
</style>

<style>
    :root {
        --p-color: #000000;
        --s-color: #27ae60;
        --m-color: #e74c3c;
        --grad-camiglobo: linear-gradient(90deg, #000000 0%, #e74c3c 50%, #27ae60 100%);
    }

    /* Estilos de Navegación Lateral */
    .nav-btn-profile { 
        width: 100%; text-align: left; background: #f9f9f9; border: 1px solid #eee; 
        padding: 18px; border-radius: 15px; cursor: pointer; color: #444; 
        font-weight: 800; font-size: 14px; transition: 0.3s; display: flex; 
        align-items: center; gap: 12px; margin-bottom: 12px;
    }
    .nav-btn-profile:hover { border-color: var(--m-color); color: var(--m-color); background: white; }
    .nav-btn-profile.active { background: var(--grad-camiglobo); color: white; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }

    /* Tarjetas de Pedidos */
    .order-card { 
        background: white; padding: 25px; border-radius: 20px; border: 1px solid #f1f1f1; 
        margin-bottom: 20px; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.02);
    }
    .order-card:hover { border-color: var(--s-color); transform: translateY(-3px); }

    /* Inputs y Botones */
    .p-input { 
        width: 100%; padding: 15px; border: 2px solid #f1f1f1; border-radius: 12px; 
        outline: none; transition: 0.3s; font-size: 14px; background: #fafafa; font-weight: 600;
    }
    .p-input:focus { border-color: var(--m-color); background: #fff; box-shadow: 0 5px 15px rgba(231, 76, 60, 0.05); }
    
    .p-btn-main { 
        width: 100%; background: var(--grad-camiglobo); color: white; padding: 18px; 
        border: none; border-radius: 50px; font-weight: 900; cursor: pointer; 
        transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; font-size: 13px;
    }
    .p-btn-main:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); filter: brightness(1.1); }
    .p-btn-main:disabled { background: #ccc !important; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

    /* Galería Premium */
    .image-box-profile { 
        background: white; border-radius: 18px; border: 1px solid #eee; 
        overflow: hidden; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }
    .image-box-profile:hover { transform: translateY(-8px) scale(1.03); border-color: var(--s-color); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

    .status-badge { font-size: 10px; color: white; padding: 6px 12px; border-radius: 30px; font-weight: 900; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }

    /* Botón Tracking / Acciones */
    .btn-tracking {
        display: block; background: #000; color: #fff; text-decoration: none; padding: 12px; 
        border-radius: 12px; font-size: 11px; font-weight: 800; text-align: center; 
        margin-top: 10px; transition: 0.3s; border: 1px solid #000;
    }
    .btn-tracking:hover { background: var(--s-color); border-color: var(--s-color); transform: scale(1.02); }

    /* Botón Cancelar */
    .btn-cancelar {
        display: block; background: #e74c3c; color: white; text-decoration: none; padding: 8px; 
        border-radius: 12px; font-size: 10px; font-weight: 800; text-align: center; 
        margin-top: 10px; transition: 0.3s; border: 1px solid #e74c3c;
    }
    .btn-cancelar:hover { background: #c0392b; border-color: #c0392b; transform: scale(1.02); }

    .btn-designer-link { color: var(--p-color); font-size: 18px; transition: 0.2s; text-decoration: none; }
    .btn-designer-link:hover { color: var(--m-color); transform: scale(1.2); }

    /* Tarjetas de direcciones */
    .addr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
    .addr-card { background: #fff; border: 2px solid #eee; border-radius: 15px; padding: 20px; position: relative; transition: 0.3s; }
    .addr-card:hover { border-color: var(--p-color); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .addr-card.predeterminada { border-color: var(--s-color); background: #f0faf4; }
    .addr-title { font-weight: 900; color: var(--p-color); font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
    .addr-body { font-size: 13px; color: #666; line-height: 1.6; }
    .btn-del-addr { position: absolute; top: 15px; right: 15px; background: #fff5f5; color: var(--m-color); border: none; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .btn-del-addr:hover { background: var(--m-color); color: white; transform: scale(1.1); }
    .btn-default-addr { background: #f0faf4; color: var(--s-color); border: 1px solid #a8e6c0; padding: 5px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; margin-top: 12px; }
    .btn-default-addr:hover { background: var(--s-color); color: white; }
    .badge-default { display: inline-flex; align-items: center; gap: 4px; background: var(--s-color); color: white; font-size: 10px; font-weight: 900; padding: 3px 8px; border-radius: 20px; margin-left: 6px; }

    /* Alertas */
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 850px) {
        .profile-grid { grid-template-columns: 1fr !important; }
        aside { order: 1; }
        main { order: 2; }
    }
</style>

<div class="container" style="max-width: 1200px; margin: 50px auto; padding: 0 20px; min-height: 85vh;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 style="color: #000; font-weight: 900; font-size: 2.4rem; margin: 0; letter-spacing: -1px;">
                ¡Hola, <?php echo htmlspecialchars($user['nombre']); ?>! 👋
            </h1>
            <p style="color: #7f8c8d; font-size: 1.1rem; font-weight: 500;">Tu centro creativo y de gestión personal.</p>
        </div>
        <div style="text-align: right;">
            <img src="https://www.camiglobo.com/images/camiglobofavicon.jpg" style="height: 65px; border-radius: 18px; box-shadow: 0 8px 20px rgba(0,0,0,0.1);" onerror="this.src='images/placeholder.png'">
        </div>
    </div>

    <?php if(isset($_GET['msg']) || isset($_GET['error'])): ?>
        <div id="status-alert" style="animation: slideDown 0.5s ease-out;">
            <?php if(isset($_GET['msg'])): ?>
                <div style="background: #111; color: white; padding: 20px; border-radius: 15px; margin-bottom: 35px; display: flex; align-items: center; gap: 15px; border-left: 6px solid var(--s-color); box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <i class="fas fa-check-circle" style="color: var(--s-color); font-size: 24px;"></i> 
                    <span style="font-weight: 700;">
                        <?php 
                            if($_GET['msg'] == 'updated') echo "Tus datos se han guardado correctamente.";
                            if($_GET['msg'] == 'pass_ok') echo "Contraseña actualizada. ¡Tu cuenta es ahora un búnker!";
                            if($_GET['msg'] == 'deleted') echo "Imagen eliminada de tu biblioteca.";
                            if($_GET['msg'] == 'pedido_cancelado') echo "✅ Pedido cancelado correctamente.";
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div style="background: #fff5f5; color: #e74c3c; padding: 20px; border-radius: 15px; margin-bottom: 35px; display: flex; align-items: center; gap: 15px; border-left: 6px solid #e74c3c; border: 1px solid #ffebeb;">
                    <i class="fas fa-shield-alt" style="font-size: 24px;"></i> 
                    <span style="font-weight: 700;">
                        <?php
                            if($_GET['error'] == 'pass_wrong') echo "La contraseña actual es incorrecta.";
                            if($_GET['error'] == 'pass_mismatch') echo "Las nuevas contraseñas no coinciden.";
                            if($_GET['error'] == 'captcha') echo "La verificación de seguridad ha fallado.";
                            if($_GET['error'] == 'security_token') echo "Error de seguridad (CSRF). Por favor, recarga.";
                            if($_GET['error'] == 'no_cancelable') echo "No se puede cancelar este pedido (ya no está pendiente).";
                            if($_GET['error'] == 'no_permission') echo "No tienes permiso para cancelar este pedido.";
                            if($_GET['error'] == 'security') echo "Error de seguridad. Intenta de nuevo.";
                            if($_GET['error'] == 'db') echo "Error al procesar. Intenta de nuevo.";
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid" style="display: grid; grid-template-columns: 320px 1fr; gap: 50px;">
        
        <aside>
            <div style="background: white; padding: 30px; border-radius: 25px; border: 1px solid #eee; box-shadow: 0 15px 40px rgba(0,0,0,0.03); position: sticky; top: 100px;">
                <button onclick="showTab('tab-pedidos', this)" class="nav-btn-profile active">
                    <i class="fas fa-box"></i> Mis Pedidos
                </button>
                <button onclick="showTab('tab-imagenes', this)" class="nav-btn-profile">
                    <i class="fas fa-images"></i> Mi Biblioteca
                </button>
                <button onclick="showTab('tab-datos', this)" class="nav-btn-profile">
                    <i class="fas fa-map-marker-alt"></i> Datos de Envío
                </button>
                <button onclick="showTab('tab-pass', this)" class="nav-btn-profile">
                    <i class="fas fa-lock"></i> Seguridad
                </button>
                
                <hr style="border: 0; border-top: 1px solid #f0f0f0; margin: 25px 0;">
                
                <a href="logout.php" style="text-decoration: none; color: #95a5a6; font-weight: 800; display: flex; align-items: center; gap: 12px; padding: 12px; font-size: 13px; transition: 0.3s;" onmouseover="this.style.color='var(--m-color)'">
                    <i class="fas fa-power-off"></i> CERRAR SESIÓN SEGURA
                </a>
            </div>
        </aside>

        <main>
            
            <!-- ====== PESTAÑA: PEDIDOS ====== -->
            <div id="tab-pedidos" class="account-tab">
                <h3 style="margin-bottom: 35px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50;">Compras Recientes</h3>

                <!-- Filtro por mes/año -->
                <?php
                // Obtener meses con pedidos
                $meses_stmt = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as mes FROM pedidos WHERE user_id = ? ORDER BY mes DESC");
                $meses_stmt->execute([$user_id]);
                $meses_disponibles = $meses_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($meses_disponibles)): ?>
                <form method="GET" style="background: #f9f9f9; padding: 20px; border-radius: 15px; margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="tab" value="pedidos">
                    <div>
                        <label style="display: block; font-size: 12px; color: #888; margin-bottom: 5px; font-weight: bold;">Filtrar por mes</label>
                        <select name="mes" style="padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; min-width: 180px;">
                            <option value="">Todos los meses</option>
                            <?php foreach($meses_disponibles as $m): ?>
                                <option value="<?php echo $m['mes']; ?>" <?php echo ($_GET['mes'] ?? '') == $m['mes'] ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($m['mes'] . '-15')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="background: var(--m-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="?tab=pedidos" style="color: #666; text-decoration: none; padding: 10px;">Limpiar</a>
                </form>
                <?php endif; ?>

                <?php if(empty($pedidos)): ?>
                    <div style="text-align: center; padding: 100px 40px; background: white; border-radius: 30px; border: 2px dashed #eee;">
                        <i class="fas fa-shopping-bag" style="font-size: 60px; color: #f5f5f5; margin-bottom: 25px;"></i>
                        <h4 style="color: #95a5a6; font-weight: 800;">Aún no has realizado pedidos</h4>
                        <a href="personalizar.php" class="p-btn-main" style="text-decoration: none; display: inline-block; width: auto; padding: 18px 50px; margin-top: 20px;">CREAR MI PRIMER DISEÑO</a>
                    </div>
                <?php else: ?>
                    <?php foreach($pedidos as $p): ?>
                        <div class="order-card" id="pedido-<?php echo $p['id']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                                <div style="flex: 1; min-width: 250px;">
                                    <a href="mi_pedido_detalle.php?id=<?php echo $p['id']; ?>" style="text-decoration: none;" title="Ver detalles del pedido">
                                        <span style="font-size: 10px; color: #bbb; font-weight: 800; text-transform: uppercase; display: inline-block; transition: color 0.2s;" onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#bbb'">
                                            REF: <?php echo htmlspecialchars($p['id_pago'] ?? 'S/N'); ?> <i class="fas fa-external-link-alt" style="font-size: 0.8em; opacity: 0.6;"></i>
                                        </span>
                                    </a>
                                    <h4 style="margin: 10px 0; color: #000; font-weight: 900; font-size: 1.3rem;"><?php echo date('d M, Y H:i', strtotime($p['fecha'])); ?></h4>
                                    
                                    <div style="font-size: 14px; color: #555; line-height: 1.6; background: #fcfcfc; padding: 15px; border-radius: 12px; border: 1px solid #f5f5f5;">
                                        <?php 
                                            $prod_text = htmlspecialchars($p['productos']); 
                                            $prod_text = preg_replace('/(https?:\/\/[^\s]+)/', '<br><a href="$1" target="_blank" style="color:var(--m-color); font-weight:bold; text-decoration:none;"><i class="fas fa-eye"></i> VER MI DISEÑO</a>', $prod_text);
                                            echo nl2br($prod_text); 
                                        ?>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; min-width: 150px;">
                                    <div style="font-weight: 900; color: #000; font-size: 26px; margin-bottom: 12px;"><?php echo formatPrecio($p['total']); ?></div>
                                    
                                    <?php 
                                        $est = strtolower(trim($p['estado']));
                                        
                                        if($est == 'enviado'): ?>
                                            <span class="status-badge" style="background: var(--s-color);"><i class="fas fa-shipping-fast"></i> EN CAMINO</span>
                                            
                                            <?php if(!empty($p['tracking_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($p['tracking_url']); ?>" target="_blank" class="btn-tracking">
                                                    <i class="fas fa-map-marker-alt"></i> RASTREAR PAQUETE
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif($est == 'pagado' || $est == 'en taller'): ?>
                                            <span class="status-badge" style="background: #3498db;"><i class="fas fa-magic"></i> EN PRODUCCIÓN</span>
                                            
                                        <?php elseif($est == 'entregado'): ?>
                                            <span class="status-badge" style="background: #2c3e50;"><i class="fas fa-check-double"></i> ENTREGADO</span>
                                            
                                        <?php elseif(strpos($est, 'pendiente') !== false): ?>
                                            <span class="status-badge" style="background: #f39c12; margin-bottom:10px;">
                                                <i class="fas fa-clock"></i> PENDIENTE PAGO
                                            </span>
                                            <form method="POST" action="cancelar_pedido.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres cancelar este pedido?\n\nEsta acción no se puede deshacer.')">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <button type="submit" class="btn-cancelar">
                                                    <i class="fas fa-times"></i> CANCELAR PEDIDO
                                                </button>
                                            </form>
                                            
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #f39c12;">
                                                <i class="fas fa-clock"></i> <?php echo strtoupper($p['estado']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <a href="mi_pedido_detalle.php?id=<?php echo $p['id']; ?>" 
                                           style="display: block; background: var(--grad-camiglobo); color: white; padding: 12px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 12px; text-transform: uppercase; margin-top: 15px; text-align: center; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                            <i class="fas fa-eye"></i> VER DETALLE
                                        </a>
                                </div>
                            </div>

                            <?php
                            // DETALLES DEL PEDIDO (productos individuales con imágenes)
                            $detalles = $detalles_por_pedido[$p['id']] ?? [];
                            if (!empty($detalles)): ?>
                            <div style="margin-top: 20px; border-top: 1px solid #f0f0f0; padding-top: 20px;">
                                <div style="margin-bottom: 10px; font-size: 13px; font-weight: 800; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-box-open" style="margin-right: 6px;"></i> Productos (<?= count($detalles) ?>)
                                </div>
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($detalles as $det):
                                        $img_producto = '';
                                        if (!empty($det['imagen_custom'])) {
                                            $img_producto = (strpos($det['imagen_custom'], 'http') === 0 || strpos($det['imagen_custom'], '/') === 0) ? $det['imagen_custom'] : '/' . ltrim($det['imagen_custom'], '/');
                                        } elseif (!empty($det['producto_imagen'])) {
                                            $img_producto = (strpos($det['producto_imagen'], 'http') === 0 || strpos($det['producto_imagen'], '/') === 0) ? $det['producto_imagen'] : '/' . ltrim($det['producto_imagen'], '/');
                                        }
                                    ?>
                                    <div style="display: flex; gap: 15px; padding: 15px; background: #fafafa; border-radius: 12px; border: 1px solid #f0f0f0; flex-wrap: wrap; align-items: flex-start;">
                                        <?php if (!empty($img_producto) && !empty($det['producto_id'])): ?>
                                            <a href="producto.php?id=<?= urlencode($det['producto_id']) ?>" style="flex-shrink: 0;">
                                                <img src="<?= h($img_producto) ?>" style="width: 80px; height: 80px; object-fit: contain; background: white; border-radius: 10px; border: 1px solid #eee;">
                                            </a>
                                        <?php endif; ?>

                                        <div style="flex: 1; min-width: 150px;">
                                            <?php if (!empty($det['producto_imagen'])): ?>
                                                <a href="producto.php?id=<?= urlencode($det['producto_id']) ?>" style="font-size:14px; color:#000; font-weight:bold; text-decoration:none;"><?= h($det['nombre'] ?? 'Producto') ?></a>
                                            <?php else: ?>
                                                <strong style="font-size:14px; color:#000;"><?= h($det['nombre'] ?? 'Producto') ?></strong>
                                                <span style="font-size:10px; color:#999; font-style:italic;"> (no disponible en catálogo)</span>
                                            <?php endif; ?>
                                            <?php
                                            $materiales = ['azulejo', 'aluminio', 'pizarra', 'cerámica blanca 11oz', 'cerámica blanca'];
                                            $es_material = in_array(strtolower($det['talla'] ?? ''), $materiales, true);
                                            $label_talla = $es_material ? 'Material' : 'Talla';
                                            ?>
                                            <div style="margin-top: 4px; font-size: 12px; color: #888;">
                                                <?= $label_talla ?>: <strong><?= h($det['talla'] ?? 'N/A') ?></strong>
                                                <?php if (!empty($det['color'])): ?>
                                                     &middot; Color: <strong><?= h($det['color']) ?></strong>
                                                <?php endif; ?>
                                                 &middot; <strong>x<?= (int)$det['cantidad'] ?></strong>
                                            </div>

                                            <?php if (!empty($det['extras_descripcion'])): ?>
                                                <div style="margin-top: 8px; font-size: 11px; color: #e74c3c; font-weight: bold;">
                                                    <i class="fas fa-star"></i> Extras: <?= h($det['extras_descripcion']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($det['notas'])): ?>
                                                <div style="margin-top: 6px; font-size: 11px; color: #e67e22; font-weight: bold;">
                                                    📝 <?= h($det['notas']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            // Diseños custom (frontal, espalda, logos extras)
                                            $has_designs = !empty($det['imagen_custom']) || !empty($det['imagen_espalda']) || !empty($det['logos_extras']);
                                            if ($has_designs):
                                                $fixPath = function($path) {
                                                    if (empty($path)) return null;
                                                    return (strpos($path, 'http') === 0 || strpos($path, '/') === 0) ? $path : '/' . ltrim($path, '/');
                                                };
                                            ?>
                                            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                                <?php if ($urlF = $fixPath($det['imagen_custom'])): ?>
                                                    <div style="text-align: center; border: 1px solid #eee; padding: 5px; border-radius: 8px; background: white;">
                                                        <small style="display: block; font-weight: 900; font-size: 8px; color: #999; margin-bottom: 3px;">FRONTAL</small>
                                                        <a href="<?= h($urlF) ?>" target="_blank">
                                                            <img src="<?= h($urlF) ?>" style="max-width: 70px; max-height: 70px; border-radius: 6px; border: 1px solid #000;">
                                                        </a>
                                                    </div>
                                                <?php endif;

                                                if ($urlB = $fixPath($det['imagen_espalda'])): ?>
                                                    <div style="text-align: center; border: 1px solid #eee; padding: 5px; border-radius: 8px; background: white;">
                                                        <small style="display: block; font-weight: 900; font-size: 8px; color: #999; margin-bottom: 3px;">ESPALDA</small>
                                                        <a href="<?= h($urlB) ?>" target="_blank">
                                                            <img src="<?= h($urlB) ?>" style="max-width: 70px; max-height: 70px; border-radius: 6px; border: 1px solid #000;">
                                                        </a>
                                                    </div>
                                                <?php endif;

                                                if (!empty($det['logos_extras'])):
                                                    $logos = json_decode($det['logos_extras'], true);
                                                    if (is_array($logos)):
                                                        foreach ($logos as $zona => $ruta):
                                                            $url_logo = $fixPath($ruta);
                                                            if ($url_logo): ?>
                                                                <div style="text-align: center; border: 1px solid #eee; padding: 5px; border-radius: 8px; background: white;">
                                                                    <small style="display: block; font-weight: 900; font-size: 8px; color: #999; margin-bottom: 3px;"><?= h(strtoupper(substr($zona, 0, 4))) ?></small>
                                                                    <a href="<?= h($url_logo) ?>" target="_blank">
                                                                        <img src="<?= h($url_logo) ?>" style="max-width: 70px; max-height: 70px; border-radius: 6px; border: 1px solid #000;">
                                                                    </a>
                                                                </div>
                                                    <?php endif;
                                                        endforeach;
                                                    endif;
                                                endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ====== PESTAÑA: BIBLIOTECA ====== -->
            <div id="tab-imagenes" class="account-tab" style="display: none;">
                <h3 style="margin-bottom: 10px; font-weight: 900; text-transform: uppercase; color: #2c3e50;">Mi Biblioteca Creativa</h3>
                <p style="color: #7f8c8d; font-size: 15px; margin-bottom: 35px;">Usa tus imágenes guardadas para crear nuevos diseños al instante.</p>
                
                <?php if(empty($recursos)): ?>
                    <div style="text-align: center; padding: 80px 40px; border-radius: 30px; border: 2px dashed #eee; color: #bdc3c7;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 40px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p style="font-weight: 700;">No tienes imágenes guardadas.</p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 25px;">
                        <?php foreach($recursos as $r): ?>
                            <div class="image-box-profile">
                                <div style="height: 180px; background: #fdfdfd; display: flex; align-items: center; justify-content: center; padding: 15px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($r['ruta_imagen']); ?>', '_blank')">
                                    <img src="<?php echo htmlspecialchars($r['ruta_imagen']); ?>" style="max-width: 100%; max-height: 100%; border-radius: 10px; object-fit: contain;">
                                </div>
                                <div style="padding: 15px; background: #fff; display: flex; justify-content: space-around; border-top: 1px solid #f9f9f9; align-items: center;">
                                    <a href="personalizar.php?recurso_id=<?php echo $r['id']; ?>" class="btn-designer-link" title="Diseñar con esta foto">
                                        <i class="fas fa-paint-brush"></i>
                                    </a>
                                    <a href="<?php echo htmlspecialchars($r['ruta_imagen']); ?>" download title="Descargar" style="color: var(--s-color); font-size: 18px;"><i class="fas fa-download"></i></a>
                                    <form action="procesar_perfil.php" method="POST" style="display:inline; margin:0; padding:0;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="accion" value="borrar_recurso">
    <input type="hidden" name="id_recurso" value="<?php echo $r['id']; ?>">
    <button type="submit" onclick="return confirm('¿Eliminar imagen definitivamente?')" title="Borrar" style="background: none; border: none; color: var(--m-color); font-size: 18px; cursor: pointer; padding: 0;">
        <i class="fas fa-trash-alt"></i>
    </button>
</form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ====== PESTAÑA: DATOS DE ENVÍO / DIRECCIONES ====== -->
            <div id="tab-datos" class="account-tab" style="display: none;">
                <h3 style="margin-bottom: 30px; font-weight: 900; text-transform: uppercase; color: #2c3e50;">Direcciones y Datos</h3>

                <div style="background: #f9f9f9; padding: 20px; border-radius: 15px; border: 1px solid #eee; margin-bottom: 30px;">
                    <label style="font-weight: 900; font-size: 11px; color: #95a5a6; text-transform: uppercase; margin-bottom: 8px; display: block;">📧 Correo Electrónico (No modificable):</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="p-input" readonly style="background: #ebebeb; cursor: not-allowed; color: #777;" title="Contacta a soporte si necesitas cambiar tu email.">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f1f1; padding-bottom: 15px;">
                    <h3 style="margin: 0; color: #2c3e50; font-weight: 900; font-size: 16px;">Tus Direcciones de Envío</h3>
                    <button type="button" onclick="document.getElementById('modal-nueva-dir').style.display='flex'" style="background: var(--s-color); color: white; border: none; padding: 10px 15px; border-radius: 10px; font-weight: 800; cursor: pointer; font-size: 12px;"><i class="fas fa-plus"></i> Nueva Dirección</button>
                </div>

                <div class="addr-grid">
                    <?php if (empty($direcciones)): ?>
                        <div style="grid-column: 1/-1; padding: 30px; text-align: center; color: #999; background: #fafafa; border-radius: 15px; border: 1px dashed #ccc;">
                            No tienes direcciones guardadas. ¡Añade una para comprar más rápido!
                        </div>
                    <?php else: ?>
                        <?php foreach($direcciones as $dir): ?>
                            <div class="addr-card <?php echo $dir['predeterminada'] ? 'predeterminada' : ''; ?>">
                                <div style="display: flex; gap: 5px; margin-bottom: 10px;">
                                    <button type="button" class="btn-edit-addr" title="Editar dirección" onclick="editarDireccion(<?php echo $dir['id']; ?>, '<?php echo h($dir['alias']); ?>', '<?php echo h($dir['nombre']); ?>', '<?php echo h($dir['direccion']); ?>', '<?php echo h($dir['ciudad']); ?>', '<?php echo h($dir['cp']); ?>', '<?php echo h($dir['telefono']); ?>')"><i class="fas fa-edit"></i></button>
                                    <form action="procesar_perfil.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="accion" value="borrar_direccion">
                                        <input type="hidden" name="id_dir" value="<?php echo $dir['id']; ?>">
                                        <button type="submit" class="btn-del-addr" title="Borrar dirección" onclick="return confirm('¿Seguro que quieres borrar esta dirección?');"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                                <div class="addr-title">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($dir['alias']); ?>
                                    <?php if($dir['predeterminada']): ?>
                                        <span class="badge-default"><i class="fas fa-check"></i> Por defecto</span>
                                    <?php endif; ?>
                                </div>
                                <div class="addr-body">
                                    <strong><?php echo htmlspecialchars($dir['nombre']); ?></strong><br>
                                    <?php echo htmlspecialchars($dir['direccion']); ?><br>
                                    <?php echo htmlspecialchars($dir['ciudad']); ?> (<?php echo htmlspecialchars($dir['cp']); ?>)<br>
                                    <i class="fas fa-phone-alt" style="font-size: 10px; margin-top:8px;"></i> <?php echo htmlspecialchars($dir['telefono']); ?>
                                </div>
                                <?php if(!$dir['predeterminada']): ?>
                                    <form action="procesar_perfil.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="accion" value="predeterminada_direccion">
                                        <input type="hidden" name="id_dir" value="<?php echo $dir['id']; ?>">
                                        <button type="submit" class="btn-default-addr">
                                            <i class="fas fa-star"></i> Poner por defecto
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== PESTAÑA: SEGURIDAD ====== -->
            <div id="tab-pass" class="account-tab" style="display: none;">
                <h3 style="margin-bottom: 30px; font-weight: 900; text-transform: uppercase; color: #2c3e50;">Seguridad de la Cuenta</h3>
                
                <form action="procesar_perfil.php" method="POST" id="form-seguridad" style="max-width: 550px; background: white; padding: 40px; border-radius: 30px; border: 1px solid #eee;">
                    <input type="hidden" name="accion" value="password">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div style="margin-bottom: 25px;">
                        <label style="font-weight: 900; font-size: 11px; color: #95a5a6; text-transform: uppercase; display: block; margin-bottom: 8px;">Contraseña Actual:</label>
                        <input type="password" name="pass_actual" required class="p-input" placeholder="••••••••">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="font-weight: 900; font-size: 11px; color: #95a5a6; text-transform: uppercase; display: block; margin-bottom: 8px;">Nueva Contraseña (mín. 8 carac.):</label>
                        <input type="password" id="nueva_pass" name="nueva_pass" required minlength="8" class="p-input" oninput="checkPasswords()">
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-weight: 900; font-size: 11px; color: #95a5a6; text-transform: uppercase; display: block; margin-bottom: 8px;">Confirmar Clave:</label>
                        <input type="password" id="nueva_pass_conf" name="nueva_pass_conf" required minlength="8" class="p-input" oninput="checkPasswords()">
                        <div id="pass-match-msg" style="font-size: 12px; font-weight: 800; margin-top: 10px; display: none; padding: 8px 15px; border-radius: 8px;"></div>
                    </div>

                    <div style="margin-bottom: 30px; display: flex; justify-content: center;">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    
                    <button type="submit" id="btn-pass-submit" class="p-btn-main" disabled>ACTUALIZAR CONTRASEÑA <i class="fas fa-key" style="margin-left: 10px;"></i></button>
                </form>
            </div>

        </main>
    </div>
</div>

<!-- ====== MODAL: NUEVA DIRECCIÓN ====== -->
<div id="modal-nueva-dir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
        <button onclick="document.getElementById('modal-nueva-dir').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #888;"><i class="fas fa-times"></i></button>
        <h3 style="margin-top: 0; color: var(--p-color); font-weight: 900;"><i class="fas fa-plus-circle"></i> Añadir Dirección</h3>
        
        <form action="procesar_perfil.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="accion" value="nueva_direccion">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                <div style="grid-column: span 2;">
                    <label style="font-size: 12px; color: #666; font-weight: 700;">Guardar como:</label>
                    <select id="tipo_dir_select" class="p-input" onchange="cambiarTipoDireccion(this.value, '<?php echo h($user['nombre']); ?>')" style="margin-bottom: 10px;">
                        <option value="casa">🏠 Casa</option>
                        <option value="trabajo">🏢 Trabajo</option>
                        <option value="otra">📍 Otra</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <input type="text" name="alias" id="alias_dir" placeholder="Nombre para identificar (escribe el nombre)" class="p-input" required>
                </div>
                <div style="grid-column: span 2;">
                    <input type="text" name="nombre_dir" id="nombre_dir" placeholder="Nombre completo de quien recibe" class="p-input" value="<?php echo h($user['nombre']); ?>" required>
                </div>
                <div style="grid-column: span 2;">
                    <input type="text" name="direccion_dir" placeholder="Calle, número, piso..." class="p-input" required>
                </div>
                <div>
                    <input type="text" name="ciudad_dir" placeholder="Ciudad" class="p-input" required>
                </div>
                <div>
                    <input type="text" name="cp_dir" placeholder="Código Postal" class="p-input" required maxlength="5" pattern="[0-9]{5}" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
                <div style="grid-column: span 2;">
                    <input type="hidden" name="tel_prefijo" id="tel_dir_prefijo">
                    <input type="tel" id="tel_dir" name="tel_dir" 
                           placeholder="600 11 22 33" 
                           class="p-input" 
                           required>
                    <div id="tel-dir-msg" style="font-size: 12px; font-weight: 800; margin-top: 8px; display: none; padding: 8px 15px; border-radius: 8px;"></div>
                    <small style="color: #7f8c8d; font-size: 11px; margin-top: 6px; display: block;">
                        <i class="fas fa-info-circle" style="color: #3498db;"></i> Haz clic en la bandera para seleccionar tu país y prefijo.
                    </small>
                </div>
            </div>
            
            <button type="submit" class="p-btn-main" style="width: 100%; margin-top: 20px;">Guardar Dirección</button>
        </form>
    </div>
</div>

<!-- MODAL EDITAR DIRECCIÓN -->
<div id="modal-editar-dir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
        <button onclick="document.getElementById('modal-editar-dir').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #888;"><i class="fas fa-times"></i></button>
        <h3 style="margin-top: 0; color: var(--p-color); font-weight: 900;"><i class="fas fa-edit"></i> Editar Dirección</h3>

        <form action="procesar_perfil.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="accion" value="editar_direccion">
            <input type="hidden" name="id_dir" id="edit_id_dir">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                <div style="grid-column: span 2;">
                    <input type="text" name="alias" id="edit_alias" placeholder="Nombre para guardarla" class="p-input" required>
                </div>
                <div style="grid-column: span 2;">
                    <input type="text" name="nombre_dir" id="edit_nombre" placeholder="Nombre completo de quien recibe" class="p-input" required>
                </div>
                <div style="grid-column: span 2;">
                    <input type="text" name="direccion_dir" id="edit_direccion" placeholder="Calle, número, piso..." class="p-input" required>
                </div>
                <div>
                    <input type="text" name="ciudad_dir" id="edit_ciudad" placeholder="Ciudad" class="p-input" required>
                </div>
                <div>
                    <input type="text" name="cp_dir" id="edit_cp" placeholder="Código Postal" class="p-input" required maxlength="5" pattern="[0-9]{5}">
                </div>
                <div style="grid-column: span 2;">
                    <input type="hidden" name="tel_prefijo" id="edit_tel_prefijo">
                    <input type="tel" id="edit_tel" name="tel_dir" placeholder="600 11 22 33" class="p-input" required>
                </div>
            </div>

            <button type="submit" class="p-btn-main" style="width: 100%; margin-top: 20px;">Actualizar Dirección</button>
        </form>
    </div>
</div>

<script>
/** LÓGICA: Banderas en el modal de nueva dirección */
const telDirInput = document.querySelector("#tel_dir");
const telDirPrefijo = document.querySelector("#tel_dir_prefijo");

const telDirIti = window.intlTelInput(telDirInput, {
    preferredCountries: ["es", "pt", "fr", "it"],
    separateDialCode: true,
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
});

// Validación en tiempo real
telDirInput.addEventListener('input', function() {
    const msg = document.getElementById('tel-dir-msg');
    const btnGuardar = this.closest('form').querySelector('button[type="submit"]');
    const soloDigitos = this.value.replace(/[^0-9]/g, '');

    if (soloDigitos.length === 0) {
        msg.style.display = 'none';
        this.style.borderColor = '#f1f1f1';
        btnGuardar.disabled = false;
        return;
    }
    if (soloDigitos.length < 6) {
        msg.style.display = 'block';
        msg.innerHTML = '<i class="fas fa-times-circle"></i> Demasiado corto, mínimo 6 dígitos';
        msg.style.color = '#e74c3c'; msg.style.background = '#fff5f5';
        this.style.borderColor = '#e74c3c';
        btnGuardar.disabled = true;
    } else {
        msg.style.display = 'block';
        msg.innerHTML = '<i class="fas fa-check-circle"></i> Teléfono válido';
        msg.style.color = '#27ae60'; msg.style.background = '#e8f5e8';
        this.style.borderColor = '#27ae60';
        btnGuardar.disabled = false;
    }
});

// Antes de enviar: guardamos el prefijo en el campo oculto
const formNuevaDir = document.querySelector('#modal-nueva-dir form');
if (formNuevaDir) {
    formNuevaDir.addEventListener('submit', function() {
        const countryData = telDirIti.getSelectedCountryData();
        telDirPrefijo.value = countryData.dialCode;
    });
}

// Resetear el modal al cerrarlo
document.querySelector('#modal-nueva-dir button[onclick]').addEventListener('click', function() {
    telDirInput.value = '';
    telDirIti.setCountry('es');
    document.getElementById('tel-dir-msg').style.display = 'none';
    telDirInput.style.borderColor = '#f1f1f1';

    // Resetear campos de dirección
    document.getElementById('alias_dir').value = '';
    document.getElementById('nombre_dir').value = '<?php echo h($user["nombre"]); ?>';
    document.getElementById('tipo_dir_select').value = 'casa';
    document.getElementById('alias_dir').value = 'Casa';
});

// Función para cambiar tipo de dirección
function cambiarTipoDireccion(tipo, nombreUsuario) {
    document.getElementById('nombre_dir').value = nombreUsuario;

    if (tipo === 'casa') {
        document.getElementById('alias_dir').value = 'Casa';
    } else if (tipo === 'trabajo') {
        document.getElementById('alias_dir').value = 'Trabajo';
    } else {
        document.getElementById('alias_dir').value = '';
        document.getElementById('alias_dir').placeholder = 'Escribe un nombre (ej: Casa de la abuela)';
    }
}

// Inicializar al cargar
cambiarTipoDireccion('casa', '<?php echo h($user["nombre"]); ?>');

// Función para abrir modal de editar dirección
function editarDireccion(id, alias, nombre, direccion, ciudad, cp, telefono) {
    document.getElementById('edit_id_dir').value = id;
    document.getElementById('edit_alias').value = alias;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_direccion').value = direccion;
    document.getElementById('edit_ciudad').value = ciudad;
    document.getElementById('edit_cp').value = cp;
    document.getElementById('edit_tel').value = telefono;

    document.getElementById('modal-editar-dir').style.display = 'flex';
}

function showTab(tabId, element) {
    document.querySelectorAll('.account-tab').forEach(t => t.style.display = 'none');
    const tab = document.getElementById(tabId);
    tab.style.display = 'block';
    tab.animate([ { opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'translateY(0)' } ], { duration: 400, fill: 'forwards' });
    document.querySelectorAll('.nav-btn-profile').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    window.history.replaceState({}, document.title, window.location.pathname);
}

/** LÓGICA: Validación de contraseñas en tiempo real */
function checkPasswords() {
    const pass1 = document.getElementById('nueva_pass').value;
    const pass2 = document.getElementById('nueva_pass_conf').value;
    const msg = document.getElementById('pass-match-msg');
    const btn = document.getElementById('btn-pass-submit');

    if (pass2.length > 0) {
        msg.style.display = 'block';
        if (pass1 === pass2 && pass1.length >= 8) {
            msg.innerHTML = '<i class="fas fa-check-circle"></i> Las contraseñas coinciden';
            msg.style.color = '#27ae60'; msg.style.background = '#e8f5e8';
            btn.disabled = false;
        } else {
            msg.innerHTML = '<i class="fas fa-times-circle"></i> No coinciden o son cortas';
            msg.style.color = '#e74c3c'; msg.style.background = '#fff5f5';
            btn.disabled = true;
        }
    } else { msg.style.display = 'none'; btn.disabled = true; }
}

window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash) {
        const targetBtn = document.querySelector(`[onclick*="${hash.substring(1)}"]`);
        if (targetBtn) targetBtn.click();
    }
});
</script>

<?php include 'includes/footer.php'; ?>