<?php
session_start();
// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman utama
header("Location: index.php");
exit;
?>