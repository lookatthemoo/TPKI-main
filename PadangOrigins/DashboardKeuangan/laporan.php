<?php
require_once 'auth_check.php';

// --- KONFIGURASI & PATH ---
$baseDir = __DIR__;
$dataDir = $baseDir . '/data/';
$configFile = $dataDir . 'laporan_config.json';
$fileTrx = $dataDir . 'transaksi.json';
$fileExp = $dataDir . 'pengeluaran.json';

// Pastikan file config ada
if (!file_exists($configFile)) file_put_contents($configFile, '[]');

// Load Data Mentah
$transaksi = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];
$pengeluaran = file_exists($fileExp) ? json_decode(file_get_contents($fileExp), true) : [];

// =========================================
// 1. LOGIKA TREND LABA RUGI (30 HARI)
// =========================================
$dailyData = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dailyData[$d] = ['income' => 0, 'expense' => 0];
}

// Hitung Income (Transaksi)
foreach ($transaksi as $t) {
    if ($t['tipe'] === 'pendapatan') {
        $d = date('Y-m-d', strtotime($t['tanggal']));
        if (isset($dailyData[$d])) $dailyData[$d]['income'] += $t['jumlah'];
    }
}
// Hitung Expense (Pengeluaran)
foreach ($pengeluaran as $e) {
    $d = date('Y-m-d', strtotime($e['tanggal']));
    if (isset($dailyData[$d])) $dailyData[$d]['expense'] += $e['jumlah'];
}

// Siapkan Data Chart 1 (Line Chart)
$trendLabels = []; $trendIncome = []; $trendExpense = [];
foreach ($dailyData as $date => $val) {
    $trendLabels[] = date('d M', strtotime($date));
    $trendIncome[] = $val['income'];
    $trendExpense[] = $val['expense'];
}
$jsTrendLabels = json_encode($trendLabels);
$jsTrendIncome = json_encode($trendIncome);
$jsTrendExpense = json_encode($trendExpense);


// =========================================
// 2. LOGIKA PROPORSI PENGELUARAN (BULAN INI)
// =========================================
$currentMonth = date('Y-m');
$totalExpMonth = 0;
$catTotals = [];

foreach ($pengeluaran as $exp) {
    if (strpos($exp['tanggal'], $currentMonth) === 0) {
        $amt = (int)$exp['jumlah'];
        $cat = $exp['kategori'] ?? 'Lain-lain';
        $totalExpMonth += $amt;
        if (!isset($catTotals[$cat])) $catTotals[$cat] = 0;
        $catTotals[$cat] += $amt;
    }
}
arsort($catTotals); // Urutkan terbesar
$jsExpLabels = json_encode(array_keys($catTotals));
$jsExpValues = json_encode(array_values($catTotals));


// =========================================
// 3. LOGIKA WIDGET KUSTOM (MODULAR)
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configData = json_decode(file_get_contents($configFile), true) ?? [];
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_widget') {
        $configData[] = [
            'name' => htmlspecialchars($_POST['nama_laporan']),
            'source' => $_POST['sumber_json'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        header("Location: laporan.php"); exit;
    }
    if ($action === 'hapus_widget') {
        $idx = (int)$_POST['index'];
        if (isset($configData[$idx])) {
            array_splice($configData, $idx, 1);
            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        }
        header("Location: laporan.php"); exit;
    }
}

