<?php
require_once 'auth_check.php';

// --- 1. LOAD DATA ---
$fileRek = 'data/rekening.json';
$fileExp = 'data/pengeluaran.json';

$rekening = file_exists($fileRek) ? json_decode(file_get_contents($fileRek), true) : [];
$pengeluaran = file_exists($fileExp) ? json_decode(file_get_contents($fileExp), true) : [];

// --- 2. HITUNG TOTAL UANG KAS (CASH ON HAND) ---
$totalCash = 0;
foreach ($rekening as $rek) {
    // Hitung semua saldo bank & e-wallet
    $totalCash += $rek['saldo'];
}

// --- 3. HITUNG BURN RATE (RATA-RATA PENGELUARAN BULANAN) ---
$monthlyExpenses = [];
foreach ($pengeluaran as $exp) {
    // Filter: Jangan hitung "Investasi Saham" atau "Dividen" sebagai biaya operasional
    // Kita hanya hitung Gaji, Operasional, Belanja, dll.
    $kategori = $exp['kategori'] ?? '';
    
    // Abaikan kategori non-operasional jika perlu (opsional)
    if (stripos($kategori, 'Investasi') === false && stripos($kategori, 'Return') === false) {
        $bulan = date('Y-m', strtotime($exp['tanggal']));
        if (!isset($monthlyExpenses[$bulan])) {
            $monthlyExpenses[$bulan] = 0;
        }
        $monthlyExpenses[$bulan] += (int)$exp['jumlah'];
    }
}

// Hitung Rata-rata 3 Bulan Terakhir (biar akurat)
$countMonth = count($monthlyExpenses);
$totalExpAll = array_sum($monthlyExpenses);
$burnRate = ($countMonth > 0) ? $totalExpAll / $countMonth : 0;

// Safety check biar gak bagi nol
if ($burnRate == 0) $burnRate = 1; 

// --- 4. HITUNG RUNWAY ---
$runwayMonths = $totalCash / $burnRate;

// Tentukan Status
if ($runwayMonths >= 6) {
    $status = "SANGAT AMAN 🛡️";
    $color = "#059669"; // Hijau Tua
    $desc = "Dana cadangan melimpah. Saatnya ekspansi buka cabang!";
} elseif ($runwayMonths >= 3) {
    $status = "AMAN ✅";
    $color = "#10b981"; // Hijau
    $desc = "Kondisi keuangan sehat. Pertahankan efisiensi.";
} elseif ($runwayMonths >= 1) {
    $status = "WASPADA ⚠️";
    $color = "#f59e0b"; // Kuning
    $desc = "Hati-hati! Jangan boros. Fokus genjot omzet.";
} else {
    $status = "BAHAYA 🚨";
    $color = "#dc2626"; // Merah
    $desc = "URGENT! Kas menipis. Segera cari investor atau potong biaya.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Runway - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f0fdfa; font-family: 'Manrope', sans-serif; }
        
        .runway-header {
            background: white; padding: 2rem; border-bottom: 1px solid #ccfbf1;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .container-run { max-width: 900px; margin: 3rem auto; padding: 0 1rem; }
        
        /* Main Card */
        .indicator-card {
            background: white; border-radius: 24px; padding: 3rem;
            box-shadow: 0 20px 40px -10px rgba(20, 184, 166, 0.2); border: 1px solid #99f6e4;
            text-align: center; position: relative; overflow: hidden;
        }
        
        .big-number { font-size: 5rem; font-weight: 800; color: <?= $color; ?>; line-height: 1; margin: 10px 0; }
        .unit { font-size: 1.5rem; font-weight: 600; color: #64748b; }
        
        .status-label {
            background: <?= $color; ?>; color: white; padding: 8px 20px; border-radius: 50px;
            font-weight: 700; letter-spacing: 1px; text-transform: uppercase; display: inline-block;
            margin-bottom: 1rem;
        }
        
        .desc-text { font-size: 1.1rem; color: #475569; max-width: 600px; margin: 0 auto; line-height: 1.6; }

        /* Detail Grid */
        .detail-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem;
        }
        .detail-box {
            background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;
            text-align: left;
        }
        .detail-title { font-size: 0.9rem; color: #64748b; font-weight: 600; margin-bottom: 5px; }
        .detail-val { font-size: 1.5rem; font-weight: 700; color: #0f172a; }

        .back-btn { text-decoration: none; color: #64748b; font-weight: 600; padding: 8px 16px; border-radius: 50px; background: #f1f5f9; }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body>

    <header class="runway-header">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #0f766e;">🛡️ Financial Runway</h1>
            <p style="color: #64748b;">Indikator Ketahanan Bisnis</p>
        </div>
        <a href="index.php" class="back-btn">← Dashboard</a>
    </header>

    <main class="container-run">
        
        <div class="indicator-card">
            <span class="status-label"><?= $status; ?></span>
            
            <div>
                <span class="big-number"><?= number_format($runwayMonths, 1, ',', '.'); ?></span>
                <span class="unit">Bulan</span>
            </div>
            
            <p class="desc-text"><?= $desc; ?></p>

            <div class="detail-grid">
                <div class="detail-box">
                    <div class="detail-title">💰 Total Uang Tunai (Cash)</div>
                    <div class="detail-val" style="color: #059669;">Rp <?= number_format($totalCash, 0, ',', '.'); ?></div>
                    <small style="color:#94a3b8;">Saldo Bank + Kas</small>
                </div>
                <div class="detail-box">
                    <div class="detail-title">🔥 Burn Rate (Biaya/Bulan)</div>
                    <div class="detail-val" style="color: #dc2626;">Rp <?= number_format($burnRate, 0, ',', '.'); ?></div>
                    <small style="color:#94a3b8;">Rata-rata pengeluaran</small>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: center; color: #94a3b8; font-size: 0.9rem;">
            *Perhitungan berdasarkan saldo saat ini dibagi rata-rata pengeluaran bulanan.
        </div>

    </main>

</body>
</html>