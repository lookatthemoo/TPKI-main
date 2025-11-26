<?php
session_start();
$filePortfolio = '../data/portfolio.json';
$fileRekening = '../data/rekening.json';
$fileTransaksi = '../data/transaksi.json'; // Menggunakan transaksi.json untuk pencatatan uang masuk

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeSaham = $_POST['kode_saham'];
    $hargaJual = (int)$_POST['harga_jual'];
    $lotJual   = (int)$_POST['jumlah_lot'];

    // Validasi dasar
    if ($lotJual <= 0 || $hargaJual <= 0) {
        header("Location: index.php");
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
            // Karena kita menjual semua lot yang dipilih (logika simplifikasi: jual semua posisi di saham tsb)
            // Jika ingin jual parsial, kurangi lot-nya. Di sini kita asumsikan jual semua lot yang ada di modal.
            // Namun, inputan mengambil total lot yang dimiliki. Jadi saham ini akan hilang dari list.
            $found = true;
            continue; // Jangan masukkan ke array baru (artinya dihapus)
        }
        $newPortfolio[] = $saham;
    }

    if (!$found) {
        die("Error: Saham tidak ditemukan di portofolio.");
    }

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
    // Kita catat sebagai "pendapatan" agar masuk di grafik dashboard utama
    $transaksiData = file_exists($fileTransaksi) ? json_decode(file_get_contents($fileTransaksi), true) : [];
    $transaksiData[] = [
        'id' => uniqid(),
        'tanggal' => date('Y-m-d'),
        'tipe' => 'pendapatan', // Tipe pendapatan agar menambah kas di dashboard utama
        'kategori' => 'Return Investasi',
        'deskripsi' => "Jual $lotJual Lot $kodeSaham @ $hargaJual",
        'jumlah' => $totalTerima
    ];
    file_put_contents($fileTransaksi, json_encode($transaksiData, JSON_PRETTY_PRINT));

    // Redirect Sukses
    header("Location: index.php?status=sukses_jual");
    exit();
}
?>