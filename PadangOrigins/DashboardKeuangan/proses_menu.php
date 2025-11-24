<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'auth_check.php';

$fileMenu = 'data/menu.json';
$uploadDir = '../menu/images/'; // Lokasi folder gambar

// Helper
function getJson($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}
function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_menu') {
        
        // 1. Handle Upload Gambar
        $fileName = 'default.jpg';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            // Nama file unik agar tidak bentrok (menu_TIMESTAMP.jpg)
            $fileName = 'menu_' . time() . '.' . $ext; 
            $dest = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                die("Gagal upload gambar. Cek permission folder.");
            }
        }

        // 2. Simpan Data ke JSON
        $menus = getJson($fileMenu);
        $newMenu = [
            'id' => 'MENU-' . time(),
            'nama' => $_POST['nama'],
            'harga' => (int)$_POST['harga'],
            'deskripsi' => $_POST['deskripsi'],
            'gambar' => $fileName
        ];
        
        $menus[] = $newMenu;
        saveJson($fileMenu, $menus);
        
        header('Location: index.php?status=menu_added');
        exit;
    }
}
header('Location: index.php');
?>