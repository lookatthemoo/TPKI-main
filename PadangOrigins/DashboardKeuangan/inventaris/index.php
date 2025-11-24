<?php
require_once '../auth_check.php';

$fileMenu = '../data/menu.json';
$menuList = file_exists($fileMenu) ? json_decode(file_get_contents($fileMenu), true) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stok & Inventaris</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .stock-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stock-table th { background: #f39c12; color: white; padding: 1rem; text-align: left; }
        .stock-table td { padding: 1rem; border-bottom: 1px solid #eee; }
        .stock-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .badge-ok { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <h1 class="logo">📦 Stok & Inventaris</h1>
            <nav><a href="../index.php" class="nav-link btn-logout">Kembali</a></nav>
        </div>
    </header>

    <main class="container" style="margin-top:2rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3>Daftar Menu Aktif (<?php echo count($menuList); ?> Item)</h3>
            <a href="../index.php?action=buka_modal_menu" class="card-btn" style="background:#f39c12; color:white; text-decoration:none;">+ Tambah Menu Baru</a>
        </div>

        <table class="stock-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Menu</th>
                    <th>Harga Jual</th>
                    <th>Status Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($menuList)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:2rem;">Belum ada data menu.</td></tr>
                <?php else: ?>
                    <?php foreach($menuList as $m): ?>
                    <tr>
                        <td><img src="../menu/images/<?php echo htmlspecialchars($m['gambar']); ?>" class="stock-img" onerror="this.src='../menu/images/makanan.jpeg'"></td>
                        <td><strong><?php echo htmlspecialchars($m['nama']); ?></strong><br><small style="color:#888;"><?php echo htmlspecialchars($m['deskripsi']); ?></small></td>
                        <td>Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></td>
                        <td><span class="badge-ok">Tersedia</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>