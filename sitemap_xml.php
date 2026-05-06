<?php
/**
 * ARCHIVO: sitemap_xml.php
 * FUNCIÓN: Generar automáticamente el mapa del sitio XML para Google.
 * Incluye páginas estáticas, categorías y los productos de la BD.
 */

require_once 'includes/config.php';

// Establecer la cabecera para que el navegador y Google lo lean como XML
header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>https://www.camiglobo.com/index.php</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/productos.php</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/personalizar.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <?php
    $categorias = ['camiseta', 'sudadera', 'taza', 'cuadro'];
    foreach ($categorias as $cat) {
        echo "    <url>\n";
        echo "        <loc>https://www.camiglobo.com/productos.php?categoria=" . $cat . "</loc>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";
        echo "    </url>\n";
    }
    ?>

    <?php
    try {
        // Consultamos todos los IDs de la tabla productos
        $stmt = $pdo->query("SELECT id FROM productos");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "    <url>\n";
            // Usamos el ID (ej: camiseta-100) para generar la URL de la ficha
            echo "        <loc>https://www.camiglobo.com/producto.php?id=" . htmlspecialchars($row['id']) . "</loc>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.7</priority>\n";
            echo "    </url>\n";
        }
    } catch (Exception $e) {
        // En caso de error de conexión, no se imprimen productos para no romper el XML
    }
    ?>

    <url>
        <loc>https://www.camiglobo.com/faq.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/politica-privacidad.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/terminos-condiciones.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/politica-envios.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/politica-reembolso.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>https://www.camiglobo.com/aviso-legal.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

</urlset>