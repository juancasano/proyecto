<?php
// Pricing helpers for server-side calculation of catalog and custom product prices
// Expects a valid PDO instance passed as the first argument where needed.

/**
 * Get the canonical base price for a catalog category from the DB.
 * Falls back to 0.0 if not found.
 */
function precioBaseCatalogo(PDO $pdo, string $categoria): float {
    try {
        $stmt = $pdo->prepare("SELECT precio FROM productos WHERE categoria = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$categoria]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['precio'])) {
            return (float)$row['precio'];
        }
    } catch (Exception $e) {
        error_log("PRECIO BASE: categoría '$categoria' no encontrada en DB.");
    }
    return 0.0;
}

/**
 * Calculate the price for a customized design based on base, type, size and extras.
 * Extras supported: doble_cara, nuca, manga_der, manga_izq
 */
function calcularPrecioPersonalizado(PDO $pdo, string $tipo, string $talla, array $extras = []): float {
    $tipo = strtolower($tipo);
    $talla = strtolower($talla);

    // Azulejo handling for cuadros
    if ($tipo === 'cuadro' && $talla === 'azulejo') {
        return 9.00;
    }

    // Base price from canón DB price for the category
    $base = precioBaseCatalogo($pdo, $tipo);
    if ($base <= 0.0) {
        error_log("PRECIO BASE: categoría '$tipo' no encontrada en DB para cálculo personalizado.");
        $base = 0.0;
    }

    $precio = $base;
    if (!empty($extras['doble_cara'])) {
        $precio += 10.00;
    }
    if (!empty($extras['nuca'])) {
        $precio += 3.00;
    }
    if (!empty($extras['manga_der'])) {
        $precio += 3.00;
    }
    if (!empty($extras['manga_izq'])) {
        $precio += 3.00;
    }
    return $precio;
}

?>
