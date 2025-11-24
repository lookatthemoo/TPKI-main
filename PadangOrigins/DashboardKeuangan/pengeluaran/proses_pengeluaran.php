<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$filePengeluaran = '../data/pengeluaran.json';
$fileRekening = '../data/rekening.json';
$fileTrx = '../data/transaksi.json';
$fileKaryawan = '../data/karyawan.json';

// Helper Load/Save
function getJson($f) { return file_exists($f) ? json_decode(file_get_contents($f), true) : []; }
function saveJson($f, $d) { file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'tambah_pengeluaran') {
    
    $sumber = $_POST['sumber_dana'];
    $kategori = $_POST['kategori'];
    $deskripsi = htmlspecialchars($_POST['deskripsi']);
    $jumlah = (int)$_POST['jumlah'];
    $admin = $_SESSION['admin_username'] ?? 'Admin';
    
    // Handle Karyawan (Jika ada)
    $penerima = "-";
    $idKaryawan = null;
    
    if (!empty($_POST['id_karyawan'])) {
        $parts = explode('|', $_POST['id_karyawan']); // ID|Nama
        $idKaryawan = $parts[0];
        $penerima = $parts[1];
    }

    // 1. UPDATE SALDO REKENING (Jika sumbernya Bank/E-Wallet)
    // Jika 'kas_laci', saldo hanya tercatat berkurang di laporan harian via transaksi.json
    if ($sumber !== 'kas_laci') {
        $rekeningData = getJson($fileRekening);
        $updated = false;
        foreach ($rekeningData as &$rek) {
            if ($rek['nama_bank'] === $sumber) {
                if ($rek['saldo'] < $jumlah) {
                    echo "<script>alert('Saldo $sumber tidak cukup!'); window.history.back();</script>";
                    exit;
                }
                $rek['saldo'] -= $jumlah;
                $updated = true;
                break;
            }
        }
        if ($updated) saveJson($fileRekening, $rekeningData);
    }

    // 2. KHUSUS GAJI/BONUS: Update Data Karyawan
    if ($idKaryawan && ($kategori === 'Gaji Karyawan' || $kategori === 'Bonus')) {
        $karyawanData = getJson($fileKaryawan);
        foreach ($karyawanData as &$k) {
            if ($k['id'] === $idKaryawan) {
                if ($kategori === 'Gaji Karyawan') {
                    $k['terakhir_gaji'] = date('Y-m-d');
                    // Opsional: Tambah ke dompet karyawan jika sistem dompet aktif
                    $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + $jumlah;
                } 
                elseif ($kategori === 'Bonus') {
                    $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + $jumlah;
                }
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);
    }

    // 3. SIMPAN KE PENGELUARAN.JSON (Log Khusus Modul Ini)
    $pengeluaranData = getJson($filePengeluaran);
    $pengeluaranData[] = [
        'id' => uniqid('EXP-'),
        'tanggal' => date('Y-m-d H:i:s'),
        'kategori' => $kategori,
        'deskripsi' => $deskripsi,
        'penerima' => $penerima, // Nama Karyawan tersimpan disini
        'sumber_dana' => $sumber,
        'jumlah' => $jumlah,
        'admin' => $admin
    ];
    saveJson($filePengeluaran, $pengeluaranData);

    // 4. SIMPAN KE TRANSAKSI.JSON (Agar Laporan Keuangan & Grafik Sinkron)
    $trxData = getJson($fileTrx);
    $trxData[] = [
        'id' => uniqid('TRX-EXP-'),
        'tanggal' => date('Y-m-d H:i:s'),
        'tipe' => 'pengeluaran',
        'akun_sumber' => $sumber,
        'akun_tujuan' => $kategori,
        'jumlah' => $jumlah,
        'deskripsi' => "$kategori: $deskripsi " . ($penerima !== '-' ? "($penerima)" : ""),
        'pelaku' => $admin
    ];
    saveJson($fileTrx, $trxData);

    header('Location: index.php?status=success');
    exit;
}
header('Location: index.php');
?>