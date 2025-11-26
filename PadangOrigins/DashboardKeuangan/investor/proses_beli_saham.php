<?php
session_start();
$fileRekening = '../data/rekening.json';
$filePengeluaran = '../data/pengeluaran.json';
$filePortfolio = '../data/portfolio.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeSaham = $_POST['kode_saham'];
    $hargaBeli = (int)$_POST['harga_saat_ini'];
    $lot = (int)$_POST['jumlah_lot'];
    
    // 1 Lot = 100 Lembar
    $totalLembar = $lot * 100;
    $totalBayar = $hargaBeli * $totalLembar;

    // 1. Cek Saldo Mandiri
    $rekeningData = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
    $mandiriIndex = -1;
    
    foreach ($rekeningData as $key => $rek) {
        // Cari Bank Mandiri
        if (stripos($rek['nama_bank'], 'Mandiri') !== false) {
            $mandiriIndex = $key;
            break;
        }
    }

    if ($mandiriIndex === -1) {
        die("Error: Rekening Mandiri tidak ditemukan di sistem!");
    }

    // Validasi Saldo Cukup
    if ($rekeningData[$mandiriIndex]['saldo'] < $totalBayar) {
        header("Location: index.php?status=gagal_saldo");
        exit();
    }

    // 2. Potong Saldo Rekening
    $rekeningData[$mandiriIndex]['saldo'] -= $totalBayar;
    file_put_contents($fileRekening, json_encode($rekeningData, JSON_PRETTY_PRINT));

    // 3. Catat di Pengeluaran (Disesuaikan dengan format pengeluaran.json yang ada)
    $pengeluaranData = file_exists($filePengeluaran) ? json_decode(file_get_contents($filePengeluaran), true) : [];
    
    // Ambil nama investor yang sedang login, jika tidak ada gunakan 'Investor'
    $namaAdmin = $_SESSION['investor_nama'] ?? 'Investor'; 

    $pengeluaranData[] = [
        'id' => uniqid('EXP-INV-'),
        'tanggal' => date('Y-m-d H:i:s'), // Format waktu lengkap
        'kategori' => 'Investasi Saham',
        'deskripsi' => "Beli $lot Lot $kodeSaham @ " . number_format($hargaBeli, 0, ',', '.'),
        'penerima' => 'Bursa Efek / Broker', // Field wajib agar tabel tidak error
        'sumber_dana' => 'Mandiri', // Sesuai nama di rekening.json
        'jumlah' => $totalBayar,
        'admin' => $namaAdmin // Mencatat siapa yang melakukan transaksi
    ];
    file_put_contents($filePengeluaran, json_encode($pengeluaranData, JSON_PRETTY_PRINT));

    // 4. Masukkan ke Portofolio Investor
    $portfolioData = file_exists($filePortfolio) ? json_decode(file_get_contents($filePortfolio), true) : [];
    
    // Cek apakah sudah punya saham ini (Average Down Logic)
    $found = false;
    foreach ($portfolioData as &$saham) {
        if ($saham['kode'] === $kodeSaham) {
            // Hitung harga rata-rata baru (Avg Price)
            $totalInvestasiLama = $saham['avg_price'] * ($saham['lot'] * 100);
            $totalInvestasiBaru = $totalInvestasiLama + $totalBayar;
            $totalLotBaru = $saham['lot'] + $lot;
            
            $saham['lot'] = $totalLotBaru;
            $saham['avg_price'] = round($totalInvestasiBaru / ($totalLotBaru * 100));
            $found = true;
            break;
        }
    }

    // Jika saham baru, tambahkan ke list
    if (!$found) {
        $portfolioData[] = [
            'kode' => $kodeSaham,
            'lot' => $lot,
            'avg_price' => $hargaBeli,
            'beli_at' => date('Y-m-d H:i:s')
        ];
    }

    file_put_contents($filePortfolio, json_encode($portfolioData, JSON_PRETTY_PRINT));

    // Redirect Sukses
    header("Location: index.php?status=sukses_beli");
    exit();
}
?>