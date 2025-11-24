<?php
session_start();

// File Database
$fileInventory = '../data/inventory.json';

// Load Data
$inventoryData = file_exists($fileInventory) ? json_decode(file_get_contents($fileInventory), true) : [];

// Action Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- TAMBAH / EDIT BARANG ---
    if ($action === 'simpan_barang') {
        // [PERBAIKAN DI SINI]
        // Cek apakah ID kosong. Jika kosong, buat ID baru. Jika ada isinya, pakai ID itu.
        $id = !empty($_POST['id']) ? $_POST['id'] : uniqid('item_'); 
        
        $nama = htmlspecialchars($_POST['nama']);
        $kategori = $_POST['kategori'];
        $qty = (int)$_POST['qty'];
        $satuan = $_POST['satuan'];
        $harga = (int)$_POST['harga'];
        
        $newItem = [
            'id' => $id,
            'nama' => $nama,
            'kategori' => $kategori,
            'qty' => $qty,
            'satuan' => $satuan,
            'harga' => $harga,
            'total_nilai' => $qty * $harga,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Cek apakah ini edit atau baru
        $isEdit = false;
        foreach ($inventoryData as &$item) {
            // Gunakan (string) untuk memastikan tipe data sama saat membandingkan
            if ((string)$item['id'] === (string)$id) {
                $item = $newItem; // Update data lama
                $isEdit = true;
                break;
            }
        }

        if (!$isEdit) {
            $inventoryData[] = $newItem; // Tambah baru
        }

        file_put_contents($fileInventory, json_encode($inventoryData, JSON_PRETTY_PRINT));
        header('Location: index.php?status=success');
        exit;
    }

    // --- HAPUS BARANG ---
    if ($action === 'hapus_barang') {
        $id = $_POST['id'];
        // Filter array untuk membuang ID yang cocok
        $inventoryData = array_filter($inventoryData, function($i) use ($id) {
            return (string)$i['id'] !== (string)$id;
        });
        
        // Re-index array agar urutan index rapi (0, 1, 2...)
        $inventoryData = array_values($inventoryData);
        file_put_contents($fileInventory, json_encode($inventoryData, JSON_PRETTY_PRINT));
        header('Location: index.php?status=deleted');
        exit;
    }
}
header('Location: index.php');
?>