<?php
require_once 'auth_check.php';

// --- PERBAIKAN KONFIGURASI PATH ---
// Gunakan __DIR__ agar mengarah ke folder saat ini (DashboardKeuangan)
$baseDir = __DIR__; 
$dataDir = $baseDir . '/data/'; // Folder data ada di dalam DashboardKeuangan

$configFile = $dataDir . 'laporan_config.json';
$fileTrx = $dataDir . 'transaksi.json';
$fileExp = $dataDir . 'pengeluaran.json';

// Pastikan folder data ada sebelum menulis file
if (!is_dir($dataDir)) {
    // Jika folder data tidak ada, buat manual (opsional, untuk jaga-jaga)
    mkdir($dataDir, 0777, true);
}

// Pastikan file config ada
if (!file_exists($configFile)) {
    file_put_contents($configFile, '[]');
}

// Load Data Mentah
function safeLoad($path) {
    return file_exists($path) ? json_decode(file_get_contents($path), true) ?? [] : [];
}

$transaksi = safeLoad($fileTrx);
$pengeluaran = safeLoad($fileExp);
$myReports = safeLoad($configFile);

// =========================================
// 1. LOGIKA KPI BULAN INI (INCOME & EXPENSE)
// =========================================
$currentMonth = date('Y-m');
$totalIncomeMonth = 0;
$totalExpMonth = 0;
$catTotals = [];

// Hitung Pemasukan (Income) Bulan Ini
foreach ($transaksi as $t) {
    if ($t['tipe'] === 'pendapatan' && strpos($t['tanggal'], $currentMonth) === 0) {
        $totalIncomeMonth += (int)$t['jumlah'];
    }
}

// Hitung Pengeluaran (Expense) Bulan Ini
foreach ($pengeluaran as $exp) {
    if (strpos($exp['tanggal'], $currentMonth) === 0) {
        $amt = (int)$exp['jumlah'];
        $cat = $exp['kategori'] ?? 'Lain-lain';
        
        $totalExpMonth += $amt;
        
        if (!isset($catTotals[$cat])) $catTotals[$cat] = 0;
        $catTotals[$cat] += $amt;
    }
}

// Urutkan Kategori Pengeluaran
arsort($catTotals);
$jsExpLabels = json_encode(array_keys($catTotals));
$jsExpValues = json_encode(array_values($catTotals));


// =========================================
// 2. LOGIKA TREND CHART (30 HARI)
// =========================================
$dailyData = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dailyData[$d] = ['income' => 0, 'expense' => 0];
}

foreach ($transaksi as $t) {
    if ($t['tipe'] === 'pendapatan') {
        $d = date('Y-m-d', strtotime($t['tanggal']));
        if (isset($dailyData[$d])) $dailyData[$d]['income'] += (int)$t['jumlah'];
    }
}
foreach ($pengeluaran as $e) {
    $d = date('Y-m-d', strtotime($e['tanggal']));
    if (isset($dailyData[$d])) $dailyData[$d]['expense'] += (int)$e['jumlah'];
}

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
// 3. LOGIKA WIDGET KUSTOM
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_widget') {
        $myReports[] = [
            'name' => htmlspecialchars($_POST['nama_laporan']),
            'source' => $_POST['sumber_json'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($configFile, json_encode($myReports, JSON_PRETTY_PRINT));
        header("Location: laporan.php"); exit;
    }
    if ($action === 'hapus_widget') {
        $idx = (int)$_POST['index'];
        if (isset($myReports[$idx])) {
            array_splice($myReports, $idx, 1);
            file_put_contents($configFile, json_encode($myReports, JSON_PRETTY_PRINT));
        }
        header("Location: laporan.php"); exit;
    }
}

