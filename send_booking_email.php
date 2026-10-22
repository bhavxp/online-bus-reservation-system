
<?php

// ========================================================
// OBRS BOOKING EMAIL SENDER
// PHPMailer + Gmail SMTP
// ========================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';


// ========================================================
// SEND BOOKING CONFIRMATION EMAIL
// ========================================================

function send_booking_confirmation_email(
    string $customer_email,
    string $customer_name,
    string $reference_no,
    array $seats,
    float $total_amount,
    string $ticket_url
): bool {

    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        error_log(
            'OBRS Email Error: Invalid customer email.'
        );

        return false;
    }

    $config = require __DIR__ .
        '/config/email_config.php';

    $mail = new PHPMailer(true);

    try {

        // =================================================
        // SMTP CONFIGURATION
        // =================================================

        $mail->isSMTP();

        $mail->Host =
            $config['smtp_host'];

        $mail->SMTPAuth = true;

        $mail->Username =
            $config['smtp_username'];

        $mail->Password =
            $config['smtp_password'];

        $mail->SMTPSecure =
            $config['smtp_secure'];

        $mail->Port =
            $config['smtp_port'];

        $mail->CharSet =
            $config['charset'];


        // =================================================
        // SENDER
        // =================================================

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );


        // =================================================
        // CUSTOMER
        // =================================================

        $mail->addAddress(
            $customer_email,
            $customer_name
        );


        // =================================================
        // EMAIL CONTENT
        // =================================================

        $seat_text =
            htmlspecialchars(
                implode(', ', $seats),
                ENT_QUOTES,
                'UTF-8'
            );

        $safe_name =
            htmlspecialchars(
                $customer_name,
                ENT_QUOTES,
                'UTF-8'
            );

        $safe_reference =
            htmlspecialchars(
                $reference_no,
                ENT_QUOTES,
                'UTF-8'
            );

        $safe_ticket_url =
            htmlspecialchars(
                $ticket_url,
                ENT_QUOTES,
                'UTF-8'
            );

        $formatted_amount =
            number_format(
                $total_amount,
                2
            );


        $mail->isHTML(true);

        $mail->Subject =
            'Booking Confirmation - ' .
            $reference_no;


        $mail->Body = '

        <div style="
            font-family:Arial,Helvetica,sans-serif;
            background:#f4f6f8;
            padding:30px 15px;
        ">

            <div style="
                max-width:600px;
                margin:auto;
                background:#ffffff;
                border-radius:14px;
                overflow:hidden;
                box-shadow:0 4px 20px rgba(0,0,0,0.08);
            ">

                <div style="
                    background:#111827;
                    color:#ffffff;
                    padding:25px;
                    text-align:center;
                ">

                    <h1 style="
                        margin:0;
                        font-size:24px;
                    ">
                        Booking Confirmed
                    </h1>

                    <p style="
                        margin:8px 0 0;
                        color:#d1d5db;
                    ">
                        Online Bus Reservation System
                    </p>

                </div>


                <div style="
                    padding:25px;
                    color:#111827;
                ">

                    <p>
                        Hello <strong>' .
                        $safe_name .
                        '</strong>,
                    </p>

                    <p>
                        Your bus booking has been
                        successfully created.
                    </p>


                    <div style="
                        background:#f3f4f6;
                        border-radius:10px;
                        padding:18px;
                        margin:20px 0;
                    ">

                        <p style="margin:7px 0;">
                            <strong>Booking Reference:</strong><br>
                            ' . $safe_reference . '
                        </p>

                        <p style="margin:7px 0;">
                            <strong>Seats:</strong><br>
                            ' . $seat_text . '
                        </p>

                        <p style="margin:7px 0;">
                            <strong>Total Amount:</strong><br>
                            ₹' . $formatted_amount . '
                        </p>

                        <p style="margin:7px 0;">
                            <strong>Payment Status:</strong><br>
                            Pending
                        </p>

                    </div>


                    <div style="
                        text-align:center;
                        margin:25px 0;
                    ">

                        <a
                            href="' . $safe_ticket_url . '"
                            style="
                                display:inline-block;
                                background:#2563eb;
                                color:#ffffff;
                                text-decoration:none;
                                padding:12px 22px;
                                border-radius:8px;
                                font-weight:bold;
                            "
                        >
                            View Your Ticket
                        </a>

                    </div>


                    <p style="
                        color:#6b7280;
                        font-size:13px;
                        line-height:1.6;
                    ">
                        Please keep your booking reference
                        safe. You may need it to access
                        your ticket.
                    </p>

                </div>


                <div style="
                    background:#f9fafb;
                    padding:18px;
                    text-align:center;
                    color:#6b7280;
                    font-size:12px;
                ">

                    This is an automated booking
                    confirmation email.

                </div>

            </div>

        </div>
        ';


        // =================================================
        // PLAIN TEXT FALLBACK
        // =================================================

        $mail->AltBody =
            "Booking Confirmed!\n\n" .
            "Passenger: " . $customer_name . "\n" .
            "Reference: " . $reference_no . "\n" .
            "Seats: " . implode(', ', $seats) . "\n" .
            "Amount: Rs. " .
            number_format($total_amount, 2) . "\n" .
            "Payment Status: Pending\n\n" .
            "Ticket: " . $ticket_url;


        // =================================================
        // SEND EMAIL
        // =================================================

        $mail->send();

        return true;


    } catch (Exception $e) {

        error_log(
            'OBRS Email Error: ' .
            $mail->ErrorInfo
        );

        return false;
    }
}

?>