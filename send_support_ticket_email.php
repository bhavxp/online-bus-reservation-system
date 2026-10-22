
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function send_support_ticket_email(
    string $customerEmail,
    string $customerName,
    int $ticketId,
    string $subject,
    string $status,
    string $reply
): bool {

    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('OBRS support email: Invalid customer email.');
        return false;
    }

    // Reuse the existing SMTP configuration.
    $config = require __DIR__ . '/config/email_config.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port = (int) $config['smtp_port'];
        $mail->CharSet = $config['charset'];

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);

        $safeName = htmlspecialchars(
            $customerName, ENT_QUOTES, 'UTF-8'
        );
        $safeSubject = htmlspecialchars(
            $subject, ENT_QUOTES, 'UTF-8'
        );
        $safeStatus = htmlspecialchars(
            $status, ENT_QUOTES, 'UTF-8'
        );
        $safeReply = nl2br(htmlspecialchars(
            $reply !== '' ? $reply : 'No additional reply was provided.',
            ENT_QUOTES,
            'UTF-8'
        ));

        $mail->Subject = 'OBRS Support Ticket #' . $ticketId . ' Update';

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto'>
                <h2 style='color:#2563eb'>OBRS Support Update</h2>
                <p>Hello <strong>{$safeName}</strong>,</p>
                <p>Your support ticket has been updated.</p>

                <div style='background:#f3f4f6;padding:18px;border-radius:10px'>
                    <p><strong>Ticket:</strong> #{$ticketId}</p>
                    <p><strong>Subject:</strong> {$safeSubject}</p>
                    <p><strong>Status:</strong> {$safeStatus}</p>
                    <p><strong>Admin Reply:</strong><br>{$safeReply}</p>
                </div>

                <p>Please log in to OBRS to check your complaint details.</p>
                <p>Regards,<br><strong>OBRS Support Team</strong></p>
            </div>
        ";

        $mail->AltBody =
            "OBRS Support Update\n" .
            "Ticket: #{$ticketId}\n" .
            "Subject: {$subject}\n" .
            "Status: {$status}\n" .
            "Admin Reply: {$reply}";

        $mail->send();
        return true;

    } catch (\Throwable $e) {
        error_log(
            'OBRS support email failed for ticket #' .
            $ticketId . ': ' . $e->getMessage()
        );
        return false;
    }
}
