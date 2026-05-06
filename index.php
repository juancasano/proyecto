<?php 
require_once 'includes/config.php';
include 'includes/header.php'; 
?>

<?php if(isset($_GET['msg']) || isset($_GET['error'])): ?>
<div class="container" id="alert-container" style="margin-top: 20px; position: relative; z-index: 1000;">
<?php
    // 1. GESTIÓN DE ERRORES (news_exists se muestra por toast en footer)
if(isset($_GET['error']) && !in_array($_GET['error'], ['news_exists', 'captcha', 'email_invalid'])) {
    echo '<div style="background:#fff5f5; color:#e03131; padding:15px; border-radius:15px; text-align:center; border:1px solid #ffa8a8; font-weight:700; margin-bottom:10px;">';
    echo 'Hubo un error. Inténtalo de nuevo.';
    echo '</div>';
}
    // 2. MENSAJE: LOGIN EXITOSO (Saludo Personalizado)
    // CORREGIDO: 'user_nombre' cambiado a 'nombre'
    if(isset($_GET['msg']) && $_GET['msg'] == 'login_success' && isset($_SESSION['nombre'])) {
        echo '<div style="background:#e7f5ff; color:#1971c2; padding:15px; border-radius:15px; text-align:center; border:1px solid #a5d8ff; font-weight:700; margin-bottom:10px; animation: fadeInDown 0.5s ease;">';
        echo '👋 ¡Hola de nuevo, ' . h($_SESSION['nombre']) . '! Qué bueno volver a verte por Camiglobo.';
        echo '</div>';
    }

    // 3. MENSAJE: SUSCRIPCIÓN NEWSLETTER (se muestra por toast en footer)
    
    // 4. MENSAJE: LOGOUT CORRECTO
    if(isset($_GET['msg']) && $_GET['msg'] == 'logged_out') {
        echo '<div style="background:#fff9db; color:#e67700; padding:15px; border-radius:15px; text-align:center; border:1px solid #ffe066; font-weight:700; animation: fadeInDown 0.5s ease;">';
        echo '🔒 Has cerrado sesión correctamente. ¡Hasta pronto!';
        echo '</div>';
    }
    ?>
</div>
<?php endif; ?>

<main> <section class="hero" style="

    background: linear-gradient(rgba(44, 62, 80, 0.7), rgba(44, 62, 80, 0.7)), url('uploads/il_1588xN.3937318787_1fyl.webp');
    background-size: cover;
    background-position: center;
    min-height: calc(100dvh - 61px); 
    padding: 100px 0; 
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
    margin-bottom: 50px;
">
    <div class="container">
        <h1 class="hero-title" style="font-size: 3.5rem; font-weight: 800; margin-bottom: 20px; text-shadow: 2px 2px 10px rgba(0,0,0,0.5);">
            🔥 Camisetas únicas que hablan por ti
        </h1>
        <p class="hero-text" style="font-size: 1.4rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; text-shadow: 1px 1px 5px rgba(0,0,0,0.5);">
            Personaliza tu estilo con la mejor calidad de impresión en Barcelona. DTF, sublimación, SubliFlock y vinilo textil. Envíos gratis a partir de 45€.
        </p>
        <div class="hero-buttons" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="personalizar.php" style="
                background: #ff6b6b;
                color: white;
                padding: 18px 45px;
                border-radius: 50px;
                text-decoration: none;
                font-weight: bold;
                font-size: 1.2rem;
                box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
                transition: 0.3s;
            " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                🎨 PERSONALIZAR AHORA
            </a>
            <a href="#colecciones" style="
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 18px 45px;
                border-radius: 50px;
                text-decoration: none;
                font-weight: bold;
                font-size: 1.2rem;
                border: 2px solid white;
                backdrop-filter: blur(5px);
                transition: 0.3s;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                EXPLORAR COLECCIÓN
            </a>
        </div>
    </div>
</section>

