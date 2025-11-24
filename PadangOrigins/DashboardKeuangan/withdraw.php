<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori = $_POST['kategori'] ?? 'bisnis'; // 'bisnis' atau 'owner'
    $jumlah = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
    $alasan = trim($_POST['reason'] ?? ''); // Bisa jadi 'deskripsi' atau 'alasan_penarikan'
    
    if ($jumlah && $jumlah > 0 && !empty($alasan)) {
        $file = 'data/transaksi.json';
        if (!file_exists($file)) die("Data tidak ditemukan");
        $trxData = json_decode(file_get_contents($file), true) ?? [];
        
        // Hitung Saldo Real
        $saldoReal = 0;
        foreach ($trxData as $t) {
            if ($t['tipe'] === 'pendapatan') $saldoReal += $t['jumlah'];
            elseif ($t['tipe'] === 'pengeluaran' || $t['tipe'] === 'penarikan') $saldoReal -= $t['jumlah'];
        }

        // --- PROSES SESUAI KATEGORI ---
        if ($kategori === 'owner') {
            // ATURAN OWNER: Harus taat batas aman
            $batasOwner = $saldoReal - MINIMUM_SALDO_OPERASIONAL;
            if ($jumlah > $batasOwner) { header('Location: laporan.php?error=melebihi_batas_aman'); exit; }

            $newTrx = [
                'id' => 'WD-' . time(),
                'tanggal' => date('Y-m-d H:i:s'),
                'tipe' => 'penarikan', // Tipe khusus owner
                'jumlah' => $jumlah,
                'deskripsi' => 'Penarikan Owner',
                'alasan_penarikan' => $alasan,
                'penerima_dana' => trim($_POST['penerima_dana'] ?? 'Owner'),
                'pelaku' => $_SESSION['admin_username']
            ];

        } else {
            // ATURAN BISNIS: Bebas selama ada saldo real
            if ($jumlah > $saldoReal) { header('Location: laporan.php?error=saldo_kurang'); exit; }

            $newTrx = [
                'id' => 'EXP-' . time(),
                'tanggal' => date('Y-m-d H:i:s'),
                'tipe' => 'pengeluaran', // Tipe operasional bisnis
                'jumlah' => $jumlah,
                'deskripsi' => $alasan, // Langsung jadi deskripsi utama
                'pelaku' => $_SESSION['admin_username']
            ];
        }

        $trxData[] = $newTrx;
        file_put_contents($file, json_encode($trxData, JSON_PRETTY_PRINT));
        header('Location: laporan.php?status=sukses'); exit;
    }
}
header('Location: laporan.php'); exit;
?>