<?php

require_once 'includes/config.php';


/**
 * ARCHIVO: includes/header.php
 * ACTUALIZACIÓN: Accesibilidad al 100% (aria-labels) y reCAPTCHA optimizado.
 */

// --- SEGURIDAD CENTRALIZADA ---
$es_admin_logueado = esAdmin();

// --- LÓGICA DE SEO DINÁMICO ---
$seo_title = isset($meta_title) ? $meta_title . " | " . SITE_NAME : "Camiglobo Barcelona | Camisetas Personalizadas y Diseño Online";
$seo_desc = "Diseña tus propias camisetas en Barcelona con Camiglobo. Calidad premium, personalización online en el acto y envíos rápidos a toda España. ¡Crea tu estilo hoy!";
?>
<!DOCTYPE html>

<html lang="es">
<head>
    <meta name="google-site-verification" content="googlef6a37aa262b23275" />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    

 
<title><?php echo $seo_title; ?></title>
<?php if (isset($noindex) && $noindex === true): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta name="description" content="<?php echo $seo_desc; ?>">
<link rel="canonical" href="https://www.camiglobo.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.camiglobo.com/">
    <meta property="og:title" content="Camiglobo Barcelona | Crea tu Camiseta">
    <meta property="og:description" content="Personalización premium de camisetas en el acto. ¡Estudio de diseño online fácil y rápido!">
    <meta property="og:image" content="https://www.camiglobo.com/favicon.jpg">

    <link rel="icon" href="https://www.camiglobo.com/favicon.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="https://www.camiglobo.com/favicon.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="https://www.camiglobo.com/favicon.jpg">

    <link rel="preload" href="https://www.camiglobo.com/common.min.css" as="style">
    <link rel="stylesheet" href="https://www.camiglobo.com/common.min.css">
    
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <style>
    /* Blindaje de menú Administrador */
    #admin-drop-container { position: relative; z-index: 100000 !important; }
    .admin-menu { z-index: 100001 !important; }

    /* Evitar que el logo se deforme en móviles muy pequeños */
    @media (max-width: 360px) {
        .logo-text { font-size: 1.1rem; }
        .logo-img { height: 30px; }
    }
   
@font-face {
    font-family: 'Font Awesome 6 Free';
    font-style: normal;
    font-weight: 900;
    font-display: swap;
    src: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-solid-900.woff2') format('woff2');
}

