<?php

require_once 'includes/config.php';



// 1. SEGURIDAD: Solo admin

if (!esAdmin()) { header("Location: login.php"); exit; }



$id = (int)($_GET['id'] ?? 0);



// 2. BUSCAR DATOS DEL PEDIDO (CABECERA)

$stmt = $pdo->prepare("SELECT p.*, u.nombre, u.email, u.telefono FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");

$stmt->execute([$id]);

$pedido = $stmt->fetch();

if (!$pedido) { die("Pedido no encontrado."); }

// --- AÑADIR ESTO ---
$nombre_cliente = $pedido['nombre'] ?? 'Usuario Eliminado (RGPD)';
$email_cliente = $pedido['email'] ?? 'Sin correo';
$telefono_cliente = $pedido['telefono'] ?? 'Sin teléfono';

// 3. BUSCAR PRODUCTOS DEL PEDIDO (DETALLE) con imagen y categoría del producto base

$stmtD = $pdo->prepare("
    SELECT pd.*, p.imagen_url as producto_imagen, p.categoria, p.descripcion as material,
           pd.imagen_custom, pd.imagen_espalda, pd.logos_extras, pd.extras_descripcion, pd.notas
    FROM pedidos_detalle pd
    LEFT JOIN productos p ON pd.producto_id = p.id
    WHERE pd.pedido_id = ?
");

$stmtD->execute([$id]);

$productos = $stmtD->fetchAll();



// Calcular subtotal y envío

$subtotal = 0;

foreach ($productos as $p) {

    $subtotal += $p['precio_unitario'] * $p['cantidad'];

}

$envio = $pedido['total'] - $subtotal;

if ($envio < 0) $envio = 0;



// 4. OBTENER IDs PARA NAVEGACIÓN CIRCULAR

// 4. NAVEGACIÓN CIRCULAR INTELIGENTE (Busca IDs que EXISTAN de verdad)
$stmtPrev = $pdo->prepare("SELECT id FROM pedidos WHERE id < ? ORDER BY id DESC LIMIT 1");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();
if (!$prevId) { $prevId = $pdo->query("SELECT MAX(id) FROM pedidos")->fetchColumn(); }

$stmtNext = $pdo->prepare("SELECT id FROM pedidos WHERE id > ? ORDER BY id ASC LIMIT 1");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();
if (!$nextId) { $nextId = $pdo->query("SELECT MIN(id) FROM pedidos")->fetchColumn(); }

// 5. LIMPIAR DIRECCIÓN DE ENTREGA

$direccion_limpia = $pedido['direccion_completa'];

$partes = explode('|', $pedido['direccion_completa']);

if (count($partes) >= 3) {

    $direccion_limpia = trim($partes[1] . ', ' . $partes[2]);

} else {

    $direccion_limpia = $pedido['direccion_completa'];

}

include 'includes/colors.php';
include 'includes/header.php';

?>



<!-- Estilos inline para compactar y ajustar a pantalla completa -->

<style>

    html, body {

        margin: 0;

    }

    .fullscreen-container {

        padding: 10px 20px 20px 20px;

        box-sizing: border-box;

    }

    .content-card {

        background: white;

        border-radius: 20px;

        box-shadow: 0 10px 30px rgba(0,0,0,0.05);

        padding: 15px 20px;

    }

    .scrollable-table {

        margin-top: 10px;

        border: 1px solid #eee;

        border-radius: 12px;

        overflow: hidden;

    }

    table {

        width: 100%;

        border-collapse: collapse;

        font-size: 0.9rem;

    }

    th {

        background: #f8f8f8;

        padding: 10px 8px;

        position: sticky;

        top: 0;

        z-index: 10;

    }

    td {

        padding: 10px 8px;

        border-top: 1px solid #eee;

    }

    .product-img {

        max-width: 50px;

        max-height: 50px;

        border-radius: 6px;

        border: 1px solid #ddd;

        object-fit: cover;

    }

    .nav-button {

        background: #f0f0f0;

        padding: 6px 12px;

        border-radius: 30px;

        color: #333;

        text-decoration: none;

        font-size: 0.9rem;

    }

    .nav-button:hover {

        background: #e0e0e0;

    }

    .estado-badge {

        font-size: 1.5rem;

        font-weight: 900;

        color: #27ae60;

    }

    .cliente-info p {

        margin: 5px 0;

        font-size: 0.9rem;

    }

    .resumen-box {

        background: #fafafa;

        padding: 12px 15px;

        border-radius: 15px;

        font-size: 0.9rem;

    }

    hr {

        margin: 12px 0;

        border: 0;

        border-top: 1px solid #f0f0f0;

    }

    h2 {

        font-size: 1.6rem;

        margin: 0;

    }

    h4 {

        font-size: 0.75rem;

        letter-spacing: 1px;

        margin: 0 0 8px 0;

        color: #bbb;

        text-transform: uppercase;

    }

    .color-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f1f1;
        padding: 3px 10px 3px 5px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #333;
        vertical-align: middle;
    }

    .color-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 1px solid rgba(0,0,0,0.15);
        flex-shrink: 0;
        display: inline-block;
    }

    .color-dot.blanco {
        border-color: #ccc;
    }

