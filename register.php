<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Default role untuk register public adalah 'customer'
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
        $stmt->execute([$username, $hashed_password]);
        
        $success = "Registrasi berhasil! Silakan login untuk mendapatkan diskon 10%.";
    } catch (PDOException $e) {
        // Jika username sudah ada (assuming username is unique in a real app, though not strictly enforced in our basic schema)
        $error = "Registrasi gagal. Coba username lain.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - Coffee Shop</title>
</head>
<body>
    <h2>Daftar Akun Baru</h2>
    
    <?php if (isset($success)) echo "<p style='color:green;'>$success <a href='login.php'>Login di sini</a></p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    
    <form method="POST">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Daftar</button>
    </form>
    <br>
    <a href="index.php">Kembali ke Menu Utama</a>
</body>
</html>