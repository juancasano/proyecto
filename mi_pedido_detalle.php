<?php
require_once 'includes/config.php';
include 'includes/colors.php';
include 'includes/header.php';

// 1. SEGURIDAD: Solo usuarios logueados pueden ver esto
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode("perfil.php"));
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// 2. OBTENER DATOS DEL PEDIDO Y VALIDAR PROPIEDAD
// La consulta AHORA comprueba que el pedido 'id' coincida con el 'user_id' de la sesión
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$pedido = $stmt->fetch();

// Si no se encuentra el pedido O no pertenece al usuario, se le redirige a su lista de pedidos.
if (!$pedido) {
    header("Location: perfil.php?error=not_found");
    exit;
}

// 3. BUSCAR PRODUCTOS DEL PEDIDO (DETALLE)
$stmtD = $pdo->prepare("
    SELECT pd.*, p.imagen_url as producto_imagen
    FROM pedidos_detalle pd
    LEFT JOIN productos p ON pd.producto_id = p.id
    WHERE pd.pedido_id = ?
");
$stmtD->execute([$id]);
$productos = $stmtD->fetchAll();

// Calcular subtotal y envío para el resumen
$subtotal = 0;
foreach ($productos as $p) {
    $subtotal += $p['precio_unitario'] * $p['cantidad'];
}
$envio = $pedido['total'] - $subtotal;
if ($envio < 0) $envio = 0; // Por si acaso

// Limpiar dirección para mostrarla de forma legible
$direccion_limpia = str_replace(' | ', ', ', $pedido['direccion_completa']);
?>

<style>
    /* Estilos generales para la página de detalle */
    .detail-container { max-width: 900px; margin: 40px auto; padding: 0 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .content-card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); padding: 30px; }
    hr { margin: 20px 0; border: 0; border-top: 1px solid #f0f0f0; }
    h2 { font-size: 1.8rem; margin: 0 0 5px 0; color: #2c3e50; font-weight: 800; }
    h4 { font-size: 0.8rem; letter-spacing: 1px; margin: 0 0 10px 0; color: #95a5a6; text-transform: uppercase; font-weight: 700; }
    
    /* Estilos para la cabecera */
    .pedido-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .estado-badge { font-size: 1rem; font-weight: 700; padding: 8px 18px; border-radius: 30px; text-transform: uppercase; }
    .estado-pagado { background: #e8f5e9; color: #27ae60; }
    .estado-entregado { background: #e7f5ff; color: #1c7ed6; }
    .estado-cancelado { background: #fff5f5; color: #e03131; }
    .estado-revision-fraudulenta, .estado-pendiente-pago { background: #fff9db; color: #f08c00; }

    /* Estilos para el desglose de productos */
    .product-breakdown { display: flex; flex-direction: column; gap: 20px; }
    .product-row { display: flex; gap: 20px; align-items: flex-start; }
    .design-image { max-width: 100px; border: 1px solid #000; border-radius: 8px; background: #fff; padding: 3px; transition: 0.3s; }
    .design-image:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .color-pill { display: inline-flex; align-items: center; gap: 6px; background: #f1f1f1; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; color: #333; }
    .color-dot { width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.15); }

    /* Botón cancelar */
    .btn-cancelar {
        display: inline-block; background: #e74c3c; color: white; text-decoration: none; padding: 10px 20px; 
        border-radius: 50px; font-size: 12px; font-weight: 800; text-align: center; 
        margin-top: 10px; transition: 0.3s; border: none; cursor: pointer;
    }
    .btn-cancelar:hover { background: #c0392b; transform: scale(1.02); }

    /* Botón de volver */
    .back-link { text-decoration: none; color: #e74c3c; font-weight: 700; font-size: 0.9rem; display: inline-block; margin-bottom: 25px; }
</style>

<div class="detail-container">
    <a href="perfil.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver a Mi Cuenta
    </a>

    <div class="content-card">
        <div class="pedido-header">
            <div>
                <h2>Detalles del Pedido</h2>
                <p style="color:#888; margin:0; font-size:0.9rem;">Realizado el <?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>
            </div>
            <div style="text-align:right;">
                <p style="margin:2px 0 0; font-size:1rem;"><strong>ID:</strong> #<?= h($pedido['id_pago']) ?></p>
            </div>
        </div>

        <hr>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div>
                <h4>Estado del Pedido</h4>
                <?php
                    $est = strtolower(trim($pedido['estado']));
                    $badge_class = 'estado-pagado';
                    if ($est == 'entregado') $badge_class = 'estado-entregado';
                    elseif ($est == 'cancelado') $badge_class = 'estado-cancelado';
                    elseif (strpos($est, 'pendiente') !== false) $badge_class = 'estado-pendiente-pago';
                ?>
                <p class="estado-badge <?= $badge_class ?>"><?= h($pedido['estado']) ?></p>
                <?php if (strpos($est, 'pendiente') !== false): ?>
                    <form method="POST" action="cancelar_pedido.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres cancelar este pedido?\n\nEsta acción no se puede deshacer.')">
                        <input type="hidden" name="id" value="<?php echo $pedido['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn-cancelar">
                            <i class="fas fa-times"></i> CANCELAR PEDIDO
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($est == 'enviado' && !empty($pedido['tracking_url']) && preg_match('#^https?://#i', $pedido['tracking_url'])): ?>
                    <a href="<?= h($pedido['tracking_url']) ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; margin-top: 10px; background: #000; color: white; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 12px; transition: 0.3s;">
                        <i class="fas fa-map-marker-alt"></i> RASTREAR PAQUETE
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <h4>Dirección de Envío</h4>
                <p style="font-size: 0.9rem; line-height: 1.6; color: #333;"><?= nl2br(h($pedido['direccion_completa'])) ?></p>
            </div>
            <div style="background: #fafafa; padding: 20px; border-radius: 15px;">
                <h4 style="margin-top:0;">Resumen del Pago</h4>
                <p style="display:flex; justify-content:space-between; font-size: 0.9rem;"><span>Subtotal:</span> <strong><?= formatPrecio($subtotal) ?></strong></p>
                <p style="display:flex; justify-content:space-between; font-size: 0.9rem;"><span>Envío:</span> <strong>+ <?= formatPrecio($envio) ?></strong></p>
                <p style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:bold; border-top:1px dashed #ccc; padding-top:10px; margin-top:10px;">
                    <span>Total:</span> <span><?= formatPrecio($pedido['total']) ?></span>
                </p>
            </div>
        </div>

        <hr>

        <h4>Productos en este Pedido</h4>
        <div class="product-breakdown">
            <?php 
            $fixPath = function($path) {
                if (empty($path)) return null;
                return (strpos($path, 'http') === 0 || strpos($path, '/') === 0) ? $path : '/' . ltrim($path, '/');
            };
            foreach($productos as $p): 
                $img_producto = '';
                if (!empty($p['imagen_custom'])) {
                    $img_producto = $fixPath($p['imagen_custom']);
                } elseif (!empty($p['producto_imagen'])) {
                    $img_producto = $fixPath($p['producto_imagen']);
                }
            ?>
                <div style="border: 1px solid #f0f0f0; border-radius: 15px; padding: 20px; background: white;">
                    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                        <!-- Imagen del producto base -->
                        <?php if (!empty($img_producto)): ?>
                            <div style="flex-shrink: 0;">
                                <?php if (!empty($p['producto_id'])): ?>
                                    <a href="producto.php?id=<?= urlencode($p['producto_id']) ?>">
                                        <img src="<?= h($img_producto) ?>" style="width: 100px; height: 100px; object-fit: contain; background: #f9f9f9; border-radius: 12px; border: 1px solid #eee;">
                                    </a>
                                <?php else: ?>
                                    <img src="<?= h($img_producto) ?>" style="width: 100px; height: 100px; object-fit: contain; background: #f9f9f9; border-radius: 12px; border: 1px solid #eee;">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div style="flex: 1; min-width: 200px;">
                            <strong style="font-size: 1.1rem; color: #2c3e50;"><?= h($p['nombre']) ?></strong>
                            <p style="margin: 8px 0; font-size: 0.9rem; color: #666;">
                                <strong>Talla:</strong> <?= h($p['talla'] ?? 'Única') ?> &nbsp;·&nbsp;
                                <strong>Color:</strong>
                                <?php 
                                    $color_nombre = $p['color'] ?? 'Estándar';
                                    $color_hex_val = $colores_hex[$color_nombre] ?? null;
                                ?>
                                <?php if ($color_hex_val): ?>
                                    <span class="color-pill">
                                        <span class="color-dot" style="background-color: <?= $color_hex_val ?>;"></span>
                                        <?= h($color_nombre) ?>
                                    </span>
                                <?php else: ?>
                                    <?= h($color_nombre) ?>
                                <?php endif; ?>
                                &nbsp;·&nbsp; <strong>x<?= (int)$p['cantidad'] ?></strong>
                            </p>
                            <?php if(!empty($p['extras_descripcion'])): ?>
                                <p style="font-size: 0.85rem; color: #e74c3c; font-weight: bold; margin: 8px 0;">
                                    <i class="fas fa-star"></i> Extras: <?= h($p['extras_descripcion']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if(!empty($p['notas'])): ?>
                                <p style="font-size: 0.85rem; color: #856404; background: #fff9db; padding: 8px; border-radius: 8px; border-left: 3px solid #f1c40f; margin: 8px 0;">
                                    <strong>Nota:</strong> <?= nl2br(h($p['notas'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div style="text-align: right; flex-shrink: 0;">
                            <p style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #27ae60;"><?= formatPrecio($p['precio_unitario'] * $p['cantidad']) ?></p>
                            <p style="margin: 5px 0 0; font-size: 0.85rem; color: #7f8c8d;"><?= formatPrecio($p['precio_unitario']) ?>/ud</p>
                        </div>
                    </div>

                    <!-- Sección de Diseños Personalizados -->
                    <?php if(!empty($p['imagen_custom']) || !empty($p['imagen_espalda']) || !empty($p['logos_extras'])): ?>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 12px; margin-top: 15px; border: 1px solid #eee;">
                        <h4 style="margin-top: 0; font-size: 0.75rem; color: #2c3e50;"><i class="fas fa-paint-brush"></i> Tu Diseño Personalizado</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-start;">
                            <?php if(!empty($p['imagen_custom'])): 
                                $urlF = $fixPath($p['imagen_custom']);
                            ?>
                                <div style="text-align: center; background: white; padding: 8px; border-radius: 10px; border: 1px solid #eee;">
                                    <small style="display: block; font-weight: 900; font-size: 10px; color: #999; margin-bottom: 5px;">FRONTAL</small>
                                    <a href="<?= h($urlF) ?>" target="_blank" title="Ver diseño frontal">
                                        <img src="<?= h($urlF) ?>" class="design-image" style="max-width: 120px;">
                                    </a>
                                    <a href="<?= h($urlF) ?>" download style="display: block; margin-top: 5px; font-size: 10px; color: #e74c3c; text-decoration: none; font-weight: bold;">
                                        <i class="fas fa-download"></i> DESCARGAR
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($p['imagen_espalda'])): 
                                $urlB = $fixPath($p['imagen_espalda']);
                            ?>
                                <div style="text-align: center; background: white; padding: 8px; border-radius: 10px; border: 1px solid #eee;">
                                    <small style="display: block; font-weight: 900; font-size: 10px; color: #999; margin-bottom: 5px;">ESPALDA</small>
                                    <a href="<?= h($urlB) ?>" target="_blank" title="Ver diseño espalda">
                                        <img src="<?= h($urlB) ?>" class="design-image" style="max-width: 120px;">
                                    </a>
                                    <a href="<?= h($urlB) ?>" download style="display: block; margin-top: 5px; font-size: 10px; color: #e74c3c; text-decoration: none; font-weight: bold;">
                                        <i class="fas fa-download"></i> DESCARGAR
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php 
                            if(!empty($p['logos_extras'])):
                                $logos = json_decode($p['logos_extras'], true);
                                if(is_array($logos)):
                                    foreach($logos as $zona => $ruta): 
                                        $urlL = $fixPath($ruta);
                            ?>
                                        <div style="text-align: center; background: white; padding: 8px; border-radius: 10px; border: 1px solid #eee;">
                                            <small style="display: block; font-weight: 900; font-size: 10px; color: #999; margin-bottom: 5px;"><?= h(strtoupper($zona)) ?></small>
                                            <a href="<?= h($urlL) ?>" target="_blank" title="Ver diseño <?= h($zona) ?>">
                                                <img src="<?= h($urlL) ?>" class="design-image" style="max-width: 80px;">
                                            </a>
                                            <a href="<?= h($urlL) ?>" download style="display: block; margin-top: 5px; font-size: 10px; color: #e74c3c; text-decoration: none; font-weight: bold;">
                                                <i class="fas fa-download"></i> DESCARGAR
                                            </a>
                                        </div>
                            <?php 
                                    endforeach;
                                endif;
                            endif; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
