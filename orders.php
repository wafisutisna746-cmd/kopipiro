<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['customer', 'cashier'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id']; $is_cashier = $_SESSION['role'] === 'cashier';

// Kasir hanya boleh mengubah status pesanan yang ditugaskan ke dirinya
if ($is_cashier && isset($_POST['order_id']) && in_array($_POST['status'], ['pending', 'paid', 'done'])) {
    $pdo->prepare("UPDATE orders SET status=? WHERE id=? AND cashier_id=?")->execute([$_POST['status'], $_POST['order_id'], $uid]);
    header('Location: orders.php'); exit;
}
$col = $is_cashier ? 'cashier_id' : 'user_id';
$q = $pdo->prepare("SELECT o.*, c.username AS cashier FROM orders o LEFT JOIN users c ON c.id = o.cashier_id WHERE o.$col = ? ORDER BY o.id DESC");
$q->execute([$uid]);
$orders = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pesanan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container">
    <h3 class="mb-3"><?= $is_cashier ? 'Pesanan yang Ditugaskan ke Kamu' : 'Pesanan Saya' ?></h3>
    <div class="card shadow-sm border-0"><div class="card-body"><?php render_orders($pdo, $orders, $is_cashier, false); ?></div></div>
</div>
</body></html>