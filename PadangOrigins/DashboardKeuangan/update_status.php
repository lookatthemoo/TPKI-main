<?php
require_once 'auth_check.php'; // Cek sesi login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['new_status'] ?? null;

    if ($orderId && $newStatus) {
        
        $transaksiFile = 'data/transaksi.json';
        $transaksiData = [];

        if (file_exists($transaksiFile)) {
            $transaksiJson = file_get_contents($transaksiFile);
            $transaksiData = json_decode($transaksiJson, true);
        }

        if (is_array($transaksiData)) {
            $orderFound = false;
            // Loop menggunakan reference (&) agar bisa mengubah nilainya langsung
            foreach ($transaksiData as &$trx) { 
                if (isset($trx['id']) && $trx['id'] === $orderId) {
                    $trx['status'] = $newStatus; // Update status pesanan
                    $orderFound = true;
                    break; // Hentikan loop jika sudah ketemu
                }
            }
            unset($trx); // Wajib di-unset setelah loop reference

            // Jika order ditemukan, simpan kembali filenya
            if ($orderFound) {
                file_put_contents($transaksiFile, json_encode($transaksiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
    }
}

// Setelah selesai, kembalikan admin ke halaman pesanan
header('Location: pesanan.php');
exit;
?>