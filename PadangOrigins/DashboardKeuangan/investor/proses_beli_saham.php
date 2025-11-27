<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // Wajib set jam WIB

require_once '../auth_check.php';

// --- CONFIG ---
$fileRekening = '../data/rekening.json';
$filePengeluaran = '../data/pengeluaran.json';
$filePortfolio = '../data/portfolio.json';

// --- FUNGSI CEK HARGA REALTIME (YAHOO FINANCE) ---
function getRealMarketPrice($kodeSaham) {
    // Format simbol untuk Yahoo Finance (Saham Indo pakai akhiran .JK)
    // Contoh: BBCA -> BBCA.JK
    $symbol = strtoupper($kodeSaham) . ".JK";
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . $symbol . "?interval=1d&range=1d";
    
    // Menyamar sebagai Browser agar tidak diblokir
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // Ambil data
    $json = @file_get_contents($url, false, $context);
    
    if ($json) {
        $data = json_decode($json, true);
        // Ambil harga 'regularMarketPrice' dari JSON response
        if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
            return (int)$data['chart']['result'][0]['meta']['regularMarketPrice'];
        }
    }
    
    return false; // Gagal ambil data (Internet mati / Kode salah)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeSaham = strtoupper($_POST['kode_saham']);
    $lot = (int)$_POST['jumlah_lot'];
    
    // 1. AMBIL HARGA ASLI DARI MARKET
    $hargaMarket = getRealMarketPrice($kodeSaham);
    
    // Fallback: Jika gagal ambil harga online (misal offline), pakai harga inputan user (jika ada)
    // Tapi prioritas utama adalah $hargaMarket
    if ($hargaMarket === false) {
        // Opsi: Bisa batalkan transaksi atau pakai input manual
        // Disini kita pakai input manual sebagai cadangan darurat
        if(isset($_POST['harga_saat_ini']) && $_POST['harga_saat_ini'] > 0) {
            $hargaBeli = (int)$_POST['harga_saat_ini'];
        } else {
            die("Error: Gagal mengambil harga pasar real-time. Periksa koneksi internet.");
        }
    } else {
        $hargaBeli = $hargaMarket;
    }

    // 1 Lot = 100 Lembar
    $totalLembar = $lot * 100;
    $totalBayar = $hargaBeli * $totalLembar;

    // 2. CEK SALDO MANDIRI
    $rekeningData = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
    $mandiriIndex = -1;
    
    foreach ($rekeningData as $key => $rek) {
        if (stripos($rek['nama_bank'], 'Mandiri') !== false) {
            $mandiriIndex = $key;
            break;
        }
    }

    if ($mandiriIndex === -1) {
        die("Error: Rekening Mandiri tidak ditemukan!");
    }

    // Validasi Saldo
    if ($rekeningData[$mandiriIndex]['saldo'] < $totalBayar) {
        header("Location: index.php?status=gagal_saldo");
        exit();
    }

    // 3. POTONG SALDO
    $rekeningData[$mandiriIndex]['saldo'] -= $totalBayar;
    file_put_contents($fileRekening, json_encode($rekeningData, JSON_PRETTY_PRINT));

    // 4. CATAT PENGELUARAN
    $pengeluaranData = file_exists($filePengeluaran) ? json_decode(file_get_contents($filePengeluaran), true) : [];
    $namaAdmin = $_SESSION['admin_username'] ?? 'Investor'; 

    $pengeluaranData[] = [
        'id' => uniqid('TRX-SAHAM-'),
        'tanggal' => date('Y-m-d H:i:s'),
        'kategori' => 'Investasi Saham',
        'deskripsi' => "Buy $lot Lot $kodeSaham @ " . number_format($hargaBeli, 0, ',', '.'),
        'penerima' => 'Bursa Efek',
        'sumber_dana' => 'Mandiri',
        'jumlah' => $totalBayar,
        'admin' => $namaAdmin
    ];
    file_put_contents($filePengeluaran, json_encode($pengeluaranData, JSON_PRETTY_PRINT));

    // 5. UPDATE PORTOFOLIO (Average Down Logic)
    $portfolioData = file_exists($filePortfolio) ? json_decode(file_get_contents($filePortfolio), true) : [];
    $found = false;
    
    foreach ($portfolioData as &$saham) {
        if ($saham['kode'] === $kodeSaham) {
            $totalInvestasiLama = $saham['avg_price'] * ($saham['lot'] * 100);
            $totalInvestasiBaru = $totalInvestasiLama + $totalBayar;
            $totalLotBaru = $saham['lot'] + $lot;
            
            $saham['lot'] = $totalLotBaru;
            $saham['avg_price'] = round($totalInvestasiBaru / ($totalLotBaru * 100));
            $found = true;
            break;
        }
    }

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