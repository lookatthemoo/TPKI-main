<?php
require_once 'auth_check.php';

// --- LOAD DATA MENU UNTUK PEMETAAN GAMBAR ---
$fileMenu = 'data/menu.json';
$menuList = file_exists($fileMenu) ? json_decode(file_get_contents($fileMenu), true) : [];

// Buat Map Otomatis: "Nama Menu" => "File Gambar"
$menuImageMap = [];
foreach ($menuList as $m) {
    // Path gambar harus mundur ke folder menu/images
    $menuImageMap[$m['nama']] = '../menu/images/' . $m['gambar'];
}
$defaultImage = '../menu/images/makanan.jpeg'; 

// ... (Sisa kode fungsi getOrders dll biarkan sama) ...


// --- FUNGSI PHP (DIPERBARUI) ---
function getOrders($dataFile) {
    if (!file_exists($dataFile)) { return []; }
    $jsonData = file_get_contents($dataFile);
    $transaksi = json_decode($jsonData, true);
    if (!is_array($transaksi)) { return []; }
    $orders = [];
    foreach ($transaksi as $trx) {
        // --- PERUBAHAN DI SINI ---
        // Kita HANYA ambil status yang relevan untuk ditampilkan di dapur
        if (isset($trx['status']) && 
           ($trx['status'] === 'Diterima' || 
            $trx['status'] === 'Sedang di Proses' || 
            $trx['status'] === 'Pesanan Jadi')) 
        {
            if ($trx['tipe'] === 'pendapatan') {
                $orders[] = $trx;
            }
        }
    }
    return array_reverse($orders); 
}

function timeElapsedString($datetime) {
    $now = new DateTime; $ago = new DateTime($datetime); $diff = $now->diff($ago);
    if ($diff->h > 0) { return $diff->h . 'j ' . $diff->i . 'm'; }
    return $diff->i . ' mnt';
}

