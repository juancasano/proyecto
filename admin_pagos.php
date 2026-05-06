<?php
/**
 * ARCHIVO: admin_pagos.php
 * FUNCIÓN: Panel de auditoría de pagos con datos de PayPal y Bizum para chargebacks y refunds.
 * VERSIÓN: Blindada con seguridad de admin y JOIN con tabla pagos.
 */

require_once 'includes/config.php';

// --- SEGURIDAD: Solo acceso para Administradores ---
if (!esAdmin()) {
    header("Location: login.php");
    exit;
}

// --- FILTROS DE TIEMPO Y BÚSQUEDA ---
$mes_filtro = $_GET['mes'] ?? 'todos';
$anio_filtro = $_GET['anio'] ?? date('Y');
$q = isset($_GET['q']) ? h($_GET['q']) : '';
$estado_filtro = $_GET['estado'] ?? '';
$metodo_filtro = $_GET['metodo'] ?? '';

// --- OBTENER PAGOS CON JOIN A PEDIDOS Y USUARIOS ---
$where_conditions = [];
$params = [];

// 1. Filtro de fecha/método
if ($metodo_filtro === '') {
    // Todos los métodos: PayPal con filtro de fecha + Bizum con filtro de created_at (fecha de creación del pago)
    if ($mes_filtro === 'todos') {
        // Todos los meses
        if ($anio_filtro !== '') {
            // Año específico: PayPal del año + Bizum del año (usando created_at)
            $where_conditions[] = "(
                (pag.payment_method = 'PAYPAL' AND YEAR(pag.captured_at) = ?)
                OR (pag.payment_method = 'BIZUM' AND YEAR(pag.created_at) = ?)
            )";
            $params[] = $anio_filtro;
            $params[] = $anio_filtro;
        } else {
            // Todos los años
            $where_conditions[] = "(pag.payment_method = 'PAYPAL' OR pag.payment_method = 'BIZUM')";
        }
    } else {
        // Mes específico: PayPal del mes + Bizum del mes (usando created_at)
        $where_conditions[] = "(
            (pag.payment_method = 'PAYPAL' AND MONTH(pag.captured_at) = ? AND YEAR(pag.captured_at) = ?)
            OR (pag.payment_method = 'BIZUM' AND MONTH(pag.created_at) = ? AND YEAR(pag.created_at) = ?)
        )";
        $params[] = $mes_filtro;
        $params[] = $anio_filtro;
        $params[] = $mes_filtro;
        $params[] = $anio_filtro;
    }
} elseif ($metodo_filtro === 'BIZUM') {
    // Solo Bizum: filtro por created_at en vez de captured_at
    if ($mes_filtro === 'todos') {
        if ($anio_filtro !== '') {
            $where_conditions[] = "YEAR(pag.created_at) = ?";
            $params[] = $anio_filtro;
        }
    } else {
        $where_conditions[] = "MONTH(pag.created_at) = ? AND YEAR(pag.created_at) = ?";
        $params[] = $mes_filtro;
        $params[] = $anio_filtro;
    }
} else {
    // Solo PayPal: con filtro de captured_at
    if ($mes_filtro === 'todos') {
        if ($anio_filtro !== '') {
            $where_conditions[] = "YEAR(pag.captured_at) = ?";
            $params[] = $anio_filtro;
        }
    } else {
        $where_conditions[] = "(MONTH(pag.captured_at) = ? AND YEAR(pag.captured_at) = ?)";
        $params[] = $mes_filtro;
        $params[] = $anio_filtro;
    }
}

// 2. Filtro de búsqueda
if ($q !== '') {
    $q_like = "%".strtr($q, ['%'=>'\\%','_'=>'\\_','\\'=>'\\\\'])."%";
    $where_conditions[] = "(pag.payer_email LIKE ? OR pag.payer_name LIKE ? OR pag.paypal_order_id LIKE ?)";
    $params[] = $q_like;
    $params[] = $q_like;
    $params[] = $q_like;
}

// 3. Filtro de método de pago
if ($metodo_filtro !== '') {
    $where_conditions[] = "pag.payment_method = ?";
    $params[] = $metodo_filtro;
}

// 4. Filtro de estado
if ($estado_filtro !== '') {
    $where_conditions[] = "pag.status = ?";
    $params[] = $estado_filtro;
}

// Construir WHERE final
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
} else {
    $where_clause = "";
}

