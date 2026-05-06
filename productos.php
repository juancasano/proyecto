<?php
/**
 * ARCHIVO: productos.php
 * FUNCIÓN: Catálogo público con filtros, motor de búsqueda y paginación.
 * ACTUALIZACIÓN: Código limpiado, estilos unificados, HTML semántico y robustez total.
 */

require_once 'includes/config.php';
include 'includes/header.php';
?>

<!-- Estilos únicos y organizados para el catálogo -->
<style>
    /* === LAYOUT PRINCIPAL === */
    /* === LAYOUT PRINCIPAL === */
.catalogo {
    min-height: 80vh;
    margin-top: 40px;
    position: relative;
    z-index: 1;
    max-width: 1600px !important; /* Permite que el diseño se abra más */
    width: 95%; /* Se acerca a los bordes en pantallas medianas */
    margin-left: auto;
    margin-right: auto;
}

    /* === BARRA DE CATEGORÍAS === */
    .categorias-nav {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 8px;
        flex-wrap: wrap;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        position: relative;
        z-index: 1; 
    }

    .filter-link {
        padding: 10px 25px;
        color: #555;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        font-size: 14px;
        transition: 0.3s;
        white-space: nowrap;
    }

    .filter-link:hover {
        color: #e74c3c;
        background: #fff5f5;
    }

    .filter-link.active {
        background: #000;
        color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* === CONTADOR DE PRODUCTOS === */
    .catalogo-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin: 0 0 20px 0;
        padding: 0 6px;
        white-space: nowrap;
    }

    .contador-productos {
        color: #bbb;
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.3px;
        overflow: visible;
    }

    .contador-productos strong {
        color: #999;
        font-weight: 700;
    }

    /* === GRID DE PRODUCTOS === */
    .productos-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    /* Tarjeta de producto */
    .producto-card {
        background: white;
        border-radius: 20px;
        padding: 0;
        border: 1px solid #eee;
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

    .producto-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        border-color: #27ae60;
    }

    /* Contenedor de imagen con efecto skeleton — ocupa todo el ancho */
    .producto-imagen-container {
        width: 100%;
        aspect-ratio: 1 / 1;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0;
        border-radius: 0;
        background: linear-gradient(90deg, #f0f0f0 25%, #f8f8f8 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        overflow: hidden;
    }

    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .producto-imagen {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        opacity: 1;
        object-position: center center;
    }
    .producto-imagen.loaded {
        opacity: 1;
    }

    /* Área de texto debajo de la imagen */
    .producto-card a {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .producto-card-info {
        padding: 15px 15px 18px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .producto-titulo {
        font-size: 15px;
        height: 40px;
        overflow: hidden;
        margin-bottom: 10px;
        color: #2c3e50;
        font-weight: 700;
        line-height: 1.3;
    }

    .producto-precio {
        color: #e74c3c;
        font-weight: 800;
        font-size: 20px;
        margin-bottom: 15px;
    }

    .btn-detalles {
        background: #000;
        color: white;
        padding: 12px 20px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 11px;
        font-weight: 800;
        transition: 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
    }

    /* Botón Admin en tarjeta */
    .admin-edit-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #e74c3c;
        color: white !important;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        z-index: 5;
        box-shadow: 0 4px 10px rgba(231,76,60,0.4);
        transition: 0.3s;
    }

    .admin-edit-btn:hover {
        background: #c0392b;
        transform: scale(1.1);
    }

    /* === PAGINACIÓN === */
/* === PAGINACIÓN PREMIUM (CÍRCULOS) === */
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin: 80px 0;
}

.pagination-container {
    display: flex;
    gap: 12px;
    overflow-x: hidden;
    scroll-behavior: smooth;
    max-width: 350px;
    padding: 10px 5px;
}

.page-btn {
    /* Círculo Perfecto */
    width: 45px;
    height: 44px;
    min-width: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    
    border: 2px solid #eee;
    border-radius: 50%; /* Esto hace el círculo */
    text-decoration: none;
    color: #555;
    font-weight: 800;
    font-size: 14px;
    background: white;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}

.page-btn:hover {
    border-color: #000;
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.page-btn.active {
    background: #000;
    color: white;
    border-color: #000;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transform: scale(1.1);
}

.scroll-arrow {
    background: white;
    color: #555;
    border: 2px solid #eee;
    cursor: pointer;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}

.scroll-arrow:hover {
    background: #000;
    color: #fff;
    border-color: #000;
    transform: scale(1.1);
}

/* Ocultar scrollbar en móviles para que se vea limpio */
.pagination-container::-webkit-scrollbar {
    display: none;
}
.pagination-container {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
    /* === MENSAJE DE REGISTRO EXITOSO === */
    .reg-success-message {
        background: #ebfbee;
        color: #2b8a3e;
        padding: 20px;
        border-radius: 15px;
        text-align: center;
        border: 1px solid #8ce99a;
        font-weight: 700;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        margin-top: 20px;
        position: relative;
        z-index: 1000;
    }

    /* === MENSAJE SIN RESULTADOS === */
    .no-resultados {
        text-align: center;
        padding: 100px 20px;
        background: white;
        border-radius: 30px;
        border: 2px dashed #eee;
    }

    .no-resultados i {
        font-size: 50px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .no-resultados h3 {
        color: #888;
    }

    .no-resultados a {
        color: #27ae60;
        font-weight: 900;
        text-decoration: none;
    }
    
    .producto-descripcion {
        font-size: 13px;
        color: #777;
        margin: 0 0 15px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2; /* Muestra máximo 2 líneas */
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
        height: 36px; /* Altura fija para que todas las tarjetas midan lo mismo */
    }

    /* === BOTÓN FILTROS EN NAV === */
    .filtros-wrapper {
        position: relative;
    }

    .btn-filtros {
        padding: 10px 20px;
        color: #555;
        background: transparent;
        border: none;
        border-radius: 30px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
        font-family: inherit;
    }

    .btn-filtros:hover {
        color: #e74c3c;
        background: #fff5f5;
    }

    .btn-filtros.activo {
        background: #000;
        color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .btn-filtros .filtros-badge {
        background: #e74c3c;
        color: white;
        font-size: 10px;
        font-weight: 800;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    /* === DROPDOWN FILTROS === */
    .filtros-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        border: 1px solid #eee;
        padding: 24px;
        width: 320px;
        z-index: 999;
        animation: dropdownFadeIn 0.2s ease;
    }

    .filtros-dropdown.abierto {
        display: block;
    }

    @keyframes dropdownFadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .filtros-dropdown h4 {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #aaa;
        margin: 0 0 10px 0;
    }

    .filtros-seccion {
        margin-bottom: 20px;
    }

    .filtros-seccion:last-of-type {
        margin-bottom: 0;
    }

    /* Ordenar por */
    .orden-opciones {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .orden-opcion {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    .orden-opcion:hover {
        background: #f7f7f7;
    }

    .orden-opcion input[type="radio"] {
        accent-color: #000;
        width: 15px;
        height: 15px;
        cursor: pointer;
    }

    .orden-opcion.seleccionada {
        background: #f0f0f0;
        font-weight: 700;
    }

    /* Rango de precio */
    .precio-inputs {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .precio-inputs input[type="number"] {
        flex: 1;
        padding: 10px 12px;
        border: 2px solid #eee;
        border-radius: 12px;
        font-size: 14px;
        font-family: inherit;
        font-weight: 600;
        color: #333;
        transition: 0.2s;
        outline: none;
        width: 100%;
        -moz-appearance: textfield;
    }

    .precio-inputs input[type="number"]::-webkit-outer-spin-button,
    .precio-inputs input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
    }

    .precio-inputs input[type="number"]:focus {
        border-color: #000;
    }

    .precio-inputs span {
        color: #aaa;
        font-weight: 700;
        font-size: 13px;
    }

    /* Botones del dropdown */
    .filtros-acciones {
        display: flex;
        gap: 10px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid #f0f0f0;
    }

    .btn-aplicar-filtros {
        flex: 1;
        background: #000;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        letter-spacing: 0.5px;
        transition: 0.2s;
        font-family: inherit;
    }

    .btn-aplicar-filtros:hover {
        background: #222;
    }

    .btn-limpiar-filtros {
        padding: 12px 18px;
        border-radius: 50px;
        border: 2px solid #eee;
        background: white;
        color: #888;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
        font-family: inherit;
        white-space: nowrap;
    }

    .btn-limpiar-filtros:hover {
        border-color: #e74c3c;
        color: #e74c3c;
    }

    /* Separador entre nav y dropdown en móvil */
    @media (max-width: 768px) {
        .filtros-dropdown {
            width: 100vw;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            border-radius: 20px;
            top: calc(100% + 8px);
        }
    }

    /* === MEDIA QUERIES === */
    @media (max-width: 1100px) {
        .productos-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .productos-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        /* Categorías en móvil: grid 3 columnas centrado */
        .categorias-nav {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 12px;
            border-radius: 20px;
            margin-bottom: 10px;
        }

        .filter-link {
            padding: 10px 8px;
            font-size: 13px;
            text-align: center;
            border-radius: 12px;
            background: #f7f7f7;
        }

        .filter-link.active {
            background: #000;
            color: white;
        }

        /* Contador: texto completo, sin cortes */
        .catalogo-toolbar {
            margin: 0 0 20px 0;
            padding: 0 2px;
        }

        .contador-productos {
            font-size: 11.5px;
            white-space: normal;
            word-break: break-word;
        }
    }

    @media (max-width: 480px) {
        .productos-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        /* En muy pequeño: 2 columnas para categorías */
        .categorias-nav {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<?php
// --- Lógica PHP (Pura, sin HTML intercalado) ---

// 1. CONFIGURACIÓN DE PAGINACIÓN
$items_por_pagina = esMovil() ? 26 : 25;
$pagina_actual = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// 2. LOGICA DE FILTROS Y BÚSQUEDA
$categoria   = $_GET['categoria'] ?? '';
$busqueda    = isset($_GET['q']) ? trim($_GET['q']) : '';
$orden       = $_GET['orden'] ?? 'nombre_asc';
$precio_min  = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? (float)$_GET['precio_min'] : null;
$precio_max  = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? (float)$_GET['precio_max'] : null;

$where = " WHERE 1=1";
$params = [];

// Filtro por Categoría
if ($categoria && $categoria !== 'all') {
    $where .= " AND categoria = :cat";
    $params[':cat'] = $categoria;
}

// Motor de Búsqueda
if ($busqueda !== '') {
    $where .= " AND (nombre LIKE :q1 OR descripcion LIKE :q2 OR categoria LIKE :q3)";
    $termino = "%$busqueda%";
    $params[':q1'] = $termino;
    $params[':q2'] = $termino;
    $params[':q3'] = $termino;
}

// Filtro precio mínimo
if ($precio_min !== null) {
    $where .= " AND precio >= :precio_min";
    $params[':precio_min'] = $precio_min;
}

// Filtro precio máximo
if ($precio_max !== null) {
    $where .= " AND precio <= :precio_max";
    $params[':precio_max'] = $precio_max;
}

// Ordenación
$ordenes_validos = [
    'nombre_asc'   => 'nombre ASC',
    'nombre_desc'  => 'nombre DESC',
    'precio_asc'   => 'precio ASC',
    'precio_desc'  => 'precio DESC',
    'novedad'      => 'id DESC',
];
$order_sql = $ordenes_validos[$orden] ?? 'nombre ASC';

// Contar filtros activos (para el badge)
$filtros_activos = 0;
if ($precio_min !== null) $filtros_activos++;
if ($precio_max !== null) $filtros_activos++;
if ($orden !== 'nombre_asc') $filtros_activos++;

// 3. CONTAR TOTAL Y OBTENER PRODUCTOS
try {
    $sql_total = "SELECT COUNT(*) FROM productos" . $where;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_productos = (int)$stmt_total->fetchColumn();
$total_paginas = ceil($total_productos / $items_por_pagina);

    // 🚀 OPTIMIZACIÓN: Solo pedimos los 4 datos necesarios para la tarjeta. Ahorra muchísima RAM.
    $sql = "SELECT id, nombre, precio, imagen_url, descripcion FROM productos" . $where . " ORDER BY " . $order_sql . " LIMIT " . (int)$items_por_pagina . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error en catálogo Camiglobo: " . $e->getMessage());
    $productos = [];
    $total_productos = 0;
    $total_paginas = 0;
}

// --- Mostrar mensaje de registro exitoso si existe ---
if(isset($_GET['msg']) && $_GET['msg'] === 'reg_success'): ?>
<div class="container">
    <div class="reg-success-message" id="reg-alert">
        🎉 ¡Registro completado! Bienvenido a la familia Camiglobo. Ya puedes empezar a personalizar.
    </div>
</div>
<script>
    setTimeout(() => {
        const alert = document.getElementById('reg-alert');
        if(alert) {
            alert.style.transition = "all 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);
</script>
<?php endif; ?>

<!-- HTML PRINCIPAL - Limpio y semántico -->
<main class="container catalogo">

    <!-- Barra de categorías -->
    <nav class="categorias-nav">
        <a href="productos.php<?php echo $busqueda ? '?q='.urlencode($busqueda) : ''; ?>" 
           class="filter-link <?php echo !$categoria ? 'active' : ''; ?>">Todo</a>
        <?php 
        $categorias = ['camiseta' => 'Camisetas', 'sudadera' => 'Sudaderas', 'taza' => 'Tazas', 'cuadro' => 'Cuadros'];
        foreach($categorias as $slug => $nombre): ?>
            <a href="productos.php?categoria=<?php echo $slug; ?><?php echo $busqueda ? '&q='.urlencode($busqueda) : ''; ?>" 
               class="filter-link <?php echo $categoria == $slug ? 'active' : ''; ?>">
                <?php echo $nombre; ?>
            </a>
        <?php endforeach; ?>

        <!-- Botón Filtros con Dropdown -->
        <div class="filtros-wrapper">
            <button class="btn-filtros <?php echo $filtros_activos > 0 ? 'activo' : ''; ?>" 
                    id="btn-filtros" 
                    type="button" 
                    onclick="toggleFiltros(event)">
                <i class="fas fa-sliders-h"></i>
                Filtros
                <?php if($filtros_activos > 0): ?>
                    <span class="filtros-badge"><?php echo $filtros_activos; ?></span>
                <?php endif; ?>
            </button>

            <div class="filtros-dropdown" id="filtros-dropdown">
                <form method="GET" action="productos.php" id="form-filtros">
                    <?php if($categoria): ?>
                        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
                    <?php endif; ?>
                    <?php if($busqueda): ?>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <?php endif; ?>

                    <!-- Ordenar por -->
                    <div class="filtros-seccion">
                        <h4><i class="fas fa-sort"></i> &nbsp;Ordenar por</h4>
                        <div class="orden-opciones">
                            <?php
                            $opciones_orden = [
                                'novedad'     => ['label' => 'Más recientes', 'icon' => 'fa-star'],
                                'precio_asc'  => ['label' => 'Precio: menor a mayor', 'icon' => 'fa-arrow-up'],
                                'precio_desc' => ['label' => 'Precio: mayor a menor', 'icon' => 'fa-arrow-down'],
                                'nombre_asc'  => ['label' => 'Nombre: A → Z', 'icon' => 'fa-font'],
                                'nombre_desc' => ['label' => 'Nombre: Z → A', 'icon' => 'fa-font'],
                            ];
                            foreach($opciones_orden as $val => $op): ?>
                                <label class="orden-opcion <?php echo $orden === $val ? 'seleccionada' : ''; ?>">
                                    <input type="radio" name="orden" value="<?php echo $val; ?>" 
                                           <?php echo $orden === $val ? 'checked' : ''; ?>
                                           onchange="this.closest('label').closest('.orden-opciones').querySelectorAll('.orden-opcion').forEach(el=>el.classList.remove('seleccionada')); this.closest('label').classList.add('seleccionada');">
                                    <i class="fas <?php echo $op['icon']; ?>" style="color:#aaa; width:14px;"></i>
                                    <?php echo $op['label']; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Rango de precio -->
                    <div class="filtros-seccion">
                        <h4><i class="fas fa-tag"></i> &nbsp;Rango de precio (€)</h4>
                        <div class="precio-inputs">
                            <input type="number" name="precio_min" placeholder="Mín." min="0" step="0.01"
                                   value="<?php echo $precio_min !== null ? number_format($precio_min, 2, '.', '') : ''; ?>">
                            <span>—</span>
                            <input type="number" name="precio_max" placeholder="Máx." min="0" step="0.01"
                                   value="<?php echo $precio_max !== null ? number_format($precio_max, 2, '.', '') : ''; ?>">
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="filtros-acciones">
                        <button type="button" class="btn-limpiar-filtros" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                        <button type="submit" class="btn-aplicar-filtros">
                            Aplicar filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- TOOLBAR: contador -->
    <div class="catalogo-toolbar">
        <p class="contador-productos">
            <strong><?php echo number_format($total_productos, 0, ',', '.'); ?></strong>
            <?php echo $total_productos === 1 ? 'producto' : 'productos'; ?>
            <?php if($busqueda): ?>
                · "<?php echo htmlspecialchars($busqueda); ?>"
            <?php elseif($categoria && $categoria !== 'all'): ?>
                · <?php echo htmlspecialchars($categorias[$categoria] ?? $categoria); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Anclaje para la paginación -->
    <div id="seccion-tienda" style="scroll-margin-top: 120px;"></div>

    <!-- Contenido principal: Grid de productos o mensaje de error -->
    <?php if(empty($productos)): ?>
        <div class="no-resultados">
            <i class="fas fa-search-minus"></i>
            <h3>No hay coincidencias para "<?php echo htmlspecialchars($busqueda); ?>"</h3>
            <a href="productos.php">MOSTRAR TODO EL CATÁLOGO</a>
        </div>
    <?php else: ?>
        <div class="productos-grid">
            
            <?php foreach($productos as $p): 
    $img_segura = str_replace('http://', 'https://', $p['imagen_url']);
?>
    <div class="producto-card" style="position: relative;">
        
        <?php if(function_exists('esAdmin') && esAdmin()): ?>
            <a href="admin_productos.php?id=<?php echo urlencode($p['id']); ?>" class="admin-edit-btn" title="Editar Producto">
                <i class="fas fa-pencil-alt"></i>
            </a>
        <?php endif; ?>

        <a href="producto.php?id=<?php echo urlencode($p['id']); ?>" title="Ver detalles de <?php echo htmlspecialchars($p['nombre']); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; flex: 1;">
            <div class="producto-imagen-container">
                <img src="<?php echo htmlspecialchars($img_segura); ?>"
                     class="producto-imagen" 
                     alt="<?php echo htmlspecialchars($p['nombre']); ?>" 
                     loading="lazy" 
                     onload="this.classList.add('loaded')"
                     onerror="this.onerror=null; this.src='https://www.camiglobo.com/images/camiglobofavicon.jpg';">
            </div>

            <div class="producto-card-info">
                <h3 class="producto-titulo"><?php echo htmlspecialchars($p['nombre']); ?></h3>

                <?php if(!empty($p['descripcion'])): ?>
                    <p class="producto-descripcion">
                        <?php echo mb_strimwidth(strip_tags($p['descripcion']), 0, 80, "..."); ?>
                    </p>
                <?php endif; ?>

                <p class="producto-precio"><?php echo number_format($p['precio'], 2, ',', '.'); ?> €</p>
                <span class="btn-detalles">VER DETALLES</span>
            </div>
        </a>
    </div>
<?php endforeach; ?>
            
        </div>

        <!-- Paginación (si hay más de una página) -->
        <?php if($total_paginas > 1): ?>
        <div class="pagination-wrapper">
<button class="scroll-arrow" onclick="scrollPagination(-100)" aria-label="Página anterior"><i class="fas fa-arrow-left"></i></button>
<div class="pagination-container" id="pagination-list">
                <?php for($i = 1; $i <= $total_paginas; $i++): 
                    $query_params = ['page' => $i];
                    if($categoria)   $query_params['categoria']  = $categoria;
                    if($busqueda)    $query_params['q']           = $busqueda;
                    if($orden !== 'nombre_asc') $query_params['orden'] = $orden;
                    if($precio_min !== null)    $query_params['precio_min'] = $precio_min;
                    if($precio_max !== null)    $query_params['precio_max'] = $precio_max;
                    $url = "productos.php?" . http_build_query($query_params);
                ?>
                    <a href="<?php echo $url; ?>#seccion-tienda" 
                       class="page-btn <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
<button class="scroll-arrow" onclick="scrollPagination(100)" aria-label="Página siguiente"><i class="fas fa-arrow-right"></i></button>
</div>
        <?php endif; ?>
    <?php endif; ?>
</main>

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
.destacados-grid-cat {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
.destcat-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee;
    overflow: hidden;
    transition: 0.3s;
    position: relative;
    text-align: center;
}
.destcat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    border-color: #27ae60;
}
.destcat-card a {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.destcat-img-wrap {
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
}
.destcat-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.destcat-info {
    padding: 15px 15px 18px;
}
.destcat-titulo {
    font-size: 15px;
    height: 40px;
    overflow: hidden;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 700;
    line-height: 1.3;
}
.destcat-desc {
    font-size: 12px;
    color: #888;
    height: 36px;
    overflow: hidden;
    line-height: 1.4;
    margin-bottom: 8px;
}
.destcat-precio {
    color: #e74c3c;
    font-weight: 800;
    font-size: 20px;
    margin-bottom: 12px;
}
.destcat-btn {
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
@media (max-width: 1100px) { .destacados-grid-cat { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .destacados-grid-cat { grid-template-columns: repeat(2, 1fr); gap: 8px; } }
@media (max-width: 480px)  { .destacados-grid-cat { grid-template-columns: repeat(2, 1fr); gap: 6px; } }
</style>

<!-- 1. Nuestras Joyas (Destacados) -->
<section class="container" style="margin-top: 60px; margin-bottom: 40px;">
    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px; font-weight: 800;">Nuestras Joyas (Destacados)</h2>
    <div class="destacados-grid-cat">
        <?php
        $stmtDest = $pdo->query("SELECT id, nombre, precio, imagen_url, descripcion FROM productos WHERE destacado = 1 ORDER BY id DESC LIMIT 10");
        while($dest = $stmtDest->fetch()):
            $img_dest = str_replace('http://', 'https://', $dest['imagen_url']);
        ?>
        <div class="destcat-card">
            <a href="producto.php?id=<?php echo urlencode($dest['id']); ?>">
                <div class="destcat-img-wrap">
                    <img src="<?php echo htmlspecialchars($img_dest); ?>"
                         alt="<?php echo htmlspecialchars($dest['nombre']); ?>"
                         loading="lazy"
                         onerror="this.src='https://www.camiglobo.com/images/camiglobofavicon.jpg';">
                </div>
                <div class="destcat-info">
                    <h3 class="destcat-titulo"><?php echo htmlspecialchars($dest['nombre']); ?></h3>
                    <?php if(!empty($dest['descripcion'])): ?>
                        <p class="destcat-desc"><?php echo mb_strimwidth(strip_tags($dest['descripcion']), 0, 80, "..."); ?></p>
                    <?php endif; ?>
                    <p class="destcat-precio"><?php echo number_format($dest['precio'], 2, ',', '.'); ?> €</p>
                    <span class="destcat-btn">VER DETALLES</span>
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

<!-- Scripts para la funcionalidad de la paginación -->
<script>
    function scrollPagination(amount) { 
        document.getElementById('pagination-list').scrollBy({ left: amount, behavior: 'smooth' }); 
    }

    // Centra el número de página activo al cargar
    window.addEventListener('DOMContentLoaded', () => {
        const activeBtn = document.querySelector('.page-btn.active');
        const container = document.getElementById('pagination-list');
        if (activeBtn && container) {
            const offsetLeft = activeBtn.offsetLeft - container.offsetLeft;
            container.scrollLeft = offsetLeft - (container.offsetWidth / 2) + (activeBtn.offsetWidth / 2);
        }
    });
</script>

<script>
    // === DROPDOWN DE FILTROS ===
    function toggleFiltros(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('filtros-dropdown');
        const btn      = document.getElementById('btn-filtros');
        dropdown.classList.toggle('abierto');
        btn.classList.toggle('activo', dropdown.classList.contains('abierto') || <?php echo $filtros_activos > 0 ? 'true' : 'false'; ?>);
    }

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        const wrapper  = document.querySelector('.filtros-wrapper');
        const dropdown = document.getElementById('filtros-dropdown');
        if (wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.remove('abierto');
            <?php if($filtros_activos === 0): ?>
            document.getElementById('btn-filtros').classList.remove('activo');
            <?php endif; ?>
        }
    });

    // Limpiar todos los filtros extra (mantiene categoría y búsqueda)
    function limpiarFiltros() {
        const url = new URL(window.location.href);
        url.searchParams.delete('orden');
        url.searchParams.delete('precio_min');
        url.searchParams.delete('precio_max');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }
</script>

<?php include 'includes/footer.php'; ?>