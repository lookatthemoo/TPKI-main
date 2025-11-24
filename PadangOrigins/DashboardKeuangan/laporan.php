<?php
require_once 'auth_check.php'; // Pastikan path ini benar sesuai struktur folder Anda

// --- KONFIGURASI & PATH ---
$baseDir = __DIR__; // Folder saat ini (DashboardKeuangan)
$dataDir = $baseDir . '/data/'; // Folder data
$configFile = $dataDir . 'laporan_config.json'; // File untuk simpan susunan widget

// Pastikan file config ada
if (!file_exists($configFile)) {
    file_put_contents($configFile, '[]');
}

// --- 1. HANDLE REQUEST (POST) ---
// Bagian ini menangani saat tombol Simpan atau Hapus ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configData = json_decode(file_get_contents($configFile), true) ?? [];
    $action = $_POST['action'] ?? '';

    // A. TAMBAH WIDGET BARU
    if ($action === 'tambah_widget') {
        $nama = htmlspecialchars($_POST['nama_laporan']);
        $sumber = $_POST['sumber_json'];

        $configData[] = [
            'name' => $nama,
            'source' => $sumber,
            'created_at' => date('Y-m-d H:i:s')
        ];

        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        header("Location: laporan.php"); // Refresh halaman
        exit;
    }

    // B. HAPUS WIDGET
    if ($action === 'hapus_widget') {
        $index = (int)$_POST['index'];
        if (isset($configData[$index])) {
            array_splice($configData, $index, 1);
            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        }
        header("Location: laporan.php"); // Refresh halaman
        exit;
    }
}

// --- 2. SCAN FILE JSON (UNTUK PILIHAN SUMBER DATA) ---
$jsonFiles = [];
if (is_dir($dataDir)) {
    $files = scandir($dataDir);
    foreach ($files as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'json') {
            // Sembunyikan file sistem agar tidak dipilih
            if ($f !== 'laporan_config.json' && $f !== 'user_config.json') { 
                $jsonFiles[] = $f;
            }
        }
    }
}

// --- 3. LOAD WIDGETS UNTUK DITAMPILKAN ---
$myReports = json_decode(file_get_contents($configFile), true) ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Modular</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* Layout Grid Widget */
        .widgets-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Desain Kartu Laporan */
        .report-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            animation: fadeIn 0.5s ease;
        }
        
        .report-header {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 1rem; border-bottom: 2px dashed #f1f5f9; margin-bottom: 1rem;
        }
        
        .report-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .file-badge { font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-weight: 600; }

        /* Tabel Dinamis */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th { background: #f8fafc; padding: 12px; text-align: left; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .data-table tr:hover td { background: #f8fafc; }

        /* Tombol & Modal */
        .btn-add { background: #2c3e50; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(44, 62, 80, 0.2); }
        .btn-delete { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem; opacity: 0.6; transition: 0.2s; }
        .btn-delete:hover { opacity: 1; transform: scale(1.1); }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-box { background: white; padding: 2rem; border-radius: 16px; width: 90%; max-width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: slideUp 0.3s; }
        @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">📊 Pusat Laporan</h1>
            <nav>
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div class="welcome-message" style="margin:0; text-align:left;">
                <h2 style="margin-bottom:5px;">Laporan Kustom</h2>
                <p style="color:#64748b;">Pantau data dari berbagai file JSON Anda di sini.</p>
            </div>
            <button onclick="document.getElementById('modalAdd').style.display='flex'" class="btn-add">
                + Tambah Laporan
            </button>
        </div>

        <div class="widgets-container">
            
            <?php if (empty($myReports)): ?>
                <div style="text-align:center; padding: 3rem; border: 2px dashed #e2e8f0; border-radius: 12px; color: #94a3b8;">
                    <h3>Belum ada laporan.</h3>
                    <p>Klik tombol <b>+ Tambah Laporan</b> untuk menampilkan data dari file JSON Anda.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($myReports as $index => $report): ?>
                    <?php
                    // Baca File JSON
                    $sourceFile = $dataDir . $report['source'];
                    $dataKonten = file_exists($sourceFile) ? json_decode(file_get_contents($sourceFile), true) : [];
                    ?>
                    
                    <div class="report-card">
                        <div class="report-header">
                            <div class="report-title">
                                <span><?= htmlspecialchars($report['name']) ?></span>
                                <span class="file-badge">📂 <?= htmlspecialchars($report['source']) ?></span>
                            </div>
                            
                            <form method="POST" onsubmit="return confirm('Hapus laporan ini dari tampilan?');">
                                <input type="hidden" name="action" value="hapus_widget">
                                <input type="hidden" name="index" value="<?= $index ?>">
                                <button type="submit" class="btn-delete" title="Hapus Laporan">🗑</button>
                            </form>
                        </div>

                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php 
                                        // Header Otomatis dari Key JSON
                                        if (!empty($dataKonten) && is_array($dataKonten)) {
                                            $firstItem = reset($dataKonten);
                                            if (is_array($firstItem)) {
                                                foreach (array_keys($firstItem) as $key) {
                                                    // Ubah 'nama_barang' jadi 'NAMA BARANG'
                                                    echo "<th>" . strtoupper(str_replace('_', ' ', $key)) . "</th>";
                                                }
                                            } else {
                                                echo "<th>DATA</th>";
                                            }
                                        } else {
                                            echo "<th>STATUS</th>";
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dataKonten) || !is_array($dataKonten)): ?>
                                        <tr><td colspan="100%" style="text-align:center; color:#94a3b8; padding:2rem;">File JSON kosong atau tidak valid.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($dataKonten as $row): ?>
                                            <tr>
                                                <?php if (is_array($row)): ?>
                                                    <?php foreach ($row as $cell): ?>
                                                        <td>
                                                            <?php 
                                                            if(is_array($cell)) {
                                                                echo '<span style="color:#ccc; font-size:0.8em;">[Array]</span>';
                                                            } elseif (is_numeric($cell) && $cell > 1000) {
                                                                echo number_format($cell, 0, ',', '.');
                                                            } else {
                                                                echo htmlspecialchars($cell);
                                                            }
                                                            ?>
                                                        </td>
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
                <h2 style="margin:0; color:#1e293b;">Tambah Laporan</h2>
                <span onclick="document.getElementById('modalAdd').style.display='none'" style="cursor:pointer; font-size:1.5rem;">×</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="tambah_widget">
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-weight:600; color:#475569; margin-bottom:5px;">Judul Laporan</label>
                    <input type="text" name="nama_laporan" required placeholder="Contoh: Stok Gudang Utama" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:600; color:#475569; margin-bottom:5px;">Pilih Data (JSON)</label>
                    <select name="sumber_json" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background:white;">
                        <option value="" disabled selected>-- Pilih File JSON --</option>
                        <?php foreach ($jsonFiles as $file): ?>
                            <option value="<?= $file ?>">📂 <?= $file ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#64748b;">*Mengambil data dari folder <code>/data/</code></small>
                </div>

                <button type="submit" class="btn-add" style="width:100%; background:#2563eb;">Simpan & Tampilkan</button>
            </form>
        </div>
    </div>

    <script>
        // Tutup modal jika klik di luar
        window.onclick = (e) => {
            if(e.target == document.getElementById('modalAdd')) {
                document.getElementById('modalAdd').style.display = 'none';
            }
        }
    </script>

</body>
</html>