<?php
require_once 'auth_check.php';

// --- 1. LOAD DATA ---
$fileMenu = 'data/menu.json';
$fileTrx  = 'data/transaksi.json';

$menus = file_exists($fileMenu) ? json_decode(file_get_contents($fileMenu), true) : [];
$transaksi = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];

// --- 2. HITUNG PERFORMA MENU ---
$menuPerformance = [];
$totalQtySold = 0;
$totalMargin = 0;

// Inisialisasi Data Menu
foreach ($menus as $m) {
    // Estimasi HPP = 60% dari Harga Jual (Standar Resto Padang)
    $hpp = $m['harga'] * 0.6;
    $margin = $m['harga'] - $hpp;

    $menuPerformance[$m['nama']] = [
        'nama' => $m['nama'],
        'harga' => $m['harga'],
        'hpp' => $hpp,
        'margin_per_unit' => $margin,
        'qty_sold' => 0,
        'total_profit' => 0,
        'img' => $m['gambar'] ?? 'default.jpg'
    ];
}

// Hitung Penjualan dari Transaksi
foreach ($transaksi as $t) {
    if ($t['tipe'] === 'pendapatan' && isset($t['items'])) {
        foreach ($t['items'] as $item) {
            $namaMenu = $item['name'];
            $qty = (int)$item['qty'];

            if (isset($menuPerformance[$namaMenu])) {
                $menuPerformance[$namaMenu]['qty_sold'] += $qty;
                $menuPerformance[$namaMenu]['total_profit'] += ($menuPerformance[$namaMenu]['margin_per_unit'] * $qty);
                
                $totalQtySold += $qty;
            }
        }
    }
}

// --- 3. TENTUKAN THRESHOLD (BATAS KATEGORI) ---
$jumlahMenu = count($menuPerformance);
if ($jumlahMenu > 0) {
    $avgPopularity = $totalQtySold / $jumlahMenu; // Rata-rata Penjualan
    
    // Hitung Rata-rata Margin (Kontribusi)
    $sumMargin = 0;
    foreach($menuPerformance as $mp) $sumMargin += $mp['margin_per_unit'];
    $avgMargin = $sumMargin / $jumlahMenu;
} else {
    $avgPopularity = 0;
    $avgMargin = 0;
}

// --- 4. KATEGORISASI (BCG MATRIX) ---
$matrix = ['star' => [], 'cow' => [], 'puzzle' => [], 'dog' => []];

