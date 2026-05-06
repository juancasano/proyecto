<?php 
require_once 'includes/config.php';

// ---  SEO dinámico para Google ---
$meta_title = "Política de Reembolsos y Devoluciones"; 

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

    .legal-page { background: #fcfcfc; }
    
    .legal-card {
        background: white; 
        padding: 50px; 
        border-radius: 20px; 
        box-shadow: var(--shadow); 
        border: 1px solid #f1f1f1;
    }

    .highlight-box {
        display: flex; 
        align-items: center; 
        gap: 20px; 
        margin-bottom: 35px; 
        padding: 25px; 
        background: #e8f5e8; 
        border-radius: 15px; 
        border-left: 5px solid var(--accent-color);
    }

    .warning-box {
        background: #fff5f5;
        border: 1px solid #feb2b2;
        padding: 25px;
        border-radius: 15px;
        margin: 30px 0;
    }

    .step-list {
        list-style: none;
        padding: 0;
    }

    .step-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .step-number {
        background: var(--dark-color);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        flex-shrink: 0;
        font-size: 14px;
    }

    @media (max-width: 600px) {
        .legal-card { padding: 25px !important; }
        .highlight-box { flex-direction: column; text-align: center; }
        h1 { font-size: 26px !important; }
    }
</style>

<section class="legal-page" style="padding: 60px 0; min-height: 80vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <h1 style="text-align: center; color: var(--dark-color); margin-bottom: 10px; font-size: 36px; font-weight: 900;">Política de Reembolsos</h1>
        <p style="text-align: center; color: #95a5a6; margin-bottom: 50px; font-weight: 600;">Transparencia total en tus devoluciones con Camiglobo.</p>
        
        <div class="legal-content" style="max-width: 850px; margin: 0 auto; line-height: 1.8;">
            <div class="legal-card">
                
                <div class="highlight-box">
                    <div style="background: white; width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-shrink: 0;">
                        <i class="fas fa-history" style="font-size: 1.5rem; color: var(--accent-color);"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #1b5e20; font-weight: 900; font-size: 18px;">Garantía de 15 Días</h3>
                        <p style="margin: 5px 0 0 0; color: #2e7d32; font-weight: 600; font-size: 14px;">Tienes 15 días naturales desde la recepción para solicitar una devolución.</p>
                    </div>
                </div>

                <h2 style="color: var(--dark-color); margin-bottom: 20px; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 30px; background: var(--primary-color); display: inline-block; border-radius: 4px;"></span>
                    Condiciones Generales
                </h2>
                <p>Para que podamos aceptar tu devolución, el artículo debe estar en las mismas condiciones en que lo recibiste: sin usar, con las etiquetas originales y en su embalaje de fábrica.</p>

                <div class="warning-box">
                    <h3 style="color: #c53030; margin: 0 0 10px 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> PRODUCTOS PERSONALIZADOS
                    </h3>
                    <p style="color: #742a2a; font-size: 15px; margin: 0;">
                        Según el <strong>Art. 103 de la Ley 3/2014</strong>, el derecho de desistimiento no se aplica a bienes confeccionados conforme a las especificaciones del consumidor. 
                        <br><br>
                        <strong>Esto significa:</strong> Las camisetas diseñadas por ti (con fotos, textos o nombres) <strong>no admiten devolución</strong> a menos que presenten un defecto de fabricación o error de impresión por nuestra parte.
                    </p>
                </div>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">🛠️ ¿Cómo solicitar un reembolso?</h3>
                <div class="step-list">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div><strong>Escríbenos:</strong> Envía un email a <em>camigloboshop@gmail.com</em> con tu número de pedido.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div><strong>Fotos (si hay fallo):</strong> Si el producto tiene un defecto, adjunta fotos claras del error.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div><strong>Envío:</strong> Una vez aprobada, te indicaremos cómo enviarnos el paquete de vuelta.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div><strong>Dinero de vuelta:</strong> Tras recibir e inspeccionar el artículo, recibirás el abono en un plazo de 5 a 10 días.</div>
                    </div>
                </div>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">💳 Métodos y Plazos</h3>
                <p>El reembolso se efectuará siempre en el mismo método de pago que utilizaste:</p>
                <ul style="padding-left: 20px; margin-bottom: 30px;">
                    <li><strong>Tarjeta Bancaria:</strong> Dependiendo de tu banco, puede tardar entre 5 y 10 días.</li>
                    <li><strong>PayPal:</strong> El abono suele ser casi instantáneo tras nuestra confirmación.</li>
                    <li><strong>Bizum / Transferencia:</strong> Se procesará en un máximo de 72h laborables.</li>
                </ul>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">⚠️ Cambios de Talla</h3>
                <p>Si te has equivocado de talla en un producto <strong>no personalizado</strong>, puedes cambiarlo asumiendo los costes de envío de vuelta y reenvío. Si el producto es personalizado, no podemos realizar el cambio, por lo que te recomendamos revisar siempre nuestra guía de tallas.</p>

                <div style="margin-top: 50px; padding: 30px; background: var(--light-color); border-radius: 20px; text-align: center; border: 1px solid #eee;">
                    <h3 style="color: var(--dark-color); margin-bottom: 15px; font-weight: 900;">¿Tienes una incidencia?</h3>
                    <p style="margin-bottom: 25px; color: var(--gray-color);">Nuestro equipo de soporte te responderá en menos de 24 horas laborables.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                        <a href="mailto:camigloboshop@gmail.com" style="background: var(--dark-color); color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 14px;">
                            <i class="fas fa-envelope"></i> EMAIL SOPORTE
                        </a>
                        <a href="https://wa.me/34653851786" style="background: #25D366; color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 14px;">
                            <i class="fab fa-whatsapp"></i> WHATSAPP DIRECTO
                        </a>
                    </div>
                </div>

                <p style="margin-top: 40px; font-size: 12px; color: #bbb; text-align: center;">
                    Camiglobo Barcelona cumple estrictamente con la normativa de Consumo de la Unión Europea.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>