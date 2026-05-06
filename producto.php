<?php 
/**
 * ARCHIVO: producto.php
 * FUNCIÓN: Ficha de producto premium con Zoom avanzado, validación pro y Cross-Selling.
 * ACTUALIZACIÓN: Blindaje de ID alfanumérico, CSRF Token y restauración total.
 */

require_once 'includes/config.php';
include 'includes/colors.php';

// 1. OBTENCIÓN Y VALIDACIÓN DEL PRODUCTO
$id_url = $_GET['id'] ?? ''; 
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id_url]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$p) { 
    header("Location: productos.php?error=not_found");
    exit; 
}

include 'includes/header.php'; 

// 2. LÓGICA DE GALERÍA (Limpieza de duplicados y nulos)
$galeria = [];
if (!empty($p['imagen_url'])) $galeria[] = trim($p['imagen_url']);
if (!empty($p['imagenes_galeria'])) {
    $extras = explode(',', $p['imagenes_galeria']);
    foreach($extras as $img) {
        $img_limpia = trim($img);
        if(!empty($img_limpia)) $galeria[] = $img_limpia;
    }
}
$galeria = array_values(array_unique($galeria));
$json_galeria = json_encode($galeria);

// Obtener vídeos del producto
$video_delante = !empty($p['video_delante']) ? trim($p['video_delante']) : '';
$video_detras = !empty($p['video_detras']) ? trim($p['video_detras']) : '';
$video_como_se_hace = !empty($p['video_como_se_hace']) ? trim($p['video_como_se_hace']) : '';
$tiene_videos = !empty($video_delante) || !empty($video_detras) || !empty($video_como_se_hace);

// 3. LÓGICA DE CATEGORÍAS Y OPCIONES
$categoria = strtolower($p['categoria'] ?? '');
$opciones = [];
$titulo_opcion = "";
$msg_error_especifico = "una opción";
$es_ropa = false; 

if (strpos($categoria, 'camiseta') !== false) {
    $opciones = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];
    $titulo_opcion = "Selecciona tu Talla";
    $msg_error_especifico = "tu talla";
    $es_ropa = true;
    $talla_datos = [
        'S'   => ['altura' => '163–170 cm', 'ancho' => '48.5', 'largo' => '69.5'],
        'M'   => ['altura' => '170–176 cm', 'ancho' => '53.5', 'largo' => '72'],
        'L'   => ['altura' => '176–182 cm', 'ancho' => '56',   'largo' => '74.5'],
        'XL'  => ['altura' => '180–186 cm', 'ancho' => '61',   'largo' => '77'],
        'XXL' => ['altura' => '184–190 cm', 'ancho' => '66',   'largo' => '78.5'],
        '3XL' => ['altura' => '+185 cm',    'ancho' => '71',   'largo' => '80'],
        '4XL' => ['altura' => '+185 cm',    'ancho' => '76',   'largo' => '81.5'],
        '5XL' => ['altura' => '+185 cm',    'ancho' => '81',   'largo' => '83'],
    ];
} elseif (strpos($categoria, 'sudadera') !== false) {
    $opciones = ['S', 'M', 'L', 'XL', 'XXL'];
    $titulo_opcion = "Selecciona tu Talla";
    $msg_error_especifico = "tu talla";
    $es_ropa = true;
    $talla_datos = [
        'S'   => ['altura' => '163–170 cm', 'ancho' => '51',   'largo' => '67'],
        'M'   => ['altura' => '170–176 cm', 'ancho' => '56',   'largo' => '70'],
        'L'   => ['altura' => '176–182 cm', 'ancho' => '61',   'largo' => '73'],
        'XL'  => ['altura' => '180–186 cm', 'ancho' => '63.5', 'largo' => '76'],
        'XXL' => ['altura' => '184–190 cm', 'ancho' => '68.5', 'largo' => '79'],
    ];
} elseif (strpos($categoria, 'cuadro') !== false) {
    $opciones = ['Aluminio', 'Pizarra', 'Azulejo'];
    $precios_material = ['Aluminio' => 0, 'Pizarra' => 0, 'Azulejo' => -21];
    $titulo_opcion = "Selecciona el Material";
    $msg_error_especifico = "el material";
} elseif (strpos($categoria, 'taza') !== false) {
    $opciones = ['Cerámica Blanca 11oz'];
    $titulo_opcion = "Tipo de Taza";
    $msg_error_especifico = "el tipo de taza";
}
// 4. COLORES POR CATEGORÍA (desde mapa central)
$solo_sudadera = ['Amarillo','Ceniza','Rojo Ladrillo','Natural','Caqui','Lima','Heather Morado','Heather Burdeos'];
$aliases_color = ['Morado Jaspeado','Burdeos Jaspeado','Azul Vintage','Azul Vintage Jaspeado','Heather Royal','Heather Verde','Rojo Vintage'];
$colores = [];
if (strpos($categoria, 'camiseta') !== false) {
    $colores = array_diff_key($colores_hex, array_flip($aliases_color));
} elseif (strpos($categoria, 'sudadera') !== false) {
    $colores = array_diff_key($colores_hex, array_flip(array_merge($solo_sudadera, $aliases_color)));
}

?>

<style>
   /* ==========================================================================
   CAMIGLOBO - ESTILOS MAESTROS (CORE + PRODUCTO)
   Versión:  (Unificada y Revisada)
   ========================================================================== */

:root {
    /* TUS COLORES EXACTOS (Extraídos de tu código) */
    --primary-color: #2c3e50;    /* Tu Azul Marino */
    --accent-color: #e74c3c;     /* Tu Rojo exacto */
    --success-green-1: #27ae60;  /* Verde botón inicio */
    --success-green-2: #2ecc71;  /* Verde botón fin */
    
    /* Colores Base */
    --dark-color: #1a1a1a;
    --light-bg: #f2f2f2;
    --white: #ffffff;
    --gray-text: #555555;
    --border-color: #eee;
    
    /* Efectos */
    --shadow: 0 10px 30px rgba(0,0,0,0.02);
    --shadow-hover: 0 15px 40px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--dark-color);
    background-color: var(--light-bg);
}

.container {
    width: 100%;
    max-width: 1250px; /* Ajustado a tu preferencia */
    margin: 0 auto;
    padding: 0 25px;
}

/* ==========================================================================
   FICHA DE PRODUCTO (Tu código optimizado)
   ========================================================================== */

/* 1. LAYOUT PRINCIPAL */
.product-container { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 60px; 
    margin: 40px auto; 
    align-items: start; 
}

/* 2. GALERÍA Y ZOOM */
.main-img-wrap {
    border-radius: 25px;
    overflow: visible;
    border: 1px solid var(--border-color);
    cursor: zoom-in;
    background: var(--white);
    height: 490px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
    position: relative;
}

.main-img-wrap .nav-arrow-main {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s;
    font-size: 18px;
}
.main-img-wrap .nav-arrow-main:hover { background: #e74c3c; }
.main-img-wrap .nav-arrow-main.left { left: 12px; }
.main-img-wrap .nav-arrow-main.right { right: 12px; }

@media (max-width: 600px) {
    .main-img-wrap .nav-arrow-main { display: flex; width: 30px; height: 30px; font-size: 14px; }
    .main-img-wrap .nav-arrow-main.left { left: 8px; }
    .main-img-wrap .nav-arrow-main.right { right: 8px; }
}

.main-img-wrap img { 
    max-width: 90%; 
    max-height: 90%; 
    object-fit: contain; 
    transition: opacity 0.5s ease-in-out; 
    opacity: 0; 
}

.main-img-wrap img.loaded { opacity: 1; }

.thumbnails { 
    display: flex; 
    gap: 15px; 
    margin-top: 20px; 
    flex-wrap: wrap; 
    justify-content: center; 
}

.thumb-btn { 
    width: 80px; 
    height: 80px; 
    border: 2px solid var(--border-color); 
    border-radius: 12px; 
    cursor: pointer; 
    object-fit: contain; 
    background: var(--white); 
    padding: 5px; 
    transition: var(--transition); 
}

.thumb-btn.active, .thumb-btn:hover { 
    border-color: var(--accent-color); 
    transform: scale(1.1); 
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2); 
}

/* 3. DETALLES DEL PRODUCTO (Textos) */
.p-title { 
    font-weight: 900; 
    font-size: 2.3rem; 
    color: var(--dark-color); 
    margin-bottom: 5px; 
    letter-spacing: -1.5px; 
    line-height: 1.1; 
}