<section id="colecciones" class="container" style="margin-top: 0; padding-top: 50px; scroll-margin-top: 61px;">
    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px; font-weight: 800;">Explora nuestras Colecciones</h2>
    
    <div class="grid-categories" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; max-width: 860px; margin: 0 auto;">
        <a href="productos.php?categoria=camiseta" style="text-decoration: none;" aria-label="Ver colección de Camisetas">
        <div style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('uploads/prod_485d00983d789b6a_1773759994.jpg'); background-size: cover; background-position: center; aspect-ratio: 4 / 3; border-radius: 15px; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h3 style="color: white; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-weight: 800; opacity: 0.7;">CAMISETAS</h3>
        </div>
    </a>

    <a href="productos.php?categoria=sudadera" style="text-decoration: none;" aria-label="Ver colección de Sudaderas">
        <div style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('uploads/c11e65d03f9246f3bd00615e429f3b15-goods.avif'); background-size: cover; background-position: center top; aspect-ratio: 4 / 3; border-radius: 15px; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h3 style="color: white; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-weight: 800; opacity: 0.7;">SUDADERAS</h3>
        </div>
    </a>

    <a href="productos.php?categoria=taza" style="text-decoration: none;" aria-label="Ver colección de Tazas">
        <div style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('uploads/65d2042c-0d17-4b7c-bb1f-c5e266d7a095.avif'); background-size: cover; background-position: center; aspect-ratio: 4 / 3; border-radius: 15px; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h3 style="color: white; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-weight: 800; opacity: 0.7;">TAZAS</h3>
        </div>
    </a>

    <a href="productos.php?categoria=cuadro" style="text-decoration: none;" aria-label="Ver colección de Cuadros">
        <div style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('uploads/prod_d956e77773b4e25f_1773919167.jpg'); background-size: cover; background-position: center; aspect-ratio: 4 / 3; border-radius: 15px; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h3 style="color: white; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-weight: 800; opacity: 0.7;">CUADROS</h3>
        </div>
    </a>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="productos.php" style="display: inline-block; padding: 15px 40px; border: 2px solid #2c3e50; color: #2c3e50; text-decoration: none; font-weight: bold; border-radius: 10px; transition: 0.3s;" onmouseover="this.style.background='#2c3e50'; this.style.color='white';">
            VER TODOS LOS PRODUCTOS →
        </a>
    </div>
</section>

<style>
.destacados-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
.dest-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee;
    overflow: hidden;
    transition: 0.3s;
    position: relative;
    text-align: center;
}
.dest-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    border-color: #27ae60;
}
.dest-card a {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.dest-img-wrap {
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
}
.dest-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.dest-info {
    padding: 15px 15px 18px;
}
.dest-titulo {
    font-size: 15px;
    height: 40px;
    overflow: hidden;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 700;
    line-height: 1.3;
}
.dest-desc {
    font-size: 12px;
    color: #888;
    height: 36px;
    overflow: hidden;
    line-height: 1.4;
    margin-bottom: 8px;
}
.dest-precio {
    color: #e74c3c;
    font-weight: 800;
    font-size: 20px;
    margin-bottom: 12px;
}
.dest-btn {
    background: #000;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
}
@media (max-width: 1100px) { .destacados-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .destacados-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; } }
@media (max-width: 480px)  { .destacados-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; } }
</style>

