<?php
// Mulai session untuk memuat keranjang yang ada
session_start();

// Inisialisasi jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fungsi format Rupiah untuk render awal
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Hitung total awal
$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += (int)$item['price']; // Pastikan integer
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Restoran</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="main-layout">
        <div class="menu-wrapper">
            
            <section class="favorites-section">
                <h3 style="font-size: 2.5rem; color: #FFFFFF; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Menu Pilihan Hari Ini</h3>
                
                <div class="favorites-grid"> 
                    <?php
                    // --- LOAD DATA DARI JSON ---
                    $fileMenu = '../DashboardKeuangan/data/menu.json';
                    $menuList = [];
                    if (file_exists($fileMenu)) {
                        $menuList = json_decode(file_get_contents($fileMenu), true) ?? [];
                    }
                    
                    // Jika kosong, tampilkan pesan
                    if (empty($menuList)) {
                        echo "<p style='color:white; text-align:center; width:100%;'>Belum ada menu tersedia.</p>";
                    }

                    // --- LOOPING MENU OTOMATIS ---
                    foreach ($menuList as $m):
                    ?>
                    <article class="menu-card">
                        <img src="images/<?php echo htmlspecialchars($m['gambar']); ?>" alt="<?php echo htmlspecialchars($m['nama']); ?>" onerror="this.src='images/makanan.jpeg'">
                        
                        <h4><?php echo htmlspecialchars($m['nama']); ?></h4>
                        <p><?php echo htmlspecialchars($m['deskripsi']); ?></p>
                        <span>Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></span>
                        
                        <form class="add-to-cart-form">
                            <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($m['nama']); ?>">
                            <input type="hidden" name="item_price" value="<?php echo $m['harga']; ?>">
                            
                            <div class="qty-wrapper" style="display:flex; gap:5px; justify-content:center; margin-bottom:10px;">
                                <input type="number" name="item_qty" value="1" min="1" class="qty-input" style="width:60px; padding:5px; text-align:center; border-radius:8px; border:1px solid #555; background:#333; color:white;">
                                <button type="submit" class="add-button-small" style="background:#e74c3c; color:white; border:none; padding:5px 15px; border-radius:8px; cursor:pointer; font-weight:bold;">TAMBAH +</button>
                            </div>
                        </form>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <footer>
            </footer>
        </div>

        <div class="dashboard-wrapper">
            <div class="order-dashboard" id="dashboard">
                <h2>Pesanan Anda</h2>
                
                <ul id="order-list">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <li class="empty-cart">Keranjang kosong...</li>
                    <?php else: ?>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                <strong><?php echo formatRupiah((int)$item['price']); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <hr>
                <div class="order-total">
                    <strong>Total:</strong>
                    <span id="total-price"><?php echo formatRupiah($totalPrice); ?></span>
                </div>

                <button class="checkout-button" id="checkout-button">Checkout</button>
                
                <button id="clear-cart-button" class="clear-cart-button" 
                        style="<?php echo empty($_SESSION['cart']) ? 'display:none;' : ''; ?>">
                    Kosongkan Keranjang
                </button>

                <div class="status-tracker-container" id="status-tracker-box">
                    <h4>Status Pesanan Anda:</h4>
                    <p class="status-text-live" id="order-status-text">Belum ada pesanan aktif.</p>
                    
                    <div class="status-progress-bar">
                        <div class="status-step" id="status-step-diterima">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                </svg>
                            </div>
                            <span>Diterima</span>
                        </div>
                        <div class="status-line"></div>
                        <div class="status-step" id="status-step-proses">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                </svg>
                            </div>
                            <span>Diproses</span>
                        </div>
                        <div class="status-line"></div>
                        <div class="status-step" id="status-step-jadi">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                  <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                            </div>
                            <span>Jadi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div id="modalCheckout" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
        <div class="modal-content" style="background:#222; padding:25px; border-radius:15px; width:90%; max-width:400px; color:white; border:1px solid #444;">
            <h2 style="text-align:center; margin-bottom:20px; color:#e74c3c;">Konfirmasi Pesanan</h2>
            
            <form id="formCheckoutFinal">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; color:#aaa;">Nama Pemesan</label>
                    <input type="text" name="customer_name" required placeholder="Contoh: Budi" style="width:100%; padding:12px; border-radius:8px; border:none; background:#333; color:white;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; color:#aaa;">Metode Pembayaran</label>
                    <select name="payment_method" id="paymentMethod" required style="width:100%; padding:12px; border-radius:8px; border:none; background:#333; color:white;">
                        <option value="kas_laci">💵 Tunai / Cashier</option>
                        <?php
                        $fileRekening = '../DashboardKeuangan/data/rekening.json';
                        if (file_exists($fileRekening)) {
                            $banks = json_decode(file_get_contents($fileRekening), true);
                            foreach ($banks as $b) {
                                echo '<option value="' . $b['nama_bank'] . '">🏦 ' . $b['nama_bank'] . ' (' . $b['jenis'] . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeCheckoutModal()" style="flex:1; padding:12px; background:#555; color:white; border:none; border-radius:8px; cursor:pointer;">Batal</button>
                    <button type="submit" style="flex:1; padding:12px; background:#e74c3c; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Bayar Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCheckoutModal() {
            document.getElementById('modalCheckout').style.display = 'flex';
        }
        function closeCheckoutModal() {
            document.getElementById('modalCheckout').style.display = 'none';
        }
    </script>
    <script src="script.js" defer></script>
</body>
</html>