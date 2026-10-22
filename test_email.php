<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/email_config.php';

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['smtp_port'];
    $mail->CharSet = $config['charset'];

    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );

    // CHANGE THIS TO YOUR GMAIL
    $mail->addAddress(
        'bhav.xp@gmail.com'
    );

    $mail->isHTML(true);

    $mail->Subject = 'OBRS SMTP Test';

    $mail->Body = '
        <h2>OBRS Email Test</h2>
        <p>If you received this email, Gmail SMTP is working correctly.</p>
    ';

    $mail->AltBody =
        'OBRS Email Test - Gmail SMTP is working correctly.';

    $mail->send();

    echo '<h2 style="color:green;">EMAIL SENT SUCCESSFULLY ✅</h2>';

} catch (Exception $e) {

    echo '<h2 style="color:red;">EMAIL FAILED ❌</h2>';

    echo '<p><strong>Error:</strong></p>';

    echo '<pre>';
    echo htmlspecialchars(
        $mail->ErrorInfo,
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</pre>';
}