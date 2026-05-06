<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: perfil.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pedido_id = (int)($_POST['id'] ?? 0);

// Verificar CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: perfil.php?error=no_permission");
    exit;
}

if (!$pedido_id) {
    header("Location: perfil.php?error=no_permission");
    exit;
}

try {
    // Verificar pedido
    $check = $pdo->prepare("SELECT p.*, u.email as user_email, u.nombre as user_nombre
                            FROM pedidos p
                            LEFT JOIN usuarios u ON p.user_id = u.id
                            WHERE p.id = ? AND p.user_id = ?");
    $check->execute([$pedido_id, $user_id]);
    $pedido = $check->fetch();

    if (!$pedido) {
        header("Location: perfil.php?error=no_permission");
        exit;
    }

    // Verificar estado
    if (strpos(strtolower($pedido['estado']), 'pendiente') === false) {
        header("Location: perfil.php?error=no_cancelable");
        exit;
    }

    // Cancelar
    $upd = $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ?");
    $upd->execute([$pedido_id]);

    if ($upd->rowCount() > 0) {
        // Enviar email al cliente
        $email = $pedido['user_email'];
        $nombre = $pedido['user_nombre'] ?? 'Cliente';
        $ref = $pedido['id_pago'];

        $asunto = "Cancelación de Pedido #$ref - Camiglobo";
        $cuerpo = "
            <div style='text-align: center;'>
                <div style='font-size: 50px; margin-bottom: 15px;'>😔</div>
                <h2 style='color: #111; margin: 0 0 15px 0; font-size: 24px;'>Hola $nombre</h2>
                <p style='color: #555; font-size: 15px; line-height: 1.7;'>Tu pedido <b style='color:#e74c3c;'>#$ref</b> ha sido cancelado correctamente.</p>
                <p style='color: #777; font-size: 14px; line-height: 1.7;'>Si no solicitaste esta cancelación, contáctanos inmediatamente.</p>
                <div style='margin: 30px 0 10px;'>
                    <a href='https://www.camiglobo.com' style='background: linear-gradient(90deg, #111 0%, #333 100%); color: white; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 14px; display: inline-block; letter-spacing: 0.5px;'>Volver a la tienda</a>
                </div>
                <p style='color: #bbb; font-size: 12px; margin-top: 25px;'>¿Fue un error? Haz un nuevo pedido cuando quieras.</p>
            </div>";

        try { enviarEmail($email, $asunto, $cuerpo, '#e74c3c'); } catch (Exception $e) {}

        // Email al admin
        try {
            $admin_cuerpo = "<p>El cliente $nombre ($email) ha cancelado el pedido #$ref</p>";
            enviarEmail(ADMIN_EMAIL, "PEDIDO CANCELADO (#$ref)", $admin_cuerpo);
        } catch (Exception $e) {}
    }

    header("Location: perfil.php?msg=pedido_cancelado");

} catch (Exception $e) {
    error_log("Error cancelar: " . $e->getMessage());
    header("Location: perfil.php?error=db");
}
?>