<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- 1. SETUP DATA ---
$fileTrx = '../data/transaksi.json';
$fileLaporan = '../data/laporan_harian.json';
$fileRekening = '../data/rekening.json'; 

function getJson($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// --- FUNGSI HITUNG SALDO TERKINI ---
function hitungSaldoSaatIni($akunTarget) {
    global $fileTrx, $fileRekening;
    $trxData = getJson($fileTrx);
    $rekeningList = getJson($fileRekening);
    
    $saldo = 0;
    foreach ($rekeningList as $rek) {
        if ($rek['nama_bank'] === $akunTarget) {
            $saldo = (int)$rek['saldo'];
            break;
        }
    }

    foreach ($trxData as $t) {
        $jumlah = (int)$t['jumlah'];
        $tipe = $t['tipe'];
        $sumber = trim($t['akun_sumber'] ?? 'kas_laci');
        $tujuan = trim($t['akun_tujuan'] ?? '');

        if ($tipe === 'pendapatan') {
            $targetMasuk = ($tujuan === '' ? 'kas_laci' : $tujuan);
            if ($targetMasuk === $akunTarget) $saldo += $jumlah;
        }
        elseif ($tipe === 'pengeluaran' || $tipe === 'penarikan') {
            if ($sumber === $akunTarget) $saldo -= $jumlah;
        }
        elseif ($tipe === 'transfer') {
            if ($sumber === $akunTarget) $saldo -= $jumlah;
            if ($tujuan === $akunTarget) $saldo += $jumlah;
        }
    }
    return $saldo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $trxData = getJson($fileTrx);
    
    $user = $_SESSION['admin_username'] ?? 'Admin';
    $tanggal = date('Y-m-d H:i:s');
    $idTrx = 'TRX-' . time();

    // --- 2. TRANSFER ANTAR KAS ---
    if ($action === 'transfer_kas') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber']; 
        $tujuan = $_POST['tujuan'];
        $catatan = $_POST['catatan'];

        $saldoSumber = hitungSaldoSaatIni($sumber);
        
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
        header('Location: index.php?status=success_transfer'); exit;
    }

    // --- 3. CATAT PENGELUARAN SPESIFIK ---
    if ($action === 'catat_pengeluaran') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber']; 
        $deskripsi = $_POST['deskripsi'];

        $trxData[] = [
            'id' => $idTrx,
            'tanggal' => $tanggal,
            'tipe' => 'pengeluaran',
            'akun_sumber' => $sumber,
            'jumlah' => $jumlah,
            'deskripsi' => $deskripsi,
            'pelaku' => $user
        ];

        saveJson($fileTrx, $trxData);
        header('Location: index.php?status=success_expense'); exit;
    }

    // --- 4. PENARIKAN OWNER (PRIVE) ---
    if ($action === 'tarik_prive') {
        $jumlah = (int)$_POST['jumlah'];
        $sumber = $_POST['sumber'];
        $catatan = $_POST['catatan'];

        $trxData[] = [
            'id' => 'PRIVE-' . time(),
            'tanggal' => $tanggal,
            'tipe' => 'penarikan',
            'akun_sumber' => $sumber,
            'jumlah' => $jumlah,
            'deskripsi' => "Prive Owner: $catatan",
            'alasan_penarikan' => $catatan,
            'penerima_dana' => 'Owner',
            'pelaku' => $user
        ];

        saveJson($fileTrx, $trxData);
        header('Location: index.php?status=success_prive'); exit;
    }

    // --- 5. TUTUP BUKU HARIAN (FIXED: SALDO BANK AMAN) ---
    if ($action === 'tutup_buku') {
        $laporanData = getJson($fileLaporan);
        
        $rincianBank = isset($_POST['rincian_bank']) ? json_decode($_POST['rincian_bank'], true) : [];

        // A. Simpan Data Terakhir ke Riwayat (Snapshot)
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
        foreach($laporanData as &$lap) {
            if($lap['tanggal'] === date('Y-m-d')) {
                $lap = $snapshot; 
                $found = true; break;
            }
        }
        if(!$found) $laporanData[] = $snapshot;
        saveJson($fileLaporan, $laporanData); // Simpan Laporan

        // B. RESET SEMUA TRANSAKSI JADI KOSONG (Agar mutasi hari esok mulai dari 0)
        saveJson($fileTrx, []); 

        // [FIX] Bagian Reset Saldo Bank DIHAPUS TOTAL.
        // Saldo di rekening.json tidak akan diganggu gugat.

        header('Location: index.php?status=success_close'); exit;
    }
}
header('Location: index.php');
?>