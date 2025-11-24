<?php
session_start();
if (!isset($_SESSION['kry_logged_in'])) { header('Location: login.php'); exit; }

$idKaryawan = $_SESSION['kry_id'];
$jumlah = (int)$_POST['jumlah'];
$sumber = $_POST['sumber']; // 'gaji' atau 'bonus'

$pathKaryawan = '../data/karyawan.json';
$pathTransaksi = '../data/transaksi.json';

$karyawanList = json_decode(file_get_contents($pathKaryawan), true);
$transaksiList = file_exists($pathTransaksi) ? json_decode(file_get_contents($pathTransaksi), true) : [];

$namaKaryawan = "";
$berhasil = false;
$pesanError = "";

foreach ($karyawanList as &$k) {
    if ($k['id'] === $idKaryawan) {
        
        if ($sumber === 'gaji') {
            $saldoSaatIni = $k['saldo_gaji'] ?? 0;
            if ($saldoSaatIni < $jumlah) {
                $pesanError = "Saldo Gaji tidak cukup! Sisa: Rp " . number_format($saldoSaatIni,0,',','.');
            } else {
                $k['saldo_gaji'] -= $jumlah;
                $berhasil = true;
                $ketSumber = "Gaji Bulanan";
            }
        } 
        elseif ($sumber === 'bonus') {
            $saldoSaatIni = $k['saldo_bonus'] ?? 0;
            if ($saldoSaatIni < $jumlah) {
                $pesanError = "Saldo Bonus tidak cukup! Sisa: Rp " . number_format($saldoSaatIni,0,',','.');
            } else {
                $k['saldo_bonus'] -= $jumlah;
                $berhasil = true;
                $ketSumber = "Bonus Harian";
            }
        }

        $namaKaryawan = $k['nama'];
        break;
    }
}

if ($berhasil) {
    // Catat Pengeluaran di Admin
    $transaksiBaru = [
        "id" => "WD-" . time(),
        "tanggal" => date('Y-m-d H:i:s'),
        "tipe" => "pengeluaran",
        "jumlah" => $jumlah,
        "deskripsi" => "Penarikan Karyawan ($ketSumber): $namaKaryawan",
        "pelaku" => $namaKaryawan
    ];
    $transaksiList[] = $transaksiBaru;

    file_put_contents($pathKaryawan, json_encode($karyawanList, JSON_PRETTY_PRINT));
    file_put_contents($pathTransaksi, json_encode($transaksiList, JSON_PRETTY_PRINT));

    echo "<script>alert('Berhasil ditarik dari $ketSumber! Silakan ambil uang di kasir.'); window.location='index.php';</script>";
} else {
    echo "<script>alert('GAGAL: $pesanError'); window.location='index.php';</script>";
}
?>