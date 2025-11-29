<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Tanya Jawab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            color: #1e293b;
        }
        .container {
            max-width: 800px;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
        }
        .icon-header {
            font-size: 80px;
            margin-bottom: 20px;
            display: block;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #0f172a;
        }
        .subtitle {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 40px;
        }
        
        /* BOX DALIL - BIAR MENCOLOK */
        .quote-box {
            background: #1e293b; /* Warna gelap biar serem dikit */
            color: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(30, 41, 59, 0.3);
            border: 2px solid #fbbf24; /* Border emas */
        }
        .quote-text {
            font-size: 1.5rem; /* Font gede biar kebaca sekelas */
            font-weight: 600;
            line-height: 1.6;
            font-style: italic;
        }
        .quote-source {
            display: block;
            margin-top: 15px;
            font-size: 1rem;
            color: #fbbf24; /* Warna emas */
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* PERINGATAN LUCU TAPI TEGAS */
        .warning-text {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            border: 2px dashed #fca5a5;
        }

        .btn-home {
            margin-top: 40px;
            display: inline-block;
            text-decoration: none;
            color: #94a3b8;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-home:hover { color: #334155; }
    </style>
</head>
<body>

    <div class="container">
        <span class="icon-header">🛑</span>
        <h1>Sesi Tanya Jawab</h1>
        <p class="subtitle">Silakan bertanya jika ada yang kurang jelas, tapi...</p>

        <div class="quote-box">
            <p class="quote-text">"Barangsiapa menyulitkan orang lain, Allah akan menyulitkan orang tersebut di hari kiamat."</p>
            <span class="quote-source">(Sahih Bukhari: 7153)</span>
        </div>

        <div class="warning-text">
            ⚠️ PERINGATAN: Mohon bikin pertanyaan yang masuk akal saja!
        </div>

        <br>
        <a href="index.php" class="btn-home">⬅ Kembali ke Dashboard Admin</a>
    </div>

</body>
</html>