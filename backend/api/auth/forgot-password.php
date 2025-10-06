<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../../vendor/autoload.php'; // Asegurate de que esta ruta sea correcta

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el email']);
    exit;
}

try {
    $pdo = db();

    // Buscar usuario
    $stmt = $pdo->prepare('SELECT ID_Usuario FROM usuarios WHERE Email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // No decimos si existe o no, por seguridad
    if (!$user) {
        echo json_encode(['ok' => true, 'msg' => 'Se envió el enlace si el correo existe']);
        exit;
    }

    // Generar token único
    $token = bin2hex(random_bytes(32));
    $expira_en = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    // Guardar token
    $stmt = $pdo->prepare('INSERT INTO reset_password_tokens (id_usuario, token, expira_en, usado) VALUES (?, ?, ?, 0)');
    $stmt->execute([$user['ID_Usuario'], $token, $expira_en]);

    // Crear enlace
    $url = "http://localhost/subete/frontend/reset-password.php?token=$token";

    // ========== ENVIAR CORREO ==========
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dea28219ce6bc9'; // tu username de Mailtrap
        $mail->Password   = 'b34c3eda4d1847'; // tu password de Mailtrap
        $mail->Port       = 2525;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';


        // Remitente y destinatario
        $mail->setFrom('no-reply@subete.com', 'Súbete');
        $mail->addAddress($email);

        // Contenido del mail
        $mail->isHTML(true);
        $mail->Subject = 'Restablece tu contraseña - Súbete';
        $mail->Body = "
            <h2>Solicitud de restablecimiento de contraseña</h2>
            <p>Haz clic en el siguiente enlace para cambiar tu contraseña:</p>
            <p><a href='$url' target='_blank'>$url</a></p>
            <p>Este enlace expirará en 1 hora.</p>
        ";

        $mail->send();

        echo json_encode(['ok' => true, 'msg' => 'Correo de restablecimiento enviado']);

    } catch (Exception $e) {
        echo json_encode(['error' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de servidor: ' . $e->getMessage()]);
}
