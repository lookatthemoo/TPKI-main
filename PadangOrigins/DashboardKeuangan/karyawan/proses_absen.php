<?php
session_start();
if (!isset($_SESSION['kry_logged_in'])) { header('Location: login.php'); exit; }

date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$idKaryawan = $_SESSION['kry_id'];

// PATH BENAR
$fileKaryawan = '../data/karyawan.json';

if (!file_exists($fileKaryawan)) die("Error Database");

$karyawanList = json_decode(file_get_contents($fileKaryawan), true);
$updated = false;

foreach ($karyawanList as &$k) {
    if ($k['id'] === $idKaryawan) {
        if (($k['terakhir_absen'] ?? '') === $hariIni) {
            header('Location: index.php?status=already'); exit;
        }
        $k['hadir'] = ($k['hadir'] ?? 0) + 1;
        $k['terakhir_absen'] = $hariIni;
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents($fileKaryawan, json_encode($karyawanList, JSON_PRETTY_PRINT));
    header('Location: index.php?status=success');
}
?>