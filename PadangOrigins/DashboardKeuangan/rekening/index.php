<?php
// Pastikan path auth_check benar
require_once '../auth_check.php';

// Ambil data rekening yang sudah ada
$fileRekening = '../data/rekening.json';
$rekeningList = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekening Perusahaan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <style>
        /* Custom style untuk kartu rekening */
        .card-bank {
            border-left: 5px solid #ccc;
        }
        .bank-bca { border-color: #005eb8; } /* Biru BCA */
        .bank-bri { border-color: #00529c; } /* Biru BRI */
        .bank-bni { border-color: #f15a23; } /* Orange BNI */
        .bank-mandiri { border-color: #ffb700; } /* Kuning Mandiri */
        .ewallet-gopay { border-color: #00aeb6; } /* Hijau GoPay */
        .ewallet-ovo { border-color: #4c2a86; } /* Ungu OVO */
        .ewallet-dana { border-color: #118eea; } /* Biru Dana */
        
        .rek-number {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 1.1rem;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: inline-block;
            color: #555;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <h1 class="logo">Financial AI Core</h1>
            <nav>
                <a href="../index.php" class="nav-link">Dashboard</a>
                <a href="#" class="nav-link active">Rekening</a>
                <a href="../logout.php" class="nav-link btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div class="welcome-message" style="text-align:left; margin-bottom:0;">
                <h2>💳 Rekening & E-Wallet</h2>
                <p>Daftar aset keuangan yang terdaftar.</p>
            </div>
            <a href="../index.php" class="card-btn" style="background:#e2e8f0; color:#475569; box-shadow:none;">⬅ Kembali</a>
        </div>

        <section class="main-nav-cards">
            
            <?php if(empty($rekeningList)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 40px; color:#888;">
                    <p>Belum ada data rekening.</p>
                </div>
            <?php else: ?>
                <?php foreach($rekeningList as $rek): ?>
                    <?php 
                        // Logika sederhana untuk warna border berdasarkan ID atau Nama
                        $classWarna = 'card-bank';
                        $id = strtolower($rek['id']);
                        if(strpos($id, 'bca') !== false) $classWarna .= ' bank-bca';
                        elseif(strpos($id, 'bri') !== false) $classWarna .= ' bank-bri';
                        elseif(strpos($id, 'bni') !== false) $classWarna .= ' bank-bni';
                        elseif(strpos($id, 'mandiri') !== false) $classWarna .= ' bank-mandiri';
                        elseif(strpos($id, 'gopay') !== false) $classWarna .= ' ewallet-gopay';
                        elseif(strpos($id, 'ovo') !== false) $classWarna .= ' ewallet-ovo';
                        elseif(strpos($id, 'dana') !== false) $classWarna .= ' ewallet-dana';
                    ?>

                    <div class="nav-card <?php echo $classWarna; ?>" style="align-items: flex-start; text-align: left;">
                        <span class="status-badge" style="position:static; display:inline-block; margin-bottom:10px; background: <?php echo ($rek['jenis'] == 'bank') ? '#e0f2fe' : '#fce7f3'; ?>; color: <?php echo ($rek['jenis'] == 'bank') ? '#0284c7' : '#be185d'; ?>;">
                            <?php echo strtoupper($rek['jenis']); ?>
                        </span>

                        <h3 style="margin: 0; font-size: 1.5rem;"><?php echo htmlspecialchars($rek['nama_bank']); ?></h3>
                        
                        <div class="rek-number">
                            <?php echo htmlspecialchars($rek['nomer']); ?>
                        </div>

                        <div style="margin-top:auto; width:100%;">
                            <small style="color:#888;">Saldo Saat Ini:</small>
                            <p style="font-size:1.8rem; font-weight:700; color:#333; margin:0;">
                                Rp <?php echo number_format($rek['saldo'], 0, ',', '.'); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>

    </main>

</body>
</html>