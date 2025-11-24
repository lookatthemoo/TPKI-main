<?php
// === PENGATURAN AWAL ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth_check.php';

$fileKaryawan = 'data/karyawan.json';
$fileTransaksi = 'data/transaksi.json';

// --- FUNGSI BANTUAN ---
function getJson($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// --- LOGIKA UTAMA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (empty($action)) {
        die("Error Sistem: Tidak ada 'action' yang diterima.");
    }

    $karyawanData = getJson($fileKaryawan);

    // 1. TAMBAH KARYAWAN (AUTO USERNAME & PASSWORD)
    if ($action === 'add_employee') {
        $nama = trim($_POST['nama']);
        
        // Bikin Username: Huruf kecil semua & buang spasi
        // Contoh: "Asep Surasep" jadi "asepsurasep"
        $usernameAuto = strtolower(str_replace(' ', '', $nama));
        
        // Password Default
        $passwordDefault = "123";

        $newEmp = [
            'id' => 'KRY-' . time(),
            'nama' => $nama,
            'posisi' => $_POST['posisi'],
            'username' => $usernameAuto, // Otomatis
            'password' => $passwordDefault, // Otomatis
            'gaji_pokok' => (int)$_POST['gaji'],
            'saldo_gaji' => 0, 'saldo_bonus' => 0, 'bonus_pending' => 0,
            'hadir' => 0, 'izin' => 0, 'performa' => 'Baik',
            'terakhir_gaji' => '-', 'terakhir_absen' => ''
        ];
        
        $karyawanData[] = $newEmp;
        saveJson($fileKaryawan, $karyawanData);
        header('Location: gaji.php?status=added'); exit;
    }

    // 2. UPDATE DATA
    if ($action === 'update_data') {
        $id = $_POST['id'];
        foreach ($karyawanData as &$k) {
            if ($k['id'] === $id) {
                if(isset($_POST['hadir'])) $k['hadir'] = (int)$_POST['hadir'];
                if(isset($_POST['izin'])) $k['izin'] = (int)$_POST['izin'];
                if(isset($_POST['performa'])) $k['performa'] = $_POST['performa'];
                
                if(isset($_POST['tambah_bonus']) && $_POST['tambah_bonus'] > 0) {
                    $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + (int)$_POST['tambah_bonus'];
                }
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);
        header('Location: gaji.php?tab=karyawan'); exit;
    }

    // 3. CAIRKAN BONUS
    if ($action === 'pay_bonus') {
        $id = $_POST['id'];
        $amount = (int)$_POST['amount'];
        foreach ($karyawanData as &$k) {
            if ($k['id'] === $id) {
                $k['saldo_bonus'] = ($k['saldo_bonus'] ?? 0) + $amount;
                $k['bonus_pending'] = 0;
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);
        header('Location: gaji.php?tab=karyawan&status=bonus_ready'); exit;
    }

    // 4. KIRIM GAJI
    if ($action === 'pay_salary') {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $amount = (int)$_POST['amount'];

        $trxData = getJson($fileTransaksi);
        $trxData[] = [
            'id' => 'PAY-' . time(),
            'tanggal' => date('Y-m-d H:i:s'),
            'tipe' => 'pengeluaran',
            'jumlah' => $amount,
            'deskripsi' => "Gaji Bulanan: $nama",
            'pelaku' => $_SESSION['admin_username'] ?? 'Admin'
        ];
        saveJson($fileTransaksi, $trxData);

        foreach ($karyawanData as &$k) {
            if ($k['id'] === $id) {
                $k['saldo_gaji'] = ($k['saldo_gaji'] ?? 0) + $amount;
                $k['terakhir_gaji'] = date('d M Y');
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);
        header('Location: gaji.php?tab=gaji&status=salary_ready'); exit;
    }

    // 5. PECAT KARYAWAN
    if ($action === 'delete_employee') {
        $id = $_POST['id'];
        $newData = [];
        foreach ($karyawanData as $k) {
            if ($k['id'] !== $id) {
                $newData[] = $k;
            }
        }
        saveJson($fileKaryawan, $newData);
        header('Location: gaji.php?status=deleted'); exit;
    }
}

header('Location: gaji.php');
exit;
?>