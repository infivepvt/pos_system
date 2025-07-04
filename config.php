<?php
$host = 'localhost';
$db = 'u263749830_simplepos';
$user = 'u263749830_pos';
$pass = '+hE27|SGvA0';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to generate invoice number
function generateInvoiceNumber() {
    return 'INV-' . time();
}
?>