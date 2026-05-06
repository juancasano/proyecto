<?php
/**
 * ARCHIVO: admin_pedidos.php
 * FUNCIÓN: Panel de control de ventas blindado y optimizado.
 * VERSIÓN: MÓVIL CON ESTRUCTURA DE TRES NIVELES (TARJETAS INTELIGENTES)
 * MEJORAS: Facturación real (sin cancelados) + Layout móvil premium + Meses en español + WhatsApp Directo + Semáforo de colores
 */

require_once 'includes/config.php';

// --- SEGURIDAD: Solo acceso para Administradores ---
if (!esAdmin()) {
    header("Location: login.php");
    exit;
}

// ========================================================================
// 🧹 MOTOR DE LIMPIEZA MAESTRO - MANTENIMIENTO ZERO
// ========================================================================
try {
    // --- NUEVO: LIMPIEZA DE LA CAJA NEGRA (AUDIT LOG > 30 DÍAS) ---
    $pdo->exec("DELETE FROM audit_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");

    // --- 1. LIMPIEZA DE BIBLIOTECA (RECURSOS > 60 DÍAS) ---
    $stmtClean = $pdo->query("SELECT id, ruta_imagen FROM biblioteca_recursos WHERE fecha < DATE_SUB(NOW(), INTERVAL 60 DAY)");    $recursos_viejos = $stmtClean->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recursos_viejos)) {
        $ids_a_borrar = [];
        foreach ($recursos_viejos as $rec) {
            $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . '/uploads/recursos/' . basename($rec['ruta_imagen']);
            if (file_exists($ruta_fisica)) @unlink($ruta_fisica);
            $ids_a_borrar[] = $rec['id'];
        }
        if (!empty($ids_a_borrar)) {
            $placeholders = implode(',', array_fill(0, count($ids_a_borrar), '?'));
            $pdo->prepare("DELETE FROM biblioteca_recursos WHERE id IN ($placeholders)")->execute($ids_a_borrar);
        }
    }
    
   // --- 2. LIMPIEZA INTELIGENTE DE DISEÑOS "HUÉRFANOS"  ---
    // Solo borramos archivos que NO existen en la tabla pedidos_detalle
    $stmtUso = $pdo->query("SELECT imagen_personalizada FROM pedidos_detalle WHERE imagen_personalizada IS NOT NULL");
    $imagenes_en_uso = $stmtUso->fetchAll(PDO::FETCH_COLUMN);
    $nombres_en_uso = array_map('basename', $imagenes_en_uso);

    $carpetas_a_limpiar = [
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/pedidos/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/custom/'
    ];

    foreach ($carpetas_a_limpiar as $dir) {
        if (is_dir($dir)) {
            $archivos = glob($dir . "*");
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    $nombre_f = basename($archivo);
                    $antiguedad = time() - filemtime($archivo);

                    // BORRAMOS SOLO SI: No está en un pedido Y tiene más de 15 días (15 * 24h * 3600s)
    $dias_limite = 15;
    $segundos_limite = $dias_limite * 24 * 3600;

    if (!in_array($nombre_f, $nombres_en_uso) && $antiguedad > $segundos_limite) {
        @unlink($archivo);
                    }
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Error en Limpieza Maestra: " . $e->getMessage());
}
// ========================================================================
// --- RECUPERAR NOMBRE DEL ADMIN ---
$admin_id = $_SESSION['user_id'];
$stmtA = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmtA->execute([$admin_id]);
$admin_data = $stmtA->fetch();
$admin_nombre = h(explode(' ', $admin_data['nombre'] ?? 'Administrador')[0]);

// --- FILTROS DE TIEMPO Y BÚSQUEDA ---
$mes_filtro = $_GET['mes'] ?? 'todos';
$anio_filtro = $_GET['anio'] ?? '';
$q = isset($_GET['q']) ? h($_GET['q']) : '';

// --- PROCESAR ACCIONES (BLINDADAS CON CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de seguridad: Token inválido.");
    }

    $id_ped_act = (int)($_POST['marcar_enviado'] ?? $_POST['marcar_entregado'] ?? $_POST['revertir_pendiente'] ?? $_POST['cancelar_admin'] ?? $_POST['borrar_pedido'] ?? 0);
    $anio_param = $anio_filtro !== '' ? "&anio=$anio_filtro" : '';
    $url_base = "admin_pedidos.php?mes=$mes_filtro$anio_param&q=" . urlencode($q);

    // ACCIÓN: BORRAR PEDIDO COMPLETO
    if (isset($_POST['borrar_pedido'])) {
        $id_ped = (int)$_POST['borrar_pedido'];
        try {
            // 1. Obtener todas las imágenes del detalle para borrarlas del disco
            $stmtImgs = $pdo->prepare("SELECT imagen_custom, imagen_espalda, logos_extras FROM pedidos_detalle WHERE pedido_id = ?");
            $stmtImgs->execute([$id_ped]);
            $filas = $stmtImgs->fetchAll();

            foreach ($filas as $fila) {
                $rutas = [];
                if (!empty($fila['imagen_custom']))  $rutas[] = $fila['imagen_custom'];
                if (!empty($fila['imagen_espalda'])) $rutas[] = $fila['imagen_espalda'];
                if (!empty($fila['logos_extras'])) {
                    $logos = json_decode($fila['logos_extras'], true);
                    if (is_array($logos)) $rutas = array_merge($rutas, array_values($logos));
                }
                foreach ($rutas as $ruta) {
                    if (empty($ruta) || strpos($ruta, 'http') === 0) continue;
                    $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($ruta, '/');
                    if (file_exists($ruta_fisica)) @unlink($ruta_fisica);
                }
            }

            // 2. Borrar líneas de detalle
            $pdo->prepare("DELETE FROM pedidos_detalle WHERE pedido_id = ?")->execute([$id_ped]);

            // 3. Borrar cabecera del pedido
            $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$id_ped]);

            header("Location: {$url_base}&msg=deleted");
            exit;
        } catch (Exception $e) {
            error_log("Error al borrar pedido: " . $e->getMessage());
            header("Location: {$url_base}&error=1");
            exit;
        }
    }

    // ACCIÓN: MARCAR COMO ENVIADO
    if (isset($_POST['marcar_enviado'])) {
        $id_ped = (int)$_POST['marcar_enviado'];
        $tracking_url = trim($_POST['tracking_url'] ?? '');

        try {
            $stmtInfo = $pdo->prepare("SELECT p.id_pago, u.email, u.nombre FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");
            $stmtInfo->execute([$id_ped]);
            $info = $stmtInfo->fetch();

            $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'Enviado', tracking_url = ? WHERE id = ?");
            $stmt->execute([$tracking_url, $id_ped]);

            if ($info && !empty($info['email'])) {
                $asunto = "🚀 Tu pedido de Camiglobo va en camino";
                $nombre = h(explode(' ', $info['nombre'])[0]);

                $cuerpo = "
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='font-size: 45px; margin-bottom: 5px;'>🚚</div>
                    <h2 style='color: #3498db; margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px;'>¡Paquete en camino!</h2>
                </div>
                <div style='padding: 0; background: #fff;'>
                    <h3 style='color: #2c3e50; font-size: 20px;'>¡Hola, $nombre!</h3>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Tu pedido <b>#".h($info['id_pago'])."</b> ya ha salido de nuestro taller.</p>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Pronto lo recibirás en tu dirección de entrega. ¡Gracias por confiar en Camiglobo Barcelona!</p>
                    " . (!empty($tracking_url) ? "<div style='margin-top:25px; text-align:center;'><a href='".h($tracking_url)."' style='background:#3498db; color:white; padding:14px 28px; text-decoration:none; border-radius:50px; font-weight:800; font-size:14px; display:inline-block;'>RASTREAR MI PAQUETE</a></div>" : "") . "
                </div>";
                try { enviarEmail($info['email'], $asunto, $cuerpo, '#3498db'); } catch (Exception $e) { error_log("Error email pedidos: " . $e->getMessage()); }
            }
            header("Location: $url_base&msg=sent#pedido-$id_ped_act");
            exit;
        } catch (Exception $e) {
            error_log("Error envío: " . $e->getMessage());
            header("Location: $url_base&error=1");
            exit;
        }
    }

    // ACCIÓN: MARCAR COMO ENTREGADO
    if (isset($_POST['marcar_entregado'])) {
        $id_ped = (int)$_POST['marcar_entregado'];

        try {
            $stmtInfo = $pdo->prepare("SELECT p.id_pago, u.email, u.nombre FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");
            $stmtInfo->execute([$id_ped]);
            $info = $stmtInfo->fetch();

            $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'Entregado' WHERE id = ?");
            $stmt->execute([$id_ped]);

            if ($info && !empty($info['email'])) {
                $asunto = "🎉 ¡Tu pedido de Camiglobo ha sido entregado!";
                $nombre = h(explode(' ', $info['nombre'])[0]);

                $cuerpo = "
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='font-size: 50px; margin-bottom: 5px;'>🎉</div>
                    <h2 style='color: #27ae60; margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px;'>¡Paquete Entregado!</h2>
                </div>
                <div style='padding: 0; background: #fff;'>
                    <h3 style='color: #2c3e50; font-size: 20px;'>¡Hola, $nombre!</h3>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Tu pedido <b>#".h($info['id_pago'])."</b> ya figura como entregado con éxito.</p>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Esperamos que disfrutes mucho de tu nuevo pedido. Si tienes cualquier duda o quieres compartir tu estilo, ¡etiquétenos en Instagram o TikTok!</p>
                    <div style='margin-top:30px; text-align:center;'>
                        <a href='https://www.camiglobo.com' style='background:#111; color:white; padding:14px 28px; text-decoration:none; border-radius:50px; font-weight:800; font-size:14px; display:inline-block;'>VOLVER A LA TIENDA</a>
                    </div>
                </div>";

                try { enviarEmail($info['email'], $asunto, $cuerpo, '#27ae60'); } catch (Exception $e) { error_log("Error email pedidos: " . $e->getMessage()); }
            }

            // Email de enhorabuena al admin
            $nombre_admin = $info ? h(explode(' ', $info['nombre'])[0]) : 'Cliente';
            $id_pago_admin = $info ? h($info['id_pago']) : $id_ped;
            try { enviarEmail(ADMIN_EMAIL, "🎉 ¡Pedido #" . $id_pago_admin . " entregado!", "<div style='text-align:center;'><div style='font-size:50px;margin-bottom:10px;'>🎉</div><h2 style='color:#27ae60;margin:0;'>Enhorabuena</h2><p style='color:#555;font-size:15px;'>Has entregado el pedido <b>#" . $id_pago_admin . "</b> a <b>" . $nombre_admin . "</b>.</p><p style='color:#777;font-size:14px;'>¡Otro pedido más con éxito!</p></div>", '#27ae60'); } catch (Exception $e) {}

            header("Location: $url_base&msg=delivered#pedido-$id_ped_act");
            exit;

        } catch (Exception $e) {
            error_log("Error al marcar entregado: " . $e->getMessage());
            header("Location: $url_base&error=1");
            exit;
        }
    }

    // ACCIÓN: REVERTIR A TALLER
    if (isset($_POST['revertir_pendiente'])) {
        $id_ped = (int)$_POST['revertir_pendiente'];
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'En taller' WHERE id = ?");
        $stmt->execute([$id_ped]);
        header("Location: $url_base&msg=reverted#pedido-$id_ped_act");
        exit;
    }

    // ACCIÓN: CANCELAR PEDIDO (POR EL ADMIN)
    if (isset($_POST['cancelar_admin'])) {
        $id_ped = (int)$_POST['cancelar_admin'];

        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ?");
            $stmt->execute([$id_ped]);

            $stmtInfo = $pdo->prepare("SELECT p.id_pago, u.email, u.nombre FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");
            $stmtInfo->execute([$id_ped]);
            $info = $stmtInfo->fetch();

            if ($info && !empty($info['email'])) {
                $asunto = "⚠️ Tu pedido en Camiglobo ha sido cancelado";
                $nombre = h(explode(' ', $info['nombre'])[0]);

                $cuerpo = "
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='font-size: 50px; margin-bottom: 5px;'>⚠️</div>
                    <h2 style='color: #e74c3c; margin: 0; font-size: 22px;'>Pedido Cancelado</h2>
                </div>
                <div>
                    <h3 style='color: #2c3e50; font-size: 20px;'>Hola, $nombre</h3>
                    <p style='color: #555; font-size: 15px; line-height: 1.7;'>Tu pedido <b>#{$info['id_pago']}</b> ha sido cancelado según tu solicitud.</p>
                    <p style='color: #777; font-size: 15px; line-height: 1.7;'>Si no solicitaste esta cancelación, contáctanos inmediatamente respondiendo a este email.</p>
                    <div style='margin-top:25px; text-align:center;'>
                        <a href='https://www.camiglobo.com' style='background:#111; color:white; padding:14px 28px; text-decoration:none; border-radius:50px; font-weight:800; font-size:14px; display:inline-block;'>VOLVER A LA TIENDA</a>
                    </div>
                </div>";

                try { enviarEmail($info['email'], $asunto, $cuerpo, '#e74c3c'); } catch (Exception $e) { error_log("Error email pedidos: " . $e->getMessage()); }
            }

            header("Location: $url_base&msg=cancelled#pedido-$id_ped_act");
            exit;
        } catch (Exception $e) {
            error_log("Error al cancelar: " . $e->getMessage());
            header("Location: $url_base&error=1");
            exit;
        }
    }
}