</style>



<div class="fullscreen-container">

    <!-- Navegación SUPERIOR (solo aquí) -->

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">

        <a href="admin_pedidos.php" style="text-decoration:none; color:#e74c3c; font-weight:bold; font-size:0.95rem;">

            ← Volver al listado

        </a>

        <div>

            <a href="?id=<?= $prevId ?>" class="nav-button"><i class="fas fa-arrow-left"></i> Anterior</a>

            <a href="?id=<?= $nextId ?>" class="nav-button" style="margin-left:5px;">Siguiente <i class="fas fa-arrow-right"></i></a>

        </div>

    </div>



    <!-- TARJETA PRINCIPAL -->

    <div class="content-card">

        <!-- Cabecera del pedido: número y estado en fila -->

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">

            <div>

                <h2>Pedido #<?= h($pedido['id_pago']) ?></h2>

                <p style="color:#888; margin:2px 0 0; font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>

            </div>

            <div style="text-align:right;">

                <span class="estado-badge"><?= strtoupper($pedido['estado']) ?></span>

                <p style="margin:2px 0 0; font-size:0.9rem;"><strong>Total:</strong> <?= formatPrecio($pedido['total']) ?></p>

            </div>

        </div>



        <hr>



        <!-- Dos columnas: Cliente y Resumen -->

        <div style="display:grid; grid-template-columns: 1fr 0.9fr; gap:20px;">

            <!-- Datos cliente -->

            <div class="cliente-info">
                <h4>Datos del Cliente</h4>
                <p><strong>Nombre:</strong> <?= h($nombre_cliente) ?></p>
                <p><strong>Email:</strong>
                    <?php if ($email_cliente !== 'Sin correo'): ?>
                        <a href="mailto:<?= h($email_cliente) ?>" style="color:#e74c3c;"><?= h($email_cliente) ?></a>
                    <?php else: ?> - <?php endif; ?>
                </p>
                <p><strong>Teléfono:</strong>
                    <?php if ($telefono_cliente !== 'Sin teléfono'): 
                        $tel_clean = preg_replace('/[^0-9+]/', '', $telefono_cliente); ?>
                        <a href="tel:<?= h($tel_clean) ?>" style="color:#e74c3c; margin-right:5px;"><?= h($telefono_cliente) ?></a>
                        <a href="https://wa.me/<?= h(ltrim($tel_clean, '+')) ?>" target="_blank" style="color:#25D366;"><i class="fab fa-whatsapp"></i></a>
                    <?php else: ?> - <?php endif; ?>
                </p>

                <p><strong>Dirección:</strong><br>

                    <a href="https://maps.google.com/?q=<?= urlencode($direccion_limpia) ?>" target="_blank" style="color:#e74c3c; font-weight:500;">

                        <i class="fas fa-map-marker-alt" style="margin-right:3px;"></i><?= nl2br(h($direccion_limpia)) ?>

                    </a>

                </p>

            </div>



            <!-- Resumen económico -->

            <div class="resumen-box">

                <h4 style="margin-top:0;">Resumen</h4>

                <p style="display:flex; justify-content:space-between;"><span>Subtotal:</span> <strong><?= formatPrecio($subtotal) ?></strong></p>

                <p style="display:flex; justify-content:space-between;"><span>Envío:</span> <strong>+ <?= formatPrecio($envio) ?></strong></p>

                <p style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:bold; border-top:1px dashed #ccc; padding-top:8px; margin-top:8px;">

                    <span>Total:</span> <span><?= formatPrecio($pedido['total']) ?></span>

                </p>

                <?php if (strpos($pedido['id_pago'], 'BIZUM') !== false): ?>

                    <p style="margin:5px 0 0;"><strong>Método:</strong> Bizum</p>

                <?php endif; ?>

            </div>

        </div>



        <h4 style="margin:15px 0 5px; display: flex; justify-content: space-between; align-items: center;">

            <span>Productos y Diseños</span>

            <button onclick="window.print();" class="nav-button" style="background: #000; color: #fff; cursor: pointer; border: none;">

                <i class="fas fa-print"></i> IMPRIMIR ORDEN

            </button>

        </h4>

        <div class="scrollable-table">

            <table>

                <thead>

                    <tr>

                        <th>Producto Base</th>

                        <th>Especificaciones Técnicas y Diseños</th>

                        <th style="text-align:center;">Cant.</th>
                        <th style="text-align:right;">Precio</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach($productos as $p): 

                    $fixPath = function($path) {
                        if(empty($path)) return null;
                        return (strpos($path, 'http') === 0 || strpos($path, '/') === 0) ? $path : $path;
                    };

                    $es_personalizado = !empty($p['imagen_custom']);
                    $color_nombre = $p['color'] ?? 'Estándar';
                    $color_hex_val = $colores_hex[$color_nombre] ?? null;

                ?>

                    <tr>

                        <td style="vertical-align: top;">

                            <?php if (!empty($p['producto_imagen'])):
                                $img_base = (strpos($p['producto_imagen'], 'http') === 0)
                                    ? $p['producto_imagen']
                                    : '/' . ltrim($p['producto_imagen'], '/');
                            ?>
                                <div style="text-align:center; border:1px solid #eee; padding:5px; border-radius:8px; background:#f9f9f9; display:inline-block; margin-bottom:6px;">
                                    <a href="<?= $img_base ?>" target="_blank">
                                        <img src="<?= $img_base ?>"
                                             style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid #ddd; display:block;">
                                    </a>
                                    <a href="<?= $img_base ?>" download style="display:block; font-size:9px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:4px;">DESCARGAR</a>
                                </div>
                            <?php endif; ?>

                            <strong style="font-size: 1.1rem;"><?= h($p['nombre']) ?></strong>

                        </td>

                        <td>
    <div style="line-height: 1.8;">
        <strong>Talla:</strong> <span style="color:#e74c3c; font-weight:bold;"><?= h($p['talla'] ?? 'Única') ?></span><br>
        <strong>Color:</strong>
        <?php if ($color_hex_val): ?>
            <span class="color-pill">
                <span class="color-dot <?= ($color_nombre === 'Blanco' || $color_nombre === 'Natural') ? 'blanco' : '' ?>"
                      style="background-color: <?= $color_hex_val ?>;"></span>
                <?= h($color_nombre) ?>
            </span>
        <?php else: ?>
            <?= h($color_nombre) ?>
        <?php endif; ?>
        <br>

        <?php if(!empty($p['extras_descripcion'])): ?>
            <div style="margin-top: 8px; font-size: 0.75rem; color: #e74c3c; font-weight: bold; background: #fff5f5; padding: 4px; border: 1px dashed #e74c3c; border-radius: 4px;">
                <i class="fas fa-plus-circle"></i> <?= h($p['extras_descripcion']) ?>
            </div>
        <?php endif; ?>

        <!-- IMÁGENES DEBAJO DE LOS EXTRAS -->
        <?php
        $tieneImagenes = !empty($p['imagen_custom']) || !empty($p['imagen_espalda']) || !empty($p['logos_extras']);
        if ($tieneImagenes): ?>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;">

                <?php if($urlF = $fixPath($p['imagen_custom'])): ?>
                    <div style="text-align:center; border:1px solid #eee; padding:5px; border-radius:8px; background:#f9f9f9;">
                        <small style="display:block; font-weight:900; font-size:10px; color:#999;">FRONTAL</small>
                        <a href="<?= $urlF ?>" target="_blank"><img src="<?= $urlF ?>" style="max-width:80px; border:1px solid #000;"></a>
                        <a href="<?= $urlF ?>" download style="display:block; font-size:9px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:4px;">DESCARGAR</a>
                    </div>
                <?php endif; ?>

                <?php if($urlB = $fixPath($p['imagen_espalda'])): ?>
                    <div style="text-align:center; border:1px solid #eee; padding:5px; border-radius:8px; background:#f9f9f9;">
                        <small style="display:block; font-weight:900; font-size:10px; color:#999;">ESPALDA</small>
                        <a href="<?= $urlB ?>" target="_blank"><img src="<?= $urlB ?>" style="max-width:80px; border:1px solid #000;"></a>
                        <a href="<?= $urlB ?>" download style="display:block; font-size:9px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:4px;">DESCARGAR</a>
                    </div>
                <?php endif; ?>

                <?php 
                if(!empty($p['logos_extras'])):
                    $logos = json_decode($p['logos_extras'], true);
                    if(is_array($logos)):
                        foreach($logos as $zona => $ruta):
                            $urlL = $fixPath($ruta);
                ?>
                            <div style="text-align:center; border:1px solid #eee; padding:5px; border-radius:8px; background:#f9f9f9;">
                                <small style="display:block; font-weight:900; font-size:10px; color:#999;"><?= strtoupper($zona) ?></small>
                                <a href="<?= $urlL ?>" target="_blank"><img src="<?= $urlL ?>" style="max-width:80px; border:1px solid #000;"></a>
                                <a href="<?= $urlL ?>" download style="display:block; font-size:9px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:4px;">DESCARGAR</a>
                            </div>
                <?php 
                        endforeach;
                    endif;
                endif; 
                ?>

            </div>
        <?php endif; ?>

        <?php if(!empty($p['notas'])): ?>
            <div style="margin-top: 8px; font-size: 0.8rem; color: #2c3e50; background: #fef9e7; padding: 8px; border-left: 3px solid #f1c40f; border-radius: 4px; line-height: 1.2;">
                <strong style="font-size: 0.65rem; color: #996515; text-transform: uppercase;">📝 Nota del cliente:</strong><br>
                <?= nl2br(h($p['notas'])) ?>
            </div>
        <?php endif; ?>



    </div>
</td>

                        <td style="text-align:center; font-size: 1.2rem; font-weight: bold;">x<?= $p['cantidad'] ?></td>
                        <td style="text-align:right; font-weight:bold; color:#27ae60; font-size:1rem; white-space:nowrap;">
                            <?= formatPrecio($p['precio_unitario'] ?? 0) ?>/ud
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
