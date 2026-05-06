<?php
/**
 * ARCHIVO: sitemap.php
 * FUNCIÓN: Mapa visual para CLIENTES (Humanos).
 * Muestra una lista organizada de enlaces con diseño bonito.
 */
require_once 'includes/config.php';
include 'includes/header.php';
?>

<style>
    /* Estilos exclusivos para el mapa del sitio */
    .sitemap-hero { 
        background: #000; 
        color: #fff; 
        padding: 60px 20px; 
        text-align: center; 
        border-bottom: 4px solid #e74c3c; 
        margin-bottom: 40px;
    }
    
    .sitemap-container {
        max-width: 1100px; 
        margin: 0 auto 60px; 
        padding: 0 20px;
    }

    .sitemap-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
        gap: 30px; 
    }
    
    .sitemap-card { 
        background: #fff; 
        padding: 30px; 
        border-radius: 20px; 
        border: 1px solid #eee; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        transition: 0.3s ease;
    }
    
    .sitemap-card:hover { 
        transform: translateY(-5px); 
        border-color: #e74c3c; 
        box-shadow: 0 15px 40px rgba(231, 76, 60, 0.15);
    }
    
    .sitemap-card h3 { 
        color: #e74c3c; 
        font-weight: 900; 
        text-transform: uppercase; 
        font-size: 14px; 
        margin-top: 0;
        margin-bottom: 20px; 
        border-bottom: 2px solid #f9f9f9; 
        padding-bottom: 15px;
        display: flex; 
        align-items: center; 
        gap: 10px;
    }
    
    .sitemap-links { 
        list-style: none; 
        padding: 0; 
        margin: 0; 
    }
    
    .sitemap-links li { 
        margin-bottom: 12px; 
    }
    
    .sitemap-links a { 
        color: #2c3e50; 
        text-decoration: none; 
        font-weight: 700; 
        transition: 0.2s; 
        font-size: 15px; 
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sitemap-links a:hover { 
        color: #e74c3c; 
        transform: translateX(5px);
    }

    .sitemap-links a i {
        font-size: 12px;
        color: #ddd;
    }
</style>

<div class="sitemap-hero">
    <h1 style="font-weight: 900; font-size: 2.5rem; margin: 0; text-transform: uppercase; letter-spacing: -1px;">Mapa del <span style="color: #e74c3c;">Sitio</span></h1>
    <p style="color: #aaa; margin-top: 10px; font-weight: 500;">Índice rápido de Camiglobo Barcelona</p>
</div>

<div class="sitemap-container">
    <div class="sitemap-grid">
        
        <div class="sitemap-card">
            <h3><i class="fas fa-shopping-bag"></i> Tienda Online</h3>
            <ul class="sitemap-links">
                <li><a href="index.php"><i class="fas fa-chevron-right"></i> Inicio</a></li>
                <li><a href="productos.php"><i class="fas fa-chevron-right"></i> Catálogo Completo</a></li>
                <li><a href="personalizar.php"><i class="fas fa-chevron-right"></i> Personalizador (Diseña tu ropa)</a></li>
                <li><a href="carrito.php"><i class="fas fa-chevron-right"></i> Ver Carrito</a></li>
            </ul>
        </div>

        <div class="sitemap-card">
            <h3><i class="fas fa-tags"></i> Categorías</h3>
            <ul class="sitemap-links">
                <li><a href="productos.php?categoria=camiseta"><i class="fas fa-chevron-right"></i> Camisetas</a></li>
                <li><a href="productos.php?categoria=sudadera"><i class="fas fa-chevron-right"></i> Sudaderas</a></li>
                <li><a href="productos.php?categoria=taza"><i class="fas fa-chevron-right"></i> Tazas</a></li>
                <li><a href="productos.php?categoria=cuadro"><i class="fas fa-chevron-right"></i> Cuadros</a></li>
            </ul>
        </div>

        <div class="sitemap-card">
            <h3><i class="fas fa-headset"></i> Ayuda y Soporte</h3>
            <ul class="sitemap-links">
                <li><a href="contacto.php"><i class="fas fa-chevron-right"></i> Contactar</a></li>
                <li><a href="faq.php"><i class="fas fa-chevron-right"></i> Preguntas Frecuentes</a></li>
                <li><a href="politica-envios.php"><i class="fas fa-chevron-right"></i> Envíos y Entregas</a></li>
                <li><a href="politica-reembolso.php"><i class="fas fa-chevron-right"></i> Devoluciones</a></li>
            </ul>
        </div>

        <div class="sitemap-card">
            <h3><i class="fas fa-scale-balanced"></i> Información Legal</h3>
            <ul class="sitemap-links">
                <li><a href="aviso-legal.php"><i class="fas fa-chevron-right"></i> Aviso Legal</a></li>
                <li><a href="politica-privacidad.php"><i class="fas fa-chevron-right"></i> Política de Privacidad</a></li>
                <li><a href="terminos-condiciones.php"><i class="fas fa-chevron-right"></i> Términos y Condiciones</a></li>
            </ul>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>