<?php
$host = 'localhost';
$dbname = 'coffeeshop';
$user = 'root';
$pass = ''; // Adjust based on your local setup (e.g., XAMPP/MAMP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>