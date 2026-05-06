<?php
/**
 * ARCHIVO: save-products.php
 * FUNCIÓN: Sincronización maestra del catálogo (Insert, Update, Delete).
 * ACTUALIZACIÓN: Generación de IDs únicos, validación de integridad y blindaje estructural.
 */

require_once 'includes/config.php';

// Respuesta siempre en JSON
header('Content-Type: application/json');

// 1. --- SEGURIDAD DE RANGO ALTO ---
if (!esAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requiere nivel Administrador.']);
    exit;
}

// 2. --- CAPTURA DE PAYLOAD ---
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

// VALIDACIÓN QA: Estructura de datos
if (!$data || !isset($data['products']) || !is_array($data['products'])) {
    echo json_encode(['success' => false, 'error' => 'Stuctura de datos corrupta o vacía.']);
    exit;
}

try {
    $pdo->beginTransaction();

$total_actual = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();

    // --- A. IDENTIFICAR PRODUCTOS A ELIMINAR (Lógica de Sincronización) ---
    $ids_recibidos = [];
    foreach ($data['products'] as $p) {
        // Solo recolectamos IDs que ya existen (los que no empiezan por N-)
        if (isset($p['id']) && strpos($p['id'], 'N-') !== 0) {
            $ids_recibidos[] = $p['id'];
        }
    }
$num_recibidos = count($ids_recibidos);
$num_a_borrar = $total_actual - $num_recibidos;
$confirmacion_manual = $data['confirmar_borrado_masivo'] ?? false;

// Si hay más de 5 productos y vas a borrar más de la mitad, bloqueamos:
if ($total_actual > 5 && $num_a_borrar > ($total_actual / 2)) {
    if (!$confirmacion_manual) {
        throw new Exception("SISTEMA DE SEGURIDAD: Intento de borrado masivo detectado ($num_a_borrar productos). Confirma la acción manualmente.");
    }
}
    if (!empty($ids_recibidos)) {
        // Borramos lo que el admin quitó de la lista
        $placeholders = implode(',', array_fill(0, count($ids_recibidos), '?'));
        $stmtDel = $pdo->prepare("DELETE FROM productos WHERE id NOT IN ($placeholders)");
        $stmtDel->execute($ids_recibidos);
    } else {
    // Caso extremo: el editor viene vacío
    if (empty($data['products']) && $confirmacion_manual) {
        $pdo->exec("DELETE FROM productos");
    } elseif (empty($data['products']) && !$confirmacion_manual && $total_actual > 0) {
         throw new Exception("SISTEMA DE SEGURIDAD: No se puede vaciar la tienda por completo sin confirmación explícita.");
    }
}

    // --- B. PREPARACIÓN DE SENTENCIAS (Optimizadas) ---
    // Corregido: Ahora el INSERT incluye el ID ya que es VARCHAR y no auto-inc
    $sql_insert = "INSERT INTO productos (id, nombre, descripcion, precio, imagen_url, imagenes_galeria, video_delante, video_detras, video_como_se_hace, categoria, destacado)
                   VALUES (:id, :nom, :des, :pre, :img, :gal, :vdel, :vdet, :vcomo, :cat, :fea)";

    $sql_update = "UPDATE productos SET
                    nombre = :nom,
                    descripcion = :des,
                    precio = :pre,
                    imagen_url = :img,
                    imagenes_galeria = :gal,
                    video_delante = :vdel,
                    video_detras = :vdet,
                    video_como_se_hace = :vcomo,
                    categoria = :cat,
                    destacado = :fea
                   WHERE id = :id";

    $stmtIns = $pdo->prepare($sql_insert);
    $stmtUpd = $pdo->prepare($sql_update);

    // --- C. PROCESAR CADA PRODUCTO ---
    foreach ($data['products'] as $p) {
        
        // 1. Limpieza y validación de campos
        $nombre = trim($p['name'] ?? 'Producto sin nombre');
        $precio = max(0, (float)($p['price'] ?? 0)); // Evitamos precios negativos
        $cat  = strtolower(trim($p['category'] ?? 'varios'));
        $img    = trim($p['image'] ?? '');
        $desc   = trim($p['description'] ?? '');
        $feat   = (!empty($p['featured']) && $p['featured'] !== false) ? 1 : 0;

        // 2. Procesar Galería (JS Array -> String CSV)
        $galeria_str = "";
        if (isset($p['gallery']) && is_array($p['gallery'])) {
            $galeria_str = implode(',', array_filter(array_map('trim', $p['gallery'])));
        }

        // 3. Procesar vídeos
        $video_delante = trim($p['videoDelante'] ?? '');
        $video_detras = trim($p['videoDetras'] ?? '');
        $video_como_se_hace = trim($p['videoComoSeHace'] ?? '');

        // 4. Identificar si es NUEVO o EXISTENTE
        if (strpos($p['id'], 'N-') === 0) {
            // --- PRODUCTO NUEVO ---
            // Generamos un ID alfanumérico único para la Primary Key VARCHAR
            $nuevo_id_real = 'prod_' . bin2hex(random_bytes(4)) . '_' . time();
            
            $stmtIns->execute([
                ':id'    => $nuevo_id_real,
                ':nom'   => $nombre,
                ':des'   => $desc,
                ':pre'   => $precio,
                ':img'   => $img,
                ':gal'   => $galeria_str,
                ':vdel'  => $video_delante,
                ':vdet'  => $video_detras,
                ':vcomo' => $video_como_se_hace,
                ':cat'   => $cat,
                ':fea'   => $feat
            ]);
        } else {
            // --- ACTUALIZAR EXISTENTE ---
            $stmtUpd->execute([
                ':id'    => $p['id'],
                ':nom'   => $nombre,
                ':des'   => $desc,
                ':pre'   => $precio,
                ':img'   => $img,
                ':gal'   => $galeria_str,
                ':vdel'  => $video_delante,
                ':vdet'  => $video_detras,
                ':vcomo' => $video_como_se_hace,
                ':cat'   => $cat,
                ':fea'   => $feat
            ]);
        }
    }

    // --- D. CIERRE DE OPERACIÓN ---
    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Catálogo sincronizado globalmente con éxito.'
    ]);

} catch (Exception $e) {
    // Si algo falla, deshacemos todo para no dejar la DB a medias
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("CRITICAL ERROR save-products: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Fallo en la base de datos. Los cambios no se han aplicado.'
    ]);
}