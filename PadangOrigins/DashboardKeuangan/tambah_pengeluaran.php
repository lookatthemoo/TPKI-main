<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    if ($jumlah && $jumlah > 0 && !empty($deskripsi)) {
        $file = 'data/transaksi.json';
        if (!file_exists($file)) {
            // Jika file belum ada, buat array kosong
            $currentData = [];
        } else {
            $currentData = json_decode(file_get_contents($file), true) ?? [];
        }

        // Buat data transaksi baru
        $newTrx = [
            'id' => 'EXP-' . time(), // ID unik untuk expense
            'tanggal' => date('Y-m-d H:i:s'),
            'tipe' => 'pengeluaran',
            'jumlah' => $jumlah,
            'deskripsi' => $deskripsi,
            'pelaku' => $_SESSION['admin_username'] // Mencatat siapa yang input
        ];

        // Tambahkan ke array dan simpan
        $currentData[] = $newTrx;
        file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        // Kembali ke dashboard dengan pesan sukses
        header('Location: index.php?status=sukses_input');
        exit;
    }
}

// Jika gagal atau akses langsung, kembalikan ke dashboard
header('Location: index.php');
exit;
?>