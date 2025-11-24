<?php
session_start();
// Matikan pesan warning di layar agar tampilan bersih
error_reporting(0); 

if (isset($_SESSION['kry_logged_in'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $fileJson = '../data/karyawan.json';
    if (file_exists($fileJson)) {
        $data = json_decode(file_get_contents($fileJson), true);
        
        foreach ($data as $k) {
            // --- PERBAIKAN DI SINI ---
            // Cek dulu apakah data karyawan ini punya username & password?
            // Jika tidak punya (misal data Owner), skip/lewati saja.
            if (!isset($k['username']) || !isset($k['password'])) {
                continue; 
            }

            // Jika punya, baru dicek loginnya
            if ($k['username'] === $user && $k['password'] === $pass) {
                $_SESSION['kry_logged_in'] = true;
                $_SESSION['kry_id'] = $k['id'];
                $_SESSION['kry_nama'] = $k['nama'];
                header('Location: index.php');
                exit;
            }
        }
        $error = "Username atau password salah!";
    } else {
        $error = "Database karyawan tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Karyawan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🔐 Portal Karyawan</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <?php if($error): ?>
                    <p style="color:#ef4444; background:#fee2e2; padding:10px; border-radius:8px; margin-bottom:1rem; font-weight:600; font-size:0.9rem;">
                        ⚠️ <?= $error ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>