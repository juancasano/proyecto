<?php
/**
 * ARCHIVO: admin/ver_pedido.php
 * FUNCIÓN: Centro de producción y visualización de detalles.
 * ACTUALIZACIÓN: Registro de errores, rutas coherentes y blindaje SQL.
 */

// --- 1. CONFIGURACIÓN DE ERRORES (LA CAJA NEGRA) ---
// Registra fallos en un archivo secreto sin mostrarlos al público
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../admin_errors.log'); 

// --- 2. CARGA DE CONFIGURACIÓN ---
// Usamos ../ para subir de nivel desde la carpeta 'admin' a la raíz
require_once '../includes/config.php';

// --- 3. SEGURIDAD: CONTROL DE ACCESO ---
if (!esAdmin()) {
    header("Location: ../login.php");
    exit;
}

// --- 4. VALIDACIÓN DE PARÁMETROS ---
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pedido_id <= 0) { 
    die("ID de pedido no válido."); 
}

// --- 5. OBTENER INFORMACIÓN GENERAL ---
// Traemos los datos del pedido y los del usuario mediante un LEFT JOIN
try {
    $stmtPedido = $pdo->prepare("SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email 
                                 FROM pedidos p 
                                 LEFT JOIN usuarios u ON p.user_id = u.id 
                                 WHERE p.id = ?");
    $stmtPedido->execute([$pedido_id]);
    // --- AÑADIR ESTO --
    
    $pedido_info = $stmtPedido->fetch();

$nombre_cliente = $pedido_info['cliente_nombre'] ?? 'Usuario Eliminado (RGPD)';
    
    if (!$pedido_info) { 
        die("El pedido no existe en la base de datos."); 
    }
} catch (Exception $e) {
    error_log("Error al consultar pedido $pedido_id: " . $e->getMessage());
    die("Error interno al recuperar los datos del pedido.");
}

// --- 3. CONSULTAR PRODUCTOS (JOIN Resiliente) ---
// Traemos el detalle y unimos con productos para obtener la imagen/nombre actual del catálogo.
// LUPA: Si producto_id es '306' y en catálogo es 'camiseta-306', el JOIN devolverá NULL en campos 'p'.
$stmtItems = $pdo->prepare("
    SELECT pd.*, p.nombre as prod_nombre, p.imagen_url as prod_imagen, p.id as prod_id_valido
    FROM pedidos_detalle pd 
    LEFT JOIN productos p ON pd.producto_id = p.id 
    WHERE pd.pedido_id = ?
");
$stmtItems->execute([$pedido_id]);
$items = $stmtItems->fetchAll();

include '../includes/header.php'; 
?>

<style>
    /* Estilos de Interfaz Pro */
    .admin-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eee; overflow: hidden; margin-bottom: 30px; }
    .status-pill { padding: 6px 15px; border-radius: 50px; font-weight: 900; font-size: 12px; text-transform: uppercase; }
    .status-ok { background: #e8f5e8; color: #27ae60; border: 1px solid #27ae60; }
    .status-wait { background: #fff9db; color: #f39c12; border: 1px solid #f39c12; }
    .status-alert { background: #fff5f5; color: #e74c3c; border: 1px solid #e74c3c; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
    
    .btn-print { background: #2c3e50; color: white; padding: 12px 25px; border-radius: 12px; text-decoration: none; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; border: none; cursor: pointer; }
    .btn-print:hover { background: #000; transform: translateY(-2px); }

    /* --- ESTILOS DE IMPRESIÓN --- */
    @media print {
        header, footer, .no-print, .btn-print, .fas { display: none !important; }
        body { background: white !important; padding: 0; margin: 0; }
        .admin-card { box-shadow: none !important; border: 1px solid #000 !important; margin-bottom: 15px; }
        .admin-container { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        .status-pill { border: 1px solid #000 !important; color: #000 !important; background: none !important; }
    }
</style>

<div class="admin-container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        
<a href="/admin_pedidos.php" style="text-decoration: none; color: #7f8c8d; font-weight: 800; font-size: 14px;">
            <i class="fas fa-chevron-left"></i> VOLVER AL PANEL
        </a>        
        <button onclick="window.print();" class="btn-print">
            <i class="fas fa-print"></i> IMPRIMIR HOJA DE TRABAJO
        </button>
    </div>

    <div class="admin-card" style="border-top: 8px solid #2c3e50;">
        <div style="padding: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
            <div>
                <p style="margin: 0; font-size: 11px; color: #999; font-weight: 900; text-transform: uppercase;">ID Registro</p>
                <h2 style="margin: 5px 0; color: #2c3e50;">#<?php echo (int)$pedido_id; ?></h2>
                <span style="font-size: 13px; color: #666; font-weight: 600;"><?php echo date('d/m/Y - H:i', strtotime($pedido_info['fecha'])); ?></span>
            </div>
            
            <div>
                <p style="margin: 0; font-size: 11px; color: #999; font-weight: 900; text-transform: uppercase;">Cliente</p>
<h3 style="margin: 5px 0; font-size: 18px;"><?php echo h($nombre_cliente); ?></h3>
<a href="mailto:<?php echo $pedido_info['cliente_email']; ?>" style="color: #e74c3c; font-size: 13px; text-decoration: none; font-weight: 700;"><?php echo h($pedido_info['cliente_email'] ?? '-'); ?></a>
            </div>

            <div>
                <p style="margin: 0; font-size: 11px; color: #999; font-weight: 900; text-transform: uppercase;">Pago & Estado</p>
                <div style="margin-top: 10px;">
                    <?php 
                        $estado_low = strtolower($pedido_info['estado']);
                        $status_class = ($estado_low == 'enviado' || $estado_low == 'entregado') ? 'status-ok' : 'status-wait';
                    ?>
                    <form action="cambiar_estado.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="id" value="<?php echo $pedido_id; ?>">
    
    <select name="nuevo_estado" onchange="this.form.submit()" style="padding: 8px; border-radius: 8px; border: 1px solid #ccc; font-weight: bold; width: 100%; cursor: pointer;">
        <?php 
            $estados = ['Pendiente', 'En Taller', 'Enviado', 'Entregado', 'Cancelado'];
            foreach($estados as $est) {
                $sel = ($pedido_info['estado'] == $est) ? 'selected' : '';
                echo "<option value='$est' $sel>$est</option>";
            }
        ?>
    </select>
</form>
                    <p style="margin: 8px 0 0; font-size: 11px; color: #777;">Ref. Pago: <?php echo h($pedido_info['id_pago'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card" style="padding: 30px; background: #fafafa;">
        <h4 style="margin: 0 0 15px 0; font-weight: 900; text-transform: uppercase; font-size: 12px; color: #2c3e50;">
            <i class="fas fa-truck"></i> Destino del Envío
        </h4>
        <div style="font-size: 16px; line-height: 1.6; color: #444;">
            <?php 
                $dir = $pedido_info['direccion_completa'] ?? '';
                $dir_parts = explode('|', $dir);
                if(count($dir_parts) > 1) {
                    foreach($dir_parts as $part) {
                        echo "<div>" . h(trim($part)) . "</div>";
                    }
                } else {
                    echo nl2br(h($dir));
                }
            ?>
        </div>
    </div>

    <div class="admin-card">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #2c3e50; color: white; text-align: left;">
                    <th style="padding: 20px; font-size: 12px; text-transform: uppercase;">Item / Referencia</th>
                    <th style="padding: 20px; font-size: 12px; text-transform: uppercase;">Configuración</th>
                    <th style="padding: 20px; font-size: 12px; text-transform: uppercase; text-align: center;">Uds.</th>
                    <th style="padding: 20px; font-size: 12px; text-transform: uppercase; text-align: center;" class="no-print">Diseño</th>
                </tr>
            </thead>
<tbody>
                <?php foreach($items as $item): 
                    // --- LÓGICA DE RESOLUCIÓN DE DATOS (Anti-RedFlag) ---
                    $nombre_item = $item['nombre'] ?: ($item['prod_nombre'] ?: 'Producto #' . $item['producto_id']);
                    $talla_item  = $item['talla'] ?: 'N/A';
                    $color_item  = $item['color'] ?: 'N/A';
                    $es_huerfano = empty($item['prod_id_valido']); 
                    
                    // Resolución de imagen principal (miniatura)
if (!empty($item['imagen_custom'])) {
    // Comprobamos si la ruta ya empieza por http o /
    $img_path = (strpos($item['imagen_custom'], 'http') === 0 || strpos($item['imagen_custom'], '/') === 0) ? $item['imagen_custom'] : '../' . $item['imagen_custom'];
} elseif (!empty($item['prod_imagen'])) {
    $img_path = (strpos($item['prod_imagen'], 'http') === 0 || strpos($item['prod_imagen'], '/') === 0) ? $item['prod_imagen'] : '../' . $item['prod_imagen'];
} else {
    $img_path = '../images/placeholder.png';
}
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" style="width: 75px; height: 75px; object-fit: contain; background: #f9f9f9; border-radius: 12px; border: 1px solid #eee;">
                            <div>
                                <strong style="font-size: 15px; display: block; color: #2c3e50;"><?php echo h($nombre_item); ?></strong>
                                <div style="margin-top: 4px;">
                                    <small style="color: #999; font-weight: bold; font-family: monospace;">ID: <?php echo h($item['producto_id']); ?></small>
                                    <?php if($es_huerfano): ?>
                                        <span class="status-alert no-print">ID NO COINCIDE CON CATÁLOGO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td style="padding: 20px;">
                        <?php
                        $materiales = ['azulejo', 'aluminio', 'pizarra', 'cerámica blanca 11oz', 'cerámica blanca'];
                        $es_material = in_array(strtolower($talla_item), $materiales, true);
                        $label_talla = $es_material ? 'Material' : 'Talla';
                        ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span style="background: #000; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;"><?= $label_talla ?>: <?php echo h($talla_item); ?></span>
                            <?php if (!empty($color_item)): ?>
                                <span style="background: #f1f1f1; color: #333; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">Color: <?php echo h($color_item); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(!empty($item['extras_descripcion'])): ?>
                            <div style="margin-top: 10px; font-size: 11px; color: #e74c3c; font-weight: bold; background: #fff5f5; padding: 6px 10px; border-radius: 6px; border: 1px dashed #e74c3c; display: inline-block;">
                                <i class="fas fa-star"></i> <?php echo h($item['extras_descripcion']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    
                    <td style="padding: 20px; text-align: center; font-size: 18px; font-weight: 900; color: #2c3e50;">
                        x<?php echo (int)$item['cantidad']; ?>
                    </td>
                    
                    <td style="padding: 20px; text-align: center;" class="no-print">
    <?php if(!empty($item['imagen_custom']) || !empty($item['imagen_espalda']) || !empty($item['logos_extras'])): ?>
        <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
            
            <?php if(!empty($item['imagen_custom'])): 
                $url_front = (strpos($item['imagen_custom'], 'http') === 0 || strpos($item['imagen_custom'], '/') === 0) ? $item['imagen_custom'] : '../' . $item['imagen_custom'];
            ?>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 9px; font-weight: 900; color: #95a5a6; width: 50px; text-align: right;">FRONTAL</span>
                    <a href="<?php echo h($url_front); ?>" target="_blank" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #3498db; width: auto;" title="Ver"><i class="fas fa-eye"></i></a>
                    <a href="<?php echo h($url_front); ?>" download="Frontal_Pedido_<?php echo $pedido_id; ?>" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #27ae60; width: auto;" title="Descargar"><i class="fas fa-download"></i></a>
                </div>
            <?php endif; ?>

            <?php if(!empty($item['imagen_espalda'])): 
                $url_back = (strpos($item['imagen_espalda'], 'http') === 0 || strpos($item['imagen_espalda'], '/') === 0) ? $item['imagen_espalda'] : '../' . $item['imagen_espalda'];
            ?>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 9px; font-weight: 900; color: #95a5a6; width: 50px; text-align: right;">ESPALDA</span>
                    <a href="<?php echo h($url_back); ?>" target="_blank" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #9b59b6; width: auto;" title="Ver"><i class="fas fa-eye"></i></a>
                    <a href="<?php echo h($url_back); ?>" download="Espalda_Pedido_<?php echo $pedido_id; ?>" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #27ae60; width: auto;" title="Descargar"><i class="fas fa-download"></i></a>
                </div>
            <?php endif; ?>

            <?php 
            if(!empty($item['logos_extras'])): 
                $logos = json_decode($item['logos_extras'], true);
                if(is_array($logos)):
                    foreach($logos as $zona => $ruta): 
                        $zona_corta = substr(strtoupper($zona), 0, 4);
                        $url_logo = (strpos($ruta, 'http') === 0 || strpos($ruta, '/') === 0) ? $ruta : '../' . $ruta;
            ?>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 9px; font-weight: 900; color: #95a5a6; width: 50px; text-align: right; overflow:hidden;" title="<?php echo h($zona); ?>"><?php echo $zona_corta; ?></span>
                    <a href="<?php echo h($url_logo); ?>" target="_blank" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #e67e22; width: auto;" title="Ver"><i class="fas fa-eye"></i></a>
                    <a href="<?php echo h($url_logo); ?>" download="Extra_<?php echo h($zona); ?>_Pedido_<?php echo $pedido_id; ?>" class="btn-print" style="padding: 6px 10px; font-size: 10px; background: #27ae60; width: auto;" title="Descargar"><i class="fas fa-download"></i></a>
                </div>
            <?php 
                    endforeach;
                endif;
            endif; 
            ?>

        </div>
    <?php else: ?>
        <span style="color: #ccc; font-size: 11px; font-style: italic;">Sin diseño custom</span>
    <?php endif; ?>
</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
    </div>

    <div style="text-align: right; padding: 20px 0 60px 0;">
        <p style="margin: 0; color: #999; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Importe Total Percibido</p>
        <h2 style="margin: 5px 0; font-size: 40px; font-weight: 900; color: #000;">
            <?php 
                $total = (float)$pedido_info['total'];
                if(function_exists('formatPrecio')) {
                    echo formatPrecio($total);
                } else {
                    echo number_format($total, 2, ',', '.') . ' €';
                }
            ?>
        </h2>
    </div>

</div>

<?php include '../includes/footer.php'; ?>