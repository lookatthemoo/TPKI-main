<?php
session_start();
if (!isset($_SESSION['kry_logged_in'])) { header('Location: login.php'); exit; }

// Set Timezone
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$idKaryawan = $_SESSION['kry_id'];

// Path Database (Relatif dari folder karyawan)
$fileKaryawan = '../data/karyawan.json';

// Baca Data
$karyawanData = json_decode(file_get_contents($fileKaryawan), true);
$updated = false;

foreach ($karyawanData as &$k) {
    if ($k['id'] === $idKaryawan) {
        
        // Cek lagi (validasi ganda) apakah sudah absen hari ini
        if ($k['terakhir_absen'] === $hariIni) {
            header('Location: index.php'); // Jika sudah, tendang balik aja
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
    
    // Kembali ke dashboard dengan pesan sukses (opsional, pake alert JS simpel aja di index)
    header('Location: index.php?absen=sukses');
} else {
    echo "Terjadi kesalahan sistem.";
}
?>