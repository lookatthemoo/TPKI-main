<?php
require_once '../auth_check.php';

$fileExp = '../data/pengeluaran.json';
$pengeluaran = file_exists($fileExp) ? json_decode(file_get_contents($fileExp), true) : [];

// --- 1. LOGIKA ANALISA DATA (Untuk Grafik) ---
$currentMonth = date('Y-m');
$totalBulanIni = 0;
$catTotals = [];

// Sortir data (Terbaru diatas)
usort($pengeluaran, function($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

foreach ($pengeluaran as $exp) {
    // Cek apakah transaksi terjadi di bulan ini
    if (strpos($exp['tanggal'], $currentMonth) === 0) {
        $jumlah = (int)$exp['jumlah'];
        $kategori = $exp['kategori'] ?? 'Lain-lain';
        
        $totalBulanIni += $jumlah;
        
        if (!isset($catTotals[$kategori])) {
            $catTotals[$kategori] = 0;
        }
        $catTotals[$kategori] += $jumlah;
    }
}

// Urutkan kategori dari yang terbesar (Untuk info "Terboros")
arsort($catTotals);
$topCategory = array_key_first($catTotals);
$topCatValue = !empty($catTotals) ? $catTotals[$topCategory] : 0;

// Siapkan Data JSON untuk Chart.js
$jsLabels = json_encode(array_keys($catTotals));
$jsValues = json_encode(array_values($catTotals));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Pengeluaran - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg: #fff1f2; --text: #1e293b; --primary: #e11d48; --card: #ffffff; }
        body { background: var(--bg); font-family: 'Manrope', sans-serif; color: var(--text); margin:0; padding-bottom:60px; }
        
        .header { background: white; padding: 1.5rem 2rem; border-bottom: 1px solid #fecaca; display: flex; justify-content: space-between; align-items: center; position:sticky; top:0; z-index:50; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        /* GRID LAYOUT UTAMA */
        .main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start; }
        
        .card { background: white; border-radius: 20px; padding: 1.5rem; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.05); border: 1px solid #ffe4e6; }
        
        /* SUMMARY BOX (Kiri Atas) */
        .summary-box { 
            background: linear-gradient(135deg, #e11d48, #be123c); color: white; 
            padding: 2rem; border-radius: 20px; text-align: center; position: relative; overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .summary-box h1 { font-size: 2.2rem; margin: 10px 0; font-weight: 800; }
        
        /* CHART BOX */
        .chart-legend { list-style: none; padding: 0; margin-top: 1rem; max-height: 200px; overflow-y: auto; }
        .legend-item { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 8px 0; border-bottom: 1px dashed #eee; }
        .legend-color { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }

        /* ACTION BUTTONS */
        .action-bar { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 2rem; }
        .btn-action {
            padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 700; 
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; font-size: 0.9rem;
            border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .btn-smart { background: #059669; color: white; }
        .btn-smart:hover { background: #047857; transform: translateY(-2px); }
        .btn-manual { background: white; color: #e11d48; border: 1px solid #e11d48; }
        .btn-manual:hover { background: #fff1f2; transform: translateY(-2px); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th { text-align: left; padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 1rem; border-bottom: 1px dashed #e2e8f0; }
        .amount { font-weight: 700; color: #e11d48; }
        
        .badge { padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .bg-inv { background: #ecfdf5; color: #047857; }
        .bg-ops { background: #eff6ff; color: #1d4ed8; }
        .bg-sal { background: #fff7ed; color: #c2410c; }

        @media (max-width: 1024px) { .main-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="header">
        <div>
            <h1 style="font-size: 1.4rem; margin:0; font-weight:800; color:#be123c;">💸 Manajemen Pengeluaran</h1>
            <p style="margin:0; color:#64748b; font-size:0.9rem;">Analisa & Kontrol Arus Kas Keluar.</p>
        </div>
        <a href="../index.php" style="text-decoration:none; color: #64748b; font-weight: 600;">← Dashboard</a>
    </header>

    <main class="container main-grid">
        
        <aside>
            <div class="summary-box">
                <span style="opacity:0.9; font-size:0.9rem;">Total Pengeluaran (<?= date('M Y'); ?>)</span>
                <h1>Rp <?= number_format($totalBulanIni, 0, ',', '.'); ?></h1>
                <?php if($topCategory): ?>
                    <div style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; display: inline-block; font-size: 0.8rem; margin-top: 5px;">
                        Terboros: <b><?= $topCategory; ?></b> (<?= round(($topCatValue/$totalBulanIni)*100); ?>%)
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 style="margin-bottom:1rem; color:#1e293b;">Proporsi Biaya</h3>
                <?php if($totalBulanIni > 0): ?>
                    <div style="height: 200px; position: relative;">
                        <canvas id="costChart"></canvas>
                    </div>
                    
                    <ul class="chart-legend">
                        <?php 
                        $colors = ['#ef4444', '#f97316', '#eab308', '#84cc16', '#3b82f6', '#6366f1', '#a855f7'];
                        $i = 0;
                        foreach($catTotals as $cat => $val):
                            $col = $colors[$i % count($colors)];
                        ?>
                            <li class="legend-item">
                                <span><span class="legend-color" style="background:<?= $col; ?>"></span> <?= $cat; ?></span>
                                <span style="font-weight:700;">Rp <?= number_format($val, 0, ',', '.'); ?></span>
                            </li>
                        <?php $i++; endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="text-align:center; color:#94a3b8; padding: 2rem;">Belum ada data bulan ini.</p>
                <?php endif; ?>
            </div>
        </aside>

        <section>
            
            <div class="action-bar">
                <a href="../belanja.php" class="btn-action btn-smart">
                    🛒 Belanja Stok (Smart)
                </a>
                <a href="../tambah_pengeluaran.php" class="btn-action btn-manual">
                    📝 Input Manual
                </a>
            </div>

            <div class="card">
                <h3 style="margin:0 0 1.5rem 0; color:#1e293b;">Riwayat Transaksi</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Sumber</th>
                                <th style="text-align:right;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pengeluaran)): ?>
                                <tr><td colspan="5" style="text-align:center; padding:3rem; color:#94a3b8;">Belum ada data transaksi.</td></tr>
                            <?php else: ?>
                                <?php foreach($pengeluaran as $p): 
                                    // Warna Badge Kategori
                                    $badgeClass = 'bg-ops';
                                    if(strpos($p['kategori'], 'Belanja') !== false || strpos($p['kategori'], 'Stok') !== false) $badgeClass = 'bg-inv';
                                    elseif(strpos($p['kategori'], 'Gaji') !== false || strpos($p['kategori'], 'Bonus') !== false) $badgeClass = 'bg-sal';
                                ?>
                                    <tr>
                                        <td style="white-space:nowrap; color:#64748b;">
                                            <?= date('d M, H:i', strtotime($p['tanggal'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars($p['kategori']); ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($p['deskripsi']); ?></div>
                                            <div style="font-size:0.75rem; color:#94a3b8;">Admin: <?= htmlspecialchars($p['admin'] ?? '-'); ?></div>
                                        </td>
                                        <td style="font-size:0.85rem;"><?= htmlspecialchars($p['sumber_dana']); ?></td>
                                        <td class="amount" style="text-align:right;">
                                            Rp <?= number_format($p['jumlah'], 0, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <script>
        // Render Chart hanya jika ada data
        <?php if($totalBulanIni > 0): ?>
        const ctx = document.getElementById('costChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= $jsLabels; ?>,
                datasets: [{
                    data: <?= $jsValues; ?>,
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#84cc16', '#3b82f6', '#6366f1', '#a855f7'],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Kita pakai legend custom HTML
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                let total = context.chart._metasets[context.datasetIndex].total;
                                let percentage = (value / total * 100).toFixed(1) + "%";
                                return context.label + ': ' + percentage;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
        <?php endif; ?>
    </script>

</body>
</html>