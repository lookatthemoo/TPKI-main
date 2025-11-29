<?php
// File: get_price.php (REVISI V2)
header('Content-Type: application/json');
error_reporting(0); // Matikan error PHP agar JSON bersih

$kode = isset($_GET['kode']) ? strtoupper($_GET['kode']) : '';

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode kosong']);
    exit;
}

// Tambahkan .JK jika belum ada
if (strpos($kode, '.JK') === false) {
    $symbol = $kode . ".JK"; 
} else {
    $symbol = $kode;
}

// URL Yahoo Finance Chart API
$url = "https://query1.finance.yahoo.com/v8/finance/chart/" . $symbol . "?interval=1d&range=1d";

// --- TEKNIK PENYAMARAN (SPOOFING USER AGENT) ---
// Ini kuncinya agar tidak diblokir (403 Forbidden) oleh Yahoo
$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n" .
                    "Accept: application/json\r\n"
    ]
];

$context = stream_context_create($options);
$json = file_get_contents($url, false, $context);

// --- CEK HASIL ---
if ($json === FALSE) {
    // JANGAN PAKAI ANGKA RANDOM LAGI
    // Lebih baik error daripada memberi harapan palsu
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal koneksi ke Yahoo Finance (Cek internet)',
        'price' => 0 // Set 0 agar P/L terlihat minus/invalid, bukan untung besar
    ]);
    exit;
}

$data = json_decode($json, true);

// Validasi struktur JSON dari Yahoo
if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
    $price = $data['chart']['result'][0]['meta']['regularMarketPrice'];
    
    // Kadang Yahoo mengembalikan null saat market tutup/error
    if ($price == null) {
        // Coba ambil 'chartPreviousClose' (harga penutupan kemarin) sebagai cadangan
        $price = $data['chart']['result'][0]['meta']['chartPreviousClose'];
    }

    echo json_encode([
        'status' => 'success',
        'kode' => $kode,
        'price' => $price
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Saham tidak ditemukan']);
}
?>