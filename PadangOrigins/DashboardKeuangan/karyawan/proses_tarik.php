<?php
session_start();
if (!isset($_SESSION['kry_logged_in'])) { header('Location: login.php'); exit; }

// --- CONFIG PATH ---
$pathData = '../data/';
$fileKaryawan = $pathData . 'karyawan.json';
$filePengeluaran = $pathData . 'pengeluaran.json';
$fileTransaksi = $pathData . 'transaksi.json';

$idSaya = $_SESSION['kry_id'];
$jumlahMinta = (int)$_POST['jumlah'];
$sumber = $_POST['sumber']; // 'gaji' atau 'bonus'

// 1. Cari Nama Saya
$karyawanList = json_decode(file_get_contents($fileKaryawan), true);
$namaSaya = "";
foreach ($karyawanList as $k) {
    if ($k['id'] === $idSaya) { $namaSaya = $k['nama']; break; }
}

// 2. HITUNG ULANG SALDO (VALIDASI)
$pengeluaranList = file_exists($filePengeluaran) ? json_decode(file_get_contents($filePengeluaran), true) : [];
$transaksiList = file_exists($fileTransaksi) ? json_decode(file_get_contents($fileTransaksi), true) : [];

$totalMasuk = 0;
$totalKeluar = 0;

// Hitung Masuk (Dari Admin)
foreach ($pengeluaranList as $p) {
    if (strtolower($p['penerima']) === strtolower($namaSaya)) {
        if (strpos(strtolower($p['kategori']), $sumber) !== false) {
            $totalMasuk += (int)$p['jumlah'];
        }
    }
}

// Hitung Keluar (Dari Saya)
foreach ($transaksiList as $t) {
    if (($t['tipe'] === 'pengeluaran') && (strtolower($t['pelaku']) === strtolower($namaSaya))) {
        if (strpos(strtolower($t['deskripsi']), $sumber) !== false) {
            $totalKeluar += (int)$t['jumlah'];
        }
    }
}

$saldoTersedia = $totalMasuk - $totalKeluar;

// 3. EKSEKUSI
if ($saldoTersedia >= $jumlahMinta) {
    $ket = ($sumber == 'gaji') ? "Gaji" : "Bonus";
    
    $transaksiBaru = [
        "id" => "WD-" . time(),
        "tanggal" => date('Y-m-d H:i:s'),
        "tipe" => "pengeluaran",
        "jumlah" => $jumlahMinta,
        "deskripsi" => "Penarikan Karyawan ($ket): $namaSaya",
        "pelaku" => $namaSaya,
        "akun_sumber" => "kas_laci",
        "akun_tujuan" => "Pribadi"
    ];
    
    $transaksiList[] = $transaksiBaru;
    file_put_contents($fileTransaksi, json_encode($transaksiList, JSON_PRETTY_PRINT));
    
    echo "<script>alert('Berhasil ditarik!'); window.location='index.php';</script>";
} else {
    echo "<script>alert('Saldo tidak cukup! Sisa: Rp ".number_format($saldoTersedia)."'); window.location='index.php';</script>";
}
?>