<section class="container" style="margin-top: 60px; margin-bottom: 40px;">
    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px; font-weight: 800;">Nuestras Joyas (Destacados)</h2>
    <div class="destacados-grid">
        <?php
        $stmt = $pdo->query("SELECT id, nombre, precio, imagen_url, descripcion FROM productos WHERE destacado = 1 ORDER BY id DESC LIMIT 10");
        while($p = $stmt->fetch()):
            $img_secure = str_replace('http://', 'https://', $p['imagen_url']);
        ?>
        <div class="dest-card">
            <a href="producto.php?id=<?php echo urlencode($p['id']); ?>">
                <div class="dest-img-wrap">
                    <img src="<?php echo htmlspecialchars($img_secure); ?>"
                         alt="<?php echo h($p['nombre']); ?>"
                         loading="lazy"
                         onerror="this.src='https://www.camiglobo.com/images/camiglobofavicon.jpg';">
                </div>
                <div class="dest-info">
                    <h3 class="dest-titulo"><?php echo h($p['nombre']); ?></h3>
                    <?php if(!empty($p['descripcion'])): ?>
                        <p class="dest-desc"><?php echo mb_strimwidth(strip_tags($p['descripcion']), 0, 80, "..."); ?></p>
                    <?php endif; ?>
                    <p class="dest-precio"><?php echo number_format($p['precio'], 2, ',', '.'); ?> €</p>
                    <span class="dest-btn">VER DETALLES</span>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<section style="background: #fff; padding: 60px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-weight: 800;">Crea tu prenda en 4 pasos</h2>
        <p style="text-align: center; color: #666; margin-bottom: 40px; font-size: 1.1rem;">100% online, sin complicaciones. En minutos tienes tu diseño listo.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">👕</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">1. ELIGE PRODUCTO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">Camiseta, Sudadera, Taza o Cuadro. Elige el color de la prenda y la talla. En ropa puedes diseñar también la <strong>nuca, manga izquierda y manga derecha</strong> como zonas independientes. El precio se actualiza en tiempo real.</p>
            </div>
            
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">📸</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">2. SUBE TU IMAGEN</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">JPG, PNG, WEBP o HEIC (iPhone). Ajusta el tamaño, rotación y opacidad. Aplica filtros profesionales (Sepia, Kodak, B&N…) o añade stickers y emojis directamente al lienzo.</p>
            </div>
            
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">✏️</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">3. PERSONALIZA EL TEXTO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">20 tipografías y 16 efectos: neón, dorado, fuego, glitch, arcoíris… Ajusta tamaño, color, espaciado y alineación. Usa plantillas listas o parte de cero.</p>
            </div>
            
            <div style="background: #f9f9f9; padding: 30px 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="font-size: 2.5rem; margin-bottom: 15px;">🛒</div>
                <h3 style="color: #2c3e50; font-weight: 800; margin-bottom: 10px;">4. PIDE TU DISEÑO</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">Previsualiza el resultado en alta calidad, añade notas si necesitas algún ajuste y añade al carrito. Enviamos a toda España, o recoge gratis en nuestro taller de Barcelona.</p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="personalizar.php" style="
                display: inline-block;
                background: #ff6b6b;
                color: white;
                padding: 18px 50px;
                border-radius: 50px;
                text-decoration: none;
                font-weight: 800;
                font-size: 1.3rem;
                box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
                transition: all 0.3s ease;
                border: 2px solid #ff6b6b;
            " onmouseover="this.style.background='white'; this.style.color='#ff6b6b'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#ff6b6b'; this.style.color='white'; this.style.transform='scale(1)'">
                🚀 EMPEZAR A PERSONALIZAR
            </a>
        </div>
    </div>
</section>

<section style="padding: 80px 0;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px; font-weight: 800;">¿Dónde estamos?</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; align-items: start;">
            <div style="padding: 20px;">
                <h3 style="color: #ff6b6b; margin-bottom: 25px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Visítanos en nuestro taller de Barcelona</h3>
                <p style="font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 25px;">
                    <strong>Camiglobo</strong> es mucho más que una tienda online; somos un taller artesanal de personalización textil ubicado en Barcelona. Cada pedido se imprime con mimo y atención al detalle en nuestras propias instalaciones.
                </p>
                <p style="font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 25px;">
                    ¿Estás cerca? Ahorra tiempo y costes de envío seleccionando <strong>"Recogida en Local"</strong>. Podrás recoger tu pedido personalmente, ver nuestras muestras físicas de telas y comprobar la calidad de nuestras impresiones. ¡Estaremos encantados de asesorarte!
                </p>
                <div style="background: #f9f9f9; padding: 25px; border-radius: 15px; border-left: 5px solid #ff6b6b;">
                    <p style="font-size: 17px; color: #2c3e50; font-weight: 700; margin-bottom: 15px;">
                        <a href="https://www.google.com/maps/search/?api=1&query=Camiglobo+Barcelona+Calle+Doctor+Bove+115" target="_blank" style="text-decoration: none; color: inherit; transition: 0.3s;" onmouseover="this.style.color='#ff6b6b'">
                            <i class="fas fa-map-marker-alt" style="color: #ff6b6b; margin-right: 12px;"></i> Calle Doctor Bové 115, 08032 Barcelona
                        </a>
                    </p>
                    <p style="font-size: 17px; color: #2c3e50; font-weight: 700; margin-bottom: 20px;">
                        <i class="fas fa-phone-alt" style="color: #ff6b6b; margin-right: 12px;"></i> +34 653 851 786
                    </p>
                    <a href="contacto.php" style="display: inline-flex; align-items: center; gap: 8px; background: #2c3e50; color: white; padding: 12px 28px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.3s;" onmouseover="this.style.background='#ff6b6b'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#2c3e50'; this.style.transform='translateY(0)'">
                        <i class="fas fa-headset"></i> CONTÁCTANOS
                    </a>
                </div>
            </div>
            <div style="width: 100%; height: 450px; border-radius: 20px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                <iframe title="Mapa de ubicación de nuestra tienda Camiglobo en Barcelona" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2991.64!2d2.159!3d41.425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12a4980998f86915%3A0x633454b8a2e76f5!2sCarrer+del+Doctor+Bov%C3%A9%2C+115%2C+08032+Barcelona!5e0!3m2!1ses!2ses!4v1708000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>


