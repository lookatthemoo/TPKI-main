<?php
require_once 'auth_check.php';

// --- KONFIGURASI & PATH ---
$baseDir = __DIR__;
$dataDir = $baseDir . '/data/';
$configFile = $dataDir . 'laporan_config.json';
$fileExp = $dataDir . 'pengeluaran.json'; // File untuk analisa chart

// Pastikan file config ada
if (!file_exists($configFile)) {
    file_put_contents($configFile, '[]');
}

// =========================================
// BAGIAN 1: LOGIKA CHART ANALISA PENGELUARAN
// =========================================
$pengeluaran = file_exists($fileExp) ? json_decode(file_get_contents($fileExp), true) : [];
$currentMonth = date('Y-m');
$totalBulanIni = 0;
$catTotals = [];

foreach ($pengeluaran as $exp) {
    // Filter Data Bulan Ini
    if (isset($exp['tanggal']) && strpos($exp['tanggal'], $currentMonth) === 0) {
        $jumlah = (int)($exp['jumlah'] ?? 0);
        $kategori = $exp['kategori'] ?? 'Lain-lain';
        
        $totalBulanIni += $jumlah;
        
        if (!isset($catTotals[$kategori])) {
            $catTotals[$kategori] = 0;
        }
        $catTotals[$kategori] += $jumlah;
    }
}

// Urutkan kategori dari terbesar
arsort($catTotals);

// Siapkan Data untuk Chart.js
$jsLabels = json_encode(array_keys($catTotals));
$jsValues = json_encode(array_values($catTotals));


// =========================================
// BAGIAN 2: LOGIKA LAPORAN MODULAR (CUSTOM)
// =========================================

// Handle Tambah/Hapus Widget
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configData = json_decode(file_get_contents($configFile), true) ?? [];
    $action = $_POST['action'] ?? '';

    // A. TAMBAH WIDGET
    if ($action === 'tambah_widget') {
        $nama = htmlspecialchars($_POST['nama_laporan']);
        $sumber = $_POST['sumber_json'];

        $configData[] = [
            'name' => $nama,
            'source' => $sumber,
            'created_at' => date('Y-m-d H:i:s')
        ];

        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        header("Location: laporan.php"); 
        exit;
    }

    // B. HAPUS WIDGET
    if ($action === 'hapus_widget') {
        $index = (int)$_POST['index'];
        if (isset($configData[$index])) {
            array_splice($configData, $index, 1);
            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        }
        header("Location: laporan.php"); 
        exit;
    }
}

// Scan File JSON untuk Pilihan
$jsonFiles = [];
if (is_dir($dataDir)) {
    $files = scandir($dataDir);
    foreach ($files as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'json') {
            if ($f !== 'laporan_config.json' && $f !== 'user_config.json') { 
                $jsonFiles[] = $f;
            }
        }
    }
}