$stmt = $pdo->prepare("
    SELECT pag.*, p.id_pago, p.estado as estado_pedido, u.nombre as cliente_nombre, u.email as cliente_email
    FROM pagos pag
    LEFT JOIN pedidos p ON pag.pedido_id = p.id
    LEFT JOIN usuarios u ON p.user_id = u.id
    $where_clause
    ORDER BY COALESCE(pag.captured_at, pag.created_at, '1970-01-01') DESC
");
$stmt->execute($params);
$pagos = $stmt->fetchAll();

// --- CÁLCULO DE ANALÍTICA ---
$total_ingresos = 0;
$completados = 0;
$pendientes = 0;
$refunded = 0;
$chargebacks = 0;

foreach ($pagos as $pago) {
    $est = strtolower(trim($pago['status'] ?? ''));
    $total_ingresos += (float)$pago['amount'];

    if ($est === 'completed') $completados++;
    elseif ($est === 'pending') $pendientes++;
    elseif ($est === 'refunded') $refunded++;
    if (!empty($pago['chargeback'])) $chargebacks++;
}

include 'includes/colors.php';
include 'includes/header.php';
?>

<style>
    :root {
        --primary-color: #2c3e50;
        --accent-color: #e74c3c;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --danger-color: #c62828;
        --bizum-color: #00aeb1;
    }

    .container {
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 15px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.03);
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.02);
        transition: transform 0.2s;
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
    .filter-bar button {
        background: #000;
        color: white;
        border: none;
        font-weight: 700;
    }
    .filter-bar button:hover {
        background: #1e293b;
        transform: translateY(-2px);
    }

    .table-pro {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
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
    }
    .table-pro th:first-child { border-radius: 12px 0 0 12px; }
    .table-pro th:last-child { border-radius: 0 12px 12px 0; }
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
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-pro td:first-child {
        border-left: 1px solid #f1f5f9;
        border-radius: 16px 0 0 16px;
    }
    .table-pro td:last-child {
        border-right: 1px solid #f1f5f9;
        border-radius: 0 16px 16px 0;
    }

    .badge-status {
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }
    .badge-completed { background: linear-gradient(145deg, #27ae60, #1e8449); color: white; }
    .badge-pending { background: linear-gradient(145deg, #f39c12, #d68910); color: white; }
    .badge-refunded { background: linear-gradient(145deg, #95a5a6, #7f8c8d); color: white; }
    .badge-revision { background: linear-gradient(145deg, #e74c3c, #c0392b); color: white; }
    .badge-created { background: linear-gradient(145deg, #00aeb1, #008c8e); color: white; }

    .badge-metodo {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-paypal { background: #0070ba; color: white; }
    .badge-bizum { background: #00aeb1; color: white; }

    .btn-action {
        padding: 10px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-view { background: #e8f4fd; color: #1565c0; }
    .btn-view:hover { background: #1565c0; color: white; }

    .chargeback-flag {
        background: #ffebee;
        color: #c62828;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        border: 1px solid #ef9a9a;
    }

    @media (max-width: 900px) {
        .table-pro, .table-pro thead, .table-pro tbody, .table-pro tr, .table-pro td {
            display: block !important;
        }
        .table-pro thead { display: none !important; }
        .table-pro tr {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 20px;
        }
        .table-pro td {
            padding: 10px 0;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-pro td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }
    }
</style>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: #000; font-weight: 800; font-size: 1.8rem; margin: 0;">
                💳 Auditoría de Pagos
            </h1>
            <p style="color: #666; font-size: 0.95rem; margin: 5px 0 0 0;">
                <i class="fas fa-shield-alt" style="color: #e74c3c;"></i> Registro blindado PayPal y Bizum
            </p>
        </div>
        <div style="text-align: right;">
            <img src="https://www.camiglobo.com/images/camiglobofavicon.jpg" style="height: 50px; border-radius: 12px;">
        </div>
    </div>

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="stat-card">
            <p><i class="fas fa-euro-sign"></i> Total Recaudado</p>
            <div style="color: #27ae60;"><?php echo formatPrecio($total_ingresos); ?></div>
            <small><?php echo $completados; ?> completados</small>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-check-circle"></i> Completados</p>
            <div style="color: #27ae60;"><?php echo $completados; ?></div>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-clock"></i> Pendientes</p>
            <div style="color: #f39c12;"><?php echo $pendientes; ?></div>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-undo"></i> Refunds</p>
            <div style="color: #95a5a6;"><?php echo $refunded; ?></div>
        </div>
        <div class="stat-card">
            <p><i class="fas fa-exclamation-triangle"></i> Chargebacks</p>
            <div style="color: #c62828;"><?php echo $chargebacks; ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-bar">
        <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; width: 100%; align-items: center;">
            <input type="hidden" name="mes" value="<?php echo $mes_filtro; ?>">
            <input type="hidden" name="anio" value="<?php echo $anio_filtro; ?>">
            <input type="text" name="q" value="<?php echo $q; ?>" placeholder="Buscar por email, nombre o ID..."
                   style="flex: 2; min-width: 180px; padding: 10px 15px; border-radius: 12px; border: 1px solid #eee; font-size: 13px;">
            <select name="metodo" style="flex: 1; min-width: 120px;">
                <option value="">Todos los métodos</option>
                <option value="PAYPAL" <?php echo $metodo_filtro === 'PAYPAL' ? 'selected' : ''; ?>>PayPal</option>
                <option value="BIZUM" <?php echo $metodo_filtro === 'BIZUM' ? 'selected' : ''; ?>>Bizum</option>
            </select>
            <select name="estado" style="flex: 1; min-width: 120px;">
                <option value="">Todos los estados</option>
                <option value="COMPLETED" <?php echo $estado_filtro === 'COMPLETED' ? 'selected' : ''; ?>>Completados</option>
                <option value="PENDING" <?php echo $estado_filtro === 'PENDING' ? 'selected' : ''; ?>>Pendientes</option>
                <option value="REFUNDED" <?php echo $estado_filtro === 'REFUNDED' ? 'selected' : ''; ?>>Refundados</option>
                <option value="CREATED" <?php echo $estado_filtro === 'CREATED' ? 'selected' : ''; ?>>Creados (Bizum)</option>
                <option value="Revision Fraudulenta" <?php echo $estado_filtro === 'Revision Fraudulenta' ? 'selected' : ''; ?>>Revision Fraudulenta</option>
            </select>
            <select name="mes" style="flex: 1; min-width: 100px;">
                <option value="todos" <?php echo $mes_filtro === 'todos' ? 'selected' : ''; ?>>Todos los meses</option>
                <?php
                $meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                for ($m = 1; $m <= 12; $m++):
                    $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                ?>
                    <option value="<?php echo $val; ?>" <?php echo $mes_filtro == $val ? 'selected' : ''; ?>><?php echo $meses_es[$m - 1]; ?></option>
                <?php endfor; ?>
            </select>
            <select name="anio" style="flex: 1; min-width: 100px;">
                <option value="">Todos los años</option>
                <?php for ($a = date('Y'); $a >= 2024; $a--): ?>
                    <option value="<?php echo $a; ?>" <?php echo $anio_filtro == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" style="flex: 1; min-width: 100px;"><i class="fas fa-search"></i> Buscar</button>
        </form>
    </div>

    <!-- Tabla de pagos -->
    <table class="table-pro">
        <thead>
            <tr>
                <th><i class="fas fa-calendar"></i> Fecha</th>
                <th><i class="fas fa-credit-card"></i> Método</th>
                <th><i class="fas fa-hashtag"></i> ID Pago</th>
                <th><i class="fas fa-user"></i> Payer</th>
                <th><i class="fas fa-envelope"></i> Email</th>
                <th><i class="fas fa-box"></i> Pedido</th>
                <th><i class="fas fa-euro-sign"></i> Importe</th>
                <th><i class="fas fa-circle"></i> Estado</th>
                <th><i class="fas fa-shield-alt"></i> Chargeback</th>
                <th><i class="fas fa-cog"></i> Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pagos)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <br>No hay pagos en este período
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($pagos as $pago):
                    $estado = strtolower($pago['status'] ?? '');
                    $metodo = strtoupper($pago['payment_method'] ?? 'PAYPAL');

                    // Badge de estado
                    $badge_class = 'badge-completed';
                    if ($estado === 'pending') $badge_class = 'badge-pending';
                    elseif ($estado === 'refunded') $badge_class = 'badge-refunded';
                    elseif ($estado === 'revision fraudulenta') $badge_class = 'badge-revision';
                    elseif ($estado === 'created') $badge_class = 'badge-created';

                    // Badge de método
                    $metodo_badge = $metodo === 'BIZUM'
                        ? '<span class="badge-metodo badge-bizum"><i class="fas fa-mobile-alt"></i> Bizum</span>'
                        : '<span class="badge-metodo badge-paypal"><i class="fab fa-paypal"></i> PayPal</span>';

                    // Fecha formateada
                    $fecha = $pago['captured_at'] ? date('d/m/Y H:i', strtotime($pago['captured_at'])) : '<span style="color: #f39c12;">Pendiente</span>';
                ?>
                    <tr>
                        <td data-label="Fecha">
                            <?php echo $fecha; ?>
                        </td>
                        <td data-label="Método">
                            <?php echo $metodo_badge; ?>
                        </td>
                        <td data-label="ID Pago">
                            <small><?php echo h($pago['paypal_order_id'] ?? '-'); ?></small>
                        </td>
                        <td data-label="Payer">
                            <strong><?php echo h($pago['payer_name'] ?? 'Anónimo'); ?></strong>
                        </td>
                        <td data-label="Email">
                            <small><?php echo h($pago['payer_email'] ?? '-'); ?></small>
                        </td>
                        <td data-label="Pedido">
                            <a href="admin_pedidos.php?q=<?php echo urlencode($pago['id_pago'] ?? ''); ?>"
                               style="color: #1565c0; font-weight: 700; text-decoration: none;">
                                #<?php echo h($pago['id_pago'] ?? '?'); ?>
                            </a>
                        </td>
                        <td data-label="Importe">
                            <strong style="color: #27ae60;"><?php echo formatPrecio($pago['amount']); ?></strong>
                        </td>
                        <td data-label="Estado">
                            <span class="badge-status <?php echo $badge_class; ?>">
                                <?php echo strtoupper(h($pago['status'])); ?>
                            </span>
                        </td>
                        <td data-label="Chargeback">
                            <?php if (!empty($pago['chargeback'])): ?>
                                <span class="chargeback-flag">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo h($pago['chargeback_reason'] ?? 'Chargeback'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #bdc3c7;">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acción">
                            <a href="ver_detalles.php?id=<?php echo $pago['pedido_id']; ?>"
                               class="btn-action btn-view">
                                <i class="fas fa-eye"></i> VER
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
