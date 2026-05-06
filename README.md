# Camiglobo Barcelona — E-commerce de Camisetas Personalizadas

> Proyecto web real en producción desarrollado íntegramente desde cero.

**[🌐 Ver en producción → camiglobo.com](https://camiglobo.com)** · **[📖 Documentación técnica completa](https://juancasano.github.io/documentacion-camiglobo.html)**

### Home

[![Home de Camiglobo](https://juancasano.github.io/assets/camiglobo.png)](https://camiglobo.com)

E-commerce completo desarrollado de principio a fin en PHP, MySQL, JavaScript ES6 y CSS3. Incluye catálogo dinámico con +100 productos, carrito y checkout, pasarela de pago PayPal, sistema de usuarios con login propio y Google OAuth, recuperación de contraseña, newsletter con envío masivo (PHPMailer/SMTP), personalizador online con Fabric.js, panel de administración propio (productos, pedidos, clientes, biblioteca de recursos), SEO técnico (Sitemap XML, Search Console, Analytics) y seguridad reforzada (HTTPS/SSL, .htaccess, variables de entorno). Desplegado en producción en Hostinger con dominio y DNS propios.

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
- **Seguridad** — HTTPS/SSL, variables de entorno, protección .htaccess, validación de formularios

---

## Estructura del proyecto

```
├── admin/               # Panel de administración
├── includes/            # PHPMailer, configuración, helpers
├── images/              # Recursos gráficos
├── carrito.php          # Gestión del carrito
├── checkout.php         # Proceso de compra
├── config.php           # Configuración de base de datos
├── personalizador.php   # Herramienta de personalización
└── .htaccess            # Seguridad y redirecciones
```

---

## Autor

**Juan Manuel Casanova Lacasa** — Desarrollador Web Full Stack  
📍 Barcelona, España  
🔗 [Portafolio](https://juancasano.github.io) · [LinkedIn](https://www.linkedin.com/in/juan-manuel-casanova-lacasa/) · [juancasano83@gmail.com](mailto:juancasano83@gmail.com)