// Scan File JSON
$jsonFiles = [];
if (is_dir($dataDir)) {
    foreach (scandir($dataDir) as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'json' && !in_array($f, ['laporan_config.json', 'user_config.json'])) {
            $jsonFiles[] = $f;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Eksekutif - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg: #f8fafc; --text: #1e293b; --primary: #0f172a; --card: #ffffff; }
        body { background: var(--bg); font-family: 'Manrope', sans-serif; color: var(--text); margin:0; padding-bottom:60px; }

        /* Header */
        .navbar { background: white; padding: 1.2rem 2rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; position:sticky; top:0; z-index:50; }
        .logo { font-size: 1.3rem; font-weight: 800; color: var(--primary); margin:0; }
        .nav-link { text-decoration: none; color: #64748b; font-weight: 600; transition:0.2s; font-size: 0.9rem; }
        .nav-link:hover { color: var(--primary); }

        /* Container Lebar */
        .container { 
            width: 95%; 
            max-width: 1600px; 
            margin: 2rem auto; 
            padding: 0 1rem; 
        }

        /* KPI GRID (2 Kolom Seimbang) */
        .kpi-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        .kpi-card { 
            background: white; padding: 2rem; border-radius: 16px; 
            border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            display: flex; align-items: center; gap: 1.5rem;
        }
        .kpi-icon { width: 64px; height: 64px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .kpi-info h4 { margin: 0 0 8px 0; color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .kpi-info h2 { margin: 0; font-size: 2rem; font-weight: 800; color: #0f172a; }
        
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-red { background: #fee2e2; color: #dc2626; }

        /* CHARTS GRID */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 3rem; }
        .chart-card { 
            background: white; border-radius: 16px; padding: 1.5rem; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
            display: flex; flex-direction: column;
        }
        .chart-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; }

        /* Divider */
        .divider { border-top: 2px dashed #cbd5e1; margin: 3rem 0; position: relative; }
        .divider::after { 
            content: 'DATA MONITORING'; background: var(--bg); color: #64748b; padding: 0 15px; 
            font-size: 0.8rem; font-weight: 700; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        }

        /* Widgets */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.2rem; font-weight: 800; color: #1e293b; }

        .widgets-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .widget-card { background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #f8fafc; padding-bottom: 0.8rem; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .data-table th { background: #f8fafc; padding: 12px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .data-table tr:last-child td { border-bottom: none; }

        .btn-add { background: #0f172a; color: white; padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
        .btn-del { color: #ef4444; background: none; border: none; cursor: pointer; opacity: 0.6; font-size: 1.1rem; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: white; padding: 2rem; border-radius: 16px; width: 400px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 1rem; }

        @media (max-width: 1024px) { 
            .kpi-grid, .charts-row, .widgets-grid { grid-template-columns: 1fr; } 
            .container { width: 100%; padding: 1rem; }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">📊 Laporan Eksekutif</div>
        <nav>
            <a href="index.php" class="nav-link">← Kembali ke Dashboard</a>
        </nav>
    </header>

    <main class="container">
        
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon icon-green">💰</div>
                <div class="kpi-info">
                    <h4>Total Pemasukan (<?= date('M'); ?>)</h4>
                    <h2>Rp <?= number_format($totalIncomeMonth, 0, ',', '.'); ?></h2>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon icon-red">💸</div>
                <div class="kpi-info">
                    <h4>Total Pengeluaran (<?= date('M'); ?>)</h4>
                    <h2>Rp <?= number_format($totalExpMonth, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-title">📈 Trend Keuangan (30 Hari)</div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-title">📉 Komposisi Biaya</div>
                <div style="height: 220px; position: relative;">
                    <?php if($totalExpMonth > 0): ?>
                        <canvas id="expenseChart"></canvas>
                    <?php else: ?>
                        <p style="text-align:center; color:#94a3b8; padding-top:80px;">Belum ada pengeluaran.</p>
                    <?php endif; ?>
                </div>
                <div style="text-align:center; margin-top:15px; font-size:0.85rem; color:#64748b;">
                    Kategori Terbesar: <b><?= !empty($catTotals) ? array_key_first($catTotals) : '-'; ?></b>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="section-header">
            <div class="section-title">📑 Laporan Modular</div>
            <button onclick="document.getElementById('modalAdd').style.display='flex'" class="btn-add">
                + Widget Baru
            </button>
        </div>

        <div class="widgets-grid">
            <?php if (empty($myReports)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem; border: 2px dashed #cbd5e1; border-radius: 16px; color: #94a3b8;">
                    <p>Belum ada widget laporan tambahan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($myReports as $index => $report): ?>
                    <?php $dataKonten = file_exists($dataDir . $report['source']) ? json_decode(file_get_contents($dataDir . $report['source']), true) : []; ?>
                    <div class="widget-card">
                        <div class="widget-header">
                            <div>
                                <b style="color:#1e293b;"><?= htmlspecialchars($report['name']) ?></b>
                                <span style="font-size:0.7rem; background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#64748b; margin-left:8px;"><?= $report['source'] ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Hapus?');" style="margin:0;">
                                <input type="hidden" name="action" value="hapus_widget">
                                <input type="hidden" name="index" value="<?= $index ?>">
                                <button type="submit" class="btn-del">×</button>
                            </form>
                        </div>
                        <div style="overflow-x:auto; max-height:250px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php if(!empty($dataKonten) && is_array(reset($dataKonten))): ?>
                                            <?php foreach(array_keys(reset($dataKonten)) as $k): if($k!=='id'): ?>
                                                <th><?= strtoupper(str_replace('_',' ',$k)); ?></th>
                                            <?php endif; endforeach; ?>
                                        <?php else: ?><th>DATA</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(array_slice($dataKonten, 0, 10) as $row): ?>
                                        <tr>
                                            <?php if(is_array($row)): foreach($row as $k=>$v): if($k!=='id'): ?>
                                                <td><?= (is_numeric($v) && $v>1000) ? number_format($v) : (is_array($v)?'Array':htmlspecialchars($v)); ?></td>
                                            <?php endif; endforeach; else: ?>
                                                <td><?= htmlspecialchars($row); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
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
            <h3 style="margin-top:0;">Tambah Widget</h3>
            <form method="POST">
                <input type="hidden" name="action" value="tambah_widget">
                <label style="font-weight:600; font-size:0.9rem; color:#64748b;">Judul Laporan</label>
                <input type="text" name="nama_laporan" required class="form-input" placeholder="Contoh: Stok Gudang">
                
                <label style="font-weight:600; font-size:0.9rem; color:#64748b;">Sumber Data (JSON)</label>
                <select name="sumber_json" required class="form-input" style="background:white;">
                    <?php foreach ($jsonFiles as $file): ?>
                        <option value="<?= $file ?>"><?= $file ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-add" style="width:100%; justify-content:center; padding:12px;">Simpan Widget</button>
                <div style="text-align:center; margin-top:10px; cursor:pointer; color:#64748b; font-size:0.9rem;" onclick="document.getElementById('modalAdd').style.display='none'">Batal</div>
            </form>
        </div>
    </div>

    <script>
        // 1. Trend Chart
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
                        borderWidth: 2, tension: 0.3, fill: true, pointRadius: 0
                    },
                    {
                        label: 'Pengeluaran', data: <?= $jsTrendExpense; ?>,
                        borderColor: '#dc2626', backgroundColor: gradRed,
                        borderWidth: 2, tension: 0.3, fill: true, pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: { x: { grid: { display: false } }, y: { grid: { borderDash: [5, 5] } } }
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
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#10b981', '#3b82f6', '#6366f1', '#a855f7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } },
                cutout: '70%'
            }
        });
        <?php endif; ?>

        window.onclick = (e) => {
            if(e.target == document.getElementById('modalAdd')) document.getElementById('modalAdd').style.display = 'none';
        }
    </script>

</body>
</html>