<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// CEK LOGIN
if (!isset($_SESSION['kry_id'])) { header('Location: login.php'); exit; }

// LOAD FILES
$fileKaryawan = '../data/karyawan.json';
$fileRekening = '../data/rekening.json';
$fileTrx      = '../data/transaksi.json';

// Helper Function
function getJson($f) { return file_exists($f) ? json_decode(file_get_contents($f), true) : []; }
function saveJson($f, $d) { file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $amount = (int)$_POST['amount'];

    // 1. Validasi Saldo Karyawan
    $karyawanData = getJson($fileKaryawan);
    $saldoKaryawan = 0;
    $kryIndex = -1;

    foreach ($karyawanData as $i => $k) {
        if ($k['id'] === $id) {
            $saldoKaryawan = $k['bonus_pending'] ?? 0;
            $kryIndex = $i;
            break;
        }
    }

    if ($kryIndex === -1 || $amount > $saldoKaryawan || $amount <= 0) {
        header("Location: index.php?status=error"); // Saldo karyawan tidak cukup
        exit;
    }

    // 2. Validasi & Potong Saldo BCA Admin
    $rekeningData = getJson($fileRekening);
    $bcaIndex = -1;
    
    foreach ($rekeningData as $i => $rek) {
        // Cari akun yang mengandung kata BCA
        if (stripos($rek['nama_bank'], 'BCA') !== false) {
            $bcaIndex = $i; break;
        }
    }

    if ($bcaIndex === -1 || $rekeningData[$bcaIndex]['saldo'] < $amount) {
        header("Location: index.php?status=wd_error_saldo"); // Saldo BCA Perusahaan kurang
        exit;
    }

    // --- EKSEKUSI TRANSAKSI ---

    // A. Kurangi Saldo BCA Perusahaan
    $rekeningData[$bcaIndex]['saldo'] -= $amount;
    saveJson($fileRekening, $rekeningData);

    // B. Kurangi Saldo Karyawan
    $karyawanData[$kryIndex]['bonus_pending'] -= $amount;
    
    // (Opsional) Catat riwayat penarikan di data karyawan jika perlu
    // $karyawanData[$kryIndex]['saldo_terambil'] = ...
    
    saveJson($fileKaryawan, $karyawanData);

    // C. Catat di Laporan Transaksi
    $trxData = getJson($fileTrx);
    $trxData[] = [
        'id' => uniqid('WD-'),
        'tanggal' => date('Y-m-d H:i:s'),
        'tipe' => 'pengeluaran',
        'akun_sumber' => 'BCA', // Sumber dana selalu BCA
        'akun_tujuan' => 'Karyawan: ' . $karyawanData[$kryIndex]['nama'],
        'jumlah' => $amount,
        'deskripsi' => "Withdraw Saldo Karyawan",
        'pelaku' => $karyawanData[$kryIndex]['nama']
    ];
    saveJson($fileTrx, $trxData);

    header("Location: index.php?status=wd_success");
    exit;
}
header("Location: index.php");
?>