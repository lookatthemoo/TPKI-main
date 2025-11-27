<?php
require_once 'auth_check.php';

// --- 1. LOAD DATA ---
$baseDir = __DIR__;
$dataDir = $baseDir . '/data/';

// Path File Data
$fileRek = $dataDir . 'rekening.json';
$fileExp = $dataDir . 'pengeluaran.json';

// Helper Load Data
function safeLoad($path) {
    return file_exists($path) ? json_decode(file_get_contents($path), true) ?? [] : [];
}

$rekening = safeLoad($fileRek);
$pengeluaran = safeLoad($fileExp);


// --- 2. HITUNG TOTAL UANG KAS (LIQUID ASSETS) ---
$totalCash = 0;
foreach ($rekening as $rek) {
    // Hitung semua saldo bank & e-wallet yang ada
    $totalCash += (int)($rek['saldo'] ?? 0);
}


// --- 3. HITUNG BURN RATE (RATA-RATA PENGELUARAN OPERASIONAL BULANAN) ---
$monthlyExpenses = [];

foreach ($pengeluaran as $exp) {
    $kategori = $exp['kategori'] ?? '';
    $jumlah = (int)($exp['jumlah'] ?? 0);
    
    // FILTER PENTING:
    // Jangan hitung "Investasi Saham", "Prive Owner", atau "Return Investasi" sebagai biaya operasional rutin.
    // Kita hanya mau menghitung "Biaya Hidup" restoran (Gaji, Listrik, Belanja Bahan, Sewa).
    $isInvestasi = stripos($kategori, 'Investasi') !== false;
    $isReturn = stripos($kategori, 'Return') !== false;
    $isPrive = stripos($kategori, 'Prive') !== false;
    
    if (!$isInvestasi && !$isReturn && !$isPrive) {
        // Ambil format YYYY-MM (Misal: 2023-11)
        $bulan = date('Y-m', strtotime($exp['tanggal']));
        
        if (!isset($monthlyExpenses[$bulan])) {
            $monthlyExpenses[$bulan] = 0;
        }
        $monthlyExpenses[$bulan] += $jumlah;
    }
}

// Hitung Rata-rata
$countMonth = count($monthlyExpenses);
$totalExpAll = array_sum($monthlyExpenses);

// Jika belum ada data pengeluaran sama sekali, set default burn rate 1 (biar gak error division by zero)
if ($countMonth > 0) {
    $burnRate = $totalExpAll / $countMonth;
} else {
    // Fallback: Jika data kosong, asumsi pengeluaran 0 (Runway Infinity)
    $burnRate = 0; 
}

// --- 4. HITUNG RUNWAY (SISA NAPAS) ---
if ($burnRate > 0) {
    $runwayMonths = $totalCash / $burnRate;
} else {
    $runwayMonths = 999; // Infinity (Aman banget)
}


// --- 5. TENTUKAN STATUS KESEHATAN ---
if ($runwayMonths >= 12) {
    $status = "SANGAT SEHAT 💎";
    $color = "#059669"; // Hijau Tua
    $desc = "Luar biasa! Cadangan kas cukup untuk operasional lebih dari setahun tanpa pemasukan.";
} elseif ($runwayMonths >= 6) {
    $status = "AMAN 🛡️";
    $color = "#10b981"; // Hijau
    $desc = "Posisi keuangan stabil. Dana cadangan cukup untuk 6 bulan ke depan.";
} elseif ($runwayMonths >= 3) {
    $status = "WASPADA ⚠️";
    $color = "#f59e0b"; // Kuning
    $desc = "Hati-hati. Fokus efisiensi dan tingkatkan omzet. Jangan belanja yang tidak perlu.";
} else {
    $status = "BAHAYA 🚨";
    $color = "#dc2626"; // Merah
    $desc = "URGENT! Kas sangat tipis (< 3 Bulan). Segera cari suntikan dana atau potong biaya drastis.";
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
        
        /* Angka Besar */
        .big-number { 
            font-size: 5rem; font-weight: 800; color: <?= $color; ?>; 
            line-height: 1; margin: 10px 0; 
        }
        .unit { font-size: 1.5rem; font-weight: 600; color: #64748b; }
        
        .status-label {
            background: <?= $color; ?>; color: white; padding: 8px 20px; border-radius: 50px;
            font-weight: 700; letter-spacing: 1px; text-transform: uppercase; display: inline-block;
            margin-bottom: 1rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .desc-text { font-size: 1.1rem; color: #475569; max-width: 600px; margin: 0 auto; line-height: 1.6; }

        /* Detail Grid */
        .detail-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 3rem;
        }
        .detail-box {
            background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;
            text-align: left; transition: transform 0.2s;
        }
        .detail-box:hover { transform: translateY(-5px); border-color: #cbd5e1; }
        
        .detail-title { font-size: 0.9rem; color: #64748b; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-val { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .detail-sub { font-size: 0.8rem; color: #94a3b8; margin-top: 5px; display: block; }

        .back-btn { text-decoration: none; color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 50px; background: #f1f5f9; transition: 0.2s; }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body>

    <header class="runway-header">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #0f766e;">🛡️ Financial Runway</h1>
            <p style="color: #64748b;">Indikator Ketahanan & Keamanan Bisnis</p>
        </div>
        <a href="index.php" class="back-btn">← Dashboard</a>
    </header>

    <main class="container-run">
        
        <div class="indicator-card">
            <span class="status-label"><?= $status; ?></span>
            
            <div>
                <?php if($runwayMonths >= 999): ?>
                    <span class="big-number">∞</span>
                    <span class="unit">Bulan (Aman)</span>
                <?php else: ?>
                    <span class="big-number"><?= number_format($runwayMonths, 1, ',', '.'); ?></span>
                    <span class="unit">Bulan</span>
                <?php endif; ?>
            </div>
            
            <p class="desc-text"><?= $desc; ?></p>

            <div class="detail-grid">
                <div class="detail-box" style="border-left: 5px solid #059669;">
                    <div class="detail-title">💰 Total Cash on Hand</div>
                    <div class="detail-val" style="color: #059669;">Rp <?= number_format($totalCash, 0, ',', '.'); ?></div>
                    <small class="detail-sub">Total saldo dari semua rekening & kas.</small>
                </div>
                
                <div class="detail-box" style="border-left: 5px solid #dc2626;">
                    <div class="detail-title">🔥 Monthly Burn Rate</div>
                    <div class="detail-val" style="color: #dc2626;">Rp <?= number_format($burnRate, 0, ',', '.'); ?></div>
                    <small class="detail-sub">Rata-rata biaya operasional per bulan.</small>
                </div>
            </div>
        </div>

        <div style="margin-top: 3rem; text-align: center; color: #94a3b8; font-size: 0.85rem; line-height: 1.6;">
            *<b>Burn Rate</b> dihitung dari rata-rata pengeluaran rutin bulanan (tidak termasuk investasi saham/aset).<br>
            *<b>Runway</b> adalah estimasi berapa lama bisnis bisa bertahan tanpa adanya pemasukan baru.
        </div>

    </main>

</body>
</html>