<?php
session_start();
require 'db.php';

$is_member = ($_SESSION['role'] ?? '') === 'customer';   // hanya customer login yang dapat diskon
$discount_rate = 0.10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['product_id'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    header('Location: index.php?cat=' . urlencode($_GET['cat'] ?? 'Semua')); exit;
}
$cat = $_GET['cat'] ?? 'Semua';
$cats = $pdo->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare("SELECT p.*, ROUND(AVG(r.rating),1) AS avg_rating, COUNT(r.id) AS review_count
    FROM products p LEFT JOIN reviews r ON r.product_id = p.id
    WHERE ? = 'Semua' OR p.category = ? GROUP BY p.id ORDER BY p.category, p.name");
$stmt->execute([$cat, $cat]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container">
    <?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Pesanan berhasil dibuat!</div><?php endif; ?>
    <div class="p-4 mb-4 bg-white rounded-3 shadow-sm border text-center">
        <h1 class="display-6 fw-bold">Nikmati Kopi Pilihan Terbaik</h1>
        <?php if ($is_member): ?><p class="lead text-success mb-0">Kamu sedang menikmati diskon member 10% untuk semua menu.</p>
        <?php else: ?><p class="lead text-muted mb-0">Dapatkan potongan <strong>10%</strong> khusus untuk pengguna terdaftar!</p><?php endif; ?>
    </div>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <?php foreach (array_merge(['Semua'], $cats) as $c): ?>
            <a href="?cat=<?= urlencode($c) ?>" class="btn btn-sm <?= $c === $cat ? 'btn-dark' : 'btn-outline-dark' ?>"><?= htmlspecialchars($c) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (empty($products)): ?><div class="col-12"><div class="alert alert-info">Belum ada produk.</div></div><?php endif; ?>
        <?php foreach ($products as $p):
            $final = $is_member ? round($p['price'] * (1 - $discount_rate)) : $p['price']; ?>
            <div class="col"><div class="card h-100 shadow-sm border-0">
                <img src="<?= htmlspecialchars($p['image_path']) ?>" class="card-img-top" alt="" style="height:200px;object-fit:cover;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between"><h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5><span class="badge bg-secondary align-self-start"><?= htmlspecialchars($p['category']) ?></span></div>
                    <p class="card-text small text-muted mb-1"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 100, '...')) ?></p>
                    <div class="small mb-2">
                        <?php if ($p['avg_rating']): ?><span class="text-warning"><?= str_repeat('★', (int)round($p['avg_rating'])) ?></span> <?= $p['avg_rating'] ?> <span class="text-muted">(<?= $p['review_count'] ?>)</span>
                        <?php else: ?><span class="text-muted">Belum ada rating</span><?php endif; ?>
                    </div>
                    <div class="mt-auto">
                        <div class="mb-2">
                            <?php if ($is_member): ?>
                                <small class="text-muted text-decoration-line-through"><?= rp($p['price']) ?></small><br>
                                <span class="fs-5 fw-bold text-success"><?= rp($final) ?></span> <span class="badge bg-danger">Diskon 10%</span>
                            <?php else: ?><span class="fs-5 fw-bold"><?= rp($p['price']) ?></span><?php endif; ?>
                        </div>
                        <form method="POST"><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-primary w-100">Tambah ke Keranjang</button></form>
                        <?php if ($is_member): ?>
                        <form method="POST" action="review.php" class="d-flex gap-1 mt-2"><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <select name="rating" class="form-select form-select-sm"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> ★</option><?php endfor; ?></select>
                            <button class="btn btn-sm btn-outline-secondary">Rating</button></form>
                        <?php endif; ?>
                    </div>
                </div>
            </div></div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>