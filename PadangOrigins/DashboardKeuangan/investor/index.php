<?php
session_start();
// --- CONFIG DATA ---
$filePortfolio = '../data/portfolio.json';
$fileRekening = '../data/rekening.json';

// Cek Jam Bursa (Senin-Jumat, 09:00 - 16:00)
$jamSekarang = (int)date('H');
$hariSekarang = date('N'); 
$isMarketOpen = ($hariSekarang <= 5 && $jamSekarang >= 9 && $jamSekarang < 16);
// $isMarketOpen = true; // Uncomment kalau mau testing malam-malam

// Ambil Data
$myPortfolio = file_exists($filePortfolio) ? json_decode(file_get_contents($filePortfolio), true) : [];
$rekeningData = file_exists($fileRekening) ? json_decode(file_get_contents($fileRekening), true) : [];
$saldoMandiri = 0;
foreach ($rekeningData as $rek) {
    if (stripos($rek['nama_bank'], 'Mandiri') !== false) {
        $saldoMandiri = $rek['saldo'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Trading Desk - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f0f3f9;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --border: #e2e8f0;
            --accent: #2962ff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Manrope', sans-serif; background: var(--bg-body); color: var(--text-primary); padding-bottom: 60px; }

        /* NAVBAR */
        .navbar { 
            background: var(--bg-card); padding: 1rem 2rem; 
            border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }
        .rdn-box { text-align: right; }
        .rdn-val { font-size: 1.1rem; font-weight: 700; color: #16a34a; }

        /* GRID UTAMA */
        .trading-container {
            display: grid;
            grid-template-columns: 1fr 380px; /* Chart Lebar, Portofolio Sempit */
            gap: 1.5rem;
            padding: 1.5rem;
            min-height: calc(100vh - 80px);
        }

        /* CHART SECTION */
        .chart-wrapper {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            overflow: hidden; display: flex; flex-direction: column; min-height: 600px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* PORTFOLIO SECTION */
        .portfolio-wrapper {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); height: fit-content;
        }

        .action-buttons {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem;
        }
        .btn-trade {
            padding: 12px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; color: white; transition: 0.2s;
        }
        .btn-buy { background: #16a34a; }
        .btn-buy:hover { background: #15803d; }
        .btn-sell { background: #dc2626; }
        .btn-sell:hover { background: #b91c1c; }

        .port-item {
            padding: 12px; border: 1px solid #f1f5f9; border-radius: 10px; margin-bottom: 10px; background: #f8fafc;
        }
        .port-item h4 { display: flex; justify-content: space-between; margin-bottom: 5px; color: var(--text-primary); }

        /* HEATMAP SECTION */
        .heatmap-container {
            padding: 0 1.5rem 2rem 1.5rem;
        }
        .heatmap-wrapper {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            overflow: hidden; padding: 10px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 2rem; border-radius: 20px; width: 420px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: zoomIn 0.2s ease-out; }
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 6px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-weight: 700; font-size: 1rem; color: #334155; outline: none; transition: 0.2s; }
        .form-group input:focus { border-color: var(--accent); }
        
        .alert-float { position: fixed; top: 30px; left: 50%; transform: translateX(-50%); padding: 12px 24px; border-radius: 50px; color: white; font-weight: 600; z-index: 1000; display: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

        @media (max-width: 1024px) { .trading-container { grid-template-columns: 1fr; height: auto; } }
    </style>
</head>
<body>

    <div id="alertSuccess" class="alert-float" style="background: #16a34a;">✅ Order Berhasil!</div>
    <div id="alertFail" class="alert-float" style="background: #dc2626;">⚠️ Transaksi Gagal / Saldo Kurang</div>

    <nav class="navbar">
        <div class="logo">Antara 33 <span style="color:#2962ff;">FINANCE</span></div>
        <div class="rdn-box">
            <small style="color:#64748b; font-weight: 600;">Saldo RDN (Mandiri)</small>
            <div class="rdn-val">Rp <?= number_format($saldoMandiri, 0, ',', '.'); ?></div>
        </div>
    </nav>
    <div style="padding: 10px 2rem; font-size: 0.85rem; background:white; border-bottom:1px solid #eee;">
        <a href="../../home/index.php" style="text-decoration:none; color: #64748b; font-weight: 600;">← Kembali ke Dashboard Utama</a>
    </div>

    <div class="tradingview-widget-container">
        <div class="tradingview-widget-container__widget"></div>
        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js" async>
        {
        "symbols": [
            { "proName": "IDX:COMPOSITE", "title": "IHSG" },
            { "proName": "IDX:BBCA", "title": "BCA" },
            { "proName": "IDX:BBRI", "title": "BRI" },
            { "proName": "IDX:TLKM", "title": "Telkom" },
            { "proName": "IDX:GOTO", "title": "GoTo" },
            { "proName": "IDX:ASII", "title": "Astra" },
            { "proName": "IDX:ANTM", "title": "Aneka Tambang" },
            { "proName": "FX_IDC:USDIDR", "title": "USD/IDR" }
        ],
        "showSymbolLogo": true,
        "colorTheme": "light",
        "isTransparent": false,
        "displayMode": "adaptive",
        "locale": "id"
        }
        </script>
    </div>

    <main class="trading-container">
        
        <div class="chart-wrapper">
            <div class="tradingview-widget-container" style="height:100%; width:100%">
                <div id="tradingview_chart" style="height:100%; width:100%"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
                <script type="text/javascript">
                new TradingView.widget({
                    "autosize": true,
                    "symbol": "IDX:BBCA", 
                    "interval": "D",
                    "timezone": "Asia/Jakarta",
                    "theme": "light",
                    "style": "1",
                    "locale": "id",
                    "toolbar_bg": "#f1f3f6",
                    "enable_publishing": false,
                    "allow_symbol_change": true, 
                    "details": true,
                    "hotlist": true,
                    "calendar": true,
                    "studies": [
                        "RSI@tv-basicstudies",      
                        "MACD@tv-basicstudies",     
                        "MASimple@tv-basicstudies"  
                    ],
                    "container_id": "tradingview_chart"
                });
                </script>
            </div>
        </div>

        <div class="portfolio-wrapper">
            
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 10px; font-weight: 800;">Panel Order</h3>
                <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
                    Status Pasar: 
                    <?php if($isMarketOpen): ?>
                        <span style="color:#16a34a; font-weight:bold; background: #dcfce7; padding: 2px 8px; border-radius: 4px;">● MARKET OPEN</span>
                    <?php else: ?>
                        <span style="color:#dc2626; font-weight:bold; background: #fee2e2; padding: 2px 8px; border-radius: 4px;">● MARKET CLOSED</span>
                    <?php endif; ?>
                </p>

                <div class="action-buttons">
                    <button class="btn-trade btn-buy" onclick="openModal('buy')" <?= !$isMarketOpen ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>BELI (Buy)</button>
                    <button class="btn-trade btn-sell" onclick="openModal('sell')" <?= !$isMarketOpen ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>JUAL (Sell)</button>
                </div>
                <small style="color:#64748b; font-size:0.75rem;">*Lihat harga di chart sebelah kiri sebelum order.</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <div class="tradingview-widget-container">
                    <div class="tradingview-widget-container__widget"></div>
                    <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
                    {
                    "interval": "1D",
                    "width": "100%",
                    "isTransparent": true,
                    "height": "300",
                    "symbol": "IDX:BBCA",
                    "showIntervalTabs": false,
                    "displayMode": "single",
                    "locale": "id",
                    "colorTheme": "light"
                    }
                    </script>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin-bottom:1rem;">

            <h3 style="margin-bottom: 1rem; font-weight: 800;">Portofolio Saya</h3>
            
            <?php if(empty($myPortfolio)): ?>
                <div style="text-align:center; color:#94a3b8; margin-top:20px; padding: 20px; border: 2px dashed #e2e8f0; border-radius: 10px;">
                    📉<br>Belum ada aset saham.
                </div>
            <?php else: ?>
                <?php foreach($myPortfolio as $saham): ?>
                    <div class="port-item">
                        <h4>
                            <span style="font-weight: 800; color: var(--accent);"><?= $saham['kode']; ?></span>
                            <span style="font-size:0.8rem; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; color: #475569;"><?= $saham['lot']; ?> Lot</span>
                        </h4>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:#64748b; margin-top: 5px;">
                            <span>Avg Price:</span>
                            <span style="font-weight: 600; color: #334155;">Rp <?= number_format($saham['avg_price']); ?></span>
                        </div>
                        <div style="margin-top:8px; font-size:0.8rem; text-align:right;">
                            <a href="#" onclick="alert('Silakan cek chart untuk update harga <?= $saham['kode']; ?> terkini')" style="text-decoration:none; color:#2962ff; font-weight: 600;">⚡ Cek Realtime</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <section class="heatmap-container">
        <h3 style="margin-bottom: 1rem; font-weight: 800; color: var(--text-primary);">Market Overview (Heatmap)</h3>
        <div class="heatmap-wrapper">
            <div class="tradingview-widget-container">
            <div class="tradingview-widget-container__widget"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-stock-heatmap.js" async>
            {
            "exchanges": [],
            "dataSource": "IDX",
            "grouping": "sector",
            "blockSize": "market_cap_basic",
            "blockColor": "change",
            "locale": "id",
            "symbolUrl": "",
            "colorTheme": "light",
            "hasTopBar": false,
            "isDataSetEnabled": false,
            "isZoomEnabled": true,
            "hasSymbolTooltip": true,
            "width": "100%",
            "height": "500"
            }
            </script>
            </div>
        </div>
    </section>

    <div id="modalTrade" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-bottom: 1.5rem; font-weight: 800;">Order Saham</h3>
            
            <form id="formTrade" method="POST">
                <input type="hidden" name="tipe_order" id="tipeOrder"> 
                
                <div class="form-group">
                    <label>Kode Saham</label>
                    <input type="text" name="kode_saham" id="inputKode" placeholder="Contoh: BBCA" required style="text-transform:uppercase; letter-spacing: 1px;">
                </div>

                <div class="form-group">
                    <label>Harga Saat Ini (Lihat Chart)</label>
                    <input type="number" name="harga_saat_ini" id="inputHarga" placeholder="Masukkan harga real di chart" required oninput="calcTotal()">
                    <small style="color:#ef4444; font-size:0.75rem; font-weight: 600;">*Wajib isi sesuai harga chart agar valid!</small>
                </div>

                <div class="form-group">
                    <label>Jumlah Lot (1 Lot = 100 Lbr)</label>
                    <input type="number" name="jumlah_lot" id="inputLot" value="1" min="1" required oninput="calcTotal()">
                </div>

                <div style="background:#f1f5f9; padding:15px; border-radius:12px; margin-top:15px; border: 1px solid #e2e8f0;">
                    <span style="font-size:0.85rem; color:#64748b; font-weight: 600; text-transform: uppercase;">Total Estimasi</span>
                    <div id="totalVal" style="font-size:1.8rem; font-weight:800; color:#0f172a; margin-top: 5px;">Rp 0</div>
                </div>

                <button type="submit" id="btnSubmit" style="width:100%; padding:15px; margin-top:20px; border:none; border-radius:12px; font-weight:800; color:white; cursor:pointer; font-size: 1rem; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">CONFIRM</button>
            </form>
            <div style="text-align:center; margin-top:15px;">
                <span onclick="closeModal()" style="cursor:pointer; color:#64748b; font-size:0.9rem; font-weight: 600;">Batal</span>
            </div>
        </div>
    </div>

    <script>
        function openModal(type) {
            const modal = document.getElementById('modalTrade');
            const form = document.getElementById('formTrade');
            const btn = document.getElementById('btnSubmit');
            const title = document.getElementById('modalTitle');
            const inputType = document.getElementById('tipeOrder');

            inputType.value = type;
            modal.style.display = 'flex';

            if(type === 'buy') {
                title.innerText = "Beli Saham";
                title.style.color = "#16a34a";
                form.action = "proses_beli_saham.php";
                btn.style.backgroundColor = "#16a34a";
                btn.innerText = "BELI SEKARANG";
            } else {
                title.innerText = "Jual Saham";
                title.style.color = "#dc2626";
                form.action = "proses_jual_saham.php"; 
                btn.style.backgroundColor = "#dc2626";
                btn.innerText = "JUAL SEKARANG";
            }
        }

        function closeModal() {
            document.getElementById('modalTrade').style.display = 'none';
        }

        function calcTotal() {
            const p = parseInt(document.getElementById('inputHarga').value) || 0;
            const l = parseInt(document.getElementById('inputLot').value) || 0;
            const total = p * l * 100;
            document.getElementById('totalVal').innerText = "Rp " + total.toLocaleString();
        }

        // Handle Alert Status
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('status') === 'sukses_beli' || urlParams.get('status') === 'sukses_jual') {
            document.getElementById('alertSuccess').style.display = 'block';
            setTimeout(() => document.getElementById('alertSuccess').style.display = 'none', 3000);
            window.history.replaceState(null, null, window.location.pathname);
        } else if(urlParams.get('status')) {
            document.getElementById('alertFail').style.display = 'block';
            setTimeout(() => document.getElementById('alertFail').style.display = 'none', 3000);
            window.history.replaceState(null, null, window.location.pathname);
        }

        window.onclick = function(e) {
            if (e.target.className === 'modal-overlay') {
                closeModal();
            }
        }
    </script>
</body>
</html>