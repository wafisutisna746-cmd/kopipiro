<?php
// api/auth.php
require '../helpers.php';

$action = $_GET['action'] ?? '';
$in = body();

switch ($action) {
    case 'register':
        $username = trim($in['username'] ?? '');
        $pass = $in['password'] ?? '';

        if (empty($username) || strlen($pass) < 6) {
            out(['message' => 'Username dan password (min 6 karakter) wajib diisi'], 400);
        }

        try {
            $st = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
            $st->execute([$username, password_hash($pass, PASSWORD_DEFAULT)]);
            out(['message' => 'Registrasi berhasil. Silakan login.'], 201);
        } catch (PDOException $e) {
            out(['message' => 'Registrasi gagal. Username sudah ada.'], 400);
        }
        break;

    case 'login':
        $st = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $st->execute([trim($in['username'] ?? '')]);
        $u = $st->fetch();

        if (!$u || !password_verify($in['password'] ?? '', $u['password'])) {
            out(['message' => 'Username atau password salah'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'role' => $u['role']];
        out($_SESSION['user']);
        break;

    case 'logout':
        session_destroy();
        out(['message' => 'Berhasil logout']);
        break;

    case 'me':
        out(me() ?? ['role' => 'guest']);
        break;

    default:
        out(['message' => 'Action tidak ditemukan'], 404);
}
?>