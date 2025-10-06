<?php
declare(strict_types=1);
header('Content-Type: application/json');
require __DIR__ . '/../../../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);
$nombre = trim($input['nombre'] ?? '');
$email = trim($input['email'] ?? '');
$asunto = trim($input['asunto'] ?? '');
$mensaje = trim($input['mensaje'] ?? '');

if (!$nombre || !$email || !$asunto || !$mensaje) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'dea28219ce6bc9'; 
    $mail->Password   = 'b34c3eda4d1847';
    $mail->Port       = 2525;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($email, $nombre);
    $mail->addAddress('soporte@subete.com', 'Soporte Súbete'); // aquí recibes el mensaje

    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body = "
        <h2>Nuevo mensaje de contacto</h2>
        <p><strong>Nombre:</strong> $nombre</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Asunto:</strong> $asunto</p>
        <p><strong>Mensaje:</strong><br>$mensaje</p>
    ";

    $mail->send();
    echo json_encode(['ok' => true, 'msg' => 'Mensaje enviado correctamente']);

} catch (Exception $e) {
    echo json_encode(['error' => 'No se pudo enviar el mensaje: ' . $mail->ErrorInfo]);
}
