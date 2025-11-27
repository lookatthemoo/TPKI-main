<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- CONFIG DATA ---
$filePortfolio = '../data/portfolio.json';
$fileRekening = '../data/rekening.json';

// Cek Jam Bursa (Senin-Jumat, 09:00 - 16:00)
$jamSekarang = (int)date('H');
$hariSekarang = date('N'); 
$isMarketOpen = ($hariSekarang <= 5 && $jamSekarang >= 9 && $jamSekarang < 16);
//$isMarketOpen = true;

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
        :root { --bg-body: #f0f3f9; --bg-card: #ffffff; --text-primary: #0f172a; --border: #e2e8f0; --accent: #2962ff; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Manrope', sans-serif; background: var(--bg-body); color: var(--text-primary); padding-bottom: 60px; }

        /* NAVBAR */
        .navbar { 
            background: var(--bg-card); padding: 1rem 2rem; 
            border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }

        /* BALANCE BOX (Dual Display) */
        .balance-wrapper { display: flex; gap: 30px; text-align: right; }
        .rdn-box small { color: #64748b; font-weight: 600; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; }
        .val-cash { font-size: 1.1rem; font-weight: 700; color: #334155; }
        .val-equity { font-size: 1.3rem; font-weight: 800; color: #2962ff; transition: color 0.3s; }

        /* LAYOUT */
        .trading-container { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; padding: 1.5rem; min-height: calc(100vh - 80px); }
        .chart-wrapper, .portfolio-wrapper { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .chart-wrapper { display: flex; flex-direction: column; min-height: 600px; }
        .portfolio-wrapper { padding: 1.5rem; overflow-y: auto; height: fit-content; }

        .action-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem; }
        .btn-trade { padding: 12px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; color: white; transition: 0.2s; }
        .btn-buy { background: #16a34a; } .btn-buy:hover { background: #15803d; }
        .btn-sell { background: #dc2626; } .btn-sell:hover { background: #b91c1c; }

        .port-item { padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
        .port-item:hover { transform: translateY(-2px); border-color: var(--accent); }
        
        .detail-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-top: 6px; color: #64748b; }
        .detail-row b { color: #0f172a; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 2rem; border-radius: 20px; width: 420px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: zoomIn 0.2s ease-out; }
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 6px; font-weight: 600; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-weight: 700; font-size: 1rem; color: #334155; outline: none; transition: 0.2s; }
        .form-input:focus { border-color: var(--accent); }
        
        .alert-float { position: fixed; top: 30px; left: 50%; transform: translateX(-50%); padding: 12px 24px; border-radius: 50px; color: white; font-weight: 600; z-index: 1000; display: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

        @media (max-width: 1024px) { .trading-container { grid-template-columns: 1fr; height: auto; } }
    </style>
</head>
<body>

    <div id="alertSuccess" class="alert-float" style="background: #16a34a;">✅ Order Berhasil!</div>
    <div id="alertFail" class="alert-float" style="background: #dc2626;">⚠️ Transaksi Gagal / Saldo Kurang</div>

    <nav class="navbar">
        <div class="logo">Antara 33 <span style="color:#2962ff;">FINANCE</span></div>
        
        <div class="balance-wrapper">
            <div class="rdn-box">
                <small>💵 Saldo Tunai (Buying Power)</small>
                <div class="val-cash">Rp <?= number_format($saldoMandiri, 0, ',', '.'); ?></div>
            </div>
            <div class="rdn-box">
                <small>📈 Total Aset (Live Equity)</small>
                <div class="val-equity" id="equityDisplay">Loading...</div>
            </div>
        </div>
    </nav>

    <div style="padding: 10px 2rem; font-size: 0.85rem; background:white; border-bottom:1px solid #eee;">
        <a href="../../DashboardKeuangan/index.php" style="text-decoration:none; color: #64748b; font-weight: 600;">← Kembali ke Dashboard Utama</a>
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
            { "proName": "FX_IDC:USDIDR", "title": "USD/IDR" }
        ],
        "showSymbolLogo": true, "colorTheme": "light", "isTransparent": false, "displayMode": "adaptive", "locale": "id"
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
                    "autosize": true, "symbol": "IDX:BBCA", "interval": "D", "timezone": "Asia/Jakarta", "theme": "light", "style": "1", "locale": "id",
                    "toolbar_bg": "#f1f3f6", "enable_publishing": false, "allow_symbol_change": true, "details": true, "hotlist": true, "calendar": true,
                    "studies": ["RSI@tv-basicstudies", "MACD@tv-basicstudies"], "container_id": "tradingview_chart"
                });
                </script>
            </div>
        </div>

        <div class="portfolio-wrapper">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 10px; font-weight: 800;">Panel Order</h3>
                <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
                    Status: <?php if($isMarketOpen): ?><span style="color:#16a34a; font-weight:bold; background: #dcfce7; padding: 2px 8px; border-radius: 4px;">● MARKET OPEN</span>
                    <?php else: ?><span style="color:#dc2626; font-weight:bold; background: #fee2e2; padding: 2px 8px; border-radius: 4px;">● MARKET CLOSED</span><?php endif; ?>
                </p>
                <div class="action-buttons">
                    <button class="btn-trade btn-buy" onclick="openModal('buy')" <?= !$isMarketOpen ? 'disabled style="opacity:0.5;"' : ''; ?>>BELI</button>
                    <button class="btn-trade btn-sell" onclick="openModal('sell')" <?= !$isMarketOpen ? 'disabled style="opacity:0.5;"' : ''; ?>>JUAL</button>
                </div>
            </div>

            <h3 style="margin-bottom: 1rem; font-weight: 800;">Portofolio Saya</h3>
            
            <?php if(empty($myPortfolio)): ?>
                <div style="text-align:center; color:#94a3b8; margin-top:20px; padding: 20px; border: 2px dashed #e2e8f0; border-radius: 10px;">📉<br>Belum ada aset saham.</div>
            <?php else: ?>
                <?php foreach($myPortfolio as $saham): ?>
                    <div class="port-item" data-code="<?= $saham['kode']; ?>" data-lot="<?= $saham['lot']; ?>" data-avg="<?= $saham['avg_price']; ?>">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <span style="font-weight: 800; color: var(--accent); font-size:1.1rem;"><?= $saham['kode']; ?></span>
                            <button onclick="fillSell('<?= $saham['kode']; ?>', <?= $saham['lot']; ?>)" style="padding:4px 10px; font-size:0.7rem; cursor:pointer; background:#fee2e2; color:#dc2626; border:none; border-radius:4px; font-weight:700;">JUAL</button>
                        </div>

                        <div class="detail-row">
                            <span>Kepemilikan:</span>
                            <b><?= $saham['lot']; ?> Lot</b>
                        </div>
                        <div class="detail-row">
                            <span>Avg Price:</span>
                            <b>Rp <?= number_format($saham['avg_price']); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Modal:</span>
                            <b>Rp <?= number_format($saham['avg_price'] * $saham['lot'] * 100); ?></b>
                        </div>

                        <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-top: 8px; background:#f1f5f9; padding:8px; border-radius:6px;">
                            <span style="color:#64748b; font-weight:600;">Keuntungan (P/L):</span>
                            <span class="pnl-val" style="font-weight: 800; color:#94a3b8;">Loading...</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="modalTrade" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-bottom: 1.5rem; font-weight: 800;">Order Saham</h3>
            <form id="formTrade" method="POST">
                <input type="hidden" name="tipe_order" id="tipeOrder"> 
                <div class="form-group">
                    <label>Kode Saham</label>
                    <input type="text" name="kode_saham" id="inputKode" placeholder="BBCA" required class="form-input" style="text-transform:uppercase;" oninput="cekHargaOtomatis()">
                    <small id="statusSaham" style="font-size:0.75rem; color:#64748b;">Ketik kode...</small>
                </div>
                <div class="form-group">
                    <label>Harga Saat Ini</label>
                    <input type="text" id="displayHarga" class="form-input" readonly style="background:#f1f5f9; font-weight:800;" value="...">
                    <input type="hidden" name="harga_saat_ini" id="inputHarga" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Lot</label>
                    <input type="number" name="jumlah_lot" id="inputLot" value="1" min="1" required class="form-input" oninput="calcTotal()">
                </div>
                <div style="background:#f1f5f9; padding:15px; border-radius:12px; margin-top:15px;">
                    <span style="font-size:0.85rem; color:#64748b; font-weight:600;">ESTIMASI TOTAL</span>
                    <div id="totalVal" style="font-size:1.8rem; font-weight:800; color:#0f172a;">Rp 0</div>
                </div>
                <button type="submit" id="btnSubmit" style="width:100%; padding:15px; margin-top:20px; border:none; border-radius:12px; font-weight:800; color:white; cursor:pointer;" disabled>BELUM SIAP</button>
            </form>
            <div style="text-align:center; margin-top:15px;"><span onclick="closeModal()" style="cursor:pointer; color:#64748b;">Batal</span></div>
        </div>
    </div>

    <script>
        const initialCash = <?= $saldoMandiri; ?>;
        let searchTimeout;

        // --- LOGIKA UTAMA: UPDATE PnL & EQUITY ---
        async function updatePnL() {
            const items = document.querySelectorAll('.port-item');
            let totalAssetValue = 0; // Total nilai saham saat ini

            for (let item of items) {
                const code = item.dataset.code;
                const lot = parseFloat(item.dataset.lot);
                const avg = parseFloat(item.dataset.avg);
                const pnlEl = item.querySelector('.pnl-val');

                try {
                    const res = await fetch('get_price.php?kode=' + code);
                    const d = await res.json();
                    if(d.status === 'success') {
                        const price = d.price;
                        const marketVal = price * lot * 100;
                        totalAssetValue += marketVal;

                        // Hitung PnL
                        const pnlPct = ((price - avg) / avg) * 100;
                        const pnlRp = marketVal - (avg * lot * 100);
                        
                        let color = pnlRp >= 0 ? '#16a34a' : '#dc2626';
                        let sign = pnlRp >= 0 ? '+' : '';
                        
                        // Tampilkan PnL (Persen & Rupiah)
                        pnlEl.innerHTML = `<span style="color:${color}">${sign}${pnlPct.toFixed(2)}% (${sign}Rp ${pnlRp.toLocaleString('id-ID')})</span>`;
                    }
                } catch(e) {}
            }

            // UPDATE TOTAL EQUITY DI NAVBAR
            // Total Equity = Saldo Tunai + Total Nilai Saham Live
            const totalEquity = initialCash + totalAssetValue;
            const elEq = document.getElementById('equityDisplay');
            elEq.innerText = "Rp " + totalEquity.toLocaleString('id-ID');

            // Warna Equity (Hijau jika Profit Total, Merah jika Rugi Total)
            // Asumsi Modal Awal adalah Saldo Tunai saat ini (sebenarnya kurang akurat, tapi cukup visual)
            // Logic lebih baik: Bandingkan dengan modal disetor (tapi data itu tidak ada di json simple ini)
            // Kita pakai warna hijau saja sebagai default "Aset Hidup"
            elEq.style.color = totalEquity >= initialCash ? "#16a34a" : "#dc2626";
        }

        setInterval(updatePnL, 10000);
        updatePnL();

        // --- FUNGSI FORM ---
        function cekHargaOtomatis() {
            const kode = document.getElementById('inputKode').value.toUpperCase();
            clearTimeout(searchTimeout);
            
            if (kode.length >= 4) {
                document.getElementById('statusSaham').innerText = "Mencari...";
                document.getElementById('btnSubmit').disabled = true;
                
                searchTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch('get_price.php?kode=' + kode);
                        const data = await res.json();
                        if (data.status === 'success') {
                            document.getElementById('inputHarga').value = data.price;
                            document.getElementById('displayHarga').value = "Rp " + data.price.toLocaleString();
                            document.getElementById('statusSaham').innerHTML = "<span style='color:#16a34a'>✅ Ditemukan</span>";
                            document.getElementById('btnSubmit').disabled = false;
                            document.getElementById('btnSubmit').innerText = "CONFIRM";
                            document.getElementById('btnSubmit').style.background = document.getElementById('tipeOrder').value == 'buy' ? '#16a34a' : '#dc2626';
                            calcTotal();
                        } else {
                            document.getElementById('displayHarga').value = "Tidak ditemukan";
                        }
                    } catch (e) {}
                }, 800);
            }
        }

        function calcTotal() {
            const p = parseInt(document.getElementById('inputHarga').value) || 0;
            const l = parseInt(document.getElementById('inputLot').value) || 0;
            document.getElementById('totalVal').innerText = "Rp " + (p * l * 100).toLocaleString('id-ID');
        }

        function openModal(type) {
            document.getElementById('modalTrade').style.display='flex';
            document.getElementById('tipeOrder').value = type;
            document.getElementById('inputKode').value = '';
            document.getElementById('totalVal').innerText = 'Rp 0';
            
            if(type === 'buy') {
                document.getElementById('modalTitle').innerText = "Beli Saham";
                document.getElementById('formTrade').action = "proses_beli_saham.php";
                document.getElementById('btnSubmit').style.background = "#94a3b8"; // Grey dulu sblm ready
            } else {
                document.getElementById('modalTitle').innerText = "Jual Saham";
                document.getElementById('formTrade').action = "proses_jual_saham.php";
                document.getElementById('btnSubmit').style.background = "#94a3b8";
            }
        }

        function fillSell(kode, lot) {
            openModal('sell');
            document.getElementById('inputKode').value = kode;
            document.getElementById('inputLot').value = lot;
            cekHargaOtomatis();
        }

        function closeModal() { document.getElementById('modalTrade').style.display='none'; }
        document.getElementById('inputKode').addEventListener('input', cekHargaOtomatis);

        // Alert Popups
        const url = new URLSearchParams(window.location.search);
        if(url.get('status') && url.get('status').includes('sukses')) {
            document.getElementById('alertSuccess').style.display='block';
            setTimeout(()=>document.getElementById('alertSuccess').style.display='none', 3000);
        }
    </script>
</body>
</html>