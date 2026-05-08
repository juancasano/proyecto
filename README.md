# Camiglobo Barcelona — E-commerce de Camisetas Personalizadas

> Proyecto web real en producción desarrollado íntegramente desde cero.

**[🌐 Ver en producción → camiglobo.com](https://camiglobo.com)** · **[📖 Documentación técnica completa](https://juancasano.github.io/documentacion-camiglobo.html)**

### Home

[![Home de Camiglobo](https://juancasano.github.io/assets/camiglobo.png)](https://camiglobo.com)

E-commerce completo desarrollado de principio a fin en PHP, MySQL, JavaScript ES6 y CSS3. Incluye catálogo dinámico con +100 productos, carrito y checkout, pasarela de pago PayPal, sistema de usuarios con login propio y Google OAuth, recuperación de contraseña, newsletter con envío masivo (PHPMailer/SMTP), personalizador online con Fabric.js, panel de administración propio (productos, pedidos, clientes, biblioteca de recursos), SEO técnico (Sitemap XML, Search Console, Analytics) y seguridad multicapa (18 capas: anti-SQLi/CSRF/XSS, BCRYPT, rate limiting, reCAPTCHA, CSP, HSTS, audit log, GDPR). Desplegado en producción en Hostinger con dominio y DNS propios.

### Catálogo de productos

[![Catálogo de productos](https://juancasano.github.io/assets/camiglobo-productos.png)](https://camiglobo.com/productos.php)

Más de 100 referencias activas en producción cargadas dinámicamente desde MySQL: camisetas, sudaderas, hoodies, cuadros y tazas con diseños de anime, manga y cultura pop. Grid responsive con tarjetas de producto (imagen, título, precio y CTA), búsqueda por palabra clave, filtros y vista de detalle individual. Cada producto se puede comprar tal cual o personalizar con el editor interactivo. Optimizado para mobile, tablet y escritorio.

### Personalizador online

[![Personalizador online](https://juancasano.github.io/assets/camiglobo-personalizador.png)](https://camiglobo.com/personalizar.php)

Editor de diseño visual interactivo construido con **Fabric.js 5.3.1** sobre canvas. Permite personalizar prendas en **5 zonas independientes** (frontal, espalda, nuca, manga izquierda y manga derecha) con texto editable (**20 Google Fonts**, **16 efectos**: neón, oro, fuego, glitch, 3D…), subida de imágenes propias, biblioteca personal de diseños, filtros, stickers, plantillas, undo/redo, auto-guardado del progreso (JSON serializado en BD) y previsualización en tiempo real. Calcula el precio dinámicamente según las zonas personalizadas (doble cara, nuca, mangas) y exporta los diseños finales a PNG para producción.

---

## Stack tecnológico

| Capa | Tecnologías |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript ES6+ |
| **Backend** | PHP, MySQL |
| **Email** | PHPMailer / SMTP |
| **Pagos** | API PayPal |
| **SEO** | Sitemap XML, Google Search Console, Google Analytics |
| **Seguridad** | HTTPS/SSL, .htaccess, variables de entorno (.env) |
| **Despliegue** | Hostinger, DNS, servidor de producción |
| **Control de versiones** | Git / GitHub |

---

## Funcionalidades principales

- **Catálogo de productos** — más de 100 productos activos con filtros y búsqueda
- **Carrito de compra** y **checkout** completo
- **Pasarela de pago PayPal** integrada
- **Emails transaccionales** — confirmación de pedido, avisos de estado
- **Personalizador online interactivo** — subida de imágenes, texto personalizado y previsualización en tiempo real
- **Panel de administración propio** — gestión de pedidos, productos y clientes
- **Diseño responsive** — mobile, tablet y escritorio con CSS3 y media queries
- **SEO técnico** — Sitemap XML, meta tags, robots.txt, Search Console, Analytics
- **Seguridad multicapa** — 18 capas implementadas: anti-SQLi (PDO preparadas), anti-CSRF (tokens `random_bytes(32)`), anti-XSS, BCRYPT, rate limiting, reCAPTCHA v2 + honeypots, sesiones seguras (HttpOnly/Secure/SameSite), CSP, HSTS, audit log, transacciones atómicas, GDPR ([detalle completo en la documentación](https://juancasano.github.io/documentacion-camiglobo.html))

---

## Estructura del proyecto

> Más de 70 archivos PHP organizados por funcionalidad. Estructura plana (sin framework MVC) heredada del proceso orgánico de aprendizaje (ver «Notas técnicas y autocrítica» más abajo).

```
camiglobo/
│
├── 🏠 PÁGINAS PÚBLICAS
│   ├── index.php                      # Home con hero y productos destacados
│   ├── productos.php                  # Catálogo con filtros y búsqueda
│   ├── producto.php                   # Detalle de producto individual
│   ├── personalizar.php               # Editor visual con Fabric.js
│   ├── carrito.php                    # Vista del carrito
│   ├── checkout.php                   # Proceso de compra
│   ├── gracias.php                    # Confirmación post-compra
│   ├── contacto.php                   # Formulario de contacto
│   └── faq.php                        # Preguntas frecuentes
│
├── 👤 SISTEMA DE USUARIOS
│   ├── login.php                      # Vista de login
│   ├── registro.php                   # Vista de registro
│   ├── recuperar.php                  # Solicitud de recuperación
│   ├── restablecer.php                # Restablecer contraseña con token
│   ├── perfil.php                     # Panel del usuario
│   ├── mi_pedido_detalle.php          # Detalle de pedido (cliente)
│   ├── cancelar_pedido.php            # Cancelar pedido propio
│   ├── baja.php                       # Eliminar cuenta (GDPR)
│   └── logout.php
│
├── 🛒 GESTIÓN DE CARRITO Y PEDIDOS
│   ├── carrito_accion.php             # Añadir/eliminar/modificar items
│   └── carrito_modificar.php          # Cambio de cantidades
│
├── 🎨 PERSONALIZADOR (DISEÑOS Y RECURSOS)
│   ├── guardar_diseno.php             # Guarda diseño final como PNG
│   ├── guardar_progreso.php           # Auto-save JSON del editor en BD
│   ├── subir_recurso.php              # Upload de imagen del usuario
│   ├── borrar_recurso.php             # Elimina recurso de la biblioteca
│   ├── obtener_recursos.php           # API: lista recursos del usuario
│   └── obtener_productos.php          # API: lista de productos
│
├── ⚙️ PROCESADORES POST (lógica de servidor)
│   ├── procesar_login.php             # Validar credenciales + sesión
│   ├── procesar_registro.php          # Crear usuario + reCAPTCHA
│   ├── procesar_google.php            # OAuth 2.0 con Google
│   ├── procesar_recuperar.php         # Generar token de recuperación
│   ├── procesar_nueva_clave.php       # Cambiar contraseña con token
│   ├── procesar_perfil.php            # Actualizar datos del usuario
│   ├── procesar_pedido.php            # Crear pedido + transacción atómica
│   ├── procesar_pago.php              # Verificación S2S de PayPal
│   └── procesar_newsletter.php        # Suscripción al newsletter
│
├── 🔐 ADMINISTRACIÓN
│   ├── admin_pedidos.php              # Listado de pedidos
│   ├── admin_productos.php            # CRUD de productos
│   ├── admin_pagos.php                # Pagos y reembolsos
│   ├── admin_biblioteca.php           # Recursos subidos por usuarios
│   ├── enviar_masivo.php              # Envío masivo de newsletter
│   ├── lista_clientes_secreta.php     # Listado de clientes (admin)
│   ├── save-products.php              # Endpoint de guardado de productos
│   └── admin/
│       ├── ver_pedido.php             # Detalle de pedido (admin)
│       └── cambiar_estado.php         # Cambiar estado de pedido
│
├── 📄 PÁGINAS LEGALES (LSSI-CE / RGPD)
│   ├── aviso-legal.php
│   ├── politica-privacidad.php
│   ├── politica-envios.php
│   ├── politica-reembolso.php
│   └── terminos-condiciones.php
│
├── 🌐 SEO
│   ├── sitemap.php                    # Sitemap visible
│   ├── sitemap_xml.php                # Sitemap.xml para Search Console
│   └── robots.txt
│
├── 🧩 INCLUDES (núcleo común)
│   ├── config.php                     # PDO, sesiones, CSRF, helpers
│   │                                  #   globales: h(), auditLog(),
│   │                                  #   enviarEmail(), validarRecaptcha()
│   ├── header.php                     # Navbar + meta + búsqueda
│   ├── footer.php                     # Pie con redes sociales
│   ├── pricing.php                    # Cálculo dinámico de precio
│   ├── colors.php                     # Paleta de colores y gradientes del editor
│   ├── PHPMailer/                     # Librería de envío de emails (SMTP)
│   ├── .env-example                   # Plantilla de variables de entorno
│   └── .htaccess                      # Bloquea acceso directo
│
├── 📦 RECURSOS Y SUBIDAS
│   ├── images/                        # Logo y assets gráficos
│   ├── uploads/
│   │   ├── .htaccess                  # Bloquea ejecución de scripts en uploads
│   │   ├── custom/                    # Diseños PNG generados por el editor
│   │   └── recursos/                  # Imágenes subidas por usuarios
│   ├── common.min.css                 # CSS principal del sitio
│   └── favicon.jpg
│
├── 🛠️ OTROS
│   ├── upload.php                     # Endpoint de subida (uso interno)
│   └── ver_detalles.php               # Vista auxiliar de detalles
│
├── 📚 DOCUMENTACIÓN Y CONFIG
│   ├── README.md                      # Este archivo
│   ├── DOCUMENTACION_ENTREGA.html     # Doc técnica con pestañas (94 KB)
│   ├── .htaccess                      # Configuración de seguridad raíz
│   └── .gitignore
```


---

## Notas técnicas

- Primer proyecto full stack desarrollado durante mi formación (IFCD0110).
- PHP procedural sin framework, estructura plana con +70 archivos.
- Desplegado y mantenido en producción real desde el inicio — aprendí a resolver incidencias en vivo, gestionar usuarios reales y cumplir plazos.
- Próximo proyecto (Vue.js — IFCD65): planeado con arquitectura MVC, componentes y tests.

---

## Autor

**Juan Manuel Casanova Lacasa** — Desarrollador Web Full Stack  
📍 Barcelona, España  
🔗 [Portafolio](https://juancasano.github.io) · [LinkedIn](https://www.linkedin.com/in/juan-manuel-casanova-lacasa/) · [juancasano83@gmail.com](mailto:juancasano83@gmail.com)