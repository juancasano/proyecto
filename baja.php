<?php
require_once 'includes/config.php';

// Recogemos el token opaco de la URL (ej: baja.php?token=abc123...)
$token = trim($_GET['token'] ?? '');

// Validar formato del token (64 caracteres hex)
if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    header("Location: index.php");
    exit;
}

// Buscar el email asociado al token
$stmt = $pdo->prepare("SELECT email FROM newsletter WHERE token_baja = ?");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: index.php");
    exit;
}
$email = $row['email'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darse de baja - Camiglobo Barcelona</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #111111; /* Estilo Camiglobo Premium */
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .card {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-top: 4px solid #e74c3c; /* Toque corporativo */
        }
        .card h1 {
            margin-top: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .email-text {
            color: #e74c3c;
            font-weight: bold;
            word-break: break-all;
            font-size: 1.1rem;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 15px;
            border-radius: 50px;
            font-weight: 900;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: 0.3s;
            font-size: 12px;
            letter-spacing: 1px;
            box-sizing: border-box;
        }
        .btn-danger {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        .btn-danger:hover {
            filter: brightness(1.1);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        .btn-secondary {
            background: #333;
            color: #aaa;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #444;
            color: white;
        }
        .icon-sad {
            font-size: 50px;
            background: linear-gradient(90deg, #fff 0%, #e74c3c 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="card">
        <i class="fas fa-heart-crack icon-sad"></i>
        <h1>¿Seguro que nos dejas?</h1>
        
        <p style="color: #aaa; line-height: 1.6; margin-bottom: 10px;">
            Estás a punto de dar de baja el correo:<br>
            <span class="email-text"><?php echo htmlspecialchars($email); ?></span>
        </p>
        
        <p style="color: #888; font-size: 13px; margin-bottom: 30px;">
            Si lo haces, te perderás nuestros códigos de descuento sorpresa y las ediciones limitadas.
        </p>

        <form action="procesar_newsletter.php" method="POST">
            <input type="hidden" name="accion" value="baja">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <button type="submit" class="btn btn-danger">Sí, darme de baja</button>
        </form>
        
        <a href="index.php" class="btn btn-secondary">Pensándolo bien... me quedo</a>
    </div>

</body>
</html>