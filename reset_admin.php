<?php
require 'db.php';

// Hash password 'admin123' secara langsung via PHP
$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Hapus jika sudah ada
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);

    // Insert ulang akun admin
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$username, $hashed_password]);

    echo "<h2 style='color:green;'>Berhasil! Akun admin telah di-reset.</h2>";
    echo "<p>Username: <b>admin</b><br>Password: <b>admin123</b></p>";
    echo "<a href='login.php'>Klik di sini untuk Login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>