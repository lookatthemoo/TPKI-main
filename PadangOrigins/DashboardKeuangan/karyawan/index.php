<?php
require_once '../auth_check.php';

// --- 1. LOAD DATA ---
$fileTrx = '../data/transaksi.json';
$fileLaporan = '../data/laporan_harian.json';
$fileRekening = '../data/rekening.json'; 
$configFile = '../data/laporan_config.json';

$trxData = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];
$historiLaporan = file_exists($fileLaporan) ? json_decode(file_get_contents($fileLaporan), true) : [];
$rekeningList = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
$myReports = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Scan JSON Files untuk Widget
$jsonFiles = [];
$dataDir = '../data/';
if (is_dir($dataDir)) {
    foreach (scandir($dataDir) as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'json' && !in_array($f, ['laporan_config.json', 'user_config.json'])) {
            $jsonFiles[] = $f;
        }
    }
}

// --- 2. HITUNG SALDO (LOGIKA UTAMA) ---
$kasLaci = 0;
$kasOps = 0;
$hariIni = date('Y-m-d');

// Cek Tutup Buku
$sudahTutupBuku = false;
foreach ($historiLaporan as $lap) {
    if ($lap['tanggal'] === $hariIni) { $sudahTutupBuku = true; break; }
}

$saldoPerBank = [];
foreach ($rekeningList as $rek) { $saldoPerBank[$rek['nama_bank']] = (int)$rek['saldo']; }

foreach ($trxData as $t) {
    $jumlah = (int)$t['jumlah'];
    $tipe = $t['tipe'];
    $akunSumber = $t['akun_sumber'] ?? 'kas_laci'; 
    $akunTujuan = $t['akun_tujuan'] ?? 'kas_laci'; 

    if ($tipe === 'pendapatan') {
        if ($akunTujuan === 'kas_laci') $kasLaci += $jumlah;
    } 
    elseif ($tipe === 'pengeluaran' || $tipe === 'penarikan') {
        if ($akunSumber === 'kas_laci') $kasLaci -= $jumlah;
        elseif ($akunSumber === 'kas_ops') $kasOps -= $jumlah; // INI DIA YANG KURANGIN SALDO OPS
        elseif (isset($saldoPerBank[$akunSumber])) $saldoPerBank[$akunSumber] -= $jumlah;
    }
    elseif ($tipe === 'transfer') {
        if ($akunSumber === 'kas_laci') $kasLaci -= $jumlah;
        elseif ($akunSumber === 'kas_ops') $kasOps -= $jumlah;
        elseif (isset($saldoPerBank[$akunSumber])) $saldoPerBank[$akunSumber] -= $jumlah;

        if ($akunTujuan === 'kas_laci') $kasLaci += $jumlah;
        elseif ($akunTujuan === 'kas_ops') $kasOps += $jumlah;
        elseif (isset($saldoPerBank[$akunTujuan])) $saldoPerBank[$akunTujuan] += $jumlah;
    }
}

