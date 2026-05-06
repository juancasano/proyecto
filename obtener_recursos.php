<?php
/**
 * ARCHIVO: obtener_recursos.php
 * FUNCIÓN: Listar las imágenes subidas por el usuario para mostrarlas en su biblioteca personal.
 */

require_once 'includes/config.php';

// Aseguramos que la respuesta sea siempre JSON
header('Content-Type: application/json');

// 1. BLOQUEO DE SEGURIDAD: Si no hay sesión activa, no devolvemos nada.
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Forzamos a entero para evitar inyección de código.
$user_id = (int)$_SESSION['user_id']; 

try {
    // 2. CONSULTA PROTEGIDA: Traemos las últimas 20 fotos exclusivas de este usuario.
    // Usamos el id DESC para que las fotos más nuevas aparezcan primero en la galería.
    $stmt = $pdo->prepare("SELECT ruta_imagen FROM biblioteca_recursos WHERE user_id = ? ORDER BY id DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. VALIDACIÓN DE RUTAS: Verificamos que el archivo realmente esté en el servidor.
    $resultado = [];
    foreach ($recursos as $res) {
        // Obtenemos la ruta guardada (ej: uploads/recursos/user_1_...png)
        $ruta_relativa = $res['ruta_imagen'];
        
        // Comprobamos la ruta física absoluta en el disco para no enviar enlaces rotos.
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $ruta_relativa)) {
            $resultado[] = $ruta_relativa;
        }
    }

    // 4. RESPUESTA: Enviamos el array de rutas al estudio de diseño.
    echo json_encode($resultado);

} catch (Exception $e) {
    // Registramos el error pero devolvemos un JSON válido para no romper el JS del frontend.
    error_log("Error en biblioteca: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>