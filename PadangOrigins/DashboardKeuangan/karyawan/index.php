<?php
session_start();
if (!isset($_SESSION['kry_logged_in'])) { header('Location: login.php'); exit; }

// --- 1. CONFIG PATH YANG BENAR ---
// Karena folder 'karyawan' ada di dalam 'DashboardKeuangan', cukup mundur 1 langkah
$pathData = '../data/';

$fileKaryawan = $pathData . 'karyawan.json';
$filePengeluaran = $pathData . 'pengeluaran.json'; 
$fileTransaksi = $pathData . 'transaksi.json';

// Validasi File
if (!file_exists($fileKaryawan)) die("Error: File database tidak ditemukan di: " . realpath($pathData));

// --- 2. AMBIL DATA DIRI ---
$idSaya = $_SESSION['kry_id'];
$karyawanList = json_decode(file_get_contents($fileKaryawan), true) ?? [];
$dataSaya = null;

foreach ($karyawanList as $k) {
    if ($k['id'] === $idSaya) { $dataSaya = $k; break; }
}
if (!$dataSaya) { session_destroy(); header('Location: login.php'); exit; }

$namaSaya = $dataSaya['nama']; 

// --- 3. HITUNG SALDO REAL-TIME (LOGIKA BARU) ---
$pengeluaranList = file_exists($filePengeluaran) ? json_decode(file_get_contents($filePengeluaran), true) : [];
$transaksiList = file_exists($fileTransaksi) ? json_decode(file_get_contents($fileTransaksi), true) : [];

// A. Hitung Pemasukan (Dari Admin)
$totalGajiMasuk = 0;
$totalBonusMasuk = 0;

foreach ($pengeluaranList as $p) {
    // Cari item yang penerimanya SAYA
    if (isset($p['penerima']) && strtolower($p['penerima']) === strtolower($namaSaya)) {
        $kat = strtolower($p['kategori'] ?? '');
        $jml = (int)$p['jumlah'];

        if (strpos($kat, 'gaji') !== false) $totalGajiMasuk += $jml;
        if (strpos($kat, 'bonus') !== false) $totalBonusMasuk += $jml;
    }
}

// B. Hitung Pengeluaran (Saya Tarik Tunai)
$totalTarikGaji = 0;
$totalTarikBonus = 0;
$riwayatSaya = [];

foreach ($transaksiList as $t) {
    // Cari transaksi penarikan oleh SAYA
    if (($t['tipe'] ?? '') === 'pengeluaran' && strtolower($t['pelaku'] ?? '') === strtolower($namaSaya)) {
        $desc = strtolower($t['deskripsi'] ?? '');
        $jml = (int)$t['jumlah'];
        $riwayatSaya[] = $t;

        // Pisahkan penarikan Gaji vs Bonus berdasarkan deskripsi
        if (strpos($desc, 'gaji') !== false) $totalTarikGaji += $jml;
        elseif (strpos($desc, 'bonus') !== false) $totalTarikBonus += $jml;
        // Default: kalau tidak ada ket, ambil dari gaji dulu
        else $totalTarikGaji += $jml;
    }
}