$totalSaldoBank = array_sum($saldoPerBank);
$totalAset = $kasLaci + $kasOps + $totalSaldoBank;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Style Dasar */
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .header-summary { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 1.2rem 2rem; border-radius: 12px; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2); }
        .header-title h2 { font-size: 0.9rem; margin-bottom: 2px; opacity: 0.9; } .header-amount { font-size: 1.8rem; font-weight: 700; }
        .btn-tutup-buku { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-disabled-tutup { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: default; }

        /* Grid */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .balance-card { background: white; padding: 1.2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; height: 100%; }
        .balance-card h4 { color: #7f8c8d; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .balance-value { font-size: 1.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 12px; }
        
        .card-laci { border-top: 4px solid #2ecc71; } .card-ops { border-top: 4px solid #f39c12; } .card-bank { border-top: 4px solid #3498db; }
        .action-row { display: flex; gap: 8px; margin-top: auto; }
        .btn-mini { flex: 1; padding: 8px; font-size: 0.8rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; color: white; }
        .bg-purple { background: #9b59b6; } .bg-blue { background: #3498db; } .bg-red { background: #ef4444; } .bg-dark { background: #34495e; }

        /* Widgets */
        .widgets-container { margin-top: 2rem; display: flex; flex-direction: column; gap: 2rem; }
        .report-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .report-header { display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th { background: #f8f9fa; padding: 10px; text-align: left; } .data-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .btn-add { background: #2c3e50; color: white; padding: 8px 15px; border-radius: 8px; border: none; cursor: pointer; }
        .btn-delete { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; }
        .modal-box { background: white; padding: 2rem; border-radius: 16px; width: 90%; max-width: 450px; animation: zoomIn 0.2s; }
        @keyframes zoomIn { from{transform:scale(0.9);opacity:0;} to{transform:scale(1);opacity:1;} }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 1rem; }
        
        /* Table Mutasi */
        .table-custom { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 10px; }
        .table-custom th { background: #2c3e50; color: white; padding: 10px; text-align: left; }
        .table-custom td { padding: 10px; border-bottom: 1px solid #eee; }
        .badge-tipe { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; }
        .tipe-masuk { background: #dcfce7; color: #166534; } .tipe-keluar { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="container">
        <h1 class="logo">💰 Keuangan & Kas</h1>
        <nav><a href="../index.php" class="nav-link btn-logout">Kembali</a></nav>
    </div>
</header>

<main class="container">

    <div class="header-summary">
        <div class="header-title"><h2>Total Aset Likuid</h2><div class="header-amount">Rp <?php echo number_format($totalAset, 0, ',', '.'); ?></div></div>
        <?php if ($sudahTutupBuku): ?>
            <button class="btn-disabled-tutup">✅ Sudah Tutup Buku</button>
        <?php else: ?>
            <button onclick="document.getElementById('modalTutup').style.display='flex'" class="btn-tutup-buku">📕 Tutup Buku Hari Ini</button>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <div class="balance-card card-laci">
            <div><h4>🔥 Kas Laci</h4><div class="balance-value">Rp <?php echo number_format(max(0, $kasLaci)); ?></div></div>
            <div class="action-row">
                <button onclick="openModal('transfer','kas_laci')" class="btn-mini bg-purple">Transfer</button>
                <button onclick="openModal('transfer','kas_laci','kas_laci')" class="btn-mini bg-blue">Setor Bank</button>
            </div>
        </div>
        
        <div class="balance-card card-ops">
            <div><h4>⚡ Kas Operasional</h4><div class="balance-value">Rp <?php echo number_format(max(0, $kasOps)); ?></div></div>
            <div class="action-row">
                <button onclick="document.getElementById('modalKasOps').style.display='flex'" class="btn-mini bg-red">Ambil Kas Ops</button>
                <button onclick="openModal('transfer','kas_ops')" class="btn-mini bg-purple">Topup</button>
            </div>
        </div>

        <div class="balance-card card-bank">
            <div><h4>🏦 Rekening & E-Wallet</h4><div class="balance-value">Rp <?php echo number_format(max(0, $totalSaldoBank)); ?></div></div>
            <div class="bank-list">
                <?php foreach ($rekeningList as $rek) echo "<div style='font-size:0.8rem; display:flex; justify-content:space-between; padding:2px 0; border-bottom:1px dashed #eee;'><span>{$rek['nama_bank']}</span><b>".number_format($rek['saldo'])."</b></div>"; ?>
            </div>
            <div class="action-row">
                <button onclick="document.getElementById('modalPrive').style.display='flex'" class="btn-mini bg-dark">Prive</button>
                <button onclick="openModal('transfer','bank')" class="btn-mini bg-purple">Transfer</button>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem;">
        <h3 style="margin:0;">📜 Laporan Kustom</h3>
        <button onclick="document.getElementById('modalAddReport').style.display='flex'" class="btn-add">+ Widget</button>
    </div>

    <div class="widgets-container">
        <?php if (empty($myReports)): ?>
            <div style="text-align:center; padding:2rem; border:2px dashed #eee; border-radius:10px; color:#999;">Belum ada widget laporan.</div>
        <?php else: ?>
            <?php foreach ($myReports as $idx => $rep): 
                $data = file_exists('../data/'.$rep['source']) ? json_decode(file_get_contents('../data/'.$rep['source']), true) : []; ?>
                <div class="report-card">
                    <div class="report-header">
                        <div><b><?= htmlspecialchars($rep['name']) ?></b> <small style="color:#0369a1; background:#e0f2fe; padding:2px 5px; border-radius:4px;"><?= $rep['source'] ?></small></div>
                        <form method="POST" action="proses_laporan.php" onsubmit="return confirm('Hapus widget?')">
                            <input type="hidden" name="action" value="hapus_widget"><input type="hidden" name="index" value="<?= $idx ?>">
                            <button class="btn-delete">🗑</button>
                        </form>
                    </div>
                    <div style="overflow-x:auto; max-height:300px;">
                        <table class="data-table">
                            <thead><tr><?php if($data && is_array($data)){ foreach(array_keys(end($data)) as $k) echo "<th>".strtoupper(str_replace('_',' ',$k))."</th>"; } else echo "<th>DATA</th>"; ?></tr></thead>
                            <tbody><?php if($data && is_array($data)){ foreach(array_reverse($data) as $r){ echo "<tr>"; foreach($r as $c) echo "<td>".(is_numeric($c) && $c>1000?number_format($c):htmlspecialchars($c))."</td>"; echo "</tr>"; } } else echo "<tr><td style='text-align:center; color:#999;'>Kosong</td></tr>"; ?></tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top:2rem;"><h3>📜 Mutasi Hari Ini</h3></div>
    <div class="table-wrapper">
        <table class="table-custom">
            <thead><tr><th>Jam</th><th>Tipe</th><th>Arus</th><th>Deskripsi</th><th>Nominal</th><th>Admin</th></tr></thead>
            <tbody>
                <?php foreach (array_reverse($trxData) as $t) { if(substr($t['tanggal'],0,10)!==$hariIni) continue;
                $cls = $t['tipe']=='pendapatan'?'tipe-masuk':($t['tipe']=='transfer'?'tipe-transfer':'tipe-keluar'); ?>
                <tr>
                    <td><?= substr($t['tanggal'], 11, 5) ?></td>
                    <td><span class="badge-tipe <?= $cls ?>"><?= strtoupper($t['tipe']) ?></span></td>
                    <td><?= $t['akun_sumber'] ?> ➔ <?= $t['akun_tujuan']??'-' ?></td>
                    <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                    <td><b>Rp <?= number_format($t['jumlah']) ?></b></td>
                    <td><?= $t['pelaku'] ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</main>

<div id="modalKasOps" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
            <h2 style="margin:0; color:#ef4444;">⚡ Ambil Kas Operasional</h2>
            <span onclick="document.getElementById('modalKasOps').style.display='none'" style="cursor:pointer; font-size:1.5rem;">×</span>
        </div>
        <form method="POST" action="proses_laporan.php">
            <input type="hidden" name="action" value="simpan_ops">
            <label style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Alasan Pengeluaran</label>
            <input type="text" name="alasan" class="form-control" required placeholder="Beli Bensin, Plastik, dll">
            <label style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Jumlah (Rp)</label>
            <input type="number" name="jumlah" class="form-control" required min="1">
            <button type="submit" style="width:100%; background:#ef4444; color:white; padding:10px; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Simpan</button>
        </form>
    </div>
</div>

<div id="modalAddReport" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;"><h2>Tambah Laporan</h2><span onclick="document.getElementById('modalAddReport').style.display='none'" style="cursor:pointer;">×</span></div>
        <form method="POST" action="proses_laporan.php">
            <input type="hidden" name="action" value="tambah_widget">
            <label>Judul</label><input type="text" name="nama_laporan" class="form-control" required>
            <label>Pilih JSON</label><select name="sumber_json" class="form-control"><?php foreach ($jsonFiles as $f) echo "<option value='$f'>$f</option>"; ?></select>
            <button type="submit" style="width:100%; background:#2c3e50; color:white; padding:10px; border:none; border-radius:8px;">Tampilkan</button>
        </form>
    </div>
</div>

<div id="modalTransfer" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;"><h2>Transfer Dana</h2><span onclick="document.getElementById('modalTransfer').style.display='none'" style="cursor:pointer;">×</span></div>
        <form method="POST" action="proses_laporan.php">
            <input type="hidden" name="action" value="transfer_kas">
            <label>Dari</label>
            <select name="sumber" id="tf_sumber" class="form-control">
                <option value="kas_laci">Kas Laci</option><option value="kas_ops">Kas Ops</option>
                <optgroup label="Bank"><?php foreach($rekeningList as $r) echo "<option value='{$r['nama_bank']}'>{$r['nama_bank']}</option>"; ?></optgroup>
            </select>
            <label>Ke</label>
            <select name="tujuan" class="form-control">
                <option value="kas_ops">Kas Ops</option><option value="kas_laci">Kas Laci</option>
                <optgroup label="Bank"><?php foreach($rekeningList as $r) echo "<option value='{$r['nama_bank']}'>{$r['nama_bank']}</option>"; ?></optgroup>
            </select>
            <label>Jumlah</label><input type="number" name="jumlah" class="form-control" required>
            <label>Catatan</label><input type="text" name="catatan" class="form-control" required>
            <button type="submit" style="width:100%; background:#9b59b6; color:white; padding:10px; border:none; border-radius:8px;">Transfer</button>
        </form>
    </div>
</div>

<div id="modalTutup" class="modal-overlay">
    <div class="modal-box">
        <h2>Konfirmasi Tutup Buku</h2>
        <p>Saldo akan disimpan di riwayat. Transaksi harian akan di-reset.</p>
        <form method="POST" action="proses_laporan.php">
            <input type="hidden" name="action" value="tutup_buku">
            <input type="hidden" name="saldo_laci" value="<?= $kasLaci ?>">
            <input type="hidden" name="saldo_ops" value="<?= $kasOps ?>">
            <input type="hidden" name="saldo_bank" value="<?= $totalSaldoBank ?>">
            <input type="hidden" name="rincian_bank" value='<?= json_encode($saldoPerBank) ?>'>
            <input type="hidden" name="total_aset" value="<?= $totalAset ?>">
            <button type="submit" style="width:100%; background:#27ae60; color:white; padding:10px; border:none; border-radius:8px; font-weight:bold;">Ya, Tutup Buku</button>
        </form>
    </div>
</div>

<script>
    function openModal(type, src, dest) {
        if(type=='transfer') {
            document.getElementById('modalTransfer').style.display='flex';
            if(src) document.getElementById('tf_sumber').value=src;
        }
    }
    window.onclick = function(e) { if(e.target.classList.contains('modal-overlay')) e.target.style.display='none'; }
</script>

</body>
</html>