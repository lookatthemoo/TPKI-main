<?php
session_start();

// 1. Cek Sesi Login
if (!isset($_SESSION['kry_id'])) {
    header("Location: login.php");
    exit;
}

$idKaryawan = $_SESSION['kry_id'];
$fileKaryawan = '../data/karyawan.json';

// 2. Ambil Data Karyawan
$me = null;
if (file_exists($fileKaryawan)) {
    $karyawanData = json_decode(file_get_contents($fileKaryawan), true) ?? [];
    foreach ($karyawanData as $k) {
        if ($k['id'] === $idKaryawan) {
            $me = $k;
            break;
        }
    }
}

if (!$me) {
    session_destroy();
    die("Data karyawan tidak ditemukan. Silakan login ulang.");
}

// Sapaan Waktu
$jam = date('H');
$sapaan = ($jam < 11) ? "Selamat Pagi" : (($jam < 15) ? "Selamat Siang" : (($jam < 19) ? "Selamat Sore" : "Selamat Malam"));

// Warna & Icon Performa
$performaColor = ($me['performa'] ?? 'Baik') === 'Baik' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
$performaIcon = ($me['performa'] ?? 'Baik') === 'Baik' ? 'fa-thumbs-up' : 'fa-thumbs-down';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Karyawan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* RESET & BASE */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f0f2f5; color: #1f2937; }

        /* NAVBAR */
        .navbar {
            background: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 1.2rem; font-weight: 700; color: #2563eb; display: flex; align-items: center; gap: 8px; }
        .btn-logout { 
            background: #fee2e2; color: #ef4444; text-decoration: none; padding: 8px 16px; border-radius: 50px; 
            font-size: 0.9rem; font-weight: 600; transition: 0.2s; border: 1px solid #fecaca;
        }
        .btn-logout:hover { background: #ef4444; color: white; }

        /* CONTAINER */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }

        /* HERO SECTION */
        .hero-card {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white; padding: 2.5rem; border-radius: 20px; margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2); position: relative; overflow: hidden;
        }
        .hero-content h1 { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .hero-content p { opacity: 0.9; font-size: 1rem; }
        .hero-decoration { position: absolute; right: -20px; top: -20px; font-size: 10rem; opacity: 0.1; transform: rotate(15deg); }

        /* GRID SYSTEM */
        .dashboard-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;
        }

        /* CARD STYLE */
        .card {
            background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; }
        .icon-box {
            width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .card-title h3 { font-size: 1rem; font-weight: 600; color: #374151; }
        .card-title span { font-size: 0.8rem; color: #9ca3af; }

        /* CARD VARIANTS */
        .card-masuk .icon-box { background: #dcfce7; color: #166534; }
        .card-wallet .icon-box { background: #f3e8ff; color: #6b21a8; }
        .card-stats .icon-box { background: #e0f2fe; color: #1e40af; }

        /* BUTTONS */
        .btn-action {
            width: 100%; padding: 12px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer;
            font-size: 0.95rem; transition: 0.2s; margin-top: 10px;
        }
        .btn-green { background: #22c55e; color: white; } .btn-green:hover { background: #16a34a; }
        .btn-purple { background: #8b5cf6; color: white; } .btn-purple:hover { background: #7c3aed; }
        .btn-disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }

        /* WALLET SPECIFIC */
        .wallet-balance { font-size: 1.8rem; font-weight: 700; color: #111827; margin: 10px 0; }
        .wallet-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; }

        /* STATS GRID */
        .stats-mini-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
        .stat-box { background: #f9fafb; padding: 10px; border-radius: 8px; text-align: center; border: 1px solid #f3f4f6; }
        .stat-val { display: block; font-weight: 700; color: #1f2937; font-size: 1.1rem; }
        .stat-lbl { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; }

        /* PERFORMA BADGE */
        .performa-badge {
            margin-top: 15px; padding: 10px; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.9rem;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .bg-green-100 { background: #dcfce7; color: #166534; }
        .bg-red-100 { background: #fee2e2; color: #991b1b; }

        /* MODAL */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
        .modal-box { background: white; width: 90%; max-width: 400px; padding: 2rem; border-radius: 16px; animation: zoomIn 0.2s; }
        @keyframes zoomIn { from {transform: scale(0.9); opacity:0;} to {transform: scale(1); opacity:1;} }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #4b5563; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo"><i class="fas fa-cube"></i> Portal Karyawan</div>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>

    <div class="container">
        
        <div class="hero-card">
            <div class="hero-content">
                <h1><?= $sapaan ?>, <?= htmlspecialchars($me['nama']) ?></h1>
                <p>Posisi: <b><?= htmlspecialchars($me['posisi']) ?></b></p>
            </div>
            <i class="fas fa-chart-line hero-decoration"></i>
        </div>

        <?php if(isset($_GET['status'])): ?>
            <div style="padding: 15px; margin-bottom: 20px; border-radius: 10px; text-align: center; font-weight: 600;
                <?php 
                    if($_GET['status']=='success') echo 'background:#dcfce7; color:#166534;';
                    elseif($_GET['status']=='error') echo 'background:#fee2e2; color:#991b1b;';
                    else echo 'background:#e0f2fe; color:#075985;';
                ?>">
                <?php 
                    if($_GET['status']=='success') echo "✅ Absen berhasil dicatat!";
                    if($_GET['status']=='already_absen') echo "⚠️ Anda sudah absen hari ini.";
                    if($_GET['status']=='wd_success') echo "✅ Penarikan berhasil! Saldo telah ditransfer.";
                    if($_GET['status']=='wd_error_saldo') echo "⚠️ Saldo BCA Perusahaan tidak cukup / Error.";
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">

            <div class="card card-masuk">
                <div class="card-header">
                    <div class="icon-box"><i class="fas fa-calendar-check"></i></div>
                    <div class="card-title"><h3>Absensi Harian</h3><span>Catat Kehadiran</span></div>
                </div>
                
                <form action="proses_absen.php" method="POST">
                    <?php if(($me['terakhir_absen'] ?? '') == date('Y-m-d')): ?>
                        <button type="button" class="btn-action btn-disabled" disabled>
                            <i class="fas fa-check-circle"></i> Sudah Absen Hari Ini
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn-action btn-green">
                            <i class="fas fa-fingerprint"></i> Absen Sekarang
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card card-wallet">
                <div class="card-header">
                    <div class="icon-box"><i class="fas fa-wallet"></i></div>
                    <div class="card-title"><h3>Dompet & Bonus</h3><span>Saldo Siap Cair</span></div>
                </div>
                <div>
                    <span class="wallet-label">Total Saldo</span>
                    <div class="wallet-balance">Rp <?= number_format($me['bonus_pending'] ?? 0, 0, ',', '.') ?></div>
                </div>
                <button onclick="document.getElementById('modalWd').style.display='flex'" class="btn-action btn-purple">
                    <i class="fas fa-money-bill-wave"></i> Tarik ke Rekening
                </button>
            </div>

            <div class="card card-stats">
                <div class="card-header">
                    <div class="icon-box"><i class="fas fa-chart-pie"></i></div>
                    <div class="card-title"><h3>Kinerja Anda</h3><span>Penilaian HRD</span></div>
                </div>
                <div class="stats-mini-grid">
                    <div class="stat-box"><span class="stat-val"><?= $me['hadir'] ?? 0 ?></span><span class="stat-lbl">Hadir</span></div>
                    <div class="stat-box"><span class="stat-val"><?= $me['izin'] ?? 0 ?></span><span class="stat-lbl">Izin</span></div>
                    <div class="stat-box"><span class="stat-val"><?= $me['alpa'] ?? 0 ?></span><span class="stat-lbl">Alpa</span></div>
                </div>
                
                <div class="performa-badge <?= $performaColor ?>">
                    <i class="fas <?= $performaIcon ?>"></i> Status Performa: <?= $me['performa'] ?? 'Baik' ?>
                </div>
            </div>

        </div>
    </div>

    <div id="modalWd" class="modal">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                <h3 style="font-size:1.2rem; color:#1f2937;">💸 Tarik Saldo</h3>
                <span onclick="document.getElementById('modalWd').style.display='none'" style="cursor:pointer; font-size:1.5rem;">&times;</span>
            </div>
            
            <form action="proses_tarik.php" method="POST">
                <input type="hidden" name="id" value="<?= $me['id'] ?>">
                
                <div class="form-group">
                    <label>Nominal Penarikan (Rp)</label>
                    <input type="number" name="amount" class="form-control" 
                           max="<?= $me['bonus_pending'] ?? 0 ?>" placeholder="Maks: <?= $me['bonus_pending'] ?? 0 ?>" required>
                    <small style="color:#6b7280; display:block; margin-top:5px;">
                        Maksimal: <b>Rp <?= number_format($me['bonus_pending'] ?? 0, 0, ',', '.') ?></b>
                    </small>
                </div>

                <button type="submit" class="btn-action btn-purple">Cairkan Sekarang</button>
            </form>
        </div>
    </div>

    <script>
        window.onclick = function(e) {
            if (e.target == document.getElementById('modalWd')) {
                document.getElementById('modalWd').style.display = 'none';
            }
        }
    </script>

</body>
</html>