<?php
require_once '../auth_check.php';

// Load Data Inventory
$fileInventory = '../data/inventory.json';
$inventoryData = file_exists($fileInventory) ? json_decode(file_get_contents($fileInventory), true) : [];

// Kategori Tetap
$kategoriList = [
    'Bahan Mentah',
    'Bahan Setengah Jadi',
    'Produk Jadi Siap Saji',
    'Packaging',
    'Bahan Operasional',
    'Peralatan Dapur'
];

// Hitung Total Aset Inventaris
$totalAsetInventaris = 0;
foreach ($inventoryData as $item) {
    $totalAsetInventaris += ($item['qty'] * $item['harga']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok & Inventaris</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <style>
        /* Custom Style untuk Halaman Inventaris */
        .inventory-header {
            background: white; padding: 1.5rem; border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 2rem;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 1rem;
        }
        .total-asset-box {
            text-align: right;
        }
        .total-asset-label { font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .total-asset-value { font-size: 2rem; font-weight: 800; color: #f39c12; line-height: 1; }

        /* Tab Navigasi Kategori */
        .category-tabs {
            display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 1.5rem;
            -webkit-overflow-scrolling: touch;
        }
        .tab-btn {
            padding: 10px 20px; border-radius: 50px; border: 1px solid #e2e8f0;
            background: white; color: #64748b; font-weight: 600; font-size: 0.9rem;
            cursor: pointer; white-space: nowrap; transition: all 0.3s;
        }
        .tab-btn.active { background: #f39c12; color: white; border-color: #f39c12; box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3); }
        .tab-btn:hover:not(.active) { background: #f8fafc; border-color: #cbd5e1; }

        /* Tabel */
        .inventory-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
        .inventory-table th { background: #f8fafc; color: #475569; padding: 1rem; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .inventory-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; }
        .row-hidden { display: none; }

        /* Badge Kategori */
        .cat-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .cat-mentah { background: #dcfce7; color: #166534; }
        .cat-setengah { background: #fef9c3; color: #854d0e; }
        .cat-siap { background: #ffedd5; color: #9a3412; }
        .cat-pack { background: #e0f2fe; color: #075985; }
        .cat-ops { background: #f3e8ff; color: #6b21a8; }
        .cat-alat { background: #f1f5f9; color: #475569; }

        .btn-icon { padding: 5px 10px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-edit { background: #e0f2fe; color: #0284c7; margin-right: 5px; }
        .btn-del { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">📦 Gudang & Inventaris</h1>
            <nav>
                <a href="../index.php" class="nav-link">Dashboard</a>
                <a href="#" class="nav-link active">Stok</a>
                <a href="../logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <div class="inventory-header">
            <div>
                <h2 style="color: #1e293b; margin-bottom: 5px;">Database Aset</h2>
                <p style="color: #64748b; font-size: 0.95rem;">Kelola stok bahan baku, packaging, dan peralatan.</p>
            </div>
            <div class="total-asset-box">
                <div class="total-asset-label">Total Nilai Aset</div>
                <div class="total-asset-value">Rp <?php echo number_format($totalAsetInventaris, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div class="category-tabs" id="tabContainer">
                <button class="tab-btn active" onclick="filterTable('all', this)">Semua</button>
                <button class="tab-btn" onclick="filterTable('Bahan Mentah', this)">🥦 Bahan Mentah</button>
                <button class="tab-btn" onclick="filterTable('Bahan Setengah Jadi', this)">🥘 Setengah Jadi</button>
                <button class="tab-btn" onclick="filterTable('Produk Jadi Siap Saji', this)">🍛 Siap Saji</button>
                <button class="tab-btn" onclick="filterTable('Packaging', this)">🥡 Packaging</button>
                <button class="tab-btn" onclick="filterTable('Bahan Operasional', this)">🧼 Ops</button>
                <button class="tab-btn" onclick="filterTable('Peralatan Dapur', this)">🍳 Alat</button>
            </div>
            <button onclick="bukaModalTambah()" class="card-btn" style="background:#f39c12; color:white; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4); white-space: nowrap;">+ Input Barang</button>
        </div>

        <div class="table-wrapper">
            <table class="inventory-table" id="tableStok">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Harga Satuan</th>
                        <th>Total Nilai</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($inventoryData)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 3rem; color: #94a3b8;">Belum ada data inventaris.</td></tr>
                    <?php else: ?>
                        <?php foreach($inventoryData as $item): 
                            // Tentukan warna badge
                            $badgeClass = 'cat-alat';
                            if($item['kategori'] == 'Bahan Mentah') $badgeClass = 'cat-mentah';
                            elseif($item['kategori'] == 'Bahan Setengah Jadi') $badgeClass = 'cat-setengah';
                            elseif($item['kategori'] == 'Produk Jadi Siap Saji') $badgeClass = 'cat-siap';
                            elseif($item['kategori'] == 'Packaging') $badgeClass = 'cat-pack';
                            elseif($item['kategori'] == 'Bahan Operasional') $badgeClass = 'cat-ops';
                        ?>
                        <tr class="data-row" data-cat="<?php echo $item['kategori']; ?>">
                            <td><strong><?php echo htmlspecialchars($item['nama']); ?></strong></td>
                            <td><span class="cat-badge <?php echo $badgeClass; ?>"><?php echo $item['kategori']; ?></span></td>
                            <td>
                                <span style="font-weight:700; color:#1e293b;"><?php echo $item['qty']; ?></span> 
                                <span style="color:#64748b; font-size:0.8rem;"><?php echo $item['satuan']; ?></span>
                            </td>
                            <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td style="font-weight:600; color:#f39c12;">Rp <?php echo number_format($item['qty'] * $item['harga'], 0, ',', '.'); ?></td>
                            <td style="text-align:center;">
                                <button class="btn-icon btn-edit" onclick='editBarang(<?php echo json_encode($item); ?>)'>✎</button>
                                <button class="btn-icon btn-del" onclick="hapusBarang('<?php echo $item['id']; ?>', '<?php echo $item['nama']; ?>')">🗑</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="modalInventory" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">📦 Input Stok Baru</h2>
                <span class="close-modal" onclick="tutupModalInventory()">×</span>
            </div>
            <form action="proses_inventory.php" method="POST">
                <input type="hidden" name="action" value="simpan_barang">
                <input type="hidden" name="id" id="inputId">
                
                <div class="modal-form-group">
                    <label ">Kategori Barang</label>
                    <select name="kategori" id="inputKategori" class="form-control" required>
                        <option value="" disabled selected >-- Pilih Kategori --</option>
                        <?php foreach($kategoriList as $kat): ?>
                            <option value="<?php echo $kat; ?>"><?php echo $kat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="nama" id="inputNama" class="form-control" required placeholder="Contoh: Beras Putih">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="modal-form-group">
                        <label>Jumlah (Qty)</label>
                        <input type="number" name="qty" id="inputQty" class="form-control" required min="0" placeholder="0">
                    </div>
                    <div class="modal-form-group">
                        <label>Satuan</label>
                        <input type="text" name="satuan" id="inputSatuan" class="form-control" required placeholder="kg/pcs/liter">
                    </div>
                </div>

                <div class="modal-form-group">
                    <label>Harga Beli (Per Satuan)</label>
                    <input type="number" name="harga" id="inputHarga" class="form-control" required min="0" placeholder="Rp">
                </div>

                <button type="submit" class="btn-submit-wd" style="background: #f39c12;">Simpan Data</button>
            </form>
        </div>
    </div>

    <form id="formHapus" action="proses_inventory.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="hapus_barang">
        <input type="hidden" name="id" id="idHapus">
    </form>

    <script>
        const modalInv = document.getElementById('modalInventory');
        const modalTitle = document.getElementById('modalTitle');
        
        // Input Elements
        const inpId = document.getElementById('inputId');
        const inpKat = document.getElementById('inputKategori');
        const inpNama = document.getElementById('inputNama');
        const inpQty = document.getElementById('inputQty');
        const inpSat = document.getElementById('inputSatuan');
        const inpHarga = document.getElementById('inputHarga');

        function bukaModalTambah() {
            modalInv.style.display = 'flex';
            modalTitle.innerText = '📦 Input Stok Baru';
            // Reset Form
            inpId.value = '';
            inpKat.value = '';
            inpNama.value = '';
            inpQty.value = '';
            inpSat.value = '';
            inpHarga.value = '';
        }

        function editBarang(data) {
            modalInv.style.display = 'flex';
            modalTitle.innerText = '✏️ Edit Stok';
            // Fill Form
            inpId.value = data.id;
            inpKat.value = data.kategori;
            inpNama.value = data.nama;
            inpQty.value = data.qty;
            inpSat.value = data.satuan;
            inpHarga.value = data.harga;
        }

        function tutupModalInventory() {
            modalInv.style.display = 'none';
        }

        function hapusBarang(id, nama) {
            if(confirm('Yakin ingin menghapus stok: ' + nama + '?')) {
                document.getElementById('idHapus').value = id;
                document.getElementById('formHapus').submit();
            }
        }

        // Filter Table Logic
        function filterTable(category, btn) {
            // Ubah tombol aktif
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter Baris Tabel
            const rows = document.querySelectorAll('.data-row');
            rows.forEach(row => {
                if (category === 'all' || row.getAttribute('data-cat') === category) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        window.onclick = (e) => {
            if(e.target == modalInv) modalInv.style.display = "none";
        }
    </script>

</body>
</html>