foreach ($menuPerformance as $mp) {
    $isHighPop = $mp['qty_sold'] >= $avgPopularity;
    $isHighMargin = $mp['margin_per_unit'] >= $avgMargin;

    if ($isHighPop && $isHighMargin) {
        $mp['cat'] = 'STAR';
        $mp['desc'] = 'Menu Primadona! Pertahankan kualitas & stok.';
        $matrix['star'][] = $mp;
    } elseif ($isHighPop && !$isHighMargin) {
        $mp['cat'] = 'CASH COW';
        $mp['desc'] = 'Laris tapi untung tipis. Coba naikkan harga sedikit.';
        $matrix['cow'][] = $mp;
    } elseif (!$isHighPop && $isHighMargin) {
        $mp['cat'] = 'PUZZLE';
        $mp['desc'] = 'Untung besar tapi jarang laku. Butuh promo/foto menarik!';
        $matrix['puzzle'][] = $mp;
    } else {
        $mp['cat'] = 'DOG';
        $mp['desc'] = 'Kurang laku & untung kecil. Pertimbangkan hapus menu.';
        $matrix['dog'][] = $mp;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Engineering - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f8fafc; font-family: 'Manrope', sans-serif; }
        
        .eng-header {
            background: white; padding: 2rem; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .back-btn { text-decoration: none; color: #64748b; font-weight: 600; padding: 8px 16px; background: #f1f5f9; border-radius: 50px; transition:0.2s; }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }

        .matrix-container {
            max-width: 1200px; margin: 2rem auto; padding: 0 1rem;
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
        }

        .matrix-card {
            background: white; border-radius: 20px; padding: 1.5rem;
            border: 1px solid #e2e8f0; min-height: 300px; display: flex; flex-direction: column;
            transition: 0.3s; position: relative; overflow: hidden;
        }
        .matrix-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }

        /* Warna Kategori */
        .card-star { border-top: 6px solid #f59e0b; background: linear-gradient(to bottom, #fffbeb, #fff); }
        .card-cow { border-top: 6px solid #10b981; background: linear-gradient(to bottom, #ecfdf5, #fff); }
        .card-puzzle { border-top: 6px solid #3b82f6; background: linear-gradient(to bottom, #eff6ff, #fff); }
        .card-dog { border-top: 6px solid #ef4444; background: linear-gradient(to bottom, #fef2f2, #fff); }

        .cat-icon { font-size: 3rem; position: absolute; top: 10px; right: 15px; opacity: 0.1; }
        .cat-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; color: #1e293b; }
        .cat-desc { font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem; font-weight: 500; }

        .menu-item {
            display: flex; align-items: center; gap: 10px; padding: 10px;
            background: rgba(255,255,255,0.6); border-radius: 12px;
            margin-bottom: 8px; border: 1px solid rgba(0,0,0,0.05); backdrop-filter: blur(5px);
        }
        .menu-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #ddd; }
        .menu-info { flex: 1; }
        .menu-name { font-weight: 700; font-size: 0.95rem; display: block; }
        .menu-stat { font-size: 0.8rem; color: #64748b; }
        .menu-profit { font-weight: 700; color: #16a34a; font-size: 0.85rem; }

        /* Responsive */
        @media (max-width: 768px) { .matrix-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="eng-header">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">🧩 Analisa menu</h1>
            <p style="color: #64748b; font-size: 0.9rem;">Analisa Profitabilitas & Popularitas</p>
        </div>
        <a href="index.php" class="back-btn">← Dashboard</a>
    </header>

    <main class="matrix-container">

        <div class="matrix-card card-star">
            <div class="cat-icon">🌟</div>
            <h2 class="cat-title" style="color:#b45309;">STARS</h2>
            <p class="cat-desc">Menu Paling Sempurna! Laris & Untung Gede.</p>
            
            <?php if(empty($matrix['star'])): ?>
                <p style="text-align:center; color:#999; margin-top:20px;">Belum ada menu di kategori ini.</p>
            <?php else: ?>
                <?php foreach($matrix['star'] as $m): ?>
                    <div class="menu-item">
                        <img src="../../menu/images/<?= $m['img']; ?>" class="menu-thumb" onerror="this.src='https://via.placeholder.com/40'">
                        <div class="menu-info">
                            <span class="menu-name"><?= $m['nama']; ?></span>
                            <span class="menu-stat">Terjual: <b><?= number_format($m['qty_sold']); ?></b></span>
                        </div>
                        <div class="menu-profit">+Rp <?= number_format($m['margin_per_unit']); ?>/porsi</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="matrix-card card-cow">
            <div class="cat-icon">🐮</div>
            <h2 class="cat-title" style="color:#047857;">CASH COWS</h2>
            <p class="cat-desc">Sapi Perah. Sangat laris, tapi margin tipis. <br><b>Saran:</b> Naikkan harga sedikit atau kurangi porsi.</p>
            
            <?php if(empty($matrix['cow'])): ?>
                <p style="text-align:center; color:#999; margin-top:20px;">Belum ada menu di kategori ini.</p>
            <?php else: ?>
                <?php foreach($matrix['cow'] as $m): ?>
                    <div class="menu-item">
                        <img src="../menu/images/<?= $m['img']; ?>" class="menu-thumb" onerror="this.src='https://via.placeholder.com/40'">
                        <div class="menu-info">
                            <span class="menu-name"><?= $m['nama']; ?></span>
                            <span class="menu-stat">Terjual: <b><?= number_format($m['qty_sold']); ?></b></span>
                        </div>
                        <div class="menu-profit" style="color:#059669;">+Rp <?= number_format($m['margin_per_unit']); ?>/porsi</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="matrix-card card-puzzle">
            <div class="cat-icon">🧩</div>
            <h2 class="cat-title" style="color:#1d4ed8;">PUZZLES</h2>
            <p class="cat-desc">Tanda Tanya. Untung gede, tapi jarang laku. <br><b>Saran:</b> Genjot promosi, foto ulang, atau diskon.</p>
            
            <?php if(empty($matrix['puzzle'])): ?>
                <p style="text-align:center; color:#999; margin-top:20px;">Belum ada menu di kategori ini.</p>
            <?php else: ?>
                <?php foreach($matrix['puzzle'] as $m): ?>
                    <div class="menu-item">
                        <img src="../../menu/images/<?= $m['img']; ?>" class="menu-thumb" onerror="this.src='https://via.placeholder.com/40'">
                        <div class="menu-info">
                            <span class="menu-name"><?= $m['nama']; ?></span>
                            <span class="menu-stat">Terjual: <b><?= number_format($m['qty_sold']); ?></b></span>
                        </div>
                        <div class="menu-profit" style="color:#2563eb;">+Rp <?= number_format($m['margin_per_unit']); ?>/porsi</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="matrix-card card-dog">
            <div class="cat-icon">🐕</div>
            <h2 class="cat-title" style="color:#b91c1c;">DOGS</h2>
            <p class="cat-desc">Beban. Sudah tidak laku, untungnya kecil pula. <br><b>Saran:</b> Hapus dari menu ganti yang baru.</p>
            
            <?php if(empty($matrix['dog'])): ?>
                <p style="text-align:center; color:#999; margin-top:20px;">Aman! Tidak ada menu beban.</p>
            <?php else: ?>
                <?php foreach($matrix['dog'] as $m): ?>
                    <div class="menu-item">
                        <img src="../../menu/images/<?= $m['img']; ?>" class="menu-thumb" onerror="this.src='https://via.placeholder.com/40'">
                        <div class="menu-info">
                            <span class="menu-name"><?= $m['nama']; ?></span>
                            <span class="menu-stat">Terjual: <b><?= number_format($m['qty_sold']); ?></b></span>
                        </div>
                        <div class="menu-profit" style="color:#dc2626;">+Rp <?= number_format($m['margin_per_unit']); ?>/porsi</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <div style="text-align:center; padding: 2rem; color:#94a3b8; font-size:0.85rem;">
        *Analisa berdasarkan Data Transaksi Aktual & Estimasi Margin 40%
    </div>

</body>
</html>