// Load Widget Tersimpan
$myReports = json_decode(file_get_contents($configFile), true) ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Lengkap & Analisa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Layout Utama */
        .container { max-width: 1200px; margin: 0 auto; padding-top: 2rem; padding-bottom: 6rem; }
        
        /* Grid Analisa (Bagian Atas) */
        .analytics-section {
            display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; margin-bottom: 3rem;
        }
        
        .chart-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #e11d48, #be123c); color: white;
            padding: 2rem; border-radius: 16px; text-align: center;
            box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.4);
            margin-bottom: 1.5rem; position: relative; overflow: hidden;
        }
        .summary-box h2 { font-size: 2.2rem; margin: 10px 0; font-weight: 800; }
        
        /* Section Divider */
        .divider { border-top: 2px dashed #e2e8f0; margin: 2rem 0; position: relative; }
        .divider::after { 
            content: 'DATA MENTAH (JSON)'; background: #f4f7f9; color: #94a3b8; padding: 0 15px; font-size: 0.8rem; font-weight: 700;
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        }

        /* Layout Laporan Kustom (Bagian Bawah) */
        .widgets-container { display: flex; flex-direction: column; gap: 2rem; }
        .report-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
            animation: fadeIn 0.5s ease;
        }
        .report-header {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 1rem; border-bottom: 2px dashed #f1f5f9; margin-bottom: 1rem;
        }
        .report-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .file-badge { font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-weight: 600; }

        /* Tabel */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th { background: #f8fafc; padding: 12px; text-align: left; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        
        /* Buttons & Modal */
        .btn-add { background: #2c3e50; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
        .btn-delete { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem; opacity: 0.6; }
        .btn-delete:hover { opacity: 1; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-box { background: white; padding: 2rem; border-radius: 16px; width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
        @media (max-width: 1024px) { .analytics-section { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">📊 Laporan Lengkap</h1>
            <nav>
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <div class="analytics-section">
            <div class="chart-card">
                <h3 style="margin:0 0 1.5rem 0; color:#1e293b;">📉 Proporsi Biaya (<?= date('F Y'); ?>)</h3>
                <?php if($totalBulanIni > 0): ?>
                    <div style="height: 280px; position: relative;">
                        <canvas id="expenseChart"></canvas>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; color:#94a3b8; padding:4rem;">Belum ada data pengeluaran bulan ini.</p>
                <?php endif; ?>
            </div>

            <div>
                <div class="summary-box">
                    <span style="opacity:0.9; font-size:0.9rem;">Total Pengeluaran Bulan Ini</span>
                    <h2>Rp <?= number_format($totalBulanIni, 0, ',', '.'); ?></h2>
                    <small>Jaga cashflow tetap positif!</small>
                </div>

                <div class="chart-card">
                    <h4 style="margin:0 0 1rem 0; color:#64748b; font-size:0.9rem;">KATEGORI TERBESAR</h4>
                    <ul style="list-style:none; padding:0; margin:0; font-size:0.9rem;">
                        <?php 
                        $colors = ['#e11d48', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
                        $i = 0;
                        foreach(array_slice($catTotals, 0, 5) as $cat => $val): 
                            $pct = ($val / $totalBulanIni) * 100;
                        ?>
                            <li style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #eee;">
                                <span style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:10px; height:10px; border-radius:50%; background:<?= $colors[$i % 5]; ?>"></span>
                                    <?= $cat; ?>
                                </span>
                                <b><?= number_format($pct, 1); ?>%</b>
                            </li>
                        <?php $i++; endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div class="welcome-message" style="margin:0; text-align:left;">
                <h2 style="margin-bottom:5px; color:#1e293b;">📑 Laporan Kustom</h2>
                <p style="color:#64748b;">Tampilkan data mentah dari database JSON Anda.</p>
            </div>
            <button onclick="document.getElementById('modalAdd').style.display='flex'" class="btn-add">
                + Tambah Widget
            </button>
        </div>

        <div class="widgets-container">
            <?php if (empty($myReports)): ?>
                <div style="text-align:center; padding: 3rem; border: 2px dashed #e2e8f0; border-radius: 12px; color: #94a3b8;">
                    <h3>Belum ada laporan tambahan.</h3>
                    <p>Klik tombol <b>+ Tambah Widget</b> untuk memonitor Inventory, Menu, atau Karyawan di sini.</p>
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
                            
                            <form method="POST" onsubmit="return confirm('Hapus laporan ini?');" style="margin:0;">
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
                                                    if($key !== 'id') // Sembunyikan ID biar rapi
                                                        echo "<th>" . strtoupper(str_replace('_', ' ', $key)) . "</th>";
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

                <button type="submit" class="btn-add" style="width:100%; background:#2563eb;">Simpan & Tampilkan</button>
            </form>
        </div>
    </div>

    <script>
        <?php if($totalBulanIni > 0): ?>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= $jsLabels; ?>,
                datasets: [{
                    data: <?= $jsValues; ?>,
                    backgroundColor: ['#e11d48', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } // Legend custom di HTML
                },
                cutout: '70%'
            }
        });
        <?php endif; ?>

        window.onclick = (e) => {
            if(e.target == document.getElementById('modalAdd')) {
                document.getElementById('modalAdd').style.display = 'none';
            }
        }
    </script>

</body>
</html>