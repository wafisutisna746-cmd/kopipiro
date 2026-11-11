<?php
// Navbar bersama + fungsi tabel pesanan. Di-include dari halaman lain setelah session_start() dan require 'db.php'.
function rp($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
function render_orders($pdo, $rows, $edit = false, $showCashier = false) {
    if (!$rows) { echo '<div class="alert alert-info mb-0">Belum ada pesanan.</div>'; return; }
    echo '<div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-dark"><tr><th>#</th><th>Tanggal</th><th>Pemesan</th><th>Item</th><th>Total</th>'
        . ($showCashier ? '<th>Kasir</th>' : '') . '<th>Status</th></tr></thead><tbody>';
    foreach ($rows as $o) {
        $it = $pdo->prepare('SELECT name, qty FROM order_items WHERE order_id=?'); $it->execute([$o['id']]);
        $items = implode(', ', array_map(fn($i) => htmlspecialchars($i['name']) . ' ×' . $i['qty'], $it->fetchAll(PDO::FETCH_ASSOC)));
        echo '<tr><td>' . htmlspecialchars($o['order_code']) . '</td><td>' . $o['created_at'] . '</td><td>' . htmlspecialchars($o['customer_name']) . ($o['user_id'] ? '' : ' <span class="badge bg-secondary">tamu</span>') . '</td><td>' . $items . '</td><td>' . rp($o['total'])
            . ($o['discount'] > 0 ? '<br><small class="text-success">hemat ' . rp($o['discount']) . '</small>' : '') . '</td>'
            . ($showCashier ? '<td>' . htmlspecialchars($o['cashier'] ?? '-') . '</td>' : '') . '<td>';
        if ($edit) {
            echo '<form method="POST"><input type="hidden" name="order_id" value="' . $o['id'] . '"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">';
            foreach (['pending', 'paid', 'done'] as $s) echo '<option' . ($s === $o['status'] ? ' selected' : '') . '>' . $s . '</option>';
            echo '</select></form>';
        } else {
    echo htmlspecialchars($o['status']);
    if ($o['status'] === 'pending' && !empty($o['user_id']) && ($_SESSION['role'] ?? '') === 'customer')
        echo ' <a class="btn btn-sm btn-success" href="pay.php?code=' . urlencode($o['order_code']) . '">Bayar</a>';
}
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}
$role = $_SESSION['role'] ?? ''; $logged = isset($_SESSION['user_id']); $cartN = array_sum($_SESSION['cart'] ?? []);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4"><div class="container">
  <a class="navbar-brand fw-bold" href="index.php">☕ Coffee Shop</a>
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="cart.php" class="btn btn-outline-light btn-sm">🛒 Keranjang (<?= $cartN ?>)</a>
    <?php if ($logged): ?>
      <?php if ($role === 'admin'): ?><a href="admin.php" class="btn btn-outline-warning btn-sm">Dashboard</a>
      <?php else: ?><a href="orders.php" class="btn btn-outline-warning btn-sm"><?= $role === 'cashier' ? 'Pesanan Masuk' : 'Pesanan Saya' ?></a><?php endif; ?>
      <span class="navbar-text">Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><?= $role === 'customer' ? ' (Diskon 10%)' : '' ?></span>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm">Login</a><a href="register.php" class="btn btn-primary btn-sm">Daftar (Diskon 10%)</a>
    <?php endif; ?>
  </div></div></nav>