<?php
/**
 * ARCHIVO: upload.php
 * FUNCIÓN: Subida segura de imágenes para productos (Admin).
 * COMPATIBILIDAD: Total con admin_productos.php y base de datos.
 */

require_once 'includes/config.php';

// Aseguramos que la respuesta sea siempre JSON para que admin_productos.php la entienda
header('Content-Type: application/json');

// --- 1. SEGURIDAD: SOLO ADMIN ---
// Si no es admin, cortamos y devolvemos error JSON para que el panel lo muestre
if (!esAdmin()) { 
    echo json_encode(['success' => false, 'error' => 'No autorizado']); 
    exit; 
}

// --- 2. VALIDACIÓN DE ENTRADA ---
// Soporte para imágenes y vídeos
// Comprobar si hay error de PHP en la subida (tamaño, permisos, etc.)
if (isset($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_OK && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
    $errores_php = [
        UPLOAD_ERR_INI_SIZE   => 'El vídeo supera upload_max_filesize en php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'El vídeo supera MAX_FILE_SIZE del formulario',
        UPLOAD_ERR_PARTIAL    => 'La subida fue interrumpida',
        UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal en el servidor',
        UPLOAD_ERR_CANT_WRITE => 'No se puede escribir en el disco',
        UPLOAD_ERR_EXTENSION  => 'Extensión de PHP bloqueó la subida',
    ];
    $msg = $errores_php[$_FILES['video']['error']] ?? 'Error de subida #' . $_FILES['video']['error'];
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
    // Subida de vídeo
    $file = $_FILES['video'];
    $directorio = "uploads/";

    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['mp4', 'webm', 'ogg'];

    if (!in_array($ext, $permitidos)) {
        echo json_encode(['success' => false, 'error' => 'Formato de vídeo no válido (MP4, WebM, OGG)']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    // Los MP4 pueden reportarse como application/octet-stream o video/quicktime según el servidor
    $mimes_permitidos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'application/octet-stream', 'application/mp4'];

    if (!in_array($mime, $mimes_permitidos)) {
        echo json_encode(['success' => false, 'error' => 'Archivo de vídeo no reconocido (MIME: ' . $mime . ')']);
        exit;
    }

    $nombreNuevo = 'video_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $rutaFinal = $directorio . $nombreNuevo;

    if (move_uploaded_file($file['tmp_name'], $rutaFinal)) {
        echo json_encode(['success' => true, 'url' => $rutaFinal]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fallo al mover el archivo de vídeo']);
    }
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Error en la subida']);
    exit;
}

$file = $_FILES['foto'];
$directorio = "uploads/";

// Crear directorio si no existe (Permisos 0755 son más seguros y compatibles que 0777)
if (!is_dir($directorio)) {
    mkdir($directorio, 0755, true);
}

// --- 3. FILTROS DE SEGURIDAD (EL BLINDAJE) ---
// Lista blanca real: Solo permitimos extensiones de imagen
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Formato no válido (Solo imágenes)']);
    exit;
}

// Validación profunda: Comprobar si el archivo es realmente una imagen (MIME Type)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mime, $mimes_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Archivo corrupto o falso']);
    exit;
}

// --- 4. PROCESADO Y GUARDADO ---
// Generamos nombre único pero limpio (mantenemos 'camiglobo_' si te gusta por marca, o usamos 'prod_')
// Usamos random_bytes para que sea matemáticamente imposible de adivinar (seguridad)
$nombreNuevo = 'prod_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
$rutaFinal = $directorio . $nombreNuevo;

if (move_uploaded_file($file['tmp_name'], $rutaFinal)) {
    // ÉXITO: Devolvemos la URL tal cual la espera admin_productos.php
    echo json_encode(['success' => true, 'url' => $rutaFinal]);
} else {
    echo json_encode(['success' => false, 'error' => 'Fallo al mover el archivo']);
}
?>