<section style="padding: 80px 0; background: #f2f2f2; border-top: 1px solid #ddd;">
    <div class="container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-weight: 800;">El Taller en Vivo</h2>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 50px;">Mira nuestro proceso creativo en <a href="https://www.instagram.com/camiglobo/" target="_blank" style="color:#e74c3c; font-weight:bold; text-decoration:none;">@camiglobo</a></p>
        
        <div style="display: flex; gap: 20px; justify-content: center; max-width: 1400px; margin: 0 auto; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/video_35680617cf358360_1773949204.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Nuestro proceso creativo</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768094939.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Personalización para la Guardia Urbana de Barcelona</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_@camiglobocamiglobo_1775768415046.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Camiseta Stranger Things — viral en TikTok</p>
            </div>
            <div style="flex: 1; min-width: 220px; max-width: 320px;">
                <video controls muted autoplay loop playsinline style="width: 100%; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <source src="uploads/ssstik.io_1775768996758.mp4" type="video/mp4">
                </video>
                <p style="text-align:center; color:#888; margin-top:10px; font-size:0.85rem;">Vinilo textil con plotter Siser Romeo</p>
            </div>
        </div>
    </div>
</section>

<section style="padding: 70px 0; background: #fff; border-top: 1px solid #eee;">
    <div class="container">
        <h2 style="text-align:center; color:#2c3e50; margin-bottom:8px; font-weight:800;">Lo que dicen nuestros clientes</h2>
        <p style="text-align:center; color:#888; margin-bottom:40px; font-size:1rem;">Más de 10.000 prendas entregadas y más de 500 pedidos personalizados y contando ⭐</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">

            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Encargué una camiseta con la foto de mi perro y quedó espectacular. La calidad de impresión es brutal, los colores muy vivos. Repetiré seguro."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#e74c3c,#ff6b6b); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">M</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">María González</div>
                        <div style="font-size:11px; color:#aaa;">Barcelona</div>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Pedí 10 sudaderas para el equipo con nuestro logo. Trato muy cercano, me asesoraron en todo y el resultado fue perfecto. Los chicos lo hacen genial."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#27ae60,#2ecc71); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">J</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Jordi Puigdomènech</div>
                        <div style="font-size:11px; color:#aaa;">Sabadell</div>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"El editor online es súper fácil de usar, lo hice todo desde el móvil en 10 minutos. En 3 días tenía la camiseta en casa. Muy recomendable."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#3498db,#74b9ff); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">L</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Laura Sánchez</div>
                        <div style="font-size:11px; color:#aaa;">Madrid</div>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div style="color:#f1c40f; font-size:18px; margin-bottom:12px;">★★★★★</div>
                <p style="color:#444; font-size:0.95rem; line-height:1.7; margin-bottom:16px;">"Compré una taza personalizada como regalo de cumple y mi madre flipó. Muy buena calidad, el diseño se ve nítido y no se borra al lavar. 100% recomiendo."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#9b59b6,#8e44ad); display:flex; align-items:center; justify-content:center; color:white; font-weight:900; font-size:15px;">A</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:#2c3e50;">Andrés Romero</div>
                        <div style="font-size:11px; color:#aaa;">Badalona</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

