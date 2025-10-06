<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php'; // Ajustá según tu ruta

function enviarMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.tu-servidor.com'; // Ej: smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu-email@gmail.com';
        $mail->Password   = 'tu-password-app'; // ⚠️ Gmail: contraseña de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('tu-email@gmail.com', 'Súbete');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar mail: " . $mail->ErrorInfo);
        return false;
    }
}
