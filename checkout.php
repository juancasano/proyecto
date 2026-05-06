<?php 
require_once 'includes/config.php';
include 'includes/pricing.php';
include 'includes/colors.php';
include 'includes/header.php'; 

// --- BLOQUE DE ALERTAS ---
if(isset($_GET['msg']) || isset($_GET['error'])): ?>
<div class="container" id="checkout-alert" style="margin-top: 20px; position: relative; z-index: 1000;">
<?php
    if(isset($_GET['msg']) && $_GET['msg'] === 'reg_success') {
        echo '<div style="background:#ebfbee; color:#2b8a3e; padding:20px; border-radius:15px; text-align:center; border:1px solid #8ce99a; font-weight:700; box-shadow: 0 10px 25px rgba(0,0,0,0.05); animation: fadeInDown 0.5s ease;">';
        echo '🎉 ¡Cuenta creada con éxito! Ya puedes completar los datos de envío para tu pedido.';
        echo '</div>';
    }
    if(isset($_GET['msg']) && $_GET['msg'] === 'login_success') {
        echo '<div style="background:#e7f5ff; color:#1971c2; padding:20px; border-radius:15px; text-align:center; border:1px solid #a5d8ff; font-weight:700; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">';
        echo '👋 ¡Hola de nuevo! Tus datos se han cargado. Ya puedes finalizar tu compra.';
        echo '</div>';
    }
    if(isset($_GET['error'])) {
        echo '<div style="background:#fff5f5; color:#e03131; padding:15px; border-radius:15px; text-align:center; border:1px solid #ffa8a8; font-weight:700; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">';
        echo '❌ Hubo un problema al procesar los datos. Por favor, revisa el formulario.';
        echo '</div>';
    }
?>
</div>
<script>
    setTimeout(() => {
        const alert = document.getElementById('checkout-alert');
        if(alert) {
            alert.style.transition = "opacity 0.5s ease, transform 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }
    }, 7000);
</script>
<?php endif; ?>

<?php
// --- LÓGICA DE NEGOCIO ---
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php?redirect=checkout';</script>"; 
    exit; 
}

$user_id = $_SESSION['user_id'];

// 1. Obtener usuario base
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$user_id]);
$user = $stmtU->fetch(PDO::FETCH_ASSOC);

// 1.2 Obtener libreta de direcciones del usuario (NUEVO)
$stmtD = $pdo->prepare("SELECT * FROM user_direcciones WHERE user_id = ? ORDER BY predeterminada DESC, id DESC");
$stmtD->execute([$user_id]);
$direcciones_guardadas = $stmtD->fetchAll(PDO::FETCH_ASSOC);

$tel_db = $user['telefono'] ?? '';
$solo_numero = (substr($tel_db, 0, 2) === '34') ? substr($tel_db, 2) : $tel_db;

if (empty($_SESSION['carrito'])) { 
    header("Location: productos.php"); 
    exit; 
}

// 2. RECÁLCULO DE TOTALES
$total_productos = 0;
$paypal_items = []; // NUEVO: Array para guardar los detalles para PayPal

foreach($_SESSION['carrito'] as $clave => $item) {
    if (isset($item['id']) && $item['id'] === 'CUSTOM_PROD') {
        $precio_p = (float)($item['precio'] ?? obtenerPrecioBase($item['tipo_base'] ?? 'camiseta'));
        $total_productos += ($precio_p * $item['cantidad']);
        
        // Añadimos el producto personalizado a PayPal
        $paypal_items[] = [
            'name' => mb_substr($item['nombre'] . ' (Talla: ' . $item['talla'] . ')', 0, 127),
            'unit_amount' => ['currency_code' => 'EUR', 'value' => number_format($precio_p, 2, '.', '')],
            'quantity' => (string)(int)$item['cantidad']
        ];
    } else {
        $stmtP = $pdo->prepare("SELECT categoria FROM productos WHERE id = ?");
        $stmtP->execute([$item['id']]);
        $prod_db = $stmtP->fetch();
        $categoria = strtolower($prod_db['categoria'] ?? '');
        $talla = strtolower($item['talla'] ?? 'M');
        $precio_db = (float)($item['precio'] ?? 0);
        if (function_exists('calcularPrecioPersonalizado') && !empty($categoria)) {
            $precio_db = calcularPrecioPersonalizado($pdo, $categoria, $talla);
        }
        $total_productos += ($precio_db * $item['cantidad']);

        // Añadimos el producto normal a PayPal
        $paypal_items[] = [
            'name' => mb_substr($item['nombre'] . ' (Talla: ' . $item['talla'] . ')', 0, 127),
            'unit_amount' => ['currency_code' => 'EUR', 'value' => number_format($precio_db, 2, '.', '')],
            'quantity' => (string)(int)$item['cantidad']
        ];
    }
}
$gastos_envio = ($total_productos >= ENVIO_GRATIS_UMBRAL) ? 0 : ENVIO_COSTE;
$total_final  = $total_productos + $gastos_envio;
?>

