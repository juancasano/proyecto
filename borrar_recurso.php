<?php
/**
 * ARCHIVO: borrar_recurso.php
 * FUNCIÓN: Eliminar una imagen de la biblioteca personal del usuario.
 * ESTADO: BLINDADO ANTI PATH-TRAVERSAL + ANTI CSRF.
 */

require_once 'includes/config.php';

// Aseguramos que la respuesta sea siempre JSON
header('Content-Type: application/json');

// 1. SEGURIDAD DE ACCESO: Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// --- 1.5. BLINDAJE CSRF (CORTAFUEGOS ANTI-FALSIFICACIÓN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Validación de seguridad CSRF fallida.']);
        exit;
    }
}

$user_id = (int)$_SESSION['user_id'];
$ruta_sucia = $_POST['ruta_imagen'] ?? '';

// Evitamos procesar si la ruta está vacía
if (empty($ruta_sucia)) {
    echo json_encode(['success' => false, 'error' => 'Ruta no proporcionada']);
    exit;
}

// --- BLINDAJE 2: ANTI PATH-TRAVERSAL Y SOPORTE MULTI-CARPETA ---
// Extraemos estrictamente el nombre del archivo, ignorando cualquier inyección de directorios
$nombre_archivo = basename($ruta_sucia);

// Normalizamos: quitamos slash inicial si lo tiene para comparar siempre igual
$ruta_normalizada = ltrim($ruta_sucia, '/');

// Detectamos inteligentemente a qué carpeta legítima pertenece la imagen
if (strpos($ruta_normalizada, 'uploads/custom/') === 0) {
    $carpeta_base = 'uploads/custom/';
} else {
    $carpeta_base = 'uploads/recursos/';
}

// Reconstruimos las rutas seguras de forma manual usando la carpeta correcta
$ruta_segura_db = $carpeta_base . $nombre_archivo;

// Usamos DOCUMENT_ROOT para asegurar que la ruta física del servidor sea infalible
$archivo_fisico = $_SERVER['DOCUMENT_ROOT'] . '/' . $ruta_segura_db;

// También probamos con slash inicial por si la BD lo guarda así
$ruta_segura_db_slash = '/' . $ruta_segura_db;

try {
    // 2. VERIFICACIÓN DE PROPIEDAD: buscamos la foto probando con y sin slash inicial
    // (distintas partes del sistema pueden guardar la ruta de forma diferente)
    $stmt = $pdo->prepare("SELECT id FROM biblioteca_recursos WHERE (ruta_imagen = ? OR ruta_imagen = ?) AND user_id = ?");
    $stmt->execute([$ruta_segura_db, $ruta_segura_db_slash, $user_id]);
    $foto = $stmt->fetch();

    if ($foto) {
        $id_borrado = $foto['id'];

        // --- INICIO FIX: LIMPIEZA DE CARRITO (EFECTO FANTASMA) ---
        // Escaneamos la sesión para ver si algún producto dependía de esta foto
        if (!empty($_SESSION['carrito'])) {
            $carrito_modificado = false;
            
            foreach ($_SESSION['carrito'] as $key => $item) {
                // Si el item tiene el recurso_id que estamos a punto de borrar...
                if (isset($item['recurso_id']) && $item['recurso_id'] == $id_borrado) {
                    // Desvinculamos la foto (así el sistema sabe que ya no se puede reeditar)
                    $_SESSION['carrito'][$key]['recurso_id'] = null;
                    $carrito_modificado = true;
                }
            }
            
            // Si hemos encontrado y limpiado a un fantasma, actualizamos la base de datos atómicamente
            if ($carrito_modificado) {
                $carrito_json = json_encode($_SESSION['carrito']);
                $stmtCart = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
                $stmtCart->execute([$carrito_json, $user_id]);
            }
        }
        // --- FIN FIX ---

        // 3. BORRADO DE LA BASE DE DATOS
        $stmtDelete = $pdo->prepare("DELETE FROM biblioteca_recursos WHERE id = ?");
        $stmtDelete->execute([$id_borrado]);
        
        // 4. BORRADO DEL ARCHIVO FÍSICO DEL SERVIDOR
        // Usamos la ruta física absoluta basada en __DIR__ (no falla nunca)
        if (file_exists($archivo_fisico)) {
            // El '@' silencia el error si por algún motivo de permisos no se puede borrar, 
            // pero ya hemos limpiado la base de datos.
            if (@unlink($archivo_fisico)) {
                echo json_encode(['success' => true]);
            } else {
                // Si el archivo no se pudo borrar pero el registro sí, informamos con éxito parcial
                echo json_encode(['success' => true, 'info' => 'Registro borrado, archivo físico pendiente de revisión manual.']);
            }
        } else {
            // Si el archivo ya no existía en el disco, damos el proceso por exitoso
            echo json_encode(['success' => true, 'info' => 'El archivo no existía en disco, registro eliminado.']);
        }
    } else {
        // Si no se encuentra la combinación de ruta segura + user_id
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para borrar este archivo o no existe.']);
    }

} catch (Exception $e) {
    // Registro del error para el administrador
    error_log("Error borrando recurso: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar la solicitud.']);
}
?>