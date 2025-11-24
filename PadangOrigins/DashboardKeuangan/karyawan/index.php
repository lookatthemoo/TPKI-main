<?php
require_once '../auth_check.php';

// --- 1. LOAD DATA ---
$fileTrx = '../data/transaksi.json';
$fileLaporan = '../data/laporan_harian.json';
$trxData = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];
$historiLaporan = file_exists($fileLaporan) ? json_decode(file_get_contents($fileLaporan), true) : [];

// --- 2. HITUNG SALDO PER KAS ---
// Default Saldo Awal (Bisa diset manual di sini atau biarkan 0 dan tambah via pemasukan)
$kasLaci = 0;
$kasOps = 0;
$kasBank = 0;

$omzetHariIni = 0;
$pengeluaranHariIni = 0;
$hariIni = date('Y-m-d');

foreach ($trxData as $t) {
    $jumlah = (int)$t['jumlah'];
    $tipe = $t['tipe'];
    
    // Default akun jika data lama (Legacy Support)
    $akunSumber = $t['akun_sumber'] ?? 'kas_laci'; 
    $akunTujuan = $t['akun_tujuan'] ?? '';

    // A. LOGIKA SALDO UTAMA
    if ($tipe === 'pendapatan') {
        // Pendapatan default masuk Kas Laci
        $kasLaci += $jumlah;
        
        if (substr($t['tanggal'], 0, 10) === $hariIni) $omzetHariIni += $jumlah;
    } 
    elseif ($tipe === 'pengeluaran' || $tipe === 'penarikan') {
        // Kurangi dari akun sumber yang sesuai
        if ($akunSumber === 'kas_laci') $kasLaci -= $jumlah;
        elseif ($akunSumber === 'kas_ops') $kasOps -= $jumlah;
        elseif ($akunSumber === 'bank') $kasBank -= $jumlah;
        
        if (substr($t['tanggal'], 0, 10) === $hariIni && $tipe === 'pengeluaran') $pengeluaranHariIni += $jumlah;
    }
    elseif ($tipe === 'transfer') {
        // Pindah Uang: Kurangi Sumber, Tambah Tujuan
        if ($akunSumber === 'kas_laci') $kasLaci -= $jumlah;
        elseif ($akunSumber === 'kas_ops') $kasOps -= $jumlah;
        elseif ($akunSumber === 'bank') $kasBank -= $jumlah;

        if ($akunTujuan === 'kas_laci') $kasLaci += $jumlah;
        elseif ($akunTujuan === 'kas_ops') $kasOps += $jumlah;
        elseif ($akunTujuan === 'bank') $kasBank += $jumlah;
    }
}