.p-price { 
    font-size: 42px; 
    color: var(--accent-color); 
    font-weight: 900; 
    margin-bottom: 10px; 
    display: block; 
    letter-spacing: -1px; 
}

.p-desc { 
    background: #fbfbfb; 
    padding: 35px; 
    border-radius: 25px; 
    margin-bottom: 23px; 
    color: var(--gray-text); 
    line-height: 1.8; 
    border-left: 8px solid var(--primary-color); 
    font-size: 16px; 
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.02); 
}

/* 4. OPCIONES Y BOTONES */
.admin-edit-badge {
    display: inline-flex; align-items: center; gap: 10px; 
    background: var(--accent-color); color: white !important; 
    padding: 12px 28px; border-radius: 50px; font-weight: 900; 
    font-size: 12px; text-decoration: none; margin-bottom: 23px; 
    transition: var(--transition); box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
    text-transform: uppercase; letter-spacing: 1px;
}
.admin-edit-badge:hover { background: #000; transform: translateY(-3px); }

.option-selector { display: flex; column-gap: 12px; row-gap: 35px; margin: 15px 0 35px; flex-wrap: wrap; align-items: flex-start; }
.option-radio { display: none; }

.option-label { 
    border: 2px solid var(--border-color); padding: 15px 25px; 
    border-radius: 15px; cursor: pointer; font-weight: 800; 
    transition: var(--transition); background: var(--white); 
    font-size: 14px; text-transform: uppercase; min-width: 60px; 
    text-align: center; 
}

.option-radio:checked + .option-label { 
    background: var(--primary-color); color: white; 
    border-color: var(--primary-color);
    box-shadow: 0 6px 18px rgba(44, 62, 80, 0.3); 
}

.size-label { position: relative; }
.size-label[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #2c3e50;
    color: #fff;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    z-index: 999;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    pointer-events: none;
    text-transform: none;
    letter-spacing: 0;
}
.size-label[data-tooltip]:hover::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #2c3e50;
    z-index: 999;
    pointer-events: none;
}

.buy-btn { 
    width: 100%; 
    background: linear-gradient(90deg, var(--success-green-1), var(--success-green-2)); 
    color: white; padding: 24px; border: none; border-radius: 50px; 
    font-weight: 900; font-size: 18px; cursor: pointer; 
    transition: 0.4s; box-shadow: 0 10px 30px rgba(39,174,96,0.3); 
    text-transform: uppercase; letter-spacing: 1px; 
    display: flex; align-items: center; justify-content: center; gap: 15px;
}
.buy-btn:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 40px rgba(39,174,96,0.4); 
    filter: brightness(1.1); 
}

/* Validación */
#validation-msg { 
    display: none; background: var(--accent-color); color: white; 
    padding: 20px; border-radius: 15px; font-weight: 800; 
    font-size: 14px; text-align: center; margin-bottom: 25px; 
    animation: shake 0.5s ease-in-out; text-transform: uppercase; 
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.2);
}
@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-8px); } 75% { transform: translateX(8px); } }

/* 5. TABLAS Y GUÍAS (Inferior) */
.guide-card { 
    margin-top: 80px; padding: 50px; background: var(--white); 
    border-radius: 30px; border: 1px solid var(--border-color); 
    box-shadow: var(--shadow); 
}
.guide-card h3 { 
    font-weight: 900; text-transform: uppercase; 
    margin-bottom: 30px; display: flex; align-items: center; gap: 12px; 
}

.tallas-table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 650px; }
.tallas-table th { background: var(--primary-color); color: white; padding: 12px 4px; font-weight: 800; font-size: 10px; text-align: center; }
.tallas-table td { padding: 12px 4px; text-align: center; border-bottom: 1px solid #f0f0f0; font-weight: 700; color: #333; font-size: 11px; }
.tallas-table tr:hover td { background: #fcfcfc; }
.tallas-table th:first-child, .tallas-table td:first-child { text-align: left; padding-left: 12px; width: 18%; }

/* 6. RELACIONADOS (Grid 5 columnas) - Mismo estilo que productos.php */
.related-grid, .productos-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

.rel-item, .producto-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee;
    padding: 0;
    text-align: center;
    transition: 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
}

.rel-item:hover, .producto-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    border-color: #27ae60;
}

/* Contenedor de imagen */
.rel-img-box, .producto-imagen-container {
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
}

.rel-img-box img, .producto-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Descripción en cards relacionados */
.rel-desc {
    font-size: 12px;
    color: #888;
    height: 36px;
    overflow: hidden;
    line-height: 1.4;
    margin-bottom: 8px;
}
/* --- BOTÓN VOLVER (Te faltaba esto) --- */
.back-store-container { text-align: center; margin: 60px 0 20px; }

.back-store-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 45px;
    background: #2c3e50;
    color: white !important;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 800;
    font-size: 14px;
    transition: 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.back-store-btn:hover {
    background: #000;
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}
/* Botón Volver */
.back-store-container { text-align: center; margin: 60px 0 20px; }

.back-store-btn {
    display: inline-flex; align-items: center; gap: 12px; 
    padding: 18px 45px; background: var(--primary-color); 
    color: white !important; text-decoration: none; 
    border-radius: 50px; font-weight: 800; font-size: 14px; 
    transition: var(--transition); text-transform: uppercase; 
    letter-spacing: 1px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.back-store-btn:hover { 
    background: #000; transform: translateY(-3px); 
    box-shadow: 0 15px 30px rgba(0,0,0,0.2); 
}

/* 7. MODALES (Zoom y Tallas) */
/* Zoom */
#zoom-modal { 
    display: none; position: fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0,0,0,0.97); z-index: 10000; 
    align-items: center; justify-content: center; backdrop-filter: blur(10px); 
}
#zoom-modal.active { display: flex; }

#zoom-img-container { 
    position: relative; cursor: grab; transition: transform 0.1s ease-out; 
    display: flex; align-items: center; justify-content: center; 
}
#zoom-img-el { 
    /* 1. Ancho: Dejamos un 5% a cada lado para que respiren las flechas */
    max-width: 90vw;   
    
    /* 2. ALTO INTELIGENTE: 
       Altura total (100vh) MENOS 120px (espacio para el texto y margen) */
    max-height: calc(100vh - 120px);  
    
    /* 3. Proporción y Estética */
    width: auto; 
    height: auto;
    object-fit: contain; 
    border-radius: 4px; 
    user-select: none; 
    box-shadow: 0 10px 40px rgba(0,0,0,0.5); 
}


/* --- Mejora de visibilidad de flechas --- */
.nav-arrow { 
    position: absolute; 
    top: 50%; 
    transform: translateY(-50%); 
    color: white; 
    font-size: 50px; 
    cursor: pointer; 
    z-index: 10001; 
    padding: 20px; 
    transition: 0.3s; 
    opacity: 0.9; /* Subido de 0.4 a 0.7 para que se vean claramente */
    user-select: none; 
    text-shadow: 0 0 15px rgba(0,0,0,0.5); /* Sombra para que resalten en fondos claros */
}
.nav-arrow:hover { 
    opacity: 1; 
    color: var(--accent-color); 
    transform: translateY(-50%) scale(1.2); 
}

/* --- Nueva X de cierre para el Zoom --- */

.close-zoom {
    position: absolute;
    top: 90px; /* Ajustado para que respire bien */
    right: 40px;
    z-index: 200005; /* Siempre encima de todo */
    
    /* Tipografía */
    color: white;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; /* Fuente limpia */
    font-size: 80px; /* Grande para que se vea fina */
    font-weight: 100; /* SUPER FINO (Light) */
    line-height: 0.5; /* Centrado vertical del símbolo */
    
    /* El truco de la X */
    transform: rotate(45deg); /* Giramos el + para que sea una X */
    transform-origin: center center;
    
    /* Interacción */
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1); /* Animación muy suave */
    user-select: none;
    
    /* Sombra suave para que se vea sobre fotos blancas */
    text-shadow: 0 2px 10px rgba(0,0,0,0.1); 
    opacity: 0.8;
}

.close-zoom:hover {
    color: var(--accent-color); /* Se pone rojo */
    transform: rotate(135deg) scale(1.1); /* Gira 90 grados más y crece un pelín */
    opacity: 1;
    text-shadow: 0 5px 20px rgba(0,0,0,0.4);
}

