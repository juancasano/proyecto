<?php 
require_once 'includes/config.php'; 

/**
 * ARCHIVO: admin_productos.php
 * FUNCIÓN: Editor maestro con Dashboard profesional
 * MÓVIL: Navegación tipo app (lista → editor como pantallas separadas)
 */

if (!esAdmin()) {
    header("Location: index.php"); 
    exit; 
}

include 'includes/header.php'; 

$id_directo = htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8');

$json_final = json_encode(['products' => []]);
try {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY categoria ASC, nombre ASC");
    $js_array = [];
    while($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $js_array[] = [
            'id'          => $p['id'], 
            'name'        => $p['nombre'], 
            'description' => $p['descripcion'],
            'price'       => (float)$p['precio'], 
            'category'    => trim($p['categoria']), 
            'image'       => $p['imagen_url'], 
            'gallery'     => !empty($p['imagenes_galeria']) ? explode(',', $p['imagenes_galeria']) : [],
            'videoDelante' => $p['video_delante'] ?? '',
            'videoDetras'  => $p['video_detras'] ?? '',
            'videoComoSeHace' => $p['video_como_se_hace'] ?? '',
            'featured'    => (bool)$p['destacado'],
            'featuredAt'  => isset($p['destacado_at']) && $p['destacado_at'] ? strtotime($p['destacado_at']) : null
        ];
    }
    $json_final = json_encode(['products' => $js_array], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error cargando catálogo en Camiglobo: " . $e->getMessage());
}
?>

<style>
    :root { 
        --grad-camiglobo: linear-gradient(90deg, #000000 0%, #e74c3c 50%, #27ae60 100%);
        --m-color: #e74c3c;
        --s-color: #27ae60;
        --app-bg: #f4f4f8;
        --panel-bg: #ffffff;
        --border: #e8e8ee;
        --text-muted: #9199a6;
    }

    /* =============================================
       RESET Y BASE
    ============================================= */
    *, *::before, *::after { box-sizing: border-box; }

    body, html { 
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        background: var(--app-bg);
    }

    /* Desktop: bloquea scroll global */
    @media (min-width: 769px) {
        body, html { overflow: hidden; height: 100%; }
    }

    /* =============================================
       CONTENEDOR PRINCIPAL
    ============================================= */
    .admin-main-container { 
        max-width: 1600px; 
        margin: 0 auto; 
        padding: 0 20px; 
    }

    @media (min-width: 769px) {
        .admin-main-container {
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }
    }

    /* =============================================
       TOPBAR
    ============================================= */
    .topbar {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 12px 0; 
        margin-bottom: 10px; 
        border-bottom: 2px solid var(--border);
        gap: 10px;
        flex-wrap: wrap;
    }

    /* =============================================
       LAYOUT DESKTOP
    ============================================= */
    .admin-layout { 
        display: flex; 
        gap: 15px; 
        flex: 1; 
        overflow: hidden; 
        min-height: 0; 
    }

    /* =============================================
       PANEL IZQUIERDO (LISTA)
    ============================================= */
    .list-panel { 
        width: 320px;
        background: var(--panel-bg); 
        border-radius: 15px; 
        padding: 12px; 
        display: flex; 
        flex-direction: column; 
        height: 100%; 
        border: 1px solid var(--border); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.03); 
        flex-shrink: 0;
    }

    .product-scroll-area { 
        flex: 1; 
        overflow-y: auto; 
        padding-right: 4px; 
        margin-top: 8px; 
        scroll-behavior: smooth; 
    }

    /* =============================================
       PANEL DERECHO (EDITOR)
    ============================================= */
    .edit-panel { 
        flex: 1; 
        background: #eceef2; 
        border-radius: 15px; 
        padding: 12px 20px;
        border: 1px solid var(--border); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.03); 
        height: 100%; 
        overflow-y: auto; 
        display: flex;
        flex-direction: column;
    }

    .edit-panel h2 { 
        margin: 0 0 -30px 0; 
        font-size: 1.1rem; 
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* =============================================
       ITEMS DE LA LISTA
    ============================================= */
    .prod-item { 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        padding: 10px; 
        border: 1px solid #f8f8f8; 
        border-radius: 10px; 
        margin-bottom: 5px; 
        cursor: pointer; 
        transition: 0.2s; 
        position: relative;
        background: white;
    }
    .prod-item:hover { border-color: var(--m-color); background: #fffafa; }
    .prod-item.active { border-color: var(--m-color); background: #fff5f5; border-left: 4px solid var(--m-color); }
    .prod-item.selected-for-delete { border-color: #e74c3c; background: #fff0f0; border-left: 4px solid #e74c3c; }
    .prod-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .prod-item-info { flex: 1; min-width: 0; }
    .prod-item-info strong { font-size: 13px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .prod-item-price { font-weight: 800; color: var(--m-color); font-size: 13px; flex-shrink: 0; }

    .prod-item-check {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        accent-color: #e74c3c;
    }

    /* =============================================
       BARRA ELIMINACIÓN MÚLTIPLE
    ============================================= */
    #multi-delete-bar {
        display: none;
        background: #fff0f0;
        border: 1px solid #ffcccc;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 8px;
        align-items: center;
        gap: 8px;
        animation: slideDown 0.2s ease;
    }
    #multi-delete-bar.visible { display: flex; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-5px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    #multi-delete-bar span { font-size: 11px; font-weight: 800; color: #e74c3c; flex: 1; }

    /* =============================================
       BOTONES
    ============================================= */
    .btn-camiglobo { 
        background: var(--grad-camiglobo); 
        color: white !important; 
        border: none; 
        padding: 8px 15px; 
        border-radius: 30px; 
        font-weight: 800; 
        cursor: pointer; 
        text-transform: uppercase; 
        font-size: 10px; 
        transition: 0.2s; 
        display: flex; 
        align-items: center; 
        gap: 6px; 
        justify-content: center;
        white-space: nowrap;
    }

    .btn-multi-del {
        background: #e74c3c; color: white; border: none;
        padding: 6px 12px; border-radius: 6px; font-size: 11px;
        font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.2s;
    }
    .btn-multi-del:hover { background: #c0392b; }

    .btn-multi-cancel {
        background: #ecf0f1; color: #555; border: none;
        padding: 6px 12px; border-radius: 6px; font-size: 11px;
        font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.2s;
    }

    #btn-select-mode {
        background: #fff; color: #e74c3c; border: 1px solid #e74c3c;
        padding: 6px 12px; border-radius: 6px; font-size: 11px;
        font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.2s; white-space: nowrap;
    }
    #btn-select-mode.active-mode { background: #e74c3c; color: white; }

    .btn-squoosh-tool {
        background: #f39c12; color: white !important; text-decoration: none !important;
        padding: 10px 16px; border-radius: 30px; font-weight: 800; font-size: 10px;
        text-transform: uppercase; display: flex; align-items: center; gap: 8px; transition: 0.3s;
        white-space: nowrap;
    }
    .btn-squoosh-tool:hover { background: #e67e22; transform: translateY(-2px); }

    /* =============================================
       FORMULARIO
    ============================================= */
    .form-row { 
        display: grid; 
        grid-template-columns: 2fr 1fr 90px; 
        gap: 8px; 
        margin-bottom: 6px; 
    }
    .form-group { margin-bottom: 6px; }
    .form-group label { 
        display: block; 
        font-weight: 800; 
        margin-bottom: 3px; 
        font-size: 10px; 
        color: var(--text-muted); 
        text-transform: uppercase; 
    }
    .form-group input, 
    .form-group textarea, 
    .form-group select { 
        width: 100%; 
        padding: 8px 12px; 
        border: 1px solid var(--border); 
        border-radius: 8px; 
        outline: none; 
        font-size: 13px; 
        background: #fafafa;
        -webkit-appearance: none;
        appearance: none;
        transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: var(--m-color);
        background: white;
    }

    /* =============================================
       GALERÍA Y PREVIEW
    ============================================= */
    .preview-container-box {
        width: 100%; height: 105px;
        background: #fcfcfc; border-radius: 10px; border: 1px solid var(--border); 
        overflow: hidden; display:flex; align-items:center; justify-content:center; cursor: zoom-in;
    }

    .gallery-manager { 
        background: #fcfcfc; border: 1px dashed #ddd; border-radius: 10px; 
        padding: 6px; height: 105px; overflow-y: auto; 
    }
    .gallery-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .gallery-item { 
        position: relative; width: 60px; border-radius: 6px; 
        border: 1px solid #eee; background: #fff; padding: 2px;
    }
    .gallery-item img { width: 100%; height: 45px; object-fit: cover; border-radius: 4px; }
    .btn-del {
        position: absolute; top: -5px; right: -5px; width: 18px; height: 18px;
        background: #e74c3c; color: white; border: none; border-radius: 50%;
        font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center;
        line-height: 1; padding: 0;
    }

    /* =============================================
       HEALTH INFO
    ============================================= */
    .health-info { 
        margin-top: 5px; font-size: 11px; font-weight: 700; 
        padding: 4px 10px; border-radius: 6px; text-align: center;
    }
    .health-ok { background: #27ae60 !important; color: white !important; }
    .health-warning { background: #f1c40f !important; color: black !important; }
    .health-danger { background: #e74c3c !important; color: white !important; }

    /* =============================================
       LIGHTBOX ZOOM
    ============================================= */
    #zoom-lightbox { 
        display: none; position: fixed; top: 0; left: 0; 
        width: 100%; height: 100%; background: rgba(0,0,0,0.92); 
        z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; 
    }
    #zoom-lightbox.show { display: flex; }
    #zoom-img { max-width: 90%; max-height: 90%; border: 3px solid white; border-radius: 8px; }

    /* =============================================
       TOAST
    ============================================= */
    #admin-toast {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        z-index: 9999; background: #2c3e50; color: white; padding: 12px 22px;
        border-radius: 30px; font-size: 13px; font-weight: 700;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3); transition: opacity 0.4s;
        white-space: nowrap; pointer-events: none;
    }

    /* =============================================
       AÑADIDO: FILTROS DE CATEGORÍA
    ============================================= */
    .cat-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 6px;
    }
    .cat-pill {
        padding: 3px 9px;
        border-radius: 20px;
        border: 1px solid #e0e0e0;
        background: #f8f8f8;
        color: #666;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
        text-transform: uppercase;
        transition: 0.15s;
        white-space: nowrap;
        user-select: none;
    }
    .cat-pill:hover { border-color: var(--m-color); color: var(--m-color); background: #fff5f5; }
    .cat-pill.active { background: var(--m-color); border-color: var(--m-color); color: white; }
    .cat-pill[data-cat="todos"].active    { background: #2c3e50; border-color: #2c3e50; }
    .cat-pill[data-cat="camiseta"].active { background: #e74c3c; border-color: #e74c3c; }
    .cat-pill[data-cat="sudadera"].active { background: #8e44ad; border-color: #8e44ad; }
    .cat-pill[data-cat="taza"].active     { background: #2980b9; border-color: #2980b9; }
    .cat-pill[data-cat="cuadro"].active   { background: #27ae60; border-color: #27ae60; }
    .cat-pill[data-cat="otro"].active     { background: #7f8c8d; border-color: #7f8c8d; }
    .cat-pill[data-cat="destacado"].active{ background: #f39c12; border-color: #f39c12; }
    .pill-count {
        display: inline-block;
        background: rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 0 4px;
        margin-left: 2px;
        font-size: 9px;
    }
    .cat-pill.active .pill-count { background: rgba(255,255,255,0.3); }
    /* Punto de color en items */
    .cat-dot { width:6px; height:6px; border-radius:50%; display:inline-block; margin-right:3px; flex-shrink:0; }
    /* Contador resultados */
    .results-count { font-size:10px; color:#aaa; text-align:right; margin-bottom:2px; }
    /* Móvil: pills más grandes */
    @media (max-width: 768px) {
        .cat-filters { gap: 6px; margin-bottom: 10px; }
        .cat-pill { font-size: 11px; padding: 6px 12px; }
    }
    /* FIN AÑADIDO */

    /* =============================================
       ============== MÓVIL ==============
       Sistema de "pantallas" tipo app nativa
    ============================================= */
    @media (max-width: 768px) {

        body, html { overflow: auto; background: var(--app-bg); }

        /* Oculta el layout de escritorio */
        .admin-layout { display: none !important; }

        /* Contenedor móvil */
        .mobile-wrapper { display: block; }
        .desktop-wrapper { display: none !important; }

        /* Pantallas: por defecto ambas en flujo normal */
        .mobile-screens {
            width: 100%;
        }

        /* Cada pantalla ocupa el ancho completo y tiene altura automática */
        .mobile-screen {
            width: 100%;
            background: var(--app-bg);
            min-height: calc(100vh - 60px);
        }

        /* Pantalla lista: visible por defecto */
        #screen-list {
            display: block;
        }
        #screen-list.hidden-screen {
            display: none;
        }

        /* Pantalla editor: oculta por defecto */
        #screen-editor {
            display: none;
        }
        #screen-editor.visible-screen {
            display: block;
        }

        /* ── TOPBAR MÓVIL ── */
        .topbar {
            padding: 10px 15px;
            background: white;
            border-radius: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .topbar::-webkit-scrollbar { display: none; }

        /* ── PANTALLA LISTA: contenido ── */
        .mobile-list-content {
            padding: 12px 15px;
        }

        /* Botón nuevo producto en móvil: más grande */
        .btn-new-mobile {
            width: 100%;
            padding: 16px;
            font-size: 14px;
            border-radius: 12px;
            margin-bottom: 12px;
            background: #000;
            letter-spacing: 1px;
        }

        /* Barra de búsqueda móvil */
        .search-bar-mobile {
            position: relative;
            margin-bottom: 12px;
        }
        .search-bar-mobile i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #bbb; font-size: 14px;
        }
        .search-bar-mobile input {
            width: 100%; padding: 14px 14px 14px 42px;
            border-radius: 12px; border: 1px solid var(--border);
            font-size: 15px; background: white;
            -webkit-appearance: none; outline: none;
        }

        /* Items lista en móvil: más altos */
        .prod-item {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid var(--border);
        }
        .prod-item img { width: 48px; height: 48px; border-radius: 10px; }
        .prod-item-info strong { font-size: 14px; }
        .prod-item-info small { font-size: 11px; }
        .prod-item-price { font-size: 15px; }
        /* Flecha indicadora en móvil */
        .prod-item::after {
            content: '›';
            color: #ccc;
            font-size: 22px;
            font-weight: 300;
            margin-left: 4px;
        }
        .prod-item.selected-for-delete::after,
        .prod-item-check ~ .prod-item::after { display: none; }

        /* ── PANTALLA EDITOR MÓVIL ── */
        .mobile-editor-content {
            padding: 0 0 30px 0;
        }

        /* Header del editor móvil con botón volver */
        .mobile-editor-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 15px;
            background: white;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .btn-back-mobile {
            background: none; border: none; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            color: var(--m-color); font-weight: 800; font-size: 14px;
            padding: 8px 4px; white-space: nowrap;
        }
        .btn-back-mobile i { font-size: 16px; }

        .mobile-editor-title {
            flex: 1; font-size: 15px; font-weight: 800;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin: 0;
        }

        .mobile-editor-delete {
            background: #fff5f5; color: var(--m-color);
            border: 1px solid #ffebeb; border-radius: 8px;
            padding: 8px 12px; font-size: 13px; cursor: pointer;
            flex-shrink: 0;
        }

        /* Cuerpo del formulario en móvil */
        .mobile-editor-body {
            padding: 16px 15px;
        }

        /* Form en móvil: todo en columna */
        .form-row { 
            grid-template-columns: 1fr; 
            gap: 0;
        }

        .form-group { margin-bottom: 14px; }
        .form-group label { font-size: 11px; margin-bottom: 5px; }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 13px 14px;
            font-size: 15px;
            border-radius: 10px;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }

        /* Portada en móvil: layout vertical */
        .cover-gallery-grid {
            display: block !important;
        }
        .cover-col, .gallery-col {
            width: 100% !important;
        }

        /* Preview imagen más grande en móvil */
        .cover-preview-mobile {
            width: 100%;
            height: 200px !important;
            border-radius: 12px !important;
            margin-bottom: 10px;
        }

        /* Galería en móvil */
        .gallery-manager {
            height: auto !important;
            min-height: 80px;
        }
        .gallery-item { width: 70px; }
        .gallery-item img { height: 55px; }

        /* Destacado en móvil */
        .featured-bar {
            border-radius: 12px;
            padding: 16px !important;
        }
        .featured-bar input[type=checkbox] {
            width: 20px; height: 20px;
            accent-color: #27ae60;
        }
        .featured-bar label {
            font-size: 14px !important;
            display: flex; align-items: center; gap: 10px;
        }

        /* Botón guardar en móvil: grande y fijo abajo */
        .btn-save-mobile {
            position: sticky;
            bottom: 15px;
            width: 100%;
            padding: 16px;
            font-size: 14px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(39, 174, 96, 0.4);
            background: var(--s-color) !important;
            margin-top: 10px;
            letter-spacing: 1px;
        }

        /* Oculta el editor de escritorio en móvil */
        #product-editor { display: none !important; }

        /* Barra selección múltiple en móvil */
        #multi-delete-bar { 
            border-radius: 10px; 
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        #multi-delete-bar span { font-size: 12px; }
        .btn-multi-del, .btn-multi-cancel { 
            padding: 8px 14px; 
            font-size: 12px; 
            border-radius: 8px;
        }

        /* Botón seleccionar en móvil */
        #btn-select-mode {
            padding: 8px 14px;
            font-size: 12px;
            border-radius: 8px;
        }

        /* Toast centrado abajo */
        #admin-toast {
            bottom: 80px;
            font-size: 13px;
            max-width: 90%;
            white-space: normal;
            text-align: center;
        }

        /* Botón subir desde PC en móvil */
        .btn-upload-pc {
            padding: 10px 14px !important;
            font-size: 12px !important;
            border-radius: 8px !important;
        }

        /* Input URL imagen en móvil */
        .image-url-row {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }
        .image-url-row input {
            flex: 1;
            font-size: 13px !important;
            padding: 10px 12px !important;
        }
        .image-url-row label {
            flex-shrink: 0;
        }

        /* Añadir a galería en móvil */
        .gallery-input-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .gallery-input-row input {
            flex: 1; font-size: 13px !important;
            padding: 10px 12px !important;
        }
    }

    /* =============================================
       Desktop: ocultar elementos exclusivos de móvil
    ============================================= */
    @media (min-width: 769px) {
        .mobile-wrapper { display: none !important; }
        .desktop-wrapper { display: flex !important; }
        .admin-layout { display: flex !important; }
    }
</style>

<!-- LIGHTBOX ZOOM -->
<div id="zoom-lightbox" onclick="this.classList.remove('show')">
    <img id="zoom-img" src="">
</div>

<!-- TOAST -->
<div id="admin-toast" style="display:none; opacity:0;"></div>

<!-- =============================================
     TOPBAR (compartida)
============================================= -->
<div class="admin-main-container">
    <div class="topbar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="https://squoosh.app/" target="_blank" class="btn-squoosh-tool" title="Reducir peso de imágenes">
                <i class="fas fa-compress-arrows-alt"></i> <span class="hide-xs">OPTIMIZAR</span>
            </a>
            <button onclick="saveToDatabase()" id="btn-save-all" class="btn-camiglobo" style="padding: 12px 22px; margin: 0;">
                <i class="fas fa-cloud-upload-alt"></i> PUBLICAR
            </button>
        </div>
    </div>

    <!-- =============================================
         LAYOUT ESCRITORIO
    ============================================= -->
    <div class="desktop-wrapper admin-layout">
        <!-- Panel lista desktop -->
        <div class="list-panel">
            <button onclick="addNewProduct()" class="btn-camiglobo" style="width:100%; margin-bottom:8px; background:#000; padding:12px;">
                <i class="fas fa-plus-circle"></i> NUEVO PRODUCTO
            </button>

            <div style="display:flex; gap:6px; margin-bottom:8px; align-items:center;">
                <button id="btn-select-mode" onclick="toggleSelectMode()">
                    <i class="fas fa-check-square"></i> SELECCIONAR
                </button>
                <button onclick="selectAll()" id="btn-select-all" style="display:none; background:#fff; color:#555; border:1px solid #ddd; padding:5px 10px; border-radius:6px; font-size:10px; font-weight:800; cursor:pointer; text-transform:uppercase;">
                    TODO
                </button>
            </div>

            <div id="multi-delete-bar">
                <span id="selected-count-label">0 seleccionados</span>
                <button class="btn-multi-cancel" onclick="clearSelection()">✕ Cancelar</button>
                <button class="btn-multi-del" onclick="deleteSelected()">🗑️ ELIMINAR</button>
            </div>

            <div style="position:relative; margin-bottom:8px;">
                <i class="fas fa-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#ccc; font-size:13px;"></i>
                <input type="text" id="search-box" style="width:100%; padding:10px 10px 10px 34px; border-radius:8px; border:1px solid var(--border); font-size:13px;" placeholder="Buscar..." onkeyup="renderProductList()">
            </div>

            <!-- AÑADIDO: filtros desktop -->
            <div class="cat-filters" id="cat-filters-desktop"></div>
            <div class="results-count" id="results-count-desktop"></div>

            <div id="product-list-container" class="product-scroll-area"></div>
        </div>

        <!-- Panel editor desktop -->
        <div class="edit-panel" id="product-editor">
            <div style="height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; color:#bdc3c7;">
                <i class="fas fa-pencil-alt" style="font-size:50px; margin-bottom:15px; opacity:0.2;"></i>
                <p style="font-weight:800;">Selecciona un producto</p>
            </div>
        </div>
    </div>

    <!-- =============================================
         LAYOUT MÓVIL (pantallas tipo app)
    ============================================= -->
    <div class="mobile-wrapper">
        <div class="mobile-screens" id="mobile-screens">

            <!-- PANTALLA 1: LISTA -->
            <div class="mobile-screen" id="screen-list">
                <div class="mobile-list-content">
                    <button onclick="addNewProduct()" class="btn-camiglobo btn-new-mobile">
                        <i class="fas fa-plus-circle"></i> NUEVO PRODUCTO
                    </button>

                    <div style="display:flex; gap:8px; margin-bottom:10px; align-items:center;">
                        <button id="btn-select-mode-m" onclick="toggleSelectMode()" style="background:#fff; color:#e74c3c; border:1px solid #e74c3c; padding:10px 14px; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer; text-transform:uppercase; transition:0.2s; white-space:nowrap;">
                            <i class="fas fa-check-square"></i> SELECCIONAR
                        </button>
                        <button onclick="selectAll()" id="btn-select-all-m" style="display:none; background:#fff; color:#555; border:1px solid #ddd; padding:10px 14px; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer; text-transform:uppercase;">
                            TODO
                        </button>
                    </div>

                    <div id="multi-delete-bar-m" style="display:none; background:#fff0f0; border:1px solid #ffcccc; border-radius:10px; padding:10px 12px; margin-bottom:10px; align-items:center; gap:8px;">
                        <span id="selected-count-label-m" style="font-size:12px; font-weight:800; color:#e74c3c; flex:1;">0 seleccionados</span>
                        <button class="btn-multi-cancel" onclick="clearSelection()" style="padding:8px 14px; font-size:12px; border-radius:8px;">✕ Cancelar</button>
                        <button class="btn-multi-del" onclick="deleteSelected()" style="padding:8px 14px; font-size:12px; border-radius:8px;">🗑️ ELIMINAR</button>
                    </div>

                    <div class="search-bar-mobile">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-box-m" placeholder="Buscar producto..." onkeyup="renderProductListMobile()">
                    </div>

                    <!-- AÑADIDO: filtros móvil -->
                    <div class="cat-filters" id="cat-filters-mobile"></div>
                    <div class="results-count" id="results-count-mobile"></div>

                    <div id="product-list-container-m" class="product-scroll-area" style="overflow:visible;"></div>
                </div>
            </div>

            <!-- PANTALLA 2: EDITOR -->
            <div class="mobile-screen" id="screen-editor">
                <!-- El contenido se inyecta por JS -->
                <div id="mobile-editor-inner"></div>
            </div>

        </div><!-- /mobile-screens -->
    </div><!-- /mobile-wrapper -->

</div><!-- /admin-main-container -->

<script>
    // =====================================================
    // DATOS
    // =====================================================
    let productsData = <?php echo $json_final; ?>.products;
    let activeId = null;
    let tempGallery = [];
    let selectMode = false;
    let selectedIds = new Set();

    // Orden de marcado: array de {id, featuredAt} ordenado de más antiguo a más nuevo
    // Si no hay featuredAt guardado, usamos índice como fallback (orden de lista)
    let featuredOrder = productsData
        .filter(p => p.featured)
        .map((p, i) => ({ id: String(p.id), at: p.featuredAt || i }))
        .sort((a, b) => a.at - b.at);
    const MAX_FEATURED = 10;

    const categoriasDisponibles = ["camiseta", "sudadera", "taza", "cuadro", "otro"];

    // AÑADIDO: categoría activa en filtro
    let activeCat = 'todos';

    // Colores por categoría para el punto visual
    const catColors = { camiseta:'#e74c3c', sudadera:'#8e44ad', taza:'#2980b9', cuadro:'#27ae60', otro:'#7f8c8d' };

    // Detecta si estamos en móvil
    const isMobile = () => window.innerWidth <= 768;

    // =====================================================
    // AÑADIDO: RENDER FILTROS DE CATEGORÍA
    // =====================================================
    function renderCatFilters() {
        const deskEl   = document.getElementById('cat-filters-desktop');
        const mobileEl = document.getElementById('cat-filters-mobile');

        // Contamos sobre TODOS los productos (sin aplicar filtro de búsqueda)
        const counts = { todos: productsData.length, destacado: productsData.filter(p => p.featured).length };
        categoriasDisponibles.forEach(c => { counts[c] = productsData.filter(p => p.category === c).length; });

        // Solo categorías con al menos 1 producto; "destacado" solo si hay alguno
        const cats = ['todos', ...categoriasDisponibles.filter(c => counts[c] > 0)];
        if (counts.destacado > 0) cats.push('destacado');

        const html = cats.map(cat => {
            const label = cat === 'todos' ? 'Todos' : cat === 'destacado' ? '⭐ Top' : cat.charAt(0).toUpperCase() + cat.slice(1);
            return `<span class="cat-pill ${activeCat === cat ? 'active' : ''}" data-cat="${cat}" onclick="setCatFilter('${cat}')">${label}<span class="pill-count">${counts[cat]}</span></span>`;
        }).join('');

        if (deskEl)   deskEl.innerHTML   = html;
        if (mobileEl) mobileEl.innerHTML = html;
    }

    function setCatFilter(cat) {
        activeCat = cat;
        renderCatFilters();
        renderProductList();
        renderProductListMobile();
    }
    // FIN AÑADIDO

    // =====================================================
    // HELPERS CAMPOS URL MÓVIL
    // =====================================================
    function toggleUrlField(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const visible = row.style.display !== 'none';
        row.style.display = visible ? 'none' : 'block';
        if (!visible) {
            // Foco automático al input dentro
            const inp = row.querySelector('input[type=text]');
            if (inp) setTimeout(() => inp.focus(), 50);
        }
    }

    function actualizarPortadaDesdeUrl(url) {
        // Actualiza el campo oculto unificado, la preview y el análisis
        const hidden = document.getElementById('f-image-hidden');
        if (hidden) hidden.value = url;
        // El campo visible ya tiene id f-image — syncLocal lo leerá
        const preview = document.getElementById('main-preview');
        if (preview) preview.src = url || 'images/placeholder.png';
        analizarSaludImagen(url);
        actualizarLinkPortada(url);
    }

    // =====================================================
    // ZOOM
    // =====================================================
    function abrirZoom(url) {
        if (!url || url.includes('placeholder')) return;
        document.getElementById('zoom-img').src = url;
        document.getElementById('zoom-lightbox').classList.add('show');
    }

    // =====================================================
    // TOAST
    // =====================================================
    function showToast(msg) {
        const toast = document.getElementById('admin-toast');
        toast.textContent = msg;
        toast.style.display = 'block';
        toast.style.opacity = '1';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { 
            toast.style.opacity = '0';
            setTimeout(() => { toast.style.display = 'none'; }, 400);
        }, 3000);
    }

    // =====================================================
    // ESCAPE HTML
    // =====================================================
    function escapeHTML(str) {
        if (!str) return "";
        return str.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    // =====================================================
    // CONTADOR DE DESTACADOS
    // =====================================================
    function updateFeaturedCounter() {
        const el = document.getElementById('featured-counter-info');
        if (!el) return;
        const total = productsData.filter(p => p.featured).length;
        const slots = `${total} de ${MAX_FEATURED} posiciones usadas`;

        if (featuredOrder.length > 0) {
            const oldest = featuredOrder[0];
            const oldestProd = productsData.find(p => String(p.id) === oldest.id);
            const fecha = oldest.at
                ? new Date(oldest.at * 1000).toLocaleDateString('es-ES', { day:'2-digit', month:'short', year:'numeric' })
                : '—';
            const nombre = oldestProd ? oldestProd.name : '—';
            el.innerHTML = `${slots}<br>Si se llega a ${MAX_FEATURED}, se quitará: <strong style="color:#e74c3c;">${nombre}</strong> <span style="opacity:0.6;">(destacado desde ${fecha})</span>`;
        } else {
            el.textContent = slots;
        }
    }

    // =====================================================
    // NAVEGACIÓN MÓVIL (pantallas tipo app)
    // =====================================================
    function goToEditor() {
        if (!isMobile()) return;
        document.getElementById('screen-list').classList.add('hidden-screen');
        document.getElementById('screen-editor').classList.add('visible-screen');
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    function goToList() {
        if (!isMobile()) return;
        document.getElementById('screen-list').classList.remove('hidden-screen');
        document.getElementById('screen-editor').classList.remove('visible-screen');
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    // =====================================================
    // RENDER LISTA DESKTOP
    // =====================================================
    function renderProductList() {
        const container = document.getElementById('product-list-container');
        if (!container) return;
        const search = (document.getElementById('search-box')?.value || '').toLowerCase();
        _renderList(container, search, false);
    }

    // =====================================================
    // RENDER LISTA MÓVIL
    // =====================================================
    function renderProductListMobile() {
        const container = document.getElementById('product-list-container-m');
        if (!container) return;
        const search = (document.getElementById('search-box-m')?.value || '').toLowerCase();
        _renderList(container, search, true);
    }

    // =====================================================
    // RENDER LISTA COMÚN — MODIFICADO: añade filtro categoría + punto color + contador
    // =====================================================
    function _renderList(container, search, mobile) {
        // AÑADIDO: aplica filtro de categoría además del de búsqueda
        const filtered = productsData.filter(p => {
            const matchSearch = p.name.toLowerCase().includes(search);
            const matchCat = activeCat === 'todos'      ? true
                           : activeCat === 'destacado'  ? p.featured
                           : p.category === activeCat;
            return matchSearch && matchCat;
        });

        // AÑADIDO: actualiza contador de resultados
        const countEl = document.getElementById(mobile ? 'results-count-mobile' : 'results-count-desktop');
        if (countEl) {
            countEl.textContent = filtered.length === productsData.length
                ? `${filtered.length} productos`
                : `${filtered.length} de ${productsData.length}`;
        }

        if (filtered.length === 0) {
            container.innerHTML = `<div style="text-align:center; padding:20px; color:#999; font-size:14px;">No hay productos</div>`;
            return;
        }

        container.innerHTML = filtered.map(p => {
            const idStr = String(p.id);
            const isSelected = selectedIds.has(idStr);
            const isActive = activeId == p.id;

            let extraClass = '';
            if (isSelected) extraClass = 'selected-for-delete';
            else if (isActive && !mobile) extraClass = 'active';

            const checkboxHtml = selectMode
                ? `<input type="checkbox" class="prod-item-check" ${isSelected ? 'checked' : ''} onclick="toggleItemSelection('${idStr}', event)">`
                : '';

            const clickAction = selectMode
                ? `onclick="toggleItemSelection('${idStr}', event)"`
                : mobile 
                    ? `onclick="loadInEditorMobile('${p.id}')"`
                    : `onclick="loadInEditor('${p.id}')"`;

            // En móvil sin selectMode añadimos la flecha via CSS ::after
            const mobileClass = mobile && !selectMode ? 'mobile-list-item' : '';

            // AÑADIDO: punto de color por categoría y badge destacado
            const color = catColors[p.category] || '#999';
            const featBadge = p.featured ? ' ⭐' : '';

            return `
                <div id="${mobile?'m-':''}item-${p.id}" class="prod-item ${extraClass} ${mobileClass}" ${clickAction} style="${selectMode ? 'padding-right:12px;' : ''}">
                    ${checkboxHtml}
                    <img src="${p.image || 'images/placeholder.png'}" loading="lazy" onerror="this.src='images/placeholder.png'">
                    <div class="prod-item-info">
                        <strong>${escapeHTML(p.name)}${featBadge}</strong>
                        <small style="color:#999; display:flex; align-items:center; gap:3px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:${color};display:inline-block;flex-shrink:0;"></span>
                            ${p.category}
                        </small>
                    </div>
                    <div class="prod-item-price">${p.price.toFixed(2)}€</div>
                </div>
            `;
        }).join('');
    }

    // =====================================================
    // EDITOR DESKTOP
    // =====================================================
    function loadInEditor(id) {
        if (selectMode) return;
        // Guardar producto actual antes de cargar otro
        if (activeId && document.getElementById('f-name')) { syncLocal(); }
        activeId = id;
        const p = productsData.find(item => item.id == id);
        if (!p) return;
        tempGallery = p.gallery ? [...p.gallery] : [];

        const editor = document.getElementById('product-editor');
        editor.innerHTML = _buildEditorHTML(p, false);

        renderGalleryUI();
        renderProductList();
        analizarSaludImagen(p.image);
        // Auto-guardado mientras escribe
        document.querySelectorAll('#product-editor input, #product-editor select, #product-editor textarea').forEach(el => {
            el.addEventListener('input', () => { if (activeId) syncLocal(); });
        });
        // Listener para cambiar precio y mostrar material al cambiar categoría (desktop)
        const catSelect = document.getElementById('f-cat');
        if (catSelect) {
            catSelect.addEventListener('change', function() {
                const precioInput = document.getElementById('f-price');
                const materialRow = document.getElementById('material-row');
                if (precioInput) {
                    const preciosPorCategoria = { camiseta: 26, sudadera: 35, cuadro: 30, taza: 12 };
                    precioInput.value = preciosPorCategoria[this.value] || 26;
                }
                if (materialRow) {
                    materialRow.style.display = (this.value === 'cuadro') ? 'block' : 'none';
                }
            });
            // Mostrar/ocultar material según categoría inicial
            const materialRow = document.getElementById('material-row');
            if (materialRow) {
                materialRow.style.display = (catSelect.value === 'cuadro') ? 'block' : 'none';
            }
        }
        // Listener para cambiar precio al seleccionar material
        const materialSelect = document.getElementById('f-material');
        if (materialSelect) {
            materialSelect.addEventListener('change', function() {
                const precioInput = document.getElementById('f-price');
                if (precioInput) {
                    precioInput.value = (this.value === 'Azulejo') ? 9 : 30;
                }
            });
        }

        setTimeout(() => {
            document.getElementById('item-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }

    // =====================================================
    // EDITOR MÓVIL
    // =====================================================
    function loadInEditorMobile(id) {
        if (selectMode) return;
        // Guardar producto actual antes de cargar otro
        if (activeId && document.getElementById('f-name')) { syncLocal(); }
        activeId = id;
        const p = productsData.find(item => item.id == id);
        if (!p) return;
        tempGallery = p.gallery ? [...p.gallery] : [];

        const inner = document.getElementById('mobile-editor-inner');
        inner.innerHTML = _buildEditorHTML(p, true);

        renderGalleryUI();
        renderProductListMobile();
        analizarSaludImagen(p.image);
        updateFeaturedCounter();
        // Auto-guardado mientras escribe
        document.querySelectorAll('#mobile-editor-inner input, #mobile-editor-inner select, #mobile-editor-inner textarea').forEach(el => {
            el.addEventListener('input', () => { if (activeId) syncLocal(); });
        });
        // Listener para cambiar precio y mostrar material al cambiar categoría (móvil)
        const catSelectM = document.getElementById('f-cat');
        if (catSelectM) {
            catSelectM.addEventListener('change', function() {
                const precioInput = document.getElementById('f-price');
                const materialRow = document.getElementById('material-row');
                if (precioInput) {
                    const preciosPorCategoria = { camiseta: 26, sudadera: 35, cuadro: 30, taza: 12 };
                    precioInput.value = preciosPorCategoria[this.value] || 26;
                }
                if (materialRow) {
                    materialRow.style.display = (this.value === 'cuadro') ? 'block' : 'none';
                }
            });
            // Mostrar/ocultar material según categoría inicial
            const materialRow = document.getElementById('material-row');
            if (materialRow) {
                materialRow.style.display = (catSelectM.value === 'cuadro') ? 'block' : 'none';
            }
        }
        // Listener para cambiar precio al seleccionar material (móvil)
        const materialSelectM = document.getElementById('f-material');
        if (materialSelectM) {
            materialSelectM.addEventListener('change', function() {
                const precioInput = document.getElementById('f-price');
                if (precioInput) {
                    precioInput.value = (this.value === 'Azulejo') ? 9 : 30;
                }
            });
        }

        goToEditor();
    }

    // =====================================================
    // CONSTRUCTOR HTML DEL EDITOR
    // =====================================================
    function _buildEditorHTML(p, mobile) {
                // Biblioteca de assets comunes (hardcodeada por ahora)
                // Solo mostrar imágenes de camisetas si la categoría es 'camiseta'
                let assetLibrary = [];
                if (p.category === 'camiseta') {
                    assetLibrary = [
                        {
                            url: '/uploads/prod_e6485774e3b6e0f6_1773817978.jpg',
                            label: 'Espalda Negra'
                        },
                        {
                            url: '/uploads/prod_e973204c95f3d46a_1773819578.jpg',
                            label: 'Espalda Blanca'
                        }
                    ];
                }

                function renderAssetLibrary(targetField, isVideo) {
                    return `
                        <div style="margin:8px 0 12px 0;">
                            <div style="font-size:11px; color:#888; margin-bottom:4px;">Biblioteca común:</div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                ${assetLibrary.filter(a => isVideo ? a.url.endsWith('.mp4') : !a.url.endsWith('.mp4')).map(a => `
                                    <div style="cursor:pointer; text-align:center;" onclick="document.getElementById('${targetField}').value='${a.url}'; if(document.getElementById('${targetField}').oninput) document.getElementById('${targetField}').oninput(); showToast('Seleccionado: ${a.label}')">
                                        ${a.url.endsWith('.mp4')
                                            ? `<video src='${a.url}' style='width:48px; height:48px; object-fit:cover; border-radius:6px; border:1px solid #ccc; background:#000;' muted playsinline preload='metadata'></video>`
                                            : `<img src='${a.url}' style='width:48px; height:48px; object-fit:cover; border-radius:6px; border:1px solid #ccc; background:#fff;'>`}
                                        <div style="font-size:10px; color:#555; margin-top:2px;">${a.label}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
        const opcionesCat = categoriasDisponibles.map(cat => 
            `<option value="${cat}" ${p.category === cat ? 'selected' : ''}>${cat.toUpperCase()}</option>`
        ).join('');

        if (mobile) {
            return `
                <!-- Header sticky con volver -->
                <div class="mobile-editor-header">
                    <button class="btn-back-mobile" onclick="goToList()">
                        <i class="fas fa-chevron-left"></i> Lista
                    </button>
                    <h2 class="mobile-editor-title">${escapeHTML(p.name)}</h2>
                    <button class="mobile-editor-delete" onclick="deleteProduct('${p.id}')">🗑️</button>
                </div>

                <!-- Cuerpo del formulario -->
                <div class="mobile-editor-body">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" id="f-name" value="${escapeHTML(p.name)}" placeholder="Nombre del producto">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div class="form-group">
                            <label>Categoría</label>
                            <select id="f-cat">${opcionesCat}</select>
                        </div>
                        <div class="form-group">
                            <label>Precio (€)</label>
                            <input type="number" step="0.01" id="f-price" value="${p.price}" inputmode="decimal">
                        </div>
                    </div>

                    <div id="material-row" class="form-group" style="display:none;">
                        <label>Material</label>
                        <select id="f-material">
                            <option value="Aluminio">Aluminio (30€)</option>
                            <option value="Pizarra">Pizarra (30€)</option>
                            <option value="Azulejo">Azulejo (9€)</option>
                        </select>
                    </div>

                    <!-- PORTADA -->
                    <div class="form-group">
                        <label>Imagen de portada</label>
                        <!-- Selector visual de biblioteca eliminado de portada -->
                        <!-- Preview clicable para zoom -->
                        <div onclick="abrirZoom(document.getElementById('main-preview').src)" 
                             style="width:100%; height:200px; background:#fcfcfc; border-radius:12px; border:2px dashed #ddd; overflow:hidden; display:flex; align-items:center; justify-content:center; cursor:zoom-in; margin-bottom:10px; position:relative;">
                            <img id="main-preview" src="${p.image || 'images/placeholder.png'}" style="max-width:100%; max-height:100%; object-fit:contain;">
                        </div>
                        <!-- Botones de acción: subir foto o pegar link -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <label style="display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; background:#3498db; color:white; border-radius:12px; font-weight:800; font-size:13px; cursor:pointer; box-shadow:0 3px 10px rgba(52,152,219,0.3);">
                                <i class="fas fa-camera" style="font-size:16px;"></i> FOTO / VÍDEO
                                <input type="file" id="file-upload" accept="image/*,video/*" style="display:none" onchange="subirDesdePC(this)">
                            </label>
                            <button type="button" onclick="toggleUrlField('portada-url-row')" 
                                    style="display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; background:#ecf0f1; color:#555; border:1px solid #ddd; border-radius:12px; font-weight:800; font-size:13px; cursor:pointer;">
                                🔗 PEGAR LINK
                            </button>
                        </div>
                        <!-- Campo URL colapsable -->
                        <div id="portada-url-row" style="display:none; margin-bottom:8px;">
                            <input type="text" id="f-image" value="${escapeHTML(p.image)}" 
                                   placeholder="https://... pega aquí la URL de la imagen"
                                   style="width:100%; padding:13px 14px; border:2px solid #3498db; border-radius:10px; font-size:14px; background:white; outline:none;"
                                   oninput="actualizarPortadaDesdeUrl(this.value)">
                        </div>
                        <!-- Campo URL oculto (siempre existe, se actualiza desde ambas vías) -->
                        <input type="text" id="f-image-hidden" value="${escapeHTML(p.image)}" style="display:none">
                        <div id="image-health-info" class="health-info" style="display:none;"></div>
                        ${p.image ? `<div id="portada-link-row" style="display:flex; align-items:center; gap:6px; margin-top:6px; background:#f0f7ff; border-radius:8px; padding:8px 10px;">
                            <a id="portada-link-a" href="${escapeHTML(p.image)}" target="_blank" title="${escapeHTML(p.image)}"
                               style="flex:1; font-size:11px; color:#3498db; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${p.image}</a>
                            <button id="portada-link-copy" onclick="navigator.clipboard.writeText(document.getElementById('f-image-hidden').value).then(()=>showToast('✅ Link copiado'))"
                                    title="Copiar link"
                                    style="flex-shrink:0; width:32px; height:32px; background:white; border:1px solid #ddd; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:13px; color:#555;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>` : `<div id="portada-link-row" style="display:none; align-items:center; gap:6px; margin-top:6px; background:#f0f7ff; border-radius:8px; padding:8px 10px;">
                            <a id="portada-link-a" href="" target="_blank" style="flex:1; font-size:11px; color:#3498db; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></a>
                            <button id="portada-link-copy" onclick="navigator.clipboard.writeText(document.getElementById('f-image-hidden').value).then(()=>showToast('✅ Link copiado'))"
                                    title="Copiar link"
                                    style="flex-shrink:0; width:32px; height:32px; background:white; border:1px solid #ddd; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:13px; color:#555;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>`}
                    <div class="form-group">
                        <label>Galería de imágenes</label>
                        <!-- Selector visual de biblioteca para galería (solo imágenes) -->
                        ${renderAssetLibrary('new-gallery-url', false)}
                        <!-- Botones de acción -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px;">
                            <label style="display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; background:#8e44ad; color:white; border-radius:12px; font-weight:800; font-size:13px; cursor:pointer; box-shadow:0 3px 10px rgba(142,68,173,0.3);">
                                <i class="fas fa-images" style="font-size:16px;"></i> FOTOS / VÍDEO
                                <input type="file" accept="image/*,video/*" multiple style="display:none" onchange="subirVariasGaleriaDesdePC(this)">
                            </label>
                            <button type="button" onclick="toggleUrlField('galeria-url-row')"
                                    style="display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; background:#ecf0f1; color:#555; border:1px solid #ddd; border-radius:12px; font-weight:800; font-size:13px; cursor:pointer;">
                                🔗 PEGAR LINK
                            </button>
                        </div>
                        <!-- Campo URL galería colapsable -->
                        <div id="galeria-url-row" style="display:none; margin-bottom:12px;">
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="new-gallery-url" 
                                       placeholder="https://... URL de la imagen de galería"
                                       style="flex:1; padding:13px 14px; border:2px solid #8e44ad; border-radius:10px; font-size:14px; background:white; outline:none;">
                                <button onclick="addGalleryImage()" 
                                        style="background:#8e44ad; color:white; border:none; padding:0 18px; border-radius:10px; cursor:pointer; font-size:22px; font-weight:800; flex-shrink:0;">+</button>
                            </div>
                        </div>
                        <!-- Miniaturas de galería -->
                        <div id="gallery-items-container" style="display:flex; flex-wrap:wrap; gap:10px; min-height:60px;"></div>
                        <p style="margin-top:8px; font-size:10px; color:#aaa; line-height:1.5;">
                            🎬 Vídeo: MP4 · máx. 50MB · 720p recomendado.<br>
                            Comprimir gratis: <a href="https://clideo.com/compress-video" target="_blank" style="color:#3498db;">Clideo</a> (online) · <a href="https://handbrake.fr" target="_blank" style="color:#3498db;">HandBrake</a> (programa)
                        </p>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea id="f-desc" rows="3" placeholder="Describe el producto...">${escapeHTML(p.description)}</textarea>
                    </div>

                    <!-- DESTACADO -->
                    <div class="featured-bar" style="background:#111; padding:16px; border-radius:12px; margin-bottom:16px;">
                        <label style="color:white; font-weight:800; font-size:14px; display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" id="f-feat" ${p.featured ? 'checked' : ''} style="width:22px; height:22px; accent-color:#27ae60;"> 
                            <span>⭐ PRODUCTO DESTACADO</span>
                        </label>
                        <div id="featured-counter-info" style="color:#aaa; font-size:11px; margin-top:8px; padding-left:32px;"></div>
                    </div>


                </div>
            `;
        } else {
            // DESKTOP (igual que antes pero con selector visual de biblioteca)
            return `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h2>${escapeHTML(p.name)}</h2>
                    <button onclick="deleteProduct('${p.id}')" style="color:#e74c3c; border:1px solid #ffebeb; background:#fff5f5; cursor:pointer; font-size:11px; padding:5px 10px; border-radius:5px;">🗑️</button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" id="f-name" value="${escapeHTML(p.name)}">
                    </div>
                    <div class="form-group">
                        <label>Categoría</label>
                        <select id="f-cat">${opcionesCat}</select>
                    </div>
                    <div class="form-group">
                        <label>Precio</label>
                        <input type="number" step="0.01" id="f-price" value="${p.price}">
                    </div>
                </div>

                <div id="material-row" class="form-group" style="display:none;">
                    <label>Material</label>
                    <select id="f-material">
                        <option value="Aluminio">Aluminio (30€)</option>
                        <option value="Pizarra">Pizarra (30€)</option>
                        <option value="Azulejo">Azulejo (9€)</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:180px 1fr; gap:20px; margin-bottom:15px;">
                    <div class="form-group">
                        <label>Portada (Clic para Zoom)</label>
                        <!-- Selector visual de biblioteca eliminado de portada -->
                        <div style="width:100%; height:140px; background:#fcfcfc; border-radius:12px; border:1px solid #eee; overflow:hidden; display:flex; align-items:center; justify-content:center; cursor:zoom-in; margin-bottom:8px;" onclick="abrirZoom(this.querySelector('img').src)">
                            <img id="main-preview" src="${p.image || 'images/placeholder.png'}" style="max-width:100%; max-height:100%; object-fit:contain;">
                        </div>
                        <div style="display:flex; gap:5px;">
                            <input type="text" id="f-image" value="${escapeHTML(p.image)}" style="flex:1; font-size:11px;" 
                                   oninput="document.getElementById('main-preview').src=this.value; analizarSaludImagen(this.value); actualizarLinkPortada(this.value)">
                            <label class="btn-camiglobo" style="padding:5px 10px; background:#3498db !important; cursor:pointer; font-size:10px; margin:0; min-width:45px;">
                                <i class="fas fa-image"></i> FOTO/VID
                                <input type="file" accept="image/*,video/*" id="file-upload" style="display:none" onchange="subirDesdePC(this)">
                            </label>
                        </div>
                        <div id="image-health-info" class="health-info" style="display:none;"></div>
                        ${p.image ? `<div id="portada-link-row" style="display:flex; align-items:center; gap:4px; margin-top:4px;">
                            <a id="portada-link-a" href="${escapeHTML(p.image)}" target="_blank" title="${escapeHTML(p.image)}"
                               style="flex:1; font-size:9px; color:#3498db; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${p.image}</a>
                            <button id="portada-link-copy" onclick="navigator.clipboard.writeText(document.getElementById('f-image').value).then(()=>showToast('✅ Link copiado'))"
                                    title="Copiar link"
                                    style="flex-shrink:0; width:18px; height:18px; background:#ecf0f1; border:none; border-radius:3px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:10px; color:#555;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>` : `<div id="portada-link-row" style="display:none; align-items:center; gap:4px; margin-top:4px;">
                            <a id="portada-link-a" href="" target="_blank" style="flex:1; font-size:9px; color:#3498db; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></a>
                            <button id="portada-link-copy" onclick="navigator.clipboard.writeText(document.getElementById('f-image').value).then(()=>showToast('✅ Link copiado'))"
                                    title="Copiar link"
                                    style="flex-shrink:0; width:18px; height:18px; background:#ecf0f1; border:none; border-radius:3px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:10px; color:#555;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>`}
                    </div>
                    
                    <div class="form-group">
                        <label>Galería rápida</label>
                        <!-- Selector visual de biblioteca para galería (solo imágenes) -->
                        ${renderAssetLibrary('new-gallery-url', false)}
                        <div class="gallery-manager" style="padding:10px; height:140px; overflow-y:auto;">
                            <div style="display:flex; gap:5px; margin-bottom:8px;">
                                <input type="text" id="new-gallery-url" style="flex:1; padding:5px; font-size:11px;" placeholder="URL...">
                                <button onclick="addGalleryImage()" style="background:#000; color:white; border:none; padding:0 10px; border-radius:5px; cursor:pointer;">+</button>
                                <label class="btn-camiglobo" style="padding:5px 8px; background:#3498db !important; cursor:pointer; font-size:10px; margin:0; min-width:40px; height:28px;">
                                    <i class="fas fa-photo-video"></i>
                                    <input type="file" accept="image/*,video/*" style="display:none" onchange="subirGaleriaDesdePC(this)">
                                </label>
                            </div>
                            <div id="gallery-items-container" class="gallery-grid"></div>
                            <p style="margin-top:6px; font-size:9px; color:#aaa; line-height:1.4;">🎬 Vídeo: MP4 · máx. 50MB · 720p — Comprimir: <a href="https://clideo.com/compress-video" target="_blank" style="color:#3498db;">Clideo</a> · <a href="https://handbrake.fr" target="_blank" style="color:#3498db;">HandBrake</a></p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="f-desc" rows="2" style="resize:none;">${escapeHTML(p.description)}</textarea>
                </div>
                
                <div style="background:#111; padding:12px; border-radius:10px; display:flex; align-items:center; margin-bottom:15px;">
                    <label style="color:white; font-weight:800; font-size:12px; cursor:pointer;">
                        <input type="checkbox" id="f-feat" ${p.featured ? 'checked' : ''}> DESTACADO
                    </label>
                </div>

            `;
        }
    }

    // =====================================================
    // GALERÍA
    // =====================================================
    function renderGalleryUI() {
        const container = document.getElementById('gallery-items-container');
        if (!container) return;

        const mobile = isMobile();
        const thumbSize = mobile ? '90px' : '60px';
        const imgHeight = mobile ? '70px' : '45px';
        const delSize  = mobile ? '24px' : '18px';
        const delFont  = mobile ? '14px' : '11px';

        const fotosHtml = tempGallery.length === 0
            ? `<div style="color:#bbb; font-size:13px; padding:8px;">Sin fotos en galería</div>`
            : tempGallery.map((url, index) => {
                const shortUrl = url.length > 30 ? url.substring(0, 28) + '…' : url;
                const isVideo = url.toLowerCase().endsWith('.mp4');
                return `
                <div style="position:relative; width:${thumbSize}; border-radius:8px; border:1px solid #eee; background:#fff; padding:2px; flex-shrink:0;">
                    ${isVideo
                        ? `<div style='position:relative; border-radius:6px; overflow:hidden; background:#000;'>
                               <video src='${escapeHTML(url)}' style='width:100%; height:${imgHeight}; object-fit:cover; display:block;' muted playsinline preload='metadata'></video>
                               <div style="position:absolute; inset:0; background:rgba(0,0,0,0.45); display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                                   <i class='fas fa-play' style='color:white; font-size:14px; margin-bottom:2px;'></i>
                                   <span style='color:white; font-size:8px; font-weight:800; letter-spacing:0.3px;'>VÍDEO</span>
                               </div>
                           </div>`
                        : `<img src="${escapeHTML(url)}" onclick="abrirZoom('${escapeHTML(url)}')" onerror="this.src='images/placeholder.png'" 
                               style="width:100%; height:${imgHeight}; object-fit:cover; border-radius:6px; cursor:zoom-in; display:block;">`}
                    <button onclick="removeGalleryImage(${index})" 
                            style="position:absolute; top:-6px; right:-6px; width:${delSize}; height:${delSize}; background:#e74c3c; color:white; border:none; border-radius:50%; font-size:${delFont}; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1; padding:0; font-weight:800;">×</button>
                    <div style="display:flex; align-items:center; gap:2px; padding:2px 1px 0 1px;">
                        <a href="${escapeHTML(url)}" target="_blank" title="${escapeHTML(url)}"
                           style="flex:1; font-size:8px; color:${isVideo ? '#27ae60' : '#3498db'}; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; line-height:1.3;"
                           onclick="event.stopPropagation();">${shortUrl}</a>
                        <button onclick="event.stopPropagation(); navigator.clipboard.writeText('${escapeHTML(url)}').then(()=>showToast('✅ Link copiado'))"
                                title="Copiar link"
                                style="flex-shrink:0; width:16px; height:16px; background:#ecf0f1; border:none; border-radius:3px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:9px; color:#555;">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            `}).join('');

        // Miniatura del vídeo "cómo se hace" si existe
        const idx = productsData.findIndex(p => String(p.id) === String(activeId));
        const videoUrl = idx !== -1 ? (productsData[idx].videoComoSeHace || '') : '';
        const videoShort = videoUrl.length > 30 ? videoUrl.substring(0, 28) + '…' : videoUrl;
        const videoHtml = videoUrl ? `
            <div style="position:relative; width:${thumbSize}; border-radius:8px; border:2px solid #27ae60; background:#fff; flex-shrink:0;">
                <div style="position:relative; border-radius:6px; overflow:hidden; background:#000;">
                    <video src="${escapeHTML(videoUrl)}" style="width:100%; height:${imgHeight}; object-fit:cover; display:block;" muted playsinline preload="metadata"></video>
                    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.45); display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                        <i class="fas fa-play" style="color:white; font-size:14px; margin-bottom:2px;"></i>
                        <span style="color:white; font-size:8px; font-weight:800; letter-spacing:0.3px;">VÍDEO</span>
                    </div>
                    <button onclick="eliminarVideo()" 
                            style="position:absolute; top:-6px; right:-6px; width:${delSize}; height:${delSize}; background:#e74c3c; color:white; border:none; border-radius:50%; font-size:${delFont}; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1; padding:0; font-weight:800; pointer-events:all;">×</button>
                </div>
                <div style="display:flex; align-items:center; gap:2px; padding:2px 1px 0 1px;">
                    <a href="${escapeHTML(videoUrl)}" target="_blank" title="${escapeHTML(videoUrl)}"
                       style="flex:1; font-size:8px; color:#27ae60; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; line-height:1.3;"
                       onclick="event.stopPropagation();">${videoShort}</a>
                    <button onclick="event.stopPropagation(); navigator.clipboard.writeText('${escapeHTML(videoUrl)}').then(()=>showToast('✅ Link copiado'))"
                            title="Copiar link"
                            style="flex-shrink:0; width:16px; height:16px; background:#ecf0f1; border:none; border-radius:3px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:9px; color:#555;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>` : '';

        container.innerHTML = fotosHtml + videoHtml;
    }

    function actualizarLinkPortada(url) {
        const row = document.getElementById('portada-link-row');
        const a   = document.getElementById('portada-link-a');
        if (!row || !a) return;
        if (url && url.trim()) {
            row.style.display = 'flex';
            a.href = url;
            a.title = url;
            a.textContent = url;
        } else {
            row.style.display = 'none';
        }
    }

    function eliminarVideo() {
        const idx = productsData.findIndex(p => String(p.id) === String(activeId));
        if (idx !== -1) {
            productsData[idx].videoComoSeHace = '';
            renderGalleryUI();
            showToast("🗑️ Vídeo eliminado — Recuerda publicar");
        }
    }

    function addGalleryImage() {
        const input = document.getElementById('new-gallery-url');
        const url = input.value.trim();
        if (url) { tempGallery.push(url); input.value = ""; renderGalleryUI(); }
    }

    function removeGalleryImage(index) {
        tempGallery.splice(index, 1);
        renderGalleryUI();
    }

    // =====================================================
    // ANÁLISIS SALUD IMAGEN
    // =====================================================
    async function analizarSaludImagen(url) {
        const infoDiv = document.getElementById('image-health-info');
        if (!infoDiv) return;
        if (!url || url === "" || url.includes('placeholder')) { infoDiv.style.display = "none"; return; }

        infoDiv.style.display = "block";
        infoDiv.innerHTML = "🔍 Analizando...";
        infoDiv.className = "health-info";

        const img = new Image();
        img.onload = async function() {
            const w = this.width, h = this.height;
            let colorClass = "health-ok", msg = "TAMAÑO PERFECTO";
            if (w > 1500 || h > 1500) { colorClass = "health-danger"; msg = "MUY GRANDE (LENTA)"; }
            else if (w > 1000 || h > 1000) { colorClass = "health-warning"; msg = "ALGO GRANDE"; }
            infoDiv.className = "health-info " + colorClass;
            infoDiv.innerHTML = `📏 ${w}x${h}px | ${msg}`;
            try {
                const response = await fetch(url);
                const blob = await response.blob();
                infoDiv.innerHTML += ` | ⚖️ ${(blob.size/1024).toFixed(1)}KB`;
            } catch(e) { infoDiv.innerHTML += ` | ⚖️ Peso: Protegido`; }
        };
        img.onerror = function() {
            infoDiv.innerHTML = "❌ URL DE IMAGEN ROTA";
            infoDiv.className = "health-info health-danger";
        };
        img.src = url;
    }

    // =====================================================
    // GUARDAR LOCAL
    // =====================================================
    function syncLocal() {
        const idx = productsData.findIndex(p => p.id == activeId);
        if (idx === -1) return;

        // Auto-guardar sin mostrar mensaje
        const idStr        = String(activeId);
        const wasFeatured  = productsData[idx].featured;
        const nowFeatured  = document.getElementById('f-feat')?.checked || false;

        // --- Gestión de destacados con timestamp ---
        if (nowFeatured && !wasFeatured) {
            // Se acaba de marcar: añadir con timestamp actual
            const now = Math.floor(Date.now() / 1000);
            featuredOrder.push({ id: idStr, at: now });
            // Guarda el timestamp en el producto
            productsData[idx].featuredAt = now;

            // Si superamos el límite, quitar el más antiguo (primero del array ya ordenado)
            if (featuredOrder.length > MAX_FEATURED) {
                const oldest = featuredOrder.shift();
                const oldestIdx = productsData.findIndex(p => String(p.id) === oldest.id);
                if (oldestIdx !== -1) {
                    productsData[oldestIdx].featured   = false;
                    productsData[oldestIdx].featuredAt = null;
                    showToast(`⚠️ Límite de ${MAX_FEATURED}: se quitó "${productsData[oldestIdx].name}"`);
                }
            }
        } else if (!nowFeatured && wasFeatured) {
            // Se acaba de desmarcar: eliminar del array y limpiar timestamp
            featuredOrder = featuredOrder.filter(e => e.id !== idStr);
            productsData[idx].featuredAt = null;
        }

        productsData[idx] = {
            ...productsData[idx],
            name:        document.getElementById('f-name')?.value.trim() || productsData[idx].name,
            category:    document.getElementById('f-cat')?.value || productsData[idx].category,
            price:       parseFloat(document.getElementById('f-price')?.value) || 0,
            image:       (document.getElementById('f-image')?.value.trim() || document.getElementById('f-image-hidden')?.value.trim() || productsData[idx].image),
            gallery:     [...tempGallery],
            videoDelante: '',
            videoDetras:  '',
            videoComoSeHace: document.getElementById('f-video-como-se-hace')?.value.trim() || productsData[idx].videoComoSeHace || '',
            description: document.getElementById('f-desc')?.value.trim() || productsData[idx].description,
            featured:    nowFeatured,
            featuredAt:  productsData[idx].featuredAt  // ya actualizado arriba si cambió
        };

        // Cuántos destacados hay ahora
        const totalFeat = productsData.filter(p => p.featured).length;
        const featMsg   = nowFeatured ? ` | ⭐ ${totalFeat}/${MAX_FEATURED} destacados` : '';

        if (!nowFeatured || wasFeatured) {
            showToast("✅ Guardado automáticamente" + featMsg);
        }

        renderCatFilters(); // AÑADIDO: actualiza contadores si cambió categoría/destacado
        renderProductList();
        renderProductListMobile();
        updateFeaturedCounter();

        // Actualiza título en móvil
        const titleEl = document.querySelector('.mobile-editor-title');
        if (titleEl) titleEl.textContent = productsData[idx].name;
    }

    // =====================================================
    // NUEVO PRODUCTO
    // =====================================================
    function addNewProduct() {
        const newId = "N-" + Date.now();
        productsData.unshift({ 
            id: newId, name: "Nuevo Producto", price: 26.00, 
            category: "camiseta", image: "", gallery: [], 
            description: "", featured: false 
        });
        renderCatFilters(); // AÑADIDO
        if (isMobile()) {
            renderProductListMobile();
            loadInEditorMobile(newId);
        } else {
            loadInEditor(newId);
        }
    }

    // =====================================================
    // ELIMINAR PRODUCTO INDIVIDUAL
    // =====================================================
    function deleteProduct(id) {
        if (confirm("¿Eliminar este producto?")) {
            productsData = productsData.filter(p => p.id != id);
            if (activeId == id) {
                activeId = null;
                if (isMobile()) {
                    document.getElementById('mobile-editor-inner').innerHTML = '';
                    goToList();
                } else {
                    document.getElementById('product-editor').innerHTML = `
                        <div style="height:100%; display:flex; justify-content:center; align-items:center; color:#ccc;">
                            <p style="font-weight:800;">Producto eliminado</p>
                        </div>`;
                }
            }
            renderCatFilters(); // AÑADIDO
            renderProductList();
            renderProductListMobile();
            showToast("🗑️ Eliminado — Recuerda publicar");
        }
    }

    // =====================================================
    // MODO SELECCIÓN MÚLTIPLE
    // =====================================================
    function toggleSelectMode() {
        selectMode = !selectMode;

        // Desktop
        const btn = document.getElementById('btn-select-mode');
        const btnAll = document.getElementById('btn-select-all');
        // Móvil
        const btnM = document.getElementById('btn-select-mode-m');
        const btnAllM = document.getElementById('btn-select-all-m');

        if (selectMode) {
            if (btn) { btn.classList.add('active-mode'); btn.innerHTML = '<i class="fas fa-times"></i> CANCELAR'; }
            if (btnM) { btnM.style.background = '#e74c3c'; btnM.style.color = 'white'; btnM.innerHTML = '<i class="fas fa-times"></i> CANCELAR'; }
            if (btnAll) btnAll.style.display = 'block';
            if (btnAllM) btnAllM.style.display = 'block';
        } else {
            if (btn) { btn.classList.remove('active-mode'); btn.innerHTML = '<i class="fas fa-check-square"></i> SELECCIONAR'; }
            if (btnM) { btnM.style.background = '#fff'; btnM.style.color = '#e74c3c'; btnM.innerHTML = '<i class="fas fa-check-square"></i> SELECCIONAR'; }
            if (btnAll) btnAll.style.display = 'none';
            if (btnAllM) btnAllM.style.display = 'none';
            clearSelection();
        }
        renderProductList();
        renderProductListMobile();
    }

    function toggleItemSelection(id, e) {
        e.stopPropagation();
        if (selectedIds.has(id)) { selectedIds.delete(id); } else { selectedIds.add(id); }
        updateMultiDeleteBar();
        renderProductList();
        renderProductListMobile();
    }

    function selectAll() {
        const searchD = document.getElementById('search-box')?.value.toLowerCase() || '';
        const searchM = document.getElementById('search-box-m')?.value.toLowerCase() || '';
        const search = searchD || searchM;
        const filtered = productsData.filter(p => p.name.toLowerCase().includes(search));
        const allVisible = filtered.every(p => selectedIds.has(String(p.id)));
        filtered.forEach(p => allVisible ? selectedIds.delete(String(p.id)) : selectedIds.add(String(p.id)));
        updateMultiDeleteBar();
        renderProductList();
        renderProductListMobile();
    }

    function clearSelection() {
        selectedIds.clear();
        updateMultiDeleteBar();
        renderProductList();
        renderProductListMobile();
    }

    function updateMultiDeleteBar() {
        const count = selectedIds.size;
        const label = `${count} producto${count !== 1 ? 's' : ''} seleccionado${count !== 1 ? 's' : ''}`;

        // Desktop
        const bar = document.getElementById('multi-delete-bar');
        const lbl = document.getElementById('selected-count-label');
        if (bar) { bar.classList.toggle('visible', count > 0 && selectMode); }
        if (lbl) lbl.textContent = label;

        // Móvil
        const barM = document.getElementById('multi-delete-bar-m');
        const lblM = document.getElementById('selected-count-label-m');
        if (barM) { barM.style.display = (count > 0 && selectMode) ? 'flex' : 'none'; }
        if (lblM) lblM.textContent = label;
    }

    function deleteSelected() {
        if (selectedIds.size === 0) return;
        const nombres = productsData.filter(p => selectedIds.has(String(p.id))).map(p => `• ${p.name}`).join('\n');
        if (!confirm(`¿Eliminar ${selectedIds.size} producto${selectedIds.size > 1 ? 's' : ''}?\n\n${nombres}`)) return;
        productsData = productsData.filter(p => !selectedIds.has(String(p.id)));
        if (activeId && selectedIds.has(String(activeId))) {
            activeId = null;
            if (isMobile()) goToList();
        }
        selectedIds.clear();
        updateMultiDeleteBar();
        renderCatFilters(); // AÑADIDO
        renderProductList();
        renderProductListMobile();
        showToast(`🗑️ Productos eliminados — Recuerda publicar`);
    }

    // =====================================================
    // PUBLICAR EN BD (Publica los cambios guardados)
    // =====================================================
    async function saveToDatabase() {
        // Guardar producto activo antes de publicar
        if (activeId && document.getElementById('f-name')) { syncLocal(); }

        const btn = document.getElementById('btn-save-all');
        btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> GUARDANDO...';
        btn.disabled = true;
        try {
            const res = await fetch('save-products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ products: productsData })
            });
            const result = await res.json();
            if (result.success) { alert("✅ Cambios publicados"); location.reload(); }
            else { alert("❌ Error: " + (result.error || "Desconocido")); }
        } catch(e) { alert("❌ Error de conexión"); }
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> PUBLICAR';
        btn.disabled = false;
    }

    // =====================================================
    // SUBIDA DESDE PC (portada)
    // =====================================================
    async function subirDesdePC(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const btn = input.parentElement;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        if (file.type.startsWith('video/')) {
            await subirVideoComoSeHace(file, btn, originalHTML);
            renderGalleryUI();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const MAX = 1000;
                let w = img.width, h = img.height;
                if (w > MAX || h > MAX) {
                    if (w > h) { h *= MAX/w; w = MAX; } else { w *= MAX/h; h = MAX; }
                }
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(async (blob) => {
                    const fd = new FormData();
                    fd.append('foto', blob, 'portada.jpg');
                    try {
                        const res = await fetch('upload.php', { method:'POST', body:fd });
                        const data = await res.json();
                        if (data.success) {
                            const fImg = document.getElementById('f-image');
                            const fImgH = document.getElementById('f-image-hidden');
                            if (fImg) fImg.value = data.url;
                            if (fImgH) fImgH.value = data.url;
                            document.getElementById('main-preview').src = data.url;
                            analizarSaludImagen(data.url);
                            syncLocal();
                            showToast("✅ Portada optimizada y lista");
                        }
                    } catch(err) { alert("❌ Error en la subida"); }
                    btn.innerHTML = originalHTML;
                }, 'image/jpeg', 0.8);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // =====================================================
    // SUBIDA MÚLTIPLE GALERÍA MÓVIL
    // =====================================================
    async function subirVariasGaleriaDesdePC(input) {
        if (!input.files || input.files.length === 0) return;
        const btn = input.parentElement;
        const originalHTML = btn.innerHTML;
        const files = Array.from(input.files);

        showToast(`📤 Subiendo ${files.length} archivo${files.length > 1 ? 's' : ''}...`);
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';

        for (const file of files) {
            if (file.type.startsWith('video/')) {
                await subirVideoComoSeHace(file, btn, originalHTML);
                continue;
            }
            await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const MAX = 1000;
                        let w = img.width, h = img.height;
                        if (w > MAX || h > MAX) {
                            if (w > h) { h *= MAX/w; w = MAX; } else { w *= MAX/h; h = MAX; }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w; canvas.height = h;
                        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                        canvas.toBlob(async (blob) => {
                            const fd = new FormData();
                            fd.append('foto', blob, 'galeria.jpg');
                            try {
                                const res = await fetch('upload.php', { method:'POST', body:fd });
                                const data = await res.json();
                                if (data.success) {
                                    tempGallery.push(data.url);
                                    renderGalleryUI();
                                }
                            } catch(err) { /* silencioso */ }
                            resolve();
                        }, 'image/jpeg', 0.8);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        syncLocal();
        showToast(`✅ ${files.length} foto${files.length > 1 ? 's' : ''} añadida${files.length > 1 ? 's' : ''} a la galería`);
        btn.innerHTML = originalHTML;
    }

    // =====================================================
    // SUBIDA DESDE PC (galería)
    // =====================================================
    async function subirGaleriaDesdePC(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const btn = input.parentElement;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        if (file.type.startsWith('video/')) {
            await subirVideoComoSeHace(file, btn, originalHTML);
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const MAX = 1000;
                let w = img.width, h = img.height;
                if (w > MAX || h > MAX) {
                    if (w > h) { h *= MAX/w; w = MAX; } else { w *= MAX/h; h = MAX; }
                }
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(async (blob) => {
                    const fd = new FormData();
                    fd.append('foto', blob, 'galeria.jpg');
                    try {
                        const res = await fetch('upload.php', { method:'POST', body:fd });
                        const data = await res.json();
                        if (data.success) {
                            tempGallery.push(data.url);
                            renderGalleryUI();
                            syncLocal();
                            showToast("✅ Foto añadida a la galería");
                        }
                    } catch(err) { alert("❌ Error en galería"); }
                    btn.innerHTML = originalHTML;
                }, 'image/jpeg', 0.8);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // =====================================================
    // SUBIR VÍDEOS
    // =====================================================
    // Sube un vídeo y lo guarda como "cómo se hace"
    async function subirVideoComoSeHace(file, btn, originalHTML) {
        const validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        if (!validTypes.includes(file.type)) {
            alert('❌ Formato de vídeo no válido. Usa MP4, WebM u OGG');
            btn.innerHTML = originalHTML;
            return;
        }
    if (file.size > 50 * 1024 * 1024) {
        alert('❌ Vídeo demasiado grande (' + (file.size/1024/1024).toFixed(1) + 'MB). Máximo 50MB.\n\nComprímelo gratis:\n• Online (sin instalar): clideo.com/compress-video\n• Programa gratuito: handbrake.fr');
        btn.innerHTML = originalHTML;
        return;
    }
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo vídeo...';
        const fd = new FormData();
        fd.append('video', file, 'video_como-se-hace.mp4');
        try {
            const res = await fetch('upload.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                const idx = productsData.findIndex(p => String(p.id) === String(activeId));
                if (idx !== -1) {
                    productsData[idx].videoComoSeHace = data.url;
                    renderGalleryUI();
                    renderProductList();
                    renderProductListMobile();
                }
                showToast("✅ Vídeo subido como 'Cómo se hace'");
            } else {
                alert("❌ Error al subir el vídeo: " + (data.error || ''));
            }
        } catch (err) {
            alert("❌ Error en la subida del vídeo");
        }
        btn.innerHTML = originalHTML;
    }

    // =====================================================
    // INIT
    // =====================================================
    document.addEventListener("DOMContentLoaded", function() {
        renderCatFilters(); // AÑADIDO
        renderProductList();
        renderProductListMobile();

        const urlId = "<?php echo $id_directo; ?>";
        if (urlId) {
            setTimeout(() => {
                if (isMobile()) loadInEditorMobile(urlId);
                else loadInEditor(urlId);
            }, 200);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>