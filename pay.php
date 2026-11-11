<?php
session_start();
require 'db.php';

$st = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND status = 'pending'");
$st->execute([$_GET['code'] ?? '']);
$o = $st->fetch(PDO::FETCH_ASSOC);
if (!$o) die('Pesanan pending tidak ditemukan.');
if ($o['user_id'] && ($_SESSION['user_id'] ?? 0) != $o['user_id']) die('Ini bukan pesananmu.');

$msg = '';
if ('Mid-server-h_Iw5XMxIREhOvdaFZpteeUs') {
    $msg = 'Pembayaran online belum dikonfigurasi. Kamu bisa membayar tunai di kasir.';
} else {
    $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $ch = curl_init('https://app.sandbox.midtrans.com/snap/v1/transactions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_USERPWD => MIDTRANS_SERVER_KEY . ':',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'transaction_details' => ['order_id' => $o['order_code'], 'gross_amount' => (int)$o['total']],
            'customer_details' => ['first_name' => $o['customer_name']],
            'callbacks' => ['finish' => $base . '/orders.php'],
        ]),
    ]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $data = json_decode((string)$resp, true);
    if ($code === 201 && !empty($data['redirect_url'])) { header('Location: ' . $data['redirect_url']); exit; }
    $msg = 'Gagal menghubungi payment gateway. Kamu bisa membayar tunai di kasir.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pembayaran</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container" style="max-width:520px">
    <div class="card shadow-sm border-0"><div class="card-body p-4 text-center">
        <h4>Pesanan <?= htmlspecialchars($o['order_code']) ?> dibuat</h4>
        <p class="fs-4 fw-bold"><?= rp($o['total']) ?></p>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <a href="<?= $o['user_id'] ? 'orders.php' : 'index.php?ok=1' ?>" class="btn btn-primary">Selesai</a>
    </div></div>
</div>
</body></html>