.arrow-left { left: 20px; }
.arrow-right { right: 20px; }

/* Modal de Vídeo */
#video-modal {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.95); z-index: 10000;
    align-items: center; justify-content: center; flex-direction: column;
}
#video-modal.active { display: flex; }
#video-modal video {
    max-width: 90vw; max-height: 80vh; border-radius: 10px;
}
#video-modal .video-title {
    color: white; font-size: 18px; font-weight: 700; margin-top: 20px;
}

.zoom-help { 
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); 
    color: white; background: rgba(255,255,255,0.1); padding: 12px 30px; 
    border-radius: 50px; font-size: 12px; font-weight: bold; 
    text-transform: uppercase; letter-spacing: 1px; pointer-events: none; 
    border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(5px); 
}

/* Tallas */
.custom-modal-overlay { 
    position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
    background: rgba(44, 62, 80, 0.6); z-index: 10000; 
    display: flex; align-items: center; justify-content: center; 
    backdrop-filter: blur(4px); 
}
.custom-modal-box {
    background: #fff; padding: 25px; border-radius: 20px;
    width: 90%; max-width: 420px; max-height: 80vh; position: relative;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: popModal 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    overflow-y: auto; overflow-x: hidden;
}
@keyframes popModal { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

.close-modal-btn { 
    position: absolute; top: 15px; right: 15px; background: #f8f9fa; 
    border: none; width: 32px; height: 32px; border-radius: 50%; 
    cursor: pointer; color: var(--accent-color); font-size: 16px; 
    transition: 0.2s; display: flex; align-items: center; justify-content: center;
}
.close-modal-btn:hover { background: var(--accent-color); color: #fff; transform: rotate(90deg); }

.custom-modal-box h3 { margin-top: 0; color: var(--primary-color); font-weight: 900; text-transform: uppercase; font-size: 18px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px; }
.custom-modal-box p { font-size: 12px; color: #7f8c8d; margin-bottom: 20px; font-weight: 600; }

.modal-size-table { width: 100%; border-collapse: collapse; text-align: center; margin-bottom: 10px; }
.modal-size-table th { background: #2c3e50; color: #fff; padding: 8px; font-size: 11px; text-transform: uppercase; font-weight: 800; border-radius: 4px; }
.modal-size-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; font-size: 12px; color: #555; }
.modal-size-table tr:last-child td { border-bottom: none; }

/* 8. SELECTOR DE COLOR */
.color-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 15px 0 30px;
}

.color-swatch {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid transparent;
    transition: var(--transition);
    position: relative;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    flex-shrink: 0;
}

.color-swatch:hover { transform: scale(1.2); }

.color-swatch.selected {
    border-color: var(--primary-color);
    transform: scale(1.2);
    box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary-color);
}

.color-swatch[data-nombre="Blanco"],
.color-swatch[data-nombre="Natural"] {
    border-color: #ddd;
}

.color-tooltip {
    display: none;
    position: absolute;
    bottom: 42px;
    left: 50%;
    transform: translateX(-50%);
    background: #1a1a1a;
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    white-space: nowrap;
    z-index: 100;
    pointer-events: none;
}

.color-swatch:hover .color-tooltip { display: block; }

.color-selected-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
    min-height: 20px;
}

/* 9. MEDIA QUERIES (Tus ajustes específicos) */
@media (max-width: 950px) { 
    .product-container { grid-template-columns: 1fr; } 
    .p-title { font-size: 2.2rem; } 
    .related-grid { grid-template-columns: repeat(3, 1fr); } 
}

@media (max-width: 768px) {
    .product-container { gap: 30px; margin: 20px auto; }
    .p-title { font-size: 1.8rem; letter-spacing: -0.5px; }
    .p-price { font-size: 32px; }
    .p-desc { padding: 20px; font-size: 14px; }
    .main-img-wrap { height: 320px; overflow: hidden; }
    .guide-card { padding: 25px 15px; margin-top: 40px; }
    .tallas-table { font-size: 10px; }
    .tallas-table th, .tallas-table td { padding: 8px 3px; }
    .related-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .custom-modal-box { padding: 18px; max-height: 75vh; margin-top: 60px; }
    .custom-modal-overlay { align-items: flex-start; padding-top: 80px; }
}

@media (max-width: 600px) { 
    .related-grid { grid-template-columns: repeat(2, 1fr); }
    .p-title { font-size: 1.5rem; }
    .p-price { font-size: 28px; }
    .buy-btn { font-size: 15px; padding: 18px; }
    .option-label { padding: 10px 14px; font-size: 13px; }
    .thumbnails { gap: 8px; }
    .thumb-btn { width: 60px; height: 60px; }
    .color-swatch { width: 28px; height: 28px; }
}

@media (max-width: 480px) {
    .p-title { font-size: 1.3rem; }
    .p-price { font-size: 24px; }
    .container { padding: 0 12px; }
    .related-grid { gap: 8px; }
}

/* Características técnicas */
.specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}
.specs-full {
    grid-column: span 2;
}
@media (max-width: 600px) {
    .specs-grid {
        grid-template-columns: 1fr;
    }
    .specs-full {
        grid-column: span 1;
    }
}

/* Sección ¿Quieres este diseño en otro producto? */
.custom-promo-mobile { display: none; }
.custom-promo-desktop { display: block; }
@media (max-width: 950px) {
    .custom-promo-mobile { display: block; }
    .custom-promo-desktop { display: none; }
}
</style>

<div class="container" style="max-width: 1250px; margin: auto; padding: 0 25px;">

    <div class="product-container">
        <div class="image-gallery">
            <!-- Main image -->
            <div class="main-img-wrap" id="main-media-wrap" onclick="openZoom()">
                <div class="nav-arrow-main left" onclick="event.stopPropagation(); navigateMain(-1)"><i class="fas fa-chevron-left"></i></div>
                <div class="nav-arrow-main right" onclick="event.stopPropagation(); navigateMain(1)"><i class="fas fa-chevron-right"></i></div>
                <img id="current-img"
                     src="<?php echo htmlspecialchars($galeria[0] ?? 'images/placeholder.png'); ?>"
                     alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                     width="550"
                     height="550"
                     fetchpriority="high"
                     onload="this.classList.add('loaded')"
                     onerror="this.src='images/placeholder.png'; this.classList.add('loaded');">
            </div>

            <div class="thumbnails">
                <?php foreach($galeria as $index => $url): ?>
                    <img src="<?php echo htmlspecialchars($url); ?>"
     class="thumb-btn <?php echo ($index === 0) ? 'active' : ''; ?>"
     width="80"
     height="80"
     loading="lazy"
     onclick="changeView('<?php echo addslashes($url); ?>', this, <?php echo $index; ?>)">
                <?php endforeach; ?>

                <!-- Miniaturas de vídeo -->
                <?php if (!empty($video_delante)): ?>
    <div class="video-thumb" onclick="changeView('<?php echo addslashes($video_delante); ?>', this, 'video_delante')" style="width:80px; height:80px; border-radius:12px; overflow:hidden; position:relative; cursor:pointer; border: 2px solid var(--border-color); background: #000; padding: 0;">
        <video src="<?php echo htmlspecialchars($video_delante); ?>#t=0.1" style="width:100%; height:100%; object-fit:cover;" muted playsinline preload="metadata"></video>
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); display:flex; flex-direction:column; align-items:center; justify-content:center; transition:0.3s;">
            <i class="fas fa-play" style="color:white; font-size:20px; margin-bottom:4px;"></i>
            <span style="color:white; font-size:9px; font-weight:800; text-align:center; letter-spacing:0.5px;">DELANTE</span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($video_detras)): ?>
    <div class="video-thumb" onclick="changeView('<?php echo addslashes($video_detras); ?>', this, 'video_detras')" style="width:80px; height:80px; border-radius:12px; overflow:hidden; position:relative; cursor:pointer; border: 2px solid var(--border-color); background: #000; padding: 0;">
        <video src="<?php echo htmlspecialchars($video_detras); ?>#t=0.1" style="width:100%; height:100%; object-fit:cover;" muted playsinline preload="metadata"></video>
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); display:flex; flex-direction:column; align-items:center; justify-content:center; transition:0.3s;">
            <i class="fas fa-play" style="color:white; font-size:20px; margin-bottom:4px;"></i>
            <span style="color:white; font-size:9px; font-weight:800; text-align:center; letter-spacing:0.5px;">DETRÁS</span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($video_como_se_hace)): ?>
    <div class="video-thumb" onclick="changeView('<?php echo addslashes($video_como_se_hace); ?>', this, 'video_como_se_hace')" style="width:80px; height:80px; border-radius:12px; overflow:hidden; position:relative; cursor:pointer; border: 2px solid var(--border-color); background: #000; padding: 0;">
        <video src="<?php echo htmlspecialchars($video_como_se_hace); ?>#t=0.1" style="width:100%; height:100%; object-fit:cover;" muted playsinline preload="metadata"></video>
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); display:flex; flex-direction:column; align-items:center; justify-content:center; transition:0.3s;">
            <i class="fas fa-play" style="color:white; font-size:20px; margin-bottom:4px;"></i>
            <span style="color:white; font-size:8px; font-weight:800; text-align:center; letter-spacing:0.5px;">CÓMO SE HACE</span>
        </div>
    </div>
