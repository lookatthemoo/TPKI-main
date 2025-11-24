<?php
require_once 'auth_check.php';
header('Content-Type: application/json');
$file = 'data/transaksi.json';
if (!file_exists($file)) { echo json_encode(['labels'=>[],'revenue'=>[],'expense'=>[]]); exit; }
$trx = json_decode(file_get_contents($file), true) ?? [];
$rev = []; $exp = []; $lbl = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $lbl[] = date('d M', strtotime($d)); $rev[$d] = 0; $exp[$d] = 0;
}
foreach ($trx as $t) {
    $d = substr($t['tanggal'], 0, 10);
    if (isset($rev[$d])) {
        if ($t['tipe'] === 'pendapatan') $rev[$d] += (int)$t['jumlah'];
        elseif ($t['tipe'] === 'pengeluaran') $exp[$d] += (int)$t['jumlah'];
    }
}
echo json_encode(['labels'=>$lbl, 'revenue'=>array_values($rev), 'expense'=>array_values($exp)]); exit;
?>