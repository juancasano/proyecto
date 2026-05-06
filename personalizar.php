<?php 
/**
 * ARCHIVO: personalizar.php
 */
include 'includes/header.php'; 
$recurso_auto_load = '';
if (isset($_GET['recurso_id']) && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $rid = filter_input(INPUT_GET, 'recurso_id', FILTER_VALIDATE_INT);
    $uid = (int)$_SESSION['user_id'];
    if ($rid !== false && $rid > 0) {
        $stmtR = $pdo->prepare("SELECT ruta_imagen FROM biblioteca_recursos WHERE id = ? AND user_id = ?");
        $stmtR->execute([$rid, $uid]);
        $res = $stmtR->fetch();
        if ($res) $recurso_auto_load = $res['ruta_imagen'];
    }
}

// Cargar diseño guardado desde el carrito para editar
$edit_diseno = null;
if (isset($_GET['edit_key']) && !empty($_SESSION['carrito'])) {
    $edit_key = $_GET['edit_key'];
    if (isset($_SESSION['carrito'][$edit_key]) && $_SESSION['carrito'][$edit_key]['id'] === 'CUSTOM_PROD') {
        $item = $_SESSION['carrito'][$edit_key];
        $edit_diseno = [
            'tipo'           => $item['tipo_base']     ?? 'camiseta',
            'talla'          => $item['talla']          ?? 'L',
            'color'          => $item['color']          ?? 'Transparente',
            'color_producto' => $item['color_producto'] ?? 'Negro',
            'notas'          => $item['notas']          ?? '',
            'diseno_front'   => $item['diseno_front']   ?? null,
            'diseno_back'    => $item['diseno_back']    ?? null,
            'diseno_minis'   => $item['diseno_minis']   ?? [],
        ];
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Bungee&family=Lobster&family=Montserrat:wght@800&family=Playfair+Display:ital,wght@1,700&family=Pacifico&family=Roboto:wght@900&family=Dancing+Script:wght@700&family=Oswald:wght@700&family=Permanent+Marker&family=Bebas+Neue&family=Righteous&family=Satisfy&family=Alfa+Slab+One&family=Press+Start+2P&family=Cinzel:wght@700&family=Abril+Fatface&family=Staatliches&family=Kalam:wght@700&family=Fjalla+One&family=Anton&family=Secular+One&display=swap" rel="stylesheet">

<!-- BARRA STICKY MÓVIL -->
<div class="mobile-sticky-nav" id="mobile-sticky-nav">
    <button class="msn-btn" onclick="document.querySelector('.card-fotos').scrollIntoView({behavior:'smooth',block:'start'})">
        <i class="fas fa-cloud-upload-alt"></i><span>Subir foto</span>
    </button>
    <button class="msn-btn" onclick="document.querySelector('.card-texto').scrollIntoView({behavior:'smooth',block:'start'})">
        <i class="fas fa-font"></i><span>Añadir texto</span>
    </button>
    <button class="msn-btn" onclick="document.querySelector('.card-plantillas').scrollIntoView({behavior:'smooth',block:'start'})">
        <i class="fas fa-layer-group"></i><span>Plantillas</span>
    </button>
    <button class="msn-btn msn-btn-undo" onclick="undoAction()" title="Deshacer">
        <i class="fas fa-undo"></i><span>Deshacer</span>
    </button>
    <button class="msn-btn msn-btn-undo" onclick="redoAction()" title="Rehacer">
        <i class="fas fa-redo"></i><span>Rehacer</span>
    </button>
</div>

<!-- FAB MENÚ FLOTANTE MÓVIL -->
<div class="fab-menu" id="fab-menu">
    <button class="fab-btn fab-main" id="fab-toggle" title="Acciones">
        <i class="fas fa-plus"></i>
    </button>
    <button class="fab-btn fab-sub" onclick="undoAction()" title="Deshacer">
        <i class="fas fa-undo"></i>
    </button>
    <button class="fab-btn fab-sub" onclick="redoAction()" title="Rehacer">
        <i class="fas fa-redo"></i>
    </button>
    <button class="fab-btn fab-sub" onclick="document.getElementById('btn-ver-lienzo').click()" title="Ver diseño">
        <i class="fas fa-eye"></i>
    </button>
    <button class="fab-btn fab-sub" onclick="duplicarElemento()" title="Duplicar">
        <i class="fas fa-copy"></i>
    </button>
</div>

<div class="container cg-container">

    <!-- CABECERA -->
    <div class="cg-header">
        <div class="cg-header-top">
            <h1 class="cg-titulo">
                <i class="fas fa-paint-roller"></i> ESTUDIO DE DISEÑO <span>CAMIGLOBO</span>
            </h1>
            <p class="cg-subtitulo">Crea tu producto personalizado en minutos. 100% online, sin complicaciones.</p>
        </div>

        <!-- GUÍA DEL EDITOR -->
        <div class="guia-editor">
            <div class="guia-header" onclick="toggleGuia()" style="cursor:pointer; user-select:none; justify-content:space-between;">
                <span><i class="fas fa-book-open"></i> GUÍA DEL EDITOR — Cómo funciona paso a paso</span>
                <span style="display:flex;gap:10px;align-items:center;">
                    <span id="guia-toggle-icon" style="font-size:14px; transition:transform 0.3s;"><i class="fas fa-chevron-right"></i></span>
                </span>
            </div>
            <div id="guia-contenido" style="display:none;">
            <div class="guia-pasos">

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(52,152,219,0.2); color:#74b9ff;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>1. Elige producto y color</strong>
                        <span>Selecciona <em>Camiseta, Sudadera, Taza o Cuadro</em>. En ropa elige también la <em>talla</em>. Para el fondo puedes elegir un <em>color sólido</em> o crear un <em>degradado</em> con dos colores y dirección personalizada (→ ↓ ↘ ↙).</span>
                        <div style="margin-top:6px;display:flex;flex-direction:column;gap:4px;">
                            <div style="font-size:10.5px;background:rgba(230,126,34,0.15);border-left:3px solid #e67e22;padding:4px 8px;border-radius:0 4px 4px 0;color:rgba(255,255,255,0.95);line-height:1.4;">
                                <strong style="color:#f39c12;">👕 Color de prenda</strong> = el color físico de la tela que recibirás. <em>No se imprime.</em>
                            </div>
                            <div style="font-size:10.5px;background:rgba(52,152,219,0.15);border-left:3px solid #3498db;padding:4px 8px;border-radius:0 4px 4px 0;color:rgba(255,255,255,0.95);line-height:1.4;">
                                <strong style="color:#74b9ff;">🖼️ Fondo del lienzo</strong> = lo que sí se imprime encima. Ponlo <em>Transparente</em> si tu imagen ya tiene fondo.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(241,196,15,0.2); color:#f1c40f;">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>2. Sube tu foto</strong>
                        <span>Pulsa <em>Subir imagen</em> — acepta JPG, PNG, WEBP y HEIC (iPhone). Las fotos se guardan en tu <em>biblioteca de perfil</em> para reutilizarlas en futuros diseños sin volver a subirlas. Una vez en el lienzo: arrastra las esquinas para <em>redimensionar</em>, usa los sliders de <em>escala, rotación y opacidad</em>, o voltéala con los botones de <em>espejo horizontal/vertical</em>. Para mejor calidad sube imágenes de <em>al menos 1000px</em>.</span>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(52,73,94,0.35); color:#a29bfe;">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>3. Filtros Pro de imagen</strong>
                        <span>Con una foto seleccionada en el lienzo, aplica cualquiera de los <em>16 filtros</em>: <em>Gris, Blanco &amp; Negro, Sepia, Vintage, Kodak, Cine, Polaroid, Contraste, Brillo, Blur, Enfoque, Pixelar, Tinte, Vibrancia</em> y <em>Ruido</em>. Cada filtro se aplica al instante y puedes volver a <em>Original</em> en cualquier momento.</span>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(231,76,60,0.2); color:#e74c3c;">
                        <i class="fas fa-font"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>4. Añade texto</strong>
                        <span>Pulsa <em>+ Añadir texto</em>, escribe y confirma con Enter. <em>Doble clic</em> sobre un texto del lienzo para editarlo. Personaliza la <em>tipografía</em> (20 fuentes), <em>color</em>, <em>tamaño</em>, <em>espaciado</em> y <em>alineación</em>. Aplica cualquiera de los <em>16 efectos</em>: <em>Sombra, Contorno negro/blanco, Neón verde/azul/rosa, Retro, Hielo, Dorado, Plata, Fuego, Glitch, Contorno grueso</em> y <em>Arcoíris</em> — cada uno se previsualiza en su botón.</span>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(26,188,156,0.2); color:#1abc9c;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>5. Plantillas y stickers</strong>
                        <span>En <em>Plantillas</em> encuentra diseños listos en 7 categorías: <em>Texto, Composición, Fondos, Layout, Eventos, Deporte y Naturaleza</em>. Aplícalas con <em>Reemplazar</em> o <em>Añadir encima</em>. <em>Doble clic</em> en el hueco 📷 para colocar tu foto desde la biblioteca. En <em>Stickers &amp; Emojis</em> tienes decenas de emojis y símbolos — haz clic para añadirlos al lienzo y ajústalos con los controles de tamaño y rotación.</span>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(22,160,133,0.2); color:#1abc9c;">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>6. Barra de herramientas</strong>
                        <span>Sobre el lienzo: <em>Deshacer/Rehacer</em> (Ctrl+Z / Ctrl+Y) · <em>Centrar, girar, duplicar</em> (Ctrl+D) · <em>Subir/bajar capa</em> · <em>Eliminar</em> (Supr) · <em>Borrar todo</em>. Las <em>líneas guía</em> aparecen al arrastrar para ayudarte a alinear con precisión.</span>
                        <div style="margin-top:6px;font-size:10.5px;background:rgba(230,126,34,0.15);border-left:3px solid #e67e22;padding:4px 8px;border-radius:0 4px 4px 0;color:rgba(255,255,255,0.95);line-height:1.4;">
                            <strong style="color:#f39c12;">⚠️ Zona de impresión</strong> (línea discontinua) — todo lo que quede <em>fuera</em> de ese rectángulo se recortará al imprimir. Mantén tu diseño dentro.
                        </div>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(155,89,182,0.2); color:#9b59b6;">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>7. Zonas extra (ropa)</strong>
                        <span>Las camisetas y sudaderas incluyen <em>Nuca, Manga izquierda y Manga derecha</em> como lienzos independientes. Haz clic en uno para activarlo y diseña en él igual que en el lienzo principal. <em>Solo se cobran las zonas que uses</em> (+3 € cada una). El precio se actualiza en tiempo real.</span>
                    </div>
                </div>

                <div class="gp-sep"><i class="fas fa-chevron-right"></i></div>

                <div class="gp-item">
                    <div class="gp-icono" style="background:rgba(39,174,96,0.2); color:#2ecc71;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="gp-texto">
                        <strong>8. Previsualiza y pide</strong>
                        <span>Pulsa <em>Ver mi diseño</em> para ver el resultado final en alta calidad — en ropa se muestran delante y detrás. Si quieres ajustes, indícalos en <em>Notas del pedido</em>. Cuando esté listo, pulsa <em>Añadir al carrito</em>. También puedes <em>Descargar</em> el diseño como PNG en cualquier momento.</span>
                    </div>
                </div>

            </div>
            <div class="guia-tips">
                <div class="gt-tip">
                    <i class="fas fa-border-all" style="color:#e67e22;"></i>
                    <span>La <strong>línea discontinua</strong> es la zona de impresión — mantén todo tu diseño dentro para que no se recorte nada.</span>
                </div>
                <div class="gt-sep"></div>
                <div class="gt-tip">
                    <i class="fas fa-keyboard" style="color:#74b9ff;"></i>
                    <span><strong>Ctrl+Z</strong> deshacer · <strong>Ctrl+Y</strong> rehacer · <strong>Supr</strong> eliminar · <strong>Ctrl+D</strong> duplicar · <strong>Doble clic</strong> editar texto.</span>
                </div>
                <div class="gt-sep"></div>
                <div class="gt-tip">
                    <i class="fas fa-palette" style="color:#f1c40f;"></i>
                    <span><strong>Fondo del lienzo</strong> = lo que se imprime. <strong>Color de prenda</strong> = el color físico del producto. Son independientes entre sí.</span>
                </div>
                <div class="gt-sep"></div>
                <div class="gt-tip">
                    <i class="fas fa-image" style="color:#2ecc71;"></i>
                    <span>Los <strong>PNG sin fondo</strong> dan el mejor resultado. Para mayor calidad de impresión, sube fotos de <strong>mínimo 1000px</strong>.</span>
                </div>
            </div>
            </div><!-- fin guia-contenido -->
        </div>
    </div>

    <div class="editor-grid">

        <!-- PANEL HERRAMIENTAS -->
        <div class="tools-panel">

            <!-- TARJETA PRODUCTO: Arriba del panel, ocupa las 2 columnas -->
            <div class="product-top-bar ptb-in-tools">

                <!-- Select oculto para compatibilidad con el JS existente -->
                <select id="product-type" style="display:none;" onchange="updateProductLogic()">
                    <option value="camiseta">Camiseta Premium</option>
                    <option value="sudadera">Sudadera con Capucha</option>
                    <option value="taza">Taza Cerámica</option>
                    <option value="cuadro">Cuadro / Lienzo</option>
                </select>

                <!-- FILA SUPERIOR: tarjetas de producto + talla/colores -->
                <div class="ptb-compact-row">


                    <!-- 4 tarjetas de producto compactas -->
                    <div class="ptb-compact-products">
                        <div class="product-card active" onclick="elegirProducto(this,'camiseta')">
                            <div class="pc-icon-wrap" style="font-size:22px;">👕</div>
                            <div class="pc-name">Camiseta</div>
                            <div class="pc-price">26,00 €</div>
                        </div>
                        <div class="product-card" onclick="elegirProducto(this,'sudadera')">
                            <div class="pc-icon-wrap" style="font-size:22px;">🥼</div>
                            <div class="pc-name">Sudadera</div>
                            <div class="pc-price">35,00 €</div>
                        </div>
                        <div class="product-card" onclick="elegirProducto(this,'taza')">
                            <div class="pc-icon-wrap" style="font-size:22px;">☕</div>
                            <div class="pc-name">Taza</div>
                            <div class="pc-price">12,00 €</div>
                        </div>
                        <div class="product-card" onclick="elegirProducto(this,'cuadro')">
                            <div class="pc-icon-wrap" style="font-size:22px;">🖼️</div>
                            <div class="pc-name">Cuadro</div>
                            <div class="pc-price">30,00 €</div>
                        </div>
                    </div>

                    <div class="ptb-vsep" style="height:auto;align-self:stretch;"></div>

                    <!-- Talla + colores -->
                    <div class="ptb-compact-options">
                        <div class="ptb-group" id="dynamic-option-wrap"></div>
                        <div class="ptb-compact-colores">
                            <div class="ptb-group" id="grupo-color-producto" style="display:none;">
                                <label class="label-sm" id="label-color-producto">
                                    👕 Color de prenda
                                    <span style="font-size:9px;color:#aaa;font-weight:600;"> — color físico del artículo que recibirás</span>
                                </label>
                                <div style="font-size:9px;color:#e67e22;background:rgba(230,126,34,0.08);border-left:3px solid #e67e22;padding:4px 7px;border-radius:0 4px 4px 0;margin-bottom:8px;line-height:1.4;">
                                    <i class="fas fa-info-circle"></i> Este es el color de la tela. <strong>No se imprime</strong> — es el color que se ve donde no hay diseño.
                                </div>
                                <div id="color-producto-options" class="palette-container ptb-palette-scroll"></div>
                                <span id="color-prenda-nombre" class="color-nombre-prenda">Negro</span>
                            </div>
                            <div class="ptb-group">
                                <label class="label-sm">
                                    🖼️ Fondo del lienzo
                                    <span style="font-size:9px;color:#aaa;font-weight:600;"> — color que se imprime sobre el producto</span>
                                </label>
                                <div id="color-options" class="palette-container ptb-palette-scroll"></div>
                                <span id="color-lienzo-nombre" class="color-nombre-display">Transparente</span>
                                <div style="font-size:9px;color:#3498db;background:rgba(52,152,219,0.08);border-left:3px solid #3498db;padding:4px 7px;border-radius:0 4px 4px 0;margin-top:4px;line-height:1.4;">
                                    <i class="fas fa-info-circle"></i> Esto <strong>sí se imprime</strong>. Elige <em>Transparente</em> si tu imagen ya tiene fondo o es un logo.
                                </div>
                                <!-- DEGRADADO — inline bajo la paleta de color -->
                                <div style="margin-top:8px;">
                                    <label class="label-sm" style="display:flex;align-items:center;gap:5px;margin-bottom:5px;">
                                        <i class="fas fa-fill-drip" style="color:#9b59b6;font-size:9px;"></i> O degradado:
                                    </label>
                                    <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
                                        <input type="color" id="grad-color1" value="#e74c3c" style="width:28px;height:28px;border-radius:6px;border:2px solid #eee;cursor:pointer;padding:1px;flex-shrink:0;">
                                        <input type="color" id="grad-color2" value="#9b59b6" style="width:28px;height:28px;border-radius:6px;border:2px solid #eee;cursor:pointer;padding:1px;flex-shrink:0;">
                                        <button onclick="applyGradient('to bottom')"       class="filter-btn grad-dir-btn" style="width:24px;height:24px;padding:0;font-size:12px;" title="Vertical ↓">↓</button>
                                        <button onclick="applyGradient('to right')"        class="filter-btn grad-dir-btn" style="width:24px;height:24px;padding:0;font-size:12px;" title="Horizontal →">→</button>
                                        <button onclick="applyGradient('to bottom right')" class="filter-btn grad-dir-btn" style="width:24px;height:24px;padding:0;font-size:12px;" title="Diagonal ↘">↘</button>
                                        <button onclick="applyGradient('to bottom left')"  class="filter-btn grad-dir-btn" style="width:24px;height:24px;padding:0;font-size:12px;" title="Diagonal ↙">↙</button>
                                        <button onclick="applyGradient(gradDirActual)" class="filter-btn" style="width:auto;padding:0 8px;height:24px;font-size:10px;font-weight:800;background:#9b59b6;color:#fff;border:none;border-radius:6px;cursor:pointer;white-space:nowrap;" title="Aplicar degradado al lienzo"><i class="fas fa-check" style="margin-right:3px;"></i>Aplicar</button>
                                        <button onclick="removeGradient()" class="filter-btn" style="width:24px;height:24px;padding:0;font-size:11px;" title="Quitar degradado"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div id="grad-preview" style="margin-top:5px;height:14px;border-radius:6px;background:linear-gradient(to right,#e74c3c,#9b59b6);border:1px solid #eee;transition:0.3s;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- SEPARADOR HORIZONTAL -->
                <div class="ptb-hsep"></div>

                <!-- FILA INFERIOR: descripción del producto -->
                <div id="product-description" class="ptb-desc-row"></div>

            </div>

            <!-- TARJETA 2: Fotos y Filtros -->
            <div class="tool-card card-fotos" style="border-left:6px solid #2c3e50;">

                <label class="btn-upload-label">
                    <i class="fas fa-cloud-upload-alt"></i> SUBIR IMAGEN
                    <input type="file" id="upload-img" hidden onchange="handleImage(event)">
                </label>
                <div id="biblioteca-area" style="display:none; margin-top:20px;">
                    <label class="label-sm">Fotos guardadas en tu perfil:</label>
                    <div id="lista-recursos" class="resource-list"></div>
                </div>

                <!-- Control de escala y rotación -->
                <div style="margin-top: 20px; background: #f8f9fa; padding: 12px; border-radius: 15px; border: 1px solid #eee;">
                    <!-- ESCALA -->
                    <label class="label-sm" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        Escalar foto seleccionada: <span id="scale-val" style="color: #2c3e50; font-weight: 900;">100%</span>
                    </label>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <button class="slider-btn" onclick="nudgeSlider('image-scale-slider',-0.05,updateImageScale)" title="Reducir">−</button>
                        <input type="range" id="image-scale-slider" min="0.01" max="2" step="0.01" value="1"
                               oninput="updateImageScale(this.value)" style="flex: 1; cursor: pointer; accent-color: #2c3e50;">
                        <button class="slider-btn" onclick="nudgeSlider('image-scale-slider',0.05,updateImageScale)" title="Aumentar">+</button>
                        <button onclick="resetImageScale()" style="background: #2c3e50; color: white; border: none; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; flex-shrink: 0;" title="Restaurar tamaño original">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>
                    <p style="font-size: 9px; color: #999; margin-top: 5px; text-align: center; font-weight: 600;">
                        <i class="fas fa-search-plus"></i> Desliza para ajustar el tamaño con precisión
                    </p>

                    <!-- ROTACIÓN -->
                    <label class="label-sm" style="display: flex; justify-content: space-between; margin-bottom: 8px; margin-top: 15px;">
                        Girar foto: <span id="rotation-val" style="color: #2c3e50; font-weight: 900;">0°</span>
                    </label>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <button class="slider-btn" onclick="nudgeSlider('image-rotation-slider',-5,updateImageRotation)" title="Girar izquierda">−</button>
                        <div style="flex: 1;">
                            <input type="range" id="image-rotation-slider" min="-180" max="180" step="1" value="0"
                                   list="rotation-ticks"
                                   oninput="updateImageRotation(this.value)" style="width: 100%; cursor: pointer; accent-color: #e74c3c;">
                            <datalist id="rotation-ticks">
                                <option value="-180"></option>
                                <option value="-90" label="-90°"></option>
                                <option value="0" label="0°"></option>
                                <option value="90" label="+90°"></option>
                                <option value="180"></option>
                            </datalist>
                            <!-- Etiquetas de referencia -->
                            <div style="display: flex; justify-content: space-between; padding: 0 2px; margin-top: 1px; position: relative;">
                                <span style="font-size: 8px; color: #bbb; font-weight: 800;">-180°</span>
                                <span style="font-size: 8px; color: #e74c3c; font-weight: 900; position: absolute; left: 25%; transform: translateX(-50%);">-90°</span>
                                <span style="font-size: 8px; color: #3498db; font-weight: 900; position: absolute; left: 50%; transform: translateX(-50%);">0°</span>
                                <span style="font-size: 8px; color: #e74c3c; font-weight: 900; position: absolute; left: 75%; transform: translateX(-50%);">+90°</span>
                                <span style="font-size: 8px; color: #bbb; font-weight: 800;">+180°</span>
                            </div>
                        </div>
                        <button class="slider-btn" onclick="nudgeSlider('image-rotation-slider',5,updateImageRotation)" title="Girar derecha">+</button>
                        <button onclick="resetImageRotation()" style="background: #e74c3c; color: white; border: none; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; flex-shrink: 0; margin-bottom: 14px;" title="Restaurar a 0°">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                    <!-- OPACIDAD -->
                    <label class="label-sm" style="display:flex; justify-content:space-between; margin-bottom:8px; margin-top:15px;">
                        Opacidad: <span id="opacity-val" style="color:#8e44ad; font-weight:900;">100%</span>
                    </label>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <button class="slider-btn" onclick="nudgeSlider('opacity-slider',-0.05,updateOpacity)" title="Reducir opacidad">−</button>
                        <input type="range" id="opacity-slider" min="0" max="1" step="0.01" value="1"
                               oninput="updateOpacity(this.value)" style="flex:1; cursor:pointer; accent-color:#8e44ad;">
                        <button class="slider-btn" onclick="nudgeSlider('opacity-slider',0.05,updateOpacity)" title="Aumentar opacidad">+</button>
                        <button onclick="resetOpacity()" style="background:#8e44ad; color:white; border:none; border-radius:8px; width:30px; height:30px; cursor:pointer; flex-shrink:0;" title="Restaurar opacidad">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                    <!-- VOLTEAR -->
                    <label class="label-sm" style="margin-top:15px; margin-bottom:8px;">Voltear imagen:</label>
                    <div style="display:flex; gap:8px;">
                        <button onclick="flipImage('x')" class="filter-btn" style="flex:1; font-size:12px;" title="Voltear horizontal">
                            <i class="fas fa-arrows-alt-h"></i> Horizontal
                        </button>
                        <button onclick="flipImage('y')" class="filter-btn" style="flex:1; font-size:12px;" title="Voltear vertical">
                            <i class="fas fa-arrows-alt-v"></i> Vertical
                        </button>
                    </div>
                </div>

                <div style="margin-top:25px;">
                    <label class="label-sm" id="filter-section">Filtros Pro (Selecciona la foto primero):</label>
                    <div class="filter-grid-modern">
                        <button onclick="applyFilter('none')"        class="fbtn fbtn-reset">✕ Original</button>
                        <button onclick="applyFilter('grayscale')"   class="fbtn fbtn-gray">⬛ Gris</button>
                        <button onclick="applyFilter('blackwhite')"  class="fbtn fbtn-bw">◐ B&amp;N</button>
                        <button onclick="applyFilter('sepia')"       class="fbtn fbtn-sepia">🟫 Sepia</button>
                        <button onclick="applyFilter('brownie')"     class="fbtn fbtn-brownie">☕ Vintage</button>
                        <button onclick="applyFilter('kodak')"       class="fbtn fbtn-kodak">📷 Kodak</button>
                        <button onclick="applyFilter('technicolor')" class="fbtn fbtn-tech">🎨 Cine</button>
                        <button onclick="applyFilter('polaroid')"    class="fbtn fbtn-polar">🔲 Polar.</button>
                        <button onclick="applyFilter('contrast')"    class="fbtn fbtn-contrast">◑ Contr.</button>
                        <button onclick="applyFilter('brightness')"  class="fbtn fbtn-bright">☀️ Brillo</button>
                        <button onclick="applyFilter('blur')"        class="fbtn fbtn-blur">💧 Blur</button>
                        <button onclick="applyFilter('sharpen')"     class="fbtn fbtn-sharp">🔍 Enfoq.</button>
                        <button onclick="applyFilter('pixelate')"    class="fbtn fbtn-pixel">▦ Pixel</button>
                        <button onclick="applyFilter('tint')"        class="fbtn fbtn-tint">🔴 Tinte</button>
                        <button onclick="applyFilter('vibrance')"    class="fbtn fbtn-vib">⚡ Vibran.</button>
                        <button onclick="applyFilter('noise')"       class="fbtn fbtn-noise">📻 Ruido</button>
                    </div>
                </div>

                <!-- STICKERS & EMOJIS -->
                <div style="margin-top:20px;">
                    <label class="label-sm" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                        <i class="fas fa-star" style="color:#e67e22;"></i> Stickers & Emojis (clic para añadir al lienzo):
                    </label>
                    <div style="font-size:10px;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.05);border-radius:8px;padding:5px 9px;margin-bottom:10px;line-height:1.4;">
                        💡 Selecciona un sticker en el lienzo para <strong style="color:rgba(255,255,255,0.85);">moverlo, redimensionarlo o eliminarlo</strong>. Usa los controles de <strong style="color:rgba(255,255,255,0.85);">tamaño y rotación del panel de texto</strong> para ajustarlo.
                    </div>
                    <div class="sticker-grid" id="sticker-grid">
                        <button class="sticker-btn" onclick="addSticker('❤️')">❤️</button>
                        <button class="sticker-btn" onclick="addSticker('🧡')">🧡</button>
                        <button class="sticker-btn" onclick="addSticker('💛')">💛</button>
                        <button class="sticker-btn" onclick="addSticker('💚')">💚</button>
                        <button class="sticker-btn" onclick="addSticker('💙')">💙</button>
                        <button class="sticker-btn" onclick="addSticker('💜')">💜</button>
                        <button class="sticker-btn" onclick="addSticker('🖤')">🖤</button>
                        <button class="sticker-btn" onclick="addSticker('🤍')">🤍</button>
                        <button class="sticker-btn" onclick="addSticker('⭐')">⭐</button>
                        <button class="sticker-btn" onclick="addSticker('🌟')">🌟</button>
                        <button class="sticker-btn" onclick="addSticker('✨')">✨</button>
                        <button class="sticker-btn" onclick="addSticker('💫')">💫</button>
                        <button class="sticker-btn" onclick="addSticker('🔥')">🔥</button>
                        <button class="sticker-btn" onclick="addSticker('⚡')">⚡</button>
                        <button class="sticker-btn" onclick="addSticker('🌈')">🌈</button>
                        <button class="sticker-btn" onclick="addSticker('☀️')">☀️</button>
                        <button class="sticker-btn" onclick="addSticker('😊')">😊</button>
                        <button class="sticker-btn" onclick="addSticker('😎')">😎</button>
                        <button class="sticker-btn" onclick="addSticker('🥳')">🥳</button>
                        <button class="sticker-btn" onclick="addSticker('😍')">😍</button>
                        <button class="sticker-btn" onclick="addSticker('🤣')">🤣</button>
                        <button class="sticker-btn" onclick="addSticker('😂')">😂</button>
                        <button class="sticker-btn" onclick="addSticker('🤩')">🤩</button>
                        <button class="sticker-btn" onclick="addSticker('😜')">😜</button>
                        <button class="sticker-btn" onclick="addSticker('👑')">👑</button>
                        <button class="sticker-btn" onclick="addSticker('💎')">💎</button>
                        <button class="sticker-btn" onclick="addSticker('🏆')">🏆</button>
                        <button class="sticker-btn" onclick="addSticker('🎯')">🎯</button>
                        <button class="sticker-btn" onclick="addSticker('🎨')">🎨</button>
                        <button class="sticker-btn" onclick="addSticker('🎵')">🎵</button>
                        <button class="sticker-btn" onclick="addSticker('🎸')">🎸</button>
                        <button class="sticker-btn" onclick="addSticker('🎂')">🎂</button>
                        <button class="sticker-btn" onclick="addSticker('🌸')">🌸</button>
                        <button class="sticker-btn" onclick="addSticker('🌺')">🌺</button>
                        <button class="sticker-btn" onclick="addSticker('🌻')">🌻</button>
                        <button class="sticker-btn" onclick="addSticker('🍀')">🍀</button>
                        <button class="sticker-btn" onclick="addSticker('🦋')">🦋</button>
                        <button class="sticker-btn" onclick="addSticker('🌙')">🌙</button>
                        <button class="sticker-btn" onclick="addSticker('❄️')">❄️</button>
                        <button class="sticker-btn" onclick="addSticker('🌊')">🌊</button>
                        <button class="sticker-btn" onclick="addSticker('🐶')">🐶</button>
                        <button class="sticker-btn" onclick="addSticker('🐱')">🐱</button>
                        <button class="sticker-btn" onclick="addSticker('🦁')">🦁</button>
                        <button class="sticker-btn" onclick="addSticker('🐺')">🐺</button>
                        <button class="sticker-btn" onclick="addSticker('🦊')">🦊</button>
                        <button class="sticker-btn" onclick="addSticker('🐻')">🐻</button>
                        <button class="sticker-btn" onclick="addSticker('🐼')">🐼</button>
                        <button class="sticker-btn" onclick="addSticker('🦄')">🦄</button>
                        <button class="sticker-btn" onclick="addSticker('✅')">✅</button>
                        <button class="sticker-btn" onclick="addSticker('❌')">❌</button>
                        <button class="sticker-btn" onclick="addSticker('♾️')">♾️</button>
                        <button class="sticker-btn" onclick="addSticker('☮️')">☮️</button>
                        <button class="sticker-btn" onclick="addSticker('☯️')">☯️</button>
                        <button class="sticker-btn" onclick="addSticker('✝️')">✝️</button>
                        <button class="sticker-btn" onclick="addSticker('🕊️')">🕊️</button>
                        <button class="sticker-btn" onclick="addSticker('💯')">💯</button>
                    </div>
                </div>
            </div>

            <!-- TARJETA 3: Texto -->
            <div class="tool-card card-texto" style="border-left:6px solid #e74c3c;">

                <!-- Botón principal -->
                <button onclick="toggleTextInput()" class="btn-add-text" id="btn-toggle-text-input">
                    <i class="fas fa-plus" id="icon-toggle-text"></i> AÑADIR TEXTO
                </button>

                <!-- Input oculto por defecto -->
                <div class="text-input-wrap" id="text-input-area" style="display:none; margin-top:10px; animation:fadeInDown 0.2s;">
                    <input type="text" id="texto-nuevo" class="text-input-field" placeholder="Escribe tu texto aquí..." maxlength="100"
                           onkeydown="if(event.key==='Enter') addText()">
                    <button onclick="addText()" class="btn-add-text-inline">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>

                <div class="text-controls-grid">
                    <div>
                        <label class="label-sm" style="display:flex;justify-content:space-between;">
                            Tamaño: <span id="text-size-val" style="color:#e74c3c;font-weight:900;">22px</span>
                        </label>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <button class="slider-btn" onclick="nudgeSlider('text-size-slider',-2,function(v){updateTextStyles('size',v);document.getElementById('text-size-val').innerText=v+'px';})" title="Reducir">−</button>
                            <input type="range" id="text-size-slider" min="4" max="150" value="22"
                                   oninput="updateTextStyles('size', this.value); document.getElementById('text-size-val').innerText=this.value+'px';"
                                   style="flex:1; accent-color:#e74c3c;">
                            <button class="slider-btn" onclick="nudgeSlider('text-size-slider',2,function(v){updateTextStyles('size',v);document.getElementById('text-size-val').innerText=v+'px';})" title="Aumentar">+</button>
                            <button onclick="resetTextSize()" style="background:#e74c3c;color:white;border:none;border-radius:8px;width:30px;height:30px;cursor:pointer;flex-shrink:0;" title="Restaurar tamaño por defecto">
                                <i class="fas fa-undo-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="label-sm">Estilo:</label>
                        <div style="display:flex; gap:8px;">
                            <button onclick="updateTextStyles('bold')"   id="btn-bold"   class="filter-btn" style="flex:1; font-size:14px;" title="Negrita"><i class="fas fa-bold"></i></button>
                            <button onclick="updateTextStyles('italic')" id="btn-italic" class="filter-btn" style="flex:1; font-size:14px;" title="Cursiva"><i class="fas fa-italic"></i></button>
                        </div>
                    </div>
                </div>

                    <!-- Rotación del texto -->
                <div style="background:#f8f9fa; padding:12px; border-radius:15px; border:1px solid #eee; margin-bottom:12px;">
                    <label class="label-sm" style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        Girar texto: <span id="text-rotation-val" style="color:#e74c3c;font-weight:900;">0°</span>
                    </label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="slider-btn" onclick="nudgeSlider('text-rotation-slider',-5,updateTextRotation)" title="Girar izquierda">−</button>
                        <div style="flex:1;">
                            <input type="range" id="text-rotation-slider" min="-180" max="180" step="1" value="0"
                                   list="text-rotation-ticks"
                                   oninput="updateTextRotation(this.value)" style="width:100%; cursor:pointer; accent-color:#e74c3c;">
                            <datalist id="text-rotation-ticks">
                                <option value="-180"></option>
                                <option value="-90" label="-90°"></option>
                                <option value="0" label="0°"></option>
                                <option value="90" label="+90°"></option>
                                <option value="180"></option>
                            </datalist>
                            <!-- Etiquetas de referencia -->
                            <div style="display:flex;justify-content:space-between;padding:0 2px;margin-top:1px;position:relative;">
                                <span style="font-size:8px;color:#bbb;font-weight:800;">-180°</span>
                                <span style="font-size:8px;color:#e74c3c;font-weight:900;position:absolute;left:25%;transform:translateX(-50%);">-90°</span>
                                <span style="font-size:8px;color:#3498db;font-weight:900;position:absolute;left:50%;transform:translateX(-50%);">0°</span>
                                <span style="font-size:8px;color:#e74c3c;font-weight:900;position:absolute;left:75%;transform:translateX(-50%);">+90°</span>
                                <span style="font-size:8px;color:#bbb;font-weight:800;">+180°</span>
                            </div>
                        </div>
                        <button class="slider-btn" onclick="nudgeSlider('text-rotation-slider',5,updateTextRotation)" title="Girar derecha">+</button>
                        <button onclick="resetTextRotation()" style="background:#e74c3c;color:white;border:none;border-radius:8px;width:30px;height:30px;cursor:pointer;flex-shrink:0;margin-bottom:14px;" title="Restaurar a 0°">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                    <!-- ESPACIADO ENTRE LETRAS -->
                    <label class="label-sm" style="display:flex;justify-content:space-between;margin-bottom:8px;margin-top:15px;">
                        Espaciado letras: <span id="spacing-val" style="color:#16a085;font-weight:900;">0</span>
                    </label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="slider-btn" onclick="nudgeSlider('letter-spacing-slider',-1,updateLetterSpacing)" title="Reducir">−</button>
                        <input type="range" id="letter-spacing-slider" min="-5" max="50" step="1" value="0"
                               oninput="updateLetterSpacing(this.value)" style="flex:1; cursor:pointer; accent-color:#16a085;">
                        <button class="slider-btn" onclick="nudgeSlider('letter-spacing-slider',1,updateLetterSpacing)" title="Aumentar">+</button>
                        <button onclick="resetLetterSpacing()" style="background:#16a085;color:white;border:none;border-radius:8px;width:30px;height:30px;cursor:pointer;flex-shrink:0;" title="Restaurar espaciado">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                    <!-- ALTURA DE LÍNEA -->
                    <label class="label-sm" style="display:flex;justify-content:space-between;margin-bottom:8px;margin-top:15px;">
                        Altura de línea: <span id="lineheight-val" style="color:#e67e22;font-weight:900;">1.2</span>
                    </label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="slider-btn" onclick="nudgeSlider('line-height-slider',-0.1,updateLineHeight)" title="Reducir">−</button>
                        <input type="range" id="line-height-slider" min="0.5" max="3" step="0.1" value="1.2"
                               oninput="updateLineHeight(this.value)" style="flex:1; cursor:pointer; accent-color:#e67e22;">
                        <button class="slider-btn" onclick="nudgeSlider('line-height-slider',0.1,updateLineHeight)" title="Aumentar">+</button>
                        <button onclick="resetLineHeight()" style="background:#e67e22;color:white;border:none;border-radius:8px;width:30px;height:30px;cursor:pointer;flex-shrink:0;" title="Restaurar altura de línea">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                    <!-- OPACIDAD DEL TEXTO -->
                    <label class="label-sm" style="display:flex;justify-content:space-between;margin-bottom:8px;margin-top:15px;">
                        Opacidad: <span id="text-opacity-val" style="color:#8e44ad;font-weight:900;">100%</span>
                    </label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="slider-btn" onclick="nudgeSlider('text-opacity-slider',-0.05,updateTextOpacity)" title="Reducir opacidad">−</button>
                        <input type="range" id="text-opacity-slider" min="0" max="1" step="0.01" value="1"
                               oninput="updateTextOpacity(this.value)" style="flex:1; cursor:pointer; accent-color:#8e44ad;">
                        <button class="slider-btn" onclick="nudgeSlider('text-opacity-slider',0.05,updateTextOpacity)" title="Aumentar opacidad">+</button>
                        <button onclick="updateTextOpacity(1)" style="background:#8e44ad;color:white;border:none;border-radius:8px;width:30px;height:30px;cursor:pointer;flex-shrink:0;" title="Restaurar opacidad">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>

                </div>

                <label class="label-sm">Color de letra:</label>
                <div id="text-colors" class="palette-container" style="margin-bottom:14px;"></div>

                <label class="label-sm">Tipografía (20 estilos):</label>
                <div class="font-picker" id="font-picker">
                    <!-- Sin serif / Modernas -->
                    <div class="font-option active" onclick="seleccionarFuente(this,'Arial')"            style="font-family:Arial;">Aa Arial</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Oswald')"           style="font-family:'Oswald';">Aa Oswald</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Montserrat')"       style="font-family:'Montserrat';">Aa Montserrat</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Anton')"            style="font-family:'Anton';">Aa Anton</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Fjalla One')"       style="font-family:'Fjalla One';">Aa Fjalla</div>
                    <!-- Display / Impacto -->
                    <div class="font-option"        onclick="seleccionarFuente(this,'Bebas Neue')"       style="font-family:'Bebas Neue';">Aa Bebas</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Staatliches')"      style="font-family:'Staatliches';">Aa Staat</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Alfa Slab One')"    style="font-family:'Alfa Slab One';">Aa Alfa</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Abril Fatface')"    style="font-family:'Abril Fatface';">Aa Abril</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Righteous')"        style="font-family:'Righteous';">Aa Right.</div>
                    <!-- Script / Caligrafía -->
                    <div class="font-option"        onclick="seleccionarFuente(this,'Pacifico')"         style="font-family:'Pacifico';">Aa Pacifico</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Lobster')"          style="font-family:'Lobster';">Aa Lobster</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Dancing Script')"   style="font-family:'Dancing Script';">Aa Dancing</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Satisfy')"          style="font-family:'Satisfy';">Aa Satisfy</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Kalam')"            style="font-family:'Kalam';">Aa Kalam</div>
                    <!-- Especiales / Decorativas -->
                    <div class="font-option"        onclick="seleccionarFuente(this,'Permanent Marker')" style="font-family:'Permanent Marker';">Aa Marker</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Bungee')"           style="font-family:'Bungee';">Aa Bungee</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Cinzel')"           style="font-family:'Cinzel';">Aa Cinzel</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Playfair Display')" style="font-family:'Playfair Display';">Aa Playfair</div>
                    <div class="font-option"        onclick="seleccionarFuente(this,'Press Start 2P')"   style="font-family:'Press Start 2P';font-size:9px;">8bit</div>
                </div>
                <div style="margin-top:25px;">
                    <label class="label-sm">Efectos de texto (Selecciona el texto primero):</label>
                    <div class="filter-grid-modern">
                        <button onclick="aplicarEfectoTexto('none')"          class="fbtn fbtn-reset">✕ Sin efecto</button>
                        <button onclick="aplicarEfectoTexto('shadow')"        class="fbtn" style="background:#dfe6e9;color:#2c3e50;border-color:#b2bec3;text-shadow:2px 2px 4px rgba(0,0,0,0.4);">💫 Sombra</button>
                        <button onclick="aplicarEfectoTexto('stroke-black')"  class="fbtn" style="background:#fff;color:#fff;border:2px solid #222;-webkit-text-stroke:1.5px #000;text-shadow:none;">⬛ C.Negro</button>
                        <button onclick="aplicarEfectoTexto('stroke-white')"  class="fbtn" style="background:#555;color:#555;border:2px solid #ccc;-webkit-text-stroke:1.5px #fff;">⬜ C.Blanco</button>
                        <button onclick="aplicarEfectoTexto('neon')"          class="fbtn" style="background:#0a1a0a;color:#39ff14;border-color:#39ff14;text-shadow:0 0 7px #39ff14,0 0 14px #39ff14;">⚡ Neón</button>
                        <button onclick="aplicarEfectoTexto('neon-blue')"     class="fbtn" style="background:#05101a;color:#00cfff;border-color:#00cfff;text-shadow:0 0 7px #00cfff,0 0 14px #00cfff;">💙 N.Azul</button>
                        <button onclick="aplicarEfectoTexto('neon-pink')"     class="fbtn" style="background:#1a0510;color:#ff2d78;border-color:#ff2d78;text-shadow:0 0 7px #ff2d78,0 0 14px #ff2d78;">🌸 N.Rosa</button>
                        <button onclick="aplicarEfectoTexto('retro')"         class="fbtn" style="background:#ff6b35;color:#fff;border-color:#c0392b;text-shadow:3px 3px 0 #c0392b;">🎨 Retro</button>
                        <button onclick="aplicarEfectoTexto('retro-blue')"    class="fbtn" style="background:#3498db;color:#fff;border-color:#1565c0;text-shadow:3px 3px 0 #1565c0;">🔷 R.Azul</button>
                        <button onclick="aplicarEfectoTexto('ice')"           class="fbtn" style="background:#d6f0ff;color:#0077b6;border-color:#90cdf4;text-shadow:1px 1px 4px rgba(0,180,255,0.5);">❄️ Hielo</button>
                        <button onclick="aplicarEfectoTexto('gold')"          class="fbtn" style="background:#3d2e00;color:#f1c40f;border-color:#d4ac0d;-webkit-text-stroke:1px #d4ac0d;text-shadow:0 0 8px rgba(241,196,15,0.9);">🌟 Dorado</button>
                        <button onclick="aplicarEfectoTexto('silver')"        class="fbtn" style="background:#2d2d2d;color:#e0e0e0;border-color:#aaa;-webkit-text-stroke:1px #aaa;text-shadow:0 0 7px rgba(200,200,200,0.8);">🥈 Plata</button>
                        <button onclick="aplicarEfectoTexto('fire')"          class="fbtn" style="background:linear-gradient(180deg,#3d0a00,#1a0500);color:#ff6b00;border-color:#ff6b00;text-shadow:0 0 6px #ff6b00,0 -3px 8px #ffcc00;">🔥 Fuego</button>
                        <button onclick="aplicarEfectoTexto('glitch')"        class="fbtn" style="background:#1a001a;color:#fff;border-color:#ff00ff;text-shadow:2px 0 #ff0000,-2px 0 #00ffff;">📺 Glitch</button>
                        <button onclick="aplicarEfectoTexto('outline-thick')" class="fbtn" style="background:#fff9c4;color:#333;border-color:#f1c40f;-webkit-text-stroke:2px #222;">🖊️ Grueso</button>
                        <button onclick="aplicarEfectoTexto('rainbow-shadow')" class="fbtn" style="background:linear-gradient(135deg,#1a001a,#00101a);color:#fff;border-color:#9b59b6;text-shadow:3px 0 #e74c3c,6px 0 #e67e22,9px 0 #f1c40f;">🌈 Arcoíris</button>
                    </div>
                </div>
            </div>

           
            <!-- COLUMNA PLANTILLAS + NOTAS -->
            <div class="plantillas-notas-col">

            <!-- TARJETA PLANTILLAS -->
            <div class="tool-card card-plantillas" style="border-left:6px solid #1abc9c;">
                <h3 class="tool-title"><span class="tool-title-icon" style="background:#1abc9c;color:#fff;"><i class="fas fa-layer-group"></i></span> Plantillas de diseño</h3>
                <p style="font-size:10px;color:#aaa;font-weight:600;margin-bottom:12px;">Aplica una plantilla como punto de partida o añádela encima de tu diseño actual.</p>

                <!-- Filtro por categoría -->
                <div class="plantillas-tabs" id="plantillas-tabs">
                    <button class="ptab active" onclick="filtrarPlantillas('texto',this)">✏️ Texto</button>
                    <button class="ptab" onclick="filtrarPlantillas('composicion',this)">🖼️ Comp.</button>
                    <button class="ptab" onclick="filtrarPlantillas('fondo',this)">🎨 Fondos</button>
                    <button class="ptab" onclick="filtrarPlantillas('layout',this)">📐 Layout</button>
                    <button class="ptab" onclick="filtrarPlantillas('eventos',this)">🎉 Eventos</button>
                    <button class="ptab" onclick="filtrarPlantillas('deporte',this)">⚽ Deporte</button>
                    <button class="ptab" onclick="filtrarPlantillas('naturaleza',this)">🌿 Nature</button>
                </div>

                <!-- Navegación paginación -->
                <div class="plantillas-paginacion" id="plantillas-paginacion">
                    <button class="plantillas-arrow" id="plantillas-prev" onclick="cambiarPagPlantillas(-1)" title="Anteriores"><i class="fas fa-chevron-left"></i></button>
                    <span class="plantillas-page-info" id="plantillas-page-info">1 / 1</span>
                    <button class="plantillas-arrow" id="plantillas-next" onclick="cambiarPagPlantillas(1)" title="Siguientes"><i class="fas fa-chevron-right"></i></button>
                </div>

                <!-- Grid de plantillas -->
                <div class="plantillas-grid" id="plantillas-grid"></div>

                <!-- Modal de aplicación -->
                <div id="modal-plantilla" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(4px);" onclick="if(event.target===this)cerrarModalPlantilla()">
                    <div style="background:#1a1a2e; border-radius:22px; padding:24px; max-width:340px; width:90%; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
                        <h3 style="color:#fff;font-size:14px;font-weight:900;margin:0 0 6px;"><i class="fas fa-layer-group" style="color:#1abc9c;"></i> Aplicar plantilla</h3>
                        <p id="modal-plantilla-nombre" style="color:#aaa;font-size:11px;font-weight:600;margin:0 0 18px;"></p>
                        <!-- Preview -->
                        <div id="modal-plantilla-preview" style="width:100%;height:120px;border-radius:14px;margin-bottom:16px;border:1px solid rgba(255,255,255,0.1);overflow:hidden;display:flex;align-items:center;justify-content:center;"></div>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <div style="background:rgba(241,196,15,0.12);border:1px solid rgba(241,196,15,0.3);border-radius:10px;padding:10px 12px;font-size:10px;color:rgba(255,255,255,0.75);line-height:1.5;">
                                <strong style="color:#f1c40f;"><i class="fas fa-lightbulb"></i> Consejos:</strong><br>
                                • <strong style="color:#fff;">Doble clic</strong> sobre cualquier texto para editarlo directamente.<br>
                                • <strong style="color:#fff;">Doble clic</strong> en el hueco 📷 <span style="color:#74b9ff;">(borde azul)</span> → te lleva a tus fotos guardadas para elegir cuál colocar.
                            </div>
                            <button onclick="aplicarPlantillaConAviso('reemplazar')" style="background:#1abc9c;color:#fff;border:none;border-radius:12px;padding:12px;font-size:12px;font-weight:900;cursor:pointer;text-transform:uppercase;letter-spacing:0.5px;">
                                <i class="fas fa-sync-alt"></i> Reemplazar lienzo actual
                            </button>
                            <button onclick="aplicarPlantilla('añadir')" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);border-radius:12px;padding:12px;font-size:12px;font-weight:900;cursor:pointer;text-transform:uppercase;letter-spacing:0.5px;">
                                <i class="fas fa-plus"></i> Añadir encima del diseño actual
                            </button>
                            <button onclick="cerrarModalPlantilla()" style="background:transparent;color:#aaa;border:none;padding:8px;font-size:11px;cursor:pointer;font-weight:700;">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NOTAS DEL PEDIDO -->
            <div class="extras-card tool-card" style="border-left:6px solid #f1c40f;">
                <h3 class="tool-title"><span class="tool-title-icon" style="background:#f1c40f;color:#fff;"><i class="fas fa-sticky-note"></i></span> Notas del pedido</h3>

                <div id="extras-ropa-wrap" style="display:none;"></div>
                <div id="desglose-precio" class="desglose-box" style="display:none;"></div>

                <div class="notas-hints">
                    <div class="notas-hint-title"><i class="fas fa-lightbulb"></i> ¿Qué puedes indicarnos aquí?</div>
                    <div class="notas-hint-lista">
                        <div class="notas-hint-item"><i class="fas fa-text-height" style="color:#3498db;"></i><span><strong>Ajustes de diseño:</strong> "Quiero el texto más grande", "centra la imagen un poco más arriba", "¿podéis eliminar el fondo de la foto?"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-heart" style="color:#e74c3c;"></i><span><strong>Dedicatorias:</strong> "Es un regalo de cumpleaños para mi madre — por favor, envuélvelo si podéis", "Fecha especial: 14 de febrero"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-palette" style="color:#8e44ad;"></i><span><strong>Dudas de color:</strong> "No estoy seguro del color de prenda, ¿el azul marino es muy oscuro?", "¿el rojo es vivo o más anaranjado?"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-ruler" style="color:#27ae60;"></i><span><strong>Talla o ajuste:</strong> "Soy talla M pero llevo ropa holgada, ¿cojo L?", "El destinatario mide 1,85 y pesa 80kg"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-images" style="color:#e67e22;"></i><span><strong>Imagen de mayor calidad:</strong> "Voy a enviaros la foto por email con más resolución — dirección: tu@email.com"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-shipping-fast" style="color:#16a085;"></i><span><strong>Urgencia o fecha límite:</strong> "Lo necesito antes del día 20", "¿hay opción de envío urgente?"</span></div>
                        <div class="notas-hint-item"><i class="fas fa-comment-dots" style="color:#f1c40f;"></i><span><strong>Cualquier otra cosa:</strong> Si algo no cabe en el editor o tienes una idea especial, ¡cuéntanosla aquí sin límite!</span></div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; margin-top:4px;">
                    <i class="fas fa-pencil-alt" style="color:#f1c40f; font-size:13px;"></i>
                    <span style="font-size:11px; font-weight:900; color:#2c3e50; text-transform:uppercase; letter-spacing:0.5px;">Escribe aquí tus instrucciones</span>
                    <span style="font-size:10px; color:#aaa; font-weight:600;">(opcional)</span>
                </div>
                <textarea id="notas-extra" class="notas-textarea" placeholder="Ej: quiero el texto más grande, eliminar el fondo de la foto, es un regalo para mi madre..." oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
            </div>

            </div><!-- FIN plantillas-notas-col -->

            <!-- TARJETA 5: Compra -->
            <div class="tool-card buy-card">
                <div class="precio-wrap">
                    <span class="precio-label">PRECIO FINAL:</span>
                    <span id="display-price" class="precio-num">26,00 €</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
                    <button onclick="abrirPreview()" class="btn-preview btn-preview-pulse">
                        <i class="fas fa-eye"></i> VER MI DISEÑO
                    </button>
                    <div style="font-size:9px;color:#aaa;font-weight:700;text-align:right;line-height:1.4;">👆 Previsualiza antes de añadir al carrito</div>
                    <div class="buy-buttons">
                        <button onclick="downloadDesign()" class="btn-download">
                            <i class="fas fa-download"></i> DESCARGAR
                        </button>
                        <button id="btn-add-cart" onclick="enviarAlCarrito()" class="btn-finish">
                            AÑADIR AL CARRITO <i class="fas fa-shopping-basket"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- FIN tools-panel -->

        <!-- PANEL CANVAS -->
        <div class="canvas-panel">

            <!-- CONTADOR DE PRECIO ENCIMA DEL LIENZO Y HERRAMIENTAS -->
            <div class="precio-live-wrap" style="margin-bottom:12px;">
                <div class="precio-live-inner">
                    <div class="precio-live-label">
                        <i class="fas fa-tag"></i> Tu precio ahora mismo
                    </div>
                    <div class="precio-live-num" id="precio-live-display">30,00 €</div>
                </div>
                <div id="precio-live-extras" class="precio-live-desglose"></div>
            </div>

            <!-- Botones Delante/Detrás -->
            <div id="view-toggles" style="display:flex; justify-content:center; gap:10px; margin-bottom:12px;">
                <button onclick="cambiarVista('front')" id="btn-vista-front" class="btn-view active">
                    <i class="fas fa-arrow-left"></i> DELANTE
                </button>
                <button onclick="cambiarVista('back')" id="btn-vista-back" class="btn-view">
                    DETRÁS <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <!-- BLOQUE CENTRADO: toolbar + lienzo -->
            <div class="canvas-center-wrap">

            <!-- BARRA DE HERRAMIENTAS -->
            <div class="pro-toolbar">
                <div class="toolbar-buttons">

                    <!-- Grupo: Historial -->
                    <div class="toolbar-group">
                        <button onclick="undoAction()" class="tool-btn tool-undo" title="Deshacer (Ctrl+Z)">
                            <i class="fas fa-undo"></i>
                            <span class="tool-btn-text">Deshacer</span>
                        </button>
                        <button onclick="redoAction()" class="tool-btn tool-redo" title="Rehacer (Ctrl+Y)">
                            <i class="fas fa-redo"></i>
                            <span class="tool-btn-text">Rehacer</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Zona impresión -->
                    <div class="toolbar-group">
                        <button onclick="toggleBoundary()" class="tool-btn tool-boundary" id="btn-boundary" title="Mostrar/ocultar zona de impresión">
                            <i class="fas fa-border-all"></i>
                            <span class="tool-btn-text">Zona</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Alineación -->
                    <div class="toolbar-group">
                        <button onclick="centerObj('h')" class="tool-btn" title="Centrar Horizontal">
                            <i class="fas fa-arrows-alt-h"></i>
                            <span class="tool-btn-text">Centro H</span>
                        </button>
                        <button onclick="centerObj('v')" class="tool-btn" title="Centrar Vertical">
                            <i class="fas fa-arrows-alt-v"></i>
                            <span class="tool-btn-text">Centro V</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Girar -->
                    <div class="toolbar-group">
                        <button onclick="rotarElemento(-10)" class="tool-btn tool-rotate-l" title="Girar -15°">
                            <i class="fas fa-undo"></i>
                            <span class="tool-btn-text">Girar</span>
                        </button>
                        <button onclick="rotarElemento(10)" class="tool-btn tool-rotate-r" title="Girar +15°">
                            <i class="fas fa-redo"></i>
                            <span class="tool-btn-text">Girar</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Restaurar / Duplicar -->
                    <div class="toolbar-group">
                        <button onclick="scaleSelected(0.9)" class="tool-btn" title="Empequeñecer">
                            <i class="fas fa-search-minus"></i>
                            <span class="tool-btn-text">Menor</span>
                        </button>
                        <button onclick="scaleSelected(1.1)" class="tool-btn" title="Agrandar">
                            <i class="fas fa-search-plus"></i>
                            <span class="tool-btn-text">Mayor</span>
                        </button>
                        <button onclick="restaurarTamano()" class="tool-btn tool-restore" title="Restaurar tamaño y rotación">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span class="tool-btn-text">Reset</span>
                        </button>
                        <button onclick="duplicarElemento()" class="tool-btn tool-duplicate" title="Duplicar elemento (Ctrl+D)">
                            <i class="fas fa-clone"></i>
                            <span class="tool-btn-text">Copiar</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Capas -->
                    <div class="toolbar-group">
                        <button onclick="activeCanvas.getActiveObject() && (activeCanvas.bringToFront(activeCanvas.getActiveObject()), activeCanvas.renderAll())" class="tool-btn" title="Traer al frente">
                            <i class="fas fa-layer-group"></i><span class="tool-btn-label">▲</span>
                            <span class="tool-btn-text">Frente</span>
                        </button>
                        <button onclick="changeLayer('up')" class="tool-btn" title="Subir una capa">
                            <i class="fas fa-layer-group"></i><span class="tool-btn-label">+</span>
                            <span class="tool-btn-text">Subir</span>
                        </button>
                        <button onclick="changeLayer('down')" class="tool-btn" title="Bajar una capa">
                            <i class="fas fa-layer-group"></i><span class="tool-btn-label">−</span>
                            <span class="tool-btn-text">Bajar</span>
                        </button>
                        <button onclick="activeCanvas.getActiveObject() && (activeCanvas.sendToBack(activeCanvas.getActiveObject()), activeCanvas.renderAll())" class="tool-btn" title="Enviar al fondo">
                            <i class="fas fa-layer-group"></i><span class="tool-btn-label">▼</span>
                            <span class="tool-btn-text">Fondo</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Voltear -->
                    <div class="toolbar-group">
                        <button onclick="flipImage('x')" class="tool-btn" title="Voltear horizontal" style="color:#8e44ad;">
                            <i class="fas fa-exchange-alt"></i>
                            <span class="tool-btn-text">Espejo</span>
                        </button>
                        <button onclick="flipImage('y')" class="tool-btn" title="Voltear vertical" style="color:#8e44ad;">
                            <i class="fas fa-arrows-alt-v"></i>
                            <span class="tool-btn-text">Espejo</span>
                        </button>
                    </div>

                    <div class="tool-sep"></div>

                    <!-- Grupo: Eliminar -->
                    <div class="toolbar-group">
                        <button onclick="deleteSelected()" class="tool-btn tool-del" title="Eliminar elemento (Supr)">
                            <i class="fas fa-trash-alt"></i>
                            <span class="tool-btn-text">Borrar</span>
                        </button>
                        <button onclick="resetCanvas()" class="tool-btn tool-reset" title="Borrar todo el lienzo">
                            <i class="fas fa-sync-alt"></i>
                            <span class="tool-btn-text">Limpiar</span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="lienzo-paralelo-wrap">
                
                <div class="canvas-principal-col">
                <div id="product-container" class="canvas-wrapper" style="position:relative;">
                    <canvas id="tshirt-canvas" width="1200" height="1400"></canvas>
                </div>
                <!-- Botones acción lienzo principal -->
                <div class="main-canvas-tools">
                    <button onclick="setActiveCanvas(canvas, true);" class="mini-btn-text"><i class="fas fa-font"></i> Texto</button>
                    <button onclick="setActiveCanvas(canvas, false); scrollToBiblioteca();" class="mini-btn-upload"><i class="fas fa-image"></i> Imagen</button>
                    <button onclick="abrirModalColorFondo()" class="mini-btn-color"><i class="fas fa-palette"></i> Color</button>
                    <button onclick="deleteSelected()" class="mini-btn-del"><i class="fas fa-trash"></i> Borrar</button>
                </div>

                <!-- Botones Delante/Detrás debajo del lienzo principal -->
                <div id="view-toggles-bottom" style="display:none; justify-content:center; gap:10px; margin-top:10px;">
                    <button onclick="cambiarVista('front')" id="btn-vista-front-bottom" class="btn-view active">
                        <i class="fas fa-arrow-left"></i> DELANTE
                    </button>
                    <button onclick="cambiarVista('back')" id="btn-vista-back-bottom" class="btn-view">
                        DETRÁS <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                </div>

                <div id="mini-canvas-wrap" class="zonas-extra-col" style="display:none;">

                    <div class="mini-section-title">
                        <i class="fas fa-tshirt"></i> Zonas adicionales — +3€ por zona usada
                    </div>

                    <div class="mini-section-label">🔝 Nuca / Cuello</div>
                    <div class="mini-canvases-row" style="margin-bottom:14px;">
                        <div class="mini-canvas-block" id="mini-block-nuca" style="max-width:100%;">
                            <div class="mini-canvas-label">Etiqueta / Nuca</div>
                            <div class="mini-canvas-inner" style="width:100%; max-width:300px; height:70px;" onclick="setActiveCanvas(miniCanvases['nuca'], false); mostrarAvisoZona(this);">
                                <canvas id="canvas-nuca" width="300" height="70"></canvas>
                                <div class="mini-tap-hint"><i class="fas fa-hand-pointer"></i> Toca para editar esta zona</div>
                            </div>
                            <div class="mini-canvas-tools" style="width:100%; max-width:300px;">
                                <button onclick="setActiveCanvas(miniCanvases['nuca'], true);" class="mini-btn-text"><i class="fas fa-font"></i> Texto</button>
                                <button onclick="setActiveCanvas(miniCanvases['nuca'], false); scrollToBiblioteca();" class="mini-btn-upload"><i class="fas fa-image"></i> Imagen</button>
                                <button onclick="abrirModalMiniColor('nuca')" class="mini-btn-color" id="mini-color-btn-nuca"><i class="fas fa-palette"></i> Color</button>
                                <button onclick="deleteMiniSelected('nuca')" class="mini-btn-del"><i class="fas fa-trash"></i> Borrar</button>
                            </div>
                        </div>
                    </div>

                    <div class="mini-section-label">👕 Mangas</div>
                    <div class="mini-canvases-row">

                        <div class="mini-canvas-block" id="mini-block-manga-izq">
                            <div class="mini-canvas-label">👈 Manga Izq.</div>
                            <div class="mini-canvas-inner" style="width:100%;" onclick="setActiveCanvas(miniCanvases['manga-izq'], false); mostrarAvisoZona(this);">
                                <canvas id="canvas-manga-izq" width="140" height="100"></canvas>
                                <div class="mini-tap-hint"><i class="fas fa-hand-pointer"></i> Toca para editar</div>
                            </div>
                            <div class="mini-canvas-tools" style="width:100%;">
                                <button onclick="setActiveCanvas(miniCanvases['manga-izq'], true);" class="mini-btn-text"><i class="fas fa-font"></i> Texto</button>
                                <button onclick="setActiveCanvas(miniCanvases['manga-izq'], false); scrollToBiblioteca();" class="mini-btn-upload"><i class="fas fa-image"></i> Imagen</button>
                                <button onclick="abrirModalMiniColor('manga-izq')" class="mini-btn-color" id="mini-color-btn-manga-izq" title="Color de fondo"><i class="fas fa-palette"></i> Color</button>
                                <button onclick="deleteMiniSelected('manga-izq')" class="mini-btn-del"><i class="fas fa-trash"></i> Borrar</button>
                            </div>
                        </div>

                        <div class="mini-canvas-block" id="mini-block-manga-der">
                            <div class="mini-canvas-label">👉 Manga Der.</div>
                            <div class="mini-canvas-inner" style="width:100%;" onclick="setActiveCanvas(miniCanvases['manga-der'], false); mostrarAvisoZona(this);">
                                <canvas id="canvas-manga-der" width="140" height="100"></canvas>
                                <div class="mini-tap-hint"><i class="fas fa-hand-pointer"></i> Toca para editar</div>
                            </div>
                            <div class="mini-canvas-tools" style="width:100%;">
                                <button onclick="setActiveCanvas(miniCanvases['manga-der'], true);" class="mini-btn-text"><i class="fas fa-font"></i> Texto</button>
                                <button onclick="setActiveCanvas(miniCanvases['manga-der'], false); scrollToBiblioteca();" class="mini-btn-upload"><i class="fas fa-image"></i> Imagen</button>
                                <button onclick="abrirModalMiniColor('manga-der')" class="mini-btn-color" id="mini-color-btn-manga-der"><i class="fas fa-palette"></i> Color</button>
                                <button onclick="deleteMiniSelected('manga-der')" class="mini-btn-del"><i class="fas fa-trash"></i> Borrar</button>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            </div><!-- FIN canvas-center-wrap -->


        </div>

    </div>
</div>

<!-- MENÚ CONTEXTUAL DE OBJETO (posición fija, funciona en canvas principal y minis) -->
<div id="ctx-menu" style="display:none;position:fixed;z-index:99999;background:#1a1a2e;border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:6px;box-shadow:0 6px 24px rgba(0,0,0,0.6);flex-direction:column;gap:3px;min-width:155px;">
    <div id="ctx-menu-inner"></div>
</div>

<!-- MODAL COLOR FONDO MINI LIENZO -->
<div id="modal-mini-color" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(4px);" onclick="if(event.target===this)cerrarModalMiniColor()">
    <div style="background:#1a1a2e; border-radius:22px; padding:24px; max-width:360px; width:90%; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div>
                <h3 style="margin:0; color:#fff; font-weight:900; font-size:1rem; letter-spacing:0.5px;">
                    <i class="fas fa-palette" style="color:#8e44ad;"></i> Color de fondo
                </h3>
                <p id="modal-mini-color-zona" style="margin:4px 0 0; color:#aaa; font-size:11px; font-weight:600;"></p>
            </div>
            <button onclick="cerrarModalMiniColor()" style="background:rgba(255,255,255,0.1); border:none; color:white; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center;" onmouseover="this.style.background='rgba(231,76,60,0.7)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modal-mini-color-grid" style="display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:16px;"></div>
        <button onclick="cerrarModalMiniColor()" style="width:100%; background:rgba(255,255,255,0.1); color:white; border:1px solid rgba(255,255,255,0.2); padding:11px; border-radius:12px; font-weight:900; font-size:12px; cursor:pointer; text-transform:uppercase; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <i class="fas fa-check"></i> CERRAR
        </button>
    </div>
</div>

<!-- MODAL PREVISUALIZACIÓN DEL DISEÑO -->
<div id="modal-preview" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(6px);" onclick="if(event.target===this)cerrarPreview()">
    <div style="background:#1a1a2e; border-radius:25px; padding:20px; max-width:95vw; width:min(700px,95vw); max-height:92vh; overflow-y:auto; position:relative; box-shadow:0 30px 80px rgba(0,0,0,0.5); display:flex; flex-direction:column; align-items:center; gap:16px; box-sizing:border-box;">
        
        <!-- Cabecera -->
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; gap:30px;">
            <div>
                <h3 style="margin:0; color:white; font-weight:900; font-size:1.1rem; letter-spacing:1px;">
                    <i class="fas fa-eye" style="color:#9b59b6;"></i> PREVISUALIZACIÓN DEL DISEÑO
                </h3>
                <p style="margin:4px 0 0; color:#aaa; font-size:11px; font-weight:600;">Así quedará tu diseño final impreso</p>
            </div>
            <button onclick="cerrarPreview()" style="background:rgba(255,255,255,0.1); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:0.2s;" onmouseover="this.style.background='rgba(231,76,60,0.7)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Imágenes del diseño: delante y detrás lado a lado para ropa -->
        <div id="preview-both-sides" style="display:none; gap:16px; justify-content:center; align-items:flex-start; flex-wrap:wrap; width:100%;">
            <div style="text-align:center;">
                <p style="margin:0 0 8px; color:#74b9ff; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:1px;"><i class="fas fa-arrow-left"></i> DELANTE</p>
                <div style="background:#fff; border-radius:14px; padding:10px; box-shadow:0 8px 30px rgba(0,0,0,0.4);">
                    <img id="preview-img-front" src="" alt="Delante" style="max-width:min(38vw,240px); max-height:40vh; display:block; border-radius:8px; object-fit:contain;">
                </div>
            </div>
            <div style="text-align:center;">
                <p style="margin:0 0 8px; color:#fd79a8; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:1px;">DETRÁS <i class="fas fa-arrow-right"></i></p>
                <div style="background:#fff; border-radius:14px; padding:10px; box-shadow:0 8px 30px rgba(0,0,0,0.4);">
                    <img id="preview-img-back" src="" alt="Detrás" style="max-width:min(38vw,240px); max-height:40vh; display:block; border-radius:8px; object-fit:contain;">
                </div>
            </div>
        </div>

        <!-- Imagen única (para productos que no son ropa) -->
        <div id="preview-single" style="background:#fff; border-radius:18px; padding:15px; box-shadow:0 10px 40px rgba(0,0,0,0.4);">
            <img id="preview-img" src="" alt="Previsualización del diseño" style="max-width:min(65vw,500px); max-height:45vh; display:block; border-radius:10px; object-fit:contain;">
        </div>

        <!-- Zonas extra: nuca y mangas (solo ropa) -->
        <div id="preview-zonas-extra" style="display:none; width:100%; max-width:620px; box-sizing:border-box;">
            <p style="margin:0 0 12px; color:#aaa; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; text-align:center; border-top:1px solid rgba(255,255,255,0.1); padding-top:14px;"><i class="fas fa-layer-group"></i> Zonas extra (nuca y mangas)</p>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; align-items:flex-start;">
                <div id="preview-zona-nuca" style="display:none; text-align:center; flex:1; min-width:160px; max-width:240px;">
                    <p style="margin:0 0 6px; color:#f1c40f; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">🔝 Nuca / Etiqueta</p>
                    <div style="background:#fff; border-radius:10px; padding:8px; box-shadow:0 4px 14px rgba(0,0,0,0.3);">
                        <img id="preview-img-nuca" src="" style="width:100%; max-width:200px; height:auto; border-radius:6px; display:block; object-fit:contain;">
                    </div>
                </div>
                <div id="preview-zona-manga-izq" style="display:none; text-align:center; flex:1; min-width:130px; max-width:180px;">
                    <p style="margin:0 0 6px; color:#74b9ff; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">👈 Manga Izq.</p>
                    <div style="background:#fff; border-radius:10px; padding:8px; box-shadow:0 4px 14px rgba(0,0,0,0.3);">
                        <img id="preview-img-manga-izq" src="" style="width:100%; max-width:160px; height:auto; border-radius:6px; display:block; object-fit:contain;">
                    </div>
                </div>
                <div id="preview-zona-manga-der" style="display:none; text-align:center; flex:1; min-width:130px; max-width:180px;">
                    <p style="margin:0 0 6px; color:#74b9ff; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">👉 Manga Der.</p>
                    <div style="background:#fff; border-radius:10px; padding:8px; box-shadow:0 4px 14px rgba(0,0,0,0.3);">
                        <img id="preview-img-manga-der" src="" style="width:100%; max-width:160px; height:auto; border-radius:6px; display:block; object-fit:contain;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;">

            <button onclick="cerrarPreview()" style="background:rgba(255,255,255,0.1); color:white; border:1px solid rgba(255,255,255,0.2); padding:12px 22px; border-radius:12px; font-weight:900; font-size:12px; cursor:pointer; text-transform:uppercase; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="fas fa-pencil-alt"></i> SEGUIR EDITANDO
            </button>

            <button onclick="cerrarPreview(); enviarAlCarrito();" style="background:linear-gradient(135deg,#27ae60,#2ecc71); color:white; border:none; padding:12px 28px; border-radius:12px; font-weight:900; font-size:13px; cursor:pointer; text-transform:uppercase; transition:0.2s; box-shadow:0 4px 18px rgba(39,174,96,0.4);" onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='brightness(1)'">
                <i class="fas fa-shopping-basket"></i> AÑADIR AL CARRITO
            </button>
        </div>
    </div>
</div>

<!-- MODAL TALLAS -->
<div id="size-guide-modal" class="custom-modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="custom-modal-box">
        <button class="close-modal-btn" onclick="document.getElementById('size-guide-modal').style.display='none'"><i class="fas fa-times"></i></button>

        <!-- Selector de tipo de producto -->
        <div style="display:flex; gap:8px; margin-bottom:15px;">
            <button onclick="showSizeGuide('camiseta')" id="btn-guide-camiseta" style="flex:1; padding:8px 5px; background:#e74c3c; color:white; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:12px;">👕 Camiseta</button>
            <button onclick="showSizeGuide('sudadera')" id="btn-guide-sudadera" style="flex:1; padding:8px 5px; background:#ecf0f1; color:#666; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:12px;">🧥 Sudadera</button>
        </div>

        <!-- Guía Camiseta -->
        <div id="guide-camiseta-content">
            <h4 style="color:#e74c3c; margin:0 0 5px 0; font-size:14px;">👕 Camiseta Valueweight T</h4>
            <p style="color:#666; font-size:11px; margin:0 0 10px 0;">Ref: 61-036-0 | Fruit of the Loom</p>
            <table class="size-table" style="font-size:11px;">
                <tr><th>Talla</th><th>Ancho</th><th>Largo</th></tr>
                <tr><td>S</td><td>48.5 cm</td><td>69.5 cm</td></tr>
                <tr><td>M</td><td>53.5 cm</td><td>72 cm</td></tr>
                <tr><td>L</td><td>56 cm</td><td>74.5 cm</td></tr>
                <tr><td>XL</td><td>61 cm</td><td>77 cm</td></tr>
                <tr><td>XXL</td><td>66 cm</td><td>78.5 cm</td></tr>
                <tr><td>3XL</td><td>71 cm</td><td>80 cm</td></tr>
                <tr><td>4XL</td><td>76 cm</td><td>81.5 cm</td></tr>
                <tr><td>5XL</td><td>81 cm</td><td>83 cm</td></tr>
            </table>
            <p style="margin:8px 0; font-size:10px; color:#666;">Tolerancia: ±2,5 cm</p>
            <p style="margin:0 0 10px 0; font-size:10px; color:#e74c3c;">4XL-5XL solo Blanco, Negro, Marino, Gris</p>

            <!-- Características Técnicas -->
            <div style="margin-top:12px; border-top:1px solid #eee; padding-top:10px;">
                <p style="margin:0 0 8px 0; font-size:11px; font-weight:700; color:#e74c3c;">📋 Características</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; font-size:9px; color:#555;">
                    <div>100% Algodón*</div>
                    <div>160 g/m² (Blanco)</div>
                    <div>165 g/m² (Colores)</div>
                    <div>Cuello Lycra®</div>
                    <div>Corte Unisex</div>
                    <div>Disponible M/Niña</div>
                    <div>Cinta cuello</div>
                    <div>Punto fino</div>
                </div>
                <p style="margin:4px 0 0 0; font-size:8px; color:#888;">*Ceniza 99% | Gris Jaspeado 97% | HD/VF/R6/RX/VH/HP/H1: 50%</p>
            </div>

            <!-- Instrucciones cuidado -->
            <div style="margin-top:10px; padding:8px; background:#fff3cd; border-radius:4px; border-left:3px solid #ffc107;">
                <strong style="color:#856404; font-size:10px; display:block; margin-bottom:8px;">Cuidado:</strong>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-start;">
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><rect x="3" y="7" width="34" height="26" rx="3" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="24" text-anchor="middle" font-size="12" font-weight="700" fill="#856404" font-family="sans-serif">40°</text></svg>
                        <div>40°C</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><polygon points="20,4 36,33 4,33" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="27" text-anchor="middle" font-size="13" font-weight="700" fill="#856404" font-family="sans-serif">X</text><line x1="5" y1="5" x2="35" y2="35" stroke="#856404" stroke-width="2"/></svg>
                        <div>No lejía</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="15" stroke="#856404" stroke-width="2" fill="none"/><circle cx="20" cy="20" r="9" stroke="#856404" stroke-width="1.2" fill="none"/></svg>
                        <div>Sí secadora</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><path d="M6 26 Q6 18 20 18 L34 18 L34 26 Z" stroke="#856404" stroke-width="2" fill="none"/><rect x="17" y="14" width="6" height="5" rx="1" stroke="#856404" stroke-width="1.2" fill="none"/></svg>
                        <div>Se puede planchar</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="15" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="25" text-anchor="middle" font-size="13" font-weight="700" fill="#856404" font-family="sans-serif">P</text><line x1="5" y1="5" x2="35" y2="35" stroke="#856404" stroke-width="2"/></svg>
                        <div>No en seco</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guía Sudadera -->
        <div id="guide-sudadera-content" style="display:none;">
            <h4 style="color:#8e44ad; margin:0 0 5px 0; font-size:14px;">🧥 Sudadera Clásica Capucha</h4>
            <p style="color:#666; font-size:11px; margin:0 0 10px 0;">Ref: 62-208-0 | Fruit of the Loom</p>
            <table class="size-table" style="font-size:11px;">
                <tr><th>Talla</th><th>Ancho</th><th>Largo</th></tr>
                <tr><td>S</td><td>51 cm</td><td>67 cm</td></tr>
                <tr><td>M</td><td>56 cm</td><td>70 cm</td></tr>
                <tr><td>L</td><td>61 cm</td><td>73 cm</td></tr>
                <tr><td>XL</td><td>63.5 cm</td><td>76 cm</td></tr>
                <tr><td>XXL</td><td>68.5 cm</td><td>79 cm</td></tr>
            </table>
            <p style="margin:8px 0 0 0; font-size:10px; color:#666;">Tolerancia: ±2,5 cm</p>

            <!-- Características Técnicas -->
            <div style="margin-top:12px; border-top:1px solid #eee; padding-top:10px;">
                <p style="margin:0 0 8px 0; font-size:11px; font-weight:700; color:#8e44ad;">📋 Características</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; font-size:9px; color:#555;">
                    <div>80% Algodón*</div>
                    <div>260 g/m² (Blanco/Gris)</div>
                    <div>280 g/m² (Colores)</div>
                    <div>Capucha doble</div>
                    <div>Cordón plano</div>
                    <div>Bolsillo canguro</div>
                    <div>Puños Lycra®</div>
                    <div>Disponible M/Niño</div>
                </div>
                <p style="margin:4px 0 0 0; font-size:8px; color:#888;">*HD/R6/RX/VF/VH: 60% Algodón</p>
            </div>

            <!-- Instrucciones cuidado -->
            <div style="margin-top:10px; padding:8px; background:#fff3cd; border-radius:4px; border-left:3px solid #ffc107;">
                <strong style="color:#856404; font-size:10px; display:block; margin-bottom:8px;">Cuidado:</strong>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-start;">
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><rect x="3" y="7" width="34" height="26" rx="3" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="24" text-anchor="middle" font-size="12" font-weight="700" fill="#856404" font-family="sans-serif">40°</text></svg>
                        <div>40°C</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><polygon points="20,4 36,33 4,33" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="27" text-anchor="middle" font-size="13" font-weight="700" fill="#856404" font-family="sans-serif">X</text><line x1="5" y1="5" x2="35" y2="35" stroke="#856404" stroke-width="2"/></svg>
                        <div>No lejía</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="15" stroke="#856404" stroke-width="2" fill="none"/><circle cx="20" cy="20" r="9" stroke="#856404" stroke-width="1.2" fill="none"/><line x1="5" y1="5" x2="35" y2="35" stroke="#856404" stroke-width="2"/></svg>
                        <div>No secadora</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><path d="M6 26 Q6 18 20 18 L34 18 L34 26 Z" stroke="#856404" stroke-width="2" fill="none"/><rect x="17" y="14" width="6" height="5" rx="1" stroke="#856404" stroke-width="1.2" fill="none"/></svg>
                        <div>Se puede planchar</div>
                    </div>
                    <div style="text-align:center; font-size:9px; color:#856404; width:38px;">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="15" stroke="#856404" stroke-width="2" fill="none"/><text x="20" y="25" text-anchor="middle" font-size="13" font-weight="700" fill="#856404" font-family="sans-serif">P</text><line x1="5" y1="5" x2="35" y2="35" stroke="#856404" stroke-width="2"/></svg>
                        <div>No en seco</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSizeGuide(type) {
    document.getElementById('guide-camiseta-content').style.display = type === 'camiseta' ? 'block' : 'none';
    document.getElementById('guide-sudadera-content').style.display = type === 'sudadera' ? 'block' : 'none';
    document.getElementById('btn-guide-camiseta').style.background = type === 'camiseta' ? '#e74c3c' : '#ecf0f1';
    document.getElementById('btn-guide-camiseta').style.color = type === 'camiseta' ? 'white' : '#666';
    document.getElementById('btn-guide-sudadera').style.background = type === 'sudadera' ? '#8e44ad' : '#ecf0f1';
    document.getElementById('btn-guide-sudadera').style.color = type === 'sudadera' ? 'white' : '#666';
}
</script>

<script>
const csrfToken = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";
const recursoAutoParaCargar = <?php echo json_encode($recurso_auto_load); ?>;
const editDiseno = <?php echo $edit_diseno ? json_encode($edit_diseno) : 'null'; ?>;

const canvas = new fabric.Canvas('tshirt-canvas', { preserveObjectStacking: true, backgroundColor: null });

// ─── AVISO AL ABANDONAR PÁGINA SIN GUARDAR ──────────────────────────────
let hayCambiosSinGuardar = false;

// Activar aviso cuando haya cambios en el canvas
canvas.on('object:added', function(e) {
    // No marcar cambios si lo que se añade es el límite de impresión (rectángulo guía)
    if (e.target && e.target === printBoundary) return;
    // Tampoco marcar si el canvas solo tiene el printBoundary (lienzo vacío)
    const objetos = canvas.getObjects().filter(function(o){ return o !== printBoundary; });
    if (objetos.length === 0) return;
    hayCambiosSinGuardar = true;
});
canvas.on('object:removed', function(e) {
    // Si tras eliminar no quedan objetos (salvo printBoundary), limpiar el flag
    const objetos = canvas.getObjects().filter(function(o){ return o !== printBoundary; });
    if (objetos.length === 0) { hayCambiosSinGuardar = false; return; }
    hayCambiosSinGuardar = true;
});
canvas.on('object:modified', function() { hayCambiosSinGuardar = true; });
canvas.on('background:changed', function() { hayCambiosSinGuardar = true; });

// Avisar al intentar abandonar
window.addEventListener('beforeunload', function(e) {
    if (hayCambiosSinGuardar) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro? Tienes cambios sin guardar en tu diseño.';
        return '¿Estás seguro? Tienes cambios sin guardar en tu diseño.';
    }
});

//Quitar aviso al guardar
function marcarGuardado() {
    hayCambiosSinGuardar = false;
}

// ─── AUTO-GUARDADO EN LOCALSTORAGE (cada 30s si hay cambios) ───────────
let autoSaveTimer = null;
function autoSaveLocal() {
    if (!hayCambiosSinGuardar || typeof canvas.toJSON !== 'function') return;
    try {
        if (printBoundary) canvas.remove(printBoundary);
        const json = JSON.stringify(canvas.toJSON(['selectable','evented','id']));
        if (printBoundary) canvas.add(printBoundary);
        const tipo = document.getElementById('product-type')?.value || 'camiseta';
        localStorage.setItem('camiglobo_autosave_' + tipo, json);
        localStorage.setItem('camiglobo_autosave_time', Date.now());
    } catch (e) { console.warn('Auto-save local falló:', e); }
}
function autoSaveInit() {
    if (autoSaveTimer) clearInterval(autoSaveTimer);
    autoSaveTimer = setInterval(autoSaveLocal, 30000);
}
autoSaveInit();

// ─── FAB MENÚ MÓVIL ───────────────────────────────────────────
(function() {
    const fab = document.getElementById('fab-menu');
    const fabToggle = document.getElementById('fab-toggle');
    if (!fab || !fabToggle) return;
    fabToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        fab.classList.toggle('open');
    });
    document.addEventListener('click', () => fab.classList.remove('open'));
    fab.addEventListener('click', (e) => e.stopPropagation());
    // Icono animado
    let fabOpen = false;
    fabToggle.addEventListener('click', () => {
        fabOpen = !fabOpen;
        fabToggle.querySelector('i').className = fabOpen ? 'fas fa-times' : 'fas fa-plus';
    });
})();

// ─── PANELES COLAPSABLES EN MÓVIL ─────────────────────────────
(function() {
    if (window.innerWidth > 640) return;
    document.querySelectorAll('.tool-card').forEach(card => {
        const header = document.createElement('div');
        header.className = 'tool-card-header';
        const title = card.querySelector('.card-title, .card-fotos, .card-texto, .card-plantillas, .card-compra, .card-extras');
        if (title) {
            header.innerHTML = title.innerHTML;
            title.innerHTML = '';
            title.appendChild(header);
        }
        card.classList.add('collapsed');
        header.addEventListener('click', () => {
            card.classList.toggle('collapsed');
        });
    });
})();

// Cargar auto-saved al inicio si existe
(function() {
    try {
        const tipo = document.getElementById('product-type')?.value || 'camiseta';
        const saved = localStorage.getItem('camiglobo_autosave_' + tipo);
        if (saved && canvas) {
            const tiempo = parseInt(localStorage.getItem('camiglobo_autosave_time') || '0');
            const haceMinutos = tiempo ? (Date.now() - tiempo) / 60000 : 0;
            if (haceMinutos < 60) {
                const jsonParsed = JSON.parse(saved);
                // Filtrar printBoundary guardado por error en auto-saves antiguos
                if (jsonParsed.objects) {
                    jsonParsed.objects = jsonParsed.objects.filter(o => o.id !== '__printBoundary__');
                }
                canvas.loadFromJSON(jsonParsed, () => {
                    // Recrear la zona de impresión
                    if (printBoundary) canvas.remove(printBoundary);
                    const info = productos[document.getElementById('product-type').value];
                    const _rawBg = info ? info.colores[colorActual] : null;
                    const bgHex = (_rawBg !== undefined && _rawBg !== null) ? _rawBg : '#ffffff';
                    const r = parseInt(bgHex.slice(1,3)||'ff',16), g = parseInt(bgHex.slice(3,5)||'ff',16), b = parseInt(bgHex.slice(5,7)||'ff',16);
                    const lum = (0.299*r + 0.587*g + 0.114*b) / 255;
                    const bc = lum > 0.6 ? 'rgba(0,0,0,0.55)' : 'rgba(255,255,255,0.7)';
                    printBoundary = new fabric.Rect({
                        width:220, height:290, fill:'transparent',
                        stroke:bc, strokeDashArray:[5,5], strokeWidth:2,
                        selectable:false, evented:false, opacity:1,
                        originX:'center', originY:'center', left:160, top:190,
                        id:'__printBoundary__'
                    });
                    canvas.add(printBoundary);
                    canvas.renderAll();
                    hayCambiosSinGuardar = false;
                    aviso('Diseño anterior restaurado desde auto-guardado', 'info');
                });
            }
        }
    } catch(e) {}
})();

// ─── SISTEMA DE AVISOS ────────────────────────────────────────
function aviso(msg, tipo = 'info') {
    const bg = { success:'#27ae60', error:'#e74c3c', info:'#2c3e50', warning:'#e67e22' }[tipo] || '#2c3e50';
    const ic = { success:'✅', error:'❌', info:'💡', warning:'⚠️' }[tipo] || '💡';
    let wrap = document.getElementById('_avisos_wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = '_avisos_wrap';
        wrap.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:2147483647;display:flex;flex-direction:column;align-items:center;gap:8px;pointer-events:none;';
        document.body.appendChild(wrap);
    }
    const el = document.createElement('div');
    el.style.cssText = 'display:flex;align-items:center;gap:10px;padding:11px 20px;border-radius:50px;font-family:inherit;font-size:12.5px;font-weight:700;color:#fff;background:' + bg + ';box-shadow:0 4px 18px rgba(0,0,0,.3);white-space:nowrap;opacity:0;transform:translateY(10px);transition:opacity .25s,transform .25s;';
    el.innerHTML = '<span>' + ic + '</span><span>' + msg + '</span>';
    wrap.appendChild(el);
    requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; });
    setTimeout(() => {
        el.style.opacity = '0'; el.style.transform = 'translateY(10px)';
        setTimeout(() => el.remove(), 280);
    }, 2000);
}



// ─── BADGE "EDITANDO AQUÍ" ────────────────────────────────────
// Usamos los labels que ya existen FUERA de los contenedores con overflow:hidden
// para evitar que se recorten. El canvas-wrapper y mini-canvas-inner tienen overflow:hidden.
function actualizarBadgeActivo(fc) {
    // Reset todos los indicadores
    const mainTools = document.querySelector('.main-canvas-tools');
    if (mainTools) {
        mainTools.classList.remove('zone-active-bar');
        // El label es hermano de mainTools (insertado antes), buscamos en el padre
        mainTools.parentNode.querySelectorAll('.zone-active-label').forEach(el => el.remove());
    }
    document.querySelectorAll('.mini-canvas-label').forEach(lbl => {
        lbl.classList.remove('mini-label-active');
        const prev = lbl.querySelector('.zone-badge');
        if (prev) prev.remove();
    });

    if (fc === canvas) {
        // Lienzo principal: añadir indicador encima de los botones de acción
        if (mainTools) {
            mainTools.classList.add('zone-active-bar');
            const lbl = document.createElement('div');
            lbl.className = 'zone-active-label';
            const lado = (typeof vistaActual !== 'undefined' && vistaActual === 'back') ? '🔙 Detrás' : '🔜 Delante';
            lbl.innerHTML = '✏️ Editando: <strong>' + lado + '</strong>';
            mainTools.parentNode.insertBefore(lbl, mainTools);
        }
    } else {
        MINI_IDS.forEach(id => {
            if (miniCanvases[id] === fc) {
                const block = document.getElementById('mini-block-' + id);
                const lbl = block ? block.querySelector('.mini-canvas-label') : null;
                // Ocultar el hint "toca para editar"
                const hint = block ? block.querySelector('.mini-tap-hint') : null;
                if (hint) hint.style.display = 'none';
                if (lbl) {
                    lbl.classList.add('mini-label-active');
                    const badge = document.createElement('span');
                    badge.className = 'zone-badge';
                    badge.textContent = '✏️ ACTIVO';
                    lbl.appendChild(badge);
                }
            }
        });
    }
}

// ─── CANVAS ACTIVO (principal o mini) ────────────────────────
let activeCanvas = canvas;

function mostrarAvisoZona(innerEl) {
    // Ocultar el hint al activar la zona
    const hint = innerEl.querySelector('.mini-tap-hint');
    if (hint) hint.style.display = 'none';
}

function setActiveCanvas(fc, scrollToText = false) {
    activeCanvas = fc;
    actualizarBtnVerLienzo();
    document.querySelectorAll('.mini-canvas-block').forEach(b => b.classList.remove('mini-active'));
    document.getElementById('product-container').classList.remove('mini-active-main');
    if (fc === canvas) {
        document.getElementById('product-container').classList.add('mini-active-main');
    } else {
        MINI_IDS.forEach(id => {
            if (miniCanvases[id] === fc) {
                document.getElementById('mini-block-' + id).classList.add('mini-active');
            }
        });
    }
    actualizarBadgeActivo(fc);
    // scrollToText funciona para cualquier lienzo (principal o mini)
    if (scrollToText) {
        const panelTexto = document.querySelector('.tool-card[style*="border-left:6px solid #e74c3c"]');
        if (panelTexto) {
            panelTexto.scrollIntoView({ behavior: 'smooth', block: 'start' });
            panelTexto.classList.add('panel-flash');
            setTimeout(() => panelTexto.classList.remove('panel-flash'), 1800);
        }
        const area = document.getElementById('text-input-area');
        const icon = document.getElementById('icon-toggle-text');
        if (area && (area.style.display === 'none' || area.style.display === '')) {
            area.style.display = 'flex';
            if (icon) icon.className = 'fas fa-minus';
            const input = document.getElementById('texto-nuevo');
            if (input) setTimeout(() => input.focus(), 500);
        }
        aviso('Escribe tu texto abajo y pulsa OK', 'info');
    }
}


// ─── BOTÓN FLOTANTE: SCROLL AL LIENZO ────────────────────────
function scrollAlLienzo() {
    let target = null;

    if (activeCanvas === canvas) {
        target = document.querySelector('.canvas-panel');
    } else {
        MINI_IDS.forEach(id => {
            if (miniCanvases[id] === activeCanvas) {
                target = document.getElementById('mini-block-' + id);
            }
        });
    }

    if (!target) target = document.querySelector('.canvas-panel');
    const y = target.getBoundingClientRect().top + window.pageYOffset - 80;
    window.scrollTo({ top: y, behavior: 'smooth' });
}

function actualizarBtnVerLienzo() {
    const btn = document.getElementById('btn-ver-lienzo');
    if (!btn) return;
    const span = btn.querySelector('span');
    if (!span) return;

    const nombres = { 'nuca': 'Nuca', 'manga-izq': 'Manga Izq', 'manga-der': 'Manga Der' };
    let label = '✏️ Editando: Delante';

    if (activeCanvas === canvas) {
        label = '✏️ Editando: ' + (vistaActual === 'back' ? 'Detrás' : 'Delante');
    } else {
        MINI_IDS.forEach(id => {
            if (miniCanvases[id] === activeCanvas) label = '✏️ Editando: ' + (nombres[id] || id);
        });
    }
    span.textContent = label;
}

function pulseEditando() {
    const btn = document.getElementById('btn-ver-lienzo');
    if (!btn) return;
    btn.classList.remove('pulse');
    void btn.offsetWidth;
    btn.classList.add('pulse');
    setTimeout(() => btn.classList.remove('pulse'), 2000);
}

function nudgeSlider(id, delta, callback) {
    const el = document.getElementById(id);
    if (!el) return;
    const min = parseFloat(el.min), max = parseFloat(el.max);
    let val = parseFloat(el.value) + delta;
    val = Math.round(val * 100) / 100;
    if (val < min) val = min;
    if (val > max) val = max;
    el.value = val;
    callback(val);
}

function scaleSelected(factor) {
    const obj = (activeCanvas || canvas).getActiveObject();
    if (!obj) { aviso('Selecciona primero un elemento en el lienzo', 'warning'); return; }
    const s = obj.scaleX * factor;
    if (s < 0.05 || s > 10) return;
    obj.set({ scaleX: s, scaleY: s });
    (activeCanvas || canvas).requestRenderAll();
}

// ─── GUÍA PLEGABLE ────────────────────────────────────────────
function toggleGuia() {
    const contenido = document.getElementById('guia-contenido');
    const icon = document.getElementById('guia-toggle-icon');
    if (!contenido) return;
    const oculto = contenido.style.display === 'none';
    contenido.style.display = oculto ? '' : 'none';
    if (icon) icon.querySelector('i').className = oculto ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
    try { localStorage.setItem('camiglobo_guia_vista', '1'); } catch(e) {}
}

// La guía está colapsada por defecto, se expande automáticamente solo la primera vez
(function() {
    var c = document.getElementById('guia-contenido');
    var i = document.getElementById('guia-toggle-icon');
    var yaVista = false;
    try { yaVista = !!localStorage.getItem('camiglobo_guia_vista'); } catch(e) {}
    if (!yaVista) {
        //Primera vez: mostrar guía automáticamente
        if (c) c.style.display = '';
        if (i) i.querySelector('i').className = 'fas fa-chevron-up';
    }
    // Ya vista: mantener colapsada (display:none ya aplicado en HTML)
})();

// (reordenamiento móvil se hace por CSS)

// ─── DOBLE-TAP EN MÓVIL → simula mousedblclick en Fabric ─────
(function() {
    var lastTapTime = 0;
    var lastTapTarget = null;
    document.addEventListener('touchend', function(e) {
        var now = Date.now();
        var el = e.target;
        // ¿Está dentro de algún canvas Fabric conocido?
        var allCanvasEls = [document.getElementById('tshirt-canvas')];
        // añadir mini canvas elements
        Object.values(miniCanvases || {}).forEach(function(mc) {
            if (mc && mc.lowerCanvasEl) allCanvasEls.push(mc.lowerCanvasEl);
        });
        var enCanvas = allCanvasEls.some(function(ce) { return ce && ce.contains(el); });
        if (!enCanvas) { lastTapTime = 0; return; }

        if (now - lastTapTime < 320 && lastTapTarget === el) {
            e.preventDefault();
            var touch = e.changedTouches[0];
            // Determinar qué canvas es
            var targetCanvas = canvas;
            Object.values(miniCanvases || {}).forEach(function(mc) {
                if (mc && mc.lowerCanvasEl && mc.lowerCanvasEl.contains(el)) targetCanvas = mc;
            });
            var zoom = targetCanvas.getZoom();
            var rect = el.getBoundingClientRect();
            var x = (touch.clientX - rect.left) / zoom;
            var y = (touch.clientY - rect.top)  / zoom;
            var obj = targetCanvas.findTarget({ clientX: touch.clientX - rect.left, clientY: touch.clientY - rect.top });
            if (obj) {
                obj.fire('mousedblclick', { e: e, pointer: { x: x, y: y } });
            }
            lastTapTime = 0;
        } else {
            lastTapTime = now;
            lastTapTarget = el;
        }
    }, { passive: false });
})();

// ─── PINCH-TO-ZOOM EN MÓVIL ────────────────────────────────────
(function() {
    if (!canvas) return;
    let initialDistance = 0;
    let initialZoom = 1;
    const canvasEl = document.getElementById('tshirt-canvas');
    if (!canvasEl) return;
    canvasEl.addEventListener('touchstart', function(e) {
        if (e.touches.length === 2) {
            initialDistance = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            initialZoom = canvas.getZoom();
        }
    }, { passive: true });
    canvasEl.addEventListener('touchmove', function(e) {
        if (e.touches.length === 2) {
            e.preventDefault();
            const currentDistance = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            if (initialDistance > 0) {
                const newZoom = initialZoom * (currentDistance / initialDistance);
                canvas.setZoom(Math.min(Math.max(newZoom, 0.5), 4));
            }
        }
    }, { passive: false });
    canvasEl.addEventListener('touchend', function(e) {
        initialDistance = 0;
    }, { passive: true });
})();


// Cuando el usuario hace doble clic en un hueco de foto de plantilla,
// guardamos qué slot y lienzo están esperando la foto.
let _activePhotoSlot   = null;   // fabric.Rect (el hueco)
let _activePhotoCanvas = null;   // fabric.Canvas donde está
let _activePhotoEmoji  = null;   // fabric.IText emoji 📷 asociado (para borrarlo)

function _scrollToFotosBlink(slotRect, fc, emojiObj) {
    // Marcar el slot activo
    _activePhotoSlot   = slotRect  || null;
    _activePhotoCanvas = fc        || canvas;
    _activePhotoEmoji  = emojiObj  || null;

    // Mostrar el área por si estuviera oculta
    const area = document.getElementById('biblioteca-area');
    if (area) area.style.display = 'block';

    // Scroll hasta la sección de fotos subidas
    const tarjeta = document.querySelector('.card-fotos');
    if (tarjeta) {
        const y = tarjeta.getBoundingClientRect().top + window.pageYOffset - 80;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }

    // Parpadeo en la lista de recursos
    setTimeout(() => {
        const lista = document.getElementById('lista-recursos');
        if (lista) {
            lista.classList.add('biblioteca-flash');
            setTimeout(() => lista.classList.remove('biblioteca-flash'), 1800);
        }
        // También parpadear el botón de subir si no hay fotos aún
        const uploadLabel = document.querySelector('.btn-upload-label');
        if (uploadLabel) {
            uploadLabel.classList.add('biblioteca-flash');
            setTimeout(() => uploadLabel.classList.remove('biblioteca-flash'), 1800);
        }
    }, 400);
}


function scrollToBiblioteca() {
    // Mostrar el área por si estuviera oculta
    const area = document.getElementById('biblioteca-area');
    if (area) area.style.display = 'block';

    // Scroll hasta la tarjeta de "Sube tu Imagen"
    const tarjeta = document.querySelector('#biblioteca-area');
    if (tarjeta) {
        tarjeta.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Parpadeo para indicar visualmente dónde mirar
        tarjeta.classList.add('biblioteca-flash');
        setTimeout(() => { tarjeta.classList.remove('biblioteca-flash'); }, 1800);
    }
}

// ─── MINI LIENZOS (Nuca + Mangas) ────────────────────────────
const miniCanvases = {};
const MINI_IDS = ['nuca', 'manga-izq', 'manga-der'];

function initMiniCanvases() {
    MINI_IDS.forEach(id => {
        const el = document.getElementById('canvas-' + id);
        if (!el) return;
        const w = parseInt(el.getAttribute('width'))  || 140;
        const h = parseInt(el.getAttribute('height')) || 100;
        const fc = new fabric.Canvas('canvas-' + id, {
            preserveObjectStacking: true,
            backgroundColor: '', // Transparente por defecto
            width:  w,
            height: h
        });
        miniCanvases[id] = fc;
        fc.on('mouse:down', () => setActiveCanvas(fc, false));
        fc.on('object:added', function(e) {
            syncMiniPrice();
            // Usar control interior para que no se recorte en el mini lienzo
            if (e.target && e.target.selectable !== false && window._ctxBtnControlMini) {
                e.target.controls = Object.assign({}, e.target.controls, { ctxBtn: window._ctxBtnControlMini });
                fc.requestRenderAll();
            }
        });
        fc.on('object:removed', () => syncMiniPrice());
        
        // NUEVO: Que las mangas/nuca informen a los sliders de escala y rotación
        fc.on('selection:created', function(e) { syncSlider(); });
        fc.on('selection:updated', function(e) { syncSlider(); ocultarCtxMenu(); });
        fc.on('object:scaling',    syncSlider);
        fc.on('object:rotating',   syncSlider);
        fc.on('object:moving',     ocultarCtxMenu);
        fc.on('selection:cleared', () => {
            document.getElementById('image-scale-slider').value = 1;
            document.getElementById('scale-val').innerText = '100%';
            document.getElementById('image-rotation-slider').value = 0;
            document.getElementById('rotation-val').innerText = '0°';
        });

        fc.renderAll();
    });
}

function syncMiniPrice() { calcularPrecio(); }

// ─── MODAL COLOR FONDO MINI LIENZOS ──────────────────────────
const MINI_BG_COLORS = [
    { nombre: 'Blanco',       hex: '#ffffff' },
    { nombre: 'Blanco roto',  hex: '#f5f0e8' },
    { nombre: 'Crema',        hex: '#fff8dc' },
    { nombre: 'Beige',        hex: '#f5e6c8' },
    { nombre: 'Arena',        hex: '#e8d5b0' },
    { nombre: 'Gris claro',   hex: '#e0e0e0' },
    { nombre: 'Gris',         hex: '#bdc3c7' },
    { nombre: 'Gris oscuro',  hex: '#4a4a4a' },
    { nombre: 'Negro',        hex: '#1a1a1a' },
    { nombre: 'Rojo',         hex: '#e74c3c' },
    { nombre: 'Burdeos',      hex: '#7b241c' },
    { nombre: 'Rosa',         hex: '#fd79a8' },
    { nombre: 'Rosa claro',   hex: '#ffd6e0' },
    { nombre: 'Naranja',      hex: '#e67e22' },
    { nombre: 'Amarillo',     hex: '#f1c40f' },
    { nombre: 'Dorado',       hex: '#d4ac0d' },
    { nombre: 'Verde menta',  hex: '#a8e6cf' },
    { nombre: 'Verde',        hex: '#2ecc71' },
    { nombre: 'Verde oscuro', hex: '#1e5631' },
    { nombre: 'Azul cielo',   hex: '#87ceeb' },
    { nombre: 'Azul',         hex: '#3498db' },
    { nombre: 'Azul marino',  hex: '#1a3a5c' },
    { nombre: 'Morado claro', hex: '#d7bde2' },
    { nombre: 'Morado',       hex: '#9b59b6' },
    { nombre: 'Marrón claro', hex: '#d4a574' },
    { nombre: 'Marrón',       hex: '#795548' },
    { nombre: 'Turquesa',     hex: '#1abc9c' },
    { nombre: 'Coral',        hex: '#ff7f7f' },
    { nombre: 'Lavanda',      hex: '#e6e6fa' },
    { nombre: 'Transparente / Sin fondo', hex: 'transparent' },
];

const MINI_ZONA_LABEL = { 'nuca': 'Nuca / Etiqueta', 'manga-izq': 'Manga Izquierda', 'manga-der': 'Manga Derecha' };
let miniColorIdActual = null;

function abrirModalMiniColor(id) {
    miniColorIdActual = id;
    const modal = document.getElementById('modal-mini-color');
    const grid  = document.getElementById('modal-mini-color-grid');
    const zona  = document.getElementById('modal-mini-color-zona');
    zona.textContent = 'Zona: ' + (MINI_ZONA_LABEL[id] || id);
    grid.innerHTML = '';
    const fc = miniCanvases[id];
    const bgActual = fc ? (fc.backgroundColor || '#ffffff') : '#ffffff';
    MINI_BG_COLORS.forEach(c => {
        const dot = document.createElement('div');
        dot.title = c.nombre;
        dot.style.cssText = `
            width:100%; aspect-ratio:1; border-radius:50%; cursor:pointer;
            background:${c.hex === 'transparent' ? 'repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 0 0 / 10px 10px' : c.hex};
            border: 3px solid ${bgActual === c.hex ? '#e74c3c' : (c.hex === '#ffffff' ? '#ddd' : 'transparent')};
            transition: transform 0.15s, border-color 0.15s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        `;
        dot.onmouseover = () => { dot.style.transform = 'scale(1.2)'; };
        dot.onmouseout  = () => { dot.style.transform = bgActual === c.hex ? 'scale(1.1)' : 'scale(1)'; };
        dot.onclick = () => {
            setMiniCanvasBg(id, c.hex);
            // actualizar borde activo
            grid.querySelectorAll('div').forEach(d => d.style.borderColor = 'transparent');
            dot.style.borderColor = '#e74c3c';
            dot.style.transform = 'scale(1.1)';
            // actualizar fondo del botón
            const btn = document.getElementById('mini-color-btn-' + id);
            if (btn) btn.style.background = c.hex === 'transparent' ? '#8e44ad' : c.hex;
        };
        grid.appendChild(dot);
    });
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalMiniColor() {
    document.getElementById('modal-mini-color').style.display = 'none';
    document.body.style.overflow = '';
    miniColorIdActual = null;
}

function setMiniCanvasBg(id, color) {
    const fc = miniCanvases[id];
    if (!fc) return;
    fc.backgroundColor = color === 'transparent' ? '' : color;
    fc.renderAll();
}

function abrirModalColorFondo() {
    // Reutiliza el modal de mini lienzos para el canvas activo
    const modal = document.getElementById('modal-mini-color');
    const grid  = document.getElementById('modal-mini-color-grid');
    const zona  = document.getElementById('modal-mini-color-zona');
    const fc    = activeCanvas;

    // Etiqueta según el lienzo activo
    let zonaLabel = 'Lienzo principal';
    MINI_IDS.forEach(id => {
        if (miniCanvases[id] === fc) zonaLabel = MINI_ZONA_LABEL[id] || id;
    });
    zona.textContent = 'Zona: ' + zonaLabel;

    miniColorIdActual = '__main__';
    grid.innerHTML = '';
    const bgActual = fc.backgroundColor || '';
    MINI_BG_COLORS.forEach(c => {
        const dot = document.createElement('div');
        dot.title = c.nombre;
        dot.style.cssText = `
            width:100%; aspect-ratio:1; border-radius:50%; cursor:pointer;
            background:${c.hex === 'transparent' ? 'repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 0 0 / 10px 10px' : c.hex};
            border: 3px solid ${bgActual === c.hex ? '#e74c3c' : (c.hex === '#ffffff' ? '#ddd' : 'transparent')};
            transition: transform 0.15s, border-color 0.15s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        `;
        dot.onmouseover = () => { dot.style.transform = 'scale(1.2)'; };
        dot.onmouseout  = () => { dot.style.transform = bgActual === c.hex ? 'scale(1.1)' : 'scale(1)'; };
        dot.onclick = () => {
            fc.backgroundColor = c.hex === 'transparent' ? '' : c.hex;
            fc.renderAll();
            // Si es el lienzo principal, actualizar colorActual también
            if (fc === canvas) {
                const tipo = document.getElementById('product-type').value;
                const info = productos[tipo];
                if (info && info.colores[c.nombre] !== undefined) colorActual = c.nombre;
            }
            grid.querySelectorAll('div').forEach(d => d.style.borderColor = 'transparent');
            dot.style.borderColor = '#e74c3c';
            dot.style.transform = 'scale(1.1)';
        };
        grid.appendChild(dot);
    });
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function updateMiniCanvasBg() {
}

async function deleteMiniSelected(id) {
    const fc = miniCanvases[id];
    if (!fc) return;
    
    const obj = fc.getActiveObject();
    if (obj) {
        // Si hay un texto o foto seleccionado, lo borra y actualiza la pantalla
        fc.remove(obj);
        fc.discardActiveObject();
        fc.renderAll();
    } else {
        // Si no hay nada seleccionado, pregunta si vaciamos toda la zona
        if (await cgConfirm({ titulo: 'Vaciar zona', mensaje: '¿Quieres eliminar todo el contenido de esta zona del diseño?', confirmar: 'Sí, vaciar', tipo: 'trash' })) {
            fc.clear();
            fc.backgroundColor = ''; // Mantenemos la transparencia para no gastar tinta
            fc.renderAll();
            syncMiniPrice(); // Como se queda vacía, quitamos los 3€ del total
        }
    }
}

function addImageToMini(id, event) {
    const file = event.target.files[0];
    if (!file) return;
    const fc = miniCanvases[id];
    if (!fc) return;
    const reader = new FileReader();
    reader.onload = f => {
        const dataUrl = f.target.result;
        fabric.Image.fromURL(dataUrl, img => {
            const maxW = fc.width  * 0.85;
            const maxH = fc.height * 0.85;
            const scale = Math.min(maxW / img.width, maxH / img.height, 1);
            img.set({
                left:    fc.width  / 2,
                top:     fc.height / 2,
                originX: 'center',
                originY: 'center',
                scaleX:  scale,
                scaleY:  scale
            });
            fc.add(img);
            fc.setActiveObject(img);
            fc.requestRenderAll();
            pulseEditando();
            aviso('Foto añadida a la zona — arrastra para ajustar', 'success');
        }, { crossOrigin: 'anonymous' });
    };
    reader.readAsDataURL(file);
    subirRecursoServidor(file);   // ← NUEVO: guarda en biblioteca del usuario
    event.target.value = '';
}

function getMiniCanvasData() {
    const promises = [];
    MINI_IDS.forEach(id => {
        const fc = miniCanvases[id];
        if (!fc) return;
        const userObjs = fc.getObjects().filter(o => o.selectable !== false);
        if (userObjs.length === 0) return;

        let multi = 1;
        if (id === 'nuca')                                 multi = 3;
        else if (id === 'manga-izq' || id === 'manga-der') multi = 7;

        const p = new Promise(function(resolve) {
            try {
                const offscreen = new fabric.StaticCanvas(document.createElement('canvas'));
                offscreen.setWidth(fc.width);
                offscreen.setHeight(fc.height);

                const jsonFC = fc.toJSON(['selectable','evented','id']);
                const jsonFiltrado = Object.assign({}, jsonFC, {
                    objects: jsonFC.objects ? jsonFC.objects.filter(function(o){ return o.selectable !== false; }) : [],
                    background: fc.backgroundColor || 'transparent'
                });

                offscreen.loadFromJSON(jsonFiltrado, function() {
                    offscreen.renderAll();
                    const dataUrl = offscreen.toDataURL({ format: 'png', multiplier: multi });
                    offscreen.dispose();
                    resolve({ id: id, dataUrl: dataUrl });
                });
            } catch(e) {
                console.warn('Error exportando mini ' + id, e);
                resolve(null);
            }
        });
        promises.push(p);
    });
    return Promise.all(promises);
}

let colorActual          = 'Transparente';
let colorProductoActual  = 'Negro';
let printBoundary  = null;
let productos      = {};
let memoriaDisenos = { front: null, back: null };
let vistaActual    = 'front';
let previewVisto   = false;

// ─── HISTORIAL DESHACER / REHACER ────────────────────────────
const HIST_MAX = 30;
let historial      = [];
let historialIndex = -1;
let historialPaused = false;

function guardarEstado() {
    if (historialPaused) return;
    const json = canvas.toJSON(['selectable','evented','id']);
    if (historialIndex < historial.length - 1) {
        historial = historial.slice(0, historialIndex + 1);
    }
    historial.push(JSON.stringify(json));
    if (historial.length > HIST_MAX) historial.shift();
    historialIndex = historial.length - 1;
    actualizarBotonesHistorial();
}

function undoAction() {
    if (historialIndex <= 0) { aviso('No hay más pasos anteriores', 'warning'); return; }
    historialIndex--;
    restaurarEstado(historial[historialIndex]);
}

function redoAction() {
    if (historialIndex >= historial.length - 1) { aviso('Ya estás en el paso más reciente', 'warning'); return; }
    historialIndex++;
    restaurarEstado(historial[historialIndex]);
}

function restaurarEstado(jsonStr) {
    historialPaused = true;
    const json = JSON.parse(jsonStr);
    canvas.loadFromJSON(json, () => {
        const tipo = document.getElementById('product-type').value;
        const info = productos[tipo];
        if (info) {
            /* fabric.Image.fromURL(info.img, (img) => {
                img.set({ scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, selectable: false, evented: false });
                canvas.setBackgroundImage(img, () => { */
                    if (printBoundary) {
                        canvas.remove(printBoundary);
                        canvas.add(printBoundary);
                    }
                    canvas.renderAll();
                    historialPaused = false;
                    calcularPrecio();
                    actualizarBotonesHistorial();
                /* });
            }); */
        } else {
            canvas.renderAll();
            historialPaused = false;
            actualizarBotonesHistorial();
        }
    });
}

function actualizarBotonesHistorial() {
    const btnUndo = document.querySelector('.tool-undo');
    const btnRedo = document.querySelector('.tool-redo');
    if (btnUndo) btnUndo.style.opacity = historialIndex <= 0 ? '0.35' : '1';
    if (btnRedo) btnRedo.style.opacity = historialIndex >= historial.length - 1 ? '0.35' : '1';
}

canvas.on('object:added',    () => { if (!historialPaused) { guardarEstado(); calcularPrecio(); } });
canvas.on('object:removed',  () => { if (!historialPaused) { guardarEstado(); calcularPrecio(); } });
canvas.on('object:modified', () => { if (!historialPaused) guardarEstado(); });
canvas.on('mouse:down',      () => setActiveCanvas(canvas));

document.addEventListener('keydown', (e) => {
    const tag = document.activeElement.tagName.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undoAction(); }
    if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.shiftKey && e.key === 'z'))) { e.preventDefault(); redoAction(); }
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') { e.preventDefault(); duplicarElemento(); }
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); autoSaveLocal(); aviso('Diseño guardado', 'success'); }
    if (e.key === 'Delete' || e.key === 'Backspace') {
        const active = activeCanvas.getActiveObject();
        if (active && active !== printBoundary) {
            activeCanvas.remove(active);
            activeCanvas.discardActiveObject();
            activeCanvas.renderAll(); // <--- Fuerza a refrescar la pantalla
        }
    }
});

// ─── ALINEACIÓN MAGNÉTICA (SNAPPING) ─────────────────────────
const SNAP_THRESHOLD = 8;
let snapLines = [];

function limpiarSnapLines() {
    snapLines.forEach(l => canvas.remove(l));
    snapLines = [];
}

function crearLinea(x1, y1, x2, y2) {
    return new fabric.Line([x1, y1, x2, y2], {
        stroke: '#3498db', strokeWidth: 1, strokeDashArray: [5, 4],
        selectable: false, evented: false, opacity: 0.8
    });
}

canvas.on('object:moving', function(e) {
    limpiarSnapLines();
    const obj  = e.target;
    if (obj === printBoundary) return;

    const cW = 400;
    const cH = 450;

    const objBounds = obj.getBoundingRect(true);
    const objCX = objBounds.left + objBounds.width  / 2;
    const objCY = objBounds.top  + objBounds.height / 2;

    const refs = {
        h: [ cH / 2, 0, cH ],
        v: [ cW / 2, 0, cW ],
    };

    let snappedH = false, snappedV = false;

    [refs.v[0], refs.v[1] + objBounds.width/2, refs.v[2] - objBounds.width/2].forEach(refX => {
        if (!snappedV && Math.abs(objCX - refX) < SNAP_THRESHOLD) {
            obj.set({ left: refX - (objBounds.width / 2) + (obj.left - objBounds.left) });
            const ln = crearLinea(refX, 0, refX, cH);
            canvas.add(ln); snapLines.push(ln);
            snappedV = true;
        }
    });

    [refs.h[0], refs.h[1] + objBounds.height/2, refs.h[2] - objBounds.height/2].forEach(refY => {
        if (!snappedH && Math.abs(objCY - refY) < SNAP_THRESHOLD) {
            obj.set({ top: refY - (objBounds.height / 2) + (obj.top - objBounds.top) });
            const ln = crearLinea(0, refY, cW, refY);
            canvas.add(ln); snapLines.push(ln);
            snappedH = true;
        }
    });

    canvas.getObjects().forEach(other => {
        if (other === obj || other === printBoundary || snapLines.includes(other)) return;
        const ob = other.getBoundingRect(true);
        const oCX = ob.left + ob.width  / 2;
        const oCY = ob.top  + ob.height / 2;

        if (!snappedV && Math.abs(objCX - oCX) < SNAP_THRESHOLD) {
            obj.set({ left: oCX - (objBounds.width / 2) + (obj.left - objBounds.left) });
            const ln = crearLinea(oCX, 0, oCX, cH);
            canvas.add(ln); snapLines.push(ln);
            snappedV = true;
        }
        if (!snappedH && Math.abs(objCY - oCY) < SNAP_THRESHOLD) {
            obj.set({ top: oCY - (objBounds.height / 2) + (obj.top - objBounds.top) });
            const ln = crearLinea(0, oCY, cW, oCY);
            canvas.add(ln); snapLines.push(ln);
            snappedH = true;
        }
    });

    obj.setCoords();
    canvas.renderAll();
});

canvas.on('object:modified', limpiarSnapLines);
canvas.on('mouse:up',        limpiarSnapLines);

const coloresLetras = [
    '#000000','#1a1a1a','#2c3e50','#34495e','#4a4a4a','#808080','#c0c0c0','#f0f0f0','#ffffff',
    '#7b241c','#c0392b','#e74c3c','#ff6b6b',
    '#e91e8c','#fd79a8','#ffd6e0',
    '#d35400','#e67e22','#ff9f43','#f39c12','#f1c40f','#fdcb6e','#d4ac0d',
    '#1e5631','#27ae60','#2ecc71','#6ab04c','#00b894','#1abc9c','#a8e6cf',
    '#1a3a5c','#1565c0','#2980b9','#3498db','#0984e3','#74b9ff','#87ceeb',
    '#6c3483','#8e44ad','#9b59b6','#6c5ce7','#a29bfe','#d7bde2',
    '#3e1f00','#6d4c41','#795548','#d4a574',
    '#00cec9','#fa8072','#e6e6fa'
];

const POSICIONES_EXTRA = ['nuca', 'manga-izq', 'manga-der'];
const LABEL_EXTRA = { 'nuca': 'Nuca', 'manga-izq': 'Manga Izquierda', 'manga-der': 'Manga Derecha' };

// ─── ESCALAR CANVAS EN MÓVIL ─────────────────────────────────
function escalarCanvas() {
    const panel  = document.querySelector('.canvas-panel');
    const wrapper = document.getElementById('product-container');
    if (!panel || !wrapper) return;

    const panelStyle = window.getComputedStyle(panel);
    const padL   = parseFloat(panelStyle.paddingLeft)  || 16;
    const padR   = parseFloat(panelStyle.paddingRight) || 16;
    const available = panel.clientWidth - padL - padR;

    const baseW = 320;
    const baseH = 380;

    let canvasW, canvasH;
    if (available < baseW) {
        const ratio = available / baseW;
        canvasW = Math.floor(baseW * ratio);
        canvasH = Math.floor(baseH * ratio);
        canvas.setWidth(canvasW);
        canvas.setHeight(canvasH);
        canvas.setZoom(ratio);
    } else {
        canvasW = baseW;
        canvasH = baseH;
        canvas.setWidth(canvasW);
        canvas.setHeight(canvasH);
        canvas.setZoom(1);
    }

    // Forzar el wrapper exactamente al tamaño del canvas
    wrapper.style.width  = canvasW + 'px';
    wrapper.style.height = canvasH + 'px';

    canvas.renderAll();
}

// ─── 1. PRODUCTO ─────────────────────────────────────────────
function elegirProducto(el, tipo) {
    const objetosActuales = canvas.getObjects().filter(o => o.selectable !== false);
    const tieneDiseno = objetosActuales.length > 0 ||
        (memoriaDisenos['back'] && (() => {
            try { return (memoriaDisenos['back'].objects || []).filter(o => o.selectable !== false).length > 0; } catch(e) { return false; }
        })());

    document.querySelectorAll('.product-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    const sel = document.getElementById('product-type');
    sel.value = tipo;
    updateProductLogic();
    const nombres = { camiseta:'Camiseta', sudadera:'Sudadera', taza:'Taza', cuadro:'Cuadro' };
    aviso('Producto: ' + (nombres[tipo] || tipo) + ' — elige color y talla abajo', 'info');
}

function updateProductLogic() {
    const tipo = document.getElementById('product-type').value;
    const info = productos[tipo];
    const wrap = document.getElementById('dynamic-option-wrap');

    let guiaBtn = info.isClothing
        ? `<span class="btn-guia-tallas" onclick="document.getElementById('size-guide-modal').style.display='flex'; showSizeGuide('${tipo}')"><i class="fas fa-ruler"></i> Ver Guía</span>`
        : '';

    let optsHtml = '';
    const esCuadro = info.label === 'Material:';
    info.opts.forEach(o => {
        let precioLabel = '';
        if (esCuadro) {
            precioLabel = o === 'Azulejo' ? ' (9€)' : ` (${info.precio}€)`;
        }
        optsHtml += `<option value="${o}"${o === 'L' ? ' selected' : ''}>${o}${precioLabel}</option>`;
    });

    wrap.innerHTML = `
        <div class="ptb-select-wrap">
            <div class="ptb-select-header">
                <label class="label-sm" style="margin:0;">${info.label}</label>
                ${guiaBtn}
            </div>
            <select id="talla-select" class="custom-select ptb-select">
                ${optsHtml}
            </select>
        </div>`;

    // Event listener para limitar colores en tallas 4XL y 5XL
    const tallaSelect = document.getElementById('talla-select');
    if (tallaSelect) {
        tallaSelect.addEventListener('change', function() {
            const talla = this.value;
            const colores4XL5XL = ['Blanco', 'Negro', 'Marino', 'Gris Jaspeado'];
            if (talla === '4XL' || talla === '5XL') {
                // Verificar si el color actual está disponible
                if (colorProductoActual && !colores4XL5XL.includes(colorProductoActual)) {
                    // Cambiar a Blanco si el color no está disponible
                    colorProductoActual = 'Blanco';
                    updateEditor();
                    aviso('Las tallas 4XL y 5XL solo están disponibles en Blanco, Negro, Marino y Gris Jaspeado', 'warning');
                }
            }
            // Recalcular precio (especialmente para azulejo que tiene precio diferente)
            calcularPrecio();
        });
    }

    const esRopa = info.isClothing;
    const lblCP = document.getElementById('label-color-producto');
    if (lblCP) {
        if (esRopa) {
            lblCP.innerHTML = '👕 Color de prenda <span style="font-size:9px;color:#aaa;font-weight:600;"> — color físico del artículo que recibirás</span>';
        } else {
            lblCP.innerHTML = '🖼️ Color marco/material <span style="font-size:9px;color:#aaa;font-weight:600;"> — color físico del artículo que recibirás</span>';
        }
    }
    document.getElementById('view-toggles').style.display        = esRopa ? 'flex'  : 'none';
    document.getElementById('view-toggles-bottom').style.display = esRopa ? 'flex'  : 'none';
    document.getElementById('extras-ropa-wrap').style.display    = esRopa ? 'block' : 'none';
    const miniWrap = document.getElementById('mini-canvas-wrap');
    if (miniWrap) miniWrap.style.display = esRopa ? 'block' : 'none';

    if (!esRopa) {
        POSICIONES_EXTRA.forEach(p => {
            const chk = document.getElementById('chk-' + p);
            const panel = document.getElementById('panel-' + p);
            if (chk) chk.checked = false;
            if (panel) panel.style.display = 'none';
        });
        if (vistaActual === 'back') {
            vistaActual = 'front';
            memoriaDisenos = { front: null, back: null };
        }
    }

    // Fondo lienzo siempre Transparente; color prenda siempre Negro
    const coloresDisponibles = Object.keys(info.colores);
    const coloresPrendaDisp  = Object.keys(info.coloresProducto || {});
    colorActual         = coloresDisponibles.includes('Transparente') ? 'Transparente' : coloresDisponibles[0];
    colorProductoActual = coloresPrendaDisp.includes('Negro') ? 'Negro' : (coloresPrendaDisp[0] || '');
    updateEditor();
    actualizarDescripcionProducto();
}

// ─── DESCRIPCIONES DE PRODUCTOS ──────────────────────────────
const productosDescripciones = {
    camiseta: {
        icono: '👕',
        detalles: [
            { icon: 'fas fa-leaf',            texto: '100% Algodón (160-165g/m²)' },
            { icon: 'fas fa-ruler-combined',  texto: 'Corte recto clásico Unisex (Tubular)' },
            { icon: 'fas fa-print',           texto: 'Impresión DTF Textil HD (No cuartea)' },
            { icon: 'fas fa-tint',            texto: 'Tintas Ecológicas base agua (Oeko-Tex)' },
            { icon: 'fas fa-temperature-low', texto: 'Lavado a máquina máx 40º (Del revés)' },
        ]
    },
    sudadera: {
        icono: '🧥',
        detalles: [
            { icon: 'fas fa-layer-group',     texto: 'Tejido grueso 280g/m² (Interior afelpado)' },
            { icon: 'fas fa-tshirt',          texto: '80% Algodón ring-spun / 20% Poliéster' },
            { icon: 'fas fa-wind',            texto: 'Capucha doble forro con cordón a tono' },
            { icon: 'fas fa-print',           texto: 'Impresión DTF Textil HD de alta durabilidad' },
            { icon: 'fas fa-shopping-bag',    texto: 'Bolsillo frontal tipo canguro' },
        ]
    },
    taza: {
        icono: '☕',
        detalles: [
            { icon: 'fas fa-coffee',          texto: 'Cerámica Blanca Premium AAA (11oz / 325ml)' },
            { icon: 'fas fa-fire',            texto: 'Resistente a Microondas y Lavavajillas' },
            { icon: 'fas fa-magic',           texto: 'Opción "Taza Mágica" (Revela diseño con calor)' },
            { icon: 'fas fa-palette',         texto: 'Sublimación 360º de colores vibrantes' },
            { icon: 'fas fa-box',             texto: 'Envío en caja anti-rotura reforzada' },
        ]
    },
    cuadro: {
        icono: '🖼️',
        detalles: [
            { icon: 'fas fa-image',           texto: 'Lienzo Canvas Premium 340g/m² (Algodón)' },
            { icon: 'fas fa-vector-square',   texto: 'Bastidor de madera de pino (2cm grosor)' },
            { icon: 'fas fa-paint-brush',     texto: 'Impresión Giclée (Calidad museo a 12 tintas)' },
            { icon: 'fas fa-sun',             texto: 'Protección UV (No pierde color con el tiempo)' },
            { icon: 'fas fa-thumbtack',       texto: 'Incluye kit de anclaje (Listo para colgar)' },
        ]
    }
};

function actualizarDescripcionProducto() {
    const tipo = document.getElementById('product-type').value;
    const desc = productosDescripciones[tipo];
    const box  = document.getElementById('product-description');
    if (!desc || !box) return;

    let html = `<div class="pdesc-titulo"><span class="pdesc-icono">${desc.icono}</span> Características</div>`;
    desc.detalles.forEach(d => {
        html += `<div class="pdesc-linea"><i class="${d.icon} pdesc-icon"></i><span>${d.texto}</span></div>`;
    });
    box.innerHTML = html;
}

function updateEditor() {
    const tipo = document.getElementById('product-type').value;
    const info = productos[tipo];

    const _bgVal = info.colores[colorActual];
    canvas.backgroundColor = (_bgVal !== undefined && _bgVal !== null) ? _bgVal : null;
    updateMiniCanvasBg();
        /* fabric.Image.fromURL(info.img, function(img) {
        img.set({ scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, selectable: false, evented: false });
        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
    }); */
    canvas.renderAll();

    const cc = document.getElementById('color-options');
    cc.innerHTML = '';
    Object.keys(info.colores).forEach(nombre => {
        const btn = document.createElement('div');
        const valColor = info.colores[nombre];
        btn.className = 'color-circle' + (colorActual === nombre ? ' active' : '') + (valColor === null ? ' transparent-circle' : '');
        if (valColor !== null) btn.style.backgroundColor = valColor;
        if (valColor === '#ffffff') btn.style.border = '2px solid #ddd';
        btn.title = nombre;
        btn.onclick = () => {
            colorActual = nombre;
            const lbl = document.getElementById('color-lienzo-nombre');
            if (lbl) lbl.textContent = nombre;
            if (activeCanvas === canvas) {
                updateEditor();
            } else {
                activeCanvas.backgroundColor = (valColor === null || valColor === 'transparent') ? '' : valColor;
                activeCanvas.renderAll();
            }
        };
        cc.appendChild(btn);
    });
    // Actualizar label nombre lienzo
    const lblLienzo = document.getElementById('color-lienzo-nombre');
    if (lblLienzo) lblLienzo.textContent = colorActual;

    const cp = document.getElementById('color-producto-options');
    const grupoCP = document.getElementById('grupo-color-producto');
    const tieneColoresProducto = info.coloresProducto && Object.keys(info.coloresProducto).length > 0;
    grupoCP.style.display = tieneColoresProducto ? '' : 'none';
    if (tieneColoresProducto) {
        cp.innerHTML = '';
        Object.keys(info.coloresProducto).forEach(nombre => {
            const btn = document.createElement('div');
            btn.className = 'color-circle' + (colorProductoActual === nombre ? ' active' : '');
            btn.style.backgroundColor = info.coloresProducto[nombre];
            if (info.coloresProducto[nombre] === '#ffffff') btn.style.border = '2px solid #ddd';
            btn.title = nombre;
            btn.onclick = () => {
                colorProductoActual = nombre;
                const lbl = document.getElementById('color-prenda-nombre');
                if (lbl) lbl.textContent = nombre;
                updateEditor();
            };
            cp.appendChild(btn);
        });
        // Actualizar label nombre prenda
        const lblPrenda = document.getElementById('color-prenda-nombre');
        if (lblPrenda) lblPrenda.textContent = colorProductoActual;
    }

    if (printBoundary) canvas.remove(printBoundary);

    const _rawBg = info.colores[colorActual];
    const bgHex = (_rawBg !== undefined && _rawBg !== null) ? _rawBg : '#ffffff';
    const r = parseInt(bgHex.slice(1,3),16), g = parseInt(bgHex.slice(3,5),16), b = parseInt(bgHex.slice(5,7),16);
    const luminancia = (0.299*r + 0.587*g + 0.114*b) / 255;
    let borderColor;
    if (luminancia > 0.6) {
        borderColor = 'rgba(0,0,0,0.55)';
    } else if (luminancia < 0.35) {
        borderColor = 'rgba(255,255,255,0.7)';
    } else {
        borderColor = 'rgba(255,255,255,0.6)';
    }

    printBoundary = new fabric.Rect({
        width: 220, height: 290, fill: 'transparent',
        stroke: borderColor, strokeDashArray: [5,5], strokeWidth: 2,
        selectable: false, evented: false, opacity: 1,
        originX: 'center', originY: 'center', left: 160, top: 190,
        id: '__printBoundary__'
    });
    canvas.add(printBoundary);
    canvas.renderAll();
    calcularPrecio();
    setTimeout(escalarCanvas, 100);
    actualizarDescripcionProducto();
    const btn = document.getElementById('btn-boundary');
    if (btn) btn.classList.add('active');
}

// ─── 3. TEXTO ────────────────────────────────────────────────
function toggleTextInput() {
    const area  = document.getElementById('text-input-area');
    const icon  = document.getElementById('icon-toggle-text');
    const input = document.getElementById('texto-nuevo');
    const open  = area.style.display === 'none' || area.style.display === '';
    area.style.display = open ? 'flex' : 'none';
    icon.className     = open ? 'fas fa-minus' : 'fas fa-plus';
    if (open && input) setTimeout(() => input.focus(), 50);
}

function addText() {
    const inputEl  = document.getElementById('texto-nuevo');
    const rawText  = inputEl ? inputEl.value.trim() : '';
    const textStr  = rawText !== '' ? rawText : 'TU TEXTO AQUÍ';

    const activeFontEl = document.querySelector('.font-option.active');
    const fontFamily   = activeFontEl ? activeFontEl.getAttribute('onclick').match(/'([^']+)'\)$/)?.[1] || 'Arial' : 'Arial';

    const sizeSlider = document.getElementById('text-size-slider');
    const fontSize   = sizeSlider ? parseInt(sizeSlider.value) : 22;

    const fc = activeCanvas;
    const cx = fc.width / 2, cy = fc.height / 2;
    // width fijo necesario para que textAlign funcione en Fabric 5 IText
    const textWidth = fc === canvas ? 220 : fc.width * 0.9;

    const text = new fabric.IText(textStr, {
        left: cx, top: cy, originX: 'center', originY: 'center',
        fontFamily: fontFamily, fontSize: fontSize, fontWeight: 'bold', fill: '#000000',
        width: textWidth, splitByGrapheme: false
    });
    text.on('editing:entered', function() { if (this.text === 'TU TEXTO AQUÍ') this.selectAll(); });
    fc.add(text);
    fc.setActiveObject(text);
    if (fc === canvas) fc.bringToFront(text);

    if (rawText === '') {
        setTimeout(() => { text.enterEditing(); text.selectAll(); fc.renderAll(); }, 50);
        aviso('Texto añadido — doble clic para editarlo', 'info');
    } else {
        fc.renderAll();
        if (inputEl) inputEl.value = '';
        const area = document.getElementById('text-input-area');
        const icon = document.getElementById('icon-toggle-text');
        if (area) area.style.display = 'none';
        if (icon) icon.className = 'fas fa-plus';
        aviso('Texto "' + textStr.substring(0,20) + (textStr.length>20?'…':'') + '" añadido al lienzo', 'success');
    }
    pulseEditando();
}
function updateTextRotation(val) {
    const obj = activeCanvas.getActiveObject();
    // Snap a 0° si está muy cerca
    let angle = parseInt(val);
    if (Math.abs(angle) <= 3) { angle = 0; }
    if (obj && obj.type && obj.type.includes('text')) {
        obj.set('angle', angle);
        obj.setCoords();
        activeCanvas.renderAll();
    }
    const slider = document.getElementById('text-rotation-slider');
    if (slider) slider.value = angle;
    document.getElementById('text-rotation-val').innerText = angle + '°';
}

function resetTextRotation() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero un texto en el lienzo', 'warning');; return; }
    if (!obj.type.includes('text')) { aviso('Esta función es solo para textos', 'warning');; return; }
    obj.set('angle', 0);
    obj.setCoords();
    activeCanvas.renderAll();
    const sl = document.getElementById('text-rotation-slider');
    if (sl) sl.value = 0;
    document.getElementById('text-rotation-val').innerText = '0°';
}

function resetTextSize() {
    const DEFAULT_SIZE = 22;
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero un texto en el lienzo', 'warning');; return; }
    if (!obj.type.includes('text')) { aviso('Esta función es solo para textos', 'warning');; return; }
    obj.set('fontSize', DEFAULT_SIZE);
    obj.setCoords();
    activeCanvas.renderAll();
    const sl = document.getElementById('text-size-slider');
    if (sl) sl.value = DEFAULT_SIZE;
    document.getElementById('text-size-val').innerText = DEFAULT_SIZE + 'px';
}

// ─── 4. IMÁGENES ─────────────────────────────────────────────
async function handleImage(e) {
    let file = e.target.files[0];
    if (!file) return;
    if (file.name.toLowerCase().endsWith('.heic') || file.type === 'image/heic') {
        try {
            const blob = await heic2any({ blob: file, toType: "image/jpeg", quality: 0.85 });
            file = new File([blob], file.name.replace(/\.heic$/i, '.jpg'), { type: "image/jpeg" });
        } catch (err) {
            aviso('No se pudo procesar la foto. Intenta con otro formato', 'error');;
            return;
        }
    }

    // Comprobar si la subida viene de un hueco de foto de plantilla
    const inp = e.target;
    const replaceTarget = inp._replaceTarget || null;
    const replaceCanvas = inp._replaceCanvas || canvas;
    const alsoRemove    = inp._alsoRemove    || null;
    inp._replaceTarget = null;
    inp._replaceCanvas = null;
    inp._alsoRemove    = null;

    const reader = new FileReader();
    reader.onload = function(f) {
        fabric.Image.fromURL(f.target.result, function(img) {
            if (replaceTarget && replaceTarget.isPhotoSlot) {
                // Ajustar la foto al tamaño del hueco y colocarla en su posición
                const scaleX = replaceTarget.width  * replaceTarget.scaleX / img.width;
                const scaleY = replaceTarget.height * replaceTarget.scaleY / img.height;
                const scale  = Math.min(scaleX, scaleY);
                img.set({
                    left:    replaceTarget.left,
                    top:     replaceTarget.top,
                    originX: replaceTarget.originX || 'center',
                    originY: replaceTarget.originY || 'center',
                    scaleX:  scale,
                    scaleY:  scale,
                    angle:   replaceTarget.angle || 0
                });
                replaceCanvas.remove(replaceTarget);  // quitar el rectángulo placeholder
                if (alsoRemove) replaceCanvas.remove(alsoRemove); // quitar el emoji 📷
                replaceCanvas.add(img);
                replaceCanvas.setActiveObject(img);
                replaceCanvas.renderAll();
                pulseEditando();
            } else {
                const targetCanvas = activeCanvas || canvas;
                let scale, left, top;
                if (targetCanvas === canvas) {
                    // Lienzo principal: posición y escala estándar
                    scale = 150 / img.width;
                    left  = 160;
                    top   = 190;
                } else {
                    // Lienzo pequeño (nuca / manga): escalar para caber bien
                    const maxW = targetCanvas.width  * 0.85;
                    const maxH = targetCanvas.height * 0.85;
                    scale = Math.min(maxW / img.width, maxH / img.height, 1);
                    left  = targetCanvas.width  / 2;
                    top   = targetCanvas.height / 2;
                }
                img.set({ left, top, originX: 'center', originY: 'center', scaleX: scale, scaleY: scale });
                targetCanvas.add(img);
                targetCanvas.setActiveObject(img);
                targetCanvas.renderAll();
                if (targetCanvas !== canvas) syncMiniPrice();
                // Aviso de baja resolución si la imagen es pequeña
                if (img.width < 500 || img.height < 500) {
                    aviso('⚠️ Imagen de baja resolución (' + img.width + '×' + img.height + 'px) — puede salir pixelada al imprimir. Intenta subir una de al menos 1000px', 'warning');
                } else {
                    aviso('Foto añadida — arrastra las esquinas para redimensionarla', 'success');
                }
                pulseEditando();
            }
        });
    };
    reader.readAsDataURL(file);
    subirRecursoServidor(file);
}

async function subirRecursoServidor(file) {
    const fd = new FormData();
    fd.append('foto', file);
    fd.append('csrf_token', csrfToken);
    const res = await fetch('subir_recurso.php', { method: 'POST', body: fd });
    if (res.ok) {
        setTimeout(cargarBiblioteca, 800);
        aviso('📁 Foto guardada en tu biblioteca — úsala cuando quieras', 'info');
    }
}

async function cargarBiblioteca() {
    try {
        const res   = await fetch('obtener_recursos.php?t=' + new Date().getTime());
        const fotos = await res.json();
        const area  = document.getElementById('biblioteca-area');
        const lista = document.getElementById('lista-recursos');
        if (Array.isArray(fotos) && fotos.length > 0) {
            area.style.display = 'block';
            lista.innerHTML = '';
            fotos.forEach(ruta => {
                const wrap = document.createElement('div');
                wrap.className = "thumb-wrap";
                const img = document.createElement('img');
                img.src = ruta; img.className = "thumb-img";
                img.onclick = () => {
                    fabric.Image.fromURL(ruta, (fImg) => {
                        // ── Si hay un slot de plantilla esperando → rellenarlo ──
                        if (_activePhotoSlot && _activePhotoCanvas) {
                            const slot = _activePhotoSlot;
                            const sfc  = _activePhotoCanvas;
                            const emoji = _activePhotoEmoji;

                            const scaleX = (slot.width  * (slot.scaleX || 1)) / fImg.width;
                            const scaleY = (slot.height * (slot.scaleY || 1)) / fImg.height;
                            const scale  = Math.min(scaleX, scaleY);

                            fImg.set({
                                left:    slot.left,
                                top:     slot.top,
                                originX: slot.originX || 'center',
                                originY: slot.originY || 'center',
                                scaleX:  scale,
                                scaleY:  scale,
                                angle:   slot.angle || 0
                            });

                            sfc.remove(slot);
                            if (emoji) sfc.remove(emoji);
                            sfc.add(fImg);
                            sfc.setActiveObject(fImg);
                            sfc.requestRenderAll();

                            // Limpiar el slot activo
                            _activePhotoSlot   = null;
                            _activePhotoCanvas = null;
                            _activePhotoEmoji  = null;

                            // Scroll de vuelta al lienzo
                            aviso('Foto colocada en la plantilla', 'success');
                            pulseEditando();
                            setTimeout(() => scrollAlLienzo(), 300);
                            return;
                        }

                        // ── Comportamiento normal ──
                        let targetCanvas = activeCanvas;

                        if (targetCanvas === canvas) {
                            // Lógica para el cuerpo principal (Pecho / Espalda)
                            const sc = 150 / fImg.width;
                            fImg.set({ left: 160, top: 190, originX: 'center', originY: 'center', scaleX: sc, scaleY: sc });
                        } else {
                            // Lógica para las zonas extra (Nuca / Mangas)
                            const maxW = targetCanvas.width  * 0.85;
                            const maxH = targetCanvas.height * 0.85;
                            const scale = Math.min(maxW / fImg.width, maxH / fImg.height, 1);
                            fImg.set({
                                left:    targetCanvas.width  / 2,
                                top:     targetCanvas.height / 2,
                                originX: 'center',
                                originY: 'center',
                                scaleX:  scale,
                                scaleY:  scale
                            });
                        }

                        // Añadimos la imagen al lienzo correspondiente
                        targetCanvas.add(fImg); 
                        targetCanvas.setActiveObject(fImg);
                        targetCanvas.requestRenderAll();
                        
                        // Si se añade a una manga o nuca, actualizamos el contador de precio
                        if (targetCanvas !== canvas) {
                            syncMiniPrice();
                        }
                        aviso('Foto añadida — arrastra las esquinas para redimensionarla', 'success');
                        pulseEditando();
                    }, { crossOrigin: 'anonymous' });
                };
                const btnTrash = document.createElement('span');
                btnTrash.innerHTML = '<i class="fas fa-trash-alt"></i>';
                btnTrash.className = "btn-del-rec";
                btnTrash.onclick = async (ev) => {
                    ev.stopPropagation();
                    if (await cgConfirm({ titulo: 'Eliminar foto', mensaje: '¿Eliminar esta foto de tu cuenta? Esta acción no se puede deshacer.', confirmar: 'Sí, eliminar', tipo: 'trash' })) {
                        const formData = new FormData();
                        formData.append('ruta_imagen', ruta);
                        formData.append('csrf_token', csrfToken);
                        try {
                            const resDel = await fetch('borrar_recurso.php', { method: 'POST', body: formData });
                            const dataDel = await resDel.json();
                            if(dataDel.success) {
                                cargarBiblioteca();
                            } else {
                                aviso('No se pudo borrar: ' + dataDel.error, 'error');;
                            }
                        } catch (error) {
                            aviso('Error de conexión al borrar la foto', 'error');;
                        }
                    }
                };
                wrap.appendChild(img); wrap.appendChild(btnTrash); lista.appendChild(wrap);
            });
        } else { area.style.display = 'none'; }
    } catch (e) { console.error("Error cargando biblioteca"); }
}

// ─── 5. EXTRAS CON LOGOS INDEPENDIENTES ─────────────────────
function togglePosicion(posId) {
    const chk   = document.getElementById('chk-' + posId);
    const panel = document.getElementById('panel-' + posId);
    panel.style.display = chk.checked ? 'block' : 'none';
    if (!chk.checked) borrarLogo(posId);
}

function previewLogo(posId, event) {
    const file = event.target.files[0];
    if (!file) return;
    const preview = document.getElementById('preview-' + posId);
    const img     = document.getElementById('img-' + posId);
    preview.style.display = 'flex';
    img.src = URL.createObjectURL(file);
}

function borrarLogo(posId) {
    const input   = document.getElementById('upload-' + posId);
    const preview = document.getElementById('preview-' + posId);
    const img     = document.getElementById('img-' + posId);
    input.value   = '';
    img.src        = '';
    preview.style.display = 'none';
}

// ─── 6. CÁLCULO DE PRECIO ────────────────────────────────────
function calcularPrecio() {
    const tipo = document.getElementById('product-type').value;
    if (!productos[tipo]) return;

    let precioBase = productos[tipo].precio;
    let extras = [];

    // Descuento para azulejo (-21€)
    if (tipo === 'cuadro') {
        const tallaSelect = document.getElementById('talla-select');
        const material = tallaSelect ? tallaSelect.value : '';
        if (material === 'Azulejo') {
            precioBase = 9.00; // Azulejo vale 9€
        }
    }

    const tieneCosas = (json) => {
        if (!json || !json.objects) return false;
        return json.objects.some(o => o.selectable !== false || o.evented !== false);
    };
    const jsonActual = canvas.toJSON();
    const tieneFront = tieneCosas(vistaActual === 'front' ? jsonActual : memoriaDisenos['front']);
    const tieneBack  = tieneCosas(vistaActual === 'back'  ? jsonActual : memoriaDisenos['back']);
    const dobleCaraActiva = tieneFront && tieneBack;
    if (dobleCaraActiva) extras.push({ label: 'Diseño doble cara', precio: 10.00 });

    MINI_IDS.forEach(posId => {
        const fc = miniCanvases[posId];
        if (fc && fc.getObjects().length > 0) {
            extras.push({ label: LABEL_EXTRA[posId], precio: 3.00 });
        }
    });

    const totalExtras = extras.reduce((sum, e) => sum + e.precio, 0);
    const total       = precioBase + totalExtras;
    const totalStr    = total.toFixed(2).replace('.', ',') + ' €';

    document.getElementById('display-price').innerText = totalStr;
    const precioEl = document.getElementById('precio-live-display');
    const oldVal = precioEl.innerText;
    precioEl.innerText = totalStr;
    if (oldVal !== totalStr) {
        precioEl.classList.remove('precio-flash');
        void precioEl.offsetWidth;
        precioEl.classList.add('precio-flash');
        precioEl.addEventListener('animationend', () => precioEl.classList.remove('precio-flash'), { once: true });
    }

    const liveExtras = document.getElementById('precio-live-extras');
    if (extras.length > 0) {
        let html = `<span class="plde-base">${precioBase.toFixed(2).replace('.', ',')} €</span>`;
        extras.forEach(e => {
            html += `<span class="plde-extra">+ ${e.label} <strong>+${e.precio.toFixed(2).replace('.', ',')} €</strong></span>`;
        });
        liveExtras.innerHTML = html;
        liveExtras.style.display = 'flex';
    } else {
        liveExtras.style.display = 'none';
        liveExtras.innerHTML = '';
    }

    const desgloseBox = document.getElementById('desglose-precio');
    if (extras.length > 0) {
        let html = `<div class="desglose-linea"><span>Producto base</span><span>${precioBase.toFixed(2).replace('.', ',')} €</span></div>`;
        extras.forEach(e => {
            html += `<div class="desglose-linea extra"><span>+ ${e.label}</span><span>+${e.precio.toFixed(2).replace('.', ',')} €</span></div>`;
        });
        html += `<div class="desglose-linea total"><span>TOTAL</span><span>${total.toFixed(2).replace('.', ',')} €</span></div>`;
        desgloseBox.innerHTML = html;
        desgloseBox.style.display = 'block';
    } else {
        desgloseBox.style.display = 'none';
    }
}

// ─── 7. FILTROS ──────────────────────────────────────────────
function applyFilter(t) {
    const obj = activeCanvas.getActiveObject();
    if (!obj || obj.type !== 'image') { aviso('Selecciona primero la foto en el lienzo', 'warning'); return; }

    const savedLeft    = obj.left;
    const savedTop     = obj.top;
    const savedScaleX  = obj.scaleX;
    const savedScaleY  = obj.scaleY;
    const savedOriginX = obj.originX;
    const savedOriginY = obj.originY;

    obj.filters = [];
    if (t === 'grayscale')   obj.filters.push(new fabric.Image.filters.Grayscale());
    if (t === 'sepia')       obj.filters.push(new fabric.Image.filters.Sepia());
    if (t === 'brownie')     obj.filters.push(new fabric.Image.filters.Brownie());
    if (t === 'kodak')       obj.filters.push(new fabric.Image.filters.Kodachrome());
    if (t === 'technicolor') obj.filters.push(new fabric.Image.filters.Technicolor());
    if (t === 'polaroid')    obj.filters.push(new fabric.Image.filters.Polaroid());
    if (t === 'pixelate')    obj.filters.push(new fabric.Image.filters.Pixelate({ blocksize: 6 }));
    if (t === 'blur')        obj.filters.push(new fabric.Image.filters.Blur({ blur: 0.5 }));
    if (t === 'sharpen')     obj.filters.push(new fabric.Image.filters.Convolute({ matrix: [0,-1,0,-1,5,-1,0,-1,0] }));
    if (t === 'blackwhite')  obj.filters.push(new fabric.Image.filters.BlackWhite());
    if (t === 'contrast')    obj.filters.push(new fabric.Image.filters.Contrast({ contrast: 0.3 }));
    if (t === 'brightness')  obj.filters.push(new fabric.Image.filters.Brightness({ brightness: 0.15 }));
    if (t === 'tint')        obj.filters.push(new fabric.Image.filters.Tint({ color: '#e74c3c', opacity: 0.4 }));
    if (t === 'vibrance')    obj.filters.push(new fabric.Image.filters.Vibrance({ vibrance: 1 }));
    if (t === 'noise')       obj.filters.push(new fabric.Image.filters.Noise({ noise: 60 }));
    if (t === 'none')        obj.filters = [];

    obj.applyFilters();
    obj.set({ left: savedLeft, top: savedTop, scaleX: savedScaleX, scaleY: savedScaleY, originX: savedOriginX, originY: savedOriginY, dirty: true });
    obj.setCoords();
    activeCanvas.requestRenderAll();
    const nombres = { grayscale:'Blanco y negro', sepia:'Sepia', brownie:'Brownie', kodak:'Kodak', technicolor:'Technicolor', polaroid:'Polaroid', pixelate:'Pixelado', blur:'Desenfoque', sharpen:'Nitidez', blackwhite:'Blanco/Negro fuerte', contrast:'Contraste', brightness:'Brillo', tint:'Tono rojo', vibrance:'Vibrance', noise:'Ruido', none:'Sin filtro' };
    aviso('Filtro: ' + (nombres[t] || t) + ' aplicado', 'success');
}

// ─── ESCALA DE IMAGEN ─────────────────────────────────────────
function updateImageScale(val) {
    const obj = activeCanvas.getActiveObject();
    if (obj && obj.type === 'image') {
        const scaleVal = parseFloat(val);
        obj.set({ scaleX: scaleVal, scaleY: scaleVal });
        document.getElementById('scale-val').innerText = Math.round(scaleVal * 100) + '%';
        obj.setCoords();
        activeCanvas.renderAll();
    }
}

function rotarElemento(grados) {
    const obj = activeCanvas.getActiveObject();
    if (!obj) return;
    const actual = obj.angle || 0;
    obj.set('angle', (actual + grados + 360) % 360);
    obj.setCoords();
    activeCanvas.renderAll();
    const nuevoAngulo = obj.angle > 180 ? obj.angle - 360 : obj.angle;
    if (obj.type === 'image') {
        const s = document.getElementById('image-rotation-slider');
        if (s) { s.value = nuevoAngulo; document.getElementById('rotation-val').innerText = nuevoAngulo + '°'; }
    } else if (obj.type && obj.type.includes('text')) {
        const s = document.getElementById('text-rotation-slider');
        if (s) { s.value = nuevoAngulo; document.getElementById('text-rotation-val').innerText = nuevoAngulo + '°'; }
    }
}

function duplicarElemento() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) return;
    obj.clone(function(clon) {
        clon.set({ left: obj.left + 18, top: obj.top + 18, evented: true });
        activeCanvas.add(clon);
        activeCanvas.setActiveObject(clon);
        activeCanvas.renderAll();
        aviso('Elemento duplicado — ya puedes editarlo por separado', 'success');
    });
}

function restaurarTamano() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { return; }
    if (obj.type === 'image') {
        const defaultScale = 150 / obj.width;
        obj.set({ scaleX: defaultScale, scaleY: defaultScale, angle: 0 });
        const ss = document.getElementById('image-scale-slider');
        if (ss) ss.value = defaultScale;
        document.getElementById('scale-val').innerText = Math.round(defaultScale * 100) + '%';
        const rs = document.getElementById('image-rotation-slider');
        if (rs) rs.value = 0;
        document.getElementById('rotation-val').innerText = '0°';
    } else if (obj.type && obj.type.includes('text')) {
        obj.set({ scaleX: 1, scaleY: 1, angle: 0 });
        const ts = document.getElementById('text-size-slider');
        if (ts) { ts.value = obj.fontSize || 22; }
        const trs = document.getElementById('text-rotation-slider');
        if (trs) trs.value = 0;
        document.getElementById('text-rotation-val').innerText = '0°';
    } else {
        obj.set({ scaleX: 1, scaleY: 1, angle: 0 });
    }
    obj.setCoords();
    activeCanvas.renderAll();
}

function resetImageScale() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero la imagen en el lienzo', 'warning');; return; }
    if (obj.type !== 'image') { aviso('Esta función es solo para imágenes', 'warning');; return; }
    const defaultScale = 150 / obj.width;
    obj.set({ scaleX: defaultScale, scaleY: defaultScale });
    document.getElementById('image-scale-slider').value = defaultScale;
    document.getElementById('scale-val').innerText = Math.round(defaultScale * 100) + '%';
    obj.setCoords();
    activeCanvas.renderAll();
}

// ─── ROTACIÓN DE IMAGEN ───────────────────────────────────────
function updateImageRotation(val) {
    const obj = activeCanvas.getActiveObject();
    if (obj && obj.type === 'image') {
        const angleVal = parseInt(val);
        obj.set('angle', angleVal);
        document.getElementById('rotation-val').innerText = angleVal + '°';
        obj.setCoords();
        activeCanvas.renderAll();
    }
}

function resetImageRotation() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero la imagen en el lienzo', 'warning');; return; }
    if (obj.type !== 'image') { aviso('Esta función es solo para imágenes', 'warning');; return; }
    obj.set('angle', 0);
    document.getElementById('image-rotation-slider').value = 0;
    document.getElementById('rotation-val').innerText = '0°';
    obj.setCoords();
    activeCanvas.renderAll();
}

function updateTextStyles(style, value) {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) {
        if (style !== 'size') aviso('Selecciona primero el texto en el lienzo', 'warning');;
        return;
    }
    if (style === 'size')   obj.set('fontSize', parseInt(value));
    if (style === 'bold') {
        const newWeight = obj.fontWeight === 'bold' ? 'normal' : 'bold';
        obj.set('fontWeight', newWeight);
        const btnB = document.getElementById('btn-bold');
        if (btnB) btnB.classList.toggle('active', newWeight === 'bold');
    }
    if (style === 'italic') {
        const newStyle = obj.fontStyle === 'italic' ? 'normal' : 'italic';
        obj.set('fontStyle', newStyle);
        const btnI = document.getElementById('btn-italic');
        if (btnI) btnI.classList.toggle('active', newStyle === 'italic');
    }
    activeCanvas.renderAll();
}

function seleccionarFuente(el, family) {
    document.querySelectorAll('.font-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    changeFont(family);
}

function changeFont(family) {
    const obj = activeCanvas.getActiveObject();
    if (obj && obj.type.includes('text')) { obj.set('fontFamily', family); activeCanvas.renderAll(); }
}

function aplicarEfectoTexto(efecto) {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) {
        aviso('Selecciona primero un texto en el lienzo', 'warning');
        return;
    }

    // Resetear siempre antes de aplicar
    obj.set({ shadow: null, stroke: null, strokeWidth: 0, paintFirst: 'fill' });

    switch (efecto) {
        case 'shadow':
            obj.set({ shadow: new fabric.Shadow({ color: 'rgba(0,0,0,0.7)', blur: 10, offsetX: 4, offsetY: 4 }) });
            break;
        case 'stroke-black':
            obj.set({ stroke: '#000000', strokeWidth: 4, paintFirst: 'stroke' });
            break;
        case 'stroke-white':
            obj.set({ stroke: '#ffffff', strokeWidth: 4, paintFirst: 'stroke' });
            break;
        case 'neon':
            obj.set({ shadow: new fabric.Shadow({ color: '#39ff14', blur: 22, offsetX: 0, offsetY: 0 }) });
            break;
        case 'neon-blue':
            obj.set({ shadow: new fabric.Shadow({ color: '#00cfff', blur: 22, offsetX: 0, offsetY: 0 }) });
            break;
        case 'neon-pink':
            obj.set({ shadow: new fabric.Shadow({ color: '#ff2d78', blur: 22, offsetX: 0, offsetY: 0 }) });
            break;
        case 'retro':
            obj.set({ shadow: new fabric.Shadow({ color: '#c0392b', blur: 0, offsetX: 5, offsetY: 5 }) });
            break;
        case 'retro-blue':
            obj.set({ shadow: new fabric.Shadow({ color: '#1565c0', blur: 0, offsetX: 5, offsetY: 5 }) });
            break;
        case 'ice':
            obj.set({
                stroke: '#90cdf4', strokeWidth: 2, paintFirst: 'stroke',
                shadow: new fabric.Shadow({ color: 'rgba(0,180,255,0.5)', blur: 10, offsetX: 1, offsetY: 1 })
            });
            break;
        case 'gold':
            obj.set({
                stroke: '#d4ac0d', strokeWidth: 2, paintFirst: 'stroke',
                shadow: new fabric.Shadow({ color: 'rgba(241,196,15,0.8)', blur: 12, offsetX: 0, offsetY: 0 })
            });
            break;
        case 'silver':
            obj.set({
                stroke: '#aaaaaa', strokeWidth: 2, paintFirst: 'stroke',
                shadow: new fabric.Shadow({ color: 'rgba(200,200,200,0.8)', blur: 10, offsetX: 0, offsetY: 0 })
            });
            break;
        case 'fire':
            obj.set({ shadow: new fabric.Shadow({ color: '#ff6b00', blur: 14, offsetX: 0, offsetY: -4 }) });
            break;
        case 'glitch':
            obj.set({ shadow: new fabric.Shadow({ color: '#ff0000', blur: 0, offsetX: 3, offsetY: 0 }) });
            break;
        case 'outline-thick':
            obj.set({ stroke: '#000000', strokeWidth: 6, paintFirst: 'stroke' });
            break;
        case 'rainbow-shadow':
            obj.set({ shadow: new fabric.Shadow({ color: '#e74c3c', blur: 0, offsetX: 6, offsetY: 0 }) });
            break;
        case 'none':
        default:
            // Ya reseteado arriba
            break;
    }

    activeCanvas.renderAll();
    const nombresEfecto = { shadow:'Sombra', 'stroke-black':'Contorno negro', 'stroke-white':'Contorno blanco', neon:'Neón verde', 'neon-blue':'Neón azul', 'neon-pink':'Neón rosa', retro:'Retro rojo', 'retro-blue':'Retro azul', ice:'Hielo', fire:'Fuego', gold:'Dorado', glitch:'Glitch', 'outline-only':'Solo contorno', 'fat-stroke':'Contorno grueso', 'rainbow-shadow':'Arcoíris', none:'Efecto eliminado' };
    aviso('Efecto "' + (nombresEfecto[efecto] || efecto) + '" aplicado al texto', 'success');
}
const PLANTILLAS = [
    // ── TEXTO ──
    {
        id: 'best-dad', cat: 'texto', nombre: 'Best Dad',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'texto', texto:'BEST', x:50, y:35, size:22, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'DAD', x:50, y:58, size:28, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'EVER', x:50, y:78, size:14, font:'Montserrat', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'team-bride', cat: 'texto', nombre: 'Team Bride',
        preview: { bg: '#ff79a8', elementos: [{ tipo:'texto', texto:'TEAM', x:50, y:32, size:14, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'BRIDE', x:50, y:58, size:26, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'💍', x:50, y:80, size:20, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'mama', cat: 'texto', nombre: 'La Mejor Mamá',
        preview: { bg: '#ffd6e0', elementos: [{ tipo:'texto', texto:'LA MEJOR', x:50, y:30, size:13, font:'Montserrat', color:'#c0392b', align:'center' }, { tipo:'texto', texto:'MAMÁ', x:50, y:56, size:30, font:'Pacifico', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'❤️', x:50, y:80, size:18, font:'Arial', color:'#e74c3c', align:'center' }] }
    },
    {
        id: 'sport', cat: 'texto', nombre: 'Sport Team',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'texto', texto:'SPORT', x:50, y:30, size:22, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'TEAM', x:50, y:55, size:22, font:'Oswald', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'2025', x:50, y:76, size:12, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'motivacional', cat: 'texto', nombre: 'No Pain No Gain',
        preview: { bg: '#1a1a1a', elementos: [{ tipo:'texto', texto:'NO PAIN', x:50, y:32, size:18, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'NO GAIN', x:50, y:56, size:18, font:'Anton', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'——————', x:50, y:72, size:10, font:'Arial', color:'#555', align:'center' }, { tipo:'texto', texto:'PUSH YOUR LIMITS', x:50, y:84, size:8, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'retro-text', cat: 'texto', nombre: 'Retro Vintage',
        preview: { bg: '#ff6b35', elementos: [{ tipo:'texto', texto:'VINTAGE', x:50, y:32, size:20, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#c0392b',blur:0,ox:4,oy:4} }, { tipo:'texto', texto:'SINCE', x:50, y:54, size:10, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'1990', x:50, y:72, size:24, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#c0392b',blur:0,ox:3,oy:3} }] }
    },
    {
        id: 'neon-text', cat: 'texto', nombre: 'Neón Nights',
        preview: { bg: '#050505', elementos: [{ tipo:'texto', texto:'NEON', x:50, y:35, size:28, font:'Bebas Neue', color:'#39ff14', align:'center', shadow:{color:'#39ff14',blur:20,ox:0,oy:0} }, { tipo:'texto', texto:'NIGHTS', x:50, y:62, size:20, font:'Bebas Neue', color:'#00cfff', align:'center', shadow:{color:'#00cfff',blur:20,ox:0,oy:0} }, { tipo:'texto', texto:'⚡', x:50, y:83, size:16, font:'Arial', color:'#ff2d78', align:'center' }] }
    },
    {
        id: 'nombre-grande', cat: 'texto', nombre: 'Nombre centrado',
        preview: { bg: '#2c3e50', elementos: [{ tipo:'texto', texto:'TU', x:50, y:28, size:14, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:56, size:26, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'AQUÍ', x:50, y:80, size:10, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },

    // ── COMPOSICIÓN ──
    {
        id: 'foto-titulo', cat: 'composicion', nombre: 'Foto + Título',
        preview: { bg: '#f8f9fa', elementos: [{ tipo:'rect', x:50, y:35, w:60, h:40, color:'#ddd', radius:8 }, { tipo:'texto', texto:'📷', x:50, y:35, size:22, font:'Arial', color:'#aaa', align:'center' }, { tipo:'texto', texto:'TU FOTO AQUÍ', x:50, y:72, size:9, font:'Montserrat', color:'#aaa', align:'center' }, { tipo:'texto', texto:'TÍTULO DEL DISEÑO', x:50, y:87, size:9, font:'Anton', color:'#2c3e50', align:'center' }] }
    },
    {
        id: 'foto-marco', cat: 'composicion', nombre: 'Foto con marco',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'rect', x:50, y:42, w:65, h:55, color:'transparent', radius:4, stroke:'#f1c40f', strokeW:3 }, { tipo:'texto', texto:'📷', x:50, y:40, size:22, font:'Arial', color:'rgba(255,255,255,0.2)', align:'center' }, { tipo:'texto', texto:'TU FOTO', x:50, y:79, size:9, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }, { tipo:'texto', texto:'★ NOMBRE ★', x:50, y:91, size:9, font:'Cinzel', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'dos-textos', cat: 'composicion', nombre: 'Foto + 2 textos',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:30, w:55, h:35, color:'#eee', radius:8 }, { tipo:'texto', texto:'📷', x:50, y:29, size:18, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'TÍTULO PRINCIPAL', x:50, y:63, size:9, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'subtítulo secundario', x:50, y:76, size:7, font:'Montserrat', color:'#777', align:'center' }, { tipo:'texto', texto:'www.camiglobo.com', x:50, y:90, size:6, font:'Montserrat', color:'#aaa', align:'center' }] }
    },
    {
        id: 'polaroid', cat: 'composicion', nombre: 'Polaroid',
        preview: { bg: '#f5f0e8', elementos: [{ tipo:'rect', x:50, y:36, w:58, h:48, color:'#fff', radius:2, stroke:'#ddd', strokeW:1 }, { tipo:'rect', x:50, y:33, w:52, h:36, color:'#e0e0e0', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:32, size:18, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'Recuerdo especial', x:50, y:73, size:7, font:'Kalam', color:'#555', align:'center' }] }
    },
    {
        id: 'insignia', cat: 'composicion', nombre: 'Insignia circular',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'circulo', x:50, y:42, r:32, color:'transparent', stroke:'#f1c40f', strokeW:3 }, { tipo:'circulo', x:50, y:42, r:26, color:'transparent', stroke:'rgba(241,196,15,0.3)', strokeW:1 }, { tipo:'texto', texto:'★', x:50, y:32, size:14, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:44, size:12, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'DESDE 2020', x:50, y:57, size:7, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },

    // ── FONDOS ──
    {
        id: 'fondo-negro', cat: 'fondo', nombre: 'Fondo negro',
        preview: { bg: '#0d0d0d', elementos: [] }
    },
    {
        id: 'fondo-blanco', cat: 'fondo', nombre: 'Fondo blanco',
        preview: { bg: '#ffffff', elementos: [] }
    },
    {
        id: 'grad-sunset', cat: 'fondo', nombre: 'Degradado Sunset',
        preview: { bgGrad: ['#ff6b35','#f7c59f'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-ocean', cat: 'fondo', nombre: 'Degradado Océano',
        preview: { bgGrad: ['#1a3a5c','#3498db'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-night', cat: 'fondo', nombre: 'Degradado Noche',
        preview: { bgGrad: ['#0d0d0d','#2c3e50'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-candy', cat: 'fondo', nombre: 'Degradado Candy',
        preview: { bgGrad: ['#fd79a8','#a29bfe'], dir:'to bottom right', elementos: [] }
    },
    {
        id: 'grad-forest', cat: 'fondo', nombre: 'Degradado Bosque',
        preview: { bgGrad: ['#1e5631','#a8e6cf'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-gold', cat: 'fondo', nombre: 'Degradado Dorado',
        preview: { bgGrad: ['#3d2e00','#d4ac0d'], dir:'to bottom', elementos: [] }
    },

    // ── LAYOUT ──
    {
        id: 'layout-centro', cat: 'layout', nombre: 'Centrado',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:50, y:50, w:40, h:30, color:'#ccc', radius:6 }, { tipo:'texto', texto:'CENTRO', x:50, y:50, size:8, font:'Montserrat', color:'#888', align:'center' }] }
    },
    {
        id: 'layout-esquina-sup', cat: 'layout', nombre: 'Esquina superior',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:25, y:22, w:35, h:25, color:'#ccc', radius:6 }, { tipo:'texto', texto:'ARRIBA IZQ', x:25, y:22, size:6, font:'Montserrat', color:'#888', align:'center' }] }
    },
    {
        id: 'layout-esquina-inf', cat: 'layout', nombre: 'Esquina inferior',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:75, y:80, w:35, h:25, color:'#ccc', radius:6 }, { tipo:'texto', texto:'ABAJO DER', x:75, y:80, size:6, font:'Montserrat', color:'#888', align:'center' }] }
    },
    {
        id: 'layout-diagonal', cat: 'layout', nombre: 'Diagonal',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'texto', texto:'DIAGONAL', x:50, y:50, size:12, font:'Anton', color:'#bbb', align:'center', angle:-30 }] }
    },
    {
        id: 'layout-banner-top', cat: 'layout', nombre: 'Banner superior',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:50, y:12, w:100, h:22, color:'#2c3e50', radius:0 }, { tipo:'texto', texto:'BANNER SUPERIOR', x:50, y:12, size:7, font:'Montserrat', color:'#fff', align:'center' }] }
    },
    {
        id: 'layout-banner-bot', cat: 'layout', nombre: 'Banner inferior',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:50, y:90, w:100, h:20, color:'#e74c3c', radius:0 }, { tipo:'texto', texto:'BANNER INFERIOR', x:50, y:90, size:7, font:'Montserrat', color:'#fff', align:'center' }] }
    },
    {
        id: 'layout-tres-zonas', cat: 'layout', nombre: '3 zonas verticales',
        preview: { bg: '#f0f0f0', elementos: [{ tipo:'rect', x:50, y:20, w:100, h:20, color:'#3498db', radius:0 }, { tipo:'rect', x:50, y:50, w:100, h:30, color:'#ecf0f1', radius:0, stroke:'#bdc3c7', strokeW:1 }, { tipo:'rect', x:50, y:82, w:100, h:20, color:'#2c3e50', radius:0 }, { tipo:'texto', texto:'ENCABEZADO', x:50, y:20, size:6, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'📷 imagen', x:50, y:50, size:8, font:'Arial', color:'#aaa', align:'center' }, { tipo:'texto', texto:'TEXTO PIE', x:50, y:82, size:6, font:'Montserrat', color:'#fff', align:'center' }] }
    },

    // ── TEXTO (más plantillas) ──
    {
        id: 'amor-eterno', cat: 'texto', nombre: 'Amor Eterno',
        preview: { bg: '#1a0510', elementos: [{ tipo:'texto', texto:'AMOR', x:50, y:32, size:26, font:'Pacifico', color:'#ff2d78', align:'center', shadow:{color:'#ff2d78',blur:18,ox:0,oy:0} }, { tipo:'texto', texto:'ETERNO', x:50, y:58, size:18, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'♾️', x:50, y:80, size:16, font:'Arial', color:'#ff2d78', align:'center' }] }
    },
    {
        id: 'wild-free', cat: 'texto', nombre: 'Wild & Free',
        preview: { bgGrad: ['#1e5631','#f39c12'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'WILD', x:50, y:35, size:24, font:'Permanent Marker', color:'#fff', align:'center', shadow:{color:'#000',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'&', x:50, y:52, size:14, font:'Kalam', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'FREE', x:50, y:70, size:24, font:'Permanent Marker', color:'#f1c40f', align:'center', shadow:{color:'#000',blur:6,ox:3,oy:3} }] }
    },
    {
        id: 'born-rebel', cat: 'texto', nombre: 'Born to Rebel',
        preview: { bg: '#0d0d0d', elementos: [{ tipo:'texto', texto:'BORN', x:50, y:28, size:22, font:'Bebas Neue', color:'#e74c3c', align:'center', shadow:{color:'#e74c3c',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'TO BE', x:50, y:47, size:16, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'REBEL', x:50, y:68, size:22, font:'Bebas Neue', color:'#e74c3c', align:'center', shadow:{color:'#e74c3c',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'✊', x:50, y:84, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'cafe-letras', cat: 'texto', nombre: 'Café & Letras',
        preview: { bg: '#3e1f00', elementos: [{ tipo:'texto', texto:'☕', x:50, y:22, size:20, font:'Arial', color:'#d4a574', align:'center' }, { tipo:'texto', texto:'BUT FIRST', x:50, y:46, size:14, font:'Oswald', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'COFFEE', x:50, y:65, size:22, font:'Anton', color:'#d4a574', align:'center', shadow:{color:'#000',blur:4,ox:2,oy:2} }, { tipo:'texto', texto:'——', x:50, y:79, size:14, font:'Arial', color:'#795548', align:'center' }] }
    },
    {
        id: 'good-vibes', cat: 'texto', nombre: 'Good Vibes',
        preview: { bgGrad: ['#fd79a8','#fdcb6e'], dir:'to right', elementos: [{ tipo:'texto', texto:'GOOD', x:50, y:34, size:22, font:'Righteous', color:'#fff', align:'center' }, { tipo:'texto', texto:'VIBES', x:50, y:57, size:22, font:'Righteous', color:'#fff', align:'center' }, { tipo:'texto', texto:'ONLY ✨', x:50, y:78, size:12, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },
    {
        id: 'hustle', cat: 'texto', nombre: 'Hustle Hard',
        preview: { bg: '#111', elementos: [{ tipo:'texto', texto:'HUSTLE', x:50, y:35, size:22, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'HARD', x:50, y:58, size:22, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'DREAM BIG', x:50, y:78, size:9, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'savage', cat: 'texto', nombre: 'Savage Mode',
        preview: { bg: '#0a0a0a', elementos: [{ tipo:'texto', texto:'SAVAGE', x:50, y:40, size:24, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#ff0000',blur:0,ox:4,oy:4} }, { tipo:'texto', texto:'MODE', x:50, y:62, size:18, font:'Bebas Neue', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'🔥 ON 🔥', x:50, y:80, size:12, font:'Arial', color:'#e74c3c', align:'center' }] }
    },
    {
        id: 'blessed', cat: 'texto', nombre: 'Blessed',
        preview: { bgGrad: ['#d4ac0d','#3d2e00'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'✨', x:50, y:20, size:16, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'BLESSED', x:50, y:46, size:24, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'& GRATEFUL', x:50, y:67, size:11, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'✨', x:50, y:83, size:16, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'bad-witch', cat: 'texto', nombre: 'Bad Witch',
        preview: { bg: '#1a001a', elementos: [{ tipo:'texto', texto:'🧙‍♀️', x:50, y:20, size:20, font:'Arial', color:'#9b59b6', align:'center' }, { tipo:'texto', texto:'BAD', x:50, y:44, size:22, font:'Bebas Neue', color:'#9b59b6', align:'center', shadow:{color:'#9b59b6',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'WITCH', x:50, y:63, size:22, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'🌙 ⭐ 🌙', x:50, y:81, size:12, font:'Arial', color:'#9b59b6', align:'center' }] }
    },
    {
        id: 'king-queen', cat: 'texto', nombre: 'King / Queen',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'texto', texto:'👑', x:50, y:20, size:20, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'KING', x:50, y:45, size:26, font:'Oswald', color:'#f1c40f', align:'center', shadow:{color:'#d4ac0d',blur:8,ox:0,oy:0} }, { tipo:'texto', texto:'OF THE', x:50, y:64, size:11, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }, { tipo:'texto', texto:'WORLD', x:50, y:80, size:16, font:'Oswald', color:'#fff', align:'center' }] }
    },
    {
        id: 'made-in', cat: 'texto', nombre: 'Made in…',
        preview: { bg: '#2c3e50', elementos: [{ tipo:'texto', texto:'MADE IN', x:50, y:32, size:14, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'SPAIN', x:50, y:57, size:28, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'🇪🇸', x:50, y:79, size:18, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'positivity', cat: 'texto', nombre: 'Be Positive',
        preview: { bgGrad: ['#00b894','#55efc4'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'BE', x:50, y:30, size:20, font:'Righteous', color:'#fff', align:'center' }, { tipo:'texto', texto:'POSITIVE', x:50, y:53, size:20, font:'Righteous', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'☀️ ALWAYS ☀️', x:50, y:76, size:10, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },

    // ── COMPOSICIÓN (más plantillas) ──
    {
        id: 'foto-redonda', cat: 'composicion', nombre: 'Foto circular',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'circulo', x:50, y:38, r:28, color:'#333', stroke:'#3498db', strokeW:3 }, { tipo:'texto', texto:'📷', x:50, y:37, size:18, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'TU NOMBRE', x:50, y:70, size:10, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'★ TÍTULO ★', x:50, y:84, size:8, font:'Montserrat', color:'#3498db', align:'center' }] }
    },
    {
        id: 'foto-collage', cat: 'composicion', nombre: 'Collage 2 fotos',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:27, y:33, w:45, h:38, color:'#eee', radius:4, stroke:'#ddd', strokeW:1 }, { tipo:'rect', x:73, y:33, w:45, h:38, color:'#eee', radius:4, stroke:'#ddd', strokeW:1 }, { tipo:'texto', texto:'📷', x:27, y:33, size:14, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'📷', x:73, y:33, size:14, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'RECUERDOS', x:50, y:72, size:9, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'para siempre', x:50, y:84, size:8, font:'Satisfy', color:'#777', align:'center' }] }
    },
    {
        id: 'foto-texto-lado', cat: 'composicion', nombre: 'Foto + texto lateral',
        preview: { bg: '#f8f9fa', elementos: [{ tipo:'rect', x:27, y:40, w:45, h:52, color:'#ddd', radius:6 }, { tipo:'texto', texto:'📷', x:27, y:40, size:16, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'TÍTULO', x:73, y:28, size:12, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'Subtítulo del', x:73, y:44, size:8, font:'Montserrat', color:'#555', align:'center' }, { tipo:'texto', texto:'diseño aquí', x:73, y:54, size:8, font:'Montserrat', color:'#555', align:'center' }, { tipo:'texto', texto:'camiglobo.com', x:73, y:78, size:6, font:'Montserrat', color:'#aaa', align:'center' }] }
    },
    {
        id: 'foto-diamond', cat: 'composicion', nombre: 'Foto rombo',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'texto', texto:'◆', x:50, y:36, size:38, font:'Arial', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'📷', x:50, y:34, size:16, font:'Arial', color:'rgba(255,255,255,0.2)', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:64, size:12, font:'Bebas Neue', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'2025', x:50, y:79, size:10, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'vintage-photo', cat: 'composicion', nombre: 'Foto vintage',
        preview: { bg: '#f5e6c8', elementos: [{ tipo:'rect', x:50, y:35, w:62, h:46, color:'#e8d5b0', radius:0, stroke:'#c8a96e', strokeW:2 }, { tipo:'rect', x:50, y:32, w:56, h:38, color:'#ddd', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:31, size:16, font:'Arial', color:'#999', align:'center' }, { tipo:'texto', texto:'~ Memories ~', x:50, y:69, size:8, font:'Dancing Script', color:'#795548', align:'center' }, { tipo:'texto', texto:'Est. 2025', x:50, y:81, size:8, font:'Kalam', color:'#a0522d', align:'center' }] }
    },
    {
        id: 'foto-badge-round', cat: 'composicion', nombre: 'Badge redondo',
        preview: { bg: '#e74c3c', elementos: [{ tipo:'circulo', x:50, y:38, r:30, color:'transparent', stroke:'#fff', strokeW:3 }, { tipo:'circulo', x:50, y:38, r:24, color:'transparent', stroke:'rgba(255,255,255,0.4)', strokeW:1 }, { tipo:'texto', texto:'📷', x:50, y:36, size:16, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'TU FOTO', x:50, y:36, size:7, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }, { tipo:'texto', texto:'CAMPEÓN', x:50, y:72, size:10, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'★★★', x:50, y:84, size:10, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'foto-strip', cat: 'composicion', nombre: 'Tira de fotos',
        preview: { bg: '#222', elementos: [{ tipo:'rect', x:50, y:22, w:65, h:18, color:'#444', radius:2 }, { tipo:'rect', x:50, y:42, w:65, h:18, color:'#444', radius:2 }, { tipo:'rect', x:50, y:62, w:65, h:18, color:'#444', radius:2 }, { tipo:'texto', texto:'📷', x:50, y:22, size:10, font:'Arial', color:'#888', align:'center' }, { tipo:'texto', texto:'📷', x:50, y:42, size:10, font:'Arial', color:'#888', align:'center' }, { tipo:'texto', texto:'📷', x:50, y:62, size:10, font:'Arial', color:'#888', align:'center' }, { tipo:'texto', texto:'BEST MOMENTS', x:50, y:83, size:7, font:'Montserrat', color:'#aaa', align:'center' }] }
    },
    {
        id: 'foto-revista', cat: 'composicion', nombre: 'Portada revista',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:10, w:100, h:15, color:'#e74c3c', radius:0 }, { tipo:'texto', texto:'CAMIGLOBO', x:50, y:10, size:8, font:'Anton', color:'#fff', align:'center' }, { tipo:'rect', x:50, y:48, w:80, h:55, color:'#eee', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:47, size:22, font:'Arial', color:'#ccc', align:'center' }, { tipo:'texto', texto:'EDICIÓN ESPECIAL', x:50, y:84, size:7, font:'Montserrat', color:'#e74c3c', align:'center' }] }
    },

    // ── FONDO (más plantillas) ──
    {
        id: 'fondo-rojo', cat: 'fondo', nombre: 'Fondo rojo',
        preview: { bg: '#e74c3c', elementos: [] }
    },
    {
        id: 'fondo-azul-marino', cat: 'fondo', nombre: 'Azul marino',
        preview: { bg: '#1a3a5c', elementos: [] }
    },
    {
        id: 'fondo-verde', cat: 'fondo', nombre: 'Verde oscuro',
        preview: { bg: '#1e5631', elementos: [] }
    },
    {
        id: 'fondo-morado', cat: 'fondo', nombre: 'Morado',
        preview: { bg: '#6c3483', elementos: [] }
    },
    {
        id: 'fondo-rosa', cat: 'fondo', nombre: 'Rosa fuerte',
        preview: { bg: '#fd79a8', elementos: [] }
    },
    {
        id: 'fondo-naranja', cat: 'fondo', nombre: 'Naranja',
        preview: { bg: '#e67e22', elementos: [] }
    },
    {
        id: 'fondo-turquesa', cat: 'fondo', nombre: 'Turquesa',
        preview: { bg: '#1abc9c', elementos: [] }
    },
    {
        id: 'fondo-gris', cat: 'fondo', nombre: 'Gris oscuro',
        preview: { bg: '#4a4a4a', elementos: [] }
    },
    {
        id: 'grad-fire', cat: 'fondo', nombre: 'Degradado Fuego',
        preview: { bgGrad: ['#e74c3c','#f39c12'], dir:'to bottom right', elementos: [] }
    },
    {
        id: 'grad-purple', cat: 'fondo', nombre: 'Degradado Morado',
        preview: { bgGrad: ['#6c3483','#a29bfe'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-mint', cat: 'fondo', nombre: 'Degradado Menta',
        preview: { bgGrad: ['#00b894','#55efc4'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-rose', cat: 'fondo', nombre: 'Degradado Rosa',
        preview: { bgGrad: ['#fd79a8','#ffeaa7'], dir:'to bottom right', elementos: [] }
    },
    {
        id: 'grad-dark', cat: 'fondo', nombre: 'Degradado Dark',
        preview: { bgGrad: ['#000000','#2d3436'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-galaxy', cat: 'fondo', nombre: 'Degradado Galaxia',
        preview: { bgGrad: ['#050520','#6c5ce7'], dir:'to bottom right', elementos: [] }
    },
    {
        id: 'grad-lava', cat: 'fondo', nombre: 'Degradado Lava',
        preview: { bgGrad: ['#1a0000','#e74c3c'], dir:'to bottom', elementos: [] }
    },
    {
        id: 'grad-ice2', cat: 'fondo', nombre: 'Degradado Hielo',
        preview: { bgGrad: ['#d6f0ff','#ffffff'], dir:'to bottom', elementos: [] }
    },

    // ── LAYOUT (más plantillas) ──
    {
        id: 'layout-texto-grande', cat: 'layout', nombre: 'Texto gigante',
        preview: { bg: '#fff', elementos: [{ tipo:'texto', texto:'XXL', x:50, y:50, size:38, font:'Anton', color:'#f0f0f0', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:50, size:18, font:'Oswald', color:'#2c3e50', align:'center' }] }
    },
    {
        id: 'layout-arco', cat: 'layout', nombre: 'Texto en arco',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'circulo', x:50, y:50, r:38, color:'transparent', stroke:'rgba(255,255,255,0.15)', strokeW:1 }, { tipo:'texto', texto:'★ TU TEXTO AQUÍ ★', x:50, y:20, size:8, font:'Montserrat', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:50, size:16, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'————————', x:50, y:68, size:8, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'SINCE 2025', x:50, y:78, size:7, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'layout-minimalista', cat: 'layout', nombre: 'Minimalista',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:48, w:30, h:2, color:'#2c3e50', radius:1 }, { tipo:'texto', texto:'NOMBRE', x:50, y:40, size:16, font:'Cinzel', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'subtítulo', x:50, y:60, size:9, font:'Montserrat', color:'#aaa', align:'center' }] }
    },
    {
        id: 'layout-sello', cat: 'layout', nombre: 'Sello oficial',
        preview: { bg: '#fff9e6', elementos: [{ tipo:'circulo', x:50, y:44, r:36, color:'transparent', stroke:'#d4ac0d', strokeW:3 }, { tipo:'circulo', x:50, y:44, r:30, color:'transparent', stroke:'#d4ac0d', strokeW:1 }, { tipo:'texto', texto:'OFICIAL', x:50, y:34, size:10, font:'Cinzel', color:'#d4ac0d', align:'center' }, { tipo:'texto', texto:'CAMIGLOBO', x:50, y:48, size:9, font:'Oswald', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'★ 2025 ★', x:50, y:60, size:8, font:'Montserrat', color:'#d4ac0d', align:'center' }] }
    },
    {
        id: 'layout-cinta', cat: 'layout', nombre: 'Cinta diagonal',
        preview: { bg: '#f8f9fa', elementos: [{ tipo:'rect', x:50, y:50, w:150, h:20, color:'#e74c3c', radius:0, angle:-35 }, { tipo:'texto', texto:'OFERTA ESPECIAL', x:50, y:50, size:8, font:'Anton', color:'#fff', align:'center', angle:-35 }, { tipo:'texto', texto:'NOMBRE', x:50, y:80, size:14, font:'Oswald', color:'#2c3e50', align:'center' }] }
    },
    {
        id: 'layout-doble-linea', cat: 'layout', nombre: 'Doble línea',
        preview: { bg: '#2c3e50', elementos: [{ tipo:'rect', x:50, y:38, w:80, h:2, color:'#f1c40f', radius:1 }, { tipo:'rect', x:50, y:62, w:80, h:2, color:'#f1c40f', radius:1 }, { tipo:'texto', texto:'TU DISEÑO', x:50, y:50, size:14, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'AQUÍ', x:50, y:50, size:14, font:'Oswald', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'layout-columnas', cat: 'layout', nombre: 'Dos columnas',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:27, y:50, w:45, h:80, color:'#2c3e50', radius:0 }, { tipo:'rect', x:73, y:50, w:45, h:80, color:'#e74c3c', radius:0 }, { tipo:'texto', texto:'LEFT', x:27, y:50, size:10, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'RIGHT', x:73, y:50, size:10, font:'Anton', color:'#fff', align:'center' }] }
    },
    {
        id: 'layout-ticket', cat: 'layout', nombre: 'Ticket / Entrada',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:20, w:90, h:18, color:'#2c3e50', radius:0 }, { tipo:'texto', texto:'★ EVENTO ESPECIAL ★', x:50, y:20, size:6, font:'Montserrat', color:'#f1c40f', align:'center' }, { tipo:'rect', x:50, y:50, w:90, h:40, color:'#f8f9fa', radius:0, stroke:'#eee', strokeW:1 }, { tipo:'texto', texto:'📷', x:30, y:50, size:14, font:'Arial', color:'#ddd', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:68, y:44, size:11, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'FILA A · ASIENTO 12', x:68, y:58, size:6, font:'Montserrat', color:'#777', align:'center' }, { tipo:'rect', x:50, y:82, w:90, h:16, color:'#e74c3c', radius:0 }, { tipo:'texto', texto:'NO REEMBOLSABLE', x:50, y:82, size:6, font:'Montserrat', color:'#fff', align:'center' }] }
    },
    {
        id: 'layout-splash', cat: 'layout', nombre: 'Splash central',
        preview: { bg: '#fff', elementos: [{ tipo:'circulo', x:50, y:44, r:40, color:'#f1c40f', stroke:'none', strokeW:0 }, { tipo:'texto', texto:'WOW!', x:50, y:44, size:22, font:'Bungee', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'TEXTO AQUÍ', x:50, y:80, size:9, font:'Montserrat', color:'#2c3e50', align:'center' }] }
    },

    {
        id: 'ano-nuevo', cat: 'eventos', nombre: 'Año Nuevo',
        preview: { bg: '#050520', elementos: [{ tipo:'texto', texto:'🎆', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FELIZ', x:50, y:43, size:16, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'2026', x:50, y:63, size:26, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'✨ NEW YEAR ✨', x:50, y:82, size:8, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'dia-mama', cat: 'eventos', nombre: 'Día de la Madre',
        preview: { bgGrad: ['#ffd6e0','#ff79a8'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌹', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FELIZ DÍA', x:50, y:44, size:14, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'MAMÁ', x:50, y:62, size:26, font:'Pacifico', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'te quiero ❤️', x:50, y:81, size:10, font:'Dancing Script', color:'#7b241c', align:'center' }] }
    },
    {
        id: 'dia-papa', cat: 'eventos', nombre: 'Día del Padre',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'texto', texto:'🛠️', x:50, y:20, size:20, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'FELIZ DÍA', x:50, y:44, size:14, font:'Oswald', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'PAPÁ', x:50, y:62, size:26, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'el mejor del mundo', x:50, y:80, size:9, font:'Kalam', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'primer-cumple', cat: 'eventos', nombre: '1er Cumpleaños',
        preview: { bgGrad: ['#a29bfe','#fd79a8'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🎂', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'MI PRIMER', x:50, y:43, size:12, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'AÑO', x:50, y:60, size:28, font:'Bungee', color:'#fff', align:'center' }, { tipo:'texto', texto:'🎉 🥳 🎊', x:50, y:80, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'aniversario', cat: 'eventos', nombre: 'Aniversario',
        preview: { bgGrad: ['#7b241c','#e74c3c'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'💍', x:50, y:20, size:20, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'ANIVERSARIO', x:50, y:44, size:14, font:'Cinzel', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'25', x:50, y:63, size:30, font:'Anton', color:'#fff', align:'center', shadow:{color:'#f1c40f',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'AÑOS JUNTOS', x:50, y:82, size:9, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }] }
    },
    {
        id: 'reyes', cat: 'eventos', nombre: 'Reyes Magos',
        preview: { bg: '#1a0a2e', elementos: [{ tipo:'texto', texto:'⭐', x:50, y:18, size:18, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'REYES', x:50, y:42, size:22, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'MAGOS', x:50, y:61, size:22, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'🎁 👑 🐪', x:50, y:80, size:14, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'jubilacion', cat: 'eventos', nombre: 'Jubilación',
        preview: { bgGrad: ['#1abc9c','#f1c40f'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🎉', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'¡ME JUBILO!', x:50, y:44, size:16, font:'Anton', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'OFICIALMENTE LIBRE', x:50, y:64, size:9, font:'Montserrat', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'🏖️ 😎', x:50, y:81, size:16, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'vuelta-cole', cat: 'eventos', nombre: 'Vuelta al cole',
        preview: { bg: '#3498db', elementos: [{ tipo:'texto', texto:'🎒', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'BACK TO', x:50, y:43, size:14, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'SCHOOL', x:50, y:62, size:22, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'✏️ 📚 ✏️', x:50, y:81, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },

    // ── DEPORTE (más plantillas) ──
    {
        id: 'tenis', cat: 'deporte', nombre: 'Tenis',
        preview: { bg: '#2ecc71', elementos: [{ tipo:'texto', texto:'🎾', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'MATCH', x:50, y:45, size:22, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'POINT', x:50, y:64, size:22, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:4,ox:2,oy:2} }, { tipo:'texto', texto:'🏆', x:50, y:82, size:16, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'natacion', cat: 'deporte', nombre: 'Natación',
        preview: { bgGrad: ['#006994','#1abc9c'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🏊', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'SWIM', x:50, y:45, size:24, font:'Anton', color:'#fff', align:'center', shadow:{color:'#00cfff',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'OR SINK', x:50, y:64, size:16, font:'Bebas Neue', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'🌊', x:50, y:82, size:18, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'baloncesto', cat: 'deporte', nombre: 'Baloncesto',
        preview: { bgGrad: ['#e67e22','#1a1a1a'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🏀', x:50, y:20, size:22, font:'Arial', color:'#e67e22', align:'center' }, { tipo:'texto', texto:'NOTHING', x:50, y:43, size:14, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'BUT NET', x:50, y:62, size:20, font:'Anton', color:'#e67e22', align:'center', shadow:{color:'#e67e22',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'⭐ CHAMPION ⭐', x:50, y:81, size:8, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'yoga', cat: 'deporte', nombre: 'Yoga',
        preview: { bgGrad: ['#a8e6cf','#1abc9c'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🧘', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FIND YOUR', x:50, y:43, size:12, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'BALANCE', x:50, y:62, size:18, font:'Satisfy', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.2)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'☮️ namaste ☮️', x:50, y:82, size:9, font:'Kalam', color:'rgba(255,255,255,0.85)', align:'center' }] }
    },
    {
        id: 'crossfit', cat: 'deporte', nombre: 'CrossFit',
        preview: { bg: '#0a0a0a', elementos: [{ tipo:'texto', texto:'⚡', x:50, y:18, size:18, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'CROSS', x:50, y:42, size:22, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'FIT', x:50, y:60, size:26, font:'Bebas Neue', color:'#e74c3c', align:'center', shadow:{color:'#e74c3c',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'NO EXCUSES', x:50, y:80, size:9, font:'Oswald', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'ski', cat: 'deporte', nombre: 'Ski',
        preview: { bgGrad: ['#87ceeb','#fff'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'⛷️', x:50, y:20, size:22, font:'Arial', color:'#1a3a5c', align:'center' }, { tipo:'texto', texto:'SKI', x:50, y:46, size:30, font:'Anton', color:'#1a3a5c', align:'center' }, { tipo:'texto', texto:'SEASON', x:50, y:66, size:16, font:'Montserrat', color:'#3498db', align:'center' }, { tipo:'texto', texto:'❄️ 2025 ❄️', x:50, y:82, size:10, font:'Arial', color:'#3498db', align:'center' }] }
    },
    {
        id: 'hiking', cat: 'deporte', nombre: 'Hiking',
        preview: { bgGrad: ['#2c3e50','#1e5631'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🥾', x:50, y:20, size:22, font:'Arial', color:'#a8e6cf', align:'center' }, { tipo:'texto', texto:'HIKE', x:50, y:44, size:24, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'MORE', x:50, y:63, size:24, font:'Anton', color:'#a8e6cf', align:'center' }, { tipo:'texto', texto:'worry less', x:50, y:82, size:10, font:'Kalam', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'padel', cat: 'deporte', nombre: 'Pádel',
        preview: { bgGrad: ['#27ae60','#2ecc71'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🏓', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'PÁDEL', x:50, y:46, size:24, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'LOVER', x:50, y:65, size:16, font:'Bebas Neue', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'⭐ PRO PLAYER ⭐', x:50, y:83, size:8, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }] }
    },
    {
        id: 'moto', cat: 'deporte', nombre: 'Moto / Biker',
        preview: { bg: '#111', elementos: [{ tipo:'texto', texto:'🏍️', x:50, y:20, size:22, font:'Arial', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'BORN TO', x:50, y:43, size:14, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'RIDE', x:50, y:63, size:28, font:'Anton', color:'#e74c3c', align:'center', shadow:{color:'#e74c3c',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'🔥 BIKER LIFE 🔥', x:50, y:83, size:8, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'escalada', cat: 'deporte', nombre: 'Escalada',
        preview: { bgGrad: ['#795548','#4a2800'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🧗', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'CLIMB', x:50, y:45, size:24, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'HIGHER', x:50, y:63, size:20, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'⛰️ PEAK SEEKER', x:50, y:82, size:8, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },

    // ── NATURALEZA (más plantillas) ──
    {
        id: 'playa', cat: 'naturaleza', nombre: 'Playa',
        preview: { bgGrad: ['#fdcb6e','#e17055'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌅', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'BEACH', x:50, y:45, size:24, font:'Pacifico', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'PLEASE ☀️', x:50, y:66, size:12, font:'Satisfy', color:'rgba(255,255,255,0.9)', align:'center' }, { tipo:'texto', texto:'🌊 🐚 🌊', x:50, y:82, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'luna', cat: 'naturaleza', nombre: 'Luna llena',
        preview: { bgGrad: ['#0d0d30','#1a1a5e'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌕', x:50, y:20, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'LUNA', x:50, y:46, size:24, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'LLENA', x:50, y:64, size:18, font:'Cinzel', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'✨ 🌟 ✨', x:50, y:82, size:14, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'tormenta', cat: 'naturaleza', nombre: 'Tormenta',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'texto', texto:'⛈️', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'STORM', x:50, y:45, size:24, font:'Anton', color:'#00cfff', align:'center', shadow:{color:'#00cfff',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'IS COMING', x:50, y:64, size:14, font:'Bebas Neue', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'⚡⚡⚡', x:50, y:82, size:16, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'arctic', cat: 'naturaleza', nombre: 'Ártico',
        preview: { bgGrad: ['#d6f0ff','#3498db'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'❄️', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'ARCTIC', x:50, y:45, size:22, font:'Anton', color:'#fff', align:'center', shadow:{color:'#00cfff',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'EXPLORER', x:50, y:64, size:14, font:'Montserrat', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'🐻‍❄️ ICE 🐻‍❄️', x:50, y:82, size:11, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'selva', cat: 'naturaleza', nombre: 'Selva tropical',
        preview: { bgGrad: ['#1e5631','#00b894'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🦜', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'JUNGLE', x:50, y:45, size:22, font:'Bungee', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.4)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'VIBES', x:50, y:64, size:16, font:'Pacifico', color:'#fff', align:'center' }, { tipo:'texto', texto:'🌿 🍃 🌿', x:50, y:82, size:14, font:'Arial', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },
    {
        id: 'atardecer', cat: 'naturaleza', nombre: 'Atardecer',
        preview: { bgGrad: ['#ff6b35','#9b59b6'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌇', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'GOLDEN', x:50, y:44, size:20, font:'Pacifico', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.2)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'HOUR', x:50, y:63, size:20, font:'Pacifico', color:'#fff', align:'center' }, { tipo:'texto', texto:'🌅 magical 🌅', x:50, y:82, size:9, font:'Satisfy', color:'rgba(255,255,255,0.85)', align:'center' }] }
    },
    {
        id: 'volcan', cat: 'naturaleza', nombre: 'Volcán',
        preview: { bgGrad: ['#1a0000','#e74c3c'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌋', x:50, y:20, size:22, font:'Arial', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'FIRE', x:50, y:44, size:26, font:'Anton', color:'#ff6b00', align:'center', shadow:{color:'#ff6b00',blur:16,ox:0,oy:-4} }, { tipo:'texto', texto:'INSIDE', x:50, y:64, size:16, font:'Bebas Neue', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'🔥 🔥 🔥', x:50, y:82, size:16, font:'Arial', color:'#e67e22', align:'center' }] }
    },
    {
        id: 'aguila', cat: 'naturaleza', nombre: 'Águila',
        preview: { bgGrad: ['#2c3e50','#1a3a5c'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🦅', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FREEDOM', x:50, y:45, size:20, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'FLY HIGH', x:50, y:64, size:14, font:'Montserrat', color:'#74b9ff', align:'center' }, { tipo:'texto', texto:'—— soar ——', x:50, y:81, size:9, font:'Satisfy', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'sakura', cat: 'naturaleza', nombre: 'Sakura',
        preview: { bgGrad: ['#ffd6e0','#ff79a8'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌸', x:25, y:20, size:16, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'🌸', x:75, y:20, size:16, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'SAKURA', x:50, y:46, size:22, font:'Cinzel', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'春', x:50, y:66, size:20, font:'Arial', color:'rgba(123,36,28,0.5)', align:'center' }, { tipo:'texto', texto:'🌸 春の花 🌸', x:50, y:83, size:9, font:'Arial', color:'#7b241c', align:'center' }] }
    },
    // ── EVENTOS originales ──
    {
        id: 'cumpleanos', cat: 'eventos', nombre: 'Cumpleaños',
        preview: { bgGrad: ['#ff79a8','#fdcb6e'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🎂', x:50, y:25, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'¡FELIZ', x:50, y:48, size:18, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'CUMPLEAÑOS!', x:50, y:65, size:14, font:'Bebas Neue', color:'#fff4', align:'center' }, { tipo:'texto', texto:'🎉🎉🎉', x:50, y:82, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'boda', cat: 'eventos', nombre: 'Boda',
        preview: { bg: '#fff9f0', elementos: [{ tipo:'texto', texto:'💍', x:50, y:22, size:20, font:'Arial', color:'#c0392b', align:'center' }, { tipo:'texto', texto:'Mr & Mrs', x:50, y:45, size:20, font:'Playfair Display', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'Para siempre', x:50, y:64, size:10, font:'Dancing Script', color:'#c0392b', align:'center' }, { tipo:'texto', texto:'✦ ✦ ✦', x:50, y:80, size:10, font:'Arial', color:'#d4ac0d', align:'center' }] }
    },
    {
        id: 'navidad', cat: 'eventos', nombre: 'Navidad',
        preview: { bgGrad: ['#1e5631','#2ecc71'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🎄', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FELIZ', x:50, y:46, size:18, font:'Bebas Neue', color:'#f1c40f', align:'center', shadow:{color:'#c0392b',blur:0,ox:3,oy:3} }, { tipo:'texto', texto:'NAVIDAD', x:50, y:62, size:18, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'⭐', x:50, y:79, size:14, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'halloween', cat: 'eventos', nombre: 'Halloween',
        preview: { bg: '#0d0d0d', elementos: [{ tipo:'texto', texto:'🎃', x:50, y:22, size:22, font:'Arial', color:'#e67e22', align:'center' }, { tipo:'texto', texto:'TRICK OR', x:50, y:47, size:16, font:'Bebas Neue', color:'#e67e22', align:'center', shadow:{color:'#e67e22',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'TREAT', x:50, y:64, size:20, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#e67e22',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'👻', x:50, y:81, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'san-valentin', cat: 'eventos', nombre: 'San Valentín',
        preview: { bgGrad: ['#e74c3c','#fd79a8'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'❤️', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'TE QUIERO', x:50, y:46, size:16, font:'Pacifico', color:'#fff', align:'center' }, { tipo:'texto', texto:'más que ayer', x:50, y:64, size:10, font:'Dancing Script', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'💕', x:50, y:80, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'graduacion', cat: 'eventos', nombre: 'Graduación',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'texto', texto:'🎓', x:50, y:22, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'GRADUADO', x:50, y:46, size:16, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'2025', x:50, y:63, size:22, font:'Bebas Neue', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'¡Lo logramos!', x:50, y:80, size:9, font:'Kalam', color:'rgba(255,255,255,0.7)', align:'center' }] }
    },
    {
        id: 'despedida', cat: 'eventos', nombre: 'Despedida soltera',
        preview: { bgGrad: ['#9b59b6','#fd79a8'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'👰', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'LAST NIGHT', x:50, y:45, size:14, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'OF FREEDOM', x:50, y:60, size:14, font:'Montserrat', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'💃', x:50, y:79, size:18, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'baby-shower', cat: 'eventos', nombre: 'Baby Shower',
        preview: { bg: '#d6f0ff', elementos: [{ tipo:'texto', texto:'👶', x:50, y:22, size:22, font:'Arial', color:'#3498db', align:'center' }, { tipo:'texto', texto:'BABY', x:50, y:46, size:20, font:'Pacifico', color:'#3498db', align:'center' }, { tipo:'texto', texto:'SHOWER', x:50, y:63, size:16, font:'Pacifico', color:'#1a3a5c', align:'center' }, { tipo:'texto', texto:'🍼 🐣 🍼', x:50, y:80, size:10, font:'Arial', color:'#3498db', align:'center' }] }
    },

    // ── DEPORTE ──
    {
        id: 'futbol', cat: 'deporte', nombre: 'Fútbol',
        preview: { bg: '#1e5631', elementos: [{ tipo:'texto', texto:'⚽', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'CAMPEONES', x:50, y:46, size:14, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#000',blur:4,ox:2,oy:2} }, { tipo:'texto', texto:'DEL MUNDO', x:50, y:62, size:12, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'⭐⭐⭐', x:50, y:80, size:12, font:'Arial', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'running', cat: 'deporte', nombre: 'Running',
        preview: { bgGrad: ['#1a1a1a','#e74c3c'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🏃', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'NEVER', x:50, y:45, size:18, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'STOP', x:50, y:62, size:22, font:'Anton', color:'#e74c3c', align:'center', shadow:{color:'#e74c3c',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'RUNNING', x:50, y:80, size:10, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'gym', cat: 'deporte', nombre: 'Gym',
        preview: { bg: '#111', elementos: [{ tipo:'texto', texto:'💪', x:50, y:22, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'BEAST', x:50, y:46, size:22, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#f1c40f',blur:8,ox:0,oy:0} }, { tipo:'texto', texto:'MODE ON', x:50, y:64, size:16, font:'Bebas Neue', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'——————', x:50, y:77, size:10, font:'Arial', color:'#333', align:'center' }, { tipo:'texto', texto:'TRAIN HARD', x:50, y:86, size:7, font:'Montserrat', color:'rgba(255,255,255,0.3)', align:'center' }] }
    },
    {
        id: 'ciclismo', cat: 'deporte', nombre: 'Ciclismo',
        preview: { bgGrad: ['#2c3e50','#3498db'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🚴', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'RIDE', x:50, y:47, size:22, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'OR DIE', x:50, y:64, size:16, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#000',blur:4,ox:2,oy:2} }, { tipo:'texto', texto:'🚵 🏔️', x:50, y:80, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'surf', cat: 'deporte', nombre: 'Surf',
        preview: { bgGrad: ['#006994','#00cfff'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🏄', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'SURF', x:50, y:46, size:24, font:'Pacifico', color:'#fff', align:'center', shadow:{color:'#00cfff',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'& CHILL', x:50, y:65, size:14, font:'Satisfy', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'🌊', x:50, y:80, size:18, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'boxeo', cat: 'deporte', nombre: 'Boxeo',
        preview: { bg: '#111', elementos: [{ tipo:'texto', texto:'🥊', x:50, y:22, size:22, font:'Arial', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'FIGHT', x:50, y:46, size:24, font:'Anton', color:'#fff', align:'center', shadow:{color:'#e74c3c',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'NIGHT', x:50, y:64, size:18, font:'Anton', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'▀▀▀▀▀▀▀', x:50, y:78, size:10, font:'Arial', color:'#333', align:'center' }, { tipo:'texto', texto:'NO PAIN NO GLORY', x:50, y:87, size:6, font:'Montserrat', color:'rgba(255,255,255,0.3)', align:'center' }] }
    },

    // ── NATURALEZA ──
    {
        id: 'bosque', cat: 'naturaleza', nombre: 'Bosque',
        preview: { bgGrad: ['#1e5631','#a8e6cf'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌲', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FOREST', x:50, y:46, size:20, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'VIBES', x:50, y:63, size:14, font:'Satisfy', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'🍃 🌿 🍃', x:50, y:80, size:12, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'montana', cat: 'naturaleza', nombre: 'Montaña',
        preview: { bgGrad: ['#2c3e50','#74b9ff'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'⛰️', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'INTO THE', x:50, y:45, size:12, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'WILD', x:50, y:62, size:24, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'🏔️ 🌌', x:50, y:80, size:14, font:'Arial', color:'#74b9ff', align:'center' }] }
    },
    {
        id: 'oceano', cat: 'naturaleza', nombre: 'Océano',
        preview: { bgGrad: ['#006994','#1abc9c'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌊', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'DEEP', x:50, y:46, size:22, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#00cfff',blur:14,ox:0,oy:0} }, { tipo:'texto', texto:'BLUE', x:50, y:63, size:18, font:'Bebas Neue', color:'#74b9ff', align:'center' }, { tipo:'texto', texto:'🐬 🐳 🐬', x:50, y:80, size:12, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'desierto', cat: 'naturaleza', nombre: 'Desierto',
        preview: { bgGrad: ['#d4a574','#e67e22'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌵', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'WILD', x:50, y:46, size:20, font:'Anton', color:'#fff', align:'center', shadow:{color:'#7b3f00',blur:4,ox:3,oy:3} }, { tipo:'texto', texto:'WEST', x:50, y:63, size:20, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'☀️', x:50, y:80, size:18, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'flores', cat: 'naturaleza', nombre: 'Flores',
        preview: { bg: '#ffd6e0', elementos: [{ tipo:'texto', texto:'🌸', x:50, y:18, size:18, font:'Arial', color:'#c0392b', align:'center' }, { tipo:'texto', texto:'🌺', x:20, y:50, size:14, font:'Arial', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'BLOOM', x:50, y:50, size:20, font:'Pacifico', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'🌺', x:80, y:50, size:14, font:'Arial', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'🌹', x:50, y:78, size:16, font:'Arial', color:'#e74c3c', align:'center' }] }
    },
    {
        id: 'galaxia', cat: 'naturaleza', nombre: 'Galaxia',
        preview: { bgGrad: ['#050520','#1a1a5e'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌌', x:50, y:22, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'STARDUST', x:50, y:46, size:14, font:'Cinzel', color:'#a29bfe', align:'center', shadow:{color:'#a29bfe',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'✨ ⭐ ✨', x:50, y:65, size:12, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'Infinite Universe', x:50, y:82, size:8, font:'Satisfy', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },

    // ── TEXTO extra (llegar a 24) ──
    {
        id: 'stay-hungry', cat: 'texto', nombre: 'Stay Hungry',
        preview: { bg: '#1a1a1a', elementos: [{ tipo:'texto', texto:'STAY', x:50, y:32, size:22, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'HUNGRY', x:50, y:52, size:20, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'stay foolish', x:50, y:71, size:10, font:'Kalam', color:'rgba(255,255,255,0.45)', align:'center' }] }
    },
    {
        id: 'no-pain', cat: 'texto', nombre: 'No Pain No Gain',
        preview: { bg: '#0d0d0d', elementos: [{ tipo:'texto', texto:'NO PAIN', x:50, y:36, size:18, font:'Bebas Neue', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'NO', x:50, y:53, size:12, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }, { tipo:'texto', texto:'GAIN', x:50, y:68, size:22, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#e74c3c',blur:14,ox:0,oy:0} }] }
    },
    {
        id: 'wanderlust', cat: 'texto', nombre: 'Wanderlust',
        preview: { bgGrad: ['#2c3e50','#74b9ff'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'✈️', x:50, y:22, size:18, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'WANDER', x:50, y:46, size:20, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'LUST', x:50, y:64, size:20, font:'Cinzel', color:'#74b9ff', align:'center', shadow:{color:'#74b9ff',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'explore the world', x:50, y:82, size:8, font:'Satisfy', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'todo-pasa', cat: 'texto', nombre: 'Todo pasa',
        preview: { bgGrad: ['#6c5ce7','#a29bfe'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'TODO', x:50, y:34, size:20, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'PASA', x:50, y:54, size:24, font:'Oswald', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'y todo llega ✨', x:50, y:75, size:10, font:'Dancing Script', color:'rgba(255,255,255,0.75)', align:'center' }] }
    },

    // ── COMPOSICIÓN extra (llegar a 24) ──
    {
        id: 'comp-polaroid', cat: 'composicion', nombre: 'Polaroid',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:36, w:62, h:52, color:'#fff', radius:2, stroke:'#ddd', strokeW:2 }, { tipo:'rect', x:50, y:30, w:52, h:34, color:'#eee', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:29, size:14, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'recuerdo 💛', x:50, y:68, size:8, font:'Kalam', color:'#555', align:'center' }] }
    },
    {
        id: 'comp-sticker', cat: 'composicion', nombre: 'Sticker redondo',
        preview: { bg: '#fdcb6e', elementos: [{ tipo:'circulo', x:50, y:46, r:40, color:'#fff', stroke:'#fdcb6e', strokeW:4 }, { tipo:'texto', texto:'⭐', x:50, y:30, size:16, font:'Arial', color:'#e67e22', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:50, size:12, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'camiglobo', x:50, y:67, size:8, font:'Montserrat', color:'#e67e22', align:'center' }] }
    },
    {
        id: 'comp-carnet', cat: 'composicion', nombre: 'Carnet / ID',
        preview: { bg: '#1a3a5c', elementos: [{ tipo:'rect', x:50, y:6, w:100, h:12, color:'#f1c40f', radius:0 }, { tipo:'texto', texto:'IDENTIFICACIÓN', x:50, y:6, size:5, font:'Montserrat', color:'#1a3a5c', align:'center' }, { tipo:'circulo', x:28, y:48, r:20, color:'#2c3e50', stroke:'rgba(255,255,255,0.3)', strokeW:1 }, { tipo:'texto', texto:'📷', x:28, y:47, size:12, font:'Arial', color:'rgba(255,255,255,0.2)', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:68, y:36, size:9, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'Cargo · Dept', x:68, y:49, size:6, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }, { tipo:'texto', texto:'ID: 00001', x:68, y:61, size:6, font:'Montserrat', color:'#f1c40f', align:'center' }, { tipo:'rect', x:50, y:90, w:100, h:20, color:'#f1c40f', radius:0 }, { tipo:'texto', texto:'camiglobo.com', x:50, y:90, size:6, font:'Montserrat', color:'#1a3a5c', align:'center' }] }
    },
    {
        id: 'comp-escudo', cat: 'composicion', nombre: 'Escudo / Equipo',
        preview: { bg: '#1a1a2e', elementos: [{ tipo:'texto', texto:'⬡', x:50, y:42, size:46, font:'Arial', color:'#16213e', align:'center' }, { tipo:'texto', texto:'⬡', x:50, y:42, size:38, color:'transparent', font:'Arial', align:'center' }, { tipo:'texto', texto:'⚽', x:50, y:34, size:14, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'CLUB', x:50, y:52, size:10, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'★ ★ ★', x:50, y:64, size:10, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:80, size:9, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'comp-poster', cat: 'composicion', nombre: 'Póster clásico',
        preview: { bg: '#f5e6c8', elementos: [{ tipo:'rect', x:50, y:5, w:94, h:6, color:'#2c3e50', radius:0 }, { tipo:'rect', x:50, y:95, w:94, h:6, color:'#2c3e50', radius:0 }, { tipo:'rect', x:50, y:45, w:80, h:44, color:'#ddd', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:44, size:18, font:'Arial', color:'#bbb', align:'center' }, { tipo:'texto', texto:'GRAN EVENTO', x:50, y:77, size:9, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'2025 · edición especial', x:50, y:87, size:6, font:'Montserrat', color:'#795548', align:'center' }] }
    },
    {
        id: 'comp-neon-frame', cat: 'composicion', nombre: 'Marco neón',
        preview: { bg: '#0a0a0a', elementos: [{ tipo:'rect', x:50, y:42, w:76, h:56, color:'transparent', radius:4, stroke:'#00cfff', strokeW:3 }, { tipo:'texto', texto:'📷', x:50, y:40, size:16, font:'Arial', color:'rgba(255,255,255,0.1)', align:'center' }, { tipo:'texto', texto:'NEON', x:50, y:76, size:12, font:'Bebas Neue', color:'#00cfff', align:'center', shadow:{color:'#00cfff',blur:18,ox:0,oy:0} }, { tipo:'texto', texto:'DREAMS', x:50, y:89, size:9, font:'Montserrat', color:'rgba(0,207,255,0.6)', align:'center' }] }
    },
    {
        id: 'comp-doble-texto', cat: 'composicion', nombre: 'Foto + 2 textos',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:28, w:80, h:38, color:'#f0f0f0', radius:6 }, { tipo:'texto', texto:'📷', x:50, y:27, size:16, font:'Arial', color:'#ccc', align:'center' }, { tipo:'texto', texto:'TÍTULO GRANDE', x:50, y:60, size:11, font:'Anton', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'descripción del producto', x:50, y:73, size:7, font:'Montserrat', color:'#888', align:'center' }, { tipo:'texto', texto:'★ camiglobo ★', x:50, y:85, size:6, font:'Montserrat', color:'#e74c3c', align:'center' }] }
    },
    {
        id: 'comp-grunge', cat: 'composicion', nombre: 'Grunge',
        preview: { bg: '#2c2c2c', elementos: [{ tipo:'rect', x:50, y:38, w:80, h:50, color:'#1a1a1a', radius:0 }, { tipo:'texto', texto:'📷', x:50, y:37, size:16, font:'Arial', color:'rgba(255,255,255,0.1)', align:'center' }, { tipo:'texto', texto:'GRUNGE', x:50, y:73, size:14, font:'Anton', color:'#e74c3c', align:'center', shadow:{color:'#000',blur:0,ox:3,oy:3} }, { tipo:'texto', texto:'STYLE', x:50, y:87, size:10, font:'Bebas Neue', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'comp-mincard', cat: 'composicion', nombre: 'Tarjeta mini',
        preview: { bg: '#6c3483', elementos: [{ tipo:'circulo', x:50, y:30, r:18, color:'rgba(255,255,255,0.15)', stroke:'rgba(255,255,255,0.3)', strokeW:1 }, { tipo:'texto', texto:'📷', x:50, y:29, size:12, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:50, y:53, size:10, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'—————', x:50, y:64, size:10, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'cargo · título', x:50, y:76, size:7, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'camiglobo.com', x:50, y:88, size:6, font:'Montserrat', color:'rgba(255,255,255,0.4)', align:'center' }] }
    },
    {
        id: 'comp-horizontal', cat: 'composicion', nombre: 'Banda horizontal',
        preview: { bg: '#f8f9fa', elementos: [{ tipo:'rect', x:50, y:30, w:100, h:35, color:'#2c3e50', radius:0 }, { tipo:'texto', texto:'📷', x:22, y:30, size:14, font:'Arial', color:'rgba(255,255,255,0.15)', align:'center' }, { tipo:'texto', texto:'NOMBRE', x:60, y:24, size:11, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'subtítulo aquí', x:60, y:38, size:7, font:'Montserrat', color:'rgba(255,255,255,0.55)', align:'center' }, { tipo:'texto', texto:'camiglobo.com · 2025', x:50, y:75, size:7, font:'Montserrat', color:'#aaa', align:'center' }] }
    },
    {
        id: 'comp-three-photos', cat: 'composicion', nombre: '3 fotos fila',
        preview: { bg: '#1a1a1a', elementos: [{ tipo:'rect', x:17, y:38, w:28, h:34, color:'#333', radius:3 }, { tipo:'rect', x:50, y:38, w:28, h:34, color:'#333', radius:3 }, { tipo:'rect', x:83, y:38, w:28, h:34, color:'#333', radius:3 }, { tipo:'texto', texto:'📷', x:17, y:38, size:10, font:'Arial', color:'#555', align:'center' }, { tipo:'texto', texto:'📷', x:50, y:38, size:10, font:'Arial', color:'#555', align:'center' }, { tipo:'texto', texto:'📷', x:83, y:38, size:10, font:'Arial', color:'#555', align:'center' }, { tipo:'texto', texto:'MOMENTOS', x:50, y:82, size:9, font:'Oswald', color:'#fff', align:'center' }] }
    },

    // ── LAYOUT extra (llegar a 24) ──
    {
        id: 'layout-wave', cat: 'layout', nombre: 'Ola inferior',
        preview: { bg: '#3498db', elementos: [{ tipo:'rect', x:50, y:82, w:110, h:40, color:'rgba(255,255,255,0.15)', radius:60 }, { tipo:'texto', texto:'NOMBRE', x:50, y:38, size:18, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'subtítulo', x:50, y:57, size:9, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }] }
    },
    {
        id: 'layout-corner', cat: 'layout', nombre: 'Esquina decorada',
        preview: { bg: '#fff', elementos: [{ tipo:'texto', texto:'◤', x:3, y:3, size:24, font:'Arial', color:'#e74c3c', align:'left' }, { tipo:'texto', texto:'◢', x:97, y:97, size:24, font:'Arial', color:'#e74c3c', align:'right' }, { tipo:'texto', texto:'NOMBRE', x:50, y:46, size:16, font:'Cinzel', color:'#2c3e50', align:'center' }, { tipo:'texto', texto:'subtítulo', x:50, y:64, size:9, font:'Montserrat', color:'#888', align:'center' }] }
    },
    {
        id: 'layout-hashtag', cat: 'layout', nombre: 'Hashtag',
        preview: { bg: '#1da1f2', elementos: [{ tipo:'texto', texto:'#', x:16, y:46, size:60, font:'Anton', color:'rgba(255,255,255,0.15)', align:'center' }, { tipo:'texto', texto:'#TU', x:50, y:40, size:20, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'#NOMBRE', x:50, y:60, size:14, font:'Anton', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'#camiglobo', x:50, y:78, size:9, font:'Montserrat', color:'rgba(255,255,255,0.55)', align:'center' }] }
    },
    {
        id: 'layout-retro', cat: 'layout', nombre: 'Retro años 80',
        preview: { bgGrad: ['#9b59b6','#e74c3c'], dir:'to right', elementos: [{ tipo:'rect', x:50, y:28, w:82, h:18, color:'rgba(0,0,0,0.25)', radius:2 }, { tipo:'texto', texto:'RETRO', x:50, y:28, size:14, font:'Bungee', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'▓▓▓▓▓▓▓▓▓', x:50, y:44, size:10, font:'Arial', color:'rgba(255,255,255,0.2)', align:'center' }, { tipo:'texto', texto:'ESTILO OCHENTA', x:50, y:60, size:10, font:'Oswald', color:'#fff', align:'center' }, { tipo:'texto', texto:'◈ 1984 ◈', x:50, y:78, size:9, font:'Montserrat', color:'rgba(255,255,255,0.55)', align:'center' }] }
    },
    {
        id: 'layout-film', cat: 'layout', nombre: 'Tira de cine',
        preview: { bg: '#0d0d0d', elementos: [{ tipo:'rect', x:50, y:50, w:90, h:80, color:'#1a1a1a', radius:0 }, { tipo:'rect', x:4, y:20, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:4, y:35, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:4, y:50, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:4, y:65, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:4, y:80, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:96, y:20, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:96, y:35, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:96, y:50, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:96, y:65, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'rect', x:96, y:80, w:5, h:5, color:'#0d0d0d', radius:1 }, { tipo:'texto', texto:'📷', x:50, y:46, size:18, font:'Arial', color:'rgba(255,255,255,0.08)', align:'center' }, { tipo:'texto', texto:'CINE', x:50, y:78, size:14, font:'Anton', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'layout-zigzag', cat: 'layout', nombre: 'Zigzag',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:18, w:100, h:20, color:'#e74c3c', radius:0 }, { tipo:'rect', x:50, y:50, w:100, h:20, color:'#2c3e50', radius:0 }, { tipo:'rect', x:50, y:82, w:100, h:20, color:'#e74c3c', radius:0 }, { tipo:'texto', texto:'ZIG', x:50, y:18, size:12, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'ZAG', x:50, y:50, size:12, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'STYLE', x:50, y:82, size:12, font:'Anton', color:'#fff', align:'center' }] }
    },
    {
        id: 'layout-magazine', cat: 'layout', nombre: 'Portada mag.',
        preview: { bg: '#fff', elementos: [{ tipo:'rect', x:50, y:7, w:100, h:13, color:'#000', radius:0 }, { tipo:'texto', texto:'MAGAZINE', x:50, y:7, size:8, font:'Anton', color:'#fff', align:'center' }, { tipo:'rect', x:50, y:56, w:100, h:70, color:'rgba(0,0,0,0.4)', radius:0 }, { tipo:'texto', texto:'PORTADA', x:50, y:68, size:14, font:'Bebas Neue', color:'#fff', align:'center' }, { tipo:'texto', texto:'EXCLUSIVA', x:50, y:82, size:10, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'● ESTE MES ●', x:50, y:93, size:6, font:'Montserrat', color:'#f1c40f', align:'center' }] }
    },
    {
        id: 'layout-neon-box', cat: 'layout', nombre: 'Caja neón',
        preview: { bg: '#0a0a0a', elementos: [{ tipo:'rect', x:50, y:48, w:82, h:50, color:'transparent', radius:6, stroke:'#ff79a8', strokeW:2 }, { tipo:'rect', x:50, y:48, w:74, h:42, color:'transparent', radius:4, stroke:'rgba(255,121,168,0.3)', strokeW:1 }, { tipo:'texto', texto:'NEON', x:50, y:42, size:18, font:'Bebas Neue', color:'#ff79a8', align:'center', shadow:{color:'#ff79a8',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'BOX', x:50, y:60, size:14, font:'Bebas Neue', color:'rgba(255,121,168,0.7)', align:'center' }] }
    },

    // ── EVENTOS extra (llegar a 24) ──
    {
        id: 'carnaval', cat: 'eventos', nombre: 'Carnaval',
        preview: { bgGrad: ['#9b59b6','#e74c3c'], dir:'to right', elementos: [{ tipo:'texto', texto:'🎭', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'CARNAVAL', x:50, y:46, size:18, font:'Bungee', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.4)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'🎊 2025 🎊', x:50, y:66, size:12, font:'Montserrat', color:'#fff', align:'center' }, { tipo:'texto', texto:'¡A disfrazarse!', x:50, y:83, size:9, font:'Kalam', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },
    {
        id: 'comunion', cat: 'eventos', nombre: 'Primera comunión',
        preview: { bg: '#f5f0ff', elementos: [{ tipo:'texto', texto:'✝️', x:50, y:20, size:20, font:'Arial', color:'#6c3483', align:'center' }, { tipo:'texto', texto:'MI PRIMERA', x:50, y:43, size:12, font:'Cinzel', color:'#6c3483', align:'center' }, { tipo:'texto', texto:'COMUNIÓN', x:50, y:60, size:16, font:'Cinzel', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'✨ nombre · 2025 ✨', x:50, y:79, size:8, font:'Dancing Script', color:'#9b59b6', align:'center' }] }
    },
    {
        id: 'pascua', cat: 'eventos', nombre: 'Pascua',
        preview: { bgGrad: ['#a8e6cf','#fdfd96'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🐣', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'HAPPY', x:50, y:44, size:16, font:'Pacifico', color:'#2c8a4a', align:'center' }, { tipo:'texto', texto:'EASTER', x:50, y:63, size:20, font:'Pacifico', color:'#7b241c', align:'center' }, { tipo:'texto', texto:'🥚 🌸 🥚', x:50, y:82, size:14, font:'Arial', color:'#2c8a4a', align:'center' }] }
    },
    {
        id: 'piñata', cat: 'eventos', nombre: 'Fiesta piñata',
        preview: { bgGrad: ['#fdcb6e','#e17055'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🪅', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FIESTA', x:50, y:45, size:22, font:'Bungee', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'TIME!', x:50, y:65, size:18, font:'Bungee', color:'#6c3483', align:'center' }, { tipo:'texto', texto:'🎉 🎊 🥳', x:50, y:83, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'retiro', cat: 'eventos', nombre: 'Retiro / Viaje',
        preview: { bgGrad: ['#2c3e50','#74b9ff'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌍', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'VIAJE', x:50, y:44, size:22, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'ÉPICO', x:50, y:63, size:18, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'✈️ destino · 2025 ✈️', x:50, y:82, size:8, font:'Montserrat', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'fin-curso', cat: 'eventos', nombre: 'Fin de curso',
        preview: { bgGrad: ['#1abc9c','#2c3e50'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🏫', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FIN DE', x:50, y:43, size:14, font:'Oswald', color:'rgba(255,255,255,0.75)', align:'center' }, { tipo:'texto', texto:'CURSO', x:50, y:61, size:22, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'📚 hasta el año que viene! 📚', x:50, y:81, size:7, font:'Kalam', color:'rgba(255,255,255,0.65)', align:'center' }] }
    },
    {
        id: 'san-juan', cat: 'eventos', nombre: 'San Juan',
        preview: { bgGrad: ['#1a0000','#e67e22'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🔥', x:50, y:20, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'NOCHE DE', x:50, y:43, size:14, font:'Montserrat', color:'rgba(255,255,255,0.7)', align:'center' }, { tipo:'texto', texto:'SAN JUAN', x:50, y:62, size:18, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'#e74c3c',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'✨ midsummer ✨', x:50, y:82, size:9, font:'Satisfy', color:'rgba(255,255,255,0.6)', align:'center' }] }
    },
    {
        id: 'apertura', cat: 'eventos', nombre: 'Gran apertura',
        preview: { bgGrad: ['#f1c40f','#e67e22'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🎊', x:50, y:20, size:20, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'GRAN', x:50, y:43, size:14, font:'Anton', color:'#7b3f00', align:'center' }, { tipo:'texto', texto:'APERTURA', x:50, y:62, size:18, font:'Anton', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'¡Ya abrimos! 🥂', x:50, y:82, size:9, font:'Kalam', color:'rgba(255,255,255,0.85)', align:'center' }] }
    },

    // ── DEPORTE extra (llegar a 24) ──
    {
        id: 'voley', cat: 'deporte', nombre: 'Vóley',
        preview: { bgGrad: ['#f1c40f','#e67e22'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🏐', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'VOLLEY', x:50, y:46, size:22, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'SPIKE IT!', x:50, y:65, size:14, font:'Bebas Neue', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'⭐ MVP ⭐', x:50, y:83, size:10, font:'Montserrat', color:'rgba(255,255,255,0.65)', align:'center' }] }
    },
    {
        id: 'golf', cat: 'deporte', nombre: 'Golf',
        preview: { bgGrad: ['#1e5631','#a8e6cf'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'⛳', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'GOLF', x:50, y:46, size:24, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'CLUB', x:50, y:64, size:16, font:'Cinzel', color:'#f1c40f', align:'center', shadow:{color:'#f1c40f',blur:8,ox:0,oy:0} }, { tipo:'texto', texto:'hole in one 🏌️', x:50, y:83, size:9, font:'Satisfy', color:'rgba(255,255,255,0.7)', align:'center' }] }
    },
    {
        id: 'esgrima', cat: 'deporte', nombre: 'Esgrima / Artes marciales',
        preview: { bg: '#111', elementos: [{ tipo:'texto', texto:'🤺', x:50, y:20, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'WARRIOR', x:50, y:46, size:20, font:'Anton', color:'#fff', align:'center', shadow:{color:'#f1c40f',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'MINDSET', x:50, y:65, size:14, font:'Oswald', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'⚔️ discipline ⚔️', x:50, y:83, size:9, font:'Kalam', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'triathlon', cat: 'deporte', nombre: 'Triatlón',
        preview: { bgGrad: ['#1a3a5c','#e74c3c'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🏊🚴🏃', x:50, y:20, size:14, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'TRI', x:50, y:44, size:26, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'ATHLETE', x:50, y:64, size:14, font:'Bebas Neue', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'iron will · iron body', x:50, y:82, size:8, font:'Montserrat', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'fútbol-americano', cat: 'deporte', nombre: 'Fútbol americano',
        preview: { bgGrad: ['#7b3f00','#e67e22'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🏈', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'TOUCH', x:50, y:44, size:20, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'DOWN!', x:50, y:63, size:22, font:'Anton', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.4)',blur:6,ox:3,oy:3} }, { tipo:'texto', texto:'🏆 CHAMPION 🏆', x:50, y:83, size:8, font:'Montserrat', color:'rgba(255,255,255,0.65)', align:'center' }] }
    },
    {
        id: 'atletismo', cat: 'deporte', nombre: 'Atletismo',
        preview: { bgGrad: ['#e74c3c','#c0392b'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🥇', x:50, y:20, size:22, font:'Arial', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'FASTER', x:50, y:43, size:18, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'HIGHER', x:50, y:60, size:18, font:'Anton', color:'#f1c40f', align:'center' }, { tipo:'texto', texto:'STRONGER', x:50, y:77, size:14, font:'Anton', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },
    {
        id: 'eskateboard', cat: 'deporte', nombre: 'Skate',
        preview: { bg: '#0d0d0d', elementos: [{ tipo:'texto', texto:'🛹', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'SKATE', x:50, y:45, size:22, font:'Permanent Marker', color:'#f1c40f', align:'center', shadow:{color:'rgba(0,0,0,0.5)',blur:4,ox:4,oy:4} }, { tipo:'texto', texto:'OR DIE', x:50, y:65, size:16, font:'Permanent Marker', color:'#e74c3c', align:'center' }, { tipo:'texto', texto:'🔥 street life 🔥', x:50, y:84, size:9, font:'Kalam', color:'rgba(255,255,255,0.5)', align:'center' }] }
    },
    {
        id: 'montain-bike', cat: 'deporte', nombre: 'MTB',
        preview: { bgGrad: ['#1e5631','#2c3e50'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🚵', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'MTB', x:50, y:46, size:28, font:'Anton', color:'#a8e6cf', align:'center', shadow:{color:'#000',blur:6,ox:4,oy:4} }, { tipo:'texto', texto:'LIFE', x:50, y:67, size:16, font:'Anton', color:'#fff', align:'center' }, { tipo:'texto', texto:'⛰️ trail rider ⛰️', x:50, y:84, size:8, font:'Montserrat', color:'rgba(255,255,255,0.55)', align:'center' }] }
    },

    // ── NATURALEZA extra (llegar a 24) ──
    {
        id: 'aurora', cat: 'naturaleza', nombre: 'Aurora boreal',
        preview: { bgGrad: ['#050520','#00b894'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🌌', x:50, y:20, size:22, font:'Arial', color:'#00b894', align:'center' }, { tipo:'texto', texto:'AURORA', x:50, y:46, size:20, font:'Cinzel', color:'#55efc4', align:'center', shadow:{color:'#00b894',blur:16,ox:0,oy:0} }, { tipo:'texto', texto:'BOREALIS', x:50, y:64, size:14, font:'Cinzel', color:'rgba(255,255,255,0.75)', align:'center' }, { tipo:'texto', texto:'✨ northern lights ✨', x:50, y:83, size:8, font:'Satisfy', color:'rgba(0,184,148,0.8)', align:'center' }] }
    },
    {
        id: 'rio', cat: 'naturaleza', nombre: 'Río & cascada',
        preview: { bgGrad: ['#006994','#a8e6cf'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'💧', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'GO WITH', x:50, y:43, size:14, font:'Montserrat', color:'rgba(255,255,255,0.8)', align:'center' }, { tipo:'texto', texto:'THE FLOW', x:50, y:62, size:18, font:'Pacifico', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.2)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'🌊 🐟 🌿', x:50, y:82, size:14, font:'Arial', color:'rgba(255,255,255,0.85)', align:'center' }] }
    },
    {
        id: 'cactus', cat: 'naturaleza', nombre: 'Cactus & sol',
        preview: { bgGrad: ['#e67e22','#f1c40f'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🌵', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'PRICKLY', x:50, y:46, size:20, font:'Righteous', color:'#fff', align:'center' }, { tipo:'texto', texto:'& PROUD', x:50, y:65, size:14, font:'Righteous', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'☀️ desert soul ☀️', x:50, y:83, size:9, font:'Kalam', color:'rgba(255,255,255,0.75)', align:'center' }] }
    },
    {
        id: 'cueva', cat: 'naturaleza', nombre: 'Cueva & rocas',
        preview: { bg: '#1a1200', elementos: [{ tipo:'texto', texto:'🗿', x:50, y:20, size:22, font:'Arial', color:'#8B7355', align:'center' }, { tipo:'texto', texto:'STONE', x:50, y:46, size:22, font:'Anton', color:'#d4a574', align:'center' }, { tipo:'texto', texto:'AGE', x:50, y:65, size:18, font:'Anton', color:'rgba(212,165,116,0.6)', align:'center' }, { tipo:'texto', texto:'ancient & wild', x:50, y:83, size:9, font:'Kalam', color:'rgba(212,165,116,0.5)', align:'center' }] }
    },
    {
        id: 'bambu', cat: 'naturaleza', nombre: 'Bambú & zen',
        preview: { bgGrad: ['#1e5631','#a8e6cf'], dir:'to right', elementos: [{ tipo:'texto', texto:'🎋', x:20, y:50, size:30, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'🎋', x:80, y:50, size:30, font:'Arial', color:'rgba(255,255,255,0.3)', align:'center' }, { tipo:'texto', texto:'ZEN', x:50, y:42, size:22, font:'Cinzel', color:'#fff', align:'center' }, { tipo:'texto', texto:'GARDEN', x:50, y:60, size:14, font:'Cinzel', color:'rgba(255,255,255,0.75)', align:'center' }, { tipo:'texto', texto:'☮️', x:50, y:79, size:16, font:'Arial', color:'#a8e6cf', align:'center' }] }
    },
    {
        id: 'nieve', cat: 'naturaleza', nombre: 'Nieve & invierno',
        preview: { bgGrad: ['#d6f0ff','#74b9ff'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'❄️', x:25, y:20, size:14, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'❄️', x:75, y:30, size:10, font:'Arial', color:'rgba(255,255,255,0.6)', align:'center' }, { tipo:'texto', texto:'WINTER', x:50, y:50, size:20, font:'Anton', color:'#fff', align:'center', shadow:{color:'#3498db',blur:10,ox:0,oy:0} }, { tipo:'texto', texto:'IS COMING', x:50, y:67, size:12, font:'Montserrat', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'🏔️ ❄️ ⛄', x:50, y:84, size:14, font:'Arial', color:'#fff', align:'center' }] }
    },
    {
        id: 'abeja', cat: 'naturaleza', nombre: 'Abeja & miel',
        preview: { bgGrad: ['#f1c40f','#e67e22'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🐝', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'BUSY', x:50, y:45, size:22, font:'Anton', color:'#7b3f00', align:'center' }, { tipo:'texto', texto:'BEE', x:50, y:64, size:22, font:'Anton', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.3)',blur:4,ox:3,oy:3} }, { tipo:'texto', texto:'🍯 honey life 🍯', x:50, y:83, size:9, font:'Kalam', color:'rgba(255,255,255,0.8)', align:'center' }] }
    },
    {
        id: 'ballena', cat: 'naturaleza', nombre: 'Ballena & mar',
        preview: { bgGrad: ['#006994','#050520'], dir:'to bottom', elementos: [{ tipo:'texto', texto:'🐋', x:50, y:20, size:22, font:'Arial', color:'#74b9ff', align:'center' }, { tipo:'texto', texto:'DEEP', x:50, y:46, size:22, font:'Bebas Neue', color:'#fff', align:'center', shadow:{color:'#74b9ff',blur:12,ox:0,oy:0} }, { tipo:'texto', texto:'OCEAN', x:50, y:64, size:16, font:'Bebas Neue', color:'#74b9ff', align:'center' }, { tipo:'texto', texto:'🌊 where giants swim 🌊', x:50, y:83, size:7, font:'Satisfy', color:'rgba(116,185,255,0.7)', align:'center' }] }
    },
    {
        id: 'libelula', cat: 'naturaleza', nombre: 'Libélula',
        preview: { bgGrad: ['#a8e6cf','#74b9ff'], dir:'to bottom right', elementos: [{ tipo:'texto', texto:'🪲', x:50, y:20, size:22, font:'Arial', color:'#fff', align:'center' }, { tipo:'texto', texto:'FREE', x:50, y:46, size:22, font:'Pacifico', color:'#fff', align:'center', shadow:{color:'rgba(0,0,0,0.2)',blur:6,ox:2,oy:2} }, { tipo:'texto', texto:'SPIRIT', x:50, y:64, size:16, font:'Pacifico', color:'rgba(255,255,255,0.85)', align:'center' }, { tipo:'texto', texto:'✨ nature soul ✨', x:50, y:83, size:9, font:'Satisfy', color:'rgba(255,255,255,0.75)', align:'center' }] }
    },
];

let plantillaActual = null;
let plantillasPaginaActual = 0;
const PLANTILLAS_POR_PAGINA = 8;
let plantillasCatActual = 'texto';

function cambiarPagPlantillas(dir) {
    const lista = PLANTILLAS.filter(p => p.cat === plantillasCatActual);
    const totalPags = Math.ceil(lista.length / PLANTILLAS_POR_PAGINA);
    plantillasPaginaActual = Math.max(0, Math.min(totalPags - 1, plantillasPaginaActual + dir));
    renderPlantillasPagina();
}

function renderPlantillasPagina() {
    const grid = document.getElementById('plantillas-grid');
    const info = document.getElementById('plantillas-page-info');
    const btnPrev = document.getElementById('plantillas-prev');
    const btnNext = document.getElementById('plantillas-next');
    if (!grid) return;
    const lista = PLANTILLAS.filter(p => p.cat === plantillasCatActual);
    const totalPags = Math.max(1, Math.ceil(lista.length / PLANTILLAS_POR_PAGINA));
    const inicio = plantillasPaginaActual * PLANTILLAS_POR_PAGINA;
    const pagina = lista.slice(inicio, inicio + PLANTILLAS_POR_PAGINA);
    if (info) info.textContent = (plantillasPaginaActual + 1) + ' / ' + totalPags;
    if (btnPrev) btnPrev.disabled = plantillasPaginaActual === 0;
    if (btnNext) btnNext.disabled = plantillasPaginaActual >= totalPags - 1;
    const paginacion = document.getElementById('plantillas-paginacion');
    if (paginacion) paginacion.style.display = totalPags <= 1 ? 'none' : 'flex';
    grid.innerHTML = pagina.map(p => {
        const estiloFondo = p.preview.bgGrad
            ? `background:linear-gradient(${p.preview.dir || 'to bottom'},${p.preview.bgGrad[0]},${p.preview.bgGrad[1] || p.preview.bgGrad[0]})`
            : `background:${p.preview.bg}`;
        return `<div class="plantilla-card" onclick="abrirModalPlantilla('${p.id}')">
            <div class="plantilla-preview" style="${estiloFondo}">
                ${renderMiniPreview(p)}
            </div>
            <div class="plantilla-nombre">${p.nombre}</div>
        </div>`;
    }).join('');
}

function filtrarPlantillas(cat, btn) {
    document.querySelectorAll('.ptab').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    plantillasCatActual = cat;
    plantillasPaginaActual = 0;
    renderPlantillasPagina();
}

function renderMiniPreview(p) {
    if (!p.preview.elementos || !p.preview.elementos.length) {
        if (p.preview.bgGrad) return `<span style="color:rgba(255,255,255,0.3);font-size:18px;">🎨</span>`;
        return `<span style="color:rgba(0,0,0,0.15);font-size:18px;">⬜</span>`;
    }
    // Renderiza un SVG simplificado como preview visual
    const svgEls = p.preview.elementos.map(el => {
        if (el.tipo === 'texto') {
            const x = el.x + '%';
            const y = el.y + '%';
            const col = el.color || '#fff';
            return `<text x="${x}" y="${y}" text-anchor="middle" dominant-baseline="middle"
                font-size="${el.size * 0.55}" fill="${col}" font-weight="900"
                transform="${el.angle ? `rotate(${el.angle},${el.x},${el.y})` : ''}"
                style="font-family:sans-serif;">${el.texto}</text>`;
        }
        if (el.tipo === 'rect') {
            const cx = el.x - el.w/2; const cy = el.y - el.h/2;
            const fill = el.color === 'transparent' ? 'none' : el.color;
            const stroke = el.stroke || 'none';
            return `<rect x="${cx}%" y="${cy}%" width="${el.w}%" height="${el.h}%"
                rx="${el.radius || 0}" fill="${fill}" stroke="${stroke}" stroke-width="${el.strokeW || 0}"/>`;
        }
        if (el.tipo === 'circulo') {
            const fill = el.color === 'transparent' ? 'none' : el.color;
            return `<circle cx="${el.x}%" cy="${el.y}%" r="${el.r * 0.55}%"
                fill="${fill}" stroke="${el.stroke || 'none'}" stroke-width="${el.strokeW || 0}"/>`;
        }
        return '';
    }).join('');
    return `<svg viewBox="0 0 100 133" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;position:absolute;top:0;left:0;">${svgEls}</svg>`;
}

function abrirModalPlantilla(id) {
    plantillaActual = PLANTILLAS.find(p => p.id === id);
    if (!plantillaActual) return;
    const modal = document.getElementById('modal-plantilla');
    document.getElementById('modal-plantilla-nombre').innerText = plantillaActual.nombre;
    // Preview en el modal
    const prevDiv = document.getElementById('modal-plantilla-preview');
    const estiloFondo = plantillaActual.preview.bgGrad
        ? `background:linear-gradient(${plantillaActual.preview.dir || 'to bottom'},${plantillaActual.preview.bgGrad[0]},${plantillaActual.preview.bgGrad[1]})`
        : `background:${plantillaActual.preview.bg}`;
    prevDiv.style.cssText = `width:100%;height:120px;border-radius:14px;margin-bottom:16px;border:1px solid rgba(255,255,255,0.1);overflow:hidden;display:flex;align-items:center;justify-content:center;position:relative;${estiloFondo}`;
    prevDiv.innerHTML = renderMiniPreview(plantillaActual);
    modal.style.display = 'flex';
}

function cerrarModalPlantilla() {
    document.getElementById('modal-plantilla').style.display = 'none';
    plantillaActual = null;
}

async function aplicarPlantillaConAviso(modo) {
    const fc = activeCanvas;
    const tieneObjetos = fc.getObjects().filter(o => o.selectable !== false).length > 0;
    if (tieneObjetos) {
        const ok = await cgConfirm({ titulo: 'Reemplazar diseño', mensaje: 'Esto borrará el diseño actual del lienzo y lo sustituirá por la plantilla. ¿Seguro?', confirmar: 'Sí, reemplazar', tipo: 'warn' });
        if (!ok) return;
    }
    aplicarPlantilla(modo);
}

function aplicarPlantilla(modo) {
    if (!plantillaActual) return;
    const fc = activeCanvas;
    const W = fc.width, H = fc.height;

    if (modo === 'reemplazar') {
        // Eliminar solo objetos editables
        const objetos = fc.getObjects().filter(o => o.selectable !== false);
        objetos.forEach(o => fc.remove(o));
        // Resetear fondo
        fc.backgroundColor = '';
    }

    const p = plantillaActual.preview;

    // Aplicar fondo
    if (p.bgGrad && p.bgGrad.length >= 2) {
        const coords = gradDirToCoords(p.dir || 'to bottom', W, H);
        fc.setBackgroundColor(new fabric.Gradient({
            type: 'linear', coords,
            colorStops: [{ offset:0, color:p.bgGrad[0] }, { offset:1, color:p.bgGrad[1] }]
        }), () => fc.renderAll());
    } else if (p.bg && modo === 'reemplazar') {
        fc.backgroundColor = p.bg === '#ffffff' ? '#ffffff' : p.bg;
    }

    // Añadir elementos
    // La zona de impresión siempre ocupa estas coordenadas fijas en Fabric (independiente del zoom):
    // center: left=160, top=190  →  borde izq: 50, borde top: 45, width: 220, height: 290
    const PZ_LEFT = 50, PZ_TOP = 45, PZ_W = 220, PZ_H = 290;

    let lastPhotoSlotRect = null;   // referencia al último rect de foto creado en esta plantilla

    (p.elementos || []).forEach(el => {
        // Mapear coordenadas % → zona de impresión
        const cx = PZ_LEFT + (el.x / 100) * PZ_W;
        const cy = PZ_TOP  + (el.y / 100) * PZ_H;

        if (el.tipo === 'texto') {
            const fontSize = Math.round((el.size / 100) * PZ_W * 0.55);
            // Usar IText para que sea editable con doble clic
            const t = new fabric.IText(el.texto, {
                left: cx, top: cy,
                originX: 'center', originY: 'center',
                fontSize: Math.max(fontSize, 18),
                fontFamily: el.font || 'Arial',
                fill: el.color || '#ffffff',
                fontWeight: '900',
                textAlign: el.align || 'center',
                angle: el.angle || 0,
                selectable: true, evented: true
            });
            if (el.shadow) {
                t.set({ shadow: new fabric.Shadow({ color: el.shadow.color, blur: el.shadow.blur, offsetX: el.shadow.ox || 0, offsetY: el.shadow.oy || 0 }) });
            }
            // Seleccionar todo al entrar en edición si contiene texto placeholder
            t.on('editing:entered', function() {
                const placeholders = ['TU TEXTO AQUÍ','TÍTULO DEL DISEÑO','TEXTO PIE','ENCABEZADO','BANNER SUPERIOR','BANNER INFERIOR','subtítulo secundario','www.camiglobo.com','TU FOTO AQUÍ','TU FOTO','NOMBRE','DESDE 2020','★ NOMBRE ★'];
                if (placeholders.some(ph => this.text === ph || this.text.toUpperCase() === ph.toUpperCase())) {
                    this.selectAll();
                }
            });

            // Si el texto es un emoji/placeholder de foto, doble clic → scroll a biblioteca
            const FOTO_TRIGGERS = ['📷', '📷 imagen', 'TU FOTO AQUÍ', 'TU FOTO'];
            if (FOTO_TRIGGERS.some(ph => el.texto === ph || el.texto.toUpperCase() === ph.toUpperCase())) {
                t.isPhotoEmoji = true;
                const slotRef = lastPhotoSlotRect; // capturar en closure
                t.on('mousedblclick', function() {
                    _scrollToFotosBlink(slotRef, fc);
                });
                // Evitar que abra edición de texto al doble clic
                t.set({ editable: false });
            }

            fc.add(t);
        } else if (el.tipo === 'rect') {
            const rw = (el.w / 100) * PZ_W;
            const rh = (el.h / 100) * PZ_H;
            const esFullWidth = el.w >= 98;  // banner de borde a borde de la zona de impresión
            // Detectar si es un hueco de foto (color gris/claro y sin stroke de diseño)
            const esHuecoDeFoto = !esFullWidth && (el.color === '#ddd' || el.color === '#eee' || el.color === '#e0e0e0' || el.color === '#ccc') && !el.stroke;
            const r = new fabric.Rect({
                left:    esFullWidth ? PZ_LEFT : cx,
                top:     cy,
                originX: esFullWidth ? 'left' : 'center',
                originY: 'center',
                width:   rw,
                height:  rh,
                fill:    el.color === 'transparent' ? 'transparent' : el.color,
                stroke:  esHuecoDeFoto ? '#3498db' : (el.stroke || null),
                strokeWidth: esHuecoDeFoto ? 2 : (el.strokeW || 0),
                strokeDashArray: esHuecoDeFoto ? [6, 4] : null,
                rx: esFullWidth ? 0 : (el.radius || 0),
                ry: esFullWidth ? 0 : (el.radius || 0),
                selectable: true, evented: true,
                isPhotoSlot: esHuecoDeFoto
            });
            if (esHuecoDeFoto) {
                lastPhotoSlotRect = r;   // guardar referencia para el emoji que venga después
                // Al hacer doble clic en el hueco → scroll a biblioteca de fotos
                r.on('mousedblclick', function() {
                    _scrollToFotosBlink(r, fc);
                });
            }
            fc.add(r);
        } else if (el.tipo === 'circulo') {
            const radio = (el.r / 100) * PZ_W;
            const c = new fabric.Circle({
                left: cx, top: cy,
                originX: 'center', originY: 'center',
                radius: radio,
                fill: el.color === 'transparent' ? 'transparent' : el.color,
                stroke: el.stroke || null,
                strokeWidth: el.strokeW || 0,
                selectable: true, evented: true
            });
            fc.add(c);
        }
    });

    fc.requestRenderAll();
    cerrarModalPlantilla();
    guardarEstado();
    const modoTexto = modo === 'reemplazar' ? 'reemplazó el lienzo' : 'se añadió encima';
    aviso('Plantilla "' + (plantillaActual?.nombre || '') + '" ' + modoTexto, 'success');
    pulseEditando();
}

function centerObj(dir) {
    const obj = activeCanvas.getActiveObject();
    if (!obj) return;
    dir === 'h' ? obj.viewportCenterH() : obj.viewportCenterV();
    obj.setCoords(); activeCanvas.renderAll();
}

// ─── OPACIDAD ─────────────────────────────────────────────────
function updateOpacity(val) {
    const obj = activeCanvas.getActiveObject();
    if (!obj) return;
    obj.set('opacity', parseFloat(val));
    document.getElementById('opacity-val').innerText = Math.round(val * 100) + '%';
    obj.setCoords();
    activeCanvas.renderAll();
}
function resetOpacity() {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero un elemento del lienzo', 'warning');; return; }
    obj.set('opacity', 1);
    const sl = document.getElementById('opacity-slider');
    if (sl) sl.value = 1;
    document.getElementById('opacity-val').innerText = '100%';
    obj.setCoords();
    activeCanvas.renderAll();
}

// ─── VOLTEAR IMAGEN ───────────────────────────────────────────
function flipImage(axis) {
    const obj = activeCanvas.getActiveObject();
    if (!obj) { aviso('Selecciona primero una imagen en el lienzo', 'warning'); return; }
    if (axis === 'x') obj.set('flipX', !obj.flipX);
    if (axis === 'y') obj.set('flipY', !obj.flipY);
    obj.setCoords();
    activeCanvas.renderAll();
    aviso('Imagen volteada ' + (axis === 'x' ? 'horizontalmente' : 'verticalmente'), 'success');
}

// ─── ESPACIADO ENTRE LETRAS ───────────────────────────────────
function updateLetterSpacing(val) {
    const obj = activeCanvas.getActiveObject();
    if (obj && obj.type && obj.type.includes('text')) {
        obj.set('charSpacing', parseFloat(val) * 10);
        document.getElementById('spacing-val').innerText = val;
        obj.setCoords();
        activeCanvas.renderAll();
    }
}
function resetLetterSpacing() {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) { aviso('Selecciona primero un texto', 'warning');; return; }
    obj.set('charSpacing', 0);
    const sl = document.getElementById('letter-spacing-slider');
    if (sl) sl.value = 0;
    document.getElementById('spacing-val').innerText = '0';
    obj.setCoords();
    activeCanvas.renderAll();
}

// ─── ALTURA DE LÍNEA ──────────────────────────────────────────
function updateLineHeight(val) {
    const obj = activeCanvas.getActiveObject();
    if (obj && obj.type && obj.type.includes('text')) {
        obj.set('lineHeight', parseFloat(val));
        document.getElementById('lineheight-val').innerText = parseFloat(val).toFixed(1);
        obj.setCoords();
        activeCanvas.renderAll();
    }
}
function resetLineHeight() {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) { aviso('Selecciona primero un texto', 'warning');; return; }
    obj.set('lineHeight', 1.2);
    const sl = document.getElementById('line-height-slider');
    if (sl) sl.value = 1.2;
    document.getElementById('lineheight-val').innerText = '1.2';
    obj.setCoords();
    activeCanvas.renderAll();
}

// ─── ALINEACIÓN DE TEXTO ──────────────────────────────────────
function updateTextOpacity(value) {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) { aviso('Selecciona primero un texto en el lienzo', 'warning'); return; }
    obj.set('opacity', parseFloat(value));
    activeCanvas.renderAll();
    const slider = document.getElementById('text-opacity-slider');
    const label  = document.getElementById('text-opacity-val');
    if (slider) slider.value = value;
    if (label)  label.textContent = Math.round(value * 100) + '%';
}

function setTextAlign(align) {
    const obj = activeCanvas.getActiveObject();
    if (!obj || !obj.type.includes('text')) { aviso('Selecciona primero un texto', 'warning'); return; }
    if (obj.isEditing) obj.exitEditing();
    // Fabric 5: necesita width fijo para que textAlign sea visible
    // Si el objeto no tiene width mayor que su contenido natural, lo forzamos
    const minWidth = activeCanvas === canvas ? 220 : activeCanvas.width * 0.9;
    if (!obj.width || obj.width < minWidth) {
        obj.set('width', minWidth);
    }
    obj.set('textAlign', align);
    if (typeof obj.initDimensions === 'function') obj.initDimensions();
    obj.setCoords();
    activeCanvas.renderAll();
    guardarEstado();
    document.querySelectorAll('.text-align-btn').forEach(b => b.classList.remove('active'));
    const activeBtn = document.querySelector('.text-align-btn[data-align="' + align + '"]');
    if (activeBtn) activeBtn.classList.add('active');
}

// ─── STICKERS / EMOJIS ───────────────────────────────────────
function addSticker(emoji) {
    const fc = activeCanvas;
    const cx = fc.width  / 2;
    const cy = fc.height / 2;
    const text = new fabric.Text(emoji, {
        left: cx, top: cy,
        originX: 'center', originY: 'center',
        fontSize: fc === canvas ? 60 : 30,
        selectable: true, evented: true
    });
    fc.add(text);
    fc.setActiveObject(text);
    fc.requestRenderAll();
    aviso('Sticker ' + emoji + ' añadido — selecciónalo para moverlo o cambiar tamaño', 'info');
    pulseEditando();
}
let gradDirActual = 'to bottom';

function applyGradient(dir) {
    gradDirActual = dir;
    const c1 = document.getElementById('grad-color1').value;
    const c2 = document.getElementById('grad-color2').value;

    // Actualizar preview
    const prev = document.getElementById('grad-preview');
    if (prev) prev.style.background = `linear-gradient(${dir}, ${c1}, ${c2})`;

    // Aplicar al lienzo activo (principal o mini)
    const fc = activeCanvas;
    const coords = gradDirToCoords(dir, fc.width, fc.height);

    const gradient = new fabric.Gradient({
        type: 'linear',
        coords: coords,
        colorStops: [
            { offset: 0, color: c1 },
            { offset: 1, color: c2 }
        ]
    });

    fc.setBackgroundColor(gradient, () => { fc.renderAll(); });
    aviso('Degradado aplicado al fondo del lienzo', 'success');
}

function gradDirToCoords(dir, w, h) {
    const map = {
        'to bottom':       { x1: 0,   y1: 0,   x2: 0,   y2: h   },
        'to right':        { x1: 0,   y1: 0,   x2: w,   y2: 0   },
        'to bottom right': { x1: 0,   y1: 0,   x2: w,   y2: h   },
        'to bottom left':  { x1: w,   y1: 0,   x2: 0,   y2: h   },
    };
    return map[dir] || map['to bottom'];
}

function removeGradient() {
    activeCanvas.setBackgroundColor('', () => { activeCanvas.renderAll(); });
    const prev = document.getElementById('grad-preview');
    if (prev) prev.style.background = '#eee';
    aviso('Fondo del lienzo eliminado', 'info');
}

// Actualizar preview en tiempo real al cambiar colores
document.addEventListener('DOMContentLoaded', () => {
    const c1 = document.getElementById('grad-color1');
    const c2 = document.getElementById('grad-color2');
    if (c1) c1.addEventListener('input', () => {
        const prev = document.getElementById('grad-preview');
        if (prev) prev.style.background = `linear-gradient(${gradDirActual}, ${c1.value}, ${c2.value})`;
    });
    if (c2) c2.addEventListener('input', () => {
        const prev = document.getElementById('grad-preview');
        if (prev) prev.style.background = `linear-gradient(${gradDirActual}, ${c1.value}, ${c2.value})`;
    });
    // Mostrar badge inicial en el lienzo principal
    setTimeout(() => actualizarBadgeActivo(canvas), 500);
    setTimeout(() => { aviso('¡Editor listo! Empieza eligiendo producto o subiendo una foto 🎨', 'success'); actualizarBtnVerLienzo(); }, 800);
});

function changeLayer(dir) {
    const obj = activeCanvas.getActiveObject();
    if (!obj) return;
    dir === 'up' ? obj.bringForward() : obj.sendBackwards();
    activeCanvas.renderAll();
    aviso(dir === 'up' ? 'Elemento movido hacia delante' : 'Elemento movido hacia detrás', 'info');
}

function toggleBoundary() {
    // La zona de impresión punteada solo existe en el lienzo principal (pecho/espalda)
    const visible = printBoundary.opacity === 0;
    printBoundary.set('opacity', visible ? 1 : 0);
    canvas.bringToFront(printBoundary); 
    canvas.renderAll();
    const btn = document.getElementById('btn-boundary');
    if (btn) btn.classList.toggle('active', visible);
}

function deleteSelected() {
    const active = activeCanvas.getActiveObject();
    if (active) {
        activeCanvas.remove(active);
        activeCanvas.discardActiveObject();
        activeCanvas.renderAll();
        aviso('Elemento eliminado del lienzo', 'warning');
    } else {
        aviso('Primero haz clic en el elemento que quieres borrar', 'warning');
    }
}

async function resetCanvas() {
    const ok = await cgConfirm({ titulo: 'Borrar lienzo', mensaje: '¿Quieres borrar todo el contenido del lienzo y empezar de cero?', confirmar: 'Sí, borrar todo', tipo: 'trash' });
    if (!ok) return;
    activeCanvas.getObjects().forEach(obj => {
        if (obj !== printBoundary) activeCanvas.remove(obj);
    });
    if (activeCanvas === canvas) { memoriaDisenos[vistaActual] = null; }
    activeCanvas.discardActiveObject();
    activeCanvas.renderAll();
    calcularPrecio();
    aviso('Lienzo limpio — empieza de cero', 'info');
}


function generarColoresTexto() {
    const p = document.getElementById('text-colors');
    p.innerHTML = '';
    coloresLetras.forEach(c => {
        const b = document.createElement('div');
        b.className = 'text-color-circle';
        b.style.backgroundColor = c;
        b.onclick = () => {
            const a = activeCanvas.getActiveObject();
            if (a && a.type.includes('text')) { a.set('fill', c); activeCanvas.renderAll(); }
            else { aviso('Selecciona primero el texto en el lienzo', 'warning');; }
        };
        p.appendChild(b);
    });
}

function downloadDesign() {
    // Guardar lienzo actual antes de exportar
    memoriaDisenos[vistaActual] = canvas.toJSON(['selectable','evented','id']);

    const tipo   = document.getElementById('product-type').value;
    const esRopa = productos[tipo] && productos[tipo].isClothing;

    let dlDelay = 0;
    function dl(dataUrl, nombre) {
        setTimeout(function() {
            const link = document.createElement('a');
            link.download = nombre; link.href = dataUrl; link.click();
        }, dlDelay);
        dlDelay += 700;
    }

    function descargarMinis() {
        const nombres = { 'nuca': 'nuca', 'manga-izq': 'manga-izquierda', 'manga-der': 'manga-derecha' };
        getMiniCanvasData().then(function(results) {
            results.forEach(function(item) {
                if (!item) return;
                dl(item.dataUrl, 'diseno-camiglobo-' + (nombres[item.id] || item.id) + '.png');
            });
        });
    }

    if (esRopa) {
        var jsonFront = memoriaDisenos['front'];  // puede ser null si nunca se visitó
        var jsonBack  = memoriaDisenos['back'];

        // Exportar ambos lados; exportarSoloDiseno devuelve null si están vacíos
        // Pasar el JSON directamente; si es null → vacío → resolverá null
        Promise.all([
            exportarSoloDiseno(jsonFront, 2),
            exportarSoloDiseno(jsonBack,  2)
        ]).then(function(urls) {
            if (urls[0]) dl(urls[0], 'diseno-camiglobo-delante.png');
            if (urls[1]) dl(urls[1], 'diseno-camiglobo-detras.png');
            descargarMinis();
        });
    } else {
        exportarSoloDiseno(memoriaDisenos[vistaActual], 2).then(function(dataUrl) {
            if (dataUrl) dl(dataUrl, 'diseno-camiglobo.png');
            descargarMinis();
        });
    }
}

// ─── 8. CARRITO ──────────────────────────────────────────────
async function enviarAlCarrito() {
    // Validar que el lienzo no esté vacío
    const objetosUsuario = canvas.getObjects().filter(o => o.selectable !== false);
    const tieneEspalda   = memoriaDisenos['back'] && (() => {
        try { return (JSON.parse(JSON.stringify(memoriaDisenos['back'])).objects || []).filter(o => o.selectable !== false).length > 0; } catch(e) { return false; }
    })();
    if (objetosUsuario.length === 0 && !tieneEspalda) {
        aviso('El lienzo está vacío — añade una foto, texto o plantilla antes de continuar', 'warning');
        return;
    }

    // Validar que ningún elemento esté fuera de la zona de impresión
    if (printBoundary) {
        const PB = printBoundary.getBoundingRect();
        const fueraDeZona = objetosUsuario.some(o => {
            const r = o.getBoundingRect();
            return r.left < PB.left || r.top < PB.top ||
                   (r.left + r.width)  > (PB.left + PB.width) ||
                   (r.top  + r.height) > (PB.top  + PB.height);
        });
        if (fueraDeZona) {
            const confirmar = await cgConfirm({ titulo: 'Elementos fuera de zona', mensaje: 'Hay elementos fuera de la zona de impresión (línea discontinua) y podrían recortarse al imprimir. ¿Añadir al carrito igualmente?', confirmar: 'Sí, añadir', cancelar: 'Volver a ajustar', tipo: 'warn' });
            if (!confirmar) return;
        }
    }

    // Sugerir previsualizar si no lo han hecho
    if (!previewVisto) {
        const confirmar = await cgConfirm({ titulo: 'Sin previsualizar', mensaje: 'Aún no has visto cómo quedará tu diseño final. Te recomendamos verlo antes de añadirlo al carrito.', confirmar: 'Añadir sin ver', cancelar: 'Ver diseño primero', tipo: 'info', colorBtn: 'blue' });
        if (!confirmar) {
            abrirPreview();
            return;
        }
    }

    const btn = document.getElementById('btn-add-cart');
    btn.innerHTML = "<i class='fas fa-sync fa-spin'></i> PROCESANDO...";
    btn.disabled  = true;

    if (printBoundary) printBoundary.set('opacity', 0);
    canvas.discardActiveObject(); canvas.renderAll();

    memoriaDisenos[vistaActual] = canvas.toJSON(['selectable','evented','id']);

        const exportMultiplier = 5; // Subimos a 4 para obtener calidad HD real de impresión (aprox 1280x1520px)
    const tempFront = document.createElement('canvas');
    tempFront.width  = canvas.width  * exportMultiplier;
    tempFront.height = canvas.height * exportMultiplier;
    const ctxF = tempFront.getContext('2d');
    ctxF.fillStyle = canvas.backgroundColor;
    ctxF.fillRect(0, 0, tempFront.width, tempFront.height);

    await new Promise(resolve => {
        const mI  = new Image();
        // AQUÍ ESTABA EL ERROR: Usamos la variable en lugar del "1" fijo
        mI.src    = canvas.toDataURL({ format: 'png', multiplier: exportMultiplier });
        mI.onload = () => { ctxF.drawImage(mI, 0, 0); resolve(); };
    });

    if (printBoundary) printBoundary.set('opacity', 1); canvas.renderAll();

    const fd = new FormData();
    fd.append('csrf_token',    csrfToken);
    fd.append('img_base64',    tempFront.toDataURL('image/png'));
    fd.append('producto_tipo', document.getElementById('product-type').value);
    fd.append('color',         colorActual);
    fd.append('color_producto', colorProductoActual);
    fd.append('talla',         document.getElementById('talla-select').value);
    fd.append('notas',         document.getElementById('notas-extra').value);

    const precioTexto  = document.getElementById('display-price').innerText;
    const precioLimpio = precioTexto.replace(' €', '').replace(',', '.');
    fd.append('precio_final', precioLimpio);

    if (memoriaDisenos['front']) fd.append('diseno_front', JSON.stringify(memoriaDisenos['front']));
    if (memoriaDisenos['back'])  fd.append('diseno_back',  JSON.stringify(memoriaDisenos['back']));

    // Flags de extras para guardar_diseno.php (doble cara + mangas/nuca)
    const tieneFrontExtra = memoriaDisenos['front'] && (() => {
        try { return (JSON.parse(JSON.stringify(memoriaDisenos['front'])).objects || []).filter(o => o.selectable !== false || o.evented !== false).length > 0; } catch(e) { return false; }
    })();
    const tieneBackExtra = memoriaDisenos['back'] && (() => {
        try { return (JSON.parse(JSON.stringify(memoriaDisenos['back'])).objects || []).filter(o => o.selectable !== false || o.evented !== false).length > 0; } catch(e) { return false; }
    })();
    if (tieneFrontExtra && tieneBackExtra) {
        fd.append('doble_cara', '1');
    }
    MINI_IDS.forEach(id => {
        const fc = miniCanvases[id];
        if (!fc) return;
        const userObjs = fc.getObjects().filter(o => o.selectable !== false);
        if (userObjs.length > 0) {
            if (id === 'nuca')      fd.append('nuca', '1');
            if (id === 'manga-izq') fd.append('manga_izq', '1');
            if (id === 'manga-der') fd.append('manga_der', '1');
        }
    });

    if (memoriaDisenos['back']) {
        const tipoP = document.getElementById('product-type').value;
        const infoP = productos[tipoP];
        await new Promise(resolve => {
            const tempBack = document.createElement('canvas');
            // Multiplicamos también el tamaño del lienzo de la espalda para igualar al frente
            tempBack.width  = canvas.width * exportMultiplier;
            tempBack.height = canvas.height * exportMultiplier;
            const ctxB = tempBack.getContext('2d');
            ctxB.fillStyle = infoP.colores[colorActual] || '#ffffff';
            ctxB.fillRect(0, 0, tempBack.width, tempBack.height);

            const fabricBack = new fabric.StaticCanvas(document.createElement('canvas'));
            fabricBack.setWidth(canvas.width);
            fabricBack.setHeight(canvas.height);
            fabric.Image.fromURL(infoP.img, function(bgImg) {
                bgImg.set({ scaleX: canvas.width / bgImg.width, scaleY: canvas.height / bgImg.height, selectable: false, evented: false });
                fabricBack.setBackgroundImage(bgImg, function() {
                    fabricBack.loadFromJSON(memoriaDisenos['back'], function() {
                        fabricBack.renderAll();
                        const imgE = new Image();
                        // Añadimos el multiplicador a la exportación de la espalda
                        imgE.src   = fabricBack.toDataURL({ format: 'png', quality: 1, multiplier: exportMultiplier });
                        imgE.onload = () => {
                            ctxB.drawImage(imgE, 0, 0);
                            fd.append('img_espalda_base64', tempBack.toDataURL('image/png'));
                            fabricBack.dispose();
                            resolve();
                        };
                    });
                });
            });
        });
    }

    const miniResults = await getMiniCanvasData();
    miniResults.forEach(item => {
        if (!item) return;
        fd.append('mini_canvas_' + item.id, item.dataUrl);
    });
    // Enviar también el JSON editable de cada mini lienzo
    MINI_IDS.forEach(id => {
        const fc = miniCanvases[id];
        if (!fc) return;
        const userObjs = fc.getObjects().filter(o => o.selectable !== false);
        if (userObjs.length === 0) return;
        const jsonMini = JSON.stringify(fc.toJSON(['selectable','evented','id']));
        fd.append('diseno_mini_' + id, jsonMini);
    });

    try {
        const res  = await fetch('guardar_diseno.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            marcarGuardado(); // Quitar aviso de cambios sin guardar
            // Limpiar auto-guardado tras pedido exitoso
            try {
                const tipo = document.getElementById('product-type')?.value || 'camiseta';
                localStorage.removeItem('camiglobo_autosave_' + tipo);
                localStorage.removeItem('camiglobo_autosave_time');
            } catch(e) {}
            window.location.href = 'carrito.php';
        } else {
            aviso('Error al guardar el diseño: ' + data.error, 'error');;
            btn.disabled = false;
            btn.innerHTML = "AÑADIR AL CARRITO <i class='fas fa-shopping-basket'></i>";
        }
    } catch (e) {
        aviso('Error de conexión al servidor', 'error');;
        btn.disabled = false;
        btn.innerHTML = "AÑADIR AL CARRITO <i class='fas fa-shopping-basket'></i>";
    }
}

// ─── SINCRONIZACIÓN DE SLIDERS ────────────────────────────────
canvas.on('selection:created', function(e) { syncSlider(); });
canvas.on('selection:updated', function(e) { syncSlider(); ocultarCtxMenu(); });
canvas.on('object:scaling',    syncSlider);
canvas.on('object:rotating',   syncSlider);
canvas.on('object:moving',     ocultarCtxMenu);

canvas.on('selection:cleared', () => {
    ocultarCtxMenu();
    document.getElementById('image-scale-slider').value = 1;
    document.getElementById('scale-val').innerText = '100%';
    document.getElementById('image-rotation-slider').value = 0;
    document.getElementById('rotation-val').innerText = '0°';
});

// ─── MENÚ CONTEXTUAL — BOTÓN EN ESQUINA DEL OBJETO ───────────
window._ctxAcciones = [];

// Hace scroll a un elemento y lo hace parpadear
function _scrollYBlink(elId) {
    ocultarCtxMenu();
    const el = document.getElementById(elId);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.remove('ctx-blink');
    void el.offsetWidth; // reflow
    el.classList.add('ctx-blink');
    el.addEventListener('animationend', () => el.classList.remove('ctx-blink'), { once: true });
}

function _buildCtxMenu(obj) {
    const inner = document.getElementById('ctx-menu-inner');
    if (!inner) return;

    const isText  = obj.type && obj.type.includes('text');
    const isImage = obj.type === 'image';

    const acciones = [];

    if (isText) {
        acciones.push({ icon: 'fa-text-height',        label: 'Tamaño de texto',     fn: () => _scrollYBlink('text-size-slider') });
        acciones.push({ icon: 'fa-sync-alt',           label: 'Rotar texto',          fn: () => _scrollYBlink('text-rotation-slider') });
        acciones.push({ icon: 'fa-eye',                label: 'Opacidad',             fn: () => _scrollYBlink('text-opacity-slider') });
        acciones.push({ icon: 'fa-text-width',         label: 'Espaciado letras',     fn: () => _scrollYBlink('letter-spacing-slider') });
        acciones.push({ icon: 'fa-grip-lines',         label: 'Altura de línea',      fn: () => _scrollYBlink('line-height-slider') });
        acciones.push({ icon: 'fa-palette',            label: 'Color de letra',       fn: () => _scrollYBlink('text-colors') });
        acciones.push({ icon: 'fa-font',               label: 'Tipografía',           fn: () => _scrollYBlink('font-picker') });
    }

    if (isImage) {
        acciones.push({ icon: 'fa-expand-arrows-alt',  label: 'Escala / tamaño',      fn: () => _scrollYBlink('image-scale-slider') });
        acciones.push({ icon: 'fa-sync-alt',           label: 'Rotar imagen',         fn: () => _scrollYBlink('image-rotation-slider') });
        acciones.push({ icon: 'fa-eye',                label: 'Opacidad',             fn: () => _scrollYBlink('opacity-slider') });
        acciones.push({ icon: 'fa-magic',              label: 'Filtros',              fn: () => { ocultarCtxMenu(); document.querySelector('.card-fotos').scrollIntoView({ behavior:'smooth', block:'start' }); setTimeout(() => _scrollYBlink('filter-section'), 400); } });
    }

    // Acciones directas comunes (sin scroll — son instantáneas)
    acciones.push({ icon: 'fa-arrows-alt-h', label: 'Centrar horizontal', fn: () => { ocultarCtxMenu(); obj.set({ left: activeCanvas.width/2,  originX:'center' }); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); aviso('Centrado horizontalmente', 'success'); } });
    acciones.push({ icon: 'fa-arrows-alt-v', label: 'Centrar vertical',   fn: () => { ocultarCtxMenu(); obj.set({ top:  activeCanvas.height/2, originY:'center' }); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); aviso('Centrado verticalmente', 'success'); } });
    acciones.push({ icon: 'fa-trash',        label: 'Eliminar',           fn: () => { activeCanvas.remove(obj); activeCanvas.renderAll(); guardarEstado(); ocultarCtxMenu(); }, color: '#e74c3c' });

    window._ctxAcciones = acciones.map(a => a.fn);

    inner.innerHTML = acciones.map((a, i) =>
        `<button onclick="window._ctxAcciones[${i}]()" style="display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;color:${a.color||'rgba(255,255,255,0.9)'};padding:7px 11px;border-radius:7px;cursor:pointer;font-size:11px;font-weight:700;text-align:left;white-space:nowrap;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">
            <i class="fas ${a.icon}" style="width:13px;text-align:center;flex-shrink:0;${a.color?'color:'+a.color:'color:#9b59b6'}"></i>${a.label}
        </button>`
    ).join('');
}

function mostrarCtxMenu(obj, x, y) {
    const menu = document.getElementById('ctx-menu');
    if (!menu) return;
    _buildCtxMenu(obj);

    const wrapper = document.getElementById('product-container');
    const wRect   = wrapper.getBoundingClientRect();

    menu.style.display = 'flex';
    menu.style.top  = '0px';
    menu.style.left = '0px';

    // x,y son coordenadas de pantalla (clientX/clientY) — position:fixed
    const mW = 165;
    const mH = menu.offsetHeight || 300;
    let left = x;
    let top  = y;
    // Ajustar para que no salga de la ventana
    if (left + mW > window.innerWidth  - 8) left = x - mW;
    if (top  + mH > window.innerHeight - 8) top  = y - mH;
    if (left < 8) left = 8;
    if (top  < 8) top  = 8;

    menu.style.left = left + 'px';
    menu.style.top  = top  + 'px';
}

function ocultarCtxMenu() {
    const menu = document.getElementById('ctx-menu');
    if (menu) menu.style.display = 'none';
    window._ctxAcciones = [];
}

// Cerrar al hacer clic fuera del menú
document.addEventListener('mousedown', function(e) {
    const menu = document.getElementById('ctx-menu');
    if (menu && menu.style.display !== 'none' && !menu.contains(e.target)) ocultarCtxMenu();
});

// Control personalizado de Fabric — botón ⋮ en esquina superior derecha
(function() {
    function renderCtrlBtn(ctx, left, top, fabricObject) {
        const size = 22;
        ctx.save();
        ctx.fillStyle = '#8e44ad';
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(left, top, size/2, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 13px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('⋮', left, top + 1);
        ctx.restore();
    }

    function makeCtxControl(offsetX, offsetY) {
        return new fabric.Control({
            x:  0.5,
            y: -0.5,
            offsetX: offsetX,
            offsetY: offsetY,
            cursorStyle: 'pointer',
            mouseUpHandler: function(eventData, transform) {
                const obj = transform.target;
                const canvasEl = obj.canvas.lowerCanvasEl;
                const rect = canvasEl.getBoundingClientRect();
                const zoom = obj.canvas.getZoom();
                const br   = obj.getBoundingRect(true);
                const x = rect.left + (br.left + br.width) * zoom;
                const y = rect.top  +  br.top              * zoom;
                ocultarCtxMenu();
                setTimeout(() => mostrarCtxMenu(obj, x, y), 10);
                return true;
            },
            render: renderCtrlBtn,
            cornerSize: 22
        });
    }

    // Canvas principal: botón fuera del objeto (esquina sup-der)
    fabric.Object.prototype.controls.ctxBtn = makeCtxControl(12, -12);

    // Mini lienzos: botón dentro del objeto para no ser recortado
    window._ctxBtnControlMini = makeCtxControl(-14, 14);
})();

function syncSlider() {
    const inner = document.getElementById('ctx-menu-inner');
    if (!menu || !inner) return;

    const isText  = obj.type && obj.type.includes('text');
    const isImage = obj.type === 'image';

    const acciones = [];

    // Acciones comunes
    acciones.push({ icon: 'fa-expand-arrows-alt', label: 'Hacer grande',   fn: () => { obj.set('scaleX', (obj.scaleX||1)*1.3); obj.set('scaleY', (obj.scaleY||1)*1.3); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); } });
    acciones.push({ icon: 'fa-compress-arrows-alt', label: 'Hacer pequeño', fn: () => { obj.set('scaleX', (obj.scaleX||1)*0.7); obj.set('scaleY', (obj.scaleY||1)*0.7); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); } });
    acciones.push({ icon: 'fa-arrows-alt-h', label: 'Centrar horizontal', fn: () => { obj.set({ left: activeCanvas.width/2, originX:'center' }); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); } });
    acciones.push({ icon: 'fa-arrows-alt-v', label: 'Centrar vertical',   fn: () => { obj.set({ top: activeCanvas.height/2, originY:'center' }); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); } });
    acciones.push({ icon: 'fa-clone',         label: 'Duplicar',           fn: () => duplicarObjeto() });
    acciones.push({ icon: 'fa-arrow-up',      label: 'Subir capa',         fn: () => { activeCanvas.bringForward(obj); activeCanvas.renderAll(); guardarEstado(); } });
    acciones.push({ icon: 'fa-arrow-down',    label: 'Bajar capa',         fn: () => { activeCanvas.sendBackwards(obj); activeCanvas.renderAll(); guardarEstado(); } });

    if (isImage) {
        acciones.push({ icon: 'fa-undo-alt',  label: 'Tamaño original',    fn: () => { obj.set({ scaleX:1, scaleY:1 }); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); } });
    }
    if (isText) {
        acciones.push({ icon: 'fa-sync-alt',  label: 'Enderezar (0°)',     fn: () => { obj.set('angle',0); obj.setCoords(); activeCanvas.renderAll(); guardarEstado(); const sl=document.getElementById('text-rotation-slider'); if(sl) sl.value=0; document.getElementById('text-rotation-val').innerText='0°'; } });
    }

    acciones.push({ icon: 'fa-trash',         label: 'Eliminar',           fn: () => { activeCanvas.remove(obj); activeCanvas.renderAll(); guardarEstado(); ocultarCtxMenu(); }, color: '#e74c3c' });

    inner.innerHTML = acciones.map((a,i) =>
        `<button onclick="(_ctxAcciones[${i}])(); _ctxAcciones=[];" style="display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;color:${a.color||'rgba(255,255,255,0.88)'};padding:6px 10px;border-radius:7px;cursor:pointer;font-size:11px;font-weight:700;text-align:left;transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">
            <i class="fas ${a.icon}" style="width:12px;text-align:center;flex-shrink:0;${a.color?'color:'+a.color:''}"></i>${a.label}
        </button>`
    ).join('');

    window._ctxAcciones = acciones.map(a => a.fn);

    // Posicionar sobre el objeto
    const canvasEl = activeCanvas.lowerCanvasEl || document.getElementById('tshirt-canvas');
    const rect = canvasEl.getBoundingClientRect();
    const wrapper = document.getElementById('product-container');
    const wRect = wrapper.getBoundingClientRect();
    const zoom = activeCanvas.getZoom();
    const br = obj.getBoundingRect(true);

    // Coordenadas relativas al wrapper
    const objTop  = rect.top - wRect.top + br.top * zoom;
    const objLeft = rect.left - wRect.left + br.left * zoom;
    const objW    = br.width * zoom;

    menu.style.display = 'flex';
    // Poner el menú centrado encima del objeto, o debajo si no hay espacio arriba
    const menuH = 280; // estimado
    let top = objTop - menuH - 6;
    if (top < 4) top = objTop + br.height * zoom + 6;
    let left = objLeft + objW/2 - 75;
    if (left < 4) left = 4;
    if (left + 160 > wrapper.offsetWidth) left = wrapper.offsetWidth - 164;

    menu.style.top  = top  + 'px';
    menu.style.left = left + 'px';
}

function ocultarCtxMenu() {
    const menu = document.getElementById('ctx-menu');
    if (menu) menu.style.display = 'none';
    window._ctxAcciones = [];
}

// Cerrar al hacer clic fuera
document.addEventListener('mousedown', function(e) {
    const menu = document.getElementById('ctx-menu');
    if (menu && !menu.contains(e.target)) ocultarCtxMenu();
});

function syncSlider() {
    const obj = activeCanvas.getActiveObject(); // AHORA LEE EL LIENZO ACTIVO

    const scaleSlider  = document.getElementById('image-scale-slider');
    const scaleDisplay = document.getElementById('scale-val');
    const rotSlider    = document.getElementById('image-rotation-slider');
    const rotDisplay   = document.getElementById('rotation-val');
    const textRotSlider  = document.getElementById('text-rotation-slider');
    const textRotDisplay = document.getElementById('text-rotation-val');
    const textSizeSlider = document.getElementById('text-size-slider');
    const textSizeDisplay = document.getElementById('text-size-val');

    if (obj && obj.type === 'image') {
        scaleSlider.value = obj.scaleX;
        scaleDisplay.innerText = Math.round(obj.scaleX * 100) + '%';

        let angulo = Math.round(obj.angle % 360);
        if (angulo > 180)  angulo -= 360;
        if (angulo < -180) angulo += 360;
        rotSlider.value = angulo;
        rotDisplay.innerText = angulo + '°';
    }

    if (obj && obj.type && obj.type.includes('text')) {
        if (textSizeSlider) textSizeSlider.value = obj.fontSize || 22;
        if (textSizeDisplay) textSizeDisplay.innerText = (obj.fontSize || 22) + 'px';
        let ta = Math.round((obj.angle || 0) % 360);
        if (ta > 180)  ta -= 360;
        if (ta < -180) ta += 360;
        if (textRotSlider)  textRotSlider.value = ta;
        if (textRotDisplay) textRotDisplay.innerText = ta + '°';

        // Espaciado y altura de línea
        const spacingSlider  = document.getElementById('letter-spacing-slider');
        const spacingDisplay = document.getElementById('spacing-val');
        const lhSlider       = document.getElementById('line-height-slider');
        const lhDisplay      = document.getElementById('lineheight-val');
        if (spacingSlider)  spacingSlider.value  = (obj.charSpacing || 0) / 10;
        if (spacingDisplay) spacingDisplay.innerText = ((obj.charSpacing || 0) / 10).toFixed(0);
        if (lhSlider)       lhSlider.value  = obj.lineHeight || 1.2;
        if (lhDisplay)      lhDisplay.innerText = (obj.lineHeight || 1.2).toFixed(1);

        // Opacidad del texto
        const textOpSlider  = document.getElementById('text-opacity-slider');
        const textOpDisplay = document.getElementById('text-opacity-val');
        const textOpVal = obj.opacity !== undefined ? obj.opacity : 1;
        if (textOpSlider)  textOpSlider.value = textOpVal;
        if (textOpDisplay) textOpDisplay.innerText = Math.round(textOpVal * 100) + '%';

        // Botón de alineación activo
        const currentAlign = obj.textAlign || 'left';
        document.querySelectorAll('.text-align-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.align === currentAlign);
        });

        // Negrita e itálica
        const btnB = document.getElementById('btn-bold');
        const btnI = document.getElementById('btn-italic');
        if (btnB) btnB.classList.toggle('active', obj.fontWeight === 'bold');
        if (btnI) btnI.classList.toggle('active', obj.fontStyle === 'italic');

        // Font picker
        const currentFont = (obj.fontFamily || 'Arial').replace(/'/g, '');
        document.querySelectorAll('.font-option').forEach(fo => {
            const foFamily = fo.getAttribute('onclick')
                ? fo.getAttribute('onclick').replace(/.*'([^']+)'.*/, '$1') : '';
            fo.classList.toggle('active', foFamily === currentFont);
        });
    }

    // Opacidad — aplica a cualquier objeto
    if (obj) {
        const opSlider  = document.getElementById('opacity-slider');
        const opDisplay = document.getElementById('opacity-val');
        if (opSlider)  opSlider.value = obj.opacity !== undefined ? obj.opacity : 1;
        if (opDisplay) opDisplay.innerText = Math.round((obj.opacity !== undefined ? obj.opacity : 1) * 100) + '%';
    }
}

function cambiarVista(nuevaVista) {
    if (vistaActual === nuevaVista) return;
    if (printBoundary) canvas.remove(printBoundary);
    memoriaDisenos[vistaActual] = canvas.toJSON(['selectable','evented','id']);

    document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-vista-' + nuevaVista).classList.add('active');
    var btnBottom = document.getElementById('btn-vista-' + nuevaVista + '-bottom');
    if (btnBottom) btnBottom.classList.add('active');
    vistaActual = nuevaVista;
    actualizarBtnVerLienzo();

    canvas.clear();
    actualizarBadgeActivo(canvas);

    const tipo = document.getElementById('product-type').value;
    const info = productos[tipo];
    /* fabric.Image.fromURL(info.img, function(img) {
        img.set({ scaleX: canvas.width / img.width, scaleY: canvas.height / img.height, selectable: false, evented: false });
        canvas.setBackgroundImage(img, function() { */
            if (memoriaDisenos[nuevaVista]) {
                canvas.loadFromJSON(memoriaDisenos[nuevaVista], function() { updateEditor(); calcularPrecio(); });
            } else {
                canvas.clear(); // Limpiar el lienzo si no hay diseño guardado
                if(printBoundary) canvas.add(printBoundary); // Re-añadir el borde si existe
                updateEditor(); 
                calcularPrecio();
            }
        /* });
    }); */
}

// ─── 10. BASE DE DATOS PRODUCTOS ─────────────────────────────
// Los productos ahora se cargan dinámicamente desde el servidor.

// ─── INIT ─────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('obtener_productos.php');
        if (!response.ok) {
            throw new Error(`Error de red al cargar productos: ${response.statusText}`);
        }
        const productosDB = await response.json();

        if (!productosDB || productosDB.length === 0) {
            console.error("No se pudieron cargar los productos desde obtener_productos.php o la respuesta estaba vacía.");
            // Opcional: mostrar un error al usuario en la UI
            return;
        }

        productosDB.forEach(p => {
            let opciones, labelText;
            if (p.categoria === 'sudadera') {
                opciones = ['S','M','L','XL','XXL']; // Fruit of the Loom: S a 2XL
                labelText = 'Talla:';
            } else if (p.categoria === 'camiseta') {
                opciones = ['S','M','L','XL','XXL','3XL','4XL','5XL'];
                labelText = 'Talla:';
            } else {
                opciones = ['S','M','L','XL','XXL','3XL','4XL','5XL'];
                labelText = 'Talla:';
            }
            let coloresBase = {
                'Transparente': null, 'Blanco': '#ffffff', 'Blanco roto': '#f5f0e8', 'Crema': '#fff8dc', 'Beige': '#f5e6c8', 'Arena': '#e8d5b0',
                'Gris claro': '#e0e0e0', 'Gris': '#bdc3c7', 'Gris oscuro': '#4a4a4a', 'Gris Jaspeado': '#9ca3af', 'Negro': '#1a1a1a',
                'Rojo': '#e74c3c', 'Burdeos': '#7b241c',
                'Rosa claro': '#ffd6e0', 'Rosa': '#fd79a8', 'Salmón': '#fa8072',
                'Naranja': '#e67e22', 'Amarillo': '#f1c40f', 'Dorado': '#d4ac0d',
                'Verde menta': '#a8e6cf', 'Verde': '#2ecc71', 'Verde oscuro': '#1e5631', 'Verde kaki': '#6b8e4e',
                'Azul cielo': '#87ceeb', 'Azul': '#3498db', 'Azul marino': '#1a3a5c',
                'Morado claro': '#d7bde2', 'Morado': '#9b59b6', 'Morado oscuro': '#6c3483',
                'Marrón claro': '#d4a574', 'Marrón': '#795548', 'Chocolate': '#3e1f00',
                'Turquesa': '#1abc9c', 'Coral': '#ff7f7f', 'Lavanda': '#e6e6fa'
            };
            let coloresProductoBase = { 'Blanco': '#ffffff', 'Negro': '#1a1a1a', 'Gris': '#95a5a6', 'Gris Jaspeado': '#9ca3af', 'Rojo': '#e74c3c', 'Azul': '#3498db', 'Azul marino': '#1a3a5c', 'Verde': '#2ecc71', 'Amarillo': '#f1c40f', 'Naranja': '#e67e22', 'Rosa': '#fd79a8', 'Morado': '#9b59b6', 'Marrón': '#795548' };
            if (p.categoria === 'taza') {
                opciones = ['Cerámica Blanca 11oz']; labelText = 'Material:';
                coloresBase = {
                    'Transparente': null, 'Blanco': '#ffffff', 'Blanco roto': '#f5f0e8', 'Crema': '#fff8dc',
                    'Gris claro': '#e0e0e0', 'Gris': '#bdc3c7', 'Negro': '#1a1a1a',
                    'Rojo': '#e74c3c', 'Burdeos': '#7b241c', 'Rosa': '#fd79a8', 'Salmón': '#fa8072',
                    'Naranja': '#e67e22', 'Amarillo': '#f1c40f', 'Dorado': '#d4ac0d',
                    'Verde menta': '#a8e6cf', 'Verde': '#2ecc71', 'Verde oscuro': '#1e5631',
                    'Azul cielo': '#87ceeb', 'Azul': '#3498db', 'Azul marino': '#1a3a5c',
                    'Morado claro': '#d7bde2', 'Morado': '#9b59b6',
                    'Marrón claro': '#d4a574', 'Marrón': '#795548',
                    'Turquesa': '#1abc9c', 'Coral': '#ff7f7f', 'Lavanda': '#e6e6fa'
                };
                coloresProductoBase = {};
            } else if (p.categoria === 'cuadro') {
                opciones = ['Aluminio', 'Pizarra', 'Azulejo']; labelText = 'Material:';
                coloresBase = {
                    'Transparente': null, 'Blanco': '#ffffff', 'Blanco roto': '#f5f0e8', 'Crema': '#fff8dc', 'Beige': '#f5e6c8',
                    'Gris claro': '#e0e0e0', 'Gris': '#bdc3c7', 'Gris oscuro': '#4a4a4a', 'Negro': '#1a1a1a',
                    'Rojo': '#e74c3c', 'Burdeos': '#7b241c', 'Rosa': '#fd79a8',
                    'Naranja': '#e67e22', 'Amarillo': '#f1c40f', 'Dorado': '#d4ac0d',
                    'Verde menta': '#a8e6cf', 'Verde': '#2ecc71', 'Verde oscuro': '#1e5631',
                    'Azul cielo': '#87ceeb', 'Azul': '#3498db', 'Azul marino': '#1a3a5c',
                    'Morado claro': '#d7bde2', 'Morado': '#9b59b6',
                    'Madera': '#f3e5ab', 'Marrón claro': '#d4a574', 'Marrón': '#795548', 'Chocolate': '#3e1f00',
                    'Turquesa': '#1abc9c', 'Coral': '#ff7f7f', 'Lavanda': '#e6e6fa'
                };
                coloresProductoBase = {};
            } else if (p.categoria === 'sudadera') {
                // Fruit of the Loom - Classic Hooded Sweat 62-208-0 (S-2XL)
                coloresProductoBase = {
                    'Blanco': '#FFFFFF', 'Girasol': '#F9A825', 'Negro': '#1C1C1C',
                    'Rojo': '#C0392B', 'Naranja': '#E67E22', 'Verde Kelly': '#27AE60',
                    'Azul Royal': '#1565C0', 'Fucsia': '#E91E8C', 'Gris Jaspeado': '#9E9E9E',
                    'Azul Marino Oscuro': '#0D1B3E', 'Gris Oscuro Jaspeado': '#555555',
                    'Rosa Claro': '#F48FB1', 'Burdeos': '#6D1717', 'Verde Oliva': '#556B2F',
                    'Verde Botella': '#1B5E20', 'Azul Cielo': '#87CEEB', 'Azul Azur': '#4169E1',
                    'Morado': '#6A0DAD', 'Marino': '#002366', 'Grafito Claro': '#607D8B',
                    'Chocolate': '#3E1C00',
                    'Vintage Heather Marino': '#4A6FA5', 'Retro Heather Royal': '#6B8CBA',
                    'Retro Heather Verde': '#6B8C6B', 'Vintage Heather Rojo': '#B24040'
                };
            } else if (p.categoria === 'camiseta') {
                // Fruit of the Loom - Valueweight T 61-036-0 (S-5XL)
                coloresProductoBase = {
                    'Blanco': '#FFFFFF', 'Girasol': '#F9A825', 'Negro': '#1C1C1C',
                    'Rojo': '#C0392B', 'Naranja': '#E67E22', 'Verde Kelly': '#27AE60',
                    'Azul Royal': '#1565C0', 'Fucsia': '#E91E8C', 'Gris Jaspeado': '#9E9E9E',
                    'Azul Marino Oscuro': '#0D1B3E', 'Gris Oscuro Jaspeado': '#555555',
                    'Amarillo': '#FFD600', 'Rosa Claro': '#F48FB1', 'Rojo Ladrillo': '#B03A2E',
                    'Burdeos': '#6D1717', 'Verde Oliva': '#556B2F', 'Verde Botella': '#1B5E20',
                    'Azul Cielo': '#87CEEB', 'Azul Azur': '#4169E1', 'Morado': '#6A0DAD',
                    'Marino': '#002366', 'Ceniza': '#B2BEB5', 'Grafito Claro': '#607D8B',
                    'Natural': '#F5F0E0', 'Caqui': '#C3B091', 'Chocolate': '#3E1C00',
                    'Lima': '#CDDC39',
                    'Vintage Heather Marino': '#4A6FA5', 'Retro Heather Royal': '#6B8CBA',
                    'Retro Heather Verde': '#6B8C6B', 'Vintage Heather Rojo': '#B24040',
                    'Heather Morado': '#7B68AA', 'Heather Burdeos': '#8B4757'
                };
            }
            productos[p.categoria] = {
                id_db: p.id, precio: parseFloat(p.precio), label: labelText, opts: opciones,
                img: p.imagen_url, isClothing: (p.categoria === 'camiseta' || p.categoria === 'sudadera'),
                colores: coloresBase, coloresProducto: coloresProductoBase
            };
        });

        // El resto del init
        generarColoresTexto();
        cargarBiblioteca();
        renderPlantillasPagina();
        initMiniCanvases();
        
        if (Object.keys(productos).length > 0) {
            updateProductLogic();
        } else {
            console.error("No hay productos cargados tras el fetch.");
        }

        if (editDiseno) {
            // Editar diseño desde el carrito: restaurar tipo, talla, color y lienzo
            setTimeout(() => {
                // 1. Seleccionar tipo de producto (sin el confirm de cambio)
                const tipoCard = document.querySelector('.product-card[onclick*="' + editDiseno.tipo + '"]');
                if (tipoCard) {
                    document.querySelectorAll('.product-card').forEach(c => c.classList.remove('active'));
                    tipoCard.classList.add('active');
                    const sel = document.getElementById('product-type');
                    if (sel) sel.value = editDiseno.tipo;
                    updateProductLogic();
                }

                setTimeout(() => {
                    // 2. Restaurar talla
                    const tallaSelect = document.getElementById('talla-select');
                    if (tallaSelect) tallaSelect.value = editDiseno.talla;

                    // 3. Restaurar colores (antes de updateEditor para que los use)
                    colorActual         = editDiseno.color;
                    colorProductoActual = editDiseno.color_producto;

                    // 4. Restaurar notas
                    const notasEl = document.getElementById('notas-extra');
                    if (notasEl) notasEl.value = editDiseno.notas || '';

                    // 5. Restaurar lienzo detrás en memoria (sin renderizar)
                    if (editDiseno.diseno_back) {
                        try {
                            memoriaDisenos['back'] = typeof editDiseno.diseno_back === 'string'
                                ? JSON.parse(editDiseno.diseno_back)
                                : editDiseno.diseno_back;
                        } catch(e) { console.warn('Error cargando diseno_back:', e); }
                    }

                    // 6. Restaurar lienzo delante: primero poner fondo de prenda,
                    //    luego dentro del callback cargar los objetos del diseño
                    const info = productos[editDiseno.tipo];
                    if (!info) { updateEditor(); return; }

                    const _bgVal = info.colores[colorActual];
                    canvas.backgroundColor = (_bgVal !== undefined && _bgVal !== null) ? _bgVal : null;

                    fabric.Image.fromURL(info.img, function(bgImg) {
                        bgImg.set({
                            scaleX: canvas.width  / bgImg.width,
                            scaleY: canvas.height / bgImg.height,
                            selectable: false, evented: false
                        });
                        canvas.setBackgroundImage(bgImg, function() {
                            // Fondo listo — ahora cargar los objetos del diseño encima
                            if (editDiseno.diseno_front) {
                                try {
                                    const jsonFront = typeof editDiseno.diseno_front === 'string'
                                        ? JSON.parse(editDiseno.diseno_front)
                                        : editDiseno.diseno_front;

                                    // Filtrar solo objetos editables (sin printBoundary)
                                    const objetos = (jsonFront.objects || []).filter(o =>
                                        o.id !== '__printBoundary__' && o.selectable !== false
                                    );

                                    const jsonLimpio = Object.assign({}, jsonFront, { objects: objetos });
                                    memoriaDisenos['front'] = jsonLimpio;

                                    canvas.loadFromJSON(jsonLimpio, function() {
                                        // Volver a poner fondo de prenda encima de los objetos cargados
                                        canvas.setBackgroundImage(bgImg, function() {
                                            // Añadir printBoundary
                                            if (printBoundary) canvas.remove(printBoundary);
                                            const _rawBg = info.colores[colorActual];
                                            const bgHex = (_rawBg !== undefined && _rawBg !== null) ? _rawBg : '#ffffff';
                                            const r = parseInt(bgHex.slice(1,3)||'ff',16);
                                            const g = parseInt(bgHex.slice(3,5)||'ff',16);
                                            const b = parseInt(bgHex.slice(5,7)||'ff',16);
                                            const lum = (0.299*r + 0.587*g + 0.114*b) / 255;
                                            const bc = lum > 0.6 ? 'rgba(0,0,0,0.55)' : 'rgba(255,255,255,0.7)';
                                            printBoundary = new fabric.Rect({
                                                width:220, height:290, fill:'transparent',
                                                stroke:bc, strokeDashArray:[5,5], strokeWidth:2,
                                                selectable:false, evented:false, opacity:1,
                                                originX:'center', originY:'center', left:160, top:190,
                                                id:'__printBoundary__'
                                            });
                                            canvas.add(printBoundary);
                                            canvas.renderAll();
                                            calcularPrecio();
                                            // Actualizar paletas de color
                                            updateEditor();

                                            // Restaurar mini lienzos (nuca y mangas)
                                            if (editDiseno.diseno_minis) {
                                                Object.entries(editDiseno.diseno_minis).forEach(([id, jsonStr]) => {
                                                    const fc = miniCanvases[id];
                                                    if (!fc || !jsonStr) return;
                                                    try {
                                                        const jsonMini = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                                                        fc.loadFromJSON(jsonMini, () => {
                                                            fc.renderAll();
                                                            syncMiniPrice();
                                                        });
                                                    } catch(e) { console.warn('Error cargando mini ' + id, e); }
                                                });
                                            }

                                            aviso('Diseño cargado — puedes seguir editando ✏️', 'success');
                                        });
                                    });
                                } catch(e) {
                                    console.warn('Error cargando diseno_front:', e);
                                    updateEditor();
                                }
                            } else {
                                updateEditor();
                            }
                        });
                    });
                }, 500);
            }, 700);

        } else if (recursoAutoParaCargar !== "") {
            setTimeout(() => {
                fabric.Image.fromURL(recursoAutoParaCargar, (fImg) => {
                    const sc = 150 / fImg.width;
                    fImg.set({ left: 160, top: 190, originX: 'center', originY: 'center', scaleX: sc, scaleY: sc });
                    canvas.add(fImg); canvas.setActiveObject(fImg);
                    canvas.renderAll();
                }, { crossOrigin: 'anonymous' });
            }, 500);
        }

        window.addEventListener('resize', escalarCanvas);
        setTimeout(escalarCanvas, 300);

    } catch (error) {
        console.error('Error fatal al inicializar el personalizador:', error);
        // Opcional: mostrar un mensaje de error permanente en la UI
    }
});

// ─── PREVISUALIZACIÓN EN MODAL ────────────────────────────────


// Exporta SOLO el diseño del usuario (sin prenda de fondo, sin printBoundary)
// jsonLado: memoriaDisenos[lado] o null para el lienzo activo
// multiplier: resolución de salida
function exportarSoloDiseno(jsonLado, multiplier) {
    multiplier = multiplier || 2;
    return new Promise(function(resolve) {
        var bgColor = canvas.backgroundColor || 'transparent';

        var objetos = [];
        if (jsonLado && jsonLado.objects) {
            objetos = jsonLado.objects.filter(function(o) {
                return o.id !== '__printBoundary__' && o.selectable !== false;
            });
            // Sin objetos de usuario → no descargar
            if (objetos.length === 0) { resolve(null); return; }
        } else {
            // jsonLado es null o sin .objects → vacío
            resolve(null); return;
        }

        var w = canvas.width, h = canvas.height;
        var tempC = new fabric.StaticCanvas(document.createElement('canvas'));
        tempC.setWidth(w); tempC.setHeight(h);
        var jsonFiltered = { version: jsonLado.version || '5.3.1', objects: objetos, background: bgColor };
        tempC.loadFromJSON(jsonFiltered, function() {
            tempC.renderAll();
            resolve(tempC.toDataURL({ format: 'png', multiplier: multiplier }));
            tempC.dispose();
        });
    });
}

function exportarLienzo() {
    if (printBoundary) printBoundary.visible = false;
    canvas.discardActiveObject();
    canvas.renderAll();
    const dataUrl = canvas.toDataURL({ format: 'png', multiplier: 2 });
    if (printBoundary) printBoundary.visible = true;
    canvas.renderAll();
    return dataUrl;
}

function renderLadoPreview(lado, targetImgId) {
    return new Promise(function(resolve) {
        const tipo = document.getElementById('product-type').value;
        const info = productos[tipo];
        const jsonLado = memoriaDisenos[lado];

        // Filtrar el printBoundary del JSON antes de renderizar
        let jsonFiltrado = jsonLado;
        if (jsonLado && jsonLado.objects) {
            jsonFiltrado = Object.assign({}, jsonLado, {
                objects: jsonLado.objects.filter(function(o) { return o.id !== '__printBoundary__'; })
            });
        }

        const tempCanvas = new fabric.Canvas(document.createElement('canvas'), {
            width: 320, height: 380, backgroundColor: canvas.backgroundColor
        });

        fabric.Image.fromURL(info.img, function(img) {
            img.set({ scaleX: 320 / img.width, scaleY: 380 / img.height, selectable: false, evented: false });
            tempCanvas.setBackgroundImage(img, function() {
                function finalize() {
                    tempCanvas.renderAll();
                    const dataUrl = tempCanvas.toDataURL({ format: 'png', multiplier: 2 });
                    document.getElementById(targetImgId).src = dataUrl;
                    tempCanvas.dispose();
                    resolve(dataUrl);
                }
                if (jsonFiltrado) {
                    tempCanvas.loadFromJSON(jsonFiltrado, finalize);
                } else {
                    finalize();
                }
            });
        });
    });
}

function abrirPreview() {
    previewVisto = true;
    memoriaDisenos[vistaActual] = canvas.toJSON(['selectable','evented','id']);

    const tipo   = document.getElementById('product-type').value;
    const esRopa = productos[tipo] && productos[tipo].isClothing;

    const single = document.getElementById('preview-single');
    const both   = document.getElementById('preview-both-sides');

    if (esRopa) {
        single.style.display = 'none';
        both.style.display   = 'flex';

        // Renderizar delante (si no hay memoria, usar lienzo actual si es front)
        if (!memoriaDisenos['front'] && vistaActual === 'front') {
            const dataUrl = exportarLienzo();
            document.getElementById('preview-img-front').src = dataUrl;
        } else {
            renderLadoPreview('front', 'preview-img-front');
        }

        // Renderizar detrás
        renderLadoPreview('back', 'preview-img-back').then(function(dataUrl) {
            // El enlace de descarga descarga la vista actual
            const dlUrl = vistaActual === 'back' ? dataUrl : document.getElementById('preview-img-front').src;
        });
    } else {
        single.style.display = '';
        both.style.display   = 'none';
        const dataUrl = exportarLienzo();
        document.getElementById('preview-img').src = dataUrl;
    }

    // Zonas extra: nuca y mangas
    const zonasWrap = document.getElementById('preview-zonas-extra');
    if (esRopa) {
        MINI_IDS.forEach(id => {
            const fc   = miniCanvases[id];
            const zona = document.getElementById('preview-zona-' + id);
            const img  = document.getElementById('preview-img-' + id);
            if (!zona || !img) return;
            const dataU = fc ? fc.toDataURL({ format: 'png', multiplier: 2 }) : '';
            img.src = dataU;
            zona.style.display = 'block';
        });
    } else {
        MINI_IDS.forEach(id => {
            const zona = document.getElementById('preview-zona-' + id);
            if (zona) zona.style.display = 'none';
        });
    }
    zonasWrap.style.display = esRopa ? 'block' : 'none';

    document.getElementById('modal-preview').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}


function cerrarPreview() {
    document.getElementById('modal-preview').style.display = 'none';
    document.body.style.overflow = '';
}

function descargarDesdeModal() {
    memoriaDisenos[vistaActual] = canvas.toJSON(['selectable','evented','id']);
    const tipo   = document.getElementById('product-type').value;
    const esRopa = productos[tipo] && productos[tipo].isClothing;

    let dlDelay = 0;
    function dl(dataUrl, nombre) {
        setTimeout(function() {
            const link = document.createElement('a');
            link.download = nombre; link.href = dataUrl; link.click();
        }, dlDelay);
        dlDelay += 700;
    }

    if (esRopa) {
        Promise.all([
            exportarSoloDiseno(memoriaDisenos['front'], 2),
            exportarSoloDiseno(memoriaDisenos['back'],  2)
        ]).then(function(urls) {
            if (urls[0]) dl(urls[0], 'diseno-camiglobo-delante.png');
            if (urls[1]) dl(urls[1], 'diseno-camiglobo-detras.png');
        });
    } else {
        exportarSoloDiseno(memoriaDisenos[vistaActual], 2).then(function(dataUrl) {
            if (dataUrl) dl(dataUrl, 'diseno-camiglobo.png');
        });
    }
}

// ─── SLIDERS: mantener pulsado 300ms para activar, luego arrastrar ───────────
(function() {
    const HOLD_MS = 300;
    const MOVE_PX = 8;

    function setSliderFromX(slider, clientX) {
        const rect  = slider.getBoundingClientRect();
        const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
        const min   = parseFloat(slider.min)  || 0;
        const max   = parseFloat(slider.max)  || 100;
        const step  = parseFloat(slider.step) || 1;
        const raw   = min + ratio * (max - min);
        slider.value = Math.round(raw / step) * step;
        slider.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function initSliderHold(slider) {
        const overlay = document.createElement('div');
        // El overlay cubre SOLO el slider, sin expandirse hacia los botones adyacentes
        overlay.style.cssText = 'position:absolute;z-index:10;touch-action:none;-webkit-tap-highlight-color:transparent;cursor:pointer;';

        const parent = slider.parentNode;
        if (getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }
        parent.appendChild(overlay);

        // Ajustar el overlay exactamente sobre el slider (sin desbordarse)
        function fitOverlay() {
            overlay.style.top    = slider.offsetTop  + 'px';
            overlay.style.left   = slider.offsetLeft + 'px';
            overlay.style.width  = slider.offsetWidth  + 'px';
            overlay.style.height = slider.offsetHeight + 'px';
        }
        window.addEventListener('load',   fitOverlay);
        window.addEventListener('resize', fitOverlay);
        setTimeout(fitOverlay, 100);

        let holdTimer = null;
        let startX = 0, startY = 0;
        let activated = false;

        function deactivate() {
            activated = false;
            clearTimeout(holdTimer);
            holdTimer = null;
            slider.style.boxShadow = '';
        }

        overlay.addEventListener('touchstart', function(e) {
            // Si el toque está sobre un botón vecino, no interceptar
            const touch = e.touches[0];
            const el = document.elementFromPoint(touch.clientX, touch.clientY);
            if (el && (el.tagName === 'BUTTON' || el.closest('button'))) return;

            e.preventDefault();
            startX = touch.clientX;
            startY = touch.clientY;
            activated = false;

            slider.style.transition = 'box-shadow 0.3s';
            slider.style.boxShadow  = '0 0 0 4px rgba(52,152,219,0.45)';

            holdTimer = setTimeout(function() {
                activated = true;
                slider.style.boxShadow = '0 0 0 5px rgba(52,152,219,0.75)';
                setSliderFromX(slider, startX);
            }, HOLD_MS);
        }, { passive: false });

        overlay.addEventListener('touchmove', function(e) {
            const cx = e.touches[0].clientX;
            const cy = e.touches[0].clientY;

            if (!activated) {
                const dx = Math.abs(cx - startX);
                const dy = Math.abs(cy - startY);
                if (dx > MOVE_PX || dy > MOVE_PX) deactivate();
                return;
            }

            // Slider activo: seguir el dedo horizontalmente
            e.preventDefault();
            setSliderFromX(slider, cx);
        }, { passive: false });

        overlay.addEventListener('touchend',    deactivate, { passive: true });
        overlay.addEventListener('touchcancel', deactivate, { passive: true });
    }

    window.addEventListener('load', function() {
        if (!('ontouchstart' in window)) return;
        document.querySelectorAll('input[type="range"]').forEach(initSliderHold);
    });
})();
</script>

<style>
/* ============================================================
   CAMIGLOBO — CSS COMPLETO
   ============================================================ */

html { max-width: 100%; }
body { overflow-x: hidden; max-width: 100%; }
.cg-container { margin-top: 15px; margin-bottom: 40px; padding-left: 10px; padding-right: 10px; box-sizing: border-box; max-width: 100%; }

/* En PC: usar casi todo el ancho disponible */
/* === FAB MENÚ FLOTANTE (solo móvil) === */
#fab-menu,
.fab-menu {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    flex-direction: column-reverse;
    gap: 10px;
}
.fab-btn {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #3498db;
    color: #fff;
    border: none;
    font-size: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.fab-btn.fab-main { background: #e74c3c; font-size: 24px; }
.fab-menu.open .fab-sub { opacity: 1; transform: translateY(0); pointer-events: auto; }

@media (min-width: 1101px) {
    .cg-container {
        padding-left: 20px;
        padding-right: 20px;
        max-width: 100%;
    }
    .container.cg-container {
        max-width: 100% !important;
        width: 100% !important;
    }
}

.editor-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 18px;
    align-items: start;
}

/* En PC grande: más ancho el panel herramientas, canvas más compacto */
@media (min-width: 1400px) {
    .editor-grid {
        grid-template-columns: 1fr 380px;
        gap: 20px;
    }
}

.tools-panel {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-areas:
        "producto producto producto"
        "fotos    texto    plantillas-col"
        "compra   compra   compra";
    gap: 12px;
    align-items: stretch;
    min-width: 0;
}
.ptb-in-tools           { grid-area: producto; margin-bottom: 0; }
.card-fotos             { grid-area: fotos; }
.card-texto             { grid-area: texto; }
.plantillas-notas-col   { grid-area: plantillas-col; display: flex; flex-direction: column; gap: 12px; }
.buy-card               { grid-area: compra; }

.canvas-panel {
    background: #fff;
    padding: 10px;
    border-radius: 22px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.05);
    border: 1px solid #f0f0f0;
    position: sticky;
    top: 70px;
    align-self: start;
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.canvas-center-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    box-sizing: border-box;
}
.canvas-wrapper {
    flex-shrink: 0;
    flex-grow: 0;
    border-radius: 16px;
    overflow: visible;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: repeating-conic-gradient(#f0f0f0 0% 25%, #ffffff 0% 50%) 0 0 / 12px 12px;
    /* el ancho lo pone escalarCanvas() via JS */
}
.canvas-wrapper canvas { border-radius: 16px; }
#tshirt-canvas  { display: block; }
/* NO forzar nada en .canvas-container — Fabric lo gestiona */

/* ── LAYOUT PARALELO (2 COLUMNAS) ───────────────────────────── */
.lienzo-paralelo-wrap {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-start;
    gap: 30px;
    width: 100%;
    box-sizing: border-box;
    flex-wrap: wrap;
}
.zonas-extra-col {
    display: flex;
    flex-direction: column;
    width: 320px;
    max-width: 100%;
    flex-shrink: 1;
    box-sizing: border-box;
}

.mini-section-title {
    font-size: 10px;
    color: #3498db;
    font-weight: 800;
    text-align: center;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f0f8ff;
    border: 1px solid #d6eaf8;
    border-radius: 10px;
    padding: 7px 10px;
}
.mini-section-label {
    font-size: 10px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    padding-left: 4px;
}
/* ── MINI LIENZOS (Nuca + Mangas) ───────────────────────────── */
.mini-canvases-row {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}
.mini-canvas-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    border: 2px solid #e8e8e8;
    border-radius: 14px;
    padding: 8px 6px;
    min-width: 0;
    flex: 1;
    max-width: 155px;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
    overflow: hidden;
    box-sizing: border-box;
}
.mini-canvas-block.mini-active {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
    background: #f0f8ff;
}
.mini-canvas-label {
    font-size: 10px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.mini-canvas-inner {
    border-radius: 8px;
    overflow: visible;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    width: 140px;
    height: 100px;
    flex-shrink: 0;
    /* Efecto cuadrícula para indicar que el fondo es transparente (Sin tinta) */
    background: repeating-conic-gradient(#f0f0f0 0% 25%, #ffffff 0% 50%) 0 0 / 12px 12px;
}
.mini-canvas-inner canvas { display: block; border-radius: 8px; }
.mini-canvas-tools {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
    width: 100%;
    box-sizing: border-box;
}
.mini-btn-text, .mini-btn-upload, .mini-btn-color, .mini-btn-del {
    padding: 6px 6px;
    border-radius: 7px;
    border: none;
    font-size: 9px;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: 0.15s;
    text-transform: uppercase;
    white-space: nowrap;
    height: 28px;
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    min-width: 0;
}
.mini-btn-text   { background: #2c3e50; color: #fff; }
.mini-btn-text:hover { background: #34495e; }
.mini-btn-upload { background: #3498db; color: #fff; cursor: pointer; }
.mini-btn-upload:hover { background: #2980b9; }
.mini-btn-color  { background: #8e44ad; color: #fff; }
.mini-btn-color:hover { background: #6c3483; }
.mini-btn-del    { background: #e74c3c; color: #fff; }
.mini-btn-del:hover { background: #c0392b; }

/* ── WRAPPER + PICKER COLOR MINI (limpiado) ─────────────────── */
.mini-btn-color-wrap { display: none; } /* ya no se usa, queda por compatibilidad */

/* ── NOTAS DEL PEDIDO ────────────────────────────────────────── */
.notas-hints {
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 14px;
    padding: 8px 12px;
    margin-bottom: 8px;
}
.notas-hint-title {
    font-size: 10px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.notas-hint-title i { color: #f1c40f; font-size: 12px; }
.notas-hint-lista { display: flex; flex-direction: column; gap: 2px; }
.notas-hint-item {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    font-size: 10px;
    color: #555;
    line-height: 1.4;
    padding: 3px 0;
    border-bottom: 1px solid #f0f0f0;
}
.notas-hint-item:last-child { border-bottom: none; }
.notas-hint-item i { margin-top: 1px; flex-shrink: 0; width: 12px; text-align: center; }
.notas-hint-item strong { color: #2c3e50; }
.notas-textarea {
    height: 90px;
    resize: vertical;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.6;
    border: 2px dashed #f1c40f !important;
    background: #fffef0 !important;
    border-radius: 12px !important;
    padding: 12px 14px !important;
    color: #2c3e50 !important;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    cursor: text;
}
.notas-textarea::placeholder {
    color: #b8a030;
    font-style: italic;
    font-size: 11px;
}
.notas-textarea:focus {
    border-color: #e6b800 !important;
    background: #fffde0 !important;
    box-shadow: 0 0 0 3px rgba(241,196,15,0.2) !important;
    outline: none;
}

.precio-live-wrap {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 12px;
    padding: 10px 16px;
    margin-bottom: 10px;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
}
.precio-live-inner { display: flex; align-items: center; justify-content: center; gap: 10px; }
.precio-live-label { font-size: 9px; font-weight: 900; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; }
.precio-live-num { font-size: 22px; font-weight: 900; color: #2ecc71; letter-spacing: -0.5px; line-height: 1; transition: all 0.3s ease; }
.precio-live-desglose { display: none; flex-direction: column; gap: 3px; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px; }
.plde-base { font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 700; }
.plde-extra { font-size: 10px; color: #f39c12; font-weight: 700; }
.plde-extra strong { color: #f1c40f; }

/* ── TOOLBAR ─────────────────────────────────────────────── */
.pro-toolbar {
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 15px;
    padding: 10px 12px;
    margin-bottom: 14px;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
    align-self: stretch;
}

.toolbar-hint-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
    font-size: 10px;
    color: #888;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Badge atajos de teclado — rediseñado limpio */
.toolbar-atajos-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 5px 10px;
    font-size: 10px;
    font-weight: 700;
    color: #555;
    white-space: nowrap;
}
.toolbar-atajos-badge i {
    color: #3498db;
    font-size: 11px;
}
.atajos-sep {
    color: #ccc;
    font-weight: 400;
    margin: 0 1px;
}

kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f0f4f8;
    border: 1px solid #c8d6e5;
    border-bottom: 2px solid #b0c4d8;
    border-radius: 5px;
    padding: 2px 6px;
    font-family: monospace;
    font-size: 9.5px;
    font-weight: 900;
    color: #2c3e50;
    line-height: 1.4;
    min-width: 18px;
}

/* Botones toolbar — agrupados y centrados */
.toolbar-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
    max-width: 100%;
    overflow-x: auto;
}

/* Grupos de botones con ligero fondo */
.toolbar-group {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 12px;
    padding: 4px 5px;
}

.tool-btn {
    background: transparent;
    border: none;
    color: #555;
    width: auto;
    min-width: 48px;
    height: auto;
    padding: 6px 8px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    transition: background 0.15s, color 0.15s, transform 0.15s;
    flex-shrink: 0;
    gap: 2px;
}
.tool-btn:hover {
    background: #2c3e50;
    color: #fff;
    transform: translateY(-1px);
}

/* Label pequeño dentro del botón (+ / −) */
.tool-btn-label {
    font-size: 13px;
    font-weight: 900;
    line-height: 1;
}

/* Texto debajo del icono */
.tool-btn-text {
    display: block;
    font-size: 7px;
    font-weight: 800;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tool-undo { color: #2980b9; background: #eaf4ff !important; }
.tool-undo:hover { background: #2980b9 !important; color: #fff !important; }
.tool-redo { color: #27ae60; background: #eafaf1 !important; }
.tool-redo:hover { background: #27ae60 !important; color: #fff !important; }
.tool-rotate-l { color: #8e44ad; }
.tool-rotate-l:hover { background: #8e44ad !important; color: #fff !important; }
.tool-rotate-r { color: #8e44ad; }
.tool-rotate-r:hover { background: #8e44ad !important; color: #fff !important; }
.tool-duplicate { color: #16a085; }
.tool-duplicate:hover { background: #16a085 !important; color: #fff !important; }

.tool-del { color: #e74c3c !important; background: #fff0f0 !important; }
.tool-del:hover { background: #e74c3c !important; color: #fff !important; }

.tool-reset { color: #e67e22 !important; }
.tool-reset:hover { background: #e67e22 !important; color: #fff !important; }

/* Botón zona de impresión */
.tool-boundary { color: #e67e22 !important; }
.tool-boundary:hover { background: #e67e22 !important; color: #fff !important; }
.tool-boundary.active { background: #e74c3c !important; color: #fff !important; animation: pulse-boundary 1.5s infinite; }

@keyframes pulse-boundary {
    0%, 100% { box-shadow: 0 0 0 0 rgba(231,76,60,0.3); }
    50%       { box-shadow: 0 0 0 5px rgba(231,76,60,0); }
}

/* Parpadeo de control al navegar desde menú contextual */
@keyframes ctx-blink-anim {
    0%   { outline: 3px solid transparent; background: transparent; }
    15%  { outline: 3px solid #9b59b6; background: rgba(155,89,182,0.2); border-radius: 8px; }
    35%  { outline: 3px solid #9b59b6; background: rgba(155,89,182,0.35); border-radius: 8px; }
    50%  { outline: 3px solid #e74c3c; background: rgba(155,89,182,0.15); border-radius: 8px; }
    65%  { outline: 3px solid #9b59b6; background: rgba(155,89,182,0.35); border-radius: 8px; }
    80%  { outline: 3px solid #9b59b6; background: rgba(155,89,182,0.2); border-radius: 8px; }
    100% { outline: 3px solid transparent; background: transparent; }
}
.ctx-blink { animation: ctx-blink-anim 2.4s ease forwards; }

/* Separador entre grupos */
.tool-sep { width: 1px; height: 28px; background: #e8e8e8; margin: 0 2px; flex-shrink: 0; }

.tool-card { display: flex; flex-direction: column; background: #fff; padding: 14px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.03); border: 1px solid #f3f3f3; min-width: 0; max-width: 100%; box-sizing: border-box; overflow: visible; }
.tool-card:nth-child(2) { padding: 12px 14px; }
.tool-title { margin-bottom: 10px; font-size: 13px; font-weight: 900; color: #2c3e50; text-transform: uppercase; border-bottom: 2px solid #f8f8f8; padding-bottom: 8px; display: flex; align-items: center; gap: 8px; }
.tool-row-flex { display: flex; gap: 10px; margin-bottom: 12px; }

.buy-card { background: #2c3e50; color: #fff; padding: 22px 28px !important; border-radius: 25px; box-shadow: 0 15px 35px rgba(44,62,80,0.2); display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 20px; }
.precio-wrap  { display: flex; flex-direction: column; gap: 4px; }
.precio-label { font-weight: 800; font-size: 12px; opacity: 0.7; letter-spacing: 1px; text-transform: uppercase; }
.precio-num   { font-size: 30px; font-weight: 900; color: #2ecc71; line-height: 1; }
.buy-buttons  { display: flex; gap: 10px; }

.extra-posicion-bloque { margin-bottom: 10px; border: 1px solid #f0f0f0; border-radius: 12px; padding: 10px 12px; background: #fafafa; }
.extra-checkbox-label  { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.extra-checkbox-label input[type="checkbox"] { width: 18px; height: 18px; accent-color: #f1c40f; cursor: pointer; flex-shrink: 0; }
.extra-checkbox-txt    { font-size: 11px; font-weight: 700; color: #2c3e50; }
.extra-checkbox-txt strong { color: #e67e22; }
.panel-extra-logo      { margin-top: 10px; animation: fadeInDown 0.2s; }
.btn-extra-upload      { background: #e67e22 !important; font-size: 10px !important; padding: 10px !important; margin-bottom: 8px; }
.preview-logo-box      { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px dashed #ccc; border-radius: 8px; padding: 6px 10px; }
.preview-logo-box img  { max-height: 35px; border-radius: 4px; }
.btn-borrar-logo       { background: #e74c3c; color: #fff; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; font-size: 10px; display: flex; align-items: center; justify-content: center; margin-left: auto; flex-shrink: 0; }

.desglose-box    { background: #f8f9fa; border: 1px solid #eee; border-radius: 12px; padding: 10px 14px; margin-top: 10px; font-size: 11px; }
.desglose-linea  { display: flex; justify-content: space-between; padding: 3px 0; color: #555; }
.desglose-linea.extra { color: #e67e22; font-weight: 700; }
.desglose-linea.total { border-top: 1px solid #ddd; margin-top: 5px; padding-top: 6px; font-weight: 900; color: #2c3e50; font-size: 12px; }

.label-sm { font-size: 10px; color: #aaa; text-transform: uppercase; margin-bottom: 8px; display: block; font-weight: 800; letter-spacing: 0.5px; }
.custom-select { width: 100%; padding: 11px; border-radius: 12px; border: 2px solid #f0f0f0; font-weight: 700; font-size: 13px; outline: none; background: #fff; box-sizing: border-box; }
.btn-upload-label { background: #2c3e50; color: #fff; padding: 13px; border-radius: 12px; font-weight: 800; font-size: 12px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-align: center; line-height: 1; box-sizing: border-box; }
.btn-add-text { background: #e74c3c; color: #fff; border: none; padding: 13px; border-radius: 12px; font-weight: 800; font-size: 12px; text-transform: uppercase; width: 100%; margin-bottom: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; line-height: 1; box-sizing: border-box; }
.btn-guia-tallas { font-size: 11px; color: #3498db; cursor: pointer; font-weight: 800; background: #ebf5fb; padding: 4px 8px; border-radius: 6px; white-space: nowrap; }

.palette-container   { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 5px; }
.color-circle        { width: 26px; height: 26px; border-radius: 50%; border: 3px solid #eee; cursor: pointer; transition: 0.2s; flex-shrink: 0; position: relative; }
.color-circle:hover  { transform: scale(1.15); border-color: #bbb; }
.color-circle.active { border-color: #e74c3c !important; transform: scale(1.2); box-shadow: 0 0 0 2px #fff, 0 0 0 4px #e74c3c; }
.color-nombre-display { font-size: 10px; font-weight: 800; color: #2c3e50; margin-top: 5px; display: block; min-height: 14px; letter-spacing: 0.3px; }
.color-nombre-prenda  { font-size: 10px; font-weight: 800; color: #2c3e50; margin-top: 5px; display: block; min-height: 14px; letter-spacing: 0.3px; }
.text-color-circle   { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #eee; cursor: pointer; flex-shrink: 0; transition: transform 0.15s, border-color 0.15s; }
.text-color-circle:hover { transform: scale(1.25); border-color: #2c3e50; }

/* Filtros modernos */
.filter-grid-modern {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}
.fbtn {
    padding: 4px 1px;
    font-size: clamp(6px, 0.75vw, 8px) !important;
    border-radius: 10px;
    border: 1.5px solid #eee;
    background: #fff;
    cursor: pointer;
    font-weight: 800;
    text-transform: uppercase;
    transition: all 0.15s;
    letter-spacing: 0px;
    text-align: center;
    color: #444;
    width: 100%;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fbtn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.fbtn:active { transform: translateY(0); }
.fbtn-reset    { background: #ffeaea; color: #e74c3c; border-color: #ffc8c8; }
.fbtn-gray     { background: #f0f0f0; color: #555; border-color: #ddd; }
.fbtn-bw       { background: linear-gradient(135deg,#fff 50%,#222 50%); color: #333; border-color: #ccc; }
.fbtn-sepia    { background: #f5e6c8; color: #7a5c2e; border-color: #e8d0a0; }
.fbtn-brownie  { background: #e8d5b0; color: #5c3d1e; border-color: #d4b896; }
.fbtn-kodak    { background: #fff3e0; color: #e65100; border-color: #ffcc80; }
.fbtn-tech     { background: linear-gradient(135deg,#ff6b6b,#4ecdc4); color: #fff; border-color: transparent; }
.fbtn-polar    { background: #e8f4f8; color: #2980b9; border-color: #aed6f1; }
.fbtn-contrast { background: linear-gradient(135deg,#fff 40%,#000 60%); color: #333; border-color: #ccc; }
.fbtn-bright   { background: #fffde7; color: #f57f17; border-color: #fff176; }
.fbtn-blur     { background: #e3f2fd; color: #1565c0; border-color: #90caf9; filter: blur(0.4px); }
.fbtn-blur:hover { filter: none; }
.fbtn-sharp    { background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; }
.fbtn-pixel    { background: #f3e5f5; color: #6a1b9a; border-color: #ce93d8; }
.fbtn-tint     { background: #fce4ec; color: #c62828; border-color: #f48fb1; }
.fbtn-vib      { background: linear-gradient(135deg,#fd79a8,#fdcb6e); color: #fff; border-color: transparent; }
.fbtn-noise    { background: #eceff1; color: #455a64; border-color: #b0bec5; }
/* ── INPUT DE TEXTO ─────────────────────────────────────────── */
.text-input-wrap {
    display: flex;
    gap: 8px;
    align-items: stretch;
    margin-bottom: 14px;
    animation: textInputAppear 0.4s ease;
}
@keyframes textInputAppear {
    0% { opacity: 0; transform: translateY(-10px) scale(0.97); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
.text-input-field {
    flex: 1;
    padding: 14px 16px;
    border: 3px solid #e74c3c;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 700;
    color: #2c3e50;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.3s;
    min-width: 0;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(231,76,60,0.15);
    animation: inputGlow 1.5s ease 2;
}
@keyframes inputGlow {
    0%, 100% { box-shadow: 0 0 0 4px rgba(231,76,60,0.15); }
    50% { box-shadow: 0 0 0 8px rgba(231,76,60,0.25), 0 0 20px rgba(231,76,60,0.1); }
}
.text-input-field:focus {
    border-color: #e74c3c;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(231,76,60,0.2), 0 0 15px rgba(231,76,60,0.15);
}
.text-input-field::placeholder {
    color: #aaa;
    font-weight: 600;
}
.btn-add-text-inline {
    background: #e74c3c;
    color: #fff;
    border: none;
    padding: 10px 14px;
    border-radius: 12px;
    font-weight: 900;
    font-size: 11px;
    text-transform: uppercase;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s, transform 0.15s;
    flex-shrink: 0;
}
.btn-add-text-inline:hover { background: #c0392b; transform: translateY(-1px); }

.font-picker {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
    margin-top: 4px;
}
.font-option {
    padding: 7px 4px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    background: #fafafa;
    cursor: pointer;
    font-size: 11px;
    color: #2c3e50;
    text-align: center;
    transition: 0.2s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.font-option:hover { border-color: #bdc3c7; background: #fff; transform: translateY(-1px); }
.font-option.active { border-color: #e74c3c; background: #fff5f5; color: #e74c3c; box-shadow: 0 3px 10px rgba(231,76,60,0.12); }

@media (max-width: 640px) {
    .font-picker { grid-template-columns: repeat(3, 1fr); }
    .font-option { font-size: 11px; padding: 9px 3px; }
    .text-input-wrap { flex-direction: column; }
    .btn-add-text-inline { width: 100%; justify-content: center; padding: 14px; font-size: 13px; }
}

.resource-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    overflow: visible;
}
.thumb-wrap {
    position: relative;
    flex-shrink: 0;
}
.thumb-img {
    width: 54px;
    height: 54px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid #eee;
    cursor: pointer;
    display: block;
    transition: border-color 0.2s, transform 0.15s;
}
.thumb-img:hover { border-color: #3498db; transform: scale(1.05); }
.btn-del-rec {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
}
.btn-del-rec   { position: absolute; top: -6px; right: -6px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 9px; display: flex; align-items: center; justify-content: center; cursor: pointer; }

.btn-view        { padding: 12px 24px; font-size: 12px; font-weight: 900; text-transform: uppercase; border-radius: 14px; border: 2px solid #ddd; background: #fff; color: #888; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; letter-spacing: 1px; }
.btn-view:hover  { border-color: #e74c3c; color: #e74c3c; background: #fff5f5; }
.btn-view.active { background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; border-color: #e74c3c; box-shadow: 0 4px 15px rgba(231,76,60,0.4); }

.btn-download       { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; font-weight: 700; padding: 12px 16px; cursor: pointer; font-size: 11px; text-transform: uppercase; transition: 0.3s; white-space: nowrap; display: flex; align-items: center; gap: 8px; }
.btn-download:hover { background: rgba(255,255,255,0.2); }
.btn-finish         { background: #27ae60; color: #fff; border: none; padding: 12px 16px; border-radius: 12px; font-weight: 900; cursor: pointer; font-size: 12px; text-transform: uppercase; box-shadow: 0 8px 20px rgba(39,174,96,0.3); transition: 0.3s; white-space: nowrap; display: flex; align-items: center; gap: 8px; }
.btn-finish:hover   { background: #2ecc71; transform: translateY(-2px); }

/* ── BARRA SUPERIOR DE PRODUCTO ───────────────────────────── */
.product-top-bar {
    background: white;
    border-radius: 16px;
    border: 1px solid #f0f0f0;
    border-left: 6px solid #3498db;
    box-shadow: 0 5px 20px rgba(0,0,0,0.03);
    padding: 12px 18px;
    margin-bottom: 16px;
}

/* Cuando está dentro del tools-panel: sin margin-bottom extra */
.ptb-in-tools {
    margin-bottom: 0;
    padding: 8px 12px 10px;
    overflow: visible;
}

/* ── LAYOUT COMPACTO HORIZONTAL DE PRODUCTO ──────────────────── */
.ptb-compact-row {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 12px;
    min-height: 90px;
    overflow: visible;
    box-sizing: border-box;
    padding-top: 4px;
}

.ptb-compact-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex-shrink: 0;
}
.ptb-compact-title {
    font-size: 9px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    text-align: center;
    line-height: 1.3;
}

/* 4 tarjetas en fila horizontal, rediseño bonito */
.ptb-compact-products {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 100px));
    gap: 8px;
    flex-shrink: 0;
    max-width: 216px;
}
.ptb-compact-products .product-card {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
    padding: 14px 8px 12px;
    border-radius: 14px;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
    text-align: center;
    background: #f4f6f9;
    border: 2px solid transparent;
    transition: all 0.2s;
    aspect-ratio: 1;
}
.ptb-compact-products .product-card:hover {
    background: #eaf4ff;
    border-color: #90cdf4;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(52,152,219,0.15);
}
.ptb-compact-products .product-card.active {
    background: linear-gradient(145deg, #e8f4fd, #d0eaf8);
    border-color: #3498db;
    box-shadow: 0 4px 12px rgba(52,152,219,0.25);
    transform: translateY(-2px);
}
.ptb-compact-products .pc-icon-wrap {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #94a3b8;
    margin-bottom: 7px;
    transition: all 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.ptb-compact-products .product-card:hover .pc-icon-wrap,
.ptb-compact-products .product-card.active .pc-icon-wrap {
    box-shadow: 0 2px 8px rgba(52,152,219,0.3);
}
.ptb-compact-products .pc-name {
    font-size: 9.5px;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    line-height: 1.2;
}
.ptb-compact-products .product-card.active .pc-name {
    color: #1e6fa8;
}
.ptb-compact-products .pc-price {
    font-size: 9px;
    font-weight: 700;
    color: #27ae60;
    margin-top: 3px;
}

/* Sección talla + colores */
.ptb-compact-options {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 0;
    justify-content: center;
    min-height: 90px;
}
.ptb-compact-colores {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.ptb-compact-options .label-sm {
    margin-bottom: 2px;
    font-size: 9px;
}

/* Paleta con scroll horizontal para que los círculos no se salgan */
.ptb-palette-scroll {
    flex-wrap: nowrap !important;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: thin;
    scrollbar-color: #ddd transparent;
    padding-bottom: 3px;
    gap: 4px !important;
}
.ptb-palette-scroll::-webkit-scrollbar { height: 4px; }
.ptb-palette-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
/* Círculo transparente (ajedrez) */
.color-circle.transparent-circle {
    background: repeating-conic-gradient(#d0d0d0 0% 25%, #ffffff 0% 50%) 0 0 / 8px 8px !important;
    border: 2px solid #ccc !important;
}
.ptb-palette-scroll .color-circle {
    width: 22px !important;
    height: 22px !important;
    flex-shrink: 0;
}

/* Selector de talla compacto dentro del tools */
.ptb-compact-options .custom-select,
.ptb-compact-options select {
    padding: 5px 8px;
    font-size: 11px;
    border-radius: 8px;
}
.ptb-compact-options .ptb-group {
    gap: 2px;
}

/* ── SEPARADOR HORIZONTAL Y FILA DESCRIPCIÓN ─────────────────── */
.ptb-hsep {
    height: 1px;
    background: #eef2f6;
    margin: 10px 0 8px;
}

/* Descripción en fila horizontal: 5 características en grid 2 cols + título */
.ptb-desc-row {
    display: grid;
    grid-template-columns: auto 1fr 1fr;
    grid-template-rows: auto auto auto;
    gap: 3px 10px;
    align-items: start;
}
/* El título ocupa las 3 columnas */
.ptb-desc-row .pdesc-titulo {
    grid-column: 1 / -1;
    margin-bottom: 4px;
    font-size: 10px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
    border-bottom: none;
}
/* Cada línea de característica */
.ptb-desc-row .pdesc-linea {
    display: flex;
    align-items: flex-start;
    gap: 5px;
    font-size: 10px;
    color: #555;
    font-weight: 600;
    padding: 3px 6px;
    border-bottom: none;
    background: #f8f9fa;
    border-radius: 7px;
    line-height: 1.35;
    border: 1px solid #eee;
}
.ptb-desc-row .pdesc-icon {
    color: #3498db;
    font-size: 10px;
    margin-top: 2px;
    flex-shrink: 0;
    width: 12px;
    text-align: center;
}

/* ★ Título de sección con número ★ */
.ptb-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    font-size: 13px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ptb-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #3498db;
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    flex-shrink: 0;
}

.product-top-inner {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
/* Fila genérica */
.ptb-fila {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
/* Fila de opciones: talla + color en horizontal */
.ptb-fila-opciones {
    flex-direction: row;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.ptb-vsep {
    width: 1px;
    height: 36px;
    background: #e0e0e0;
    flex-shrink: 0;
    align-self: center;
}
.ptb-vsep-main { height: 44px; }
.ptb-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex-shrink: 0;
}
.ptb-colores-wrap {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-shrink: 0;
}
.ptb-select { min-width: 150px; padding: 9px 12px; }
.ptb-desc {
    flex: 1;
    min-width: 220px;
    margin-top: 0 !important;
    font-size: 10.5px;
}
@media (max-width: 900px) { .ptb-desc { display: none; } }

@media (max-width: 640px) {
    .product-top-bar { padding: 12px 14px; }
    .product-top-inner { gap: 12px; }
    .ptb-select { min-width: 0; width: 100%; }
    .ptb-group { width: 100%; }
}


.cg-header { margin-bottom: 16px; }
.cg-header-top { text-align: center; margin-bottom: 12px; }
.cg-titulo {
    color: #2c3e50; font-weight: 900; text-transform: uppercase;
    letter-spacing: -1.5px; font-size: 1.7rem; margin-bottom: 4px;
}
.cg-titulo i { color: #e74c3c; }
.cg-titulo span { color: #e74c3c; }
.cg-subtitulo { color: #888; font-size: 0.85rem; font-weight: 600; margin: 0; }

.guia-editor {
    background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%);
    border-radius: 16px;
    padding: 12px 18px;
    box-shadow: 0 10px 30px rgba(44,62,80,0.18);
    margin-bottom: 14px;
    overflow: hidden;
    box-sizing: border-box;
}
.guia-header {
    font-size: 11px; font-weight: 900; color: #f1c40f;
    text-transform: uppercase; letter-spacing: 2px;
    margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
}
#tour-start-btn {
    display: none; /* Se muestra solo en móvil o cuando se requiere */
}
.guia-pasos {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    margin-bottom: 10px;
}
.gp-item {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 10px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 10px;
    transition: background 0.2s;
}
.gp-item:hover { background: rgba(255,255,255,0.13); }
.gp-icono {
    width: 32px; height: 32px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.gp-texto { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 0; }
.gp-texto strong { font-size: 11px; color: #fff; font-weight: 900; text-transform: uppercase; letter-spacing: 0.3px; }
.gp-texto span   { font-size: 10.5px; color: rgba(255,255,255,0.9); line-height: 1.5; font-weight: 500; }
.gp-texto em     { color: #f1c40f; font-style: normal; font-weight: 700; }
.gp-sep { display: none; }
.gp-num { display: none; }

.guia-tips {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 8px 14px;
    display: flex;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
}
.gt-tip {
    display: flex; align-items: flex-start; gap: 8px;
    font-size: 10px; color: rgba(255,255,255,0.85);
    line-height: 1.4; font-weight: 500; flex: 1; min-width: 180px;
}
.gt-tip i { font-size: 12px; margin-top: 1px; flex-shrink: 0; }
.gt-tip strong { color: #fff; font-weight: 800; }
.gt-sep { width: 1px; height: 32px; background: rgba(255,255,255,0.12); flex-shrink: 0; }

@media (max-width: 900px) {
    .gt-sep { display: none; }
    .guia-tips { flex-direction: column; gap: 10px; }
    .guia-pasos { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .guia-editor { padding: 14px; }
    .guia-pasos { grid-template-columns: 1fr; }
}

.custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(44,62,80,0.6); z-index: 10000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.custom-modal-box     { background: #fff; padding: 25px; border-radius: 20px; width: 90%; max-width: 480px; max-height: 85vh; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow-y: auto; overflow-x: hidden; }
.close-modal-btn      { position: absolute; top: 15px; right: 15px; background: #f8f9fa; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #e74c3c; font-size: 16px; }
.size-table    { width: 100%; border-collapse: collapse; text-align: center; table-layout: fixed; min-width: 500px; }
.size-table th { background: #2c3e50; color: #fff; padding: 14px 6px; font-size: 11px; text-transform: uppercase; }
.size-table td { padding: 14px 6px; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #555; }
.size-table th:first-child, .size-table td:first-child { text-align: left; padding-left: 15px; width: 15%; }

.btn-preview {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 900;
    font-size: 11px;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: 0.2s;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.btn-preview:hover { filter: brightness(1.15); transform: translateY(-1px); }

@keyframes previewPulse {
    0%   { box-shadow: 0 4px 18px rgba(142,68,173,0.35); }
    50%  { box-shadow: 0 4px 28px rgba(142,68,173,0.7), 0 0 0 6px rgba(142,68,173,0.12); }
    100% { box-shadow: 0 4px 18px rgba(142,68,173,0.35); }
}
.btn-preview-pulse {
    animation: previewPulse 2.2s ease-in-out infinite;
}
.btn-preview-pulse:hover {
    animation: none;
}

@media (max-width: 1100px) {
    .editor-grid { grid-template-columns: 1fr; gap: 16px; }
    .canvas-panel { order: -1; position: static; width: 100%; }
    .tools-panel {
        grid-template-columns: 1fr 1fr;
        grid-template-areas:
            "producto producto"
            "fotos  texto"
            "plantillas-col plantillas-col"
            "compra compra";
    }
    .plantillas-notas-col { flex-direction: row; flex-wrap: wrap; }
    .plantillas-notas-col .card-plantillas { flex: 1; min-width: 260px; }
    .plantillas-notas-col .extras-card     { flex: 1; min-width: 260px; }
}

@media (max-width: 780px) {
    .tools-panel {
        grid-template-columns: 1fr;
        grid-template-areas: "producto" "fotos" "texto" "plantillas-col" "compra";
    }
    .plantillas-notas-col { flex-direction: column; }
}

@media (max-width: 640px) {
    .cg-container { padding-left: 10px; padding-right: 10px; max-width: 100vw; overflow-x: hidden; }
    .canvas-panel { order: 0; }
    .tools-panel {
        grid-template-columns: 1fr;
        grid-template-areas: "producto" "fotos" "texto" "plantillas-col" "compra";
        max-width: 100%;
        overflow: hidden;
    }
    .canvas-panel { padding: 8px; max-width: 100%; overflow: hidden; box-sizing: border-box; }
    .canvas-wrapper { width: 100% !important; aspect-ratio: 1/1.2; }
    #tshirt-canvas { width: 100% !important; height: auto !important; }
    .canvas-panel .canvas-container { width: 100% !important; max-width: 100%; margin: 0 auto; }
    .precio-live-num { font-size: 26px; }
    .precio-live-inner { flex-direction: row; gap: 10px; justify-content: center; }
    .precio-live-label { font-size: 9px; }
    .toolbar-hint-row { flex-direction: column; align-items: flex-start; gap: 6px; }
    .toolbar-atajos-badge { display: none; }
    .toolbar-buttons { gap: 4px; flex-wrap: wrap; justify-content: center; max-width: 100%; overflow: hidden; }
    .toolbar-group { padding: 3px; flex-wrap: wrap; }
    .tool-sep { display: none; }
    .btn-view { padding: 12px 18px; font-size: 12px; }
    .cg-titulo { font-size: 1.3rem !important; letter-spacing: -0.5px; }
    .cg-subtitulo { font-size: 0.85rem; }
    .truco-pro-pasos { flex-wrap: wrap; gap: 8px; }
    .tp-paso { flex: 1 1 calc(50% - 8px); min-width: 130px; }
    .tp-flecha { display: none; }
    .tool-card { padding: 14px; max-width: 100%; box-sizing: border-box; overflow: visible; }
    .filter-grid-modern { grid-template-columns: repeat(2, 1fr); gap: 6px; }
    .fbtn { font-size: 7.5px; height: 40px; border-radius: 10px; }
    input[type="range"] { height: 28px; cursor: pointer; }
    .btn-upload-label { padding: 13px; font-size: 12px; }
    .btn-add-text { padding: 13px; font-size: 12px; }
    .buy-card { flex-direction: column !important; text-align: center; gap: 14px; }
    .buy-card > div:last-child { align-items: center !important; width: 100%; }
    .buy-buttons { flex-direction: column; width: 100%; gap: 10px; }
    .btn-download { width: 100%; justify-content: center; padding: 16px; font-size: 13px; }
    .btn-finish   { width: 100%; justify-content: center; padding: 16px; font-size: 14px; }
    .btn-preview  { width: 100%; justify-content: center; padding: 14px; font-size: 13px; }
    .custom-select { padding: 14px; font-size: 14px; }
    .ptb-compact-row { flex-wrap: wrap; gap: 8px; min-height: 0; }
    .ptb-compact-products {
        grid-template-columns: repeat(4, 1fr);
        max-width: 100%;
        width: 100%;
        flex-shrink: 1;
    }
    .ptb-compact-products .product-card { min-width: 0; width: 100%; box-sizing: border-box; padding: 8px 4px; }
    .ptb-compact-products .pc-icon-wrap { width: 28px; height: 28px; font-size: 13px; }
    .ptb-compact-products .pc-name { font-size: 8px; }
    .ptb-compact-products .pc-price { font-size: 7px; }
    .ptb-compact-label { flex-direction: row; gap: 8px; width: 100%; }
    .ptb-vsep { display: none; }
    .ptb-compact-options { min-height: 0; width: 100%; }
    .ptb-desc-row { grid-template-columns: 1fr 1fr; }
    .color-circle      { width: 34px; height: 34px; }
    .ptb-palette-scroll .color-circle { width: 28px !important; height: 28px !important; }
    .ptb-palette-scroll {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        flex-wrap: nowrap !important;
        padding-bottom: 6px !important;
    }
    .text-color-circle { width: 32px; height: 32px; }
    .extra-checkbox-label input[type="checkbox"] { width: 22px; height: 22px; }
    .extra-posicion-bloque { padding: 12px; }
    .thumb-img { width: 54px; height: 54px; }
    .custom-modal-box { padding: 18px; border-radius: 18px; max-height: 70vh; }
    .custom-modal-overlay { align-items: flex-start; padding-top: 80px; }
    .size-table td, .size-table th { padding: 10px 8px; font-size: 13px; }
    /* Mini lienzos en móvil */
    .mini-canvas-block { max-width: 100% !important; flex: 1 1 100%; }
    .mini-canvases-row { flex-direction: column; align-items: center; }
    .mini-canvas-block[id="mini-block-manga-izq"],
    .mini-canvas-block[id="mini-block-manga-der"] { max-width: 90% !important; }
    .mini-canvas-inner { width: 100% !important; height: auto !important; min-height: 80px; }
    .mini-canvas-tools { gap: 4px; }
    .mini-btn-text, .mini-btn-upload, .mini-btn-color, .mini-btn-del {
        font-size: 10px !important;
        height: 32px !important;
    }
    .mini-color-picker { grid-template-columns: repeat(6, 22px); }
    .mini-color-dot { width: 22px; height: 22px; }

    /* ── MEJORAS MÓVIL ────────────────────────────────────────── */
    #tshirt-canvas { max-width: 100% !important; height: auto !important; }
    .canvas-wrapper { width: 100% !important; overflow: visible; }
    .canvas-panel { padding: 8px !important; }

    /* Panel collapsible */
    .tool-card { padding: 10px !important; margin-bottom: 6px; }
    .tool-card-header {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding-bottom: 8px;
        margin-bottom: 8px;
        border-bottom: 1px solid #eee;
    }
    .tool-card-header::after { content: '▼'; font-size: 10px; transition: transform 0.2s; }
    .tool-card.collapsed .tool-card-body { display: none; }
    .tool-card.collapsed .tool-card-header::after { transform: rotate(-90deg); }
    .tool-card-body { display: block; }

    /* FAB — estilos collapsible móvil (el bloque principal está fuera del media query) */
    .fab-sub {
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
        transition: all 0.2s;
        width: 46px;
        height: 46px;
        font-size: 16px;
        background: #2ecc71;
    }

    /* Guía en móvil */
    .guia-pasos { grid-template-columns: 1fr; }
    .guia-tips { flex-direction: column !important; gap: 8px !important; }
    .gt-tip { font-size: 11px !important; padding: 8px !important; }
    .gt-sep { display: none !important; }
}

@keyframes fadeInDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

/* ── PLANTILLAS ──────────────────────────────────────────── */
.plantillas-tabs {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.ptab {
    background: #f0f0f0;
    border: 1.5px solid #ddd;
    border-radius: 20px;
    padding: 5px 10px;
    font-size: 10px;
    font-weight: 800;
    cursor: pointer;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    transition: all 0.15s;
    white-space: nowrap;
}
.ptab:hover  { background: #e8f8f5; border-color: #1abc9c; color: #1abc9c; }
.ptab.active { background: #1abc9c; border-color: #1abc9c; color: #fff; }

.plantillas-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 5px;
}
.plantilla-card {
    border-radius: 8px;
    border: 2px solid #eee;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.15s, border-color 0.15s, box-shadow 0.15s;
    background: #fff;
    display: flex;
    flex-direction: column;
}
.plantilla-card:hover {
    transform: translateY(-2px);
    border-color: #1abc9c;
    box-shadow: 0 4px 14px rgba(26,188,156,0.25);
}
.plantilla-preview {
    width: 100%;
    aspect-ratio: 3/4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 900;
    overflow: hidden;
    position: relative;
}
.plantilla-nombre {
    font-size: 7px;
    font-weight: 800;
    text-align: center;
    color: #555;
    text-transform: uppercase;
    padding: 3px 2px;
    letter-spacing: 0.2px;
    line-height: 1.2;
    background: #fafafa;
    border-top: 1px solid #eee;
}

@media (max-width: 640px) {
    .plantillas-grid { grid-template-columns: repeat(4, 1fr); gap: 4px; }
    .ptab { font-size: 9px; padding: 4px 8px; }
}


.sticker-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
}
.sticker-btn {
    background: #f8f9fa;
    border: 1.5px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    padding: 5px 2px;
    text-align: center;
    transition: transform 0.15s, background 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}
.sticker-btn:hover { background: #e8f4fd; transform: scale(1.2); border-color: #3498db; }
.sticker-btn:active { transform: scale(1.05); }

/* ── DEGRADADO BOTONES DIRECCIÓN ─────────────────────────── */
.grad-dir-btn { font-size: 16px !important; font-weight: 900; }

@media (max-width: 640px) {
    .sticker-grid { grid-template-columns: repeat(6, 1fr); }
    .sticker-btn { font-size: 20px; }
}

/* Descripción de producto */
.product-desc-box {
    margin-top: 14px;
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 14px;
    padding: 12px 14px;
    animation: fadeInDown 0.3s ease;
}
.pdesc-titulo {
    font-size: 11px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pdesc-icono { font-size: 14px; }
.pdesc-linea {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 11px;
    color: #555;
    font-weight: 600;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
    line-height: 1.4;
}
.pdesc-linea:last-child { border-bottom: none; }
.pdesc-icon {
    color: #3498db;
    font-size: 11px;
    margin-top: 2px;
    flex-shrink: 0;
    width: 14px;
    text-align: center;
}

/* ── ICONO EN TÍTULOS DE TARJETAS ────────────────────────── */
.tool-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    font-size: 15px;
    flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}
.tool-title-step {
    opacity: 0.45;
    font-size: 12px;
    margin-right: -2px;
}

/* ── BOTÓN RESTAURAR TAMAÑO ──────────────────────────────── */
.tool-restore { color: #8e44ad !important; }
.tool-restore:hover { background: #8e44ad !important; color: #fff !important; }

/* ── SELECTOR PRODUCTO/TALLA MEJORADO ────────────────────── */
.ptb-select-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 160px;
}
.ptb-select-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.ptb-select-header .label-sm {
    margin: 0;
}


/* ── TARJETAS DE PRODUCTO CLICABLES ─────────────────────── */
.product-cards-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.product-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border: 2px solid #eee;
    border-radius: 14px;
    background: #fafafa;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.product-card:hover {
    border-color: #3498db;
    background: #f0f8ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52,152,219,0.15);
}
.product-card.active {
    border-color: #3498db;
    background: linear-gradient(135deg, #ebf5fb, #d6eaf8);
    box-shadow: 0 4px 14px rgba(52,152,219,0.25);
    transform: translateY(-2px);
}
.pc-icon {
    font-size: 20px;
    color: #bbb;
    transition: color 0.2s;
    line-height: 1;
    flex-shrink: 0;
    width: 28px;
    text-align: center;
}
.product-card:hover .pc-icon,
.product-card.active .pc-icon {
    color: #3498db;
}
.pc-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.pc-name {
    font-size: 11px;
    font-weight: 900;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.pc-price {
    font-size: 10px;
    font-weight: 700;
    color: #27ae60;
}

/* ── CONTROLES DE TEXTO ──────────────────────────────────── */
.text-controls-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}

/* ── BOTÓN +/- SLIDER ──────────────────────────────────────── */
.slider-btn {
    background: #f0f0f0; border: 1.5px solid #ddd; border-radius: 8px;
    width: 28px; height: 28px; cursor: pointer; font-size: 16px; font-weight: 900;
    color: #555; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: 0.15s; user-select: none; line-height: 1;
}
.slider-btn:hover { background: #e0e0e0; color: #222; border-color: #bbb; }
.slider-btn:active { transform: scale(0.9); background: #d0d0d0; }

/* ── BOTÓN FILTRO ────────────────────────────────────────── */
.filter-btn {
    background: #f8f9fa;
    border: 2px solid #eee;
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    font-weight: 800;
    font-size: 12px;
    color: #2c3e50;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.filter-btn:hover {
    background: #2c3e50;
    color: #fff;
    border-color: #2c3e50;
    transform: translateY(-1px);
}

/* ── CANVAS PRINCIPAL ACTIVO ─────────────────────────────── */
.mini-active-main {
    box-shadow: 0 0 0 3px rgba(52,152,219,0.35) !important;
    border-radius: 16px;
}

/* Barra de "editando" encima de los botones del lienzo principal */
.zone-active-label {
    width: 100%;
    background: #ebf5fb;
    border: 1.5px solid #3498db;
    border-radius: 8px;
    color: #1a6f9a;
    font-size: 10px;
    font-weight: 800;
    text-align: center;
    padding: 5px 8px;
    box-sizing: border-box;
    margin-bottom: 4px;
    letter-spacing: 0.3px;
}

/* Botones de zona activa con borde azul */
.main-canvas-tools.zone-active-bar .mini-btn-text,
.main-canvas-tools.zone-active-bar .mini-btn-upload,
.main-canvas-tools.zone-active-bar .mini-btn-color,
.main-canvas-tools.zone-active-bar .mini-btn-del {
    border-color: #3498db;
}

/* Label del mini-canvas activo */
.mini-canvas-label.mini-label-active {
    color: #1a6f9a;
}

/* Badge "✏️ ACTIVO" dentro del label */
.zone-badge {
    display: inline-block;
    background: #3498db;
    color: #fff;
    font-size: 8px;
    font-weight: 900;
    padding: 2px 7px;
    border-radius: 20px;
    margin-left: 6px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    vertical-align: middle;
}


/* ══════════════════════════════════════════════════════════════
   CORRECCIÓN DEFINITIVA MÓVIL — evita desbordamiento en tarjetas
   ══════════════════════════════════════════════════════════════ */

/* Regla universal: ningún hijo del tools-panel puede ser más ancho que su contenedor */
@media (max-width: 1100px) {
    .editor-grid,
    .tools-panel,
    .tool-card,
    .product-top-bar {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    .canvas-panel {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* Zonas extra col: quitar ancho fijo */
    .zonas-extra-col {
        width: 100% !important;
        max-width: 100% !important;
        flex-shrink: 1 !important;
    }

    /* Mini canvas inner: ancho flexible */
    .mini-canvas-inner {
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        min-height: 60px;
        flex-shrink: 1;
    }
    .mini-canvas-inner canvas {
        width: 100% !important;
        height: auto !important;
    }

    /* Palette container: wrap por defecto, pero scroll si tiene la clase ptb-palette-scroll */
    .palette-container {
        flex-wrap: wrap;
        max-width: 100%;
        overflow-x: hidden;
    }
    .palette-container.ptb-palette-scroll {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        -webkit-overflow-scrolling: touch;
    }

    /* Filter grid */
    .filter-grid-modern {
        grid-template-columns: repeat(4, 1fr);
        max-width: 100%;
    }

    /* Font picker */
    .font-picker {
        grid-template-columns: repeat(4, 1fr);
        max-width: 100%;
    }

    /* Notas hints */
    .notas-hints {
        max-width: 100%;
        overflow: hidden;
    }

    /* Buy card */
    .buy-card {
        flex-direction: column !important;
        text-align: center;
        gap: 14px;
        max-width: 100%;
    }
    .buy-card > div:last-child { align-items: center !important; width: 100%; }
    .buy-buttons { flex-direction: column; width: 100%; gap: 10px; }
    .btn-download, .btn-finish, .btn-preview { width: 100%; justify-content: center; }
}

@media (max-width: 640px) {
    /* Canvas panel correctamente ajustado */
    .canvas-panel {
        padding: 8px !important;
    }

    /* Lienzo paralelo: apilado */
    .lienzo-paralelo-wrap {
        flex-direction: column;
        gap: 16px;
    }
    .zonas-extra-col {
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Filter 2 columnas */
    .filter-grid-modern {
        grid-template-columns: repeat(2, 1fr) !important;
    }

    /* Font picker 3 columnas */
    .font-picker {
        grid-template-columns: repeat(3, 1fr) !important;
    }

    /* Mini botones: 2 filas de 2 */
    .mini-canvas-tools {
        flex-wrap: wrap !important;
        gap: 5px !important;
    }
    .mini-btn-text, .mini-btn-upload, .mini-btn-color, .mini-btn-del {
        flex: 0 1 calc(50% - 5px) !important;
        min-width: 0 !important;
        font-size: 10px !important;
        padding: 8px 4px !important;
        height: 34px !important;
    }

    /* Product cards: 4 en fila */
    .ptb-compact-products {
        grid-template-columns: repeat(4, 1fr) !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .ptb-compact-products .product-card {
        min-width: 0 !important;
        width: 100% !important;
        padding: 8px 4px !important;
    }

    /* Guia editor padding mínimo */
    .guia-editor { padding: 12px 10px !important; }
}

/* ══════════════════════════════════════════════════════════════
   TIPOGRAFÍA MÓVIL — textos más legibles sin romper layout
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {

    /* Etiquetas y labels pequeños */
    .label-sm                { font-size: 12px !important; }
    .mini-section-label      { font-size: 12px !important; }
    .mini-canvas-label       { font-size: 12px !important; }
    .mini-section-title      { font-size: 12px !important; }

    /* Guía de uso */
    .gp-texto strong         { font-size: 13px !important; }
    .gp-texto span           { font-size: 12px !important; }
    .guia-header             { font-size: 13px !important; }
    .gt-tip                  { font-size: 12px !important; }

    /* Tour guiada */
    #tour-start-btn          { display: inline-flex !important; }
    #tour-tooltip            { max-width:90vw !important; }
    #tour-hole               { border-radius:8px !important; }

    /* Tarjeta de texto y controles */
    .tool-title              { font-size: 15px !important; }
    .font-option             { font-size: 12px !important; padding: 10px 4px !important; }

    /* Filtros */
    .fbtn                    { font-size: 9px !important; }

    /* Notas */
    .notas-hint-title        { font-size: 13px !important; }
    .notas-hint-item span    { font-size: 12px !important; line-height: 1.5; }

    /* Precio desglose */
    .desglose-box            { font-size: 13px !important; }
    .plde-base, .plde-extra  { font-size: 12px !important; }
    .precio-live-label       { font-size: 11px !important; }

    /* Descripción de producto */
    .pdesc-linea             { font-size: 13px !important; }
    .pdesc-titulo            { font-size: 12px !important; }

    /* Tarjetas de producto (solo un poco) */
    .ptb-compact-products .pc-name  { font-size: 10px !important; }
    .ptb-compact-products .pc-price { font-size: 9px !important; }

    /* Botones mini canvas */
    .mini-btn-text, .mini-btn-upload,
    .mini-btn-color, .mini-btn-del   { font-size: 11px !important; }

    /* Precio final */
    .precio-label            { font-size: 13px !important; }
    .precio-num              { font-size: 30px !important; }

    /* Toolbar hints */
    .toolbar-hint            { font-size: 12px !important; }
    .toolbar-hint-badge      { font-size: 11px !important; }

    /* Texto area notas */
    .notas-textarea          { font-size: 14px !important; }

    /* Select / custom select */
    .custom-select           { font-size: 14px !important; }

    /* Extras checkboxes */
    .extra-checkbox-txt      { font-size: 13px !important; }

    /* Rotación labels */
    #scale-val, #rotation-val,
    #text-size-val, #text-rotation-val { font-size: 13px !important; }

    /* Nuevos controles de texto */
    #spacing-val, #lineheight-val, #opacity-val { font-size: 13px !important; }

    /* Stickers — menos columnas en móvil */
    .sticker-grid { grid-template-columns: repeat(6, 1fr) !important; }
    .sticker-btn  { font-size: 22px !important; padding: 6px 2px !important; }

    /* Degradado inline — wrap en móvil */
    #grad-preview { height: 18px !important; }

    /* Guía — pasos en columna única */
    .gp-texto span { font-size: 11px !important; line-height: 1.5 !important; }
}

/* ══════════════════════════════════════════════
   BIBLIOTECA DE FOTOS — MÓVIL
   ══════════════════════════════════════════════ */
@media (max-width: 640px) {
    #biblioteca-area {
        overflow: visible !important;
    }
    .resource-list {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 8px;
        gap: 10px !important;
        /* scrollbar fino */
        scrollbar-width: thin;
        scrollbar-color: #ddd transparent;
    }
    .resource-list::-webkit-scrollbar { height: 4px; }
    .resource-list::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

    .thumb-wrap {
        flex-shrink: 0 !important;
    }
    .thumb-img {
        width: 70px !important;
        height: 70px !important;
        border-radius: 12px !important;
        border-width: 3px !important;
    }
    .btn-del-rec {
        width: 22px !important;
        height: 22px !important;
        font-size: 10px !important;
        top: -6px !important;
        right: -6px !important;
    }
}

/* ── PARPADEO BIBLIOTECA ─────────────────────────────────── */
@keyframes bibliotecaFlash {
    0% { box-shadow: none; }
    25% { box-shadow: 0 0 0 4px rgba(231,76,60,0.4); }
    75% { box-shadow: 0 0 0 4px rgba(231,76,60,0.4); }
    100% { box-shadow: none; }
}
.biblioteca-flash {
    animation: bibliotecaFlash 1.8s ease forwards;
}

/* Flash del panel de texto al abrir desde botón Texto */
@keyframes panelFlash {
    0% { box-shadow: none; }
    25% { box-shadow: 0 0 0 4px rgba(231,76,60,0.35), 0 0 20px rgba(231,76,60,0.15); }
    75% { box-shadow: 0 0 0 4px rgba(231,76,60,0.35), 0 0 20px rgba(231,76,60,0.15); }
    100% { box-shadow: none; }
}
.panel-flash {
    animation: panelFlash 1.8s ease forwards;
}
.biblioteca-flash {
    animation: bibliotecaFlash 1.8s ease forwards;
    border-radius: 14px;
}

/* ══════════════════════════════════════════════════════════════
   BOTÓN FLOTANTE "VER LIENZO" — solo móvil
   ══════════════════════════════════════════════════════════════ */
#btn-ver-lienzo {
    display: flex;
    align-items: center;
    gap: 8px;
    position: fixed;
    bottom: 24px;
    left: 20px;
    z-index: 9000;
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 13px 20px;
    font-size: 14px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 6px 24px rgba(142,68,173,0.45);
    cursor: pointer;
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
    transition: opacity 0.25s ease, transform 0.25s ease, background 0.2s;
}
#btn-ver-lienzo:active { transform: scale(0.95); }
#btn-ver-lienzo i { font-size: 16px; }
@keyframes btnPulse {
    0%, 100% { box-shadow: 0 6px 24px rgba(142,68,173,0.45); transform: scale(1); }
    50% { box-shadow: 0 6px 30px rgba(142,68,173,0.8), 0 0 0 8px rgba(142,68,173,0.2); transform: scale(1.08); }
}
#btn-ver-lienzo.pulse { animation: btnPulse 0.6s ease 3; }

/* ══════════════════════════════════════════════════════════════
   BOTONES ACCIÓN LIENZO PRINCIPAL
   ══════════════════════════════════════════════════════════════ */
.canvas-principal-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    flex-grow: 0;
}
.main-canvas-tools {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    width: 100%;
    box-sizing: border-box;
}
.main-canvas-tools .mini-btn-text,
.main-canvas-tools .mini-btn-upload,
.main-canvas-tools .mini-btn-color,
.main-canvas-tools .mini-btn-del {
    height: 36px;
    font-size: 11px;
    border-radius: 10px;
    width: 100%;
    box-sizing: border-box;
}

/* ══════════════════════════════════════════════════════════════
   PAGINACIÓN PLANTILLAS
   ══════════════════════════════════════════════════════════════ */
.plantillas-paginacion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
}
.plantillas-arrow {
    background: #f0f0f0;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    color: #555;
    transition: all 0.15s;
    flex-shrink: 0;
}
.plantillas-arrow:hover:not(:disabled) {
    background: #1abc9c;
    border-color: #1abc9c;
    color: #fff;
}
.plantillas-arrow:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.plantillas-page-info {
    font-size: 10px;
    font-weight: 800;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 36px;
    text-align: center;
}

/* ══════════════════════════════════════════════════════════════
   COLUMNA PLANTILLAS-NOTAS: misma altura que las otras columnas
   ══════════════════════════════════════════════════════════════ */
.plantillas-notas-col {
    height: 100%;
    min-height: 0;
}
.plantillas-notas-col .card-plantillas {
    flex: 1 1 auto;
    min-height: 0;
}
.plantillas-notas-col .extras-card {
    /* Flex-grow para ocupar el espacio restante */
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
/* En la columna de 3 cols del tools-panel, las 3 tarjetas principales deben tener el mismo alto */
@media (min-width: 781px) {
    .card-fotos,
    .card-texto,
    .plantillas-notas-col {
        /* Cada columna ocupa todo el alto disponible en su celda de grid */
        align-self: stretch;
    }
    .plantillas-notas-col .extras-card .notas-hints {
        flex: 1 1 auto;
        overflow-y: auto;
    }
}

/* ══════════════════════════════════════════════════════════════
   BARRA STICKY NAVEGACIÓN MÓVIL
   ══════════════════════════════════════════════════════════════ */
.mobile-sticky-nav {
    display: none; /* solo en móvil */
}

@media (max-width: 1100px) {
    .mobile-sticky-nav {
        display: flex;
        position: sticky;
        top: 50px;
        left: 0;
        right: 0;
        z-index: 100000;
        background: #fff;
        border-bottom: 2px solid #eee;
        box-shadow: 0 2px 12px rgba(0,0,0,0.10);
        gap: 0;
        width: 100%;
        box-sizing: border-box;
        padding: 0;
    }
    body {
        padding-top: 0;
    }
    .msn-btn {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        padding: 10px 4px 8px;
        border: none;
        background: transparent;
        color: #555;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        border-right: 1px solid #eee;
        transition: background 0.15s, color 0.15s;
    }
    .msn-btn:last-child { border-right: none; }
    .msn-btn i {
        font-size: 16px;
        margin-bottom: 1px;
    }
    .msn-btn:hover, .msn-btn:active {
        background: #f0f8ff;
        color: #3498db;
    }
    .msn-btn-canvas {
        background: linear-gradient(135deg, #8e44ad22, #9b59b622);
        color: #8e44ad;
    }
    .msn-btn-canvas:hover, .msn-btn-canvas:active {
        background: linear-gradient(135deg, #8e44ad, #9b59b6);
        color: #fff;
    }
    .msn-btn-undo { color: #2c3e50; }
    .msn-btn-undo:hover, .msn-btn-undo:active { background: #f0f4ff; color: #3498db; }
    /* En tablet: ocultar botones flotantes (ya tiene sticky nav) */
    .fab-menu { display: none !important; }
}

/* Móvil: botón ver lienzo siempre visible */

/* Mini canvas tap hint overlay */
.mini-canvas-inner { position: relative; }
.mini-tap-hint {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: rgba(142,68,173,0.82);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-radius: 8px;
    pointer-events: none;
    transition: opacity 0.3s;
    z-index: 2;
}
.mini-canvas-block.mini-active .mini-tap-hint,
.mini-canvas-inner:has(canvas:not(:empty)) .mini-tap-hint { opacity: 0; }

/* Precio destello al cambiar */
@keyframes precio-flash-anim {
    0%   { color: inherit; transform: scale(1); }
    25%  { color: #27ae60; transform: scale(1.12); }
    60%  { color: #27ae60; transform: scale(1.08); }
    100% { color: inherit; transform: scale(1); }
}
.precio-flash { animation: precio-flash-anim 0.6s ease forwards; }

@media (max-width: 640px) {
    .msn-btn { font-size: 9px; padding: 8px 2px 6px; }
    .msn-btn i { font-size: 14px; }
    /* Dar espacio al body para la barra sticky */
    .cg-container { padding-top: 4px; }
}

/* ═══════════════════════════════════════════════════════
   MODAL DE CONFIRMACIÓN CUSTOM
═══════════════════════════════════════════════════════ */
#cg-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 9, 8, 0.65);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: cgModalFadeIn 0.18s ease;
}
#cg-modal-overlay.active { display: flex; }

@keyframes cgModalFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

#cg-modal-box {
    background: #fff;
    border-radius: 20px;
    padding: 36px 36px 28px;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 32px 80px rgba(0,0,0,0.22), 0 0 0 1px rgba(0,0,0,0.06);
    animation: cgModalSlideUp 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
}
@keyframes cgModalSlideUp {
    from { transform: translateY(18px) scale(0.97); opacity: 0; }
    to   { transform: translateY(0)    scale(1);    opacity: 1; }
}

#cg-modal-icon {
    width: 52px; height: 52px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
}
#cg-modal-icon.warn  { background: rgba(231,76,60,0.1);  color: #e74c3c; }
#cg-modal-icon.info  { background: rgba(52,152,219,0.1); color: #3498db; }
#cg-modal-icon.trash { background: rgba(231,76,60,0.1);  color: #e74c3c; }

#cg-modal-title {
    font-family: 'Syne', 'Segoe UI', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: #111;
    margin-bottom: 8px;
    line-height: 1.3;
}
#cg-modal-msg {
    font-size: 0.9rem;
    color: #777;
    line-height: 1.65;
    margin-bottom: 28px;
}
#cg-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.cgm-btn {
    padding: 11px 22px;
    border-radius: 10px;
    border: none;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    letter-spacing: 0.2px;
}
.cgm-btn-cancel {
    background: #f2f2f2;
    color: #555;
}
.cgm-btn-cancel:hover { background: #e8e8e8; color: #222; }

.cgm-btn-confirm {
    background: #e74c3c;
    color: #fff;
    box-shadow: 0 4px 14px rgba(231,76,60,0.3);
}
.cgm-btn-confirm:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(231,76,60,0.35);
}
.cgm-btn-confirm.blue {
    background: #3498db;
    box-shadow: 0 4px 14px rgba(52,152,219,0.3);
}
.cgm-btn-confirm.blue:hover {
    background: #2980b9;
    box-shadow: 0 8px 20px rgba(52,152,219,0.35);
}

@media (max-width: 480px) {
    #cg-modal-box { padding: 28px 22px 22px; }
    #cg-modal-actions { flex-direction: column-reverse; }
    .cgm-btn { width: 100%; text-align: center; padding: 13px; }
}
</style>


<!-- ── MODAL DE CONFIRMACIÓN CUSTOM ── -->
<div id="cg-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="cg-modal-title">
    <div id="cg-modal-box">
        <div id="cg-modal-icon"><i id="cg-modal-icon-i"></i></div>
        <div id="cg-modal-title"></div>
        <div id="cg-modal-msg"></div>
        <div id="cg-modal-actions">
            <button class="cgm-btn cgm-btn-cancel" id="cgm-cancel">Cancelar</button>
            <button class="cgm-btn cgm-btn-confirm" id="cgm-confirm">Confirmar</button>
        </div>
    </div>
</div>

<script>
/**
 * cgConfirm(opciones) → Promise<boolean>
 * Sustituye al confirm() nativo con un modal bonito.
 *
 * opciones = {
 *   titulo:   string,
 *   mensaje:  string,
 *   confirmar: string,   // texto botón OK  (default "Confirmar")
 *   cancelar:  string,   // texto botón NO  (default "Cancelar")
 *   tipo:     'warn' | 'trash' | 'info'   (default 'warn')
 *   colorBtn: 'red' | 'blue'             (default 'red')
 * }
 */
function cgConfirm({ titulo = '', mensaje = '', confirmar = 'Confirmar', cancelar = 'Cancelar', tipo = 'warn', colorBtn = 'red' } = {}) {
    return new Promise(function(resolve) {
        const overlay  = document.getElementById('cg-modal-overlay');
        const iconEl   = document.getElementById('cg-modal-icon');
        const iconI    = document.getElementById('cg-modal-icon-i');
        const titleEl  = document.getElementById('cg-modal-title');
        const msgEl    = document.getElementById('cg-modal-msg');
        const btnOk    = document.getElementById('cgm-confirm');
        const btnNo    = document.getElementById('cgm-cancel');

        // Icono
        const iconos = { warn: 'fas fa-exclamation-triangle', trash: 'fas fa-trash-alt', info: 'fas fa-eye' };
        iconEl.className = 'cg-modal-icon ' + tipo;
        iconEl.style.cssText = tipo === 'info'
            ? 'width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:20px;background:rgba(52,152,219,0.1);color:#3498db;'
            : 'width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:20px;background:rgba(231,76,60,0.1);color:#e74c3c;';
        iconI.className = iconos[tipo] || iconos.warn;

        titleEl.textContent = titulo;
        msgEl.textContent   = mensaje;
        btnOk.textContent   = confirmar;
        btnNo.textContent   = cancelar;

        // Color botón confirmar
        btnOk.className = 'cgm-btn cgm-btn-confirm' + (colorBtn === 'blue' ? ' blue' : '');

        overlay.classList.add('active');
        btnNo.focus();

        function cleanup(result) {
            overlay.classList.remove('active');
            btnOk.removeEventListener('click', onOk);
            btnNo.removeEventListener('click', onNo);
            overlay.removeEventListener('click', onOverlay);
            document.removeEventListener('keydown', onKey);
            resolve(result);
        }
        function onOk()      { cleanup(true);  }
        function onNo()      { cleanup(false); }
        function onOverlay(e){ if (e.target === overlay) cleanup(false); }
        function onKey(e)    { if (e.key === 'Escape') cleanup(false); if (e.key === 'Enter') cleanup(true); }

        btnOk.addEventListener('click', onOk);
        btnNo.addEventListener('click', onNo);
        overlay.addEventListener('click', onOverlay);
        document.addEventListener('keydown', onKey);
    });
}
</script>

<!-- BOTÓN FLOTANTE "VER LIENZO" — solo móvil -->
<button id="btn-ver-lienzo" onclick="scrollAlLienzo()" aria-label="Ver lienzo">
    <i class="fas fa-pencil-alt"></i>
    <span>✏️ Editando: Delante</span>
</button>
<?php include 'includes/footer.php'; ?>