<?php endif; ?>
            </div>

            <!-- Aviso personalización -->
            <div class="custom-promo custom-promo-desktop" style="margin-top:18px; background:linear-gradient(135deg, #f8f9fa 0%, #fff5f5 100%); border-radius:16px; padding:16px 18px; border-left:4px solid #e74c3c;">
                <p style="margin:0 0 6px 0; font-size:13px; font-weight:800; color:#2c3e50;">🎨 ¿Quieres este diseño en otro producto?</p>
                <p style="margin:0 0 12px 0; font-size:12px; color:#666; line-height:1.6;">Podemos poner <strong>cualquier diseño en cualquier prenda</strong> — camiseta, sudadera, taza o cuadro. Solo pídenos lo que necesitas y lo hacemos realidad.</p>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="https://wa.me/34653851786?text=Hola!%20Me%20interesa%20personalizar%20un%20producto%20con%20un%20diseño%20concreto" 
                       target="_blank"
                       style="display:inline-flex; align-items:center; gap:7px; background:#25D366; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;"
                       onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                        <i class="fab fa-whatsapp" style="font-size:15px;"></i> WhatsApp
                    </a>
                    <a href="mailto:camigloboshop@gmail.com?subject=Quiero%20personalizar%20un%20producto"
                       style="display:inline-flex; align-items:center; gap:7px; background:#2c3e50; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;"
                       onmouseover="this.style.filter='brightness(1.3)'" onmouseout="this.style.filter='none'">
                        <i class="fas fa-envelope" style="font-size:13px;"></i> Email
                    </a>
                    <a href="tel:+34653851786"
                       style="display:inline-flex; align-items:center; gap:7px; background:#e74c3c; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;"
                       onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                        <i class="fas fa-phone" style="font-size:13px;"></i> 653 851 786
                    </a>
                </div>
            </div>
        </div>

        <div class="product-details">
            <?php if (function_exists('esAdmin') && esAdmin()): ?>
                <a href="admin_productos.php?id=<?php echo urlencode($p['id']); ?>" class="admin-edit-badge"><i class="fas fa-magic"></i> EDITAR ESTE PRODUCTO</a>
            <?php endif; ?>

            <h1 class="p-title"><?php echo htmlspecialchars($p['nombre']); ?></h1>
            <span class="p-price" id="product-price" data-base="<?php echo $p['precio']; ?>"><?php echo number_format($p['precio'], 2, ',', '.'); ?> €</span>

            <!-- Badge envío gratis -->
            <div style="display:inline-flex; align-items:center; gap:8px; background:#e8f5e9; color:#2e7d32; padding:8px 16px; border-radius:50px; font-size:13px; font-weight:700; margin:10px 0 5px;">
                <i class="fas fa-truck" style="font-size:14px;"></i> Envío gratis a partir de 45&euro;
            </div>
            
            <div class="p-desc"><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></div>

            <form action="carrito_accion.php" method="POST" id="product-form">
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                
                <?php if ($titulo_opcion): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-weight: 900; font-size: 12px; text-transform: uppercase; color: #95a5a6; letter-spacing: 1.5px;"><?php echo $titulo_opcion; ?>:</label>
                        
                        <?php if ($es_ropa): ?>
                            <span style="font-size:11px; color:#3498db; cursor:pointer; font-weight:800; background:#ebf5fb; padding:4px 8px; border-radius:6px; transition:0.2s;" onmouseover="this.style.background='#d6eaf8'" onmouseout="this.style.background='#ebf5fb'" onclick="document.getElementById('size-guide-modal').style.display='flex'; showProductGuide('<?php echo strpos($categoria, 'sudadera') !== false ? 'sudadera' : 'camiseta'; ?>')">
                                <i class="fas fa-ruler"></i> Ver Guía
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="option-selector">
    <?php foreach($opciones as $opt): ?>
        <label class="size-label" <?php if (isset($talla_datos[$opt])): ?>data-tooltip="Altura: <?php echo $talla_datos[$opt]['altura']; ?> | Ancho: <?php echo $talla_datos[$opt]['ancho']; ?> cm | Largo: <?php echo $talla_datos[$opt]['largo']; ?> cm"<?php endif; ?>>
            <input type="radio" name="talla" value="<?php echo htmlspecialchars($opt); ?>" class="option-radio" onchange="hideValMsg()" <?php echo (($es_ropa && $opt === 'L') || (!$es_ropa && $opt === $opciones[0])) ? 'checked' : ''; ?>>
            <span class="option-label"><?php echo htmlspecialchars($opt); ?><?php if (!$es_ropa && isset($precios_material[$opt])): ?> <small style="opacity:0.7">(<?php echo number_format($p['precio'] + $precios_material[$opt], 2); ?>€)</small><?php endif; ?></span>
        </label>
    <?php endforeach; ?>
</div>
                <?php endif; ?>

                <?php if (!empty($colores)): ?>
                <input type="hidden" name="color" id="color-seleccionado" value="<?php echo isset($colores['Negro']) ? 'Negro' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="font-weight: 900; font-size: 12px; text-transform: uppercase; color: #95a5a6; letter-spacing: 1.5px;">Color de la prenda:</label>
                    <span class="color-selected-name" id="color-nombre-display"><?php echo isset($colores['Negro']) ? 'Negro' : '— Elige un color —'; ?></span>
                </div>
                <div class="color-selector">
                    <?php foreach ($colores as $nombre => $hex): ?>
                        <div class="color-swatch <?php echo $nombre === 'Negro' ? 'selected' : ''; ?>"
                             data-nombre="<?php echo htmlspecialchars($nombre); ?>"
                             style="background-color: <?php echo $hex; ?>;"
                             onclick="seleccionarColor('<?php echo htmlspecialchars($nombre, ENT_QUOTES); ?>', this)">
                            <span class="color-tooltip"><?php echo htmlspecialchars($nombre); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 40px; display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid #f0f0f0; width: fit-content;">
                    <span style="font-weight: 900; font-size: 12px; text-transform: uppercase; color: #95a5a6;">Cantidad:</span>
                    <input type="number" name="cantidad" value="1" min="1" max="99" style="padding: 10px; width: 70px; border: none; outline:none; text-align: center; font-weight: 900; font-size: 18px; color: #2c3e50;">
                </div>

                <div id="validation-msg">
                    <i class="fas fa-hand-point-up" style="margin-right: 10px;"></i> 
                    Atención: Selecciona <?php echo $msg_error_especifico; ?>
                </div>