$totalAset = $kasLaci + $kasOps + $kasBank;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* --- CUSTOM STYLE HALAMAN INI --- */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .balance-card { background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: relative; overflow: hidden; }
        .balance-card h4 { color: #888; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .balance-value { font-size: 2rem; font-weight: 800; color: #333; margin-bottom: 15px; }
        
        /* Warna Aksen Kartu */
        .card-laci { border-left: 5px solid #2ecc71; }
        .card-ops { border-left: 5px solid #f39c12; }
        .card-bank { border-left: 5px solid #3498db; }

        .btn-mini { padding: 5px 12px; font-size: 0.8rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; color: white; margin-right: 5px; }
        .bg-purple { background: #9b59b6; }
        .bg-dark { background: #34495e; }

        .section-title { margin-top: 3rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        
        /* Tabel Custom */
        .table-custom { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); font-size: 0.9rem; }
        .table-custom th { background: #f8f9fa; padding: 12px 15px; text-align: left; color: #555; font-weight: 600; }
        .table-custom td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .badge-tipe { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        
        .tipe-masuk { background: #e6f9ed; color: #2ecc71; }
        .tipe-keluar { background: #fdeaea; color: #e74c3c; }
        .tipe-transfer { background: #e3f2fd; color: #3498db; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <h1 class="logo">💰 Keuangan & Kas</h1>
            <nav><a href="../index.php" class="nav-link btn-logout">Kembali Dashboard</a></nav>
        </div>
    </header>

    <main class="container">
        
        <div style="background: #2c3e50; color: white; padding: 2rem; border-radius: 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h2 style="margin-bottom: 5px;">Total Aset Likuid</h2>
                <div style="font-size: 2.5rem; font-weight: 800;">Rp <?php echo number_format($totalAset, 0, ',', '.'); ?></div>
                <small>Gabungan Kas Laci + Operasional + Bank</small>
            </div>
            <div>
                <button onclick="bukaModal('modalTutupBuku')" class="btn-submit-wd" style="background: #e74c3c; border: 2px solid rgba(255,255,255,0.2);">📕 TUTUP BUKU HARI INI</button>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="balance-card card-laci">
                <h4>🔥 Kas Laci (Cashier)</h4>
                <div class="balance-value">Rp <?php echo number_format($kasLaci, 0, ',', '.'); ?></div>
                <div style="display: flex; gap: 5px;">
                    <button onclick="modalTransfer('kas_laci')" class="btn-mini bg-purple">Transfer ➔</button>
                    <button onclick="modalSetorBank()" class="btn-mini" style="background: #3498db;">Setor Bank</button>
                </div>
            </div>

            <div class="balance-card card-ops">
                <h4>⚡ Kas Operasional</h4>
                <div class="balance-value">Rp <?php echo number_format($kasOps, 0, ',', '.'); ?></div>
                <div style="display: flex; gap: 5px;">
                    <button onclick="modalPengeluaran('kas_ops')" class="btn-mini" style="background: #e67e22;">Catat Biaya</button>
                    <button onclick="modalTransfer('kas_ops')" class="btn-mini bg-purple">Isi Saldo +</button>
                </div>
            </div>

            <div class="balance-card card-bank">
                <h4>🏦 Rekening Bank</h4>
                <div class="balance-value">Rp <?php echo number_format($kasBank, 0, ',', '.'); ?></div>
                <div style="display: flex; gap: 5px;">
                    <button onclick="modalPrive()" class="btn-mini bg-dark">Tarik Prive</button>
                    <button onclick="modalTransfer('bank')" class="btn-mini bg-purple">Transfer ➔</button>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h3>📜 Mutasi Hari Ini (<?php echo date('d M Y'); ?>)</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Jam</th>
                        <th>Tipe</th>
                        <th>Dari ➔ Ke</th>
                        <th>Deskripsi</th>
                        <th>Nominal</th>
                        <th>Pelaku</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    // Loop dari transaksi TERBARU ke LAMA
                    foreach (array_reverse($trxData) as $t) {
                        // Filter hanya hari ini
                        if (substr($t['tanggal'], 0, 10) !== $hariIni) continue;
                        $count++;
                        
                        $badgeClass = 'tipe-transfer';
                        if ($t['tipe'] === 'pendapatan') $badgeClass = 'tipe-masuk';
                        elseif ($t['tipe'] === 'pengeluaran' || $t['tipe'] === 'penarikan') $badgeClass = 'tipe-keluar';
                        
                        // Label Akun
                        $sumber = $t['akun_sumber'] ?? '-';
                        $tujuan = $t['akun_tujuan'] ?? '-';
                        $arus = ($t['tipe']=='transfer') ? "$sumber ➔ $tujuan" : $sumber;
                    ?>
                    <tr>
                        <td><?php echo substr($t['tanggal'], 11, 5); ?></td>
                        <td><span class="badge-tipe <?php echo $badgeClass; ?>"><?php echo strtoupper($t['tipe']); ?></span></td>
                        <td><?php echo htmlspecialchars($arus); ?></td>
                        <td><?php echo htmlspecialchars($t['deskripsi']); ?></td>
                        <td style="font-weight:bold;">Rp <?php echo number_format($t['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($t['pelaku'] ?? 'Sys'); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if($count == 0): ?>
                        <tr><td colspan="6" style="text-align:center; padding:2rem;">Belum ada transaksi hari ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-title">
            <h3>📚 Riwayat Laporan Harian (Tutup Buku)</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="table-custom">
                <thead>
                    <tr style="background:#34495e; color:white;">
                        <th>Tanggal</th>
                        <th>Waktu Tutup</th>
                        <th>Kas Laci</th>
                        <th>Kas Ops</th>
                        <th>Bank</th>
                        <th>Omzet</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($historiLaporan) as $lap): ?>
                    <tr>
                        <td><strong><?php echo date('d M Y', strtotime($lap['tanggal'])); ?></strong></td>
                        <td><?php echo $lap['waktu_tutup']; ?></td>
                        <td>Rp <?php echo number_format($lap['saldo_laci'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($lap['saldo_ops'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($lap['saldo_bank'], 0, ',', '.'); ?></td>
                        <td style="color:#2ecc71; font-weight:bold;">Rp <?php echo number_format($lap['omzet_hari_ini'], 0, ',', '.'); ?></td>
                        <td><?php echo $lap['petugas']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="modalTransfer" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>🔄 Transfer Antar Kas</h2><span class="close-modal" onclick="tutupModal('modalTransfer')">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="transfer_kas">
                <div class="modal-form-group">
                    <label>Dari Kas</label>
                    <select name="sumber" id="transferSumber" class="form-control">
                        <option value="kas_laci">Kas Laci</option>
                        <option value="kas_ops">Kas Operasional</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Ke Kas</label>
                    <select name="tujuan" class="form-control">
                        <option value="kas_ops">Kas Operasional</option>
                        <option value="bank">Bank</option>
                        <option value="kas_laci">Kas Laci</option>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Catatan</label><input type="text" name="catatan" required class="form-control" placeholder="Contoh: Topup Ops"></div>
                <button type="submit" class="btn-submit-wd bg-purple">Transfer Sekarang</button>
            </form>
        </div>
    </div>

    <div id="modalPengeluaran" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>💸 Catat Pengeluaran</h2><span class="close-modal" onclick="tutupModal('modalPengeluaran')">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="catat_pengeluaran">
                <div class="modal-form-group">
                    <label>Sumber Dana</label>
                    <select name="sumber" id="expSumber" class="form-control">
                        <option value="kas_ops">Kas Operasional</option>
                        <option value="kas_laci">Kas Laci</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Keperluan</label><input type="text" name="deskripsi" required class="form-control" placeholder="Beli Gas, Galon, dll"></div>
                <button type="submit" class="btn-submit-wd" style="background:#e74c3c;">Simpan Pengeluaran</button>
            </form>
        </div>
    </div>

    <div id="modalPrive" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>🤵 Penarikan Owner (Prive)</h2><span class="close-modal" onclick="tutupModal('modalPrive')">×</span></div>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="tarik_prive">
                <div class="modal-form-group">
                    <label>Ambil Dari</label>
                    <select name="sumber" class="form-control">
                        <option value="bank">Bank</option>
                        <option value="kas_laci">Kas Laci</option>
                    </select>
                </div>
                <div class="modal-form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <div class="modal-form-group"><label>Catatan</label><input type="text" name="catatan" required class="form-control" placeholder="Keperluan pribadi..."></div>
                <button type="submit" class="btn-submit-wd bg-dark">Tarik Dana</button>
            </form>
        </div>
    </div>

    <div id="modalTutupBuku" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>📕 Konfirmasi Tutup Buku</h2><span class="close-modal" onclick="tutupModal('modalTutupBuku')">×</span></div>
            <p style="margin-bottom:15px; color:#555;">Akan menyimpan snapshot keuangan saat ini ke Riwayat Laporan.</p>
            <form action="proses_laporan.php" method="POST">
                <input type="hidden" name="action" value="tutup_buku">
                <input type="hidden" name="saldo_laci" value="<?php echo $kasLaci; ?>">
                <input type="hidden" name="saldo_ops" value="<?php echo $kasOps; ?>">
                <input type="hidden" name="saldo_bank" value="<?php echo $kasBank; ?>">
                <input type="hidden" name="total_aset" value="<?php echo $totalAset; ?>">
                <input type="hidden" name="omzet" value="<?php echo $omzetHariIni; ?>">
                
                <div style="background:#f9f9f9; padding:10px; border-radius:8px; margin-bottom:15px;">
                    <div><strong>Kas Laci:</strong> Rp <?php echo number_format($kasLaci, 0, ',', '.'); ?></div>
                    <div><strong>Omzet Hari Ini:</strong> Rp <?php echo number_format($omzetHariIni, 0, ',', '.'); ?></div>
                </div>

                <button type="submit" class="btn-submit-wd" style="background:#27ae60;">✅ Simpan & Tutup Buku</button>
            </form>
        </div>
    </div>

    <script>
        // Helper Modal
        function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        
        // Trigger Spesifik
        function modalTransfer(sumber) {
            document.getElementById('transferSumber').value = sumber;
            bukaModal('modalTransfer');
        }
        function modalPengeluaran(sumber) {
            document.getElementById('expSumber').value = sumber;
            bukaModal('modalPengeluaran');
        }
        function modalSetorBank() {
            document.getElementById('transferSumber').value = 'kas_laci';
            // Setor Bank itu intinya Transfer Laci -> Bank
            document.querySelector('select[name="tujuan"]').value = 'bank';
            bukaModal('modalTransfer');
        }
        function modalPrive() { bukaModal('modalPrive'); }

        // Close on outside click
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
        }
    </script>

</body>
</html>