// C. Saldo Akhir
$saldoGaji = $totalGajiMasuk - $totalTarikGaji;
$saldoBonus = $totalBonusMasuk - $totalTarikBonus;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Poppins',sans-serif;background:#f3f4f6;margin:0;padding:20px}.container{max-width:500px;margin:0 auto}
        .card{background:white;padding:20px;border-radius:15px;box-shadow:0 4px 10px rgba(0,0,0,0.05);margin-bottom:20px}
        .header{background:#2563eb;color:white;padding:25px;border-radius:20px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center}
        .btn{padding:10px 20px;border-radius:10px;border:none;font-weight:bold;cursor:pointer;color:white;width:100%}
        .btn-absen{background:#2563eb}.btn-gaji{background:#16a34a}.btn-bonus{background:#f59e0b}
        .btn:disabled{background:#ccc;cursor:not-allowed}
        .saldo-box{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .nominal{font-size:1.5rem;font-weight:bold;color:#1f2937}
        .history-item{border-bottom:1px solid #eee;padding:10px 0;display:flex;justify-content:space-between;font-size:0.9rem}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <small>Halo,</small>
                <h2 style="margin:0"><?php echo htmlspecialchars($dataSaya['nama']); ?></h2>
                <span style="font-size:0.8rem;background:rgba(255,255,255,0.2);padding:3px 8px;border-radius:5px"><?php echo $dataSaya['posisi']; ?></span>
            </div>
            <a href="logout.php" onclick="return confirm('Keluar?')" style="color:white;text-decoration:underline;font-size:0.9rem">Logout</a>
        </div>

        <div class="card">
            <form action="proses_absen.php" method="POST">
                <?php if(($dataSaya['terakhir_absen'] ?? '') === date('Y-m-d')): ?>
                    <button type="button" class="btn" style="background:#dbeafe;color:#1e40af">✅ Sudah Absen Hari Ini</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-absen">📍 Absen Masuk</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <small style="color:#6b7280;font-weight:bold">DOMPET GAJI</small>
            <div class="saldo-box">
                <div class="nominal">Rp <?php echo number_format($saldoGaji,0,',','.'); ?></div>
            </div>
            <?php if($saldoGaji > 0): ?>
                <button onclick="modal('gaji', <?php echo $saldoGaji; ?>)" class="btn btn-gaji">Tarik Gaji</button>
            <?php else: ?>
                <button class="btn" disabled>Saldo Kosong</button>
            <?php endif; ?>
        </div>

        <div class="card">
            <small style="color:#6b7280;font-weight:bold">DOMPET BONUS</small>
            <div class="saldo-box">
                <div class="nominal">Rp <?php echo number_format($saldoBonus,0,',','.'); ?></div>
            </div>
            <?php if($saldoBonus > 0): ?>
                <button onclick="modal('bonus', <?php echo $saldoBonus; ?>)" class="btn btn-bonus">Tarik Bonus</button>
            <?php else: ?>
                <button class="btn" disabled>Saldo Kosong</button>
            <?php endif; ?>
        </div>

        <div class="card">
            <h4 style="margin-top:0">Riwayat Penarikan</h4>
            <?php foreach(array_slice(array_reverse($riwayatSaya), 0, 5) as $rw): ?>
                <div class="history-item">
                    <div><?php echo $rw['deskripsi']; ?><br><small style="color:#999"><?php echo substr($rw['tanggal'],0,16); ?></small></div>
                    <div style="color:#dc2626;font-weight:bold">-<?php echo number_format($rw['jumlah']); ?></div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($riwayatSaya)) echo "<small>Belum ada data.</small>"; ?>
        </div>
    </div>

    <div id="modalTarik" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center">
        <div style="background:white;padding:25px;border-radius:15px;width:85%;max-width:350px">
            <h3 style="margin-top:0">Tarik Dana</h3>
            <form action="proses_tarik.php" method="POST" onsubmit="return confirm('Yakin tarik dana?')">
                <input type="hidden" name="sumber" id="inpSumber">
                <p>Maksimal: <b id="txtMax"></b></p>
                <input type="number" name="jumlah" style="width:100%;padding:10px;margin-bottom:15px;box-sizing:border-box" required placeholder="Masukkan jumlah..." min="1000">
                <div style="display:flex;gap:10px">
                    <button type="button" onclick="document.getElementById('modalTarik').style.display='none'" class="btn" style="background:#eee;color:#333">Batal</button>
                    <button type="submit" class="btn btn-absen">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function modal(jenis, max) {
            document.getElementById('modalTarik').style.display='flex';
            document.getElementById('inpSumber').value=jenis;
            document.getElementById('txtMax').innerText = 'Rp '+new Intl.NumberFormat('id-ID').format(max);
        }
    </script>
</body>
</html>