<button type="submit" class="buy-btn" id="btn-add" aria-label="Añadir <?php echo htmlspecialchars($p['nombre']); ?> al carrito">
                        <i class="fas fa-cart-plus"></i> AÑADIR AL CARRITO
                </button>
            </form>

            <!-- Trust icons -->
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:8px; background:#f8f9fa; padding:10px 16px; border-radius:12px; flex:1; min-width:140px;">
                    <i class="fas fa-palette" style="font-size:18px; color:#e74c3c;"></i>
                    <div>
                        <div style="font-weight:800; font-size:12px; color:#2c3e50; line-height:1.3;">Producción</div>
                        <div style="font-size:11px; color:#7f8c8d;">2-3 días laborables</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; background:#f8f9fa; padding:10px 16px; border-radius:12px; flex:1; min-width:140px;">
                    <i class="fas fa-shipping-fast" style="font-size:18px; color:#3498db;"></i>
                    <div>
                        <div style="font-weight:800; font-size:12px; color:#2c3e50; line-height:1.3;">Envío</div>
                        <div style="font-size:11px; color:#7f8c8d;">24-72h tras producción</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; background:#f8f9fa; padding:10px 16px; border-radius:12px; flex:1; min-width:140px;">
                    <i class="fas fa-award" style="font-size:18px; color:#f39c12;"></i>
                    <div>
                        <div style="font-weight:800; font-size:12px; color:#2c3e50; line-height:1.3;">+10.000</div>
                        <div style="font-size:11px; color:#7f8c8d;">Prendas entregadas</div>
                    </div>
                </div>
            </div>

            <!-- Aviso personalización (solo móvil, debajo del carrito) -->
            <div class="custom-promo custom-promo-mobile" style="margin-top:18px; background:linear-gradient(135deg, #f8f9fa 0%, #fff5f5 100%); border-radius:16px; padding:16px 18px; border-left:4px solid #e74c3c;">
                <p style="margin:0 0 6px 0; font-size:13px; font-weight:800; color:#2c3e50;">🎨 ¿Quieres este diseño en otro producto?</p>
                <p style="margin:0 0 12px 0; font-size:12px; color:#666; line-height:1.6;">Podemos poner <strong>cualquier diseño en cualquier prenda</strong> — camiseta, sudadera, taza o cuadro. Solo pídenos lo que necesitas y lo hacemos realidad.</p>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="https://wa.me/34653851786?text=Hola!%20Me%20interesa%20personalizar%20un%20producto%20con%20un%20diseño%20concreto" 
                       target="_blank"
                       style="display:inline-flex; align-items:center; gap:7px; background:#25D366; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;">
                        <i class="fab fa-whatsapp" style="font-size:15px;"></i> WhatsApp
                    </a>
                    <a href="mailto:camigloboshop@gmail.com?subject=Quiero%20personalizar%20un%20producto"
                       style="display:inline-flex; align-items:center; gap:7px; background:#2c3e50; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;">
                        <i class="fas fa-envelope" style="font-size:13px;"></i> Email
                    </a>
                    <a href="tel:+34653851786"
                       style="display:inline-flex; align-items:center; gap:7px; background:#e74c3c; color:white; padding:9px 16px; border-radius:50px; font-size:12px; font-weight:800; text-decoration:none; transition:0.3s;">
                        <i class="fas fa-phone" style="font-size:13px;"></i> 653 851 786
                    </a>
                </div>
            </div>
            </div>
    </div>

<section class="guide-card">
        <?php if (strpos($categoria, 'taza') !== false): ?>
            <h3><i class="fas fa-mug-hot" style="color:#e74c3c;"></i> Especificaciones Técnicas</h3>
            <table class="tallas-table">
                <tbody>
                    <tr><td>Capacidad</td><td>325 ml (11 oz)</td></tr>
                    <tr><td>Material</td><td>Cerámica Premium AAA+ de alto brillo</td></tr>
                    <tr><td>Resistencia</td><td>Apta para lavavajillas y microondas</td></tr>
                </tbody>
            </table>
        <?php else: ?>
            <h3><i class="fas fa-star" style="color:#e74c3c;"></i> Calidad Camiglobo</h3>
            <p style="color:#666; line-height: 1.8;">Utilizamos técnicas de impresión de última generación — DTF de alta definición, sublimación, SubliFlock y vinilo textil de corte — para garantizar que los colores permanezcan vivos tras decenas de lavados. Seleccionamos la técnica ideal según el material y el diseño para el mejor resultado posible.</p>
        <?php endif; ?>
    </section>

</div>

<section style="background: #fff; padding: 60px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-top: 60px;">
    <div class="container" style="max-width: 1250px; margin: auto; padding: 0 25px;">
        
        <h2 style="text-align: center; font-weight: 900; text-transform: uppercase; margin-bottom: 50px; font-size: 1.8rem; color: #2c3e50;">También te puede interesar</h2>
        
        <div class="related-grid">
            <?php
            $limite_rel = esMovil() ? 26 : 25;
            $stmtR = $pdo->prepare("SELECT id, nombre, precio, imagen_url, descripcion FROM productos WHERE categoria = ? AND id != ? ORDER BY RAND() LIMIT $limite_rel");
            $stmtR->execute([$p['categoria'], $p['id']]);
            while($rel = $stmtR->fetch()):
                $img_rel = str_replace('http://', 'https://', $rel['imagen_url']);
            ?>
                <div class="rel-item">
                    <a href="producto.php?id=<?php echo urlencode($rel['id']); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column;">
                        <div class="rel-img-box">
                            <img src="<?php echo htmlspecialchars($img_rel); ?>"
                                 class="producto-imagen"
                                 alt="<?php echo htmlspecialchars($rel['nombre']); ?>"
                                 loading="lazy"
                                 onerror="this.src='https://www.camiglobo.com/images/camiglobofavicon.jpg';">
                        </div>
                        <div style="padding: 15px 15px 18px;">
                            <h3 class="producto-titulo" style="font-size: 15px; height: 40px; overflow: hidden; margin-bottom: 8px; color: #2c3e50; font-weight: 700; line-height: 1.3;"><?php echo h($rel['nombre']); ?></h3>
                            <?php if(!empty($rel['descripcion'])): ?>
                                <p class="rel-desc"><?php echo mb_strimwidth(strip_tags($rel['descripcion']), 0, 80, "..."); ?></p>
                            <?php endif; ?>
                            <p class="producto-precio" style="color: #e74c3c; font-weight: 800; font-size: 20px; margin-bottom: 12px;"><?php echo number_format($rel['precio'], 2, ',', '.'); ?> €</p>
                            <span class="btn-detalles" style="background: #000; color: white; padding: 10px 20px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block;">VER DETALLES</span>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="back-store-container">
            <a href="productos.php" class="back-store-btn">
                <i class="fas fa-arrow-left"></i> VOLVER A LA TIENDA
            </a>
        </div>

    </div>
</section>
</div>

<!-- ==================== SECCIONES ADICIONALES ==================== -->

<style>
.review-card {
    background:#f9f9f9; border-radius:20px; padding:25px; border:1px solid #eee; box-shadow:0 4px 15px rgba(0,0,0,0.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.review-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    border-color: #f1c40f;
}
.destacados-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
.dest-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee;
    overflow: hidden;
    transition: 0.3s;
    position: relative;
    text-align: center;
}
.dest-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    border-color: #27ae60;
}
.dest-card a {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.dest-img-wrap {
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
}
.dest-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.dest-info {
    padding: 15px 15px 18px;
}
.dest-titulo {
    font-size: 15px;
    height: 40px;
    overflow: hidden;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 700;
    line-height: 1.3;
}
.dest-desc {
    font-size: 12px;
    color: #888;
    height: 36px;
    overflow: hidden;
    line-height: 1.4;
    margin-bottom: 8px;
}
.dest-precio {
    color: #e74c3c;
    font-weight: 800;
    font-size: 20px;
    margin-bottom: 12px;
}
.dest-btn {
    background: #000;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
}
@media (max-width: 1100px) { .destacados-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .destacados-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; } }
@media (max-width: 480px)  { .destacados-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; } }
</style>

<!-- 1. Nuestras Joyas (Destacados) -->
<section class="container" style="margin-top: 60px; margin-bottom: 40px;">
    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px; font-weight: 800;">Nuestras Joyas (Destacados)</h2>
    <div class="destacados-grid">
        <?php
        $stmtDest = $pdo->query("SELECT id, nombre, precio, imagen_url, descripcion FROM productos WHERE destacado = 1 ORDER BY id DESC LIMIT 10");
        while($dest = $stmtDest->fetch()):
            $img_dest = str_replace('http://', 'https://', $dest['imagen_url']);
        ?>
        <div class="dest-card">
            <a href="producto.php?id=<?php echo urlencode($dest['id']); ?>">
                <div class="dest-img-wrap">
                    <img src="<?php echo htmlspecialchars($img_dest); ?>"
                         alt="<?php echo h($dest['nombre']); ?>"
                         loading="lazy"
                         onerror="this.src='https://www.camiglobo.com/images/camiglobofavicon.jpg';">
                </div>
                <div class="dest-info">
                    <h3 class="dest-titulo"><?php echo h($dest['nombre']); ?></h3>
                    <?php if(!empty($dest['descripcion'])): ?>
                        <p class="dest-desc"><?php echo mb_strimwidth(strip_tags($dest['descripcion']), 0, 80, "..."); ?></p>
                    <?php endif; ?>
                    <p class="dest-precio"><?php echo number_format($dest['precio'], 2, ',', '.'); ?> €</p>
                    <span class="dest-btn">VER DETALLES</span>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- 2. Crea tu prenda en 4 pasos -->
