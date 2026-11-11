<?php
session_start();
require 'db.php';

$cart = $_SESSION['cart'] ?? [];
$a = $_POST['a'] ?? ''; $pid = (int)($_POST['product_id'] ?? 0); $error = '';
if ($a === 'plus') $cart[$pid] = ($cart[$pid] ?? 0) + 1;
if ($a === 'minus') { $cart[$pid] = ($cart[$pid] ?? 0) - 1; if ($cart[$pid] <= 0) unset($cart[$pid]); }
$_SESSION['cart'] = $cart;

$rows = [];
if ($cart) {
    $in = implode(',', array_fill(0, count($cart), '?'));
    $q = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)"); $q->execute(array_keys($cart));
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
}
$rate = (($_SESSION['role'] ?? '') === 'customer') ? 0.10 : 0;   // aturan diskon hanya di server
$unit = fn($p) => (int) round($p['price'] * (1 - $rate));
$subtotal = 0; $total = 0;
foreach ($rows as $p) { $subtotal += $p['price'] * $cart[$p['id']]; $total += $unit($p) * $cart[$p['id']]; }
$discount = $subtotal - $total;

if ($a === 'order' && $rows) {
    $name = isset($_SESSION['user_id']) ? $_SESSION['username'] : trim($_POST['customer_name'] ?? '');
    if ($name === '') $error = 'Isi nama kamu dulu.';
    else {
        $cashier = $pdo->query("SELECT u.id FROM users u WHERE u.role='cashier' ORDER BY (SELECT COUNT(*) FROM orders o WHERE o.cashier_id=u.id AND o.status<>'done') LIMIT 1")->fetchColumn() ?: null;
        $code = 'KP-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO orders (order_code, user_id, customer_name, cashier_id, subtotal, discount, total) VALUES (?,?,?,?,?,?,?)")
            ->execute([$code, $_SESSION['user_id'] ?? null, $name, $cashier, $subtotal, $discount, $total]);
        $oid = $pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO order_items (order_id, name, price, qty) VALUES (?,?,?,?)");
        foreach ($rows as $p) $ins->execute([$oid, $p['name'], $unit($p), $cart[$p['id']]]);
        $pdo->commit();
        $_SESSION['cart'] = [];
        header('Location: pay.php?code=' . urlencode($code)); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Checkout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container">
    <h3 class="mb-3">Checkout</h3>
    <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (!$rows): ?><div class="alert alert-info">Keranjang masih kosong. <a href="index.php">Lihat menu</a></div>
    <?php else: ?>
    <div class="card shadow-sm border-0"><div class="card-body">
        <div class="table-responsive"><table class="table align-middle">
            <thead class="table-dark"><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th class="text-end">Total</th></tr></thead><tbody>
            <?php foreach ($rows as $p): $q = $cart[$p['id']]; ?>
                <tr><td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?php if ($rate): ?><small class="text-muted text-decoration-line-through"><?= rp($p['price']) ?></small> <?php endif; ?><?= rp($unit($p)) ?></td>
                    <td><form method="POST" class="d-inline"><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button name="a" value="minus" class="btn btn-sm btn-outline-secondary">−</button> <?= $q ?> <button name="a" value="plus" class="btn btn-sm btn-outline-secondary">+</button></form></td>
                    <td class="text-end"><?= rp($unit($p) * $q) ?></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= rp($subtotal) ?></td></tr>
            <?php if ($rate): ?><tr><td colspan="3" class="text-end text-success">Diskon member 10%</td><td class="text-end text-success">− <?= rp($discount) ?></td></tr><?php endif; ?>
            <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= rp($total) ?></td></tr>
        </tbody></table></div>
        <form method="POST">
            <?php if (!isset($_SESSION['user_id'])): ?><input type="text" name="customer_name" class="form-control mb-3" placeholder="Nama kamu (tanpa login)" required>
            <p class="small text-muted">Login sebagai customer untuk diskon 10%.</p><?php endif; ?>
            <button name="a" value="order" class="btn btn-primary">Pesan Sekarang</button>
        </form>
    </div></div>
    <?php endif; ?>
</div>
</body></html>