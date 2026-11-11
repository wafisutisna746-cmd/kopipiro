<?php
session_start();
require 'db.php';

// Proteksi Halaman: Hanya admin (owner) yang boleh mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin.");
}

$message = ""; $message_type = ""; $tab = $_GET['tab'] ?? 'pesanan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['a'] ?? '';
    $tab = 'menu';
    if ($a === 'add_product') {
        $name = trim($_POST['name']); $price = (int)$_POST['price'];
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $target_file = $target_dir . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $pdo->prepare("INSERT INTO products (name, price, image_path, category, description) VALUES (?,?,?,?,?)")
                    ->execute([$name, $price, $target_file, $_POST['category'], trim($_POST['description'])]);
                $message = "Produk berhasil ditambahkan!"; $message_type = "success";
            } else { $message = "Gagal mengunggah gambar."; $message_type = "danger"; }
        } else { $message = "Format gambar harus JPG, JPEG, PNG, atau WEBP."; $message_type = "warning"; }
    }
    if ($a === 'set_price') { $pdo->prepare("UPDATE products SET price=? WHERE id=?")->execute([(int)$_POST['price'], (int)$_POST['id']]); }
    if ($a === 'del_product') { $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]); }
    if ($a === 'add_cashier') {
        $tab = 'kasir';
        try {
            $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?,?, 'cashier')")
                ->execute([trim($_POST['username']), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            $message = "Akun kasir dibuat."; $message_type = "success";
        } catch (PDOException $e) { $message = "Username sudah dipakai."; $message_type = "warning"; }
    }
}
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

function bars($title, $rows) {
    $max = max([1, ...array_map(fn($r) => $r['v'], $rows)]);
    echo '<div class="card shadow-sm border-0 mb-3"><div class="card-body"><h5>' . $title . '</h5>';
    if (!$rows) echo '<p class="text-muted mb-0">Belum ada penjualan.</p>';
    foreach ($rows as $r) {
        echo '<div class="small mt-2">' . htmlspecialchars($r['label']) . ' — ' . rp($r['v']) . '</div><div class="progress" style="height:14px"><div class="progress-bar bg-dark" style="width:' . ($r['v'] / $max * 100) . '%"></div></div>';
    }
    echo '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Coffee Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container">
    <div class="d-flex gap-2 flex-wrap mb-3">
        <?php foreach (['pesanan' => 'Semua Pesanan', 'grafik' => 'Grafik Penjualan', 'menu' => 'Kelola Menu', 'kasir' => 'Akun Kasir'] as $k => $label): ?>
            <a href="?tab=<?= $k ?>" class="btn btn-sm <?= $k === $tab ? 'btn-dark' : 'btn-outline-dark' ?>"><?= $label ?></a>
        <?php endforeach; ?>
        <a href="export.php" class="btn btn-sm btn-success ms-auto">⬇ Download Excel</a>
    </div>
    <?php if ($message): ?><div class="alert alert-<?= $message_type ?> py-2"><?= $message ?></div><?php endif; ?>

    <?php if ($tab === 'pesanan'): ?>
        <div class="card shadow-sm border-0"><div class="card-body">
            <?php render_orders($pdo, $pdo->query("SELECT o.*, c.username AS cashier FROM orders o LEFT JOIN users c ON c.id=o.cashier_id ORDER BY o.id DESC")->fetchAll(PDO::FETCH_ASSOC), false, true); ?>
        </div></div>

    <?php elseif ($tab === 'grafik'):
    $daily = $pdo->query("SELECT DATE(created_at) AS day, SUM(total) AS revenue FROM orders WHERE status IN ('paid','done') GROUP BY DATE(created_at) ORDER BY day")->fetchAll(PDO::FETCH_ASSOC);
    $top = $pdo->query("SELECT i.name, SUM(i.qty) AS qty FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.status IN ('paid','done') GROUP BY i.name ORDER BY qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); ?>
    <div class="alert alert-light border">Grafik hanya menghitung pesanan berstatus <b>paid</b> atau <b>done</b>.</div>
    <div class="row g-3">
        <div class="col-md-7"><div class="card shadow-sm border-0"><div class="card-body"><canvas id="c1"></canvas></div></div></div>
        <div class="col-md-5"><div class="card shadow-sm border-0"><div class="card-body"><canvas id="c2"></canvas></div></div></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('c1'), {type: 'bar', data: {labels: <?= json_encode(array_column($daily, 'day'), JSON_HEX_TAG) ?>,
        datasets: [{label: 'Pendapatan (Rp)', data: <?= json_encode(array_map('floatval', array_column($daily, 'revenue'))) ?>}]}});
    new Chart(document.getElementById('c2'), {type: 'pie', data: {labels: <?= json_encode(array_column($top, 'name'), JSON_HEX_TAG) ?>,
        datasets: [{data: <?= json_encode(array_map('intval', array_column($top, 'qty'))) ?>}]}});
    </script>

    <?php elseif ($tab === 'kasir'): ?>
        <div class="card shadow-sm border-0" style="max-width:420px"><div class="card-body p-4">
            <h4 class="mb-3">Tambah Akun Kasir</h4>
            <form method="POST"><input type="hidden" name="a" value="add_cashier">
                <input type="text" name="username" class="form-control mb-3" placeholder="Username kasir" required>
                <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                <button class="btn btn-primary w-100">Buat Akun Kasir</button></form>
        </div></div>

    <?php else: ?>
    <div class="row g-4">
        <div class="col-md-5"><div class="card shadow-sm border-0"><div class="card-body p-4">
            <h4 class="card-title mb-3">Tambah Menu Baru</h4>
            <form action="admin.php" method="POST" enctype="multipart/form-data"><input type="hidden" name="a" value="add_product">
                <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" placeholder="Contoh: Caramel Macchiato" required></div>
                <div class="mb-3"><label class="form-label">Kategori</label>
                    <select name="category" class="form-select"><option>Coffee</option><option>Non-Coffee</option><option>Toast</option><option>Lainnya</option></select></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Harga (Rp)</label><input type="number" step="500" name="price" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Foto Produk</label><input type="file" name="image" class="form-control" accept="image/*" required></div>
                <button type="submit" class="btn btn-primary w-100">Tambah ke Menu</button>
            </form>
        </div></div></div>
        <div class="col-md-7"><div class="card shadow-sm border-0"><div class="card-body p-4">
            <h4 class="card-title mb-3">Daftar Menu Saat Ini</h4>
            <?php if (empty($products)): ?><div class="alert alert-info mb-0">Belum ada menu.</div><?php else: ?>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead class="table-dark"><tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th></th></tr></thead><tbody>
                <?php foreach ($products as $p): ?><tr>
                    <td><img src="<?= htmlspecialchars($p['image_path']) ?>" alt="foto" style="width:50px;height:50px;object-fit:cover;" class="rounded"></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td><td><?= htmlspecialchars($p['category']) ?></td>
                    <td><form method="POST"><input type="hidden" name="a" value="set_price"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="number" name="price" value="<?= $p['price'] ?>" step="500" class="form-control form-control-sm" style="width:110px" onchange="this.form.submit()"></form></td>
                    <td><form method="POST" onsubmit="return confirm('Hapus menu ini?')"><input type="hidden" name="a" value="del_product"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td>
                </tr><?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
        </div></div></div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>