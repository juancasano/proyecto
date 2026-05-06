<?php 
require_once 'includes/config.php';

// --- SEO dinámico para Google ---
$meta_title = "Términos y Condiciones de Uso"; 

include 'includes/header.php'; 
?>

<style>
    :root {
        --primary-color: #e74c3c;
        --light-color: #f8f9fa;
        --gray-color: #777;
        --dark-color: #2c3e50;
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

    .section-title {
        color: var(--dark-color); 
        margin: 40px 0 20px 0; 
        font-weight: 900; 
        font-size: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: var(--primary-color);
        font-size: 1.2rem;
    }

    .info-block {
        background: var(--light-color);
        padding: 20px;
        border-radius: 15px;
        border-left: 5px solid var(--dark-color);
        margin: 25px 0;
    }

    @media (max-width: 600px) {
        .legal-card { padding: 25px !important; }
        h1 { font-size: 26px !important; }
    }
</style>

<section class="legal-page" style="padding: 60px 0; min-height: 80vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <h1 style="text-align: center; color: var(--dark-color); margin-bottom: 10px; font-size: 36px; font-weight: 900;">Términos y Condiciones</h1>
        <p style="text-align: center; color: #95a5a6; margin-bottom: 50px; font-weight: 600;">Reglas del juego claras para una experiencia Camiglobo perfecta.</p>
        
        <div class="legal-content" style="max-width: 850px; margin: 0 auto; line-height: 1.8;">
            <div class="legal-card">
                
                <h2 style="color: var(--dark-color); margin-bottom: 25px; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 30px; background: var(--primary-color); display: inline-block; border-radius: 4px;"></span>
                    Condiciones Generales
                </h2>
                
                <p>Bienvenido a <strong>camiglobo.com</strong>. Al acceder y utilizar nuestro sitio web, aceptas someterte a las presentes Condiciones Generales de Uso. Este sitio es propiedad de <strong>CAMIGLOBO SL</strong>, con sede en Barcelona.</p>

                <div class="info-block">
                    <i class="fas fa-info-circle"></i> 
                    El uso de esta web implica la aceptación de nuestra <strong>Política de Privacidad</strong> y <strong>Política de Reembolsos</strong>.
                </div>

                <h3 class="section-title"><i class="fas fa-user-shield"></i> Compromiso del Usuario</h3>
                <p>Como usuario de Camiglobo, te comprometes a hacer un uso lícito del sitio. Queda estrictamente prohibido:</p>
                <ul style="padding-left: 20px; margin-bottom: 20px;">
                    <li>Utilizar contenidos de la web para fines publicitarios sin permiso.</li>
                    <li>Subir diseños que vulneren derechos de autor o que sean ofensivos/ilegales.</li>
                    <li>Intentar hackear o sobrecargar el sistema del servidor.</li>
                </ul>

                <h3 class="section-title"><i class="fas fa-copyright"></i> Propiedad Intelectual</h3>
                <p>Todo el contenido de <strong>camiglobo.com</strong> (textos, logos, software de diseño, fotografías) está protegido por leyes de propiedad intelectual. 
                <br><br>
                <strong>Nota sobre tus diseños:</strong> Al subir un diseño para personalizar una camiseta, garantizas que tienes los derechos para usar esa imagen. Camiglobo SL no se hace responsable de infracciones de copyright cometidas por el usuario.</p>

                <h3 class="section-title"><i class="fas fa-exclamation-circle"></i> Limitación de Responsabilidad</h3>
                <p>En Camiglobo trabajamos para que la web vuele, pero no garantizamos la ausencia de errores técnicos puntuales o interrupciones del servicio. No nos hacemos responsables de:</p>
                <ul style="padding-left: 20px; margin-bottom: 20px;">
                    <li>Errores en el diseño final si la imagen subida por el usuario es de baja calidad.</li>
                    <li>Retrasos causados por las empresas de transporte (Correos, SEUR, MRW).</li>
                </ul>

                <h3 class="section-title"><i class="fas fa-gavel"></i> Ley Aplicable</h3>
                <p>Estas condiciones se rigen por la <strong>legislación española</strong>. Para cualquier conflicto, las partes se someten a los juzgados y tribunales de la ciudad de <strong>Barcelona</strong>.</p>

                <div style="margin-top: 50px; padding: 30px; background: #2c3e50; border-radius: 15px; color: white; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; font-weight: 900;">¿Alguna duda legal?</h4>
                    <p style="margin: 0; opacity: 0.9; font-size: 14px;">
                        Estamos a tu disposición en: <br>
                        <strong>camigloboshop@gmail.com</strong>
                    </p>
                    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                         <a href="https://wa.me/34653851786" style="color: #25D366; text-decoration: none; font-weight: 800;"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    </div>
                </div>

                <p style="margin-top: 40px; font-size: 12px; color: #bbb; text-align: center;">
                    Última actualización: <?php echo date('d/m/Y'); ?> | Camiglobo SL - Barcelona.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>