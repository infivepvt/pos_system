<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php?page=dashboard");
            exit;
        } else {
            // Invalid credentials
            header("Location: login.php?error=Invalid username or password");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: login.php?error=Database error");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>