$semuaPesanan = getOrders('data/transaksi.json');
$kolomDiterima = []; $kolomProses = []; $kolomJadi = [];
foreach ($semuaPesanan as $order) {
    switch ($order['status']) {
        case 'Diterima': $kolomDiterima[] = $order; break;
        case 'Sedang di Proses': $kolomProses[] = $order; break;
        case 'Pesanan Jadi': $kolomJadi[] = $order; break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display - Visual</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- Style Lama (Compact Mode) --- */
        .order-kanban-container { gap: 1rem; }
        .order-column { padding: 0.8rem; }
        .order-column h3 { font-size: 1rem; margin-bottom: 0.8rem; padding-bottom: 0.5rem; }
        .order-card { padding: 0.8rem; margin-bottom: 0.8rem; box-shadow: 0 2px 5px rgba(0,0,0,0.08); position: relative; }
        .order-card-header { margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #eee; }
        .order-info h4 { font-size: 1rem; margin-bottom: 2px; }
        .order-meta { display: flex; gap: 10px; font-size: 0.75rem; color: #666; }
        .order-timer { color: #e67e22; font-weight: 600; }
        .order-type-badge { font-size: 0.65rem; padding: 2px 6px; height: fit-content; }
        .badge-dine-in { background: #e3f2fd; color: #1565c0; }
        .badge-take-away { background: #fff3e0; color: #e65100; }
        .btn-status { padding: 0.4rem; font-size: 0.9rem; margin-top: 0.5rem; }

        /* --- Tampilan Item Dengan Gambar --- */
        .order-item-list { display: flex; flex-direction: column; gap: 8px; margin: 0.8rem 0; padding-top: 0.5rem; border-top: 1px solid #f0f0f0; }
        .item-pic-row { display: flex; align-items: center; gap: 10px; background: #f8f9fa; padding: 6px; border-radius: 10px; }
        .item-pic-row img { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .item-pic-row .item-qty { font-weight: 700; font-size: 0.95rem; color: #333; min-width: 20px; }
        .item-pic-row .item-name { font-size: 0.9rem; color: #555; font-weight: 500; line-height: 1.3; }
        
        /* --- [CSS BARU] Style untuk Tombol "Ambil" --- */
        .btn-ambil {
            background-color: #2ecc71; /* Hijau Selesai */
            border: 2px solid #27ae60;
            box-shadow: 0 2px 5px rgba(46, 204, 113, 0.2);
            color: white;
            font-weight: 700;
        }
        .btn-ambil:hover {
            background-color: #27ae60;
        }
    </style>
    <meta http-equiv="refresh" content="30">
</head>
<body>

   <header class="navbar">
        <div class="container">
            <h1 class="logo">Kitchen Display</h1> <nav>
                <a href="index.php" class="nav-link">Dashboard</a> <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container" style="padding-top: 1.5rem;">
        <div class="order-kanban-container">

            <section class="order-column">
                <h3>Baru (<?php echo count($kolomDiterima); ?>)</h3>
                <div class="order-list-wrapper">
                    <?php if (empty($kolomDiterima)) echo '<p class="order-empty">Kosong</p>'; ?>
                    <?php foreach ($kolomDiterima as $order): ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div class="order-info">
                                <h4><?php echo htmlspecialchars($order['customer_name'] ?? ('Meja ' . rand(1, 15))); ?></h4>
                                <div class="order-meta"><span>#<?php echo substr($order['id'], 0, 5); ?></span><span class="order-timer">⏳ <?php echo timeElapsedString($order['tanggal']); ?></span></div>
                            </div>
                            <?php $tipe = $order['order_type'] ?? (rand(0, 1) ? 'Dine-in' : 'Take-away'); ?>
                            <span class="order-type-badge badge-<?php echo strtolower(str_replace(' ', '-', $tipe)); ?>"><?php echo $tipe; ?></span>
                        </div>
                        <div class="order-item-list">
                            <?php if(isset($order['items']) && is_array($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php $itemName = htmlspecialchars($item['name'] ?? 'Item'); $imagePath = $menuImageMap[$itemName] ?? $defaultImage; ?>
                                    <div class="item-pic-row"><img src="<?php echo $imagePath; ?>"><span class="item-qty"><?php echo htmlspecialchars($item['qty'] ?? '1'); ?>x</span><span class="item-name"><?php echo $itemName; ?></span></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="update_status.php" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="new_status" value="Sedang di Proses" class="btn-status btn-proses">🔥 Masak</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="order-column" style="background-color: #e3f2fd;">
                <h3>Dimasak (<?php echo count($kolomProses); ?>)</h3>
                <div class="order-list-wrapper">
                    <?php foreach ($kolomProses as $order): ?>
                    <div class="order-card" style="border-left: 3px solid #2196f3;">
                        <div class="order-card-header">
                            <div class="order-info">
                                <h4><?php echo htmlspecialchars($order['customer_name'] ?? ('Meja ' . rand(1, 15))); ?></h4>
                                <div class="order-meta"><span class="order-timer">⏳ <?php echo timeElapsedString($order['tanggal']); ?></span></div>
                            </div>
                        </div>
                        <div class="order-item-list">
                            <?php if(isset($order['items']) && is_array($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php $itemName = htmlspecialchars($item['name'] ?? 'Item'); $imagePath = $menuImageMap[$itemName] ?? $defaultImage; ?>
                                    <div class="item-pic-row"><img src="<?php echo $imagePath; ?>"><span class="item-qty"><?php echo htmlspecialchars($item['qty'] ?? '1'); ?>x</span><span class="item-name"><?php echo $itemName; ?></span></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="update_status.php" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="new_status" value="Pesanan Jadi" class="btn-status btn-selesai">✅ Selesai</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="order-column" style="background-color: #e8f5e9;">
                <h3>Saji (<?php echo count($kolomJadi); ?>)</h3>
                <div class="order-list-wrapper">
                    <?php foreach ($kolomJadi as $order): ?>
                    <div class="order-card" style="border-left: 3px solid #2ecc71;">
                        
                        <div class="order-card-header">
                            <div class="order-info">
                                <h4><?php echo htmlspecialchars($order['customer_name'] ?? ('Meja ' . rand(1, 15))); ?></h4>
                                <div class="order-meta"><span>#<?php echo substr($order['id'], 0, 5); ?></span></div>
                            </div>
                            <?php $tipe = $order['order_type'] ?? 'Dine-in'; ?>
                            <span class="order-type-badge badge-<?php echo strtolower(str_replace(' ', '-', $tipe)); ?>"><?php echo $tipe; ?></span>
                        </div>
                        <div class="order-item-list">
                            <?php if(isset($order['items']) && is_array($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php $itemName = htmlspecialchars($item['name'] ?? 'Item'); $imagePath = $menuImageMap[$itemName] ?? $defaultImage; ?>
                                    <div class="item-pic-row">
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo $itemName; ?>">
                                        <span class="item-qty"><?php echo htmlspecialchars($item['qty'] ?? '1'); ?>x</span>
                                        <span class="item-name"><?php echo $itemName; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form action="update_status.php" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="new_status" value="Telah Diambil" class="btn-status btn-ambil">
                                ✔️ Ambil & Sajikan
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>
    </main>
</body>
</html>