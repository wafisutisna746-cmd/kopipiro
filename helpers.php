<?php
// helpers.php
session_start();

$host = 'localhost';
$dbname = 'coffeeshop'; // Sesuaikan dengan nama DB kamu di phpMyAdmin
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function me(): ?array {
    return $_SESSION['user'] ?? null;
}

function need_role(array $roles): array {
    $u = me();
    if (!$u) out(['message' => 'Silakan login dulu'], 401);
    if (!in_array($u['role'], $roles, true)) out(['message' => 'Akses ditolak'], 403);
    return $u;
}

function final_price(int $price, ?array $user): int {
    $is_member = $user && $user['role'] === 'customer';
    return $is_member ? (int) round($price * 0.90) : $price;
}
?>