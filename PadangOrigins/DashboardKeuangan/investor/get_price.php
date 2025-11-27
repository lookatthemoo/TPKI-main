<?php
// get_price.php - API Mini buat cek harga saham
header('Content-Type: application/json');

if (!isset($_GET['kode'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kode kosong']);
    exit;
}

$kodeSaham = strtoupper(trim($_GET['kode']));
$symbol = $kodeSaham . ".JK"; // Format Yahoo untuk saham Indo

// URL Yahoo Finance
$url = "https://query1.finance.yahoo.com/v8/finance/chart/" . $symbol . "?interval=1d&range=1d";

// Menyamar jadi browser biar gak diblokir
$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36\r\n"
    ]
];
$context = stream_context_create($options);

// Ambil data (pakai @ biar gak muncul warning PHP di output JSON)
$json = @file_get_contents($url, false, $context);

if ($json) {
    $data = json_decode($json, true);
    // Cek apakah data valid
    if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
        $price = (int)$data['chart']['result'][0]['meta']['regularMarketPrice'];
        
        // Cek nama perusahaan (opsional, biar keren)
        $name = $kodeSaham; // Default pakai kode
        // Kadang yahoo gak ngasih nama jelas di endpoint ini, jadi kita pakai kode aja biar cepet
        
        echo json_encode(['status' => 'success', 'price' => $price, 'code' => $kodeSaham]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Saham tidak ditemukan']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal koneksi ke bursa']);
}
?>