<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

try {
    // Seleccionamos un producto base por cada categoría para el editor
    $stmt = $pdo->query("SELECT MIN(id) as id, MIN(nombre) as nombre, MIN(precio) as precio, MIN(imagen_url) as imagen_url, categoria FROM productos 
                         WHERE categoria IN ('camiseta', 'sudadera', 'taza', 'cuadro') 
                         GROUP BY categoria");
    $db_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($db_productos);
} catch (Exception $e) {
    echo json_encode([]);
}