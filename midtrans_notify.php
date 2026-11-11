<?php
// Webhook dari Midtrans (URL ini diisi di dashboard Midtrans > Payment Notification URL)
require 'db.php';
$n = json_decode(file_get_contents('php://input'), true) ?? [];

// 1. Pastikan benar-benar dari Midtrans: SHA512(order_id + status_code + gross_amount + server key)
$expected = hash('sha512', ($n['order_id'] ?? '') . ($n['status_code'] ?? '') . ($n['gross_amount'] ?? '') . MIDTRANS_SERVER_KEY);
if (!hash_equals($expected, $n['signature_key'] ?? '')) { http_response_code(403); exit(json_encode(['message' => 'Invalid signature'])); }

// 2. Sukses = status_code 200 + (settlement, atau capture dengan fraud accept)
$status = $n['transaction_status'] ?? ''; $fraud = $n['fraud_status'] ?? 'accept';
$paid = ($n['status_code'] ?? '') === '200' && ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept'));
if ($paid) {   // cek nominal juga, dan hanya ubah pesanan pending (aman kalau dikirim dua kali)
    $pdo->prepare("UPDATE orders SET status='paid' WHERE order_code=? AND total=? AND status='pending'")
        ->execute([$n['order_id'], (int)$n['gross_amount']]);
}
echo json_encode(['ok' => true]);