<section style="background: #fff; padding: 60px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-weight: 800;">Crea tu prenda en 4 pasos</h2>
        <p style="text-align: center; color: #666; margin-bottom: 40px; font-size: 1.1rem;">100% online, sin complicaciones. En minutos tienes tu diseño listo.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">👕</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">1. ELIGE PRODUCTO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">Camiseta, Sudadera, Taza o Cuadro. Elige el color de la prenda y la talla. En ropa puedes diseñar también la <strong>nuca, manga izquierda y manga derecha</strong> como zonas independientes. El precio se actualiza en tiempo real.</p>
            </div>
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">📸</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">2. SUBE TU IMAGEN</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">JPG, PNG, WEBP o HEIC (iPhone). Ajusta el tamaño, rotación y opacidad. Aplica filtros profesionales (Sepia, Kodak, B&N…) o añade stickers y emojis directamente al lienzo.</p>
            </div>
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">✏️</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">3. PERSONALIZA EL TEXTO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">20 tipografías y 16 efectos: neón, dorado, fuego, glitch, arcoíris… Ajusta tamaño, color, espaciado y alineación. Usa plantillas listas o parte de cero.</p>
            </div>
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">🛒</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">4. PIDE TU DISEÑO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">Previsualiza el resultado en alta calidad, añade notas si necesitas algún ajuste y añade al carrito. Enviamos a toda España, o recoge gratis en nuestro taller de Barcelona.</p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="personalizar.php" style="display: inline-block; background: #ff6b6b; color: white; padding: 18px 50px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 1.3rem; box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4); transition: all 0.3s ease; border: 2px solid #ff6b6b;" onmouseover="this.style.background='white'; this.style.color='#ff6b6b'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#ff6b6b'; this.style.color='white'; this.style.transform='scale(1)'">
                🚀 EMPEZAR A PERSONALIZAR
            </a>
        </div>
    </div>
</section>

<!-- 3. ¿Dónde estamos? -->
<section style="padding: 80px 0;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px; font-weight: 800;">¿Dónde estamos?</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; align-items: start;">
            <div style="padding: 20px;">
                <h3 style="color: #ff6b6b; margin-bottom: 25px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Visítanos en nuestro taller de Barcelona</h3>
                <p style="font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 25px;">
                    <strong>Camiglobo</strong> es mucho más que una tienda online; somos un taller artesanal de personalización textil ubicado en Barcelona. Cada pedido se imprime con mimo y atención al detalle en nuestras propias instalaciones.
                </p>
                <p style="font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 25px;">
                    ¿Estás cerca? Ahorra tiempo y costes de envío seleccionando <strong>"Recogida en Local"</strong>. Podrás recoger tu pedido personalmente, ver nuestras muestras físicas de telas y comprobar la calidad de nuestras impresiones. ¡Estaremos encantados de asesorarte!
                </p>
                <div style="background: #f9f9f9; padding: 25px; border-radius: 15px; border-left: 5px solid #ff6b6b;">
                    <p style="font-size: 17px; color: #2c3e50; font-weight: 700; margin-bottom: 15px;">
                        <a href="https://www.google.com/maps/search/?api=1&query=Camiglobo+Barcelona+Calle+Doctor+Bove+115" target="_blank" style="text-decoration: none; color: inherit; transition: 0.3s;" onmouseover="this.style.color='#ff6b6b'">
                            <i class="fas fa-map-marker-alt" style="color: #ff6b6b; margin-right: 12px;"></i> Calle Doctor Bové 115, 08032 Barcelona
                        </a>
                    </p>
                    <p style="font-size: 17px; color: #2c3e50; font-weight: 700; margin-bottom: 20px;">
                        <i class="fas fa-phone-alt" style="color: #ff6b6b; margin-right: 12px;"></i> +34 653 851 786
                    </p>
                    <a href="contacto.php" style="display: inline-flex; align-items: center; gap: 8px; background: #2c3e50; color: white; padding: 12px 28px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.3s;" onmouseover="this.style.background='#ff6b6b'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#2c3e50'; this.style.transform='translateY(0)'">
                        <i class="fas fa-headset"></i> CONTÁCTANOS
                    </a>
                </div>
            </div>
            <div style="width: 100%; height: 450px; border-radius: 20px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                <iframe title="Mapa de ubicación de nuestra tienda Camiglobo en Barcelona" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2991.64!2d2.159!3d41.425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12a4980998f86915%3A0x633454b8a2e76f5!2sCarrer+del+Doctor+Bov%C3%A9%2C+115%2C+08032+Barcelona!5e0!3m2!1ses!2ses!4v1708000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- 4. El Taller en Vivo -->
<section style="padding: 80px 0; background: #f2f2f2; border-top: 1px solid #ddd;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-weight: 800;">El Taller en Vivo</h2>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 50px;">Mira nuestro proceso creativo en <a href="https://www.instagram.com/camiglobo/" target="_blank" style="color:#e74c3c; font-weight:bold; text-decoration:none;">@camiglobo</a></p>
        
        <div style="display: flex; gap: 20px; justify-content: center; max-width: 1400px; margin: 0 auto; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/video_35680617cf358360_1773949204.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Nuestro proceso creativo</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768094939.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Personalización para la Guardia Urbana de Barcelona</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768415046.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Camiseta Stranger Things — viral en TikTok</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_1775768996758.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Vinilo textil con plotter Siser Romeo</p>
            </div>
        </div>
    </div>
</section>

<!-- 5. Lo que dicen nuestros clientes -->
<section style="padding: 70px 0; background: #fff; border-top: 1px solid #eee;">
    <div class="container">
        <h2 style="text-align:center; color:#2c3e50; margin-bottom:8px; font-weight:800;">Lo que dicen nuestros clientes</h2>
        <p style="text-align:center; color:#888; margin-bottom:40px; font-size:1rem;">Más de 10.000 prendas entregadas y más de 500 pedidos personalizados y contando ⭐</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">
            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Encargué una camiseta con la foto de mi perro y quedó espectacular. La calidad de impresión es brutal, los colores muy vivos. Repetiré seguro."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#e74c3c,#ff6b6b); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">M</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">María González</div>
                        <div style="font-size:11px; color:#aaa;">Barcelona</div>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Pedí 10 sudaderas para el equipo con nuestro logo. Trato muy cercano, me asesoraron en todo y el resultado fue perfecto. Los chicos lo hacen genial."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#27ae60,#2ecc71); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">J</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Jordi Puigdomènech</div>
                        <div style="font-size:11px; color:#aaa;">Sabadell</div>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"El editor online es súper fácil de usar, lo hice todo desde el móvil en 10 minutos. En 3 días tenía la camiseta en casa. Muy recomendable."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#3498db,#74b9ff); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">L</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Laura Sánchez</div>
                        <div style="font-size:11px; color:#aaa;">Madrid</div>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Compré una taza personalizada como regalo de cumple y mi madre flipó. Muy buena calidad, el diseño se ve nítido y no se borra al lavar. 100% recomiendo."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#9b59b6,#8e44ad); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">A</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Andrés Romero</div>
                        <div style="font-size:11px; color:#aaa;">Badalona</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="zoom-modal">
    <div class="close-zoom" onclick="closeZoom()">+</div>

    <div class="nav-arrow arrow-left" onclick="navigateZoom(-1)"><i class="fas fa-chevron-left"></i></div>
    <div class="nav-arrow arrow-right" onclick="navigateZoom(1)"><i class="fas fa-chevron-right"></i></div>
    <div id="zoom-img-container"><img id="zoom-img-el" src=""></div>
    <div class="zoom-help"><i class="fas fa-search-plus"></i> Usa la rueda o pellizca para ampliar</div>
</div>

<!-- Modal de Vídeo -->
<div id="video-modal" onclick="if(event.target === this) closeVideoModal()">
    <div class="close-zoom" onclick="closeVideoModal()">+</div>
    <video id="video-modal-player" controls autoplay>
        <source src="" type="video/mp4">
        Tu navegador no soporta la reproducción de vídeos.
    </video>
    <div class="video-title" id="video-modal-title"></div>
