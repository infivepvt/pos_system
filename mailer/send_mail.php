<?php

$mail_host = 'smtp.gmail.com'; // Set your SMTP server
$mail_username = 'nethminasachindu7@gmail.com'; // SMTP username
$mail_password = 'lpdiXqbpIoujPvhh'; // SMTP password
$mail_admin_email = 'sachindunethmina6@gmail.com'; // Admin email address
$mail_from_name = 'Infive POS System'; // From name

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

class AppMailer
{
    public function sendMail($subject, $html)
    {
        global $mail_host, $mail_username, $mail_password, $mail_from_name, $mail_admin_email;

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output (set to SMTP::DEBUG_SERVER only for testing)
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $mail_host;                             // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = $mail_username;                         // SMTP username
            $mail->Password   = $mail_password;                         // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
            $mail->Port       = 465;                                    // TCP port to connect to

            // Recipients
            $mail->setFrom($mail_username, $mail_from_name);
            $mail->addAddress($mail_admin_email, "Admin");              // Add a recipient
            $mail->addReplyTo($mail_username, $mail_from_name);

            // Content
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $html;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Optionally log the error to a file instead of outputting it
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
