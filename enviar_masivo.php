<?php
require_once 'includes/config.php';

if (!esAdmin()) { http_response_code(403); exit; }

header('Content-Type: application/json');

// --- CSRF ---
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['ok' => false, 'msg' => 'Token de seguridad inválido.']);
    exit;
}

$tipo    = $_POST['tipo']    ?? '';
$asunto  = trim(preg_replace('/[\r\n]/', '', $_POST['asunto']  ?? ''));
if (empty($asunto) || mb_strlen($asunto) > 200) {
    echo json_encode(['ok' => false, 'msg' => 'Asunto inválido o demasiado largo.']);
    exit;
}
$mensaje = trim($_POST['mensaje'] ?? '');

if (!in_array($tipo, ['clientes', 'newsletter'])) {
    echo json_encode(['ok' => false, 'msg' => 'Tipo de lista no válido.']);
    exit;
}
if (empty($asunto) || empty($mensaje)) {
    echo json_encode(['ok' => false, 'msg' => 'El asunto y el mensaje son obligatorios.']);
    exit;
}

// --- RECOGER DESTINATARIOS ---
$emailsSeleccionados = [];
if (!empty($_POST['destinatarios'])) {
    $emailsSeleccionados = json_decode($_POST['destinatarios'], true) ?? [];
    $emailsSeleccionados = array_filter($emailsSeleccionados, function($e) {
        return filter_var($e, FILTER_VALIDATE_EMAIL);
    });
}

if (!empty($emailsSeleccionados)) {
    $placeholders = implode(',', array_fill(0, count($emailsSeleccionados), '?'));
    if ($tipo === 'clientes') {
        $stmt = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE email IN ($placeholders) ORDER BY id ASC");
    } else {
        $stmt = $pdo->prepare("SELECT email, '' AS nombre, token_baja FROM newsletter WHERE email IN ($placeholders) ORDER BY id ASC");
    }
    $stmt->execute(array_values($emailsSeleccionados));
    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else if ($tipo === 'clientes') {
    $stmt = $pdo->query("SELECT email, nombre FROM usuarios ORDER BY id ASC");
    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT email, '' AS nombre, token_baja FROM newsletter ORDER BY id ASC");
    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($destinatarios)) {
    echo json_encode(['ok' => false, 'msg' => 'No hay destinatarios en esta lista.']);
    exit;
}

// --- ENVÍO ---
$enviados = 0;
$errores  = 0;
$errLista = [];

// Aumentar tiempo de ejecución para listas grandes
set_time_limit(300);

$mensajeHtml = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

foreach ($destinatarios as $dest) {
    $nombre = !empty($dest['nombre']) ? htmlspecialchars($dest['nombre']) : 'Cliente';
    $saludo = ($tipo === 'clientes') ? "<p style='font-size:16px;'>Hola, <strong>$nombre</strong> 👋</p>" : "<p style='font-size:16px;'>Hola 👋</p>";

    $body = "
        $saludo
        <div style='font-size:15px; line-height:1.8; color:#333;'>
            $mensajeHtml
        </div>
    ";

    $unsub = ($tipo === 'newsletter') ? ($dest['token_baja'] ?? '') : '';
    try {
        enviarEmail($dest['email'], $asunto, $body, '#111', $unsub);
        $enviados++;
    } catch (Exception $e) {
        $errores++;
        $errLista[] = $dest['email'];
        error_log("Error envío masivo a {$dest['email']}: " . $e->getMessage());
    }

    // Pequeña pausa para no saturar el servidor SMTP
    if ($enviados % 10 === 0) usleep(500000); // 0.5s cada 10 mails
}

$msg = "✓ Enviados: <strong>$enviados</strong>";
if (!empty($emailsSeleccionados)) {
    $msg .= " (seleccionados)";
}
if ($errores > 0) {
    $msg .= " | ✗ Fallidos: <strong>$errores</strong>";
}

echo json_encode(['ok' => true, 'msg' => $msg, 'enviados' => $enviados, 'errores' => $errores]);