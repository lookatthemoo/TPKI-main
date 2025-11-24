<?php
require_once 'auth_check.php';

// Ambil data karyawan terbaru
$fileKaryawan = 'data/karyawan.json';
$karyawanList = [];
if (file_exists($fileKaryawan)) {
    $karyawanList = json_decode(file_get_contents($fileKaryawan), true) ?? [];
}

$activeTab = $_GET['tab'] ?? 'karyawan'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR System - Gaji & Karyawan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- STYLE GLOBAL --- */
        body { background-color: #f4f7f9; }
        .tab-navigation { display: flex; justify-content: center; gap: 15px; margin: 20px 0 30px 0; flex-wrap: wrap; }
        .nav-tab { background: #fff; padding: 12px 30px; border-radius: 50px; font-weight: 600; color: #64748b; cursor: pointer; border: 1px solid #e2e8f0; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .nav-tab.active { background: #2575fc; color: white; border-color: #2575fc; box-shadow: 0 4px 12px rgba(37, 117, 252, 0.3); }
        .nav-tab:hover:not(.active) { background: #f1f5f9; }
        
        .view-section { display: none; animation: fadeIn 0.3s ease-out; }
        .view-section.active { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; padding-bottom: 80px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* KARTU KARYAWAN */
        .emp-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9; display: flex; flex-direction: column; height: 100%; position: relative; }
        .emp-card.orange-top { border-top: 4px solid #f59e0b; }
        .emp-card.blue-top { border-top: 4px solid #3b82f6; }

        /* HEADER KARTU */
        .emp-header { 
            display: flex; justify-content: space-between; align-items: flex-start; 
            margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #e2e8f0; 
            position: relative; 
        }
        .emp-info { flex: 1; padding-right: 10px; } 
        .emp-info h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; line-height: 1.3; }
        .emp-pos { font-size: 0.8rem; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; }
        .header-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }

        .badge-status { font-size: 0.75rem; font-weight: 600; padding: 4px 8px; border-radius: 6px; white-space: nowrap; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-gray { background: #f3f4f6; color: #6b7280; }

        .btn-delete-small {
            background: #fee2e2; color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.9rem; transition: 0.2s;
        }
        .btn-delete-small:hover { background: #ef4444; color: white; }

        /* STATS & FORM */
        .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .stat-item { background: #f8fafc; padding: 10px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0; }
        .stat-val { font-size: 1.2rem; font-weight: 700; color: #334155; display: block; }
        .stat-lbl { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

        .control-panel { background: #fff; padding: 0; margin-bottom: 15px; }
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 5px; }
        .input-group { display: flex; gap: 8px; }
        .form-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; color: #334155; transition: all 0.2s; }
        .form-input:focus { border-color: #2575fc; outline: none; box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.1); }
        .btn-simpan { background: #f59e0b; color: white; border: none; padding: 0 15px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; white-space: nowrap; }
        .btn-simpan:hover { background: #d97706; }

        .money-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 15px; text-align: center; margin-top: auto; }
        .money-title { font-size: 0.8rem; color: #b45309; font-weight: 600; margin-bottom: 5px; }
        .money-amount { font-size: 1.5rem; font-weight: 800; color: #d97706; margin-bottom: 10px; }
        
        .btn-action { width: 100%; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: transform 0.1s; }
        .btn-action:active { transform: scale(0.98); }
        .btn-cair { background: #f59e0b; color: white; }
        .btn-gaji { background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

        .float-add { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4); cursor: pointer; border: none; transition: transform 0.2s; z-index: 100; }
        .float-add:hover { transform: scale(1.1) rotate(90deg); }
        
        /* MODAL & OVERLAY */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-content { background: white; width: 90%; max-width: 400px; padding: 25px; border-radius: 16px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease; position: relative; }
        @keyframes slideUp { from {transform: translateY(30px); opacity:0;} to {transform: translateY(0); opacity:1;} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .modal-header h2 { margin: 0; font-size: 1.2rem; color: #1e293b; }
        .close-modal { font-size: 1.5rem; cursor: pointer; color: #94a3b8; transition: 0.2s; }
        .close-modal:hover { color: #ef4444; }

        /* Confirm Pay Details */
        .pay-detail-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .pay-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; color: #64748b; }
        .pay-row.total { border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 10px; font-weight: 700; color: #1e293b; font-size: 1.1rem; }
        .source-badge { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }

        .btn-confirm-pay { background: #2563eb; color: white; width: 100%; padding: 12px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; font-size: 1rem; }
        .btn-confirm-pay:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">HR System</h1>
            <nav>
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <?php if(isset($_GET['status'])): ?>
            <?php 
                $msg = ""; $bg = "#dcfce7"; $color = "#166534";
                if($_GET['status'] == 'salary_ready') $msg = "✅ Gaji Bulanan berhasil dikirim via BCA!";
                elseif($_GET['status'] == 'bonus_ready') $msg = "✅ Bonus berhasil dikirim via BCA!";
                elseif($_GET['status'] == 'deleted') $msg = "🗑️ Karyawan berhasil dihapus.";
                elseif($_GET['status'] == 'error_saldo_bca') { $msg = "⚠️ GAGAL: Saldo BCA Perusahaan tidak mencukupi."; $bg="#fee2e2"; $color="#991b1b"; }
                elseif($_GET['status'] == 'error_no_bca') { $msg = "⚠️ ERROR: Akun BCA tidak ditemukan di sistem."; $bg="#fee2e2"; $color="#991b1b"; }
            ?>
            <div style="background:<?php echo $bg; ?>; color:<?php echo $color; ?>; padding:12px; border-radius:8px; margin-bottom:20px; text-align:center; font-weight:600;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="tab-navigation">
            <div onclick="switchTab('gaji')" id="tab-btn-gaji" class="nav-tab <?php echo $activeTab === 'gaji' ? 'active' : ''; ?>">
                💰 Gaji Bulanan
            </div>
            <div onclick="switchTab('karyawan')" id="tab-btn-karyawan" class="nav-tab <?php echo $activeTab === 'karyawan' ? 'active' : ''; ?>">
                👥 Absen & Bonus
            </div>
        </div>

        <div id="view-gaji" class="view-section <?php echo $activeTab === 'gaji' ? 'active' : ''; ?>">
            <?php foreach($karyawanList as $k): ?>
            <div class="emp-card blue-top">
                <div class="emp-header">
                    <div class="emp-info">
                        <h3><?php echo htmlspecialchars($k['nama']); ?></h3>
                        <span class="emp-pos"><?php echo htmlspecialchars($k['posisi']); ?></span>
                    </div>
                    <div class="header-actions">
                        <div class="badge-status bg-gray">Gaji Pokok</div>
                        <button type="button" class="btn-delete-small" onclick="confirmDelete('<?php echo $k['id']; ?>', '<?php echo htmlspecialchars($k['nama']); ?>')">🗑️</button>
                    </div>
                </div>

                <div style="text-align:center; margin-bottom: 20px;">
                    <h2 style="font-size:1.8rem; color:#1e293b; margin:0;">Rp <?php echo number_format($k['gaji_pokok'], 0, ',', '.'); ?></h2>
                    <small style="color:#64748b;">Terakhir: <?php echo $k['terakhir_gaji']; ?></small>
                </div>

                <div style="margin-top:auto;"> 
                    <button type="button" class="btn-action btn-gaji" 
                        onclick="openPayModal('salary', '<?php echo $k['id']; ?>', '<?php echo htmlspecialchars($k['nama']); ?>', <?php echo $k['gaji_pokok']; ?>)">
                        💸 Kirim Gaji Full
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="view-karyawan" class="view-section <?php echo $activeTab === 'karyawan' ? 'active' : ''; ?>">
            <?php foreach($karyawanList as $k): ?>
            <div class="emp-card orange-top">
                <div class="emp-header">
                    <div class="emp-info">
                        <h3><?php echo htmlspecialchars($k['nama']); ?></h3>
                        <span class="emp-pos"><?php echo htmlspecialchars($k['posisi']); ?></span>
                    </div>
                    <div class="header-actions">
                        <?php if(($k['terakhir_absen'] ?? '') == date('Y-m-d')): ?>
                            <span class="badge-status bg-green">✅ HADIR</span>
                        <?php else: ?>
                            <span class="badge-status bg-gray">BELUM ABSEN</span>
                        <?php endif; ?>
                        <button type="button" class="btn-delete-small" onclick="confirmDelete('<?php echo $k['id']; ?>', '<?php echo htmlspecialchars($k['nama']); ?>')">🗑️</button>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-item"><span class="stat-val"><?php echo $k['hadir']; ?></span><span class="stat-lbl">Hadir</span></div>
                    <div class="stat-item"><span class="stat-val"><?php echo $k['izin']; ?></span><span class="stat-lbl">Izin</span></div>
                </div>

                <form action="proses_karyawan.php" method="POST" class="control-panel">
                    <input type="hidden" name="action" value="update_data">
                    <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                    <div class="form-group">
                        <label class="form-label">Koreksi Data</label>
                        <div class="input-group">
                            <input type="number" name="hadir" value="<?php echo $k['hadir']; ?>" class="form-input">
                            <select name="performa" class="form-input">
                                <option value="Baik" <?php echo $k['performa']=='Baik'?'selected':''; ?>>Baik</option>
                                <option value="Kurang" <?php echo $k['performa']=='Kurang'?'selected':''; ?>>Kurang</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tambah Bonus (+)</label>
                        <div class="input-group">
                            <input type="number" name="tambah_bonus" placeholder="Rp.." class="form-input">
                            <button type="submit" class="btn-simpan">Simpan</button>
                        </div>
                    </div>
                </form>

                <div class="money-box">
                    <div class="money-title">BONUS PENDING</div>
                    <div class="money-amount">Rp <?php echo number_format($k['bonus_pending'] ?? 0, 0, ',', '.'); ?></div>
                    <?php if(($k['bonus_pending'] ?? 0) > 0): ?>
                        <button type="button" class="btn-action btn-cair" 
                            onclick="openPayModal('bonus', '<?php echo $k['id']; ?>', '<?php echo htmlspecialchars($k['nama']); ?>', <?php echo $k['bonus_pending']; ?>)">
                            ⚡ Kirim Bonus (Via BCA)
                        </button>
                    <?php else: ?>
                        <button class="btn-action btn-disabled" disabled>Tidak Ada Bonus</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <button class="float-add" onclick="document.getElementById('modalAdd').style.display='flex'">+</button>

    <div id="modalConfirmPay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="payModalTitle">💸 Konfirmasi Pembayaran</h2>
                <span class="close-modal" onclick="closePayModal()">×</span>
            </div>
            
            <div class="pay-detail-box">
                <div class="pay-row"><span>Penerima:</span> <strong id="payName">-</strong></div>
                <div class="pay-row"><span>Sumber Dana:</span> <span class="source-badge">BCA (Otomatis)</span></div>
                <div class="pay-row total"><span>Total Transfer:</span> <span id="payAmount" style="color:#2563eb;">Rp 0</span></div>
            </div>

            <p style="font-size:0.85rem; color:#64748b; margin-bottom:20px; text-align:center;">
                Pastikan saldo BCA perusahaan mencukupi sebelum melanjutkan transaksi ini.
            </p>

            <form action="proses_karyawan.php" method="POST">
                <input type="hidden" name="action" id="payAction">
                <input type="hidden" name="id" id="payId">
                <input type="hidden" name="nama" id="payNameInput"> <input type="hidden" name="amount" id="payAmountInput">
                <button type="submit" class="btn-confirm-pay">✅ Ya, Transfer Sekarang</button>
            </form>
        </div>
    </div>

    <div id="modalAdd" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2>Tambah Karyawan</h2><span class="close-modal" onclick="document.getElementById('modalAdd').style.display='none'">×</span></div>
            <form action="proses_karyawan.php" method="POST">
                <input type="hidden" name="action" value="add_employee">
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Posisi</label><input type="text" name="posisi" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Gaji Pokok (Rp)</label><input type="number" name="gaji" class="form-input" required></div>
                <button type="submit" class="btn-simpan" style="width:100%; padding:10px; background:#2563eb;">Simpan Data</button>
            </form>
        </div>
    </div>

    <div id="modalDelete" class="modal-overlay">
        <div class="modal-content modal-delete-content">
            <h2 style="color:#ef4444; margin-bottom:10px;">⚠️ Pecat Karyawan?</h2>
            <p style="margin-bottom:20px; color:#555;">Yakin ingin menghapus data <strong id="delName">...</strong>?</p>
            <form action="proses_karyawan.php" method="POST">
                <input type="hidden" name="action" value="delete_employee">
                <input type="hidden" name="id" id="delId">
                <button type="submit" class="btn-confirm-delete">Iya, Pecat</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalDelete').style.display='none'">Batal</button>
            </form>
        </div>
    </div>

    <script>
        // Fungsi Tab
        function switchTab(tabName) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
            document.getElementById('view-' + tabName).classList.add('active');
            document.getElementById('tab-btn-' + tabName).classList.add('active');
        }

        // Fungsi Modal Pembayaran (Salary/Bonus)
        function openPayModal(type, id, nama, amount) {
            document.getElementById('modalConfirmPay').style.display = 'flex';
            
            // Set Visual Text
            document.getElementById('payName').innerText = nama;
            document.getElementById('payAmount').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
            
            // Set Form Values
            document.getElementById('payId').value = id;
            document.getElementById('payNameInput').value = nama;
            document.getElementById('payAmountInput').value = amount;

            if(type === 'salary') {
                document.getElementById('payModalTitle').innerText = '💰 Bayar Gaji Bulanan';
                document.getElementById('payAction').value = 'pay_salary';
            } else {
                document.getElementById('payModalTitle').innerText = '⚡ Kirim Bonus Cair';
                document.getElementById('payAction').value = 'pay_bonus';
            }
        }

        function closePayModal() {
            document.getElementById('modalConfirmPay').style.display = 'none';
        }

        // Fungsi Modal Delete
        function confirmDelete(id, nama) {
            document.getElementById('delId').value = id;
            document.getElementById('delName').innerText = nama;
            document.getElementById('modalDelete').style.display = 'flex';
        }

        // Tutup modal jika klik luar
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
        }
    </script>

</body>
</html>