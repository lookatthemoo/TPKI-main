<?php
require_once '../auth_check.php';

$baseDir = dirname(__DIR__);
$filePengeluaran = $baseDir . '/data/pengeluaran.json';

$jsonRaw = file_exists($filePengeluaran) ? file_get_contents($filePengeluaran) : '[]';
$pengeluaranList = json_decode($jsonRaw, true);
if (!is_array($pengeluaranList)) $pengeluaranList = [];

$totalExpense = 0;
$bulanIni = date('Y-m');
foreach ($pengeluaranList as $p) {
    if (isset($p['tanggal']) && strpos($p['tanggal'], $bulanIni) === 0) {
        $totalExpense += (int)($p['jumlah'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pengeluaran</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <style>
        /* ... style sama ... */
        .expense-header { background: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #ef4444; }
        .total-label { font-size: 0.9rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .total-amount { font-size: 2.5rem; font-weight: 700; color: #ef4444; margin-top: 5px; }
        .table-wrapper { background: white; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th { background: #f8fafc; padding: 1.2rem; text-align: left; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        td { padding: 1.2rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        .badge-cat { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .cat-ops { background: #ffedd5; color: #9a3412; } .cat-gaji { background: #dbeafe; color: #1e40af; } .cat-bonus { background: #dcfce7; color: #166534; } .cat-default { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container"><h1 class="logo">💸 Riwayat Pengeluaran</h1><nav><a href="../index.php" class="nav-link">Dashboard</a><a href="../logout.php" class="nav-link btn-logout">Logout</a></nav></div>
    </header>
    <main class="container">
        <div class="expense-header">
            <div><div class="total-label">Total Keluar (Bulan Ini)</div><div class="total-amount">Rp <?php echo number_format($totalExpense, 0, ',', '.'); ?></div></div>
            <div style="text-align:right; color:#94a3b8; font-size:0.9rem;"><i>*Data otomatis dari Kas Ops<br>& Penggajian Karyawan.</i></div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Waktu</th><th>Kategori</th><th>Deskripsi</th><th>Sumber Dana</th><th>Nominal</th><th>Petugas</th></tr></thead>
                <tbody>
                    <?php if(empty($pengeluaranList)): ?><tr><td colspan="6" style="text-align:center; padding:3rem; color:#94a3b8;">Belum ada data.</td></tr><?php else: ?>
                    <?php foreach(array_reverse($pengeluaranList) as $exp): 
                        $kat = $exp['kategori'] ?? 'Umum';
                        $badge = 'cat-default';
                        if(stripos($kat, 'Operasional') !== false) $badge = 'cat-ops';
                        if(stripos($kat, 'Gaji') !== false) $badge = 'cat-gaji';
                        if(stripos($kat, 'Bonus') !== false) $badge = 'cat-bonus';
                    ?>
                    <tr>
                        <td><strong><?php echo date('d M Y', strtotime($exp['tanggal'])); ?></strong><br><small style="color:#94a3b8;"><?php echo date('H:i', strtotime($exp['tanggal'])); ?></small></td>
                        <td><span class="badge-cat <?php echo $badge; ?>"><?php echo htmlspecialchars($kat); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($exp['deskripsi']); ?></strong><?php if(!empty($exp['penerima']) && $exp['penerima'] !== '-'): ?><br><small style="color:#64748b;">Penerima: <?php echo htmlspecialchars($exp['penerima']); ?></small><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($exp['sumber_dana'] ?? '-'); ?></td>
                        <td style="font-weight:700; color:#ef4444;">- Rp <?php echo number_format((int)$exp['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($exp['admin'] ?? 'System'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>