<script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=EUR&disable-funding=credit"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

<style>
.iti { width: 100% !important; display: block !important; }
    #checkout-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 40px; align-items: start; }
    
    .label-checkout { font-weight: 900; font-size: 11px; color: #95a5a6; display: block; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
    .input-checkout { width: 100%; padding: 18px; border: 2px solid #f3f3f3; border-radius: 15px; outline: none; transition: 0.3s; font-size: 15px; background: #fafafa; color: #2c3e50; font-weight: 600; }
    .input-checkout:focus { border-color: #e74c3c; background: #fff; box-shadow: 0 8px 20px rgba(231, 76, 60, 0.05); }
    .input-error { border-color: #e74c3c !important; background: #fffafa !important; animation: shake 0.4s ease-in-out; }

    /* NUEVO: Tarjetas de direcciones */
    .addr-card { background: #fff; border: 2px solid #eee; border-radius: 15px; padding: 20px; cursor: pointer; transition: 0.3s; position: relative; }
    .addr-card:hover { border-color: #e74c3c; box-shadow: 0 5px 15px rgba(231,76,60,0.1); }
    .addr-card.active { border-color: #e74c3c; background: #fff5f5; }
    .addr-card.active::after { content: '\f058'; font-family: 'Font Awesome 5 Free'; font-weight: 900; color: #e74c3c; position: absolute; top: 15px; right: 15px; font-size: 18px; }

    @keyframes shake { 
        0%, 100% { transform: translateX(0); } 
        25% { transform: translateX(-6px); } 
        75% { transform: translateX(6px); } 
    }
    .shake-red-alert { animation: shake 0.4s ease-in-out 2; border: 2px solid #e74c3c !important; background: #fff5f5 !important; box-shadow: 0 0 15px rgba(231, 76, 60, 0.2); }

    .paypal-wrapper { padding: 15px; border-radius: 25px; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 2px solid transparent; background: #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    .paypal-wrapper:hover { transform: translateY(-8px) scale(1.02); border-color: #27ae60; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }

    #btn-bizum-wa { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
    #btn-bizum-wa:hover { transform: translateY(-8px) scale(1.02); filter: brightness(1.1); box-shadow: 0 20px 40px rgba(39, 174, 96, 0.3) !important; }
    
    @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.5); } 70% { box-shadow: 0 0 0 20px rgba(46, 204, 113, 0); } 100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); } }
    .btn-highlight { animation: pulse-green 2s infinite; }
    .iti__country-list { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #eee; z-index: 1001; }

    @media (max-width: 900px) { #checkout-grid { grid-template-columns: 1fr !important; } }
</style>

<div class="container" style="max-width: 1200px; margin: 50px auto; padding: 0 20px; min-height: 80vh;">
    
    <div style="text-align: center; margin-bottom: 50px;">
        <h1 style="font-weight: 900; color: #2c3e50; text-transform: uppercase; letter-spacing: -1px; font-size: 2.5rem;">
            <i class="fas fa-shield-check" style="color: #27ae60;"></i> Checkout <span style="color: #e74c3c;">Seguro</span>
        </h1>
        <p style="color: #95a5a6; font-weight: 500;">Completa tus datos para recibir tus creaciones de Camiglobo.</p>
    </div>

    <div id="checkout-grid">
        
        <div style="background: white; padding: 45px; border-radius: 30px; border: 1px solid #eee; box-shadow: 0 10px 40px rgba(0,0,0,0.03);">
            <h3 style="color: #2c3e50; margin-bottom: 35px; font-weight: 800; display: flex; align-items: center; gap: 15px; border-bottom: 2px solid #f9f9f9; padding-bottom: 20px;">
                <i class="fas fa-map-location-dot" style="color: #e74c3c;"></i> Detalles de Entrega
            </h3>
            
            <?php if(!empty($direcciones_guardadas)): ?>
                <div style="margin-bottom: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label class="label-checkout" style="margin-bottom: 0;">Tus direcciones guardadas:</label>
                        <a href="perfil.php#tab-datos" target="_blank" style="font-size: 12px; color: #e74c3c; font-weight: 800; text-decoration: none;">
                            <i class="fas fa-cog"></i> Gestionar direcciones
                        </a>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        
                        <?php foreach($direcciones_guardadas as $idx => $dir): ?>
                            <?php $str_dir = htmlspecialchars($dir['nombre']." | ".$dir['direccion']." | ".$dir['ciudad']." (".$dir['cp'].") | Tlf: ".$dir['telefono']); ?>
                            
                            <div class="addr-card <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                 onclick="seleccionarDireccion(this, <?php echo json_encode($str_dir); ?>)">
                                <span style="font-weight: 900; color: #2c3e50; display: block; margin-bottom: 5px;">
                                    <i class="fas fa-bookmark" style="color: #e74c3c;"></i> <?php echo htmlspecialchars($dir['alias']); ?>
                                </span>
                                <span style="font-size: 13px; color: #666; line-height: 1.4;">
                                    <?php echo htmlspecialchars($dir['nombre']); ?><br>
                                    <?php echo htmlspecialchars($dir['direccion']); ?><br>
                                    <?php echo htmlspecialchars($dir['ciudad']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <div class="addr-card" onclick="mostrarFormularioNueva(this)" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fafafa; border: 2px dashed #ccc;">
                            <i class="fas fa-plus-circle" style="font-size: 24px; color: #aaa; margin-bottom: 10px;"></i>
                            <span style="font-weight: 800; color: #888;">Nueva Dirección</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div id="formulario_nueva_direccion" style="<?php echo !empty($direcciones_guardadas) ? 'display:none;' : 'display:block;'; ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div style="grid-column: span 2;">
                        <label class="label-checkout">Nombre y Apellidos:</label>
                        <input type="text" id="envio_nombre" value="<?php echo h($user['nombre'] ?? ''); ?>" class="input-checkout req-field" placeholder="Nombre completo">
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label class="label-checkout">Dirección Completa:</label>
                        <input type="text" id="envio_direccion" value="<?php echo h($user['direccion'] ?? ''); ?>" placeholder="Calle, número, piso..." class="input-checkout req-field">
                    </div>

                    <div>
                        <label class="label-checkout">Ciudad:</label>
                        <input type="text" id="envio_ciudad" value="<?php echo h($user['ciudad'] ?? ''); ?>" class="input-checkout req-field">
                    </div>
                    
                    <div>
                        <label class="label-checkout">Código Postal:</label>
                        <input type="text" id="envio_cp" value="<?php echo h($user['cp'] ?? ''); ?>" 
                               class="input-checkout req-field" maxlength="5" 
                               pattern="[0-9]{5}"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>

                    <div style="grid-column: span 2;">
                        <label class="label-checkout">Teléfono Móvil:</label>
                        <input type="tel" id="envio_telefono" value="<?php echo h($solo_numero); ?>" 
                               class="input-checkout req-field" style="width: 100% !important;">
                    </div>

                    <div style="grid-column: span 2; margin-top: 10px; background: #fdfdfd; padding: 20px; border-radius: 15px; border: 1px solid #eee;">
                        <label class="label-checkout" style="margin-bottom: 8px;">Dale un nombre para guardarla (Ej: Casa, Trabajo):</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="alias_nueva_dir" class="input-checkout" placeholder="Mi Dirección" style="padding: 12px; font-size: 13px; flex: 1;">
                            <button type="button" id="btn-guardar-dir" onclick="guardarDireccionEnPerfil()" 
                                style="white-space: nowrap; background: #27ae60; color: white; border: none; padding: 12px 18px; border-radius: 12px; font-weight: 900; cursor: pointer; font-size: 13px; transition: 0.2s;"
                                onmouseover="this.style.background='#219a55'" onmouseout="this.style.background='#27ae60'">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                        <div id="dir-guardada-msg" style="display:none; margin-top: 10px; font-size: 13px; font-weight: 800; color: #27ae60; padding: 8px 12px; background: #e8f5e8; border-radius: 8px;">
                            <i class="fas fa-check-circle"></i> ¡Dirección guardada en tu perfil!
                        </div>
                        <!-- Campo oculto para compatibilidad con appendAddressData al comprar -->
                        <input type="hidden" id="guardar_nueva_dir_hidden" value="0">
                    </div>
                </div>
            </div>

            <div id="terms-container" style="margin-top: 35px; padding: 25px; background: #fcfcfc; border-radius: 20px; border: 1px solid #f0f0f0; transition: 0.3s;">
                <label style="display: flex; align-items: flex-start; gap: 15px; cursor: pointer; font-size: 14px; color: #555;">
                    <input type="checkbox" id="acepto_terminos" style="margin-top: 5px; transform: scale(1.4);">
                    <span>He leído y acepto los <a href="terminos-condiciones.php" target="_blank" style="color: #e74c3c; font-weight: bold; text-decoration: none;">Términos y condiciones</a> de Camiglobo Barcelona.</span>
                </label>
            </div>

            <div id="error-box" style="display: none; background: #fff5f5; color: #e03131; padding: 18px; border-radius: 15px; margin-top: 30px; font-weight: 800; border-left: 6px solid #e74c3c;">
                <i class="fas fa-exclamation-triangle"></i> <span id="error-msg">Revisa los datos marcados.</span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 25px;">
            
            <div style="background: white; padding: 35px; border-radius: 30px; border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <h4 style="margin-bottom: 25px; font-weight: 900; text-transform: uppercase; font-size: 13px; letter-spacing: 1.5px; color: #7f8c8d;">Tu Pedido</h4>
                
                <div style="max-height: 250px; overflow-y: auto; margin-bottom: 25px; padding-right: 10px;">
                    <?php foreach(array_reverse($_SESSION['carrito'], true) as $item): ?>
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #f8f8f8;">
                            <?php $img = ($item['id'] === 'CUSTOM_PROD') ? $item['imagen_personalizada'] : ($item['imagen'] ?? 'images/placeholder.png'); ?>
                            <img src="<?php echo h($img); ?>" style="width: 50px; height: 50px; object-fit: contain; border-radius: 10px; background: #fff; border: 1px solid #eee;">
                                <div style="font-size: 13px; flex: 1;">
                                    <strong style="display:block; color: #2c3e50;"><?php echo h($item['nombre']); ?></strong>
                                    <span style="color: #95a5a6; font-weight: 600;">
                                        Talla <?php echo h($item['talla']); ?>
                                        <?php 
                                        $color_ck = $item['color_producto'] ?? $item['color'] ?? '';
                                        if (!empty($color_ck) && $color_ck !== 'Estándar'):
                                            $hex_ck = $colores_hex[$color_ck] ?? null;
                                        ?>
                                            &nbsp;|&nbsp; Color:
                                            <?php if ($hex_ck): ?>
                                                <span style="display:inline-flex; align-items:center; gap:3px; margin-left:3px;">
                                                    <span style="width:12px; height:12px; border-radius:50%; background:<?php echo $hex_ck; ?>; border:1px solid rgba(0,0,0,0.15); display:inline-block; flex-shrink:0;"></span>
                                                    <span><?php echo h($color_ck); ?></span>
                                                </span>
                                            <?php else: ?>
                                                <?php echo h($color_ck); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        &nbsp;· x<?php echo (int)$item['cantidad']; ?>
                                    </span>
                                    <?php if (!empty($item['extras_descripcion'])): ?>
                                    <div style="margin-top:4px; font-size:12px; color:#e74c3c;">
                                        Extras: <?php echo is_array($item['extras_descripcion']) ? implode(', ', $item['extras_descripcion']) : $item['extras_descripcion']; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <div style="font-weight: 800; color: #2c3e50;"><?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?>€</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 2px solid #f9f9f9; padding-top: 20px;">
                    <div style="display:flex; justify-content: space-between; font-weight: 900; font-size: 32px; color: #000;">
                        <span>TOTAL:</span><span><?php echo number_format($total_final, 2, ',', '.'); ?> €</span>
                    </div>
                </div>
            </div>

            <div style="background: #f4f6f7; padding: 45px; border-radius: 35px; box-shadow: 0 20px 60px rgba(0,0,0,0.06); text-align: center; border: 1px solid #e2e8f0;">
                
                <p style="color: #64748b; font-size: 12px; margin-bottom: 30px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px;">
                    MÉTODO DE PAGO SEGURO
                </p>
                
                <div class="paypal-wrapper">
                    <div id="paypal-button-container"></div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 20px; margin: 30px 0;">
                    <hr style="flex: 1; border: none; border-top: 2px solid #e2e8f0;">
                    <span style="color: #94a3b8; font-size: 11px; font-weight: 900;">O BIEN</span>
                    <hr style="flex: 1; border: none; border-top: 2px solid #e2e8f0;">
                </div>

                <button type="button" id="btn-bizum-wa" onclick="enviarBizum()" class="btn-highlight" style="width:100%; background:linear-gradient(90deg, #27ae60, #2ecc71); color:white; padding:22px; border:none; border-radius:60px; font-weight:900; cursor:pointer; text-transform: uppercase; font-size: 15px; box-shadow: 0 10px 20px rgba(39, 174, 96, 0.2);">
                    <i class="fab fa-whatsapp" style="margin-right: 12px; font-size: 20px;"></i> Bizum / Transferencia
                </button>

                <div style="margin-top: 35px; display: flex; justify-content: center; gap: 12px; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 25px;">
                    <i class="fas fa-lock" style="color: #27ae60; font-size: 16px;"></i>
                    <span style="color: #334155; font-size: 13px; font-weight: 800; letter-spacing: 0.5px;">
                        Seguridad SSL 256-bit Certificada
                    </span>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE LIBRETA DE DIRECCIONES ---
    let usandoDireccionGuardada = <?php echo !empty($direcciones_guardadas) ? 'true' : 'false'; ?>;
let direccionSeleccionadaStr = <?php echo !empty($direcciones_guardadas) ? json_encode($direcciones_guardadas[0]['nombre'].' | '.$direcciones_guardadas[0]['direccion'].' | '.$direcciones_guardadas[0]['ciudad'].' ('.$direcciones_guardadas[0]['cp'].') | Tlf: '.$direcciones_guardadas[0]['telefono']) : '""'; ?>;
    function seleccionarDireccion(element, dirStr) {
        document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('active'));
        element.classList.add('active');
        document.getElementById('formulario_nueva_direccion').style.display = 'none';
        direccionSeleccionadaStr = dirStr;
        usandoDireccionGuardada = true;
    }

    function mostrarFormularioNueva(element) {
        document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('active'));
        element.classList.add('active');
        document.getElementById('formulario_nueva_direccion').style.display = 'block';
        usandoDireccionGuardada = false;
    }

    async function guardarDireccionEnPerfil() {
        const nombre = document.getElementById('envio_nombre').value.trim();
        const calle  = document.getElementById('envio_direccion').value.trim();
        const ciudad = document.getElementById('envio_ciudad').value.trim();
        const cp     = document.getElementById('envio_cp').value.trim();
        const tel    = phoneInput.getNumber();
        const alias  = document.getElementById('alias_nueva_dir').value.trim() || 'Mi Direccion';

        if (!nombre || !calle || !ciudad || !cp) {
            alert('Completa primero todos los campos de direccion (nombre, calle, ciudad y CP).');
            return;
        }

        const btn = document.getElementById('btn-guardar-dir');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Guardando...';
        btn.disabled = true;

        try {
            const countryData = phoneInput.getSelectedCountryData();
            const prefijo = countryData.dialCode || '34';
            const soloNumero = tel.replace('+' + prefijo, '').replace(/\s/g, '');

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('accion', 'nueva_direccion');
            formData.append('alias', alias);
            formData.append('nombre_dir', nombre);
            formData.append('direccion_dir', calle);
            formData.append('ciudad_dir', ciudad);
            formData.append('cp_dir', cp);
            formData.append('tel_dir', soloNumero);
            formData.append('tel_prefijo', prefijo);

            await fetch('procesar_perfil.php', { method: 'POST', body: formData });

            document.getElementById('dir-guardada-msg').style.display = 'block';
            document.getElementById('guardar_nueva_dir_hidden').value = '0';
            btn.innerHTML = '<i class="fas fa-check"></i> Guardada';
            btn.style.background = '#27ae60';
            btn.disabled = true;
        } catch(e) {
            btn.innerHTML = '<i class="fas fa-save"></i> Guardar';
            btn.disabled = false;
            alert('Error al guardar. Intentalo de nuevo.');
        }
    }

    // --- INICIALIZACIÓN TELÉFONO ---
    const phoneInputField = document.querySelector("#envio_telefono");
    const phoneInput = window.intlTelInput(phoneInputField, {
        preferredCountries: ["es", "pt", "fr", "it"],
        separateDialCode: true,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
    });

    const telDB = "<?php echo preg_replace('/[^0-9]/', '', $user['telefono'] ?? ''); ?>";
    if(telDB) {
        if(telDB.length > 9) phoneInput.setNumber("+" + telDB);
        else if(telDB.length === 9) phoneInput.setNumber("+34" + telDB);
    }

    // 1. CONSTRUCTOR DE DIRECCIÓN
    function buildAddress() {
        if (usandoDireccionGuardada) return direccionSeleccionadaStr;

        const n = document.getElementById('envio_nombre').value.trim();
        const d = document.getElementById('envio_direccion').value.trim();
        const c = document.getElementById('envio_ciudad').value.trim();
        const cp = document.getElementById('envio_cp').value.trim();
        const t = phoneInput.getNumber(); 
        
        return `${n} | ${d} | ${c} (${cp}) | Tlf: ${t}`;
    }

    // 1.5 EMPAQUETAR DATOS EXTRAS (Para guardar la dirección en la DB al comprar)
    function appendAddressData(payloadObj) {
        if (!usandoDireccionGuardada && document.getElementById('guardar_nueva_dir_hidden').value === '1') {
            payloadObj.guardar_dir = '1';
            payloadObj.dir_alias = document.getElementById('alias_nueva_dir').value || 'Mi Dirección';
            payloadObj.dir_nombre = document.getElementById('envio_nombre').value.trim();
            payloadObj.dir_calle = document.getElementById('envio_direccion').value.trim();
            payloadObj.dir_ciudad = document.getElementById('envio_ciudad').value.trim();
            payloadObj.dir_cp = document.getElementById('envio_cp').value.trim();
            payloadObj.dir_tel = phoneInput.getNumber();
        }
        return payloadObj;
    }

    // 2. VALIDADOR
    function isFormValid() {
        const checkbox = document.getElementById('acepto_terminos');
        const termsCont = document.getElementById('terms-container');
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');
        let valid = true;

        termsCont.classList.remove('shake-red-alert');

        if (!usandoDireccionGuardada) {
            const fields = document.querySelectorAll('.req-field');
            fields.forEach(f => {
                f.classList.remove('input-error');
                if(f.value.trim() === '') { f.classList.add('input-error'); valid = false; }
            });

            if (phoneInputField.value.trim() !== '' && !phoneInput.isValidNumber()) {
                phoneInputField.classList.add('input-error');
                errorMsg.innerText = "El número de teléfono no es válido para el país.";
                valid = false;
            } else if (!valid) {
                errorMsg.innerText = "Por favor, completa todos los campos marcados en rojo.";
            }
        }

        if (valid && !checkbox.checked) {
            errorMsg.innerText = "Debes aceptar los términos y condiciones de compra.";
            termsCont.classList.add('shake-red-alert');
            valid = false;
        }

        errorBox.style.display = valid ? 'none' : 'block';
        if(!valid) errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return valid;
    }

    document.getElementById('envio_cp').addEventListener('blur', function() {
        if (this.value.length > 0 && this.value.length < 5) {
            this.style.borderColor = '#e74c3c';
            alert("⚠️ El Código Postal debe tener exactamente 5 números.");
        } else {
            this.style.borderColor = '#f3f3f3';
        }
    });

    // 4. LÓGICA DE REGISTRO BIZUM
    function enviarBizum() {
        if(!isFormValid()) return;
        
        const btn = document.getElementById('btn-bizum-wa');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> REGISTRANDO...';
        btn.style.pointerEvents = 'none';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'procesar_pedido.php';
        
        let fields = { direccion: buildAddress(), total: '<?php echo $total_final; ?>', csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' };
        fields = appendAddressData(fields);

        for (const key in fields) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = key; input.value = fields[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }

    // 5. BOTONES PAYPAL
    paypal.Buttons({
        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' },
        onClick: (data, actions) => {
            if (!isFormValid()) return actions.reject();
            return actions.resolve();
        },
        createOrder: (data, actions) => {
            return actions.order.create({
                purchase_units: [{
                    amount: { 
                        currency_code: 'EUR',
                        value: '<?php echo number_format($total_final, 2, ".", ""); ?>',
                        breakdown: {
                            item_total: {
                                currency_code: 'EUR',
                                value: '<?php echo number_format($total_productos, 2, ".", ""); ?>'
                            },
                            shipping: {
                                currency_code: 'EUR',
                                value: '<?php echo number_format($gastos_envio, 2, ".", ""); ?>'
                            }
                        }
                    },
                    description: "Camiglobo Barcelona - Pedido",
                    items: <?php echo json_encode($paypal_items); ?>
                }]
            });
        },
        onApprove: (data, actions) => {
            return actions.order.capture().then(details => {
                document.getElementById('checkout-grid').innerHTML = `
                    <div style="grid-column: 1/-1; text-align:center; padding:120px; background:white; border-radius:30px;">
                        <i class="fas fa-spinner fa-spin" style="font-size:60px; color:#27ae60;"></i>
                        <h2 style="margin-top:30px; font-weight:900;">¡Pago confirmado!</h2>
                        <p style="color:#7f8c8d;">Estamos registrando tu pedido en nuestro taller, un segundo...</p>
                    </div>`;

                let payload = {
                    orderID: details.id,
                    total: '<?php echo $total_final; ?>',
                    direccion: buildAddress(),
                    metodo: 'PayPal'
                };
                payload = appendAddressData(payload);

                fetch('procesar_pago.php', {
                    method: 'POST',
                    headers: { 'content-type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    if(res.success) window.location.href = 'gracias.php?id=' + details.id;
                    else alert("Error interno: " + res.error);
                })
                .catch(err => alert("Error de red. Tu pedido se ha pagado pero hubo un fallo al registrarlo."));
            });
        },
        onError: (err) => {
            console.error('PayPal Error:', err);
            // Registrar en audit_log si está disponible
            fetch('procesar_pago.php', {
                method: 'POST',
                headers: { 'content-type': 'application/json' },
                body: JSON.stringify({ error: true, details: err, orderID: err?.orderID })
            }).catch(() => {});
            alert('Hubo un error con PayPal. Por favor, inténtalo de nuevo o contacta con soporte si el problema persiste.');
        }
    }).render('#paypal-button-container');
</script>

<?php include 'includes/footer.php'; ?>
