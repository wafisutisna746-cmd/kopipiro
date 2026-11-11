<?php
// api/products.php
require '../helpers.php';

$user = me();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $st = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        $products = $st->fetchAll();
        
        foreach ($products as &$p) {
            $p['price'] = (int)$p['price'];
            $p['final_price'] = final_price($p['price'], $user);
        }
        out($products);
        break;

    case 'POST':
        need_role(['admin']);
        
        $name = trim($_POST['name'] ?? '');
        $price = (int)($_POST['price'] ?? 0);

        if (empty($name) || $price < 1) {
            out(['message' => 'Nama dan harga produk wajib diisi'], 400);
        }

        $image_path = '';
        if (!empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                out(['message' => 'Format gambar harus JPG, PNG, atau WEBP'], 400);
            }

            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_name = bin2hex(random_bytes(8)) . '.' . $ext;
            $image_path = 'uploads/' . $file_name;
            move_uploaded_file($f['tmp_name'], "../" . $image_path);
        }

        $st = $pdo->prepare("INSERT INTO products (name, price, image_path) VALUES (?, ?, ?)");
        $st->execute([$name, $price, $image_path]);

        out(['message' => 'Produk berhasil ditambahkan', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        need_role(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        $price = (int)(body()['price'] ?? 0);

        if ($id < 1 || $price < 1) out(['message' => 'ID dan harga tidak valid'], 400);

        $st = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
        $st->execute([$price, $id]);
        out(['message' => 'Harga berhasil diubah']);
        break;

    default:
        out(['message' => 'Method tidak diizinkan'], 405);
}
?>