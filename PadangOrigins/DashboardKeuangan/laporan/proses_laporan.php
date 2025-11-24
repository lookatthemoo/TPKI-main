<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- DEFINISI PATH MUTLAK (ANTI-GAGAL) ---
$baseDir = dirname(__DIR__); // Mundur satu folder ke 'DashboardKeuangan'
$dataDir = $baseDir . '/data/';

// Pastikan folder data ada
if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }

$fileTrx        = $dataDir . 'transaksi.json';
$fileLaporan    = $dataDir . 'laporan_harian.json';
$fileRekening   = $dataDir . 'rekening.json'; 
$fileKasOps     = $dataDir . 'kas_ops.json'; 
$filePengeluaran= $dataDir . 'pengeluaran.json';
$configFile     = $dataDir . 'laporan_config.json';

// --- HELPER FUNCTION ---
function getJson($file) {
    if (!file_exists($file)) {
        // Jika file tidak ada, buat file baru dengan isi array kosong
        file_put_contents($file, '[]');
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : []; // Pastikan selalu return array
}

function saveJson($file, $data) {
    // Menggunakan LOCK_EX agar tidak bentrok saat disimpan
    $success = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($success === false) {
        die("Error: Gagal menyimpan ke file $file. Cek permission folder.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user = $_SESSION['admin_username'] ?? 'Admin';
    $tanggal = date('Y-m-d H:i:s');
    $idTrx = 'TRX-' . time();

    // ==============================================================
    // 1. AMBIL KAS OPS (LOGIKA UTAMA)
    // ==============================================================
    if ($action === 'simpan_ops') {
        $alasan = htmlspecialchars($_POST['alasan']); 
        $jumlah = (int)$_POST['jumlah'];

        // A. Simpan ke Detail Kas Ops (kas_ops.json)
        $opsData = getJson($fileKasOps);
        $opsData[] = [
            'id' => uniqid('OPS-'),
            'tanggal' => $tanggal,
            'alasan_pengeluaran' => $alasan,
            'jumlah' => $jumlah,
            'sumber' => 'Kas Operasional',
            'petugas' => $user
        ];
        saveJson($fileKasOps, $opsData);

        // B. Simpan ke Log Global (transaksi.json) -> INI YANG MEMOTONG SALDO
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => uniqid('TRX-OPS-'),
            'tanggal' => $tanggal,
            'tipe' => 'pengeluaran',
            'akun_sumber' => 'kas_ops', // Wajib 'kas_ops' agar saldo berkurang
            'akun_tujuan' => 'Pengeluaran',
            'jumlah' => $jumlah,
            'deskripsi' => "Kas Ops: $alasan",
            'pelaku' => $user
        ];
        saveJson($fileTrx, $trxData);

        // C. Simpan ke Pusat Pengeluaran (pengeluaran.json) -> AGAR MUNCUL DI TABEL PENGELUARAN
        $pengeluaranData = getJson($filePengeluaran);
        $pengeluaranData[] = [
            'id' => uniqid('EXP-OPS-'),
            'tanggal' => $tanggal,
            'kategori' => 'Operasional',
            'deskripsi' => $alasan,
            'penerima' => '-',
            'sumber_dana' => 'Kas Operasional',
            'jumlah' => $jumlah,
            'admin' => $user
        ];
        saveJson($filePengeluaran, $pengeluaranData);

        header("Location: index.php?status=success_ops");
        exit;
    }

    // --- 2. TRANSFER ANTAR KAS ---
    if ($action === 'transfer_kas') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber']; $tujuan = $_POST['tujuan']; $catatan = $_POST['catatan'];
        
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => $idTrx, 'tanggal' => $tanggal, 'tipe' => 'transfer',
            'akun_sumber' => $sumber, 'akun_tujuan' => $tujuan,
            'jumlah' => $jumlah, 'deskripsi' => "Transfer: $sumber -> $tujuan ($catatan)", 'pelaku' => $user
        ];
        saveJson($fileTrx, $trxData);
        header('Location: index.php?status=success_transfer'); exit;
    }

    // --- 3. WIDGET LAPORAN ---
    if ($action === 'tambah_widget') {
        $configData = getJson($configFile);
        $configData[] = ['name' => htmlspecialchars($_POST['nama_laporan']), 'source' => $_POST['sumber_json'], 'created_at' => $tanggal];
        saveJson($configFile, $configData);
        header("Location: index.php"); exit;
    }
    if ($action === 'hapus_widget') {
        $configData = getJson($configFile);
        if (isset($configData[(int)$_POST['index']])) {
            array_splice($configData, (int)$_POST['index'], 1);
            saveJson($configFile, $configData);
        }
        header("Location: index.php"); exit;
    }

    // --- 4. TUTUP BUKU ---
    if ($action === 'tutup_buku') {
        $laporanData = getJson($fileLaporan);
        $rincianBank = isset($_POST['rincian_bank']) ? json_decode($_POST['rincian_bank'], true) : [];

        $snapshot = [
            'tanggal' => date('Y-m-d'),
            'waktu_tutup' => date('H:i:s'),
            'saldo_laci' => (int)$_POST['saldo_laci'],
            'saldo_ops' => (int)$_POST['saldo_ops'],
            'saldo_bank' => (int)$_POST['saldo_bank'], 
            'rincian_bank' => $rincianBank,           
            'total_aset' => (int)$_POST['total_aset'],
            'petugas' => $user
        ];
        
        $found = false;
        foreach($laporanData as &$lap) { if($lap['tanggal'] === date('Y-m-d')) { $lap = $snapshot; $found = true; break; } }
        if(!$found) $laporanData[] = $snapshot;
        
        saveJson($fileLaporan, $laporanData);
        saveJson($fileTrx, []); // Reset Transaksi Harian
        header('Location: index.php?status=success_close'); exit;
    }
}
header('Location: index.php');
?>