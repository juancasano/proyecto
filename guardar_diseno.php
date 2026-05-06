<?php
/**
 * ARCHIVO: guardar_diseno.php
 * FUNCIÓN: Procesamiento seguro de lienzos Fabric.js y persistencia en carrito.
 * ACTUALIZACIÓN: Precio con extras (doble cara + mangas/nuca), espalda PNG, logos extras.
 * FIX: Guarda recurso_id en carrito para poder recargar el diseño desde carrito.php
 * ESTADO: ULTRA ACTIVADO - BLINDADO.
 */

require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ─── 0. BLINDAJE CSRF (CORTAFUEGOS ANTI-FALSIFICACIÓN) ───────────────────
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        error_log("Ataque CSRF bloqueado en guardar_diseno.php. IP: " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => false, 'error' => 'Validación de seguridad CSRF fallida.']);
        exit;
    }

    // ─── BLINDAJE ANTI-BOTS: RATE LIMITING POR IP ────────────────────────────
    $ip_usuario = $_SERVER['REMOTE_ADDR'];
    $stmt_limit = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE ip = ? AND action = 'SAVE_DESIGN' AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt_limit->execute([$ip_usuario]);
    $subidas_recientes = (int)$stmt_limit->fetchColumn();

    if ($subidas_recientes >= 20) {
        error_log("Bloqueo Anti-Spam: IP $ip_usuario intentó saturar el servidor de diseños.");
        echo json_encode(['success' => false, 'error' => 'Por seguridad, has alcanzado el límite de diseños por hora. Inténtalo más tarde.']);
        exit;
    }

    $user_log_id = $_SESSION['user_id'] ?? 0;
    $stmt_log    = $pdo->prepare("INSERT INTO audit_log (user_id, action, details, ip) VALUES (?, 'SAVE_DESIGN', 'Diseño temporal generado', ?)");
    $stmt_log->execute([$user_log_id, $ip_usuario]);

    // ─── 1. CAPTURA Y SANEAMIENTO ─────────────────────────────────────────────
    $max_length = 15000000;
    if (isset($_POST['img_base64']) && strlen($_POST['img_base64']) > $max_length) {
        error_log("Bloqueo Anti-DoS: Payload Base64 demasiado grande desde IP " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => false, 'error' => 'La imagen es demasiado pesada para el servidor.']);
        exit;
    }

    $img   = $_POST['img_base64'] ?? '';
    $tipo  = substr(trim($_POST['producto_tipo'] ?? 'camiseta'), 0, 50);
    $color = substr(trim($_POST['color'] ?? 'N/A'), 0, 50);
    $talla = substr(trim($_POST['talla'] ?? 'M'), 0, 50);
    $notas = substr(strip_tags($_POST['notas'] ?? ''), 0, 500);

    // ─── 3. VALIDACIÓN IMAGEN FRONTAL ─────────────────────────────────────────
    if (empty($img) || strlen($img) > 10485760 || strpos($img, 'data:image/png;base64,') === false) {
        echo json_encode(['success' => false, 'error' => 'Los datos de la imagen son inválidos, están corruptos o exceden el tamaño.']);
        exit;
    }

    // ─── 4. DIRECTORIOS SEGUROS ───────────────────────────────────────────────
    $dir_base = __DIR__ . '/uploads/custom/';
    if (!file_exists($dir_base)) {
        if (!mkdir($dir_base, 0755, true)) {
            error_log("ERROR CRÍTICO: No se pudo crear el directorio de diseños en $dir_base");
            echo json_encode(['success' => false, 'error' => 'Error de infraestructura en el servidor.']);
            exit;
        }
    }

    // ─── 5. LIMPIEZA DE ARCHIVOS ──────────────────────────────────────────────
    $archivos = glob($dir_base . "*.png");
    if (count($archivos) > 30) {
        try {
            // Rutas de pedidos ACTIVOS (no entregados, o entregados hace menos de 6 meses)
            $stmtRutas = $pdo->query("
                SELECT pd.imagen_custom, pd.imagen_espalda
                FROM pedidos_detalle pd
                JOIN pedidos p ON p.id = pd.pedido_id
                WHERE (pd.imagen_custom IS NOT NULL OR pd.imagen_espalda IS NOT NULL)
                AND NOT (
                    LOWER(p.estado) = 'entregado'
                    AND p.fecha < DATE_SUB(NOW(), INTERVAL 6 MONTH)
                )
                UNION
                SELECT ruta_imagen, NULL FROM biblioteca_recursos
                WHERE ruta_imagen LIKE 'uploads/custom/%'
            ");
            $rutasEnUso = [];
            while ($row = $stmtRutas->fetch(PDO::FETCH_NUM)) {
                if ($row[0]) $rutasEnUso[basename($row[0])] = true;
                if ($row[1]) $rutasEnUso[basename($row[1])] = true;
            }

            // Añadir también los logos_extras de pedidos activos
            $stmtExtras = $pdo->query("
                SELECT pd.logos_extras
                FROM pedidos_detalle pd
                JOIN pedidos p ON p.id = pd.pedido_id
                WHERE pd.logos_extras IS NOT NULL
                AND NOT (
                    LOWER(p.estado) = 'entregado'
                    AND p.fecha < DATE_SUB(NOW(), INTERVAL 6 MONTH)
                )
            ");
            while ($row = $stmtExtras->fetch(PDO::FETCH_ASSOC)) {
                $extras = json_decode($row['logos_extras'], true);
                if (is_array($extras)) {
                    foreach ($extras as $ruta) {
                        $rutasEnUso[basename($ruta)] = true;
                    }
                }
            }

            // Rutas en carritos activos de usuarios
            $stmtCarritos = $pdo->query("SELECT carrito_guardado FROM usuarios WHERE carrito_guardado IS NOT NULL");
            while ($row = $stmtCarritos->fetch(PDO::FETCH_ASSOC)) {
                $carrito = json_decode($row['carrito_guardado'], true);
                if (!is_array($carrito)) continue;
                foreach ($carrito as $item) {
                    if (!empty($item['imagen_personalizada'])) $rutasEnUso[basename($item['imagen_personalizada'])] = true;
                    if (!empty($item['imagen_espalda']))       $rutasEnUso[basename($item['imagen_espalda'])]       = true;
                    if (!empty($item['logos_extras']) && is_array($item['logos_extras'])) {
                        foreach ($item['logos_extras'] as $ruta) {
                            $rutasEnUso[basename($ruta)] = true;
                        }
                    }
                }
            }

            // Borrar: huérfanos con +24h O de pedidos entregados hace +6 meses
            foreach ($archivos as $archivo) {
                $nombre = basename($archivo);
                $edad   = time() - filemtime($archivo);
                if (!isset($rutasEnUso[$nombre]) && $edad > 86400) {
                    @unlink($archivo);
                }
            }
        } catch (Exception $e) {
            error_log("Error limpieza uploads/custom: " . $e->getMessage());
        }
    }

    // ─── 6. GUARDAR IMAGEN FRONTAL ────────────────────────────────────────────
    $img_data     = str_replace(['data:image/png;base64,', ' '], ['', '+'], $img);
    $decoded_data = base64_decode($img_data, true);

    if (!$decoded_data) {
        echo json_encode(['success' => false, 'error' => 'La codificación de la imagen es errónea.']);
        exit;
    }
    if (!getimagesizefromstring($decoded_data)) {
        error_log("ATAQUE DETECTADO: Intento de subir archivo no-imagen base64.");
        echo json_encode(['success' => false, 'error' => 'El archivo enviado no es una imagen válida.']);
        exit;
    }

    $token_unid        = bin2hex(random_bytes(8));
    $filename          = 'design_' . $token_unid . '_' . time() . '.png';
    $filepath_completo = $dir_base . $filename;
    $filepath_relativo = 'uploads/custom/' . $filename;

    if (!file_put_contents($filepath_completo, $decoded_data)) {
        error_log("Fallo de escritura en: $filepath_completo. Revisar permisos de carpeta.");
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el diseño por falta de permisos en el taller.']);
        exit;
    }
    chmod($filepath_completo, 0644);

    // ─── 7. GUARDAR IMAGEN ESPALDA (si existe) ────────────────────────────────
    // Detect front/back presence (robusto)
    $img_base64_front = $_POST['img_base64'] ?? '';
    $diseno_front     = $_POST['diseno_front'] ?? '';
    $front_present      = !empty($img_base64_front) || !empty($diseno_front);
    $tiene_espalda      = !empty($_POST['diseno_back']) || !empty($_POST['img_espalda_base64']);
    $filepath_espalda_relativo = null;
    $img_esp_raw               = $_POST['img_espalda_base64'] ?? '';

    if ($tiene_espalda && strpos($img_esp_raw, 'data:image/png;base64,') === 0 && strlen($img_esp_raw) <= 10485760) {
        $esp_data    = str_replace(['data:image/png;base64,', ' '], ['', '+'], $img_esp_raw);
        $esp_decoded = base64_decode($esp_data, true);

        if ($esp_decoded && getimagesizefromstring($esp_decoded)) {
            $fname_esp  = 'design_' . $token_unid . '_back_' . time() . '.png';
            $fpath_esp  = $dir_base . $fname_esp;
            if (file_put_contents($fpath_esp, $esp_decoded)) {
                chmod($fpath_esp, 0644);
                $filepath_espalda_relativo = 'uploads/custom/' . $fname_esp;
            }
        }
    }

    // ─── 8. GUARDAR LOGOS EXTRAS (nuca / mangas) ─────────────────────────────
    $LABEL_EXTRA            = ['nuca' => 'Nuca', 'manga-izq' => 'Manga Izquierda', 'manga-der' => 'Manga Derecha'];
    $logos_extras_guardados = [];
    $posiciones_ids         = ['nuca', 'manga-izq', 'manga-der'];

    foreach ($posiciones_ids as $posId) {
        $key_post = 'mini_canvas_' . $posId; // El nombre que envía el JS de personalizar.php
        
        if (!empty($_POST[$key_post]) && strpos($_POST[$key_post], 'data:image/png;base64,') === 0) {
            $raw_data = $_POST[$key_post];
            $img_data = str_replace(['data:image/png;base64,', ' '], ['', '+'], $raw_data);
            $decoded  = base64_decode($img_data);

            if ($decoded && getimagesizefromstring($decoded)) {
                $fname_ex = 'extra_' . $posId . '_' . $token_unid . '_' . time() . '.png';
                $fpath_ex = $dir_base . $fname_ex;

                if (file_put_contents($fpath_ex, $decoded)) {
                    chmod($fpath_ex, 0644);
                    // IMPORTANTE: Guardamos como Clave (Zona) => Valor (Ruta) para que ver_detalles lo lea bien
                    $zona_nombre = $LABEL_EXTRA[$posId] ?? $posId;
                    $logos_extras_guardados[$zona_nombre] = 'uploads/custom/' . $fname_ex;
                }
            }
        }
    }

    // ─── 2. PRECIO REAL CON EXTRAS ────────────────────────────────────────────
    // Calcular precio canónico en servidor (no confiar en precio enviado)
    global $pdo;
    $precio_base = 0.0;
    require_once __DIR__ . '/includes/pricing.php';
    if (function_exists('precioBaseCatalogo')) {
        $precio_base = precioBaseCatalogo($pdo, strtolower($tipo));
    }
    $tipo_limpio = strtolower($tipo);
    $talla_limpia = strtolower(trim($talla));
    if ($tipo_limpio === 'cuadro' && $talla_limpia === 'azulejo') {
        $precio_real = 9.00; // Azulejo fixed price
    } else {
        // Extras del diseño con lógica de front/back
        $front_present_local = $front_present;
        $back_present_local  = $tiene_espalda;
        // Doble cara solo si hay front y back
        $hasDobleCara = ($front_present_local && $back_present_local) && !empty($_POST['doble_cara']);
        $hasNuca = !empty($_POST['nuca']);
        $hasMangaDer = !empty($_POST['manga_der']);
        $hasMangaIzq = !empty($_POST['manga_izq']);
        $extras = [
            'doble_cara' => $hasDobleCara,
            'nuca' => $hasNuca,
            'manga_der' => $hasMangaDer,
            'manga_izq' => $hasMangaIzq,
        ];
        if (function_exists('calcularPrecioPersonalizado')) {
            $precio_real = calcularPrecioPersonalizado($pdo, $tipo_limpio, $talla_limpia, $extras);
        } else {
            $precio_real = $precio_base;
            if ($hasDobleCara) $precio_real += 10.0;
            if ($hasNuca) $precio_real += 3.0;
            if ($hasMangaDer) $precio_real += 3.0;
            if ($hasMangaIzq) $precio_real += 3.0;
        }
    }

    $extras_descripcion = [];

    if ($front_present && $tiene_espalda && $hasDobleCara) {
        $extras_descripcion[] = 'Doble cara (+10€)';
    }
    // Ahora sí: $logos_extras_guardados ya tiene los archivos confirmados en disco
    foreach ($logos_extras_guardados as $zona => $ruta) {
        $extras_descripcion[] = $zona . ' (+3€)';
    }

    // ─── 9. RECURSO ID: NO se guarda en biblioteca_recursos ─────────────────
    // El diseño renderizado del carrito NO debe aparecer en la biblioteca del usuario.
    // La ruta ya se guarda en la sesión del carrito en el paso 10.
    $recurso_id_guardado = null;

    // ─── 10. ACTUALIZACIÓN DEL CARRITO ────────────────────────────────────────

   
    // ─── 10. ACTUALIZACIÓN DEL CARRITO ────────────────────────────────────────
    $cart_key = 'CUSTOM_' . bin2hex(random_bytes(4));

    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    $nombre_carrito = "Personalizado: " . ucfirst($tipo_limpio) . " ($color)";
    if (!empty($extras_descripcion)) {
        $nombre_carrito .= ' [' . implode(', ', $extras_descripcion) . ']';
    }

    // Guardar JSON del diseño para poder recargar el lienzo al editar
    $diseno_front_json = $_POST['diseno_front'] ?? null;
    $diseno_back_json  = $_POST['diseno_back']  ?? null;
    $color_producto    = substr(trim($_POST['color_producto'] ?? ''), 0, 50);

    // JSON de mini lienzos (nuca, manga-izq, manga-der)
    $diseno_minis = [];
    foreach (['nuca', 'manga-izq', 'manga-der'] as $mid) {
        $key = 'diseno_mini_' . $mid;
        if (!empty($_POST[$key])) {
            $diseno_minis[$mid] = $_POST[$key];
        }
    }

    $_SESSION['carrito'][$cart_key] = [
        'id'                   => 'CUSTOM_PROD',
        'nombre'               => $nombre_carrito,
        'cantidad'             => 1,
        'talla'                => $talla,
        'color'                => $color,
        'color_producto'       => $color_producto,
        'precio'               => $precio_real,
        'precio_base'          => $precio_base,
        'extras_descripcion'   => $extras_descripcion,
        'imagen_personalizada' => $filepath_relativo,
        'imagen_espalda'       => $filepath_espalda_relativo,
        'logos_extras'         => $logos_extras_guardados,
        'notas'                => $notas,
        'tipo_base'            => $tipo_limpio,
        'diseno_front'         => $diseno_front_json,
        'diseno_back'          => $diseno_back_json,
        'diseno_minis'         => $diseno_minis,
        'cart_key'             => $cart_key,
        'recurso_id'           => null,
        'timestamp'            => time()
    ];

    // Sincronizar en BD si el usuario está logueado
    if (isset($_SESSION['user_id'])) {
        $carrito_json = json_encode($_SESSION['carrito']);
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET carrito_guardado = ? WHERE id = ?");
            $stmt->execute([$carrito_json, $_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Error actualizando carrito en DB: " . $e->getMessage());
        }
    }

    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'error' => 'Acceso no permitido por este método.']);
}
?>
