<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm-password']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: signup.php?error=All fields are required");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: signup.php?error=Invalid email format");
        exit;
    }

    if ($password !== $confirm_password) {
        header("Location: signup.php?error=Passwords do not match");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: signup.php?error=Password must be at least 6 characters");
        exit;
    }

    try {
        // Check for duplicate username or email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: signup.php?error=Username or email already exists");
            exit;
        }

        // Hash password and insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);

        // Redirect to login page after successful registration
        header("Location: login.php?success=Registration successful! Please log in.");
        exit;
    } catch (PDOException $e) {
        header("Location: signup.php?error=Database error");
        exit;
    }
} else {
    header("Location: signup.php");
    exit;
}
?>