</div>

<div id="size-guide-modal" class="custom-modal-overlay" style="display:none;" onclick="if(event.target === this) this.style.display='none'">
    <div class="custom-modal-box">
        <button class="close-modal-btn" onclick="document.getElementById('size-guide-modal').style.display='none'">
            <i class="fas fa-times"></i>
        </button>

        <!-- Selector de tipo -->
        <div style="display:flex; gap:8px; margin-bottom:15px;">
            <button onclick="showProductGuide('camiseta')" id="btn-guide-camiseta" style="flex:1; padding:8px 5px; background:#e74c3c; color:white; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:12px;">👕 Camiseta</button>
            <button onclick="showProductGuide('sudadera')" id="btn-guide-sudadera" style="flex:1; padding:8px 5px; background:#ecf0f1; color:#666; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:12px;">🧥 Sudadera</button>
        </div>

        <!-- Guía Camiseta -->
        <div id="guide-camiseta-content">
            <h4 style="color:#e74c3c; margin:0 0 5px 0; font-size:14px;">👕 Camiseta Valueweight T</h4>
            <p style="color:#666; font-size:11px; margin:0 0 10px 0;">Ref: 61-036-0 | Fruit of the Loom</p>
            <table class="modal-size-table" style="font-size:11px;">
                <tr><th>Talla</th><th>Altura</th><th>Ancho</th><th>Largo</th></tr>
                <tr><td>S</td><td>163–170 cm</td><td>48.5 cm</td><td>69.5 cm</td></tr>
                <tr><td>M</td><td>170–176 cm</td><td>53.5 cm</td><td>72 cm</td></tr>
                <tr><td>L</td><td>176–182 cm</td><td>56 cm</td><td>74.5 cm</td></tr>
                <tr><td>XL</td><td>180–186 cm</td><td>61 cm</td><td>77 cm</td></tr>
                <tr><td>XXL</td><td>184–190 cm</td><td>66 cm</td><td>78.5 cm</td></tr>
                <tr><td>3XL</td><td>+185 cm</td><td>71 cm</td><td>80 cm</td></tr>
                <tr><td>4XL</td><td>+185 cm</td><td>76 cm</td><td>81.5 cm</td></tr>
                <tr><td>5XL</td><td>+185 cm</td><td>81 cm</td><td>83 cm</td></tr>
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
                <p style="margin:4px 0 0 0; font-size:8px; color:#888;">*Ceniza 99% | Gris Jaspeado 97% | HD/VF/R6/RX/VH/HP/H1 50%</p>
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
            <table class="modal-size-table" style="font-size:11px;">
                <tr><th>Talla</th><th>Altura</th><th>Ancho</th><th>Largo</th></tr>
                <tr><td>S</td><td>163–170 cm</td><td>51 cm</td><td>67 cm</td></tr>
                <tr><td>M</td><td>170–176 cm</td><td>56 cm</td><td>70 cm</td></tr>
                <tr><td>L</td><td>176–182 cm</td><td>61 cm</td><td>73 cm</td></tr>
                <tr><td>XL</td><td>180–186 cm</td><td>63.5 cm</td><td>76 cm</td></tr>
                <tr><td>XXL</td><td>184–190 cm</td><td>68.5 cm</td><td>79 cm</td></tr>
            </table>
            <p style="margin:8px 0 0 0; font-size:10px; color:#666;">Tolerancia: ±2,5 cm</p>

            <!-- Características Técnicas -->
            <div style="margin-top:12px; border-top:1px solid #eee; padding-top:10px;">
                <p style="margin:0 0 8px 0; font-size:11px; font-weight:700; color:#8e44ad;">📋 Características</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; font-size:9px; color:#555;">
                    <div>80% Algodón*</div>
                    <div>260 g/m² (Blanco)</div>
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
function showProductGuide(type) {
    document.getElementById('guide-camiseta-content').style.display = type === 'camiseta' ? 'block' : 'none';
    document.getElementById('guide-sudadera-content').style.display = type === 'sudadera' ? 'block' : 'none';
    document.getElementById('btn-guide-camiseta').style.background = type === 'camiseta' ? '#e74c3c' : '#ecf0f1';
    document.getElementById('btn-guide-camiseta').style.color = type === 'camiseta' ? 'white' : '#666';
    document.getElementById('btn-guide-sudadera').style.background = type === 'sudadera' ? '#8e44ad' : '#ecf0f1';
    document.getElementById('btn-guide-sudadera').style.color = type === 'sudadera' ? 'white' : '#666';
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const galeriaImages = <?php echo $json_galeria; ?>;
    let currentIndex = 0;
    let isVideoMode = false;
    let scale = 1, translateX = 0, translateY = 0, isDragging = false, startX, startY;
    const modal = document.getElementById('zoom-modal'), container = document.getElementById('zoom-img-container');

    // Crear array combinado de fotos y vídeos para navegación
    const allMedia = [...galeriaImages];
    <?php if (!empty($video_delante)): ?>allMedia.push('<?php echo addslashes($video_delante); ?>');<?php endif; ?>
    <?php if (!empty($video_detras)): ?>allMedia.push('<?php echo addslashes($video_detras); ?>');<?php endif; ?>
    <?php if (!empty($video_como_se_hace)): ?>allMedia.push('<?php echo addslashes($video_como_se_hace); ?>');<?php endif; ?>

    function changeView(src, el, index) {
        const mainWrap = document.getElementById('main-media-wrap');
        const esVideo = src.match(/\.(mp4|webm|ogg)$/i) || (typeof index === 'string' && index.startsWith('video_'));

        // Siempre limpar todo primero para evitar estado inconsistente
        mainWrap.innerHTML = '';

        // Crear flecha izquierda
        const leftArrow = document.createElement('div');
        leftArrow.className = 'nav-arrow-main left';
        leftArrow.innerHTML = '<i class="fas fa-chevron-left"></i>';
        leftArrow.onclick = function(e) { e.stopPropagation(); navigateMain(-1); };
        mainWrap.appendChild(leftArrow);

        // Crear flecha derecha
        const rightArrow = document.createElement('div');
        rightArrow.className = 'nav-arrow-main right';
        rightArrow.innerHTML = '<i class="fas fa-chevron-right"></i>';
        rightArrow.onclick = function(e) { e.stopPropagation(); navigateMain(1); };
        mainWrap.appendChild(rightArrow);

        if (esVideo) {
            // Video
            mainWrap.setAttribute('onclick', 'toggleVideo()');
            mainWrap.style.cursor = 'pointer';
            const video = document.createElement('video');
            video.id = 'main-video';
            video.autoplay = true;
            video.muted = true;
            video.loop = true;
            video.controls = true;
            video.style.cssText = 'width:100%; max-height:100%; height:100%; object-fit:contain; background:#000; border-radius:15px;';
            const source = document.createElement('source');
            source.src = src;
            source.type = 'video/mp4';
            video.appendChild(source);
            mainWrap.appendChild(video);
            // overflow visible para que las flechas no se corten
        } else {
            // Imagen
            const img = document.createElement('img');
            img.id = 'current-img';
            img.src = src;
            img.alt = 'Producto';
            img.width = 550;
            img.height = 550;
            img.style.cssText = 'max-width:90%; max-height:90%; object-fit:contain; opacity:0; transition: opacity 0.5s ease-in-out;';
            
            // EL TRUCO: Mostrar la imagen solo cuando ya ha cargado
            img.onload = function() {
                this.classList.add('loaded');
                this.style.opacity = '1';
            };
            
            mainWrap.appendChild(img);
            mainWrap.onclick = openZoom;
            mainWrap.style.cursor = 'zoom-in';
        }

        // Actualizar miniaturas activas
        const allThumbs = document.querySelectorAll('.thumb-btn, .video-thumb');
        allThumbs.forEach(b => b.classList.remove('active'));
        if (el && el.classList) {
            el.classList.add('active');
        } else if (typeof index === 'number' && allThumbs[index]) {
            allThumbs[index].classList.add('active');
        }

        currentIndex = typeof index === 'number' ? index : 0;
    }

    function toggleVideo() {
        const mainVideo = document.getElementById('main-video');
        if (mainVideo) {
            if (mainVideo.paused) mainVideo.play();
            else mainVideo.pause();
        }
    }

    function hideValMsg() { document.getElementById('validation-msg').style.display = 'none'; }

    <?php if (isset($precios_material) && !empty($precios_material)): ?>
    const preciosMaterial = <?php echo json_encode($precios_material); ?>;
    
    document.querySelectorAll('input[name="talla"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const precioEl = document.getElementById('product-price');
            if (precioEl && preciosMaterial[this.value] !== undefined) {
                const base = parseFloat(precioEl.dataset.base) || 0;
                const ajuste = preciosMaterial[this.value];
                const nuevoPrecio = base + ajuste;
                precioEl.textContent = nuevoPrecio.toFixed(2).replace('.', ',') + ' €';
                
                const ldPriceEl = document.querySelector('script[type="application/ld+json"]');
                if (ldPriceEl) {
                    try {
                        const ldData = JSON.parse(ldPriceEl.textContent);
                        ldData.offers.price = nuevoPrecio.toFixed(2);
                        ldPriceEl.textContent = JSON.stringify(ldData);
                    } catch(e) {}
                }
            }
        });
    });
    <?php endif; ?>

    function openZoom() {
        const currentMedia = allMedia[currentIndex] || galeriaImages[0];
        const container = document.getElementById('zoom-img-container');

        // Si es un vídeo, mostrar en el modal
        if (currentMedia.match(/\.(mp4|webm|ogg)$/i)) {
            container.innerHTML = '<video id="zoom-video-el" controls autoplay style="max-width:90vw;max-height:calc(100vh - 120px);border-radius:10px;"><source src="' + currentMedia + '" type="video/mp4">Tu navegador no soporta vídeos.</video>';
        } else {
            // Si es imagen, asegurar que hay un img
            let zoomImgEl = document.getElementById('zoom-img-el');
            if (!zoomImgEl) {
                container.innerHTML = '<img id="zoom-img-el" src="" style="max-width:90vw;max-height:calc(100vh - 120px);object-fit:contain;border-radius:4px;box-shadow:0 10px 40px rgba(0,0,0,0.5);">';
                zoomImgEl = document.getElementById('zoom-img-el');
            }
            zoomImgEl.src = currentMedia;
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        resetPosition();
    }

    function closeZoom() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
        // Limpiar el container al cerrar para evitar problemas
        const container = document.getElementById('zoom-img-container');
        container.innerHTML = '<img id="zoom-img-el" src="">';
    }

    function navigateMain(dir) {
        currentIndex = (currentIndex + dir + allMedia.length) % allMedia.length;
        const newSrc = allMedia[currentIndex];
        const thumbs = document.querySelectorAll('.thumb-btn, .video-thumb');
        const targetThumb = thumbs[currentIndex];
        if (targetThumb) {
            changeView(newSrc, targetThumb, currentIndex);
        }
    }

    // Navegación en zoom
    function navigateZoom(dir) {
        currentIndex = (currentIndex + dir + allMedia.length) % allMedia.length;
        const newSrc = allMedia[currentIndex];
        const container = document.getElementById('zoom-img-container');

        if (newSrc.match(/\.(mp4|webm|ogg)$/i)) {
            // Es vídeo en el zoom
            container.innerHTML = '<video id="zoom-video-el" controls autoplay style="max-width:90vw;max-height:calc(100vh - 120px);border-radius:10px;"><source src="' + newSrc + '" type="video/mp4">Tu navegador no soporta vídeos.</video>';
        } else {
            // Es imagen en el zoom
            container.innerHTML = '<img id="zoom-img-el" src="' + newSrc + '" style="max-width:90vw;max-height:calc(100vh - 120px);object-fit:contain;border-radius:4px;box-shadow:0 10px 40px rgba(0,0,0,0.5);">';
        }
        resetPosition();
    }

    // Funciones para el modal de vídeo (modal separado para cuando se hace click en miniatura de vídeo)
    function openVideoModal(url, title) {
        const videoModal = document.getElementById('video-modal');
        const videoPlayer = document.getElementById('video-modal-player');
        const videoTitle = document.getElementById('video-modal-title');
        videoPlayer.src = url;
        videoTitle.textContent = title;
        videoModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
        const videoModal = document.getElementById('video-modal');
        const videoPlayer = document.getElementById('video-modal-player');
        videoPlayer.pause();
        videoPlayer.src = '';
        videoModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Cerrar vídeo con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVideoModal();
            closeZoom();
        }
    });

