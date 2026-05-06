<?php 
require_once 'includes/config.php';

// --- Definimos el título ANTES del header ---
$meta_title = "Política de Envíos y Plazos"; 

include 'includes/header.php'; 
?>

<style>
    :root {
        --primary-color: #e74c3c;
        --light-color: #f8f9fa;
        --gray-color: #777;
        --dark-color: #2c3e50;
        --accent-color: #27ae60;
        --shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    
    .legal-page {
        background: #fcfcfc; /* Un tono roto para que el blanco de la caja resalte */
    }

    /* Mejora de legibilidad en móviles */
    @media (max-width: 600px) {
        .legal-content { padding: 10px; }
        .legal-content > div { padding: 25px !important; }
        h1 { font-size: 26px !important; }
    }
</style>

<section class="legal-page" style="padding: 60px 0; min-height: 80vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-size: 36px; font-weight: 900;">Política de Envíos</h1>
        <p style="text-align: center; color: #95a5a6; margin-bottom: 50px; font-weight: 600;">Todo lo que necesitas saber sobre cómo llega tu Camiglobo.</p>
        
        <div class="legal-content" style="max-width: 850px; margin: 0 auto; line-height: 1.8;">
            <div style="background: white; padding: 50px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid #f1f1f1;">
                
                <h2 style="color: var(--dark-color); margin-bottom: 30px; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 30px; background: var(--primary-color); display: inline-block; border-radius: 4px;"></span>
                    TARIFAS Y TIEMPOS
                </h2>
                
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding: 25px; background: var(--light-color); border-radius: 15px; border: 1px solid #eee; transition: 0.3s;" onmouseover="this.style.borderColor='#e74c3c'" onmouseout="this.style.borderColor='#eee'">
                    <div style="background: white; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-shrink: 0;">
                        <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: var(--dark-color); font-weight: 900; font-size: 18px;">Envío Peninsular: 4,95€</h3>
                        <p style="margin: 5px 0 0 0; color: var(--gray-color); font-weight: 600; font-size: 14px;">Entrega garantizada en 3-5 días laborables.</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 40px; padding: 25px; background: #eefdf3; border-radius: 15px; border: 1px solid #c8e6c9; transition: 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="background: white; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-shrink: 0;">
                        <i class="fas fa-star" style="font-size: 1.5rem; color: var(--accent-color);"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: var(--accent-color); font-weight: 900; font-size: 18px;">Envío GRATIS</h3>
                        <p style="margin: 5px 0 0 0; color: #2e7d32; font-weight: 700; font-size: 14px;">En todos tus pedidos superiores a 45€.</p>
                    </div>
                </div>

                <div style="color: #444; font-size: 15px;">
                    <h3 style="color: var(--dark-color); margin: 35px 0 15px 0; font-weight: 900;">🌍 Zonas de Envío</h3>
                    <p>En <strong>Camiglobo Barcelona</strong> operamos principalmente en la España peninsular. Si te encuentras en Baleares, Canarias, Ceuta o Melilla, escríbenos por WhatsApp antes de pedir para darte la mejor tarifa personalizada.</p>

                    <h3 style="color: var(--dark-color); margin: 35px 0 15px 0; font-weight: 900;">⏳ Fabricación y Entrega</h3>
                    <p>Al ser expertos en personalización, tenemos dos tiempos que debes conocer:</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> 
                            <span><strong>Producción:</strong> 24-72h para dejar tu diseño perfecto.</span>
                        </li>
                        <li style="margin-bottom: 12px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> 
                            <span><strong>Tránsito:</strong> 48-72h de viaje hasta tu puerta.</span>
                        </li>
                    </ul>

                    <h3 style="color: var(--dark-color); margin: 35px 0 15px 0; font-weight: 900;">📦 Seguimiento de Pedido</h3>
                    <p>Nada más salir del taller, te enviaremos un email con tu <strong>código de seguimiento</strong>. Podrás ver dónde está tu camiseta en cada segundo desde tu cuenta o la web del transportista.</p>
                </div>

                <div style="margin-top: 50px; border-top: 2px dashed #eee; padding-top: 40px;">
                    <h3 style="color: var(--dark-color); margin-bottom: 25px; font-weight: 900; text-align: center;">Dudas frecuentes</h3>
                    
                    <details style="background: #f9f9f9; padding: 15px 20px; border-radius: 12px; margin-bottom: 10px; cursor: pointer;">
                        <summary style="font-weight: 800; color: var(--dark-color);">¿Puedo cambiar la dirección de envío?</summary>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;">¡Corre! Si aún no hemos entregado el paquete al transportista, podemos cambiarla. Escríbenos por WhatsApp con tu número de pedido.</p>
                    </details>

                    <details style="background: #f9f9f9; padding: 15px 20px; border-radius: 12px; margin-bottom: 10px; cursor: pointer;">
                        <summary style="font-weight: 800; color: var(--dark-color);">¿Qué pasa si el paquete llega dañado?</summary>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;">En Camiglobo nos hacemos responsables. Si ves la caja golpeada, hazle una foto y no la aceptes, o avísanos en menos de 24h para que te enviemos una nueva sin coste.</p>
                    </details>
                </div>

                <div style="margin-top: 50px; text-align: center;">
                    <a href="https://wa.me/34653851786" style="display: inline-flex; align-items: center; gap: 10px; background: #25D366; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: 900; transition: 0.3s; box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2);">
                        <i class="fab fa-whatsapp" style="font-size: 1.4rem;"></i> ¿MÁS DUDAS? ESCRÍBENOS
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>