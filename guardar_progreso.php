<?php
/**
 * ARCHIVO: guardar_progreso.php
 * FUNCIÓN: Auto-save del diseño en progreso en la DB para sincronización entre dispositivos.
 */

require_once 'includes/config.php';

header('Content-Type: application/json');

// Solo POST y solo usuarios logueados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado.']);
    exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'CSRF inválido.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Recoger y validar el payload
$raw = $_POST['diseno_json'] ?? '';
if (empty($raw) || strlen($raw) > 2000000) { // máx 2MB
    echo json_encode(['success' => false, 'error' => 'Payload inválido.']);
    exit;
}

// Verificar que es JSON válido
$parsed = json_decode($raw, true);
if (!$parsed) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET diseno_en_progreso = ? WHERE id = ?");
    $stmt->execute([$raw, $user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error guardando progreso diseño: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}