// --- NUEVO: CERRAR AL CLICKAR FUERA (Overlay) ---
    modal.addEventListener('click', function(e) {
        // Si el elemento clickeado es el fondo oscuro (modal) 
        // O el contenedor flex que centra la imagen (pero no la imagen en sí)
        if (e.target === modal || e.target === container) {
            closeZoom();
        }
    });

    function resetPosition() { scale = 1; translateX = 0; translateY = 0; updateTransform(); }
    function updateTransform() { container.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`; }

    // Exponer funciones al scope global para que los onclick del HTML funcionen
    window.changeView = changeView;
    window.openZoom = openZoom;
    window.closeZoom = closeZoom;
    window.navigateMain = navigateMain;
    window.navigateZoom = navigateZoom;
    window.toggleVideo = toggleVideo;
    window.hideValMsg = hideValMsg;
    window.openVideoModal = openVideoModal;
    window.closeVideoModal = closeVideoModal;

    modal.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY * -0.001;
        scale = Math.min(Math.max(1, scale + delta), 4);
        updateTransform();
    }, { passive: false });

    modal.addEventListener('mousedown', (e) => {
        if (scale > 1) { isDragging = true; startX = e.clientX - translateX; startY = e.clientY - translateY; e.preventDefault(); }
    });
    window.addEventListener('mousemove', (e) => { if (isDragging) { translateX = e.clientX - startX; translateY = e.clientY - startY; updateTransform(); } });
    window.addEventListener('mouseup', () => isDragging = false);

    modal.addEventListener('touchstart', (e) => {
        if (scale > 1 && e.touches.length === 1) { isDragging = true; startX = e.touches[0].clientX - translateX; startY = e.touches[0].clientY - translateY; }
    });
    modal.addEventListener('touchmove', (e) => {
        if (isDragging && e.touches.length === 1) { e.preventDefault(); translateX = e.touches[0].clientX - startX; translateY = e.touches[0].clientY - startY; updateTransform(); }
    }, { passive: false });
    window.addEventListener('touchend', () => isDragging = false);

    document.getElementById('product-form').addEventListener('submit', function(e) {
        const radios = document.getElementsByName('talla');
        if (radios.length > 0) {
            let sel = false;
            for(let r of radios) { if(r.checked) sel = true; }
            if(!sel) { 
                e.preventDefault(); 
                const msg = document.getElementById('validation-msg');
                msg.style.display = 'block';
                msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return; 
            }
        }
        const colorInput = document.getElementById('color-seleccionado');
        if (colorInput && colorInput.value === '') {
            e.preventDefault();
            const msg = document.getElementById('validation-msg');
            msg.textContent = '⚠️ Por favor, selecciona un color';
            msg.style.display = 'block';
            msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        document.getElementById('btn-add').innerHTML = '<i class="fas fa-spinner fa-spin"></i> AÑADIENDO...';
    });

    }); // fin DOMContentLoaded

    window.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('active')) return;
        if (e.key === "ArrowLeft") navigateZoom(-1);
        if (e.key === "ArrowRight") navigateZoom(1);
        if (e.key === "Escape") closeZoom();
    });
</script>

<script>
    function seleccionarColor(nombre, el) {
        document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('color-seleccionado').value = nombre;
        document.getElementById('color-nombre-display').textContent = nombre;
        document.getElementById('validation-msg').style.display = 'none';
    }

    // Inicializar color por defecto al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const negroSwatch = document.querySelector('.color-swatch[data-nombre="Negro"]');
        if (negroSwatch) {
            seleccionarColor('Negro', negroSwatch);
        }
    });
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?php echo htmlspecialchars($p['nombre']); ?>",
  "image": "<?php echo $p['imagen_url']; ?>",
  "description": "<?php echo htmlspecialchars(strip_tags($p['descripcion'])); ?>",
  "brand": { "@type": "Brand", "name": "Camiglobo" },
  "offers": {
    "@type": "Offer",
    "url": "<?php echo 'https://www.camiglobo.com/producto.php?id=' . $p['id']; ?>",
    "priceCurrency": "EUR",
    "price": "<?php echo $p['precio']; ?>",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
</script>
<?php include 'includes/footer.php'; ?>