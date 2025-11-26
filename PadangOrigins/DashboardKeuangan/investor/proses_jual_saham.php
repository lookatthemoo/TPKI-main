<?php
session_start();
$filePortfolio = '../data/portfolio.json';
$fileRekening = '../data/rekening.json';
$fileTransaksi = '../data/transaksi.json'; // Menggunakan transaksi.json untuk pencatatan uang masuk

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- BAGIAN YANG DIUBAH MULAI DARI SINI ---
    $kodeSaham = strtoupper($_POST['kode_saham']); 

    // Cek nama inputan form (karena form TradingView pakai name="harga_saat_ini")
    if (isset($_POST['harga_saat_ini'])) {
        $hargaJual = (int)$_POST['harga_saat_ini'];
    } else {
        $hargaJual = (int)$_POST['harga_jual']; // Jaga-jaga kalau pakai form lama
    }

    $lotJual = (int)$_POST['jumlah_lot'];
    // --- BATAS PERUBAHAN ---

    // Validasi dasar
    if ($lotJual <= 0 || $hargaJual <= 0) {
        header("Location: index.php?status=error");
        exit();
    }

    // 1. Hitung Total Uang Diterima (Withdraw + Profit)
    $totalLembar = $lotJual * 100;
    $totalTerima = $hargaJual * $totalLembar;

    // 2. Update Portofolio (Hapus Saham)
    $portfolioData = file_exists($filePortfolio) ? json_decode(file_get_contents($filePortfolio), true) : [];
    $newPortfolio = [];
    $found = false;

    foreach ($portfolioData as $saham) {
        if ($saham['kode'] === $kodeSaham) {
            // Logika: Menjual semua lot yang dipilih. Saham ini dihapus dari list portfolio.
            $found = true;
            continue; // Skip (hapus) saham ini dari array baru
        }
        $newPortfolio[] = $saham;
    }

    if (!$found) {
        // Jika saham tidak ditemukan, hentikan proses
        die("Error: Saham tidak ditemukan di portofolio.");
    }

    // Simpan portofolio baru (tanpa saham yang dijual)
    file_put_contents($filePortfolio, json_encode($newPortfolio, JSON_PRETTY_PRINT));

    // 3. Update Rekening (Tambah Saldo Mandiri)
    $rekeningData = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
    $mandiriIndex = -1;

    foreach ($rekeningData as $key => $rek) {
        if (stripos($rek['nama_bank'], 'Mandiri') !== false) {
            $mandiriIndex = $key;
            break;
        }
    }

    if ($mandiriIndex !== -1) {
        $rekeningData[$mandiriIndex]['saldo'] += $totalTerima;
        file_put_contents($fileRekening, json_encode($rekeningData, JSON_PRETTY_PRINT));
    }

    // 4. Catat Transaksi (Pemasukan)
    $transaksiData = file_exists($fileTransaksi) ? json_decode(file_get_contents($fileTransaksi), true) : [];
    $transaksiData[] = [
        'id' => uniqid(),
        'tanggal' => date('Y-m-d'),
        'tipe' => 'pendapatan', // Masuk sebagai pendapatan agar kas bertambah di dashboard
        'kategori' => 'Return Investasi',
        'deskripsi' => "Jual $lotJual Lot $kodeSaham @ " . number_format($hargaJual, 0, ',', '.'),
        'jumlah' => $totalTerima
    ];
    file_put_contents($fileTransaksi, json_encode($transaksiData, JSON_PRETTY_PRINT));

    // Redirect Sukses
    header("Location: index.php?status=sukses_jual");
    exit();
}
?>