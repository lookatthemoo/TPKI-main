<?php
require_once '../auth_check.php';

// --- 1. LOAD DATA ---
$fileTrx = '../data/transaksi.json';
$fileLaporan = '../data/laporan_harian.json';
$fileRekening = '../data/rekening.json'; 

$trxData = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];
$historiLaporan = file_exists($fileLaporan) ? json_decode(file_get_contents($fileLaporan), true) : [];
$rekeningList = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];

// --- 2. HITUNG SALDO PER KAS & BANK ---
$kasLaci = 0;
$kasOps = 0;
$omzetHariIni = 0; // Variabel ada tapi dibiarkan 0 (Dummy)
$pengeluaranHariIni = 0;
$hariIni = date('Y-m-d');

// Cek apakah hari ini sudah tutup buku?
$sudahTutupBuku = false;
foreach ($historiLaporan as $lap) {
    if ($lap['tanggal'] === $hariIni) {
        $sudahTutupBuku = true;
        break;
    }
}

// Inisialisasi saldo bank
$saldoPerBank = [];
foreach ($rekeningList as $rek) {
    $saldoPerBank[$rek['nama_bank']] = (int)$rek['saldo']; 
}

// --- LOGIKA PERHITUNGAN ---
foreach ($trxData as $t) {
    $jumlah = (int)$t['jumlah'];
    $tipe = $t['tipe'];
    $akunSumber = $t['akun_sumber'] ?? 'kas_laci'; 
    $akunTujuan = $t['akun_tujuan'] ?? 'kas_laci'; 

    // 1. Logika Pendapatan
    if ($tipe === 'pendapatan') {
        // Hanya tambah ke Kas Laci jika tujuannya 'kas_laci'
        if ($akunTujuan === 'kas_laci') {
            $kasLaci += $jumlah;
        }
        // LOGIKA OMZET SUDAH DIHAPUS TOTAL DI SINI
    } 
    
    // 2. Logika Pengeluaran / Penarikan
    elseif ($tipe === 'pengeluaran' || $tipe === 'penarikan') {
        if ($akunSumber === 'kas_laci') $kasLaci -= $jumlah;
        elseif ($akunSumber === 'kas_ops') $kasOps -= $jumlah;
        elseif (isset($saldoPerBank[$akunSumber])) $saldoPerBank[$akunSumber] -= $jumlah;
        
        if (substr($t['tanggal'], 0, 10) === $hariIni && $tipe === 'pengeluaran') $pengeluaranHariIni += $jumlah;
    }
    
    // 3. Logika Transfer
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
    <title>Laporan Keuangan Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        /* --- STYLE COMPACT & MINIMALIS --- */
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        
        .header-summary {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white; padding: 1.2rem 2rem; border-radius: 12px;
            margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
        }
        .header-title h2 { font-size: 0.9rem; font-weight: 500; opacity: 0.9; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px; }
        .header-amount { font-size: 1.8rem; font-weight: 700; }
        
        .btn-tutup-buku {
            background: #e74c3c; color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;
            transition: transform 0.2s; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        .btn-tutup-buku:hover { background: #c0392b; transform: translateY(-2px); }
        
        .btn-disabled-tutup {
            background: #27ae60; color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: default;
            opacity: 0.9; display: flex; align-items: center; gap: 8px;
        }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .balance-card { 
            background: white; padding: 1.2rem; border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f1f1f1; 
            display: flex; flex-direction: column; height: 100%;
        }
        .balance-card h4 { color: #7f8c8d; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .balance-value { font-size: 1.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 12px; }
        
        .card-laci { border-top: 4px solid #2ecc71; }
        .card-ops { border-top: 4px solid #f39c12; }
        .card-bank { border-top: 4px solid #3498db; }

        .action-row { display: flex; gap: 8px; margin-top: auto; }
        .btn-mini { 
            flex: 1; padding: 8px; font-size: 0.8rem; border-radius: 6px; border: none; 
            cursor: pointer; font-weight: 500; color: white; transition: opacity 0.2s;
        }
        .btn-mini:hover { opacity: 0.9; }
        
        .bg-purple { background: #9b59b6; } .bg-dark { background: #34495e; }
        .bg-blue { background: #3498db; } .bg-orange { background: #e67e22; }

        .bank-list { margin-bottom: 12px; border-top: 1px dashed #eee; padding-top: 8px; max-height: 150px; overflow-y: auto; }
        .bank-item { display: flex; justify-content: space-between; padding: 5px 0; font-size: 0.85rem; border-bottom: 1px solid #f9f9f9; }
        .bank-name { font-weight: 500; color: #555; display: flex; align-items: center; gap: 6px; }
        .bank-saldo { font-weight: 700; color: #3498db; }
        .bank-badge { font-size: 0.65rem; padding: 1px 5px; border-radius: 3px; color: white; font-weight: 600; }
        .badge-bank { background: #34495e; } .badge-ewallet { background: #1abc9c; }

        .section-header { margin-top: 2rem; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px; font-size: 1.1rem; font-weight: 600; color: #2c3e50; }
        
        .table-wrapper { overflow-x: auto; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .table-custom th { background: #f8f9fa; padding: 10px 15px; text-align: left; color: #666; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .table-custom td { padding: 10px 15px; border-bottom: 1px solid #f1f1f1; color: #444; vertical-align: top; }
        
        .badge-tipe { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .tipe-masuk { background: #e6f9ed; color: #2ecc71; }
        .tipe-keluar { background: #fdeaea; color: #e74c3c; }
        .tipe-transfer { background: #e3f2fd; color: #3498db; }

        .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 1.5rem; border-radius: 12px; width: 90%; max-width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: zoomIn 0.2s ease; }
        @keyframes zoomIn { from { transform: scale(0.95); opacity:0; } to { transform: scale(1); opacity:1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem; transition: 0.2s; }
        .modal-form-group label { font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 4px; display: block; }
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
            <div class="header-title">
                <h2>Total Aset Likuid</h2>
                <div class="header-amount">Rp <?php echo number_format($totalAset, 0, ',', '.'); ?></div>
            </div>
            
            <?php if ($sudahTutupBuku): ?>
                <button class="btn-disabled-tutup" title="Anda sudah tutup buku hari ini">
                    <span>✅</span> Sudah Tutup Buku
                </button>
            <?php else: ?>
                <button onclick="bukaModal('modalTutupBuku')" class="btn-tutup-buku">
                    <span>📕</span> Tutup Buku Hari Ini
                </button>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <div class="balance-card card-laci">
                <div>
                    <h4>🔥 Kas Laci (Cashier)</h4>
                    <div class="balance-value">Rp <?php echo number_format(max(0, $kasLaci), 0, ',', '.'); ?></div>
                </div>
                <div class="action-row">
                    <button onclick="modalTransfer('kas_laci')" class="btn-mini bg-purple">Transfer</button>
                    <button onclick="modalSetorBank()" class="btn-mini bg-blue">Setor Bank</button>
                </div>
            </div>

            <div class="balance-card card-ops">
                <div>
                    <h4>⚡ Kas Operasional</h4>
                    <div class="balance-value">Rp <?php echo number_format(max(0, $kasOps), 0, ',', '.'); ?></div>
                </div>
                <div class="action-row">
                    <button onclick="modalPengeluaran('kas_ops')" class="btn-mini bg-orange">Catat Biaya</button>
                    <button onclick="modalTransfer('kas_ops')" class="btn-mini bg-purple">Topup</button>
                </div>
            </div>

            <div class="balance-card card-bank">
                <div>
                    <h4>🏦 Rekening & E-Wallet</h4>
                    <div class="balance-value">Rp <?php echo number_format(max(0, $totalSaldoBank), 0, ',', '.'); ?></div>
                    
                    <div class="bank-list">
                        <?php foreach ($rekeningList as $rek): 
                            $saldoSaatIni = $saldoPerBank[$rek['nama_bank']] ?? 0;
                            $badgeColor = ($rek['jenis'] == 'bank') ? 'badge-bank' : 'badge-ewallet';
                        ?>
                        <div class="bank-item">
                            <div class="bank-name">
                                <span class="bank-badge <?php echo $badgeColor; ?>"><?php echo strtoupper($rek['jenis']); ?></span>
                                <?php echo htmlspecialchars($rek['nama_bank']); ?>
                            </div>
                            <div class="bank-saldo">
                                <?php echo number_format(max(0, $saldoSaatIni), 0, ',', '.'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="action-row">
                    <button onclick="modalPrive()" class="btn-mini bg-dark">Prive</button>
                    <button onclick="modalTransfer('bank')" class="btn-mini bg-purple">Transfer</button>
                </div>
            </div>
        </div>

        <div class="section-header">📜 Mutasi Hari Ini (<?php echo date('d M Y'); ?>)</div>
        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Jam</th><th>Tipe</th><th>Arus Dana</th><th>Deskripsi</th><th>Nominal</th><th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach (array_reverse($trxData) as $t) {
                        if (substr($t['tanggal'], 0, 10) !== $hariIni) continue;
                        $count++;
                        $badgeClass = 'tipe-transfer';
                        if ($t['tipe'] === 'pendapatan') $badgeClass = 'tipe-masuk';
                        elseif ($t['tipe'] === 'pengeluaran' || $t['tipe'] === 'penarikan') $badgeClass = 'tipe-keluar';
                        
                        $sumber = $t['akun_sumber'] ?? '-';
                        $tujuan = $t['akun_tujuan'] ?? '-';
                        $arus = ($t['tipe']=='transfer') ? "$sumber ➔ $tujuan" : $sumber;
                    ?>
                    <tr>
                        <td><?php echo substr($t['tanggal'], 11, 5); ?></td>
                        <td><span class="badge-tipe <?php echo $badgeClass; ?>"><?php echo strtoupper($t['tipe']); ?></span></td>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($arus); ?></td>
                        <td><?php echo htmlspecialchars($t['deskripsi']); ?></td>
                        <td style="font-weight:700; color:#333;">Rp <?php echo number_format($t['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($t['pelaku'] ?? 'Sys'); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if($count == 0): ?><tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#999;">Belum ada transaksi hari ini.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-header">📚 Riwayat Tutup Buku</div>
        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr style="background:#2c3e50; color:white;">
                        <th style="color:grey;">Tanggal</th>
                        <th style="color:grey;">Waktu</th>
                        <th style="color:grey;">Kas Laci</th>
                        <th style="color:grey;">Kas Ops</th>
                        <th style="color:grey;">Rekening & E-Wallet</th>
                        <th style="color:grey;">Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($historiLaporan) as $lap): ?>
                    <tr>
                        <td><strong><?php echo date('d M Y', strtotime($lap['tanggal'])); ?></strong></td>
                        <td><?php echo $lap['waktu_tutup']; ?></td>
                        <td>Rp <?php echo number_format($lap['saldo_laci'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($lap['saldo_ops'], 0, ',', '.'); ?></td>
                        <td>
                            <?php 
                            if (isset($lap['rincian_bank']) && is_array($lap['rincian_bank'])) {
                                echo "<ul style='margin:0; padding-left:0; list-style:none;'>";
                                foreach($lap['rincian_bank'] as $nmBank => $jml) {
                                    if($jml > 0) {
                                        echo "<li style='font-size:0.8rem; border-bottom:1px dashed #eee; padding:2px 0;'>
                                                <span style='color:#555;'>$nmBank:</span> 
                                                <b style='color:#2980b9;'>".number_format($jml,0,',','.')."</b>
                                              </li>";
                                    }
                                }
                                echo "</ul>";
                                echo "<div style='margin-top:4px; font-size:0.8rem; font-weight:bold; color:#333; border-top:1px solid #ddd;'>Total: ".number_format($lap['saldo_bank'],0,',','.')."</div>";
                            } else {
                                echo "Rp " . number_format($lap['saldo_bank'], 0, ',', '.');
                            }
                            ?>
                        </td>
                        <td><?php echo $lap['petugas']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="modalTransfer" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>🔄 Transfer Dana</h2><span class="close-modal" onclick="tutupModal('modalTransfer')" style="cursor:pointer; font-size:1.5rem;">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="transfer_kas">
                <div class="modal-form-group">
                    <label>Dari Kas</label>
                    <select name="sumber" id="transferSumber" class="form-control">
                        <option value="kas_laci">🔥 Kas Laci</option>
                        <option value="kas_ops">⚡ Kas Operasional</option>
                        <optgroup label="Bank & E-Wallet">
                            <?php foreach ($rekeningList as $rek): ?><option value="<?php echo $rek['nama_bank']; ?>"><?php echo $rek['nama_bank']; ?></option><?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Ke Kas</label>
                    <select name="tujuan" class="form-control">
                        <option value="kas_ops">⚡ Kas Operasional</option>
                        <option value="kas_laci">🔥 Kas Laci</option>
                        <optgroup label="Bank & E-Wallet">
                            <?php foreach ($rekeningList as $rek): ?><option value="<?php echo $rek['nama_bank']; ?>"><?php echo $rek['nama_bank']; ?></option><?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Catatan</label><input type="text" name="catatan" required class="form-control" placeholder="Contoh: Setor Tunai"></div>
                <button type="submit" class="btn-submit-wd bg-purple" style="width:100%; padding:12px; color:white; font-weight:bold; border:none; border-radius:6px; cursor:pointer;">Transfer Sekarang</button>
            </form>
        </div>
    </div>

    <div id="modalPengeluaran" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>💸 Catat Pengeluaran</h2><span class="close-modal" onclick="tutupModal('modalPengeluaran')" style="cursor:pointer; font-size:1.5rem;">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="catat_pengeluaran">
                <div class="modal-form-group">
                    <label>Sumber Dana</label>
                    <select name="sumber" id="expSumber" class="form-control">
                        <option value="kas_ops">Kas Operasional</option>
                        <option value="kas_laci">Kas Laci</option>
                        <optgroup label="Bank"><?php foreach ($rekeningList as $rek): ?><option value="<?php echo $rek['nama_bank']; ?>"><?php echo $rek['nama_bank']; ?></option><?php endforeach; ?></optgroup>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Keperluan</label><input type="text" name="deskripsi" required class="form-control"></div>
                <button type="submit" class="btn-submit-wd" style="background:#e74c3c; width:100%; padding:12px; color:white; font-weight:bold; border:none; border-radius:6px; cursor:pointer;">Simpan Pengeluaran</button>
            </form>
        </div>
    </div>

    <div id="modalPrive" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>🤵 Penarikan Owner</h2><span class="close-modal" onclick="tutupModal('modalPrive')" style="cursor:pointer; font-size:1.5rem;">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="tarik_prive">
                <div class="modal-form-group">
                    <label>Ambil Dari</label>
                    <select name="sumber" class="form-control">
                        <optgroup label="Bank"><?php foreach ($rekeningList as $rek): ?><option value="<?php echo $rek['nama_bank']; ?>"><?php echo $rek['nama_bank']; ?></option><?php endforeach; ?></optgroup>
                        <option value="kas_laci">Kas Laci</option>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Catatan</label><input type="text" name="catatan" required class="form-control"></div>
                <button type="submit" class="btn-submit-wd bg-dark" style="width:100%; padding:12px; color:white; font-weight:bold; border:none; border-radius:6px; cursor:pointer;">Tarik Dana</button>
            </form>
        </div>
    </div>

    <div id="modalTutupBuku" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>📕 Konfirmasi Tutup Buku</h2><span class="close-modal" onclick="tutupModal('modalTutupBuku')" style="cursor:pointer; font-size:1.5rem;">×</span></div>
            <p style="margin-bottom:15px; color:#555; font-size:0.9rem;">
                ⚠️ Peringatan: Tindakan ini akan mereset semua saldo kas menjadi Rp 0 untuk memulai hari besok. Data hari ini akan disimpan di Riwayat.
            </p>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="tutup_buku">
                <input type="hidden" name="saldo_laci" value="<?php echo $kasLaci; ?>">
                <input type="hidden" name="saldo_ops" value="<?php echo $kasOps; ?>">
                <input type="hidden" name="saldo_bank" value="<?php echo $totalSaldoBank; ?>">
                <input type="hidden" name="rincian_bank" value="<?php echo htmlspecialchars(json_encode($saldoPerBank)); ?>">
                <input type="hidden" name="total_aset" value="<?php echo $totalAset; ?>">
                <button type="submit" class="btn-submit-wd" style="background:#27ae60; width:100%; padding:12px; color:white; font-weight:bold; border:none; border-radius:6px; cursor:pointer;">✅ Ya, Tutup Buku</button>
            </form>
        </div>
    </div>

    <script>
        function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        function modalTransfer(sumber) {
            if(sumber !== 'bank') document.getElementById('transferSumber').value = sumber;
            bukaModal('modalTransfer');
        }
        function modalSetorBank() {
            document.getElementById('transferSumber').value = 'kas_laci';
            bukaModal('modalTransfer');
        }
        function modalPrive() { bukaModal('modalPrive'); }
        function modalPengeluaran(sumber) {
            document.getElementById('expSumber').value = sumber;
            bukaModal('modalPengeluaran');
        }
        window.onclick = function(e) { if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; }
    </script>

</body>
</html>