include 'includes/colors.php';
include 'includes/header.php';

// --- OBTENER DATOS CON LÓGICA GLOBAL VS FILTRADO ---
$where_conditions = [];
$params = [];

// 1. Filtro de búsqueda
if ($q !== '') {
    $q_like = "%".strtr($q, ['%'=>'\\%','_'=>'\\_','\\'=>'\\\\'])."%";
    $where_conditions[] = "(p.id_pago LIKE ? OR u.nombre LIKE ? OR u.email LIKE ? OR p.productos LIKE ?)";
    $params = [$q_like, $q_like, $q_like, $q_like];
}

// 2. Filtro de fecha
if ($mes_filtro !== 'todos') {
    // Mes específico: filtro por mes y año
    $where_conditions[] = "MONTH(p.fecha) = ? AND YEAR(p.fecha) = ?";
    $params[] = $mes_filtro;
    $params[] = $anio_filtro;
} else {
    // Todos los meses
    if ($anio_filtro !== '') {
        // Año específico
        $where_conditions[] = "YEAR(p.fecha) = ?";
        $params[] = $anio_filtro;
    }
    // Si año está vacío: sin filtro de fecha (todos los años)
}

// Construir WHERE final
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
} else {
    $where_clause = "";
}

// ✅ CAMBIO 1: Consulta SQL actualizada para incluir extras (Manga, Nuca, etc.)
$stmt = $pdo->prepare("
    SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
           (SELECT GROUP_CONCAT(DISTINCT pd.extras_descripcion SEPARATOR ' | ') 
            FROM pedidos_detalle pd 
            WHERE pd.pedido_id = p.id AND pd.extras_descripcion IS NOT NULL) as todos_los_extras
    FROM pedidos p 
    LEFT JOIN usuarios u ON p.user_id = u.id 
    $where_clause
    ORDER BY p.fecha DESC
");
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// --- LÓGICA DE EXPORTACIÓN CSV CON BOM ---
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pedidos_camiglobo.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID Pago', 'Fecha', 'Cliente', 'Email', 'Productos', 'Total', 'Estado']);
    foreach ($pedidos as $row) {
        fputcsv($output, [$row['id_pago'], $row['fecha'], $row['cliente_nombre'], $row['cliente_email'], $row['productos'], $row['total'], $row['estado']]);
    }
    fclose($output);
    exit;
}

