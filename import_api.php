<?php
// Import menu kopi dari API publik (tanpa API key). Dijalankan sekali oleh admin: buka /import_api.php
session_start();
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') die('Akses ditolak.');

$ch = curl_init('https://api.sampleapis.com/coffee/hot');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
$list = json_decode((string)$resp, true);

$added = 0;
if ($code === 200 && is_array($list)) {
    $exists = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
    $ins = $pdo->prepare("INSERT INTO products (name, price, image_path, category, description) VALUES (?,?,?,?,?)");
    $rate = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating) VALUES (?, NULL, ?)");
    foreach ($list as $c) {
        $exists->execute([$c['title']]);
        if ($exists->fetchColumn()) continue;
        $desc = $c['description'] . ' Bahan: ' . implode(', ', $c['ingredients'] ?? []);
        $price = 20000 + ($c['id'] % 6) * 2500;   // API tidak punya harga, jadi kita buat sendiri
        $ins->execute([$c['title'], $price, $c['image'] ?? '', 'Coffee', $desc]);
        $pid = $pdo->lastInsertId();
        for ($i = 0; $i < 3; $i++) $rate->execute([$pid, random_int(3, 5)]);   // rating demo
        $added++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Import API</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:560px">
<?php if ($code === 200 && is_array($list)): ?>
    <div class="alert alert-success"><?= $added ?> menu baru diimpor (sisanya sudah ada).</div>
<?php else: ?>
    <div class="alert alert-danger">API publik gagal (HTTP <?= (int)$code ?>) <?= htmlspecialchars($err) ?>. Coba lagi nanti.</div>
<?php endif; ?>
<a href="admin.php?tab=menu" class="btn btn-dark">Kembali ke admin</a></div></body></html>