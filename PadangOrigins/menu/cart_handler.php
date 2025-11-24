<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

function calculateTotal() {
    $t = 0;
    foreach ($_SESSION['cart'] as $item) {
        $t += (int)$item['price'] * (int)$item['qty'];
    }
    return $t;
}

$response = [];

switch ($action) {
    case 'add':
        $newName = $input['name'];
        $newPrice = (int)$input['price'];
        // PERHATIKAN INI: Wajib ambil 'qty' dari input
        $newQty = isset($input['qty']) ? (int)$input['qty'] : 1; 

        if ($newQty > 0) {
            $found = false;
            // Cek apakah item sudah ada? Kalau ada, tambahkan jumlahnya (Group)
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['name'] === $newName) {
                    $item['qty'] += $newQty; 
                    $found = true;
                    break;
                }
            }
            // Kalau belum, buat baru
            if (!$found) {
                $_SESSION['cart'][] = [
                    'name' => $newName,
                    'price' => $newPrice,
                    'qty' => $newQty
                ];
            }
        }
        
        $response = ['cart' => $_SESSION['cart'], 'total' => calculateTotal(), 'success' => true];
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        $response = ['cart' => [], 'total' => 0, 'success' => true];
        break;

    case 'checkout':
        if (count($_SESSION['cart']) > 0) {
            // DEFINE FILE PATHS
            $transaksiFile = '../DashboardKeuangan/data/transaksi.json'; 
            $rekeningFile  = '../DashboardKeuangan/data/rekening.json'; // File Rekening

            // Ambil data dari input JS
            $custName = $input['customer_name'] ?? 'Pelanggan';
            $payMethod = $input['payment_method'] ?? 'kas_laci';
            
            // Buat Deskripsi Barang (Contoh: "2x Rendang, 1x Ayam")
            $itemDetails = [];
            foreach ($_SESSION['cart'] as $c) {
                $itemDetails[] = "{$c['qty']}x {$c['name']}";
            }
            $descString = implode(", ", $itemDetails);

            $orderId = uniqid('order_');
            $total = calculateTotal();

            // ==============================================================
            // 1. LOGIKA UPDATE SALDO REKENING (Auto-Sync)
            // ==============================================================
            if (file_exists($rekeningFile)) {
                $rekeningData = json_decode(file_get_contents($rekeningFile), true);
                $isRekeningUpdated = false;

                foreach ($rekeningData as &$rek) {
                    // Cek kecocokan Nama Bank (misal: BCA == BCA) atau ID (BANK-BCA)
                    // Menggunakan strtolower agar tidak sensitif huruf besar/kecil
                    if (strtolower($rek['nama_bank']) === strtolower($payMethod) || 
                        strtolower($rek['id']) === strtolower($payMethod)) {
                        
                        // TAMBAH SALDO!
                        $rek['saldo'] += $total;
                        $isRekeningUpdated = true;
                        
                        // Ubah payMethod jadi nama resmi banknya agar rapi di laporan
                        $payMethod = $rek['nama_bank']; 
                        break; 
                    }
                }

                // Simpan perubahan saldo jika ada rekening yang cocok
                if ($isRekeningUpdated) {
                    file_put_contents($rekeningFile, json_encode($rekeningData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
            // ==============================================================

            // 2. SIMPAN TRANSAKSI KE LOG
            $newTransaction = [
                'id'          => $orderId,
                'tanggal'     => date('Y-m-d H:i:s'),
                'tipe'        => 'pendapatan', 
                'status'      => 'Diterima',
                'jumlah'      => $total,
                
                // DATA KEUANGAN
                'akun_sumber' => 'Customer', 
                'akun_tujuan' => $payMethod, // Ini akan berisi "BCA", "GoPay", atau "kas_laci"
                'deskripsi'   => "Order ($custName): $descString",
                'pelaku'      => 'System',
                
                'items'       => $_SESSION['cart'],
                'customer_name' => $custName
            ];
            
            $transaksiData = file_exists($transaksiFile) ? json_decode(file_get_contents($transaksiFile), true) : [];
            $transaksiData[] = $newTransaction;
            file_put_contents($transaksiFile, json_encode($transaksiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // RESET CART
            $_SESSION['cart'] = []; 
            $_SESSION['current_order_id'] = $orderId; 
            
            $response = ['success' => true, 'message' => 'Checkout berhasil & Saldo Terupdate!', 'cart' => [], 'total' => 0, 'order_id' => $orderId, 'order_status' => 'Diterima'];
        } else {
             $response = ['success' => false, 'message' => 'Keranjang kosong.'];
        }
        break;
}

echo json_encode($response);
exit;
?>