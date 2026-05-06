<?php
/**
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

// --- RECUPERAR NOMBRE DEL ADMIN ---
$admin_id = $_SESSION['user_id'];
$stmtA = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmtA->execute([$admin_id]);
$admin_data = $stmtA->fetch();
$admin_nombre = h(explode(' ', $admin_data['nombre'] ?? 'Administrador')[0]);

// --- FILTROS DE TIEMPO Y BÚSQUEDA ---
$mes_filtro = $_GET['mes'] ?? date('m');
$anio_filtro = $_GET['anio'] ?? date('Y');
$q = isset($_GET['q']) ? h($_GET['q']) : '';

// --- PROCESAR ACCIONES (BLINDADAS CON CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de seguridad: Token inválido.");
    }

    $id_ped_act = (int)($_POST['marcar_enviado'] ?? $_POST['marcar_entregado'] ?? $_POST['revertir_pendiente'] ?? $_POST['cancelar_admin'] ?? 0);
    $url_base = "admin_pedidos.php?mes=$mes_filtro&anio=$anio_filtro&q=" . urlencode($q);
    $url_retorno = $url_base . "#pedido-$id_ped_act";

    // ACCIÓN: MARCAR COMO ENVIADO
    if (isset($_POST['marcar_enviado'])) {
        $id_ped = (int)$_POST['marcar_enviado'];
            $tracking_url = trim($_POST['tracking_url'] ?? '');

            // Validar y forzar HTTPS en tracking URL
            $tracking_url = filter_var($tracking_url, FILTER_VALIDATE_URL);
            if ($tracking_url && str_starts_with($tracking_url, 'http://')) {
                $tracking_url = str_replace('http://', 'https://', $tracking_url);
            }
            if (!$tracking_url) {
                $tracking_url = '';
            }

        try {
            $stmtInfo = $pdo->prepare("SELECT p.id_pago, u.email, u.nombre FROM pedidos p LEFT JOIN usuarios u ON p.user_id = u.id WHERE p.id = ?");
            $stmtInfo->execute([$id_ped]);
            $info = $stmtInfo->fetch();

            $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'Enviado', tracking_url = ? WHERE id = ?");
            $stmt->execute([$tracking_url, $id_ped]);

            if ($info && !empty($info['email'])) {
                $asunto = "🚀 Tu pedido de Camiglobo va en camino";
                $nombre = h(explode(' ', $info['nombre'])[0]);
                $logo_url = "https://www.camiglobo.com/images/camiglobofavicon.jpg";

                $cuerpo = "
                <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                    <div style='background: #fff; padding: 30px; text-align: center; border-bottom: 1px solid #eee;'>
                        <img src='$logo_url' style='height: 50px; border-radius: 12px; margin-bottom: 10px;'>
                        <div style='font-weight: 900; font-size: 22px; color: #000; letter-spacing: -1px;'>CAMIGLOBO</div>
                    </div>
                    <div style='background: #000; padding: 25px; text-align: center;'>
                        <h2 style='color: white; margin: 0; text-transform: uppercase; font-size: 18px; letter-spacing: 2px;'>¡Paquete en camino!</h2>
                    </div>
                    <div style='padding: 40px; background: #fff;'>
                        <h3 style='color: #2c3e50; font-size: 22px;'>¡Hola, $nombre!</h3>
                        <p style='color: #555; font-size: 16px; line-height: 1.6;'>Tu pedido <b>#".h($info['id_pago'])."</b> ya ha salido de nuestro taller.</p>
                        <p style='color: #555; font-size: 16px; line-height: 1.6;'>Pronto lo recibirás en tu dirección de entrega. ¡Gracias por confiar en Camiglobo Barcelona!</p>
                        " . (!empty($tracking_url) ? "<div style='margin-top:25px; text-align:center;'><a href='".h($tracking_url)."' style='background:#e74c3c; color:white; padding:15px 25px; text-decoration:none; border-radius:10px; font-weight:bold;'>RASTREAR MI PAQUETE</a></div>" : "") . "
                    </div>
                    <div style='background: #fafafa; padding: 25px; text-align: center; color: #999; font-size: 12px;'>
                        © " . date('Y') . " Camiglobo Barcelona - Diseños que vuelan.
                    </div>
                </div>";
                try { enviarEmail($info['email'], $asunto, $cuerpo); } catch (Exception $e) { error_log("Error email biblioteca: " . $e->getMessage()); }
            }
            header("Location: $url_retorno&msg=sent");
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
                $logo_url = "https://www.camiglobo.com/images/camiglobofavicon.jpg";

                $cuerpo = "
                <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                    <div style='background: #fff; padding: 30px; text-align: center; border-bottom: 1px solid #eee;'>
                        <img src='$logo_url' style='height: 50px; border-radius: 12px; margin-bottom: 10px;'>
                        <div style='font-weight: 900; font-size: 22px; color: #000; letter-spacing: -1px;'>CAMIGLOBO</div>
                    </div>
                    <div style='background: #27ae60; padding: 25px; text-align: center;'>
                        <h2 style='color: white; margin: 0; text-transform: uppercase; font-size: 18px; letter-spacing: 2px;'>¡Paquete Entregado!</h2>
                    </div>
                    <div style='padding: 40px; background: #fff;'>
                        <h3 style='color: #2c3e50; font-size: 22px;'>¡Hola, $nombre!</h3>
                        <p style='color: #555; font-size: 16px; line-height: 1.6;'>Tu pedido <b>#".h($info['id_pago'])."</b> ya figura como entregado con éxito.</p>
                        <p style='color: #555; font-size: 16px; line-height: 1.6;'>Esperamos que disfrutes mucho de tu nueva camiseta. Si tienes cualquier duda o quieres compartir tu estilo, ¡etiquétenos en Instagram o TikTok!</p>
                        <div style='margin-top:30px; text-align:center;'>
                            <a href='https://www.camiglobo.com' style='background:#000; color:white; padding:15px 25px; text-decoration:none; border-radius:10px; font-weight:bold;'>VOLVER A LA TIENDA</a>
                        </div>
                    </div>
                    <div style='background: #fafafa; padding: 25px; text-align: center; color: #999; font-size: 12px;'>
                        © " . date('Y') . " Camiglobo Barcelona - Diseños que vuelan.
                    </div>
                </div>";

                try { enviarEmail($info['email'], $asunto, $cuerpo); } catch (Exception $e) { error_log("Error email biblioteca: " . $e->getMessage()); }
            }

            header("Location: $url_retorno&msg=delivered");
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
        header("Location: $url_retorno&msg=reverted");
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
                <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden;'>
                    <div style='background: #e74c3c; padding: 20px; text-align: center;'>
                        <h2 style='color: white; margin: 0;'>Pedido Cancelado</h2>
                    </div>
                    <div style='padding: 30px; background: #fff;'>
                        <h3>Hola, $nombre</h3>
                        <p>Tu pedido <b>#{$info['id_pago']}</b> ha sido cancelado según tu solicitud.</p>
                        <p>Si no solicitaste esta cancelación, contáctanos inmediatamente.</p>
                        <p style='color: #666; font-size: 14px;'>Camiglobo Barcelona</p>
                    </div>
                </div>";

                try { enviarEmail($info['email'], $asunto, $cuerpo); } catch (Exception $e) { error_log("Error email biblioteca: " . $e->getMessage()); }
            }

            header("Location: $url_retorno&msg=cancelled");
            exit;
        } catch (Exception $e) {
            error_log("Error al cancelar: " . $e->getMessage());
            header("Location: $url_base&error=1");
            exit;
        }
    }
}

include 'includes/header.php';

// --- OBTENER DATOS CON LÓGICA GLOBAL VS FILTRADO ---
if ($q !== '') {
    $where_clause = "WHERE (p.id_pago LIKE ? OR u.nombre LIKE ? OR u.email LIKE ? OR p.productos LIKE ?)";
    $q_safe = strtr($q, ['%'=>'\\%', '_'=>'\\_', '\\'=>'\\\\']);
    $params = ["%$q_safe%", "%$q_safe%", "%$q_safe%", "%$q_safe%"];
} else {
    $where_clause = "WHERE MONTH(p.fecha) = ? AND YEAR(p.fecha) = ?";
    $params = [$mes_filtro, $anio_filtro];
}

$stmt = $pdo->prepare("
    SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono
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
$dias_del_mes = (int)date('t', strtotime("$anio_filtro-$mes_filtro-01"));
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
        border-collapse: collapse;
        background: white;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    }
    .table-pro th {
        background: #f8fafc;
        padding: 18px 16px;
        font-size: 11px;
        text-transform: uppercase;
        color: #475569;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
        text-align: left;
    }
    .table-pro td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .table-pro tr:last-child td {
        border-bottom: none;
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
        gap: 8px;
        width: 100%;
    }

    .btn-action {
        width: 100%;
        padding: 10px 12px;
        height: 46px;
        border-radius: 40px;
        font-weight: 700;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-transform: uppercase;
        text-decoration: none;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        line-height: 1;
    }
    .btn-action i {
        font-size: 14px;
    }

    .btn-ship {
        background: linear-gradient(145deg, #000000, #1a1a1a);
        color: #fff;
    }
    .btn-deliver {
        background: linear-gradient(145deg, #f9a825, #f57f17); /* Amarillo mostaza */
        color: #000;
    }
    .btn-cancel-admin {
        background: #fff;
        color: #c62828;
        border: 2px solid #c62828;
        box-shadow: none;
    }
    .btn-revert, .btn-rehabilitar {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        box-shadow: none;
    }

    .btn-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 18px rgba(0,0,0,0.15);
        filter: brightness(1.05);
    }
    .btn-ship:hover { background: linear-gradient(145deg, #1a1a1a, #000); }
    .btn-deliver:hover { background: linear-gradient(145deg, #f57f17, #f9a825); }
    .btn-cancel-admin:hover { background: #ffebee; }
    .btn-revert:hover, .btn-rehabilitar:hover { background: #e2e8f0; }

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
            default:
                $texto_alerta = "👋 ¡Hola de nuevo, " . $admin_nombre . "! Todo listo para gestionar tus ventas.";
        }
    } else {
        $texto_alerta = "👋 ¡Hola, " . $admin_nombre . "! Todo listo para gestionar tus ventas de hoy.";
    }
    ?>

    <div style="background: <?php echo $alerta_color; ?>; color: <?php echo $alerta_texto; ?>; padding: 12px 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid <?php echo $alerta_borde; ?>; font-weight: 600; font-size: 14px;">
        <i class="fas fa-<?php echo isset($_GET['msg']) && $_GET['msg'] === 'cancelled' ? 'times-circle' : 'check-circle'; ?>"></i> <?php echo $texto_alerta; ?>
    </div>

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
                <a href="?mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&export=csv"
                   style="background: #27ae60; color: white; padding: 10px 15px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-excel"></i> EXPORTAR MES
                </a>
            </div>
        </div>

        <form method="GET" style="display: flex; gap: 8px; width: 100%; border-top: 1px solid #f5f5f5; padding-top: 15px;">
            <select name="mes" style="flex: 1;">
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
                <?php for ($a = date('Y'); $a >= 2024; $a--): ?>
                    <option value="<?php echo $a; ?>" <?php echo $anio_filtro == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" style="background: #333;"><i class="fas fa-calendar-alt"></i> CAMBIAR MES</button>
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
            <i class="fas fa-chart-area"></i> Ventas diarias (sin cancelados) - <?php echo $meses_es[$mes_filtro - 1]; ?> <?php echo $anio_filtro; ?>
        </h4>
        <canvas id="ventasChart" style="max-height: 200px;"></canvas>
    </div>

    <!-- Tabla de pedidos -->
    <div style="overflow-x: hidden; border-radius: 20px; background: white; box-shadow: 0 2px 12px rgba(0,0,0,0.03);">
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

                        // Color de fondo según estado (semáforo)
                        $bg_color = "#ffffff";
                        if ($is_cancelled) $bg_color = "#fff5f5";
                        if ($is_sent) $bg_color = "#f0fff4";
                        if ($is_delivered) $bg_color = "#f8fafd";

                        // WhatsApp link
                        $wa_link = "";
                        if (!empty($p['cliente_telefono'])) {
                            $tlf = preg_replace('/[^0-9]/', '', $p['cliente_telefono']);
                            if (strlen($tlf) == 9) $tlf = "34" . $tlf;
                            $msj_wa = rawurlencode("¡Hola " . explode(' ', $p['cliente_nombre'])[0] . "! Te escribo de Camiglobo por tu pedido #" . $p['id_pago'] . " 👋");
                            $wa_link = "https://wa.me/$tlf?text=$msj_wa";
                        }
                    ?>
                        <tr id="pedido-<?php echo $p['id']; ?>" style="background-color: <?php echo $bg_color; ?> !important; <?php echo ($is_delivered || $is_cancelled) ? 'opacity: 0.9;' : ''; ?>">
                            <td>
                                <div class="mobile-only-label">FECHA:</div>
                                <strong><?php echo date('d/m', strtotime($p['fecha'])); ?></strong><br>
                                <small style="color: #999;">#<?php echo h($p['id_pago']); ?></small>
                            </td>
                            <td>
                                <div class="mobile-only-label">CLIENTE:</div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <strong style="font-size: 11px; color: #000;"><?php echo h($p['cliente_nombre'] ?? 'Invitado'); ?></strong>
                                    <?php if ($wa_link): ?>
                                        <a href="<?php echo $wa_link; ?>" target="_blank" style="color: #25D366; font-size: 15px;" title="WhatsApp directo">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <small style="color: #666; font-size: 9px; display: block;"><?php echo h($p['cliente_email'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <div class="mobile-only-label">PRODUCTOS:</div>
                                <div style="font-size: 11px; font-weight: 500; color: #333; line-height: 1.4;">
                                    <?php
                                    $texto_productos = h($p['productos']);
                                    if (preg_match('/(https?:\/\/[^\s]+)/', $texto_productos, $matches)) {
                                        $url_diseno = $matches[1];
                                        $texto_limpio = rtrim(trim(str_replace($url_diseno, '', $texto_productos)), ',');
                                        echo "<div style='margin-bottom:8px;'>" . nl2br($texto_limpio) . "</div>";
                                        echo "<a href='$url_diseno' target='_blank' class='design-link'><i class='fas fa-paint-brush'></i> DESCARGAR DISEÑO</a>";
                                    } else {
                                        echo nl2br($texto_productos);
                                    }
                                    ?>
                                </div>
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
                                    <a href="/ver_detalles.php?id=<?= $p['id'] ?>" class="btn-action" style="background: #3498db !important; color: white !important; border: 1.5px solid #2980b9 !important;">
                                        <i class="fas fa-eye"></i> DETALLES
                                    </a>

                                    <?php if ($is_delivered): ?>
                                        <div class="badge-status badge-entregado"><i class="fas fa-check-circle"></i> ENTREGADO</div>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="revertir_pendiente" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-revert"><i class="fas fa-undo"></i> REVERTIR</button>
                                        </form>

                                    <?php elseif ($is_sent): ?>
                                        <div class="badge-status badge-enviado"><i class="fas fa-paper-plane"></i> ENVIADO</div>
                                        <form method="POST" style="width: 100%;">
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

                                    <?php else: ?>
                                        <div class="badge-status badge-proceso"><i class="fas fa-clock"></i> <?php echo strtoupper(h($p['estado'] ?? 'EN TALLER')); ?></div>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="marcar_enviado" value="<?php echo $p['id']; ?>">
                                            <input type="text" name="tracking_url" class="tracking-input" placeholder="URL Seguimiento">
                                            <button type="submit" class="btn-action btn-ship" onclick="return confirm('¿Confirmar envío?')">
                                                <i class="fas fa-paper-plane"></i> ENVIAR
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres CANCELAR este pedido?');" style="width: 100%;">
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

<?php include 'includes/footer.php'; ?>