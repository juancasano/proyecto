<?php
// Mapa central de colores: nombre -> hex
// Unifica TODOS los colores de admin_pedidos, ver_detalles y carrito_accion
// con aliases para el mismo color con nombres diferentes

$colores_hex = [
    // Comunes camiseta + sudadera
    'Blanco'                      => '#FFFFFF',
    'Girasol'                     => '#F9A825',
    'Negro'                       => '#1C1C1C',
    'Rojo'                        => '#C0392B',
    'Verde Kelly'                 => '#27AE60',
    'Azul Royal'                  => '#1565C0',
    'Fucsia'                      => '#E91E8C',
    'Gris Jaspeado'               => '#9E9E9E',
    'Azul Marino Oscuro'          => '#0D1B3E',
    'Gris Oscuro Jaspeado'        => '#555555',
    'Rosa Claro'                  => '#F48FB1',
    'Burdeos'                     => '#6D1717',
    'Verde Oliva'                 => '#556B2F',
    'Verde Botella'               => '#1B5E20',
    'Azul Cielo'                  => '#87CEEB',
    'Azul Azur'                   => '#4169E1',
    'Morado'                      => '#6A0DAD',
    'Marino'                      => '#002366',
    'Grafito Claro'               => '#607D8B',
    'Chocolate'                   => '#3E1C00',
    'Natural'                     => '#F5F0E0',
    'Caqui'                       => '#C3B091',
    'Lima'                        => '#CDDC39',
    // Naranja/Amarillo (comunes a ambas)
    'Naranja'                     => '#E67E22',
    'Amarillo'                    => '#FFD600',
    // Solo camiseta
    'Rojo Ladrillo'               => '#B03A2E',
    'Ceniza'                      => '#B2BEB5',
    // Heather / Jaspeado (diferentes nombres para el mismo hex)
    'Heather Morado'              => '#7B68AA',
    'Morado Jaspeado'             => '#7B68AA',
    'Heather Burdeos'             => '#8B4757',
    'Burdeos Jaspeado'            => '#8B4757',
    // Vintage / Heather (diferentes nombres para el mismo hex)
    'Vintage Heather Marino'      => '#4A6FA5',
    'Azul Vintage'                => '#4A6FA5',
    'Azul Vintage Jaspeado'       => '#4A6FA5',
    'Retro Heather Royal'         => '#6B8CBA',
    'Heather Royal'               => '#6B8CBA',
    'Retro Heather Verde'         => '#6B8C6B',
    'Heather Verde'               => '#6B8C6B',
    'Vintage Heather Rojo'        => '#B24040',
    'Rojo Vintage'                => '#B24040',
];

// Alias para compatibilidad con código existente
$COLORES_HEX = $colores_hex;
?>
