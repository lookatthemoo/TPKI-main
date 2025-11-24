<?php
session_start();
header('Content-Type: application/json');

// Cek apakah pelanggan punya session order_id
if (!isset($_SESSION['current_order_id'])) {
    echo json_encode(['status' => 'Belum ada pesanan']);
    exit;
}

$currentOrderId = $_SESSION['current_order_id'];
$transaksiFile = '../DashboardKeuangan/data/transaksi.json';

if (!file_exists($transaksiFile)) {
    echo json_encode(['status' => 'Error: Data tidak ditemukan']);
    exit;
}

$transaksiData = json_decode(file_get_contents($transaksiFile), true);
if (!is_array($transaksiData)) {
    echo json_encode(['status' => 'Error: Data rusak']);
    exit;
}

$statusDitemukan = 'Pesanan tidak ditemukan';

// Cari pesanan pelanggan di file JSON
foreach (array_reverse($transaksiData) as $trx) { // Cari dari yang terbaru
    if (isset($trx['id']) && $trx['id'] === $currentOrderId) {
        $statusDitemukan = $trx['status']; // Ambil statusnya
        break;
    }
}

// Jika status sudah "Pesanan Jadi", hapus session agar bisa order lagi
if ($statusDitemukan === 'Pesanan Jadi') {
    unset($_SESSION['current_order_id']);
}

echo json_encode(['status' => $statusDitemukan]);
exit;
?>