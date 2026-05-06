<?php 
/**
 * ARCHIVO: carrito.php
 * FUNCIÓN: Gestión completa del carrito con diseño limpio, eliminación fluida y efecto zoom.
 * ACTUALIZACIÓN: Protección CSRF por método POST (Máxima Seguridad).
 */

require_once 'includes/config.php';
include 'includes/pricing.php';
include 'includes/colors.php';
include 'includes/header.php';
?>

<style>
    /* 1. LAYOUT PRINCIPAL */
    .cart-wrapper { display: flex; gap: 30px; align-items: start; }
    .cart-items-panel { flex: 1; }
    .cart-summary-panel { width: 380px; position: sticky; top: 100px; }
    
    /* 2. TABLA DE PRODUCTOS */
    .cart-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #eee; overflow: hidden; }
    .cart-table { width: 100%; border-collapse: collapse; }
    .cart-table th { background: #fcfcfc; padding: 20px; text-align: left; color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #f1f1f1; }
    
    .cart-item-row { border-bottom: 1px solid #f9f9f9; transition: 0.4s ease; }
    .cart-item-row:hover { background: #fffcfc; }
    
    /* 3. CONTROLES DE CANTIDAD */
    .qty-control { 
        display: flex; align-items: center; justify-content: center; gap: 10px; 
        background: #f8f9fa; padding: 5px 12px; border-radius: 50px; border: 1px solid #eee; 
        width: fit-content; margin: 0 auto; 
    }
    .qty-btn { 
        width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; 
        text-decoration: none; color: #333; font-weight: 900; transition: 0.2s; 
        border-radius: 50%; border: none; cursor: pointer; background: transparent; 
    }
    .qty-btn:hover { background: #e74c3c; color: white; }
    
    .badge-custom { 
        background: #fff0f0; color: #e74c3c; padding: 4px 10px; border-radius: 6px; 
        font-size: 10px; font-weight: 800; text-transform: uppercase; 
        display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; 
    }
    .badge-custom a { color: #e74c3c; text-decoration: none; }
    .badge-custom a:hover { text-decoration: underline; }

    /* 4. BOTONES Y ACCIONES */
    .btn-buy { 
        display: block; text-align: center; background: #2c3e50; color: white !important; 
        padding: 22px; border-radius: 15px; text-decoration: none; font-weight: 900; 
        transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: none; cursor: pointer;
        font-size: 16px; text-transform: uppercase; letter-spacing: 1px;
    }
    .btn-buy:hover { background: #000; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }

    /* 5. BLOQUE DE CONFIANZA */
    .payment-trust-box { margin-top: 30px; padding: 25px; background: #fdfdfd; border-radius: 20px; border: 1px solid #f0f0f0; text-align: center; }
    .payment-icons-row { display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 20px; }
    .pay-icon { background: #fff; border: 1px solid #eee; padding: 8px 12px; border-radius: 10px; display: flex; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .pay-icon i { font-size: 20px; }
    .fa-cc-paypal { color: #003087; } 
    .fa-cc-visa { color: #1a1f71; } 
    .fa-cc-mastercard { color: #eb001b; }
    .bizum-text { color: #00aeb1; font-weight: 900; font-size: 11px; text-transform: uppercase; }

    .trust-badges { display: flex; flex-direction: column; gap: 12px; padding-top: 15px; border-top: 1px solid #f0f0f0; }
    .trust-item { display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 12px; font-weight: 700; color: #2c3e50; }
    .trust-item i { color: #27ae60; font-size: 15px; }
    .trust-subtext { display: block; font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

    /* 6. NOTIFICACIÓN TOAST */
    #cart-toast {
        position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%) translateY(150px);
        background: #1a1a1a; color: white; padding: 16px 35px; border-radius: 50px;
        font-weight: 800; font-size: 14px; z-index: 10000; box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        transition: 0.6s cubic-bezier(0.23, 1, 0.32, 1); display: flex; align-items: center; gap: 12px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    #cart-toast.show { transform: translateX(-50%) translateY(0); }

    /* 7. RESPONSIVE */
    @media (max-width: 992px) {
        .cart-wrapper { flex-direction: column; }
        .cart-summary-panel { width: 100%; position: static; }
    }

    /* --- THUMB DE IMAGEN EN CARRITO --- */
    .cart-img-thumb-wrap {
        width: 80px; height: 100px; flex-shrink: 0;
        border-radius: 10px; border: 1px solid #f0f0f0;
        background: #fff; overflow: hidden; cursor: pointer;
        position: relative; transition: box-shadow 0.2s, transform 0.2s;
    }
    .cart-img-thumb-wrap:hover { box-shadow: 0 4px 16px rgba(52,152,219,0.3); transform: scale(1.04); }
    .cart-img-thumb-wrap img { width: 100%; height: 100%; object-fit: contain; }
    .cart-img-thumb-wrap:hover::after {
        content: "\f00e"; font-family: "Font Awesome 5 Free"; font-weight: 900;
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.6); display: flex; align-items: center; justify-content: center;
        color: #333; font-size: 20px;
    }

    /* 8. MOVIL - CARRITO REDISEÑADO */
    @media (max-width: 700px) {
        /* Ocultar cabecera */
        .cart-table thead { display: none; }
        .cart-card { overflow: hidden; }
        .cart-table { max-width: 100%; width: 100%; }
        .cart-items-panel { max-width: 100%; overflow: hidden; }

        /* Cada fila: bloque vertical */
        .cart-item-row {
            display: block !important;
            padding: 16px !important;
            border-bottom: 2px solid #f5f5f5 !important;
        }

        /* Todas las celdas: bloque */
        .cart-item-row td {
            display: block !important;
            padding: 0 !important;
            border: none !important;
        }

        /* Celda producto: imagen + info en fila */
        .td-product { margin-bottom: 14px !important; max-width: 100%; overflow: hidden; }
        .td-product > div {
            display: flex !important;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
            overflow: hidden;
        }
        /* El div de info (nombre, talla, nota) no puede desbordarse */
        .td-product > div > div {
            min-width: 0;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .cart-img-thumb-wrap { width: 78px !important; height: 88px !important; }

        /* Fila inferior: qty + X + precio en una línea */
        .td-qty, .td-total, .td-remove {
            display: inline-flex !important;
            vertical-align: middle;
        }
        .td-qty {
            align-items: center;
            padding: 0 !important;
        }
        .td-remove {
            align-items: center;
            padding: 0 0 0 10px !important;
        }
        .td-remove a { font-size: 20px !important; }
        .td-total {
            flex-direction: column;
            align-items: flex-end;
            float: right;
            padding: 0 !important;
        }
        .td-total div { font-size: 16px !important; }

        /* Título y botón */
        .btn-buy { font-size: 15px; padding: 18px; }
        .cart-page-title { font-size: 1.4rem !important; }
    }
</style>

<div id="cart-toast">
    <i class="fas fa-sync-alt fa-spin" id="toast-icon"></i> 
    <span id="toast-msg">Actualizando tu carrito...</span>
</div>

<!-- MODAL VISUALIZADOR DE DISEÑO -->
<div id="design-preview-modal" onclick="if(event.target===this)closeDesignPreview()" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.88);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(10px);">
    <div style="background:#1a1a2e;border-radius:24px;padding:28px;max-width:820px;width:94%;max-height:92vh;overflow-y:auto;position:relative;box-shadow:0 30px 80px rgba(0,0,0,0.6);box-sizing:border-box;">
        <button onclick="closeDesignPreview()" style="position:absolute;top:16px;right:20px;background:none;border:none;color:#aaa;font-size:36px;cursor:pointer;line-height:1;">&times;</button>
        <h3 id="dp-titulo" style="color:#fff;font-size:15px;font-weight:900;margin:0 0 4px;padding-right:40px;"></h3>
        <div id="dp-meta" style="color:#aaa;font-size:11px;font-weight:700;margin-bottom:20px;"></div>
        <div id="dp-vistas" style="display:flex;gap:16px;flex-wrap:wrap;justify-content:center;margin-bottom:20px;"></div>
        <div id="dp-extras-wrap" style="display:none;">
            <div style="color:#f1c40f;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;"><i class="fas fa-tshirt"></i> Zonas adicionales</div>
            <div id="dp-extras" style="display:flex;gap:12px;flex-wrap:wrap;"></div>
        </div>
        <div id="dp-notas-wrap" style="display:none;margin-top:16px;background:rgba(255,255,255,0.05);border-radius:12px;padding:14px;">
            <div style="color:#f1c40f;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fas fa-sticky-note"></i> Notas del pedido</div>
            <div id="dp-notas" style="color:#ddd;font-size:12px;line-height:1.6;"></div>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px; min-height: 75vh;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <h1 class="cart-page-title" style="margin: 0; display: flex; align-items: center; gap: 15px; font-weight: 900; color: #2c3e50; font-size: 2rem;">
            <i class="fas fa-shopping-cart" style="color:#e74c3c;"></i> Tu Carrito
        </h1>
        <a href="productos.php" style="text-decoration: none; color: #7f8c8d; font-weight: 700; font-size: 14px;">
            <i class="fas fa-arrow-left"></i> VOLVER A LA TIENDA
        </a>
    </div>

    <?php if (empty($_SESSION['carrito'])): ?>
        <div style="text-align:center; padding: 100px 40px; background: #fff; border-radius: 30px; border: 2px dashed #ddd;">
            <div style="font-size: 70px; color: #f1f1f1; margin-bottom: 25px;"><i class="fas fa-ghost"></i></div>
            <h2 style="color: #2c3e50; font-weight: 800;">Tu carrito está vacío</h2>
            <p style="color: #999; font-size: 18px; margin-bottom: 30px;">Parece que aún no has diseñado nada increíble hoy.</p>
            <a href="personalizar.php" style="display: inline-block; background: #e74c3c; color: white; padding: 20px 40px; border-radius: 50px; text-decoration: none; font-weight: 900;">DISEÑAR AHORA</a>
        </div>
    <?php else: ?>
        
        <div class="cart-wrapper">
            <div class="cart-items-panel">
                <div class="cart-card">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 55%;">Producto</th>
                                <th style="text-align: center;">Cantidad</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $subtotal_productos = 0;
                        foreach(array_reverse($_SESSION['carrito'], true) as $clave => $item):
                            $es_p = (isset($item['id']) && $item['id'] === 'CUSTOM_PROD');
                            $row_id = "row-" . md5($clave);

                            // Lógica de precios simplificada y centralizada.
                            // El precio unitario correcto ('precio') SIEMPRE está en la sesión para cualquier tipo de producto.
                            // No es necesario recalcular nada aquí.
                            $unitPrice = (float)($item['precio'] ?? 0);
                            $linea_total = $unitPrice * $item['cantidad'];
                            $subtotal_productos += $linea_total;


                            if ($es_p) {
                                $cart_key_enc = urlencode($clave);
                                $item_link = "personalizar.php?edit_key=" . $cart_key_enc;
                            } else {
                                $item_link = "producto.php?id=" . urlencode($item['id'] ?? '');
                            }

                            $img_src = $es_p 
                                ? htmlspecialchars($item['imagen_personalizada'] ?? 'images/placeholder.png') 
                                : htmlspecialchars($item['imagen'] ?? 'images/placeholder.png');
                            $dp_key = "item_" . md5($clave); // clave única por item para el mapa JS
                        ?>
                        <tr class="cart-item-row" id="<?php echo $row_id; ?>">
                            <td class="td-product" style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <div class="cart-img-thumb-wrap" onclick="openItemPreview('<?php echo $dp_key; ?>')" style="cursor:pointer;">
                                        <img src="<?php echo $img_src; ?>" alt="Producto" onerror="this.src='images/placeholder.png'">
                                    </div>
                                    <div>
                                        <a href="<?php echo $item_link; ?>" style="text-decoration:none; font-weight: 800; color: #2c3e50; font-size: 16px; display:block; margin-bottom: 5px;">
                                            <?php echo htmlspecialchars($item['nombre'] ?? 'Producto Camiglobo'); ?>
                                        </a>
                                        <span style="background: #f1f1f1; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; color: #666;">
                                            Talla: <?php echo htmlspecialchars($item['talla'] ?? 'M'); ?>
                                        </span>
                                        <?php 
                                        $color_nombre_item = $item['color_producto'] ?? $item['color'] ?? '';
                                        $color_hex_item = $colores_hex[$color_nombre_item] ?? '#ccc';
                                        ?>
                                        <?php if (!empty($color_nombre_item) && $color_nombre_item !== 'Estándar'): ?>
                                        <span style="background: #f1f1f1; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; color: #666; display:inline-flex; align-items:center; gap:5px; margin-left:5px;">
                                            <span style="width:12px; height:12px; border-radius:50%; background:<?php echo htmlspecialchars($color_hex_item); ?>; border:1px solid rgba(0,0,0,0.15); display:inline-block; flex-shrink:0;"></span>
                                            <?php echo htmlspecialchars($color_nombre_item); ?>
                                        </span>
                                        <?php endif; ?>
                                        <div style="margin-top: 8px; font-weight: 800; color: #27ae60; font-size: 14px;">
                                            <?php if ($es_p): ?>
                                                <?php
                                                // Lógica de desglose simplificada y segura
                                                // Leemos directamente los valores ya calculados y guardados en la sesión
                                                $base_precio = (float)($item['precio_base'] ?? 0);
                                                $extras_costo = $unitPrice - $base_precio;
                                                $extras_items = $item['extras_descripcion'] ?? [];
                                                ?>
                                                Base: <?php echo number_format($base_precio, 2, ',', '.'); ?>€
                                                <?php if ($extras_costo > 0 && !empty($extras_items)): ?>
                                                    + Extras: <?php echo number_format($extras_costo, 2, ',', '.'); ?>€
                                                    <span style="font-size:11px; color:#e74c3c; font-weight:600; display:block; margin-top:3px;">
                                                        <?php echo htmlspecialchars(implode(', ', $extras_items)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo number_format($unitPrice, 2, ',', '.'); ?>€/ud
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($es_p): ?>
                                            <br>
                                            <div class="badge-custom">
                                                <i class="fas fa-paint-brush"></i>
                                                <a href="personalizar.php?edit_key=<?php echo urlencode($clave); ?>">✏️ Editar diseño</a>
                                            </div>
                                        <?php else: ?>
                                            <br>
                                            <div class="badge-custom">
                                                <i class="fas fa-edit"></i>
                                                <a href="producto.php?id=<?php echo urlencode($item['id'] ?? ''); ?>">✏️ Cambiar talla/color</a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['notas'])): ?>
    <div style="margin-top: 10px; background: #fff9db; border-left: 4px solid #f1c40f; padding: 10px; border-radius: 8px; font-size: 13px; color: #856404; word-break: break-word; overflow-wrap: anywhere;">
        <i class="fas fa-sticky-note"></i> <strong>Nota:</strong> 
        <?php echo htmlspecialchars($item['notas']); ?>
    </div>
<?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="td-qty" style="padding: 20px; text-align: center;">
                                <div class="qty-control">
                                    <button class="qty-btn" onclick="handleAction('menos', '<?php echo urlencode($clave); ?>', '<?php echo $row_id; ?>', <?php echo $item['cantidad']; ?>)">-</button>
                                    <span style="font-weight: 900; font-size: 16px; color: #000; min-width: 25px;"><?php echo (int)$item['cantidad']; ?></span>
                                    <button class="qty-btn" onclick="handleAction('mas', '<?php echo urlencode($clave); ?>', '<?php echo $row_id; ?>')">+</button>
                                </div>
                            </td>
                            <td class="td-total" style="padding: 20px; text-align: right;">
                                <div style="font-weight: 900; font-size: 18px; color: #2c3e50;"><?php echo number_format($linea_total, 2, ',', '.'); ?> €</div>
                                <small style="color: #bbb; font-weight: bold;">
                                    <?php echo number_format($unitPrice, 2, ',', '.'); ?>€/ud
                                </small>
                            </td>
                            <td class="td-remove" style="padding: 20px 25px; text-align: center;">
                                <a href="javascript:void(0)" onclick="handleAction('eliminar', '<?php echo urlencode($clave); ?>', '<?php echo $row_id; ?>')" style="color: #ddd; font-size: 20px; transition: 0.3s;" onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#ddd'">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                    // Emitir el mapa JS con todos los datos de los items para el modal
                    echo '<script>const CART_ITEMS_DATA = {};';
                    foreach($_SESSION['carrito'] as $clave => $item):
                        $es_p = (isset($item['id']) && $item['id'] === 'CUSTOM_PROD');
                        $dp_key = "item_" . md5($clave);
                        $img_src_js = $es_p
                            ? ($item['imagen_personalizada'] ?? 'images/placeholder.png')
                            : ($item['imagen'] ?? 'images/placeholder.png');
                        $obj = [
                            'es_custom' => $es_p,
                            'nombre'    => $item['nombre'] ?? '',
                            'talla'     => $item['talla']  ?? '',
                            'color'     => $item['color']  ?? '',
                            'precio'    => number_format((float)($item['precio'] ?? 0), 2, ',', '.') . ' €',
                            'front'     => $es_p ? ($item['imagen_personalizada'] ?? '') : $img_src_js,
                            'back'      => $es_p ? ($item['imagen_espalda'] ?? '') : '',
                            'extras'    => $es_p ? ($item['logos_extras'] ?? []) : [],
                            'notas'     => $es_p ? ($item['notas'] ?? '') : '',
                        ];
                        echo 'CART_ITEMS_DATA[' . json_encode($dp_key) . ']=' . json_encode($obj) . ';';
                    endforeach;
                    echo '</script>';
                    ?>
                </div>
            </div>

            <?php 
            $umbral_gratis = ENVIO_GRATIS_UMBRAL; 
            $costo_envio   = ($subtotal_productos >= $umbral_gratis) ? 0 : ENVIO_COSTE; 
            $total_final   = $subtotal_productos + $costo_envio;
            ?>
            <div class="cart-summary-panel">
                <div style="background: white; padding: 35px; border-radius: 25px; border: 1px solid #eee; box-shadow: 0 15px 50px rgba(0,0,0,0.05);">
                    <h3 style="margin-bottom: 25px; font-weight: 900; text-transform: uppercase; font-size: 1.1rem; color: #2c3e50; border-bottom: 2px solid #f9f9f9; padding-bottom: 15px;">Resumen de compra</h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #7f8c8d; font-weight: 600;">
                        <span>Subtotal:</span>
                        <span><?php echo number_format($subtotal_productos, 2, ',', '.'); ?> €</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 30px; color: #7f8c8d; font-weight: 600;">
                        <span>Envío:</span>
                        <span><?php echo ($costo_envio == 0) ? '<strong style="color: #27ae60;">GRATIS</strong>' : number_format($costo_envio, 2, ',', '.') . ' €'; ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 35px; align-items: center; background: #fcfcfc; padding: 20px; border-radius: 15px; border: 1px solid #f0f0f0;">
                        <span style="font-size: 16px; font-weight: 900; color: #2c3e50;">TOTAL:</span>
                        <span style="font-size: 28px; font-weight: 900; color: #e74c3c;"><?php echo number_format($total_final, 2, ',', '.'); ?> €</span>
                    </div>
                    
                    <a href="checkout.php" class="btn-buy">
                        CONTINUAR AL PAGO <i class="fas fa-lock" style="margin-left: 10px; font-size: 14px; opacity: 0.6;"></i>
                    </a>

                    <div class="payment-trust-box">
                        <div class="payment-icons-row">
                            <div class="pay-icon"><i class="fab fa-cc-paypal"></i></div>
                            <div class="pay-icon"><i class="fab fa-cc-visa"></i></div>
                            <div class="pay-icon"><i class="fab fa-cc-mastercard"></i></div>
                            <div class="pay-icon"><span class="bizum-text">Bizum</span></div>
                        </div>
                        <div class="trust-badges">
                            <div class="trust-item">
                                <i class="fas fa-shield-alt"></i>
                                <div>Compra protegida por Camiglobo<span class="trust-subtext">Garantía de satisfacción</span></div>
                            </div>
                            <div class="trust-item">
                                <i class="fas fa-lock"></i>
                                <div>Seguridad SSL 256-bit<span class="trust-subtext">Transacción encriptada</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; background: <?php echo ($costo_envio == 0) ? '#e8f5e8' : '#fff5f5'; ?>; padding: 15px; border-radius: 15px; border: 1px solid <?php echo ($costo_envio == 0) ? '#d4edda' : '#ffebeb'; ?>; text-align: center;">
                    <?php if ($costo_envio > 0): ?>
                        <span style="font-size: 12px; color: #e74c3c; font-weight: 700;">
                            🚀 Te faltan <strong><?php echo number_format($umbral_gratis - $subtotal_productos, 2, ',', '.'); ?> €</strong> para el envío <strong>GRATIS</strong>.
                        </span>
                    <?php else: ?>
                        <span style="font-size: 12px; color: #27ae60; font-weight: 800;">
                            🎉 ¡Envío <strong>GRATIS</strong> activado para tu pedido!
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // --- RECUPERAR POSICIÓN DEL SCROLL AL CARGAR ---
    window.addEventListener('DOMContentLoaded', () => {
        const scrollPos = sessionStorage.getItem('cartScrollPos');
        if (scrollPos) {
            window.scrollTo(0, scrollPos);
            sessionStorage.removeItem('cartScrollPos');
        }
    });

    // --- ABRIR MODAL CON LOS DATOS DEL ITEM ---
    function openItemPreview(key) {
        const data = (typeof CART_ITEMS_DATA !== 'undefined') ? CART_ITEMS_DATA[key] : null;
        if (!data) return;

        const modal = document.getElementById('design-preview-modal');

        document.getElementById('dp-titulo').textContent = data.nombre || 'Vista del diseño';
        const meta = [];
        if (data.talla)  meta.push('Talla: ' + data.talla);
        if (data.color)  meta.push('Color: ' + data.color);
        if (data.precio) meta.push(data.precio);
        document.getElementById('dp-meta').textContent = meta.join('  ·  ');

        // Vistas delante / detrás
        const vistasEl = document.getElementById('dp-vistas');
        vistasEl.innerHTML = '';
        function crearVista(src, label) {
            if (!src) return;
            const wrap = document.createElement('div');
            wrap.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:8px;';
            if (label) {
                const lbl = document.createElement('div');
                lbl.style.cssText = 'color:#aaa;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px;';
                lbl.textContent = label;
                wrap.appendChild(lbl);
            }
            const img = document.createElement('img');
            img.src = src;
            img.style.cssText = 'max-width:340px;max-height:420px;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,0.5);object-fit:contain;background:#fff;';
            img.onerror = function() { this.src = 'images/placeholder.png'; };
            wrap.appendChild(img);
            vistasEl.appendChild(wrap);
        }
        crearVista(data.front, data.back ? '🔜 Delante' : '');
        if (data.back) crearVista(data.back, '🔙 Detrás');

        // Zonas extras
        const extrasWrap = document.getElementById('dp-extras-wrap');
        const extrasEl   = document.getElementById('dp-extras');
        extrasEl.innerHTML = '';
        const tieneExtras = data.extras && Object.keys(data.extras).length > 0;
        extrasWrap.style.display = tieneExtras ? 'block' : 'none';
        if (tieneExtras) {
            for (const [zona, ruta] of Object.entries(data.extras)) {
                const wrap = document.createElement('div');
                wrap.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:6px;';
                const lbl = document.createElement('div');
                lbl.style.cssText = 'color:#aaa;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px;';
                lbl.textContent = zona;
                const img = document.createElement('img');
                img.src = ruta;
                img.style.cssText = 'max-width:160px;max-height:100px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.4);object-fit:contain;background:#fff;';
                img.onerror = function() { this.src = 'images/placeholder.png'; };
                wrap.appendChild(lbl);
                wrap.appendChild(img);
                extrasEl.appendChild(wrap);
            }
        }

        // Notas
        const notasWrap = document.getElementById('dp-notas-wrap');
        if (data.notas && data.notas.trim()) {
            document.getElementById('dp-notas').textContent = data.notas;
            notasWrap.style.display = 'block';
        } else {
            notasWrap.style.display = 'none';
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDesignPreview() {
        document.getElementById('design-preview-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDesignPreview(); });

    function handleAction(accion, key, rowId, currentQty = null) {
        const toast    = document.getElementById('cart-toast');
        const toastMsg = document.getElementById('toast-msg');
        const toastIcon = document.getElementById('toast-icon');
        const row      = document.getElementById(rowId);
        
        const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

        let accionFinal = accion;
        if (accion === 'menos' && currentQty === 1) {
            accionFinal = 'eliminar';
        }

        if (accionFinal === 'eliminar') {
            toastMsg.innerText = "Producto fuera del carrito";
            toastIcon.className = "fas fa-trash-alt";
            toastIcon.style.color = "#e74c3c";
            toastIcon.style.animation = "none";
            row.style.opacity = '0.3';
            row.style.filter = 'grayscale(1) blur(1px)';
        } else {
            toastMsg.innerText = "Actualizando carrito...";
            toastIcon.className = "fas fa-sync-alt fa-spin";
            toastIcon.style.color = "#27ae60";
        }

        toast.classList.add('show');

        setTimeout(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'carrito_modificar.php';

            const campos = {
                'accion': accionFinal,
                'key': key,
                'csrf_token': csrfToken
            };

            for (const nombre in campos) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = nombre;
                input.value = campos[nombre];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            sessionStorage.setItem('cartScrollPos', window.scrollY);
            form.submit();
        }, 700);
    }
</script>

<?php include 'includes/footer.php'; ?>
