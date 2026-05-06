<?php
require_once 'includes/config.php';

// Aseguramos que la respuesta sea JSON
header('Content-Type: application/json');

// --- 1. SEGURIDAD DE ACCESO: SOLO USUARIOS LOGUEADOS ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Por seguridad, debes iniciar sesión para subir tus propias imágenes.'
    ]);
    exit;
}

// --- 1.5. BLINDAJE CSRF (NUEVO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Validación de seguridad CSRF fallida.']);
        exit;
    }
}

$user_id = $_SESSION['user_id'];

// --- 2. CONTROL DE INUNDACIÓN (ANTI-DoS) - VALIDACIÓN CONTRA BASE DE DATOS ---
$limite_subidas = 15; // Máximo 15 imágenes por hora

try {
    // Consultamos la realidad: ¿Cuántas subidas lleva este ID de usuario en la última hora?
    $stmt_limit = $pdo->prepare("SELECT COUNT(*) FROM biblioteca_recursos WHERE user_id = ? AND fecha > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt_limit->execute([$user_id]);
    $subidas_reales = (int)$stmt_limit->fetchColumn();

    if ($subidas_reales >= $limite_subidas) {
        echo json_encode([
            'success' => false, 
            'error' => 'Límite de seguridad alcanzado (15 subidas/hora). Inténtalo de nuevo más tarde.'
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Fallo Rate Limit DB: " . $e->getMessage());
    exit(json_encode(['success' => false, 'error' => 'Error de validación de seguridad.']));
}

// --- 3. PROCESAMIENTO Y BLINDAJE DE LA SUBIDA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    
    $file = $_FILES['foto'];

    // Validación de errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Error al recibir el archivo.']);
        exit;
    }

    // Validación de tamaño (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'La imagen es demasiado pesada (máx 5MB).']);
        exit;
    }

    // Validación de tipo real (MIME) vía Magic Bytes
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mime_real, $permitidos)) {
        echo json_encode(['success' => false, 'error' => 'Formato no permitido. Solo JPG, PNG o WEBP.']);
        exit;
    }

    // Validación gráfica básica
    if (!getimagesize($file['tmp_name'])) {
        echo json_encode(['success' => false, 'error' => 'El archivo no es una imagen válida.']);
        exit;
    }

    // Rutas absolutas seguras (Sin depender de DOCUMENT_ROOT)
    $dir_recursos = __DIR__ . '/uploads/recursos/';
    if (!file_exists($dir_recursos)) {
        // BLINDAJE 1: Permisos 0755 en lugar de 0777
        mkdir($dir_recursos, 0755, true);
    }

    $extension = $permitidos[$mime_real];
    // BLINDAJE 2: Entropía extra en el nombre para evitar colisiones
    $nombre_limpio = 'user_' . $user_id . '_' . uniqid(rand(), true) . '.' . $extension;
    $ruta_destino = $dir_recursos . $nombre_limpio;
    $url_publica = 'uploads/recursos/' . $nombre_limpio;

    // BLINDAJE 3: RE-RENDERIZADO GD (Destruye virus en metadatos / Polyglots)
    $image_resource = null;
    switch ($mime_real) {
        case 'image/jpeg': $image_resource = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $image_resource = @imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $image_resource = @imagecreatefromwebp($file['tmp_name']); break;
    }

    if (!$image_resource) {
        echo json_encode(['success' => false, 'error' => 'La imagen está corrupta o contiene datos no permitidos.']);
        exit;
    }

    // Mantener las transparencias si es PNG o WEBP
    if ($mime_real === 'image/png' || $mime_real === 'image/webp') {
        imagealphablending($image_resource, false);
        imagesavealpha($image_resource, true);
    }

    // Guardar la imagen completamente nueva y limpia
    $guardado = false;
    switch ($extension) {
        case 'jpg':  $guardado = imagejpeg($image_resource, $ruta_destino, 90); break;
        case 'png':  $guardado = imagepng($image_resource, $ruta_destino, 8); break;
        case 'webp': $guardado = imagewebp($image_resource, $ruta_destino, 90); break;
    }
    
    // Liberar memoria del servidor
    imagedestroy($image_resource);

    if ($guardado) {
        
        // BLINDAJE 4: Quitar permisos de ejecución al archivo guardado
        chmod($ruta_destino, 0644);
        
        try {
            // Guardamos en la base de datos
            $sql = "INSERT INTO biblioteca_recursos (user_id, ruta_imagen, fecha) VALUES (?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $url_publica]);

            echo json_encode(['success' => true, 'url' => $url_publica]);

            // ── LIMPIEZA: borrar recursos de usuarios sin pedidos en 2+ meses ──
            // Se ejecuta de forma silenciosa con 1 de cada 10 subidas (para no sobrecargar)
            if (rand(1, 10) === 1) {
                try {
                    // Usuarios que no han hecho ningún pedido en los últimos 2 meses
                    $stmtViejos = $pdo->query("
                        SELECT br.id, br.user_id, br.ruta_imagen
                        FROM biblioteca_recursos br
                        WHERE br.fecha < DATE_SUB(NOW(), INTERVAL 2 MONTH)
                        AND br.user_id NOT IN (
                            SELECT DISTINCT user_id FROM pedidos
                            WHERE fecha > DATE_SUB(NOW(), INTERVAL 2 MONTH)
                        )
                        AND br.ruta_imagen LIKE 'uploads/recursos/%'
                        LIMIT 50
                    ");
                    $viejos = $stmtViejos->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($viejos)) {
                        $ids = array_column($viejos, 'id');
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $pdo->prepare("DELETE FROM biblioteca_recursos WHERE id IN ($placeholders)")
                            ->execute($ids);

                        foreach ($viejos as $viejo) {
                            $ruta_fisica = __DIR__ . '/' . $viejo['ruta_imagen'];
                            if (file_exists($ruta_fisica)) {
                                @unlink($ruta_fisica);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error limpieza recursos inactivos: " . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            // Si falla la DB, borramos la imagen física para no acumular basura
            @unlink($ruta_destino);
            error_log("Error DB Recursos: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error de registro en base de datos.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error procesando la imagen en el servidor.']);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Petición no válida.']);
}
?>