@font-face {
    font-family: 'Font Awesome 6 Brands';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-brands-400.woff2') format('woff2');
}
        :root { 
            --p-color: #2c3e50; 
            --m-color: #27ae60; 
            --grad-camiglobo: linear-gradient(90deg, #000000 10%, #e74c3c 50%, #27ae60 90%);
        }
        
        nav { 
            background: white; 
            padding: 10px 0; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
            position: -webkit-sticky; 
            position: sticky; 
            top: 0; 
            z-index: 99999 !important; 
            border-bottom: 1px solid #f1f1f1; 
        }        
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 15px; gap: 15px; }
        
        /* LOGO */
        .logo-link { display: flex; align-items: center; gap: 10px; text-decoration: none; transition: transform 0.3s ease; flex-shrink: 0; }
        .logo-link:hover { transform: scale(1.02); }
        .logo-img { height: 40px; width: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .logo-text { 
            font-size: 1.5rem; 
            font-weight: 900; 
            letter-spacing: 0.5px;
            background: var(--grad-camiglobo);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            display: inline-block;
        }

        /* BUSCADOR */
        .header-search { flex-grow: 1; max-width: 220px; position: relative; }
        .header-search input { 
            width: 100%; padding: 8px 35px 8px 15px; border: 1px solid #e0e0e0; 
            border-radius: 50px; outline: none; font-size: 13px; transition: 0.3s; 
            background: #f9f9f9; font-family: inherit; color: #333; font-weight: 600;
        }
        .header-search input:focus { border-color: var(--m-color); background: #fff; box-shadow: 0 3px 10px rgba(231, 76, 60, 0.08); }
        .header-search button { 
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
            background: none; border: none; color: #aaa; cursor: pointer; transition: 0.2s; font-size: 14px;
        }
        
        /* ENLACES */
        .nav-links { display: flex; gap: 12px; list-style: none; align-items: center; margin: 0; padding: 0; }
        
        /* REDES SOCIALES — escritorio: visibles por defecto */
        .header-socials { display: flex; gap: 8px; border-right: 1px solid #e0e0e0; padding-right: 12px; align-items: center; }

        .icon-social { font-size: 16px; transition: all 0.3s ease; display: inline-flex; text-decoration: none; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: #fdfdfd; border: 1px solid #eee; color: #fff !important; }
        
        .icon-tiktok { background: #000; box-shadow: 2px 2px 0px rgba(255,0,80,0.6); }
        .icon-tiktok:hover { transform: translateY(-3px) scale(1.1); filter: brightness(1.2); } 
        
        .icon-instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .icon-instagram:hover { transform: translateY(-3px) scale(1.1); filter: brightness(1.1); }
        
        .icon-threads { background: #fff; color: #000 !important; border: 1px solid #eee; }
        .icon-threads:hover { transform: translateY(-3px) scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .icon-whatsapp { background: #25D366; }
        .icon-whatsapp:hover { transform: translateY(-3px) scale(1.1); filter: brightness(1.1); }

        /* Ocultar WhatsApp en escritorio (>1024px) */
        @media (min-width: 1025px) {
            .icon-whatsapp-hide-desktop { display: none !important; }
        }

        /* BOTONES PRINCIPALES */
        .btn-shop-main {
            background: #000 !important;
            color: white !important;
            padding: 9px 22px;
            border-radius: 50px;
            font-weight: 900 !important;
            font-size: 12px !important;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-shop-main:hover { transform: translateY(-3px) scale(1.05); background: #222 !important; box-shadow: 0 8px 20px rgba(0,0,0,0.25); }

        /* BOTÓN PERSONALIZAR */
        .btn-customize {
            background: #fff !important; 
            color: var(--p-color) !important; 
            padding: 8px 18px; 
            border: 1px solid #ddd;
            border-radius: 50px;
            font-weight: 800 !important; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            text-transform: uppercase; 
            font-size: 11px !important;
            transition: all 0.3s ease;
            text-decoration: none;
            box-sizing: border-box;
        }
        .btn-customize:hover { 
            background: #000 !important; 
            color: #fff !important; 
            border-color: #000 !important; 
            transform: translateY(-3px) scale(1.05); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .btn-customize:hover i { color: white !important; }

        /* BOTÓN ENTRAR */
        .btn-login {
            background: var(--p-color) !important;
            color: white !important;
            padding: 9px 22px;
            border-radius: 50px;
            font-weight: 900 !important;
            font-size: 11px !important;
            text-transform: uppercase;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(44, 62, 80, 0.2);
        }
        .btn-login:hover { transform: translateY(-2px); background: #1a252f !important; box-shadow: 0 6px 15px rgba(44, 62, 80, 0.3); }

        /* BOTÓN CONTACTO */
        .btn-contact-header {
            background: transparent !important;
            color: var(--p-color) !important;
            padding: 8px 15px;
            border: 1.5px solid #eee;
            border-radius: 50px;
            font-weight: 800 !important;
            font-size: 11px !important;
            text-transform: uppercase;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-contact-header:hover {
            background: #fdfdfd !important;
            border-color: var(--m-color);
            color: var(--m-color) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* ACCIONES USUARIO */
        .user-actions { display: flex; gap: 8px; align-items: center; margin-left: 5px; }
        .action-icon { position: relative; font-size: 16px; color: #555; text-decoration: none; transition: 0.3s; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: #fdfdfd; border: 1px solid #eee; }
        .action-icon:hover { color: var(--m-color); border-color: var(--m-color); transform: translateY(-2px); }
        .cart-count { position: absolute; top: -5px; right: -5px; background: var(--m-color); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; font-weight: 900; border: 1.5px solid white; line-height: 1; }

        /* MINI DROPDOWN PERFIL */
        .user-profile-wrapper { position: relative; display: inline-block; }
        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white;
            border: 1px solid #eee;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 12px 16px;
            min-width: 200px;
            z-index: 100002;
            text-align: left;
        }
        .user-profile-wrapper:hover .user-dropdown { display: block; }
        .user-dropdown-name, .user-dropdown-email {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            padding: 4px 0;
            white-space: nowrap;
        }
        .user-dropdown-name { font-weight: 700; color: #111; }
        .user-dropdown-email { font-weight: 400; color: #777; font-size: 12px; }
        .user-dropdown-name i, .user-dropdown-email i { color: var(--m-color); width: 14px; text-align: center; }
        .user-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 12px;
            width: 12px;
            height: 12px;
            background: white;
            border-left: 1px solid #eee;
            border-top: 1px solid #eee;
            transform: rotate(45deg);
        }

        /* DROPDOWN ADMIN */
        .admin-dropdown { position: relative; display: inline-block; margin-left: 5px; padding-left: 12px; border-left: 1px solid #e0e0e0; }
        .btn-admin-main {
            background: var(--m-color) !important; 
            color: white !important; 
            padding: 8px 16px; 
            border: none;
            border-radius: 50px;
            font-weight: 900 !important; 
            font-size: 11px !important;
            text-transform: uppercase; 
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(39, 174, 96, 0.2);
            transition: 0.3s;
        }
        .btn-admin-main:hover { transform: translateY(-2px); background: #219a52 !important; }

        .admin-menu {
            display: none;
            flex-direction: column;
            position: absolute; 
            top: 100%; 
            right: 0; 
            background: white; 
            min-width: 160px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            border-radius: 12px; 
            overflow: hidden; 
            margin-top: 12px; 
            border: 1px solid #eee; 
            z-index: 1001;
        }
        .admin-menu::before { content: ""; position: absolute; top: -6px; right: 20px; width: 12px; height: 12px; background: white; transform: rotate(45deg); border-top: 1px solid #eee; border-left: 1px solid #eee; }
        .admin-dropdown.active .admin-menu { display: flex; }
        .admin-menu a { padding: 12px 15px; color: var(--p-color); text-decoration: none; font-size: 12px; font-weight: 800; border-bottom: 1px solid #f9f9f9; display: flex; align-items: center; gap: 8px; transition: 0.2s; background: white; position: relative; z-index: 2; }
        .admin-menu a:hover { background: #fff5f5; color: var(--m-color); }

        /* RESPONSIVE */
        .mobile-menu-btn { display: none; font-size: 22px; color: var(--p-color); cursor: pointer; background: none; border: none; }

        /* Iconos móvil/tablet (login + carrito) — ocultos en escritorio, visibles en ≤1024px */
        .mobile-icon-btn {
            display: none;
            position: relative;
            font-size: 18px;
            color: var(--p-color);
            text-decoration: none;
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #fdfdfd;
            border: 1px solid #eee;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .mobile-icon-btn:hover { color: var(--m-color); border-color: var(--m-color); }
        .mobile-icon-btn .cart-count { position: absolute; top: -5px; right: -5px; background: var(--m-color); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; font-weight: 900; border: 1.5px solid white; line-height: 1; }

        @media (max-width: 1080px) { .header-search { display: none; } }

        /* ≤1024px: hamburguesa (móvil + tablet pequeña + tablet grande) */
        @media (max-width: 1024px) {
            .mobile-menu-btn { display: block; }
            .mobile-icon-btn { display: flex; }
            .nav-container { flex-wrap: wrap; padding: 12px 15px; }
            .nav-links { display: none; width: 100%; flex-direction: column; gap: 15px; background: white; padding: 20px 0; border-top: 1px solid #eee; margin-top: 5px; }
            .nav-links.active { display: flex; z-index: 100002 !important; }
            .header-socials { border-right: none; padding-right: 0; justify-content: center; width: 100%; margin: 10px 0; }
            .user-actions { width: 100%; justify-content: center; margin: 10px 0; }
            .btn-shop-main, .btn-customize, .btn-login { width: 100%; justify-content: center; }
            .admin-dropdown { border-left: none; padding-left: 0; width: 100%; border-top: 1px dashed #eee; padding-top: 15px; }
            .admin-menu { position: static; box-shadow: none; border: none; display: flex !important; background: #f9f9f9; margin-top: 10px; }
            .admin-menu::before { display: none; }
            .admin-menu a { background: transparent; justify-content: center; }
            .icon-social { display: flex !important; }
            .icon-whatsapp-hide-desktop { display: inline-flex !important; }
        }
        
        /* --- AJUSTE PARA MANTENER LA CABECERA EN 1 LÍNEA --- */
@media (max-width: 480px) {
    .mobile-icon-btn { width: 32px; height: 32px; font-size: 15px; }
    .logo-text { font-size: 1.2rem; }
    .logo-img { height: 32px; }
    .logo-link { gap: 5px; flex-shrink: 1; }
    .nav-container { padding: 10px; gap: 5px; }
}

/* --- ESTILOS BARRA BÚSQUEDA MÓVIL --- */
.mobile-search-container {
    display: none;
    width: 100%;
    padding: 10px 15px;
    background: #fff;
    border-top: 1px solid #eee;
    box-sizing: border-box;
}
.mobile-search-container.active {
    display: block;
}
.mobile-search-container form {
    position: relative;
    width: 100%;
}
.mobile-search-container input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 50px;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}
.mobile-search-container input:focus {
    border-color: var(--m-color);
}
.mobile-search-container button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--m-color);
    font-size: 16px;
    cursor: pointer;
}
    </style>
</head>
<body>

<nav>
    <div class="nav-container">
        
        <a href="https://www.camiglobo.com/index.php" class="logo-link" aria-label="Inicio Camiglobo">
            <img src="https://www.camiglobo.com/images/camiglobofavicon.jpg" alt="Camiglobo Barcelona Logo" class="logo-img">
            <span class="logo-text">CAMIGLOBO</span>
        </a>

        <form action="https://www.camiglobo.com/productos.php" method="GET" class="header-search">
            <input type="text" name="q" placeholder="Buscar camiseta..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" aria-label="Campo de búsqueda">
            <button type="submit" aria-label="Botón buscar"><i class="fas fa-search"></i></button>
        </form>

        <!-- BARRA MÓVIL/TABLET: login + carrito + hamburguesa (visible en ≤1024px) -->
<div style="display:flex; align-items:center; gap:4px; flex-wrap:nowrap;">
    
            <button class="mobile-icon-btn" onclick="toggleMobileSearch()" aria-label="Abrir buscador" style="background: none; border: 1px solid #eee; cursor: pointer;">
    <i class="fas fa-search"></i>
</button>

            <a href="https://www.camiglobo.com/contacto.php" class="mobile-icon-btn" aria-label="Contactar con Camiglobo">
                <i class="fas fa-headset"></i>
            </a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="https://www.camiglobo.com/perfil.php" class="mobile-icon-btn" aria-label="Mi cuenta">
                    <i class="fas fa-user"></i>
                </a>
            <?php else: ?>
                <a href="https://www.camiglobo.com/login.php" class="mobile-icon-btn" aria-label="Iniciar sesión">
                    <i class="fas fa-user"></i>
                </a>
            <?php endif; ?>

            <a href="https://www.camiglobo.com/carrito.php" class="mobile-icon-btn" aria-label="Ver carrito de compras">
                <i class="fas fa-shopping-cart"></i>
                <?php 
                $count_mobile = 0;
                if(isset($_SESSION['carrito'])) {
                    foreach($_SESSION['carrito'] as $item) { $count_mobile += $item['cantidad']; }
                }
                if($count_mobile > 0) echo "<span class='cart-count'>$count_mobile</span>";
                ?>
            </a>

            <button class="mobile-menu-btn" onclick="document.getElementById('main-menu').classList.toggle('active')" aria-label="Abrir menú de navegación">
                <i class="fas fa-bars"></i>
            </button>

        </div>

        <ul class="nav-links" id="main-menu">

            <li><a href="https://www.camiglobo.com/contacto.php" class="btn-contact-header" aria-label="Contactar con Camiglobo"><i class="fas fa-headset"></i> CONTÁCTANOS</a></li>

            <li><a href="https://www.camiglobo.com/productos.php" class="btn-shop-main"><i class="fas fa-store"></i> TIENDA</a></li>
            <li><a href="https://www.camiglobo.com/personalizar.php" class="btn-customize"><i class="fas fa-paint-brush" style="color:#e74c3c;"></i> PERSONALIZAR</a></li>
            
            <li class="user-actions">
                <a href="https://www.camiglobo.com/carrito.php" class="action-icon" aria-label="Ver carrito de compras">
                    <i class="fas fa-shopping-cart"></i>
                    <?php 
                    $count = 0;
                    if(isset($_SESSION['carrito'])) {
                        foreach($_SESSION['carrito'] as $item) { $count += $item['cantidad']; }
                    }
                    if($count > 0) echo "<span class='cart-count'>$count</span>";
                    ?>
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-profile-wrapper">
                        <a href="https://www.camiglobo.com/perfil.php" class="action-icon" aria-label="Mi Cuenta">
                            <i class="fas fa-user"></i>
                        </a>
                        <div class="user-dropdown">
                            <div class="user-dropdown-name"><i class="fas fa-user-circle"></i> <?php echo h($_SESSION['nombre'] ?? 'Usuario'); ?></div>
                            <div class="user-dropdown-email"><i class="fas fa-envelope"></i> <?php echo h($_SESSION['email'] ?? ''); ?></div>
                        </div>
                    </div>
                    <a href="https://www.camiglobo.com/logout.php" class="action-icon logout-icon" aria-label="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="https://www.camiglobo.com/login.php" class="btn-login">ENTRAR</a>
                <?php endif; ?>
            </li>

            <?php if(isset($_SESSION['user_id']) && $es_admin_logueado): ?>
                <li class="admin-dropdown" id="admin-drop-container">
                    <div class="btn-admin-main" onclick="toggleAdminMenu(event)" aria-label="Abrir panel de administración" role="button" tabindex="0">
                        <i class="fas fa-crown"></i> ADMIN <i class="fas fa-chevron-down" style="font-size:9px;"></i>
                    </div>
                    <div class="admin-menu">
                        <a href="https://www.camiglobo.com/admin_pedidos.php"><i class="fas fa-chart-line"></i> Pedidos</a>
                        <a href="https://www.camiglobo.com/admin_pagos.php"><i class="fas fa-credit-card"></i> Pagos</a>
                        <a href="https://www.camiglobo.com/admin_productos.php"><i class="fas fa-boxes"></i> Stock</a>
                        <a href="https://www.camiglobo.com/lista_clientes_secreta.php"><i class="fas fa-users"></i> Clientes</a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- REDES SOCIALES: al final para que en móvil/tablet aparezcan debajo de todo -->
            <li class="header-socials">
                <a href="https://www.tiktok.com/@camiglobocamiglobo" target="_blank" class="icon-social icon-tiktok" aria-label="Ir a nuestro TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://www.instagram.com/camiglobo/" target="_blank" class="icon-social icon-instagram" aria-label="Ir a nuestro Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.threads.net/@camiglobo" target="_blank" class="icon-social icon-threads" aria-label="Ir a nuestro Threads"><i class="fa-brands fa-threads"></i></a>
                <a href="https://wa.me/34653851786" target="_blank" class="icon-social icon-whatsapp icon-whatsapp-hide-desktop" aria-label="Contactar por WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </li>
            
        </ul>
    </div>
    <div id="mobile-search-bar" class="mobile-search-container">
    <form action="https://www.camiglobo.com/productos.php" method="GET">
        <input type="text" name="q" placeholder="Buscar camiseta..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" aria-label="Escribe aquí tu búsqueda">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
</div>
</nav>

<div class="cursor-dot" id="cursorDot"></div>

<script>
    /** SISTEMA DE NAVEGACIÓN Y ACCESIBILIDAD **/

    // 1. Función única para abrir/cerrar el menú Admin
    function toggleAdminMenu(event) {
        if (event) event.stopPropagation();
        const container = document.getElementById('admin-drop-container');
        if (container) container.classList.toggle('active');
    }

    // 2. Cerrar el menú automáticamente si el usuario hace clic fuera de él
    document.addEventListener('click', function(event) {
        const container = document.getElementById('admin-drop-container');
        if (container && !container.contains(event.target)) {
            container.classList.remove('active');
        }
    });

    // 3. Accesibilidad: Cerrar el menú al pulsar la tecla "Escape"
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            const container = document.getElementById('admin-drop-container');
            if (container) container.classList.remove('active');
        }
    });

    // 4. Accesibilidad TOP: Abrir con la tecla "Enter" cuando el botón tiene el foco
    document.addEventListener('DOMContentLoaded', function() {
        const adminBtn = document.querySelector('.btn-admin-main');
        if (adminBtn) {
            adminBtn.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    toggleAdminMenu(e);
                }
            });
        }
    });
    
    // 5. Mostrar/Ocultar barra de búsqueda en móvil
function toggleMobileSearch() {
    const searchBar = document.getElementById('mobile-search-bar');
    searchBar.classList.toggle('active');
    
    // Si se abre, poner el cursor automáticamente dentro
    if (searchBar.classList.contains('active')) {
        searchBar.querySelector('input').focus();
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var dot = document.getElementById('cursorDot');
  if(!dot) return;
  document.addEventListener('mousemove', function(e){
    dot.style.left = e.clientX + 'px';
    dot.style.top  = e.clientY + 'px';
  });
  document.querySelectorAll('a, button').forEach(function(el){
    el.addEventListener('mouseenter', function(){ dot.classList.add('expand'); });
    el.addEventListener('mouseleave', function(){ dot.classList.remove('expand'); });
  });
});
</script>