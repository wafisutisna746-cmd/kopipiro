<?php
session_start();
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'customer') { header('Location: login.php'); exit; }
$pid = (int)($_POST['product_id'] ?? 0); $r = (int)($_POST['rating'] ?? 0);
if ($pid > 0 && $r >= 1 && $r <= 5) {   // satu customer = satu rating per produk (bisa diubah)
    $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)")
        ->execute([$pid, $_SESSION['user_id'], $r]);
}
header('Location: index.php'); exit;