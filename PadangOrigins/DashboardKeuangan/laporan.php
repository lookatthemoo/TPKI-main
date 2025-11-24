<?php
// --- SETUP & LOGIC ---
require_once 'auth_check.php';
$base_dir = __DIR__; 
$file_harian = $base_dir . '/data/laporan_harian.json';

// Variables
$riwayat = [];
$total_omzet_all = 0;
$total_uang_real = 0; // Total kekayaan saat ini
$saldo_laci_terkini = 0;
$saldo_bank_terkini = 0;

if (file_exists($file_harian)) {
    $json_raw = file_get_contents($file_harian);
    $decoded = json_decode($json_raw, true);

    if (is_array($decoded)) {
        $riwayat = $decoded;
        
        // Sorting
        usort($riwayat, function ($a, $b) {
            $t_a = strtotime($a['tanggal'] . ' ' . ($a['waktu_tutup'] ?? '00:00'));
            $t_b = strtotime($b['tanggal'] . ' ' . ($b['waktu_tutup'] ?? '00:00'));
            return $t_b - $t_a;
        });

        // Hitung Total Omzet (Akumulasi Penjualan)
        foreach ($riwayat as $r) {
            $total_omzet_all += $r['omzet_hari_ini'] ?? 0;
        }

        // Ambil Data Terkini (Snapshot Keuangan Saat Ini)
        if (!empty($riwayat)) {
            // Kita ambil data dari baris paling atas (terbaru)
            $terbaru = $riwayat[0];
            $saldo_laci_terkini = $terbaru['saldo_laci'] ?? 0;
            $saldo_bank_terkini = $terbaru['saldo_bank'] ?? 0;
            
            // Total Uang Real = Apa yang tertulis di Total Aset JSON
            $total_uang_real = $terbaru['total_aset'] ?? 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial AI Core - Realtime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- AI CORE THEME 2.0 (High Contrast) --- */
        :root {
            --bg-core: #0f172a;
            --panel-bg: #1e293b;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --accent-cyan: #06b6d4;
            --accent-green: #22c55e;
            --accent-purple: #8b5cf6;
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-core);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            padding: 2rem;
        }

        .btn-back {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-sub);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .btn-back:hover {
            background: var(--accent-cyan);
            color: #000;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
        }

        /* CARD HEADER UTAMA (Money Focus) */
        .money-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        
        .money-label { font-size: 0.85rem; text-transform: uppercase; color: var(--text-sub); letter-spacing: 1px; margin-bottom: 0.5rem; }
        .money-value { font-size: 2.5rem; font-weight: 700; color: #fff; }
        .money-sub { font-size: 0.9rem; color: var(--accent-green); }

        /* TABEL LOG */
        .table-container {
            background: var(--panel-bg);
            border-radius: 20px;
            border: 1px solid var(--border-glass);
            overflow: hidden;
        }
        .table-ai { width: 100%; border-collapse: collapse; }
        .table-ai th {
            text-align: left;
            padding: 1.5rem;
            color: var(--text-sub);
            font-size: 0.75rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-glass);
            background: rgba(0,0,0,0.2);
        }
        .table-ai td {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: var(--text-main);
        }
        .table-ai tr:hover td { background: rgba(255,255,255,0.02); }

        /* Badge Style */
        .badge-money {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-glass);
            margin-right: 5px;
        }
        .c-cyan { color: var(--accent-cyan); border-color: rgba(6, 182, 212, 0.3); }
        .c-purple { color: var(--accent-purple); border-color: rgba(139, 92, 246, 0.3); }

        @media(max-width:768px) {
            .money-value { font-size: 1.8rem; }
            .d-flex-gap { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Dashboard</a>
        <div class="text-end">
            <span class="badge bg-dark border border-secondary">AI SYSTEM V.2.0</span>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="money-card">
                <div class="money-label"><i class="fas fa-vault me-2"></i>Total Kekayaan Real (Aset)</div>
                <div class="money-value">Rp <?= number_format($total_uang_real, 0, ',', '.') ?></div>
                <div class="mt-3 d-flex gap-2">
                    <span class="badge-money c-cyan"><i class="fas fa-cash-register me-1"></i> Laci: <?= number_format($saldo_laci_terkini/1000, 0) ?>k</span>
                    <span class="badge-money c-purple"><i class="fas fa-university me-1"></i> Bank: <?= number_format($saldo_bank_terkini/1000, 0) ?>k</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="money-card" style="background: linear-gradient(135deg, #064e3b 0%, #022c22 100%); border-color: var(--accent-green);">
                <div class="money-label" style="color: #86efac;"><i class="fas fa-chart-line me-2"></i>Akumulasi Omzet</div>
                <div class="money-value" style="color: #bef264;">Rp <?= number_format($total_omzet_all, 0, ',', '.') ?></div>
                <div class="mt-3 text-white-50 small">
                    Total nilai transaksi penjualan yang pernah tercatat.
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="p-4 border-bottom border-secondary">
            <h5 class="m-0 fw-bold"><i class="fas fa-history me-2"></i>Log Riwayat Tutup Buku</h5>
        </div>
        <div class="table-responsive">
            <table class="table-ai">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Penjualan (Omzet)</th>
                        <th>Posisi Saldo Akhir</th>
                        <th>Total Aset</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($riwayat as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                <small class="text-muted"><?= $row['waktu_tutup'] ?? '-' ?></small>
                            </td>
                            
                            <td>
                                <div style="color: var(--accent-green); font-weight: bold; font-size: 1.1rem;">
                                    + Rp <?= number_format($row['omzet_hari_ini'] ?? 0, 0, ',', '.') ?>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">TRANSAKSI HARI INI</small>
                            </td>

                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="badge-money c-cyan" style="width: fit-content;">
                                        Laci: Rp <?= number_format($row['saldo_laci'] ?? 0, 0, ',', '.') ?>
                                    </div>
                                    <?php if(($row['saldo_bank'] ?? 0) > 0): ?>
                                    <div class="badge-money c-purple" style="width: fit-content;">
                                        Bank: Rp <?= number_format($row['saldo_bank'] ?? 0, 0, ',', '.') ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="fw-bold fs-5">
                                Rp <?= number_format($row['total_aset'] ?? 0, 0, ',', '.') ?>
                            </td>

                            <td>
                                <span class="badge bg-secondary text-dark fw-bold"><?= $row['petugas'] ?? 'SYS' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>