</main><style>
.review-card {
    background:#f9f9f9; border-radius:20px; padding:25px; border:1px solid #eee; box-shadow:0 4px 15px rgba(0,0,0,0.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.review-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    border-color: #f1c40f;
}
   body { 
    background-color: #f2f2f2; 
    margin: 0; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    overflow-x: clip; 
    width: 100%;
    position: relative;
}
html { 
    scroll-behavior: smooth; 
    overflow-x: clip; 
    width: 100%;
}
    .product-card { background-color: white !important; }
    img { max-width: 100%; height: auto; }
    
    * {
        box-sizing: border-box;
        max-width: 100%;
    }
    
    .container {
        width: 100%;
        padding-left: 15px;
        padding-right: 15px;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }

    /* MASCARA PARA VÍDEOS DE INSTAGRAM - SIN PARTE BLANCA */
    .instagram-wrapper {
        width: 330px; 
        height: 580px; 
        overflow: hidden;
        border-radius: 25px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        background: #000;
        position: relative;
    }
    
    .instagram-wrapper iframe,
    .instagram-wrapper .instagram-media,
    .instagram-wrapper blockquote {
        width: 100% !important;
        min-width: 100% !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 115% !important;
        transform: translateY(-55px);
        background: transparent !important;
    }
    
    /* Ocultar completamente la parte de metadatos (likes, comentarios, etc.) */
    .instagram-wrapper .instagram-media > div > div:last-child,
    .instagram-wrapper .instagram-media > div > p,
    .instagram-wrapper .instagram-media a[href*="instagram.com/p/"] + div,
    .instagram-wrapper .instagram-media ._aagv,
    .instagram-wrapper .instagram-media ._aagu,
    .instagram-wrapper .instagram-media ._aagt,
    .instagram-wrapper .instagram-media ._a9_b,
    .instagram-wrapper .instagram-media ._a9_c {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        height: 0 !important;
    }
    
    /* Estilo para la descripción en la Home */
    .product-description-home {
        font-size: 14px;
        color: #666;
        line-height: 1.4;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 2; /* Máximo 2 líneas */
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 40px; /* Altura fija para alineación */
    }

    @media (max-width: 900px) {
        body, html {
            overflow-x: clip;  /* CAMBIADO de hidden a clip */
            width: 100%;
        }
        
        .container {
            width: 100%;
            padding-left: 15px;
            padding-right: 15px;
            box-sizing: border-box;
        }
        
        .hero-title { 
            font-size: 2rem !important; 
            word-wrap: break-word;
            padding-left: 15px;
            padding-right: 15px;
            margin-bottom: 12px !important;
        }

        .hero-text {
            font-size: 1rem !important;
            margin-bottom: 20px !important;
            padding: 0 15px;
        }
        
        .hero-buttons { 
            flex-direction: column !important; 
            gap: 12px !important; 
            padding: 0 20px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .hero-buttons a { 
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
            padding: 14px 20px !important;
            font-size: 1rem !important;
        }

        .hero {
            min-height: 100dvh !important;
            padding: 20px 0 !important;
            justify-content: center;
        }
        
        .grid-categories { 
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px;
            gap: 15px;
            width: 100%;
        }
        
        .grid-categories a {
            width: 100%;
        }
        
        .grid-categories a div {
            width: 100%;
            box-sizing: border-box;
            background-size: cover !important;
        }
        
        [style*="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))"] {
            grid-template-columns: 1fr !important;
            gap: 15px;
            padding: 0 5px;
        }
        
        [style*="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr))"] {
            grid-template-columns: 1fr !important;
            gap: 20px;
        }
        
        .instagram-wrapper { 
            width: 100%; 
            max-width: 330px;
            margin-left: auto;
            margin-right: auto;
        }
        
        [style*="height: 450px"] {
            height: 350px !important;
            width: 100% !important;
        }
        
        section {
            width: 100%;
            overflow-x: hidden;
        }
    }
</style>


<script>
    // Hacer que los mensajes de éxito/error desaparezcan solos a los 6 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.container [style*="border-radius:15px"]');
        alerts.forEach(el => {
            el.style.transition = "opacity 0.5s ease";
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 6000);
</script>
<?php include 'includes/footer.php'; ?>