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
        file_put_contents($file, '[]');
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveJson($file, $data) {
    $success = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($success === false) {
        die("Error: Gagal menyimpan ke file $file. Cek permission folder.");
    }
}

// --- FUNGSI KHUSUS UPDATE SALDO BANK ---
function updateSaldoBank($namaBank, $jumlah, $isPemasukan = true) {
    global $fileRekening;
    $rekeningData = getJson($fileRekening);
    $updated = false;

    foreach ($rekeningData as &$rek) {
        if ($rek['nama_bank'] === $namaBank) {
            if ($isPemasukan) {
                $rek['saldo'] += $jumlah;
            } else {
                $rek['saldo'] -= $jumlah;
            }
            $updated = true;
            break;
        }
    }

    if ($updated) {
        saveJson($fileRekening, $rekeningData);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user = $_SESSION['admin_username'] ?? 'Admin';
    $tanggal = date('Y-m-d H:i:s');
    $idTrx = 'TRX-' . time();

    // ==============================================================
    // 1. AMBIL KAS OPS
    // ==============================================================
    if ($action === 'simpan_ops') {
        $alasan = htmlspecialchars($_POST['alasan']); 
        $jumlah = (int)$_POST['jumlah'];

        // A. Simpan ke Detail Kas Ops
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

        // B. Simpan ke Log Global (Untuk Arus Kas Harian)
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => uniqid('TRX-OPS-'),
            'tanggal' => $tanggal,
            'tipe' => 'pengeluaran',
            'akun_sumber' => 'kas_ops',
            'akun_tujuan' => 'Pengeluaran',
            'jumlah' => $jumlah,
            'deskripsi' => "Kas Ops: $alasan",
            'pelaku' => $user
        ];
        saveJson($fileTrx, $trxData);

        // C. Simpan ke Pusat Pengeluaran
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

    // ==============================================================
    // 2. TRANSFER ANTAR KAS (PERBAIKAN DISINI)
    // ==============================================================
    if ($action === 'transfer_kas') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber']; 
        $tujuan = $_POST['tujuan']; 
        $catatan = $_POST['catatan'];
        
        // A. Catat Log Transaksi (Agar muncul di tabel mutasi)
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => $idTrx, 
            'tanggal' => $tanggal, 
            'tipe' => 'transfer',
            'akun_sumber' => $sumber, 
            'akun_tujuan' => $tujuan,
            'jumlah' => $jumlah, 
            'deskripsi' => "Transfer: $sumber -> $tujuan ($catatan)", 
            'pelaku' => $user
        ];
        saveJson($fileTrx, $trxData);

        // B. UPDATE SALDO REAL DI REKENING.JSON (Jika Bank Terlibat)
        
        // Cek Sumber: Jika sumbernya Bank (Bukan Kas Laci/Ops), kurangi saldonya
        if ($sumber !== 'kas_laci' && $sumber !== 'kas_ops') {
            updateSaldoBank($sumber, $jumlah, false); // false = Pengurangan
        }

        // Cek Tujuan: Jika tujuannya Bank (Bukan Kas Laci/Ops), tambah saldonya
        if ($tujuan !== 'kas_laci' && $tujuan !== 'kas_ops') {
            updateSaldoBank($tujuan, $jumlah, true); // true = Penambahan
        }

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
    
    // --- 5. PRIVE OWNER (TARIK DANA) ---
    if ($action === 'tarik_prive') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber'];
        $catatan = htmlspecialchars($_POST['catatan']);

        // A. Catat Log Transaksi
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => uniqid('PRV-'),
            'tanggal' => $tanggal,
            'tipe' => 'penarikan',
            'akun_sumber' => $sumber,
            'akun_tujuan' => 'Pribadi',
            'jumlah' => $jumlah,
            'deskripsi' => "Prive Owner: $catatan",
            'pelaku' => $user
        ];
        saveJson($fileTrx, $trxData);

        // B. Update Saldo Bank jika sumbernya Bank
        if ($sumber !== 'kas_laci' && $sumber !== 'kas_ops') {
            updateSaldoBank($sumber, $jumlah, false); // Kurangi
        }

        // C. Catat di Pengeluaran Global juga (sebagai Prive)
        $pengeluaranData = getJson($filePengeluaran);
        $pengeluaranData[] = [
            'id' => uniqid('EXP-PRV-'),
            'tanggal' => $tanggal,
            'kategori' => 'Prive Owner',
            'deskripsi' => "Penarikan Owner ($sumber)",
            'penerima' => 'Owner',
            'sumber_dana' => $sumber,
            'jumlah' => $jumlah,
            'admin' => $user
        ];
        saveJson($filePengeluaran, $pengeluaranData);

        header('Location: index.php?status=success_prive'); exit;
    }
}
header('Location: index.php');
?>