<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- KONFIGURASI FILE ---
$fileKaryawan = 'data/karyawan.json';
$fileRekening = 'data/rekening.json';
$fileTrx      = 'data/transaksi.json';
// [BARU] File Pengeluaran untuk pencatatan otomatis
$filePengeluaran = 'data/pengeluaran.json'; 

// --- HELPER FUNCTIONS ---
function getJson($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminName = $_SESSION['admin_username'] ?? 'Admin';

    // ==============================================================
    // 1. BAYAR GAJI BULANAN (OTOMATIS CATAT PENGELUARAN)
    // ==============================================================
    if ($action === 'pay_salary') {
        $id = $_POST['id'];
        $amount = (int)$_POST['amount'];
        $namaKaryawan = $_POST['nama'];

        // A. Cari Akun BCA
        $rekeningData = getJson($fileRekening);
        $bcaIndex = -1;
        $namaBank = "BCA"; // Default name

        foreach ($rekeningData as $i => $rek) {
            if (stripos($rek['nama_bank'], 'BCA') !== false) { 
                $bcaIndex = $i; 
                $namaBank = $rek['nama_bank']; // Ambil nama asli di json (misal: "Bank BCA")
                break; 
            }
        }

        // Validasi Saldo
        if ($bcaIndex === -1 || $rekeningData[$bcaIndex]['saldo'] < $amount) {
            header("Location: gaji.php?status=error_saldo_bca");
            exit;
        }

        // B. Potong Saldo BCA
        $rekeningData[$bcaIndex]['saldo'] -= $amount;
        saveJson($fileRekening, $rekeningData);

        // C. Update Data Karyawan (Masuk Dompet)
        $karyawanData = getJson($fileKaryawan);
        foreach ($karyawanData as &$k) {
            if ($k['id'] === $id) {
                $k['terakhir_gaji'] = date('Y-m-d');
                $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + $amount;
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);

        // D. Catat Log Transaksi (Global)
        $trxData = getJson($fileTrx);
        $trxData[] = [
            'id' => uniqid('SAL-'), 'tanggal' => date('Y-m-d H:i:s'),
            'tipe' => 'pengeluaran', 'akun_sumber' => $namaBank,
            'akun_tujuan' => "Karyawan: $namaKaryawan",
            'jumlah' => $amount, 'deskripsi' => "Gaji Bulanan: $namaKaryawan",
            'pelaku' => $adminName
        ];
        saveJson($fileTrx, $trxData);

        // E. [FITUR BARU] OTOMATIS MASUK KE PENGELUARAN.JSON
        $pengeluaranData = getJson($filePengeluaran);
        $pengeluaranData[] = [
            'id' => uniqid('EXP-AUTO-'),
            'tanggal' => date('Y-m-d H:i:s'),
            'kategori' => 'Gaji Karyawan', // Kategori otomatis
            'deskripsi' => "Gaji Bulanan: $namaKaryawan",
            'penerima' => $namaKaryawan,
            'sumber_dana' => $namaBank, // Otomatis BCA
            'jumlah' => $amount,
            'admin' => $adminName . ' (Auto)'
        ];
        saveJson($filePengeluaran, $pengeluaranData);

        header("Location: gaji.php?status=salary_ready");
        exit;
    }

    // ==============================================================
    // 2. BAYAR BONUS (OTOMATIS CATAT PENGELUARAN)
    // ==============================================================
    if ($action === 'pay_bonus') {
        $id = $_POST['id'];
        $amount = (int)$_POST['amount'];
        // Kita perlu ambil nama karyawan dulu dari ID
        $karyawanData = getJson($fileKaryawan);
        $namaKaryawan = "Karyawan";
        foreach($karyawanData as $k) { if($k['id'] === $id) { $namaKaryawan = $k['nama']; break; } }

        // A. Cari Akun BCA
        $rekeningData = getJson($fileRekening);
        $bcaIndex = -1;
        $namaBank = "BCA";

        foreach ($rekeningData as $i => $rek) {
            if (stripos($rek['nama_bank'], 'BCA') !== false) { 
                $bcaIndex = $i; 
                $namaBank = $rek['nama_bank'];
                break; 
            }
        }

        if ($bcaIndex !== -1 && $rekeningData[$bcaIndex]['saldo'] >= $amount) {
            // Potong Saldo
            $rekeningData[$bcaIndex]['saldo'] -= $amount;
            saveJson($fileRekening, $rekeningData);

            // Update Karyawan (Masuk Dompet)
            foreach ($karyawanData as &$k) {
                if ($k['id'] === $id) {
                    $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + $amount;
                    break;
                }
            }
            saveJson($fileKaryawan, $karyawanData);

            // Log Transaksi
            $trxData = getJson($fileTrx);
            $trxData[] = [
                'id' => uniqid('BON-'), 'tanggal' => date('Y-m-d H:i:s'),
                'tipe' => 'pengeluaran', 'akun_sumber' => $namaBank,
                'jumlah' => $amount, 'deskripsi' => "Bonus: $namaKaryawan",
                'pelaku' => $adminName
            ];
            saveJson($fileTrx, $trxData);

            // [FITUR BARU] OTOMATIS MASUK KE PENGELUARAN.JSON
            $pengeluaranData = getJson($filePengeluaran);
            $pengeluaranData[] = [
                'id' => uniqid('EXP-BON-'),
                'tanggal' => date('Y-m-d H:i:s'),
                'kategori' => 'Bonus',
                'deskripsi' => "Bonus Kinerja: $namaKaryawan",
                'penerima' => $namaKaryawan,
                'sumber_dana' => $namaBank,
                'jumlah' => $amount,
                'admin' => $adminName . ' (Auto)'
            ];
            saveJson($filePengeluaran, $pengeluaranData);
            
            header("Location: gaji.php?status=bonus_ready");
        } else {
            header("Location: gaji.php?status=error_saldo_bca");
        }
        exit;
    }

    // ==============================================================
    // 3. UPDATE DATA KARYAWAN (NON-KEUANGAN)
    // ==============================================================
    if ($action === 'update_data') {
        $id = $_POST['id'];
        $karyawanData = getJson($fileKaryawan);
        foreach ($karyawanData as &$k) {
            if ($k['id'] === $id) {
                $k['hadir'] = (int)$_POST['hadir'];
                $k['performa'] = $_POST['performa'];
                // Catatan: Penambahan bonus manual lewat form "Tambah Bonus" 
                // TIDAK otomatis motong saldo BCA, karena itu baru "Janji Bonus" (Pending).
                // Uang baru keluar saat tombol "Kirim Bonus (Via BCA)" ditekan.
                if (!empty($_POST['tambah_bonus'])) {
                    // Ini belum masuk dompet real, bisa dianggap 'hutang bonus' perusahaan
                    // Tapi untuk simplifikasi di sistem Anda sebelumnya, ini langsung masuk 'bonus_pending'
                    // Yang nanti bisa ditarik/dikirim.
                    // Kita tidak catat ke pengeluaran.json DULU, nanti pas dikirim baru catat.
                    $k['bonus_pending'] = ($k['bonus_pending'] ?? 0) + (int)$_POST['tambah_bonus'];
                }
                break;
            }
        }
        saveJson($fileKaryawan, $karyawanData);
        header("Location: gaji.php?tab=karyawan");
        exit;
    }

    // --- 4. TAMBAH KARYAWAN ---
    if ($action === 'add_employee') {
        $karyawanData = getJson($fileKaryawan);
        $karyawanData[] = [
            'id' => uniqid('EMP-'), 'nama' => htmlspecialchars($_POST['nama']),
            'posisi' => htmlspecialchars($_POST['posisi']), 'gaji_pokok' => (int)$_POST['gaji'],
            'hadir' => 0, 'izin' => 0, 'alpa' => 0, 'bonus_pending' => 0,
            'performa' => 'Baik', 'terakhir_gaji' => '-', 'terakhir_absen' => '-',
            'username' => strtolower(str_replace(' ', '', $_POST['nama'])),
            'password' => '12345'
        ];
        saveJson($fileKaryawan, $karyawanData);
        header("Location: gaji.php?tab=karyawan");
        exit;
    }

    // --- 5. HAPUS KARYAWAN ---
    if ($action === 'delete_employee') {
        $id = $_POST['id'];
        $karyawanData = getJson($fileKaryawan);
        $karyawanData = array_filter($karyawanData, function($k) use ($id) { return $k['id'] !== $id; });
        saveJson($fileKaryawan, array_values($karyawanData));
        header("Location: gaji.php?status=deleted");
        exit;
    }
}
header("Location: gaji.php");
?>