// --- CÁLCULO DE ANALÍTICA ---
// Si es "todos los meses", usamos 31 días como defecto o el año seleccionado
if ($mes_filtro === 'todos') {
    $dias_del_mes = 31;
    $anio_para_calculo = $anio_filtro !== '' ? $anio_filtro : date('Y');
    $mes_para_calculo = 12; // Usamos diciembre para tener todos los días del año
} else {
    $dias_del_mes = (int)date('t', strtotime("$anio_filtro-$mes_filtro-01"));
    $anio_para_calculo = $anio_filtro;
    $mes_para_calculo = (int)$mes_filtro;
}

$ventas_por_dia = array_fill(1, $dias_del_mes, 0);
$total_ingresos = 0;
$pendientes = 0;
$cancelados = 0;
$enviados = 0;
$entregados = 0;

foreach ($pedidos as $p) {
    $est_p = strtolower(trim($p['estado'] ?? ''));

    if ($est_p !== 'cancelado') {
        $total_ingresos += (float)$p['total'];
        $dia = (int)date('j', strtotime($p['fecha']));
        if ($dia >= 1 && $dia <= $dias_del_mes) {
            $ventas_por_dia[$dia] += (float)$p['total'];
        }
    }

    if ($est_p === 'enviado') $enviados++;
    elseif ($est_p === 'entregado') $entregados++;
    elseif ($est_p === 'cancelado') $cancelados++;
    else $pendientes++;
}
$js_labels = json_encode(range(1, $dias_del_mes));
$js_data = json_encode(array_values($ventas_por_dia));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- FontAwesome ya está en el header, no es necesario cargarlo otra vez -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->

