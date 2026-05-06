<?php 
require_once 'includes/config.php';

// --- Definimos el título ANTES del header para el SEO ---
$meta_title = "Política de Privacidad y Protección de Datos"; 

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

    .legal-page {
        background: #fcfcfc;
    }

    .legal-card {
        background: white; 
        padding: 50px; 
        border-radius: 20px; 
        box-shadow: var(--shadow); 
        border: 1px solid #f1f1f1;
    }

    .principio-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #eee;
        transition: 0.3s;
    }

    .principio-item:hover {
        border-color: var(--primary-color);
        transform: translateX(5px);
    }

    .principio-item i {
        color: var(--primary-color);
        font-size: 1.2rem;
        margin-top: 3px;
    }

    @media (max-width: 600px) {
        .legal-card { padding: 25px !important; }
        h1 { font-size: 26px !important; }
    }
</style>

<section class="legal-page" style="padding: 60px 0; min-height: 80vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <h1 style="text-align: center; color: var(--dark-color); margin-bottom: 10px; font-size: 36px; font-weight: 900;">Política de Privacidad</h1>
        <p style="text-align: center; color: #95a5a6; margin-bottom: 50px; font-weight: 600;">Tu privacidad es nuestra prioridad en Camiglobo Barcelona.</p>
        
        <div class="legal-content" style="max-width: 850px; margin: 0 auto; line-height: 1.8;">
            <div class="legal-card">
                
                <h2 style="color: var(--dark-color); margin-bottom: 25px; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 30px; background: var(--primary-color); display: inline-block; border-radius: 4px;"></span>
                    Información al Usuario
                </h2>
                
                <p><strong>CAMIGLOBO SL</strong>, como Responsable del Tratamiento, le informa que, según lo dispuesto en el <strong>Reglamento (UE) 2016/679 (RGPD)</strong> y en la <strong>L.O. 3/2018 (LOPDGDD)</strong>, trataremos su datos personales con total seguridad y transparencia.</p>
                
                <p style="margin-bottom: 30px;">En esta política detallamos cómo protegemos tus datos cuando diseñas tus camisetas con nosotros.</p>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">🛡️ Nuestros Principios</h3>
                
                <div class="principio-item">
                    <i class="fas fa-balance-scale"></i>
                    <div>
                        <strong>Legalidad y Transparencia:</strong> Solo pedimos los datos necesarios para enviarte tu pedido.
                    </div>
                </div>

                <div class="principio-item">
                    <i class="fas fa-database"></i>
                    <div>
                        <strong>Minimización:</strong> Si no es necesario para la factura o el envío, no te lo pediremos.
                    </div>
                </div>

                <div class="principio-item">
                    <i class="fas fa-user-shield"></i>
                    <div>
                        <strong>Seguridad:</strong> Aplicamos cifrado y medidas técnicas para que tus diseños y datos estén a salvo.
                    </div>
                </div>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">📊 Datos que Tratamos</h3>
                <p>Para procesar tus pedidos en Camiglobo, recogemos:</p>
                <ul style="padding-left: 20px; margin-bottom: 30px;">
                    <li><strong>Identificación:</strong> Nombre, apellidos y DNI (para facturación).</li>
                    <li><strong>Contacto:</strong> Email y teléfono (para avisos de envío).</li>
                    <li><strong>Logística:</strong> Dirección completa de entrega.</li>
                    <li><strong>Datos de Cuenta:</strong> Tu historial de pedidos y direcciones guardadas para facilitar futuras compras.</li>
                    <li><strong>Carrito Persistente:</strong> Para tu comodidad, si inicias sesión, guardamos el contenido de tu carrito en tu cuenta para que puedas acceder a él desde cualquier dispositivo. Como visitante, este proceso se realiza mediante cookies técnicas.</li>
                </ul>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">⚖️ Tus Derechos</h3>
                <p>Tienes el control total sobre tus datos. Puedes ejercer tus derechos de:</p>
                <div style="background: var(--light-color); padding: 25px; border-radius: 15px; border: 1px solid #eee; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 30px;">
                    <span><i class="fas fa-eye" style="color:var(--primary-color)"></i> Acceso</span>
                    <span><i class="fas fa-edit" style="color:var(--primary-color)"></i> Rectificación</span>
                    <span><i class="fas fa-trash-alt" style="color:var(--primary-color)"></i> Supresión</span>
                    <span><i class="fas fa-file-export" style="color:var(--primary-color)"></i> Portabilidad</span>
                </div>
                <p>Simplemente envíanos un email a <a href="mailto:camigloboshop@gmail.com" style="color: var(--primary-color); font-weight: bold; text-decoration: none;">camigloboshop@gmail.com</a> con tu solicitud.</p>

                <h3 style="color: var(--dark-color); margin: 40px 0 20px 0; font-weight: 900; font-size: 20px;">🏢 Información Legal del Responsable</h3>
                
                <div style="margin-top: 20px; padding: 30px; background: #2c3e50; border-radius: 15px; color: white; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; color: #fff; font-weight: 900;">CAMIGLOBO SL</h4>
                    <p style="margin: 0; opacity: 0.9; font-size: 15px;">
                        C/ DOCTOR BOVÉ 115<br>
                        08032 BARCELONA (BARCELONA)<br>
                        <i class="fas fa-envelope" style="margin-right: 5px;"></i> camigloboshop@gmail.com
                    </p>
                </div>

                <p style="margin-top: 40px; font-size: 13px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                    Esta política ha sido revisada para cumplir con el RGPD. Última actualización: <?php echo date('d/m/Y'); ?>.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>