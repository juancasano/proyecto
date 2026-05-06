<?php 
require_once 'includes/config.php';

// --- SEO dinámico ---
$meta_title = "Preguntas Frecuentes (FAQ)"; 

include 'includes/header.php'; 
?>

<style>
    :root {
        --primary-color: #e74c3c;
        --dark-color: #2c3e50;
        --light-bg: #fcfcfc;
    }

    .faq-page { background: var(--light-bg); padding: 60px 0; min-height: 80vh; }
    
    .faq-container { max-width: 850px; margin: 0 auto; padding: 0 20px; }

    .faq-card { 
        background: white; 
        padding: 40px; 
        border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        border: 1px solid #f1f1f1; 
        margin-bottom: 30px; 
    }

    .faq-category-title { 
        color: var(--dark-color); 
        border-bottom: 2px solid var(--primary-color); 
        padding-bottom: 15px; 
        margin-bottom: 25px; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        font-weight: 900; 
        text-transform: uppercase;
        font-size: 20px;
    }

    /* ESTILO ACORDEÓN  */
    details {
        margin-bottom: 15px;
        border-radius: 12px;
        background: #f9f9f9;
        transition: 0.3s;
        border: 1px solid transparent;
    }

    details[open] {
        background: white;
        border-color: #eee;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    summary {
        padding: 18px 20px;
        font-weight: 800;
        color: var(--dark-color);
        cursor: pointer;
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        outline: none;
        font-size: 16px;
    }

    summary::after {
        content: '\f078'; /* FontAwesome Chevron Down */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        transition: 0.3s;
        font-size: 12px;
        color: var(--primary-color);
    }

    details[open] summary::after {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 20px 20px 20px;
        color: #666;
        line-height: 1.7;
        font-size: 15px;
    }

    .cta-box {
        margin-top: 60px;
        text-align: center;
        background: white;
        padding: 40px;
        border-radius: 20px;
        border: 2px dashed #eee;
    }

    @media (max-width: 600px) {
        .faq-card { padding: 25px !important; }
        h1 { font-size: 28px !important; }
    }
</style>

<section class="faq-page">
    <div class="faq-container">
        
        <h1 style="text-align: center; color: var(--dark-color); margin-bottom: 10px; font-size: 36px; font-weight: 900;">Preguntas Frecuentes</h1>
        <p style="text-align: center; color: #95a5a6; margin-bottom: 50px; font-weight: 600;">¿Tienes dudas? Aquí tienes las respuestas más rápidas.</p>

        <div class="faq-card">
            <h2 class="faq-category-title"><i class="fas fa-palette"></i> Personalización</h2>
            
            <details>
                <summary>¿Cómo envío mi diseño para personalizar?</summary>
                <div class="faq-answer">
                    Es muy fácil: puedes subirlo directamente en nuestro <strong>estudio de diseño online</strong> al añadir el producto al carrito. Si prefieres, también puedes enviarlo tras la compra por email a <strong style="color:var(--primary-color)">camigloboshop@gmail.com</strong> o por WhatsApp.
                </div>
            </details>

            <details>
                <summary>¿Qué calidad debe tener mi imagen?</summary>
                <div class="faq-answer">
                    Para un resultado óptimo, recomendamos archivos <strong>PNG sin fondo</strong> o JPG en alta resolución. No te preocupes: si vemos que tu imagen se va a ver pixelada, te avisaremos antes de imprimir para buscar una solución.
                </div>
            </details>

            <details>
                <summary>¿Qué técnica de impresión utilizáis?</summary>
                <div class="faq-answer">
                    Utilizamos técnicas de impresión de última generación — <strong>DTF de alta definición, sublimación, SubliFlock y vinilo textil de corte</strong> — para garantizar que los colores permanezcan vivos tras decenas de lavados. Seleccionamos la técnica ideal según el material y el diseño para el mejor resultado posible. Cada diseño se imprime con tintas profesionales y se fija a alta temperatura en nuestro taller de Barcelona.
                </div>
            </details>
        </div>

        <div class="faq-card">
            <h2 class="faq-category-title"><i class="fas fa-shipping-fast"></i> Envíos y Plazos</h2>
            
            <details>
                <summary>¿Cuánto tardaré en recibir mi pedido?</summary>
                <div class="faq-answer">
                    Los pedidos personalizados suelen estar listos en <strong>2-3 días de producción</strong>. Una vez salen del taller, el envío tarda entre 24h y 72h laborables según la zona de España en la que estés.
                </div>
            </details>

            <details>
                <summary>¿Es gratis el envío?</summary>
                <div class="faq-answer">
                    ¡Sí! En Camiglobo Barcelona premiamos los pedidos grandes. Si tu compra supera los <strong>45€</strong>, el envío corre de nuestra cuenta.
                </div>
            </details>
        </div>

        <div class="faq-card">
            <h2 class="faq-category-title"><i class="fas fa-shield-alt"></i> Pagos y Devoluciones</h2>
            
            <details>
                <summary>¿Qué métodos de pago aceptan?</summary>
                <div class="faq-answer">
                    Aceptamos <strong>PayPal, Bizum, Tarjeta de Crédito y Transferencia bancaria</strong>. Todos los pagos se procesan mediante pasarelas blindadas. Tus datos financieros nunca se guardan en nuestro servidor.
                </div>
            </details>

            <details>
                <summary>¿Puedo devolver una camiseta si no me gusta?</summary>
                <div class="faq-answer">
                    Si el producto es genérico, tienes 15 días. Si es un <strong>producto personalizado</strong>, solo aceptamos devoluciones si hay un defecto de fabricación o error de impresión, ya que se fabrica exclusivamente para ti. Revisa bien tu diseño antes de confirmar.
                </div>
            </details>
        </div>

        <div class="cta-box">
            <h2 style="font-size: 24px; color: var(--dark-color); margin-bottom: 20px; font-weight: 900;">¿Tu duda no está aquí?</h2>
            <p style="color: #7f8c8d; margin-bottom: 30px; font-weight: 600;">Escríbenos y nuestro equipo te responderá personalmente.</p>
            
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="https://wa.me/34653851786" target="_blank" style="background: #25D366; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: 900; display: flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 8px 20px rgba(37, 211, 102, 0.2);">
                    <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> WHATSAPP DIRECTO
                </a>
                <a href="mailto:camigloboshop@gmail.com" style="background: var(--dark-color); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: 900; display: flex; align-items: center; gap: 10px; transition: 0.3s;">
                    <i class="fas fa-envelope"></i> ENVIAR EMAIL
                </a>
            </div>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>