<style>
    /* ==========================================================================
       ESTILOS ESPECÍFICOS PARA EL PANEL DE ADMINISTRACIÓN
       (Complementan al CSS global common.min.css)
       ========================================================================== */
    :root {
        --primary-color: #2c3e50;
        --accent-color: #ff6b6b;
        --dark-color: #333;
        --light-color: #f8f9fa;
        --border-color: #e0e0e0;
    }

    * {
        box-sizing: border-box;
        max-width: 100%;
    }

    body {
        overflow-x: hidden;
        width: 100%;
        background: #f2f4f8;  /* Fondo gris suave */
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }

    .container {
        width: 100%;
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 15px;
        overflow-x: hidden;
    }

    /* --- TARJETAS DE ESTADÍSTICAS --- */
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.03);
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.05);
    }
    .stat-card p {
        color: #64748b;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        margin: 0 0 8px 0;
        letter-spacing: 0.3px;
    }
    .stat-card div {
        font-size: 28px;
        font-weight: 800;
    }
    .stat-card small {
        color: #94a3b8;
        font-size: 11px;
    }

    /* --- BARRA DE FILTROS --- */
    .filter-bar {
        background: #fff;
        padding: 20px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        gap: 15px;
        border: 1px solid rgba(0,0,0,0.03);
        box-shadow: 0 8px 20px rgba(0,0,0,0.02);
        flex-wrap: wrap;
        margin-bottom: 25px;
    }
    .filter-bar select, .filter-bar button, .filter-bar input {
        padding: 12px 18px;
        border-radius: 40px;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        font-size: 13px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    .filter-bar input {
        cursor: text;
        background: #f8fafc;
    }
    .filter-bar input:focus {
        border-color: #000;
        outline: none;
        background: #fff;
    }
    .filter-bar select:hover {
        border-color: #cbd5e1;
    }
    .filter-bar button {
        background: #000;
        color: white;
        border: none;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .filter-bar button:hover {
        background: #1e293b;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* --- TABLA PRINCIPAL --- */
    .table-pro {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        background: transparent;
    }
    .table-pro thead tr {
        background: transparent;
    }
    .table-pro th {
        background: #f8fafc;
        padding: 14px 16px;
        font-size: 11px;
        text-transform: uppercase;
        color: #475569;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 700;
        text-align: left;
    }
    .table-pro th:first-child { border-radius: 12px 0 0 12px; }
    .table-pro th:last-child  { border-radius: 0 12px 12px 0; }
    .table-pro tbody tr {
        background: white;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        border-radius: 16px;
        transition: box-shadow 0.2s;
    }
    .table-pro tbody tr:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.09);
    }
    .table-pro td {
        padding: 16px;
        vertical-align: top;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-pro td:first-child {
        border-left: 1px solid #f1f5f9;
        border-radius: 16px 0 0 16px;
        border-right: none;
    }
    .table-pro td:last-child {
        border-right: 1px solid #f1f5f9;
        border-radius: 0 16px 16px 0;
        border-left: none;
    }

    /* --- BADGES DE ESTADO (NO CLICABLES) --- */
    .badge-status {
        padding: 10px 18px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 12px;
        width: 100%;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        border: none;
        cursor: default;
        pointer-events: none;
        transition: none;
    }
    .badge-enviado {
        background: linear-gradient(145deg, #2e7d32, #1b5e20);
        color: white;
    }
    .badge-proceso {
        background: linear-gradient(145deg, #e65100, #bf360c);
        color: white;
    }
    .badge-entregado {
        background: linear-gradient(145deg, #0d47a1, #0a2e6e);
        color: white;
    }
    .badge-cancelado {
        background: linear-gradient(145deg, #c62828, #8b1e1e);
        color: white;
    }

    /* --- CONTENEDOR DE ACCIONES (BOTONES) --- */
    .actions-grid {
        display: flex;
        flex-direction: column;
        gap: 7px;
        width: 180px;
    }

    /* Separador visual entre grupos de botones */
    .actions-grid .btn-divider {
        border: none;
        border-top: 1px dashed #e2e8f0;
        margin: 2px 0;
    }

    .btn-action {
        width: 100%;
        padding: 10px 14px;
        height: 42px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-transform: uppercase;
        text-decoration: none;
        letter-spacing: 0.4px;
        line-height: 1;
    }
    .btn-action i { font-size: 13px; }

    /* VER DETALLES — azul */
    .btn-action[href*="ver_detalles"] {
        background: #e8f4fd !important;
        color: #1565c0 !important;
        border: 1.5px solid #90caf9 !important;
        box-shadow: none !important;
    }
    .btn-action[href*="ver_detalles"]:hover {
        background: #1565c0 !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(21,101,192,0.25) !important;
    }

    /* ENVIAR — negro primario */
    .btn-ship {
        background: #000;
        color: #fff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
    .btn-ship:hover {
        background: #222;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }

    /* ENTREGAR — verde */
    .btn-deliver {
        background: #27ae60;
        color: #fff;
        border: 1.5px solid #27ae60;
        box-shadow: 0 3px 10px rgba(39,174,96,0.25);
        animation: pulseGreen 2s infinite;
    }
    .btn-deliver:hover {
        background: #219a52;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(39,174,96,0.35);
        animation: none;
    }
    @keyframes pulseGreen {
        0%, 100% { box-shadow: 0 3px 10px rgba(39,174,96,0.25); }
        50% { box-shadow: 0 3px 20px rgba(39,174,96,0.5); }
    }

    /* CANCELAR — rojo outline */
    .btn-cancel-admin {
        background: #fff;
        color: #c62828;
        border: 1.5px solid #ef9a9a;
    }
    .btn-cancel-admin:hover {
        background: #ffebee;
        border-color: #c62828;
        transform: translateY(-2px);
    }

    /* REVERTIR / REHABILITAR — gris neutro */
    .btn-revert, .btn-rehabilitar {
        background: #f1f5f9;
        color: #64748b;
        border: 1.5px solid #cbd5e1;
    }
    .btn-revert:hover, .btn-rehabilitar:hover {
        background: #e2e8f0;
        color: #334155;
        transform: translateY(-2px);
    }

    .tracking-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 40px;
        border: 2px solid #e2e8f0;
        font-size: 12px;
        margin-bottom: 6px;
        outline: none;
        transition: 0.2s;
        background: #f8fafc;
    }
    .tracking-input:focus {
        border-color: #000;
        background: #fff;
    }

    /* WhatsApp links dentro de la tabla (NO afecta al header) */
    .table-pro .fa-whatsapp {
        color: #25D366;
        font-size: 18px;
        transition: transform 0.2s;
    }
    .table-pro .fa-whatsapp:hover {
        transform: scale(1.2);
    }

    /* --- RESPONSIVE MÓVIL (ESTRUCTURA 3 NIVELES) --- */
    @media (max-width: 850px) {
        .table-pro, .table-pro thead, .table-pro tbody, .table-pro tr, .table-pro td {
            display: block !important;
        }
        .table-pro thead {
            display: none !important;
        }
        .table-pro tr {
            display: grid !important;
            grid-template-areas: 
                "fecha cliente"
                "productos productos"
                "total acciones";
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 6px 20px rgba(0,0,0,0.03);
        }
        .table-pro td:nth-child(1) {
            grid-area: fecha;
            border-bottom: 1px solid #f1f5f9;
            padding: 0 0 10px 0 !important;
        }
        .table-pro td:nth-child(2) {
            grid-area: cliente;
            text-align: right;
            border-bottom: 1px solid #f1f5f9;
            padding: 0 0 10px 0 !important;
        }
        .table-pro td:nth-child(3) {
            grid-area: productos;
            background: #f8fafc;
            border-radius: 20px;
            padding: 16px !important;
            margin: 5px 0;
            border: none !important;
        }
        .table-pro td:nth-child(4) {
            grid-area: total;
            display: flex;
            align-items: center;
            padding: 10px 0 0 0 !important;
            border: none !important;
        }
        .table-pro td:nth-child(5) {
            grid-area: acciones;
            padding: 10px 0 0 0 !important;
            border: none !important;
        }
        .actions-grid {
            flex-direction: row !important;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn-action {
            flex: 1;
            min-width: 100px;
            font-size: 10px;
            height: 42px;
        }
        .mobile-only-label {
            display: block !important;
            font-size: 8px !important;
            font-weight: 900 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8 !important;
            margin-bottom: 4px;
        }
        canvas { height: 180px !important; }
    }

    /* Línea de resumen inferior */
    .summary-line {
        margin-top: 20px;
        padding: 16px 20px;
        background: #fff;
        border-radius: 40px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 12px;
        color: #475569;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .summary-line span i {
        margin-right: 6px;
        font-size: 14px;
    }

    /* Títulos y textos */
    h1 {
        color: #0f172a;
        font-weight: 800;
        font-size: 1.8rem;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin: 5px 0 0 0;
    }
    .subtitle i {
        color: #e74c3c;
    }

    /* Enlace de diseño (para productos) */
    .design-link {
        background: #e74c3c;
        color: white;
        padding: 6px 12px;
        border-radius: 40px;
        text-decoration: none;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        transition: 0.2s;
    }
    .design-link:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* ── MODAL CONFIRMACIÓN ── */
    .confirm-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 100000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .confirm-overlay.active { display: flex; }
    .confirm-box {
        background: white;
        border-radius: 20px;
        max-width: 420px;
        width: 100%;
        padding: 32px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        text-align: center;
        animation: confirmPop 0.25s ease;
    }
    @keyframes confirmPop {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .confirm-icon { font-size: 42px; margin-bottom: 12px; }
    .confirm-title { font-size: 18px; font-weight: 900; color: #111; margin-bottom: 8px; }
    .confirm-msg { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 24px; }
    .confirm-btns { display: flex; gap: 12px; justify-content: center; }
    .confirm-btn {
        padding: 12px 28px;
        border: none;
        border-radius: 50px;
        font-weight: 800;
        font-size: 13px;
        cursor: pointer;
        transition: 0.2s;
        text-transform: uppercase;
    }
    .confirm-btn-cancel {
        background: #f0f0f0;
        color: #555;
    }
    .confirm-btn-cancel:hover { background: #ddd; }
    .confirm-btn-ok { color: white; }
    .confirm-btn-ok:hover { opacity: 0.85; transform: translateY(-1px); }
    .confirm-btn-ok.btn-enviar { background: #3498db; }
    .confirm-btn-ok.btn-entregar { background: #27ae60; }
    .confirm-btn-ok.btn-cancelar { background: #e74c3c; }

    /* ── TOAST FLOTANTE ── */
    .toast-float {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 100001;
        padding: 16px 24px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        animation: toastIn 0.4s ease, toastOut 0.4s ease 3.5s forwards;
        max-width: 400px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toast-success { background: #27ae60; color: white; }
    .toast-error { background: #e74c3c; color: white; }
    .toast-neutral { background: #555; color: white; }
    @keyframes toastIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes toastOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(120%); opacity: 0; } }

</style>

<div class="container">

    <?php
    // Mensajes de alerta
    $texto_alerta = "";
    $alerta_color = "#d4edda";
    $alerta_texto = "#155724";
    $alerta_borde = "#28a745";

    if (isset($_GET['msg'])) {
        switch ($_GET['msg']) {
            case 'sent':
                $texto_alerta = "✅ Pedido marcado como ENVIADO y email notificado al cliente.";
                break;
            case 'delivered':
                $texto_alerta = "✅ Pedido marcado como ENTREGADO correctamente.";
                break;
            case 'reverted':
                $texto_alerta = "🔄 Estado revertido a EN TALLER.";
                break;
            case 'cancelled':
                $texto_alerta = "❌ Pedido CANCELADO correctamente.";
                $alerta_color = "#f8d7da";
                $alerta_texto = "#721c24";
                $alerta_borde = "#dc3545";
                break;
            case 'deleted':
                $texto_alerta = "🗑️ Pedido eliminado para siempre. No queda rastro en la base de datos.";
                $alerta_color = "#f3f4f6";
                $alerta_texto = "#374151";
                $alerta_borde = "#9ca3af";
                break;
            default:
                $texto_alerta = "👋 ¡Hola de nuevo, " . $admin_nombre . "! Todo listo para gestionar tus ventas.";
        }
    } else {
        $texto_alerta = "👋 ¡Hola, " . $admin_nombre . "! Todo listo para gestionar tus ventas de hoy.";
    }
    ?>
    <?php if (isset($_GET['msg'])): ?>
    <div class="toast-float <?php echo $alerta_borde === '#dc3545' ? 'toast-error' : ($alerta_borde === '#9ca3af' ? 'toast-neutral' : 'toast-success'); ?>" id="toastAdmin">
        <i class="fas fa-<?php echo $_GET['msg'] === 'cancelled' ? 'times-circle' : ($_GET['msg'] === 'deleted' ? 'trash-alt' : 'check-circle'); ?>"></i>
        <?php echo $texto_alerta; ?>
    </div>
    <?php else: ?>
    <div style="background: <?php echo $alerta_color; ?>; color: <?php echo $alerta_texto; ?>; padding: 12px 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid <?php echo $alerta_borde; ?>; font-weight: 600; font-size: 14px;">
        <i class="fas fa-hand-sparkles"></i> <?php echo $texto_alerta; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #dc3545; font-weight: 600;">
            <i class="fas fa-exclamation-triangle"></i> Hubo un error al procesar la acción. Revisa los logs del servidor.
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: #000; font-weight: 800; font-size: 1.8rem; margin: 0; letter-spacing: -0.5px;">
                ¡Hola, <?php echo $admin_nombre; ?>! ⚡
            </h1>
            <p style="color: #666; font-size: 0.95rem; margin: 5px 0 0 0;">
                <i class="fas fa-chart-line" style="color: #e74c3c;"></i> Panel de Gestión de <span style="color:#e74c3c; font-weight: 700;">Ventas</span>
            </p>
        </div>
        <div style="text-align: right;">
            <img src="https://www.camiglobo.com/images/camiglobofavicon.jpg" style="height: 50px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>
    </div>

    <!-- FILTROS + BUSCADOR + EXPORTACIÓN -->
    <div class="filter-bar" style="display: flex; flex-direction: column; gap: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px;">
            <form method="GET" style="display: flex; gap: 8px; flex: 1; min-width: 300px;">
                <input type="hidden" name="mes" value="<?php echo $mes_filtro; ?>">
                <input type="hidden" name="anio" value="<?php echo $anio_filtro; ?>">
                <input type="text" name="q" value="<?php echo $q; ?>" placeholder="Buscar por ID, nombre o email..."
                       style="flex: 1; padding: 10px 15px; border-radius: 12px; border: 1px solid #eee; font-size: 13px;">
                <button type="submit" style="background: #000; color: white; padding: 10px 20px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700;">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <div style="display: flex; gap: 8px;">
                <?php
                $anio_csv = $anio_filtro !== '' ? "&anio=$anio_filtro" : '';
                $mes_csv = $mes_filtro !== 'todos' ? "&mes=$mes_filtro" : '';
                ?>
                <a href="?mes=<?php echo $mes_filtro; ?><?php echo $anio_csv; ?>&export=csv"
                   style="background: #27ae60; color: white; padding: 10px 15px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-excel"></i> EXPORTAR
                </a>
            </div>
        </div>

        <form method="GET" style="display: flex; gap: 8px; width: 100%; border-top: 1px solid #f5f5f5; padding-top: 15px;">
            <select name="mes" style="flex: 1;">
                <option value="todos" <?php echo $mes_filtro === 'todos' ? 'selected' : ''; ?>>Todos los meses</option>
                <?php
                $meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                for ($m = 1; $m <= 12; $m++):
                    $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                ?>
                    <option value="<?php echo $val; ?>" <?php echo $mes_filtro == $val ? 'selected' : ''; ?>>
                        <?php echo $meses_es[$m - 1]; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="anio" style="flex: 1;">
                <option value="">Todos los años</option>
                <?php for ($a = date('Y'); $a >= 2024; $a--): ?>
                    <option value="<?php echo $a; ?>" <?php echo $anio_filtro == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" style="background: #333;"><i class="fas fa-calendar-alt"></i> FILTRAR</button>
        </form>
    </div>

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="stat-card">
            <p><i class="fas fa-euro-sign"></i> Facturación Total (Reales)</p>
            <div style="color: #2e7d32;"><?php echo formatPrecio($total_ingresos); ?></div>
            <small><?php echo count($pedidos) - $cancelados; ?> pedidos válidos</small>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-hourglass-half"></i> En Producción</p>
            <div style="color: #e65100;"><?php echo $pendientes; ?></div>
            <small><?php echo $enviados; ?> enviados · <?php echo $entregados; ?> entregados</small>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-times-circle"></i> Cancelados</p>
            <div style="color: #c62828;"><?php echo $cancelados; ?></div>
            <small><?php echo count($pedidos) > 0 ? round(($cancelados / count($pedidos)) * 100) : 0; ?>% del total</small>
        </div>
    </div>

    <!-- Gráfico de ventas -->
    <div style="background: white; padding: 20px; border-radius: 20px; border: 1px solid #f0f0f0; margin-bottom: 25px;">
        <h4 style="margin: 0 0 15px 0; font-weight: 700; text-transform: uppercase; font-size: 12px; color: #999;">
            <i class="fas fa-chart-area"></i> Ventas diarias (sin cancelados) -
            <?php
            if ($mes_filtro === 'todos'):
                echo $anio_filtro !== '' ? "Año $anio_filtro" : "Todos los años";
            else:
                echo $meses_es[$mes_filtro - 1] . " $anio_filtro";
            endif;
            ?>
        </h4>
        <canvas id="ventasChart" style="max-height: 200px;"></canvas>
    </div>

    <!-- Tabla de pedidos -->
    <div style="border-radius: 20px; padding: 10px 0;">
        <table class="table-pro">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar"></i> Fecha/ID</th>
                    <th><i class="fas fa-user"></i> Cliente</th>
                    <th><i class="fas fa-box"></i> Productos</th>
                    <th><i class="fas fa-euro-sign"></i> Total</th>
                    <th style="text-align: center;"><i class="fas fa-cog"></i> Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                            <strong>No hay pedidos en este período</strong>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $p):
                        $estado_raw = trim($p['estado'] ?? '');
                        $estado_actual = strtolower($estado_raw);
                        $is_sent = ($estado_actual === 'enviado');
                        $is_delivered = ($estado_actual === 'entregado');
                        $is_cancelled = ($estado_actual === 'cancelado');
                        $is_pending_payment = ($estado_actual === 'pendiente pago');
                        $nombre_mostrar = $p['cliente_nombre'] ?? 'Usuario Eliminado (RGPD)';
                        // Color de fondo según estado (semáforo)
                        $bg_color = "#ffffff";
                        if ($is_cancelled) $bg_color = "#fff5f5";
                        if ($is_sent) $bg_color = "#f0fff4";
                        if ($is_delivered) $bg_color = "#f8fafd";
                        if ($is_pending_payment) $bg_color = "#fffbeb";

                        // WhatsApp link
                        $wa_link = "";
                        if (!empty($p['cliente_telefono'])) {
                            $tlf = preg_replace('/[^0-9]/', '', $p['cliente_telefono']);
                            if (strlen($tlf) == 9) $tlf = "34" . $tlf;
$msj_wa = rawurlencode("¡Hola " . explode(' ', $nombre_mostrar)[0] . "! Te escribo de Camiglobo por tu pedido #" . $p['id_pago'] . " 👋");
$wa_link = "https://wa.me/$tlf?text=$msj_wa";
                        }

                        // DETALLES DEL PEDIDO (productos con imagen, talla, color, diseños)
                        $stmtDet = $pdo->prepare("
                            SELECT pd.*, pr.imagen_url as producto_imagen
                            FROM pedidos_detalle pd
                            LEFT JOIN productos pr ON pd.producto_id = pr.id
                            WHERE pd.pedido_id = ?
                        ");
                        $stmtDet->execute([$p['id']]);
                        $detalle_productos = $stmtDet->fetchAll();
                    ?>
                        <tr id="pedido-<?php echo $p['id']; ?>" style="background-color: <?php echo $bg_color; ?> !important; <?php echo ($is_delivered || $is_cancelled) ? 'opacity: 0.9;' : ''; ?>">
                            <td>
                                <div class="mobile-only-label">FECHA:</div>
                                <strong><?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?></strong><br>
                                <small style="color: #999;">#<?php echo h($p['id_pago']); ?></small>
                            </td>
<td>
    <div class="mobile-only-label">CLIENTE:</div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <strong style="font-size: 11px; color: #000;"><?php echo h($nombre_mostrar); ?></strong>                                    <?php if ($wa_link): ?>
                                        <a href="<?php echo $wa_link; ?>" target="_blank" style="color: #25D366; font-size: 15px;" title="WhatsApp directo">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <small style="color: #666; font-size: 9px; display: block;"><?php echo h($p['cliente_email'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <div class="mobile-only-label">PRODUCTOS:</div>
                                <?php
                                $fixPath = function($path) {
                                    if(empty($path)) return null;
                                    return (strpos($path, 'http') === 0 || strpos($path, '/') === 0) ? $path : '/' . ltrim($path, '/');
                                };
                                foreach ($detalle_productos as $det):
                                    $color_nombre = $det['color'] ?? 'Estándar';
                                    $color_hex_val = $colores_hex[$color_nombre] ?? null;
                                ?>
                                <div style="border:1px solid #f0f0f0; border-radius:10px; padding:8px; margin-bottom:8px; background:#fafafa;">
                                    
                                    <!-- Imagen del producto base -->
                                    <?php if (!empty($det['producto_imagen'])):
                                        $img_base = $fixPath($det['producto_imagen']); ?>
                                        <div style="text-align:center; margin-bottom:6px;">
                                            <a href="<?= $img_base ?>" target="_blank">
                                                <img src="<?= $img_base ?>" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #ddd;">
                                            </a>
                                            <a href="<?= $img_base ?>" download style="display:block; font-size:9px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:2px;">DESCARGAR</a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Nombre y cantidad -->
                                    <div style="font-weight:700; font-size:11px; color:#000; margin-bottom:4px;">
                                        <?= h($det['nombre']) ?> <span style="color:#e74c3c;">x<?= $det['cantidad'] ?></span>
                                        <span style="float:right; color:#27ae60; font-weight:800; font-size:12px;">
                                            <?= formatPrecio($det['precio_unitario'] ?? 0) ?>/ud
                                        </span>
                                    </div>

                                    <!-- Talla y color -->
                                    <div style="font-size:10px; line-height:1.8;">
                                        <strong>Talla:</strong> <span style="color:#e74c3c; font-weight:bold;"><?= h($det['talla'] ?? 'Única') ?></span>
                                        &nbsp;|&nbsp;
                                        <strong>Color:</strong>
                                        <?php if ($color_hex_val): ?>
                                            <span style="display:inline-flex; align-items:center; gap:4px; background:#f1f1f1; padding:1px 7px 1px 3px; border-radius:20px; font-weight:700; vertical-align:middle;">
                                                <span style="width:14px; height:14px; border-radius:50%; background:<?= $color_hex_val ?>; border:1px solid rgba(0,0,0,0.15); display:inline-block;"></span>
                                                <?= h($color_nombre) ?>
                                            </span>
                                        <?php else: ?>
                                            <?= h($color_nombre) ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Extras -->
                                    <?php if (!empty($det['extras_descripcion'])): ?>
                                        <div style="margin-top:4px; font-size:9px; color:#e74c3c; font-weight:bold; background:#fff5f5; padding:3px 5px; border:1px dashed #e74c3c; border-radius:4px;">
                                            <i class="fas fa-plus-circle"></i> <?= h($det['extras_descripcion']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Imágenes de diseño (frontal, espalda, logos) -->
                                    <?php
                                    $tieneImagenes = !empty($det['imagen_custom']) || !empty($det['imagen_espalda']) || !empty($det['logos_extras']);
                                    if ($tieneImagenes): ?>
                                        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:6px;">
                                            <?php if ($urlF = $fixPath($det['imagen_custom'])): ?>
                                                <div style="text-align:center; border:1px solid #eee; padding:3px; border-radius:6px; background:#fff;">
                                                    <small style="display:block; font-weight:900; font-size:8px; color:#999;">FRONTAL</small>
                                                    <a href="<?= $urlF ?>" target="_blank"><img src="<?= $urlF ?>" style="max-width:55px; border:1px solid #000;"></a>
                                                    <a href="<?= $urlF ?>" download style="display:block; font-size:8px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:2px;">DESCARGAR</a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($urlB = $fixPath($det['imagen_espalda'])): ?>
                                                <div style="text-align:center; border:1px solid #eee; padding:3px; border-radius:6px; background:#fff;">
                                                    <small style="display:block; font-weight:900; font-size:8px; color:#999;">ESPALDA</small>
                                                    <a href="<?= $urlB ?>" target="_blank"><img src="<?= $urlB ?>" style="max-width:55px; border:1px solid #000;"></a>
                                                    <a href="<?= $urlB ?>" download style="display:block; font-size:8px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:2px;">DESCARGAR</a>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                            if (!empty($det['logos_extras'])):
                                                $logos = json_decode($det['logos_extras'], true);
                                                if (is_array($logos)):
                                                    foreach ($logos as $zona => $ruta):
                                                        $urlL = $fixPath($ruta); ?>
                                                        <div style="text-align:center; border:1px solid #eee; padding:3px; border-radius:6px; background:#fff;">
                                                            <small style="display:block; font-weight:900; font-size:8px; color:#999;"><?= strtoupper($zona) ?></small>
                                                            <a href="<?= $urlL ?>" target="_blank"><img src="<?= $urlL ?>" style="max-width:55px; border:1px solid #000;"></a>
                                                            <a href="<?= $urlL ?>" download style="display:block; font-size:8px; color:#e74c3c; text-decoration:none; font-weight:bold; margin-top:2px;">DESCARGAR</a>
                                                        </div>
                                            <?php       endforeach;
                                                endif;
                                            endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Nota del cliente -->
                                    <?php if (!empty($det['notas'])): ?>
                                        <div style="margin-top:5px; font-size:9px; color:#2c3e50; background:#fef9e7; padding:5px; border-left:3px solid #f1c40f; border-radius:4px;">
                                            <strong style="text-transform:uppercase; color:#996515;">📝 Nota:</strong> <?= nl2br(h($det['notas'])) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                                <?php endforeach; ?>

                                <?php if (!empty($p['tracking_url'])): ?>
                                    <div style="margin-top: 6px; font-size: 10px;">
                                        <a href="<?php echo h($p['tracking_url']); ?>" target="_blank" style="color: #0d47a1; text-decoration: underline;">
                                            <i class="fas fa-external-link-alt"></i> Tracking
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="mobile-only-label">TOTAL:</div>
                                <strong style="color: #e74c3c; font-size: 16px;"><?php echo formatPrecio($p['total']); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <div class="actions-grid">
                                    
<a href="ver_detalles.php?id=<?= $p['id'] ?>" class="btn-action" style="background: #3498db !important; color: white !important; border: 1.5px solid #2980b9 !important;">
    <i class="fas fa-eye"></i> DETALLES
</a>
<hr class="btn-divider">
<?php if ($is_cancelled): ?>
    <form method="POST" data-confirm="¿Borrar este pedido para siempre? Se eliminarán todos los datos e imágenes." data-type="borrar">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="borrar_pedido" value="<?php echo $p['id']; ?>">
        <button type="submit" class="btn-action" style="background:#1a1a1a; color:#ff6b6b; border:1.5px solid #ff6b6b;">
            <i class="fas fa-trash-alt"></i> BORRAR
        </button>
    </form>
<?php endif; ?>

                                    <?php if ($is_delivered): ?>
                                        <div class="badge-status badge-entregado"><i class="fas fa-check-circle"></i> ENTREGADO</div>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="revertir_pendiente" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-revert"><i class="fas fa-undo"></i> REVERTIR</button>
                                        </form>

                                    <?php elseif ($is_sent): ?>
                                        <div class="badge-status badge-enviado"><i class="fas fa-paper-plane"></i> ENVIADO</div>
                                        <form method="POST" style="width: 100%;" data-confirm="¿Confirmar entrega del pedido?" data-type="entregar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="marcar_entregado" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-deliver"><i class="fas fa-check-double"></i> ENTREGAR</button>
                                        </form>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="revertir_pendiente" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-revert"><i class="fas fa-undo"></i> REVERTIR</button>
                                        </form>

                                    <?php elseif ($is_cancelled): ?>
                                        <div class="badge-status badge-cancelado"><i class="fas fa-times-circle"></i> CANCELADO</div>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="revertir_pendiente" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-rehabilitar"><i class="fas fa-magic"></i> REHABILITAR</button>
                                        </form>

                                    <?php elseif ($is_pending_payment): ?>
                                        <div class="badge-status" style="background: linear-gradient(145deg, #f59e0b, #d97706); color: #fff;">
                                            <i class="fas fa-clock"></i> PENDIENTE PAGO
                                        </div>
                                        <div style="background:#fffbeb; border:1.5px dashed #f59e0b; border-radius:8px; padding:8px 10px; font-size:10px; color:#92400e; font-weight:600; text-align:center;">
                                            <i class="fas fa-lock"></i> No se puede enviar hasta que el cliente pague
                                        </div>
                                        <form method="POST" style="width: 100%;" data-confirm="¿Seguro que quieres CANCELAR este pedido?" data-type="cancelar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="cancelar_admin" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-cancel-admin"><i class="fas fa-times"></i> CANCELAR</button>
                                        </form>

                                    <?php else: ?>
                                        <div class="badge-status badge-proceso"><i class="fas fa-clock"></i> <?php echo strtoupper(h($p['estado'] ?? 'EN TALLER')); ?></div>
                                        <form method="POST" style="width: 100%;" data-confirm="¿Confirmar envío del pedido?" data-type="enviar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="marcar_enviado" value="<?php echo $p['id']; ?>">
                                            <input type="text" name="tracking_url" class="tracking-input" placeholder="URL Seguimiento">
                                            <button type="submit" class="btn-action btn-ship">
                                                <i class="fas fa-paper-plane"></i> ENVIAR
                                            </button>
                                        </form>
                                        <form method="POST" style="width: 100%;" data-confirm="¿Seguro que quieres CANCELAR este pedido?" data-type="cancelar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="cancelar_admin" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-cancel-admin"><i class="fas fa-times"></i> CANCELAR</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Resumen rápido -->
    <div class="summary-line">
        <span><i class="fas fa-check-circle" style="color: #2e7d32;"></i> Entregados: <?php echo $entregados; ?></span>
        <span><i class="fas fa-truck" style="color: #0d47a1;"></i> Enviados: <?php echo $enviados; ?></span>
        <span><i class="fas fa-clock" style="color: #e65100;"></i> En taller: <?php echo $pendientes; ?></span>
        <span><i class="fas fa-times-circle" style="color: #c62828;"></i> Cancelados: <?php echo $cancelados; ?></span>
    </div>
</div>

<!-- MODAL CONFIRMACIÓN -->
<div class="confirm-overlay" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-icon" id="confirmIcon">⚠️</div>
        <div class="confirm-title" id="confirmTitle">Confirmar acción</div>
        <div class="confirm-msg" id="confirmMsg">¿Estás seguro?</div>
        <div class="confirm-btns">
            <button class="confirm-btn confirm-btn-cancel" onclick="closeConfirm()">Cancelar</button>
            <button class="confirm-btn confirm-btn-ok" id="confirmBtnOk" onclick="confirmSubmit()">Confirmar</button>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('ventasChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(231, 76, 60, 0.15)');
    gradient.addColorStop(1, 'rgba(231, 76, 60, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $js_labels; ?>,
            datasets: [{
                label: 'Ventas (€)',
                data: <?php echo $js_data; ?>,
                borderColor: '#e74c3c',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#e74c3c',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 8,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return 'Ventas: ' + context.parsed.y.toFixed(2) + ' €';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f5f5f5' },
                    ticks: {
                        font: { size: 9, weight: '500' },
                        callback: function (value) {
                            return value + '€';
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 9, weight: '500' } }
                }
            }
        }
    });
</script>

<script>
/* ── AUTO-OCULTAR TOAST FLOTANTE ── */
var toast = document.getElementById('toastAdmin');
if (toast) {
    setTimeout(function() {
        toast.style.display = 'none';
    }, 4000);
}

/* ── MODAL CONFIRMACIÓN ── */
var confirmForm = null;

function showConfirm(form, message, type) {
    confirmForm = form;
    var icons = { enviar: '📦', entregar: '✅', cancelar: '⚠️', revertir: '↩️', rehabilitar: '🔄', borrar: '🗑️' };
    var titles = { enviar: 'Confirmar envío', entregar: 'Confirmar entrega', cancelar: 'Cancelar pedido', revertir: 'Revertir pedido', rehabilitar: 'Rehabilitar pedido', borrar: 'Borrar pedido' };
    var btnClasses = { enviar: 'btn-enviar', entregar: 'btn-entregar', cancelar: 'btn-cancelar', revertir: 'btn-enviar', rehabilitar: 'btn-entregar', borrar: 'btn-cancelar' };

    document.getElementById('confirmIcon').textContent = icons[type] || '⚠️';
    document.getElementById('confirmTitle').textContent = titles[type] || 'Confirmar acción';
    document.getElementById('confirmMsg').textContent = message;
    var btn = document.getElementById('confirmBtnOk');
    btn.className = 'confirm-btn confirm-btn-ok ' + (btnClasses[type] || 'btn-enviar');
    btn.textContent = type === 'enviar' ? '📦 Confirmar envío' : type === 'entregar' ? '✅ Confirmar entrega' : type === 'cancelar' ? '🗑️ Cancelar' : '✓ Confirmar';

    document.getElementById('confirmModal').classList.add('active');
}

function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('active');
    confirmForm = null;
}

function confirmSubmit() {
    if (confirmForm) confirmForm.submit();
}

document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeConfirm();
});

document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm(this, this.getAttribute('data-confirm'), this.getAttribute('data-type') || 'enviar');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
