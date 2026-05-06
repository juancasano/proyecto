<footer id="footer" style="background-color: #111111; color: #ffffff; padding: 4rem 0 2rem; margin-top: 60px; border-top: 4px solid #e74c3c; overflow-x: hidden; font-family: inherit;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <div class="footer-grid">
            
            <div class="footer-col">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.5rem;">
<img src="images/camiglobofavicon.jpg" alt="Logo Camiglobo" width="45" height="45" style="width: 45px; height: 45px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.5);">
<div class="footer-logo-text">CAMIGLOBO</div>
                </div>

                <p style="color: #999; line-height: 1.6; margin-bottom: 2rem; font-size: 0.95rem;">
                    🔥 Camisetas únicas que hablan por ti. Expertos en personalización premium en Barcelona con DTF, sublimación, SubliFlock y vinilo textil. Camisetas, sudaderas, tazas, cuadros, peluches natalicio y ropa laboral. Más de 10.000 prendas entregadas y más de 500 pedidos personalizados.
                </p>
                
                <div class="footer-socials">
                    <a href="https://www.tiktok.com/@camiglobocamiglobo" target="_blank" rel="noopener" aria-label="Síguenos en TikTok" class="soc-tk"><i class="fab fa-tiktok"></i></a>
                    <a href="https://www.instagram.com/camiglobo/" target="_blank" rel="noopener" aria-label="Síguenos en Instagram" class="soc-ig"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.threads.net/@camiglobo" target="_blank" rel="noopener" aria-label="Síguenos en Threads" class="soc-th"><i class="fa-brands fa-threads"></i></a>
                    <a href="https://wa.me/34653851786" target="_blank" rel="noopener" aria-label="Contactar por WhatsApp" class="soc-wa"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Ayuda</h3>
                <ul class="footer-links">
                    <li><a href="contacto.php">Contacto</a></li>
                    <li><a href="faq.php">Preguntas Frecuentes</a></li>
                    <li><a href="politica-envios.php">Política de Envíos</a></li>
                    <li><a href="politica-reembolso.php">Reembolsos</a></li>
                    <li><a href="sitemap.php">Mapa del Sitio</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Legal</h3>
                <ul class="footer-links">
                    <li><a href="aviso-legal.php">Aviso Legal</a></li>
                    <li><a href="politica-privacidad.php">Privacidad</a></li>
                    <li><a href="terminos-condiciones.php">Términos y Condiciones</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Newsletter</h3>
                <p style="color: #999; font-size: 0.9rem; margin-bottom: 1.2rem;">Suscríbete para recibir ofertas flash exclusivas y novedades.</p>
                
                 <form id="form-footer-news" action="procesar_newsletter.php" method="POST">
     <input type="hidden" name="accion" value="alta">
     
     <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
     
     <input type="text" name="bot_check" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
     <input type="email" name="email" required placeholder="Tu correo electrónico" class="news-input">
     
     <!-- reCAPTCHA Widget -->
     <div style="margin-bottom: 20px; display: flex; justify-content: center;">
     <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
 </div>
     
 <button type="submit" class="news-btn">
         SUSCRIBIRME AHORA <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
     </button>
 </form>
            </div>
            
        </div>

        <div class="footer-bottom">
            <div class="footer-contact">
                <a href="mailto:camigloboshop@gmail.com" aria-label="Enviar un correo a Camiglobo"><i class="fas fa-envelope" style="color:#e74c3c;"></i> camigloboshop@gmail.com</a>
                <a href="https://maps.google.com/?q=Calle+Doctor+Bove+115+Barcelona" target="_blank" rel="noopener" aria-label="Ver ubicación en Google Maps"><i class="fas fa-map-marker-alt" style="color:#e74c3c;"></i> C/ Doctor Bové 115, BCN</a>
                <span><i class="fas fa-truck" style="color:#e74c3c;"></i> Envíos a toda España</span>
            </div>
            <div class="footer-copyright">
                © <?php echo date('Y'); ?> Camiglobo Barcelona. Todos los derechos reservados.
            </div>
        </div>
    </div>
</footer>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<div id="toast-alert" class="toast-hidden">
    <i id="toast-icon" class="fas fa-info-circle"></i>
    <span id="toast-msg">Mensaje</span>
</div>

<div id="cookie-banner" style="display: none; position: fixed; bottom: 20px; left: 20px; right: auto; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); color: white; padding: 25px; border-radius: 15px; z-index: 99999; border-left: 5px solid #27ae60; box-shadow: 0 10px 30px rgba(0,0,0,0.5); flex-direction: column; gap: 15px; max-width: 380px; margin: 0;">
     
      <p style="margin: 0; font-size: 14px; font-weight: 500; line-height: 1.6;">Usamos cookies técnicas esenciales para que la web funcione. Permiten guardar tu carrito y mantener tu sesión. No usamos cookies de análisis o marketing.</p>
      
      <div style="display: flex; gap: 10px; width: 100%;">
          <button onclick="manageCookies('true')" style="background: #27ae60; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 50px; font-weight: 800; font-size: 11px; text-transform: uppercase; flex: 1; transition: 0.3s;" onmouseover="this.style.background='#1e8449'" onmouseout="this.style.background='#27ae60'">
              Aceptar
          </button>
          <button onclick="manageCookies('false')" style="background: transparent; color: #ccc; border: 1px solid #555; padding: 10px 20px; cursor: pointer; border-radius: 50px; font-weight: 800; font-size: 11px; text-transform: uppercase; flex: 1; transition: 0.3s;" onmouseover="this.style.color='white'; this.style.borderColor='white'" onmouseout="this.style.color='#ccc'; this.style.borderColor='#555'">
              Rechazar
          </button>
      </div>
  </div>

