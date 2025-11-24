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
            $transaksiFile = '../DashboardKeuangan/data/transaksi.json'; 
            
            // Ambil data dari input JS
            $custName = $input['customer_name'] ?? 'Pelanggan';
            $payMethod = $input['payment_method'] ?? 'kas_laci';
            
            // Buat Deskripsi Barang (Contoh: "2x Rendang, 1x Ayam")
            $itemDetails = [];
            foreach ($_SESSION['cart'] as $c) {
                $itemDetails[] = "{$c['qty']}x {$c['name']}";
            }
            $descString = implode(", ", $itemDetails);

            // Tentukan Tujuan Uang (Untuk Laporan Keuangan)
            // Jika 'kas_laci', masuk ke Laci. Jika Bank, masuk ke Bank tersebut.
            // Di Laporan Keuangan nanti:
            // - Jika tipe='pendapatan' dan akun_tujuan='kas_laci' -> Tambah Saldo Laci
            // - Jika tipe='pendapatan' dan akun_tujuan='BCA' -> Tambah Saldo BCA
            
            $orderId = uniqid('order_');
            $total = calculateTotal();

            $newTransaction = [
                'id'          => $orderId,
                'tanggal'     => date('Y-m-d H:i:s'),
                'tipe'        => 'pendapatan', // Tetap pendapatan
                'status'      => 'Diterima',
                'jumlah'      => $total,
                
                // KOLOM BARU UNTUK LAPORAN KEUANGAN PRO
                'akun_sumber' => 'Customer', // Uang dari Customer
                'akun_tujuan' => $payMethod, // Masuk ke Laci / Bank
                'deskripsi'   => "Order ($custName): $descString", // Deskripsi Lengkap
                'pelaku'      => 'System', // Tercatat otomatis
                
                'items'       => $_SESSION['cart'],
                'customer_name' => $custName
            ];
            
            $transaksiData = file_exists($transaksiFile) ? json_decode(file_get_contents($transaksiFile), true) : [];
            $transaksiData[] = $newTransaction;
            file_put_contents($transaksiFile, json_encode($transaksiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $_SESSION['cart'] = []; 
            $_SESSION['current_order_id'] = $orderId; 
            
            $response = ['success' => true, 'message' => 'Checkout berhasil!', 'cart' => [], 'total' => 0, 'order_id' => $orderId, 'order_status' => 'Diterima'];
        } else {
             $response = ['success' => false, 'message' => 'Keranjang kosong.'];
        }
        break;
}

echo json_encode($response);
exit;
?>