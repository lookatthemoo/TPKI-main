<?php
require_once 'auth_check.php';

// Definisi File Database
$fileInv = 'data/inventory.json';
$fileExp = 'data/pengeluaran.json';
$fileRek = 'data/rekening.json'; // File Rekening untuk SeaBank

// 1. Load Data Inventory
$inventory = file_exists($fileInv) ? json_decode(file_get_contents($fileInv), true) : [];
$lowStockItems = [];

// Filter Barang yang Stoknya Menipis (<= 5)
foreach ($inventory as $key => $item) {
    $minStok = isset($item['min_stok']) ? $item['min_stok'] : 5;
    
    // Cek Stok
    if (isset($item['qty']) && $item['qty'] <= $minStok) {
        $item['index'] = $key;
        
        // Estimasi Beli: Kembalikan ke stok aman (Target: 20 pcs)
        // Jika stok 3, maka saran beli = 17
        $item['saran_beli'] = 20 - $item['qty']; 
        $lowStockItems[] = $item;
    }
}

// 2. Proses Eksekusi Belanja (Saat Tombol Ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'belanja_selesai') {
    
    // A. Hitung Total Belanja Dulu
    $totalBelanja = 0;
    $transaksiBelanja = []; // Simpan data sementara biar gak bolak-balik loop

    foreach ($_POST['beli'] as $index => $qtyBeli) {
        if ($qtyBeli > 0) {
            $hargaSatuan = (int)$_POST['harga'][$index];
            $subtotal = $qtyBeli * $hargaSatuan;
            $totalBelanja += $subtotal;
            
            $transaksiBelanja[$index] = [
                'qty' => $qtyBeli,
                'harga' => $hargaSatuan
            ];
        }
    }

    if ($totalBelanja > 0) {
        // B. Cek Saldo SeaBank
        $rekeningData = file_exists($fileRek) ? json_decode(file_get_contents($fileRek), true) : [];
        $bankIndex = -1;

        // Cari Index SeaBank
        foreach ($rekeningData as $key => $rek) {
            if (stripos($rek['nama_bank'], 'SeaBank') !== false) {
                $bankIndex = $key;
                break;
            }
        }

        // Validasi Rekening
        if ($bankIndex === -1) {
            die("Error: Akun SeaBank tidak ditemukan di database rekening!");
        }

        // Cek Apakah Saldo Cukup?
        if ($rekeningData[$bankIndex]['saldo'] < $totalBelanja) {
            // Redirect Error Saldo
            header("Location: belanja.php?status=gagal_saldo");
            exit();
        }

        // C. Eksekusi Transaksi (Potong Saldo)
        $rekeningData[$bankIndex]['saldo'] -= $totalBelanja;
        file_put_contents($fileRek, json_encode($rekeningData, JSON_PRETTY_PRINT));

        // D. Update Stok Inventory (Barang Bertambah)
        $itemsDibeliNama = [];
        foreach ($transaksiBelanja as $idx => $data) {
            if (isset($inventory[$idx])) {
                // Tambah Stok (Restock)
                $inventory[$idx]['qty'] += $data['qty'];
                // Update Harga Beli Terakhir (Biar data update terus)
                $inventory[$idx]['harga'] = $data['harga'];
                $inventory[$idx]['updated_at'] = date('Y-m-d H:i:s');

                $namaBarang = $inventory[$idx]['nama'];
                $satuan = $inventory[$idx]['satuan'] ?? 'pcs';
                $itemsDibeliNama[] = "$namaBarang (+{$data['qty']} $satuan)";
            }
        }
        file_put_contents($fileInv, json_encode($inventory, JSON_PRETTY_PRINT));

        // E. Catat Laporan Pengeluaran
        $pengeluaran = file_exists($fileExp) ? json_decode(file_get_contents($fileExp), true) : [];
        $pengeluaran[] = [
            'id' => uniqid('EXP-SHOP-'),
            'tanggal' => date('Y-m-d H:i:s'),
            'kategori' => 'Belanja Bahan Baku',
            'deskripsi' => 'Restock: ' . implode(', ', $itemsDibeliNama),
            'penerima' => 'Vendor Pasar',
            'sumber_dana' => 'SeaBank', // Sesuai Request
            'jumlah' => $totalBelanja,
            'admin' => $_SESSION['admin_username'] ?? 'Admin'
        ];
        file_put_contents($fileExp, json_encode($pengeluaran, JSON_PRETTY_PRINT));

        // Redirect Sukses
        header("Location: belanja.php?status=sukses");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Procurement - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f0fdf4; font-family: 'Manrope', sans-serif; }
        .shop-header {
            background: white; padding: 2rem; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .container-shop { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        
        .shop-card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08); border: 1px solid #d1fae5;
        }
        
        .table-shop { width: 100%; border-collapse: collapse; }
        .table-shop th { background: #059669; color: white; padding: 15px; text-align: left; font-size: 0.9rem; }
        .table-shop td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; vertical-align: middle; }
        
        .input-qty { width: 70px; padding: 8px; border: 2px solid #e2e8f0; border-radius: 8px; text-align: center; font-weight: 700; }
        .input-price { width: 120px; padding: 8px; border: 2px solid #e2e8f0; border-radius: 8px; }
        
        .alert-stock { color: #dc2626; font-weight: 700; background: #fee2e2; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; }
        
        .btn-finish {
            background: #059669; color: white; padding: 15px 30px; border: none; border-radius: 10px;
            font-weight: 800; font-size: 1.1rem; cursor: pointer; float: right; margin: 20px;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4); transition: 0.3s;
        }
        .btn-finish:hover { background: #047857; transform: translateY(-2px); }

        .wa-btn {
            text-decoration: none; background: #25D366; color: white; padding: 10px 20px; 
            border-radius: 50px; font-weight: 700; font-size: 0.9rem; display: inline-flex; 
            align-items: center; gap: 5px;
        }

        /* Alert Box Styles */
        .alert-box { padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid transparent; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    </style>
</head>
<body>

    <header class="shop-header">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #064e3b;">🛒 Daftar Belanja Pasar</h1>
            <p style="color: #64748b;">Smart Restock System (SeaBank Integrated)</p>
        </div>
        <a href="index.php" style="text-decoration:none; color:#64748b; font-weight:600;">← Kembali</a>
    </header>

    <main class="container-shop">
        
        <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
            <div class="alert-box alert-success">
                ✅ <b>Transaksi Berhasil!</b><br>
                - Stok gudang telah bertambah.<br>
                - Pembayaran via <b>SeaBank</b> berhasil dicatat.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['status']) && $_GET['status']=='gagal_saldo'): ?>
            <div class="alert-box alert-danger">
                ⚠️ <b>Transaksi Ditolak!</b><br>
                Saldo <b>SeaBank</b> tidak mencukupi untuk melakukan pembayaran ini.<br>
                Silakan top-up rekening terlebih dahulu.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="belanja_selesai">
            
            <div class="shop-card">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="color:#0f172a;">📋 Barang Stok Kritis (< 5)</h3>
                    
                    <?php 
                        $waText = "Daftar Belanja PadangOrigins:%0a";
                        foreach($lowStockItems as $ls) {
                            $waText .= "- " . $ls['nama'] . " (" . $ls['saran_beli'] . " " . ($ls['satuan'] ?? 'pcs') . ")%0a";
                        }
                    ?>
                    <a href="https://wa.me/?text=<?= $waText; ?>" target="_blank" class="wa-btn">
                        📲 Kirim ke WA
                    </a>
                </div>

                <table class="table-shop">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Sisa Stok</th>
                            <th>Rencana Beli</th>
                            <th>Harga Satuan (Rp)</th>
                            <th>Subtotal Estimasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($lowStockItems)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">Semua stok aman! Tidak ada yang perlu dibeli.</td></tr>
                        <?php else: ?>
                            <?php foreach($lowStockItems as $item): ?>
                                <tr>
                                    <td>
                                        <b><?= $item['nama']; ?></b><br>
                                        <small style="color:#64748b;">Kategori: <?= $item['kategori'] ?? '-'; ?></small>
                                    </td>
                                    <td>
                                        <span class="alert-stock">Sisa: <?= $item['qty']; ?> <?= $item['satuan'] ?? 'pcs'; ?></span>
                                    </td>
                                    <td>
                                        <input type="number" name="beli[<?= $item['index']; ?>]" 
                                               class="input-qty" value="<?= $item['saran_beli']; ?>" 
                                               oninput="hitungTotal()"> <?= $item['satuan'] ?? 'pcs'; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="harga[<?= $item['index']; ?>]" 
                                               class="input-price" value="<?= isset($item['harga']) ? $item['harga'] : 0; ?>" 
                                               oninput="hitungTotal()">
                                    </td>
                                    <td style="font-weight:700; color:#059669;" id="subtotal-<?= $item['index']; ?>">
                                        Rp 0
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if(!empty($lowStockItems)): ?>
                    <div style="background:#f0fdf4; padding:20px; text-align:right; border-top:1px solid #d1fae5;">
                        <div style="margin-bottom:10px; font-size:0.9rem; color:#047857;">
                            Metode Pembayaran: <b>Bank SeaBank</b> (Otomatis)
                        </div>
                        <span style="font-size:1.1rem; color:#064e3b; margin-right:15px;">Total Belanja:</span>
                        <span id="grandTotal" style="font-size:1.8rem; font-weight:800; color:#059669;">Rp 0</span>
                        <br>
                        <button type="submit" class="btn-finish">✅ Bayar & Update Stok</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>

    </main>

    <script>
        function hitungTotal() {
            let grandTotal = 0;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const qtyInput = row.querySelector('.input-qty');
                const priceInput = row.querySelector('.input-price');
                const subDisplay = row.querySelector('td:last-child');
                
                if(qtyInput && priceInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const sub = qty * price;
                    
                    subDisplay.innerText = "Rp " + sub.toLocaleString('id-ID');
                    grandTotal += sub;
                }
            });
            
            document.getElementById('grandTotal').innerText = "Rp " + grandTotal.toLocaleString('id-ID');
        }
        
        // Hitung saat load pertama
        document.addEventListener('DOMContentLoaded', hitungTotal);
    </script>

</body>
</html>