<script>
    // --- LÓGICA DEL BANNER DE COOKIES ---
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function manageCookies(consent) {
        setCookie('cookie_consent', consent, 365); // Guardar preferencia por 1 año
        document.getElementById('cookie-banner').style.display = 'none';
    }

    // Se ejecuta al cargar la página
    (function() {
        var banner = document.getElementById('cookie-banner');
        // Si no existe la cookie de consentimiento, mostramos el banner
        if (!getCookie('cookie_consent')) {
            banner.style.display = 'flex'; // Usamos flex porque el CSS lo espera
        }
    })();
    // --- FIN DE LA LÓGICA DEL BANNER ---
</script>

   <script>
    function showToast(message, type) {
        var toast = document.getElementById('toast-alert');
        var icon = document.getElementById('toast-icon');
        var msg = document.getElementById('toast-msg');
        toast.className = '';
        toast.classList.add('toast-visible');
        toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        msg.textContent = message;
        setTimeout(function(){ toast.className = 'toast-hidden'; }, 4000);
    }
    var params = new URLSearchParams(window.location.search);
    if(params.get('msg') === 'news_sub') showToast('¡Bienvenido a la familia! Revisa tu email.', 'success');
    else if(params.get('error') === 'news_exists') showToast('Este correo ya está suscrito.', 'error');
    else if(params.get('error') === 'captcha') showToast('La verificación de seguridad falló.', 'error');
    else if(params.get('error') === 'email_invalid') showToast('El correo introducido no es válido.', 'error');
   </script>



<style>
    /* CSS OPTIMIZADO */
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 3rem; margin-bottom: 3rem; }

    .footer-logo-text {
        font-size: 1.8rem; font-weight: 900; letter-spacing: 1px;
        background: linear-gradient(90deg, #fff 0%, #e74c3c 50%, #27ae60 100%);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
    }

    .footer-title { color: #fff; font-size: 1.1rem; font-weight: 800; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px; }

    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: 0.8rem; }
    .footer-links a { color: #aaa; text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: 0.3s; display: inline-block; }
    .footer-links a:hover { color: #e74c3c; transform: translateX(5px); }

    .footer-socials { display: flex; gap: 15px; }
    .footer-socials a { font-size: 20px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; text-decoration: none; color: #fff !important; }
    
    .soc-tk { background: #000; box-shadow: 2px 2px 0px rgba(255,0,80,0.8), -2px -2px 0px rgba(0,242,254,0.8); }
    .soc-tk:hover { transform: translateY(-5px) scale(1.1); filter: brightness(1.2); }
    
    .soc-ig { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); box-shadow: 0 3px 10px rgba(225, 48, 108, 0.3); }
    .soc-ig:hover { transform: translateY(-5px) scale(1.1); box-shadow: 0 8px 20px rgba(225, 48, 108, 0.6); filter: brightness(1.1); }
    
    .footer-socials a.soc-th { background: #fff; color: #000 !important; box-shadow: 0 3px 10px rgba(255, 255, 255, 0.1); }
    .footer-socials a.soc-th:hover { transform: translateY(-5px) scale(1.1); box-shadow: 0 8px 20px rgba(255, 255, 255, 0.4); }
    
    .soc-wa { background: #25D366; box-shadow: 0 3px 10px rgba(37, 211, 102, 0.3); }
    .soc-wa:hover { transform: translateY(-5px) scale(1.1); box-shadow: 0 8px 20px rgba(37, 211, 102, 0.6); filter: brightness(1.1); }

    .news-input { 
        width: 100%; padding: 14px 15px; border-radius: 10px; border: 1px solid #333; 
        background: #1a1a1a; color: #fff; font-size: 0.95rem; outline: none; transition: 0.3s; 
        margin-bottom: 12px; font-family: inherit; box-sizing: border-box;
    }
    .news-input:focus { border-color: #e74c3c; background: #000; box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2); }

    .news-btn { 
        width: 100%; background: linear-gradient(90deg, #000 0%, #e74c3c 50%, #27ae60 100%); 
        color: white; border: none; padding: 14px; border-radius: 50px; font-weight: 900; 
        font-size: 12px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
    }
    .news-btn:hover { transform: translateY(-2px); filter: brightness(1.15); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }

    #toast-alert {
        position: fixed; top: 30px; right: 30px; padding: 15px 25px; border-radius: 12px;
        color: white; font-weight: bold; font-size: 14px; z-index: 999999;
        display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .toast-hidden { transform: translateX(150%); opacity: 0; pointer-events: none; }
    .toast-visible { transform: translateX(0); opacity: 1; pointer-events: auto; }
    .toast-success { background: #27ae60; border-left: 6px solid #1e8449; }
    .toast-error { background: #e74c3c; border-left: 6px solid #c0392b; }
    #toast-icon { font-size: 18px; }

    .footer-bottom { border-top: 1px solid #222; padding-top: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .footer-contact { display: flex; gap: 20px; font-size: 0.85rem; color: #888; flex-wrap: wrap; }
    .footer-contact a { color: inherit; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px; }
    .footer-contact a:hover { color: #fff; }
    .footer-copyright { font-size: 0.85rem; color: #666; font-weight: bold; }

    @media (max-width: 992px) { .footer-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { 
        .footer-grid { grid-template-columns: 1fr; text-align: center; gap: 2.5rem; } 
        .footer-socials, .footer-contact, .footer-bottom { justify-content: center; text-align: center; }
        .footer-links a:hover { transform: translateY(-2px); }
        #cookie-banner { flex-direction: column; text-align: center; }
        #toast-alert { top: auto; bottom: 30px; left: 20px; right: 20px; justify-content: center; }
    }
</style>