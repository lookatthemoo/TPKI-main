<?php
require_once 'auth_check.php';

// Data Ringkas untuk Tampilan Dashboard
$fileTrx = 'data/transaksi.json';
$fileMenu = 'data/menu.json';

// Hitung Kas Sekilas
$transaksiData = file_exists($fileTrx) ? json_decode(file_get_contents($fileTrx), true) : [];
$kasTotal = 0;
foreach ($transaksiData as $t) {
    if ($t['tipe'] === 'pendapatan')
        $kasTotal += $t['jumlah'];
    elseif ($t['tipe'] === 'pengeluaran' || $t['tipe'] === 'penarikan')
        $kasTotal -= $t['jumlah'];
}

// Hitung Jumlah Menu
$menuList = file_exists($fileMenu) ? json_decode(file_get_contents($fileMenu), true) : [];
$totalMenu = count($menuList);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">Financial AI Core</h1>
            <nav>
                <a href="index.php" class="nav-link active">Dashboard</a>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">

        <?php if (isset($_GET['status']) && $_GET['status'] == 'menu_added'): ?>
            <div
                style="background:#dcfce7; color:#166534; padding:15px; border-radius:10px; margin-bottom:20px; text-align:center; border:1px solid #bbf7d0;">
                ✅ Menu Berhasil Ditambahkan!
            </div>
        <?php endif; ?>

        <div class="welcome-message">
            <h2>Halo, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>! 👋</h2>
            <p>Pusat kendali operasional restoran Anda.</p>
        </div>

        <section class="main-nav-cards">

            <div class="nav-card">
                <span class="status-badge" style="background:#e0fcf6; color:#00b894;">DAILY</span>
                <div class="card-icon" style="color: #00b894; background: #e0fcf6;">📊</div>
                <h3>Laporan Harian</h3>
                <p>Pantau Kas, Omzet Hari Ini, dan Pengeluaran Harian.</p>
                <a href="laporan/" class="card-btn"
                    style="background: #00b894; color: white; box-shadow: 0 4px 15px rgba(0, 184, 148, 0.3);">Buka
                    Laporan ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#fff3cd; color:#856404;">STOCK</span>
                <div class="card-icon" style="color: #f39c12; background: #fff3cd;">📦</div>
                <h3>Stok & Inventaris</h3>
                <p>Kelola <?php echo $totalMenu; ?> item menu yang tersedia saat ini.</p>
                <a href="inventaris/" class="card-btn"
                    style="background: #f39c12; color: white; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);">Cek
                    Inventaris ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge">LIVE</span>
                <div class="card-icon">🍳</div>
                <h3>Dapur & Pesanan</h3>
                <p>Pantau pesanan masuk secara real-time.</p>
                <a href="pesanan.php" class="card-btn btn-blue">Buka Dapur ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge badge-dev">REPORT</span>
                <div class="card-icon">💰</div>
                <h3>Laporan Lengkap</h3>
                <p>Analisa keuangan bulanan dan penarikan dana.</p>
                <a href="laporan.php" class="card-btn btn-orange">Analisa Full ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#e0e7ff; color:#4338ca;">WALLET</span>
                <div class="card-icon" style="color: #4338ca; background: #e0e7ff;">💳</div>
                <h3>Rekening & E-Wallet</h3>
                <p>Cek Saldo BCA, BRI, Mandiri, E-Wallet, dll.</p>
                <a href="rekening/" class="card-btn"
                    style="background: #4338ca; color: white; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.3);">Kelola
                    Saldo ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#e3f2fd; color:#1565c0;">HRD</span>
                <div class="card-icon" style="color:#673ab7;">👥</div>
                <h3>Gaji & Karyawan</h3>
                <p>Manajemen tim, absensi, dan penggajian.</p>
                <a href="gaji.php" class="card-btn" style="background:#673ab7; color:white;">Kelola Tim ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#fee2e2; color:#991b1b;">EXPENSE</span>
                <div class="card-icon" style="color: #ef4444; background: #fee2e2;">💸</div>
                <h3>Pusat Pengeluaran</h3>
                <p>Catat Gaji, Bonus, Operasional, & Belanja.</p>
                <a href="pengeluaran/" class="card-btn"
                    style="background: #ef4444; color: white; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);">Kelola
                    Biaya ➔</a>
            </div>

            <div class="nav-card">
                <div class="card-icon" style="color: #9b59b6; background: #f5eef8;">🍔</div>
                <h3>Kelola Menu</h3>
                <p>Tambah menu baru dan upload foto.</p>
                <button onclick="bukaModalMenu()" class="card-btn" style="background: #9b59b6; color: white;">Tambah
                    Menu +</button>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#E0F2F1; color:#00897B;">INVEST</span>
                <div class="card-icon" style="color: #00897B; background: #E0F2F1;">🤝</div>
                <h3>Investasi</h3>
                <p>Akses laporan dividen, profit sharing & pemodal.</p>
                <a href="investor/index.php" class="card-btn"
                    style="background: #00897B; color: white; box-shadow: 0 4px 15px rgba(0, 137, 123, 0.3);">Masuk
                    Investor ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#f1f5f9; color:#475569;">GLOBAL</span>
                <div class="card-icon" style="color: #475569; background: #f1f5f9;">📰</div>
                <h3>Berita Pasar</h3>
                <p>Update ekonomi global, saham, dan forex terkini.</p>
                <a href="berita.php" class="card-btn"
                    style="background: #475569; color: white; box-shadow: 0 4px 15px rgba(71, 85, 105, 0.3);">Baca
                    Berita ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#e0e7ff; color:#4338ca;">PRO TOOLS</span>
                <div class="card-icon" style="color: #4338ca; background: #e0e7ff;">🛠️</div>
                <h3>Market Tools</h3>
                <p>Screener Saham, Kalender Ekonomi, & Komoditas.</p>
                <a href="tools.php" class="card-btn"
                    style="background: #4338ca; color: white; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.3);">Buka Tools
                    ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#fff7ed; color:#c2410c;">STRATEGY</span>
                <div class="card-icon" style="color: #ea580c; background: #fff7ed;">🧩</div>
                <h3>Menu Engineering</h3>
                <p>Analisa Profitabilitas: Mana menu 'Bintang' & mana 'Beban'.</p>
                <a href="analisa_menu.php" class="card-btn"
                    style="background: #ea580c; color: white; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);">Cek
                    Matriks ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#ecfdf5; color:#047857;">URGENT</span>
                <div class="card-icon" style="color: #059669; background: #ecfdf5;">🛒</div>
                <h3>Belanja Pasar</h3>
                <p>Daftar belanja otomatis & kontrol harga bahan baku.</p>
                <a href="belanja.php" class="card-btn"
                    style="background: #059669; color: white; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);">Cek Daftar
                    Belanja ➔</a>
            </div>

            <div class="nav-card">
                <span class="status-badge" style="background:#f0fdfa; color:#0d9488;">SURVIVAL</span>
                <div class="card-icon" style="color: #14b8a6; background: #f0fdfa;">🛡️</div>
                <h3>Napas Bisnis (Runway)</h3>
                <p>Estimasi ketahanan dana kas operasional (Bulan).</p>
                <a href="runway.php" class="card-btn"
                    style="background: #14b8a6; color: white; box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);">Cek
                    Status Aman ➔</a>
            </div>
        </section>

    </main>

    <div id="modalMenu" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🍔 Tambah Menu Baru</h2><span class="close-modal" onclick="tutupModalMenu()">×</span>
            </div>
            <form action="proses_menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_menu">
                <div class="modal-form-group"><label>Nama Makanan</label><input type="text" name="nama" required
                        placeholder="Contoh: Rendang Daging"></div>
                <div class="modal-form-group"><label>Harga (Rp)</label><input type="number" name="harga" required
                        placeholder="20000"></div>
                <div class="modal-form-group"><label>Deskripsi Singkat</label><textarea name="deskripsi" rows="2"
                        required placeholder="Potongan daging sapi empuk..."></textarea></div>
                <div class="modal-form-group"><label>Foto Makanan</label><input type="file" name="gambar"
                        accept="image/*" required style="padding: 5px;"><small style="color:#e74c3c;">*Wajib Upload Foto
                        (JPG/PNG/WEBP)</small></div>
                <button type="submit" class="btn-submit-wd" style="background: #9b59b6;">Simpan Menu</button>
            </form>
        </div>
    </div>

    <script>
        const mM = document.getElementById('modalMenu');
        function bukaModalMenu() { mM.style.display = "flex"; }
        function tutupModalMenu() { mM.style.display = "none"; }

        // Cek URL kalau ada request buka modal dari halaman lain
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'buka_modal_menu') {
            bukaModalMenu();
        }

        window.onclick = (e) => {
            if (e.target == mM) mM.style.display = "none";
        }
    </script>

</body>

</html>