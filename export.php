<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';   // composer require phpoffice/phpspreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (($_SESSION['role'] ?? '') !== 'admin') die('Akses ditolak.');

$rows = $pdo->query("SELECT o.order_code, o.created_at, o.customer_name, c.username AS cashier, o.subtotal, o.discount, o.total, o.status
    FROM orders o LEFT JOIN users c ON c.id = o.cashier_id ORDER BY o.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('Orders');
$sheet->fromArray(['Kode', 'Tanggal', 'Pemesan', 'Kasir', 'Subtotal', 'Diskon', 'Total', 'Status'], null, 'A1');
$sheet->fromArray(array_map('array_values', $rows), null, 'A2');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="kopipiro-orders.xlsx"');
(new Xlsx($book))->save('php://output');