// Load List JSON untuk dropdown
$jsonFiles = [];
if (is_dir($dataDir)) {
    $scanned = scandir($dataDir);
    foreach ($scanned as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'json' && $f !== 'laporan_config.json' && $f !== 'user_config.json') {
            $jsonFiles[] = $f;
        }
    }
}
$myReports = json_decode(file_get_contents($configFile), true) ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Report - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Layout Tweaks */
        .container { max-width: 1200px; margin: 0 auto; padding-top: 2rem; padding-bottom: 6rem; }
        
        /* Grid Utama */
        .top-section { margin-bottom: 3rem; }
        
        /* Card Styling */
        .chart-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
            margin-bottom: 1.5rem; position: relative;
        }
        .chart-title { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        
        /* Row untuk 2 Chart */
        .charts-row {
            display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;
        }
        
        /* Summary Box */
        .summary-box {
            background: linear-gradient(135deg, #0f172a, #334155); color: white;
            padding: 2rem; border-radius: 16px; text-align: center;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
            margin-bottom: 1.5rem;
        }
        .summary-box h2 { font-size: 2.5rem; margin: 10px 0; font-weight: 800; color: #f8fafc; }

        /* Divider */
        .divider { border-top: 2px dashed #e2e8f0; margin: 3rem 0; position: relative; }
        .divider::after { 
            content: 'DATA MONITORING (CUSTOM)'; background: #f4f7f9; color: #94a3b8; padding: 0 15px; font-size: 0.8rem; font-weight: 700;
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        }

        /* Custom Reports */
        .widgets-container { display: flex; flex-direction: column; gap: 2rem; }
        .report-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
        }
        .report-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 2px dashed #f1f5f9; margin-bottom: 1rem; }
        .file-badge { font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-weight: 600; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th { background: #f8fafc; padding: 12px; text-align: left; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        
        /* Buttons */
        .btn-add { background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-add:hover { background: #1d4ed8; transform: translateY(-2px); }
        .btn-delete { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem; opacity: 0.6; }
        .btn-delete:hover { opacity: 1; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-box { background: white; padding: 2rem; border-radius: 16px; width: 420px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }

        @media (max-width: 1024px) { .charts-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">📊 Laporan Eksekutif</h1>
            <nav>
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <div class="top-section">
            
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-title">📈 Trend Laba Rugi (30 Hari)</div>
                    <div style="height: 350px; width: 100%;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div class="summary-box">
                        <span style="opacity:0.8; font-size:0.9rem;">Total Pengeluaran (<?= date('M'); ?>)</span>
                        <h2>Rp <?= number_format($totalExpMonth, 0, ',', '.'); ?></h2>
                        <small style="background:rgba(255,255,255,0.1); padding:3px 10px; border-radius:20px;">Cash Flow Control</small>
                    </div>

                    <div class="chart-card" style="flex: 1;">
                        <div class="chart-title">📉 Proporsi Biaya</div>
                        <div style="height: 200px; position: relative;">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="divider"></div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h2 style="margin:0; color:#1e293b;">Laporan Kustom</h2>
                <p style="margin:0; color:#64748b; font-size:0.9rem;">Monitoring data spesifik dari database.</p>
            </div>
            <button onclick="document.getElementById('modalAdd').style.display='flex'" class="btn-add">
                + Tambah Widget
            </button>
        </div>

        <div class="widgets-container">
            <?php if (empty($myReports)): ?>
                <div style="text-align:center; padding: 4rem; border: 2px dashed #e2e8f0; border-radius: 16px; color: #94a3b8;">
                    <h3>Belum ada laporan tambahan.</h3>
                    <p>Butuh melihat data <b>Stok Gudang</b> atau <b>Daftar Karyawan</b> di sini?<br>Klik tombol <b>+ Tambah Widget</b> di atas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($myReports as $index => $report): ?>
                    <?php
                    $sourceFile = $dataDir . $report['source'];
                    $dataKonten = file_exists($sourceFile) ? json_decode(file_get_contents($sourceFile), true) : [];
                    ?>
                    <div class="report-card">
                        <div class="report-header">
                            <div class="report-title">
                                <span><?= htmlspecialchars($report['name']) ?></span>
                                <span class="file-badge">📂 <?= htmlspecialchars($report['source']) ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Hapus widget ini?');" style="margin:0;">
                                <input type="hidden" name="action" value="hapus_widget">
                                <input type="hidden" name="index" value="<?= $index ?>">
                                <button type="submit" class="btn-delete" title="Hapus Widget">🗑</button>
                            </form>
                        </div>

                        <div style="overflow-x:auto; max-height: 400px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php 
                                        if (!empty($dataKonten) && is_array($dataKonten)) {
                                            $firstItem = reset($dataKonten);
                                            if (is_array($firstItem)) {
                                                foreach (array_keys($firstItem) as $key) {
                                                    if($key !== 'id') echo "<th>" . strtoupper(str_replace('_', ' ', $key)) . "</th>";
                                                }
                                            } else { echo "<th>DATA</th>"; }
                                        } else { echo "<th>STATUS</th>"; }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dataKonten) || !is_array($dataKonten)): ?>
                                        <tr><td colspan="100%" style="text-align:center; color:#94a3b8; padding:2rem;">Data Kosong.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($dataKonten, 0, 20) as $row): ?>
                                            <tr>
                                                <?php if (is_array($row)): ?>
                                                    <?php foreach ($row as $k => $cell): ?>
                                                        <?php if($k !== 'id'): ?>
                                                            <td>
                                                                <?php 
                                                                if(is_array($cell)) echo '<span style="color:#ccc;">[Array]</span>';
                                                                elseif (is_numeric($cell) && $cell > 1000) echo number_format($cell, 0, ',', '.');
                                                                else echo htmlspecialchars($cell);
                                                                ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <td><?= htmlspecialchars($row) ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <div id="modalAdd" class="modal-overlay">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0; color:#1e293b;">Tambah Widget</h2>
                <span onclick="document.getElementById('modalAdd').style.display='none'" style="cursor:pointer; font-size:1.5rem;">×</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah_widget">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-weight:600; color:#475569; margin-bottom:5px;">Judul Laporan</label>
                    <input type="text" name="nama_laporan" required placeholder="Contoh: Stok Gudang" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:600; color:#475569; margin-bottom:5px;">Pilih Data (JSON)</label>
                    <select name="sumber_json" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background:white;">
                        <option value="" disabled selected>-- Pilih File --</option>
                        <?php foreach ($jsonFiles as $file): ?>
                            <option value="<?= $file ?>">📂 <?= $file ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-add" style="width:100%;">Simpan Widget</button>
            </form>
        </div>
    </div>

    <script>
        // 1. Chart Trend (Line)
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        let gradGreen = ctxTrend.createLinearGradient(0, 0, 0, 400);
        gradGreen.addColorStop(0, 'rgba(22, 163, 74, 0.2)'); gradGreen.addColorStop(1, 'rgba(22, 163, 74, 0.0)');
        let gradRed = ctxTrend.createLinearGradient(0, 0, 0, 400);
        gradRed.addColorStop(0, 'rgba(220, 38, 38, 0.2)'); gradRed.addColorStop(1, 'rgba(220, 38, 38, 0.0)');

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= $jsTrendLabels; ?>,
                datasets: [
                    {
                        label: 'Pemasukan', data: <?= $jsTrendIncome; ?>,
                        borderColor: '#16a34a', backgroundColor: gradGreen,
                        borderWidth: 3, tension: 0.4, fill: true, pointRadius: 0, pointHoverRadius: 6
                    },
                    {
                        label: 'Pengeluaran', data: <?= $jsTrendExpense; ?>,
                        borderColor: '#dc2626', backgroundColor: gradRed,
                        borderWidth: 3, tension: 0.4, fill: true, pointRadius: 0, pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Chart Expense (Doughnut)
        <?php if($totalExpMonth > 0): ?>
        const ctxExp = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctxExp, {
            type: 'doughnut',
            data: {
                labels: <?= $jsExpLabels; ?>,
                datasets: [{
                    data: <?= $jsExpValues; ?>,
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#10b981', '#3b82f6', '#6366f1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } },
                cutout: '65%'
            }
        });
        <?php endif; ?>

        // Modal Logic
        window.onclick = (e) => {
            if(e.target == document.getElementById('modalAdd')) document.getElementById('modalAdd').style.display = 'none';
        }
    </script>

</body>
</html>