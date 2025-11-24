<?php
session_start();
// Cek Sesi Login
if (!isset($_SESSION['kry_id'])) { 
    header('Location: login.php'); 
    exit; 
}

// Set Timezone
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$idKaryawan = $_SESSION['kry_id'];

// Path Database
$fileKaryawan = '../data/karyawan.json';

// Baca Data
$karyawanData = file_exists($fileKaryawan) ? json_decode(file_get_contents($fileKaryawan), true) : [];
$updated = false;

foreach ($karyawanData as &$k) {
    if ($k['id'] === $idKaryawan) {
        
        // VALIDASI GANDA: Cek apakah sudah absen hari ini?
        if (($k['terakhir_absen'] ?? '') === $hariIni) {
            // Jika sudah, jangan update apa-apa, langsung balik
            header('Location: index.php?status=already_absen'); 
            exit;
        }

        // LOGIKA UTAMA: Tambah Kehadiran
        $k['hadir'] = ($k['hadir'] ?? 0) + 1;
        
        // Simpan tanggal hari ini sebagai 'terakhir_absen'
        $k['terakhir_absen'] = $hariIni;
        
        $updated = true;
        break;
    }
}

if ($updated) {
    // Simpan kembali ke JSON
    file_put_contents($fileKaryawan, json_encode($karyawanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    header('Location: index.php?status=success');
} else {
    header('Location: index.php?status=error');
}
?>