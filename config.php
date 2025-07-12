<?php
date_default_timezone_set('Asia/Colombo');

$host = 'localhost';
$port = '3307'; // <-- specify your port here
$db   = 'pos_system';
$user = 'root';
$pass = 'infive1234';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to generate invoice number
function generateInvoiceNumber()
{
    return 'INV-' . time();
}


// Mailer configurations
$mail_host = 'smtp.gmail.com'; // Set your SMTP server
$mail_username = 'nethminasachindu7@gmail.com'; // SMTP username
$mail_password = 'lpdiXqbpIoujPvhh'; // SMTP password
$mail_admin_email = 'sachindunethmina6@gmail.com'; // Admin email address
$mail_from_name = 'Infive POS System'; // From name