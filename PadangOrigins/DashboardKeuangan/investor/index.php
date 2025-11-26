<?php
session_start();
$filePortfolio = '../data/portfolio.json';
$fileRekening = '../data/rekening.json';

// Ambil Data Portofolio
$myPortfolio = file_exists($filePortfolio) ? json_decode(file_get_contents($filePortfolio), true) : [];

// Ambil Saldo Mandiri
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
    <title>Trading Floor - Financial AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* TEMA PUTIH (LIGHT MODE) */
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --green-profit: #10b981;
            --red-loss: #ef4444;
            --accent: #00695c; /* Warna Teal PadangOrigins */
            --accent-hover: #004d40;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); padding-bottom: 50px; }
        
        .navbar { 
            background: var(--card-bg); display: flex; justify-content: space-between; 
            padding: 1.2rem 2rem; border-bottom: 1px solid var(--border-color); align-items: center; 
            position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--accent); letter-spacing: -0.5px; }
        
        .balance-box { text-align: right; }
        .balance-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .balance-val { font-size: 1.3rem; font-weight: 800; color: var(--text-main); }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }

        /* Market List */
        .market-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .ticker-card {
            background: var(--card-bg); padding: 1.2rem; border-radius: 16px; margin-bottom: 1rem;
            display: flex; justify-content: space-between; align-items: center; 
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; 
            border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .ticker-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            border-color: var(--accent); 
        }
        .stock-code { font-weight: 800; font-size: 1.1rem; color: var(--text-main); }
        .stock-name { font-size: 0.85rem; color: var(--text-muted); }
        .stock-price { font-weight: 700; font-size: 1.1rem; text-align: right; }
        .stock-change { font-size: 0.85rem; text-align: right; font-weight: 600; }
        .up { color: var(--green-profit); }
        .down { color: var(--red-loss); }

        /* Portfolio Section */
        .portfolio-section { 
            background: var(--card-bg); padding: 1.5rem; border-radius: 20px; 
            height: fit-content; border: 1px solid var(--border-color); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); position: sticky; top: 100px;
        }
        .port-header { font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .port-item { margin-bottom: 1.5rem; background: #f1f5f9; padding: 1rem; border-radius: 12px; }
        .port-row { display: flex; justify-content: space-between; margin-bottom: 0.4rem; }
        .lbl { color: var(--text-muted); font-size: 0.9rem; }
        .val { font-weight: 600; color: var(--text-main); }
        .pnl-val { font-weight: 800; }

        /* Tombol Aksi */
        .btn-action-group { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .btn-sell { 
            flex: 1; padding: 0.6rem; background: var(--card-bg); border: 2px solid var(--red-loss); 
            color: var(--red-loss); border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; 
        }
        .btn-sell:hover { background: var(--red-loss); color: white; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-box { 
            background: var(--card-bg); padding: 2rem; border-radius: 24px; width: 420px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid var(--border-color);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--accent); }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 600; }
        .form-group input { 
            width: 100%; padding: 1rem; background: #f8fafc; border: 2px solid var(--border-color); 
            color: var(--text-main); border-radius: 12px; font-size: 1.1rem; font-weight: 600; outline: none; transition: 0.2s;
        }
        .form-group input:focus { border-color: var(--accent); }
        
        .btn-confirm { width: 100%; padding: 1.2rem; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 1rem; margin-top: 1rem; transition: 0.3s; }
        .btn-buy-confirm { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(0, 105, 92, 0.3); }
        .btn-buy-confirm:hover { background: var(--accent-hover); transform: translateY(-2px); }
        .btn-sell-confirm { background: var(--red-loss); color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
        .btn-sell-confirm:hover { background: #dc2626; transform: translateY(-2px); }
        
        .btn-close { position: absolute; top: 1.5rem; right: 1.5rem; cursor: pointer; color: var(--text-muted); font-size: 1.2rem; background: none; border: none; }

        /* Alert Box */
        .alert-box { 
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%); 
            padding: 1rem 2rem; border-radius: 50px; color: white; font-weight: 600; 
            z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); display: none;
            animation: fadeInDown 0.5s ease;
        }
        @keyframes fadeInDown { from { top: -50px; opacity: 0; } to { top: 20px; opacity: 1; } }
        .alert-error { background: var(--red-loss); }
        .alert-success { background: var(--green-profit); }

        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div id="alertError" class="alert-box alert-error">⚠️ Saldo Rekening Mandiri Tidak Cukup!</div>
    <div id="alertSuccess" class="alert-box alert-success">✅ Transaksi Berhasil!</div>

    <nav class="navbar">
        <div class="logo">Financial AI <span style="font-weight:300;">Trade</span></div>
        <div class="balance-box">
            <div class="balance-label">Saldo RDN (Mandiri)</div>
            <div class="balance-val">Rp <?= number_format($saldoMandiri, 0, ',', '.'); ?></div>
        </div>
    </nav>
    <div style="padding: 1rem 2rem; background: #fff; border-bottom: 1px solid #eee;">
        <a href="../../DashboardKeuangan/index.php" style="color: var(--text-muted); text-decoration: none; font-weight:600; font-size: 0.9rem;">← Kembali ke Dashboard</a>
    </div>

    <main class="container">
        
        <section>
            <div class="market-header">
                <h2 style="color: var(--text-main);">🇮🇩 IHSG Market Live</h2>
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="height:10px; width:10px; background:var(--green-profit); border-radius:50%; display:inline-block; animation: pulse 1.5s infinite;"></span>
                    <span style="color:var(--green-profit); font-weight:600; font-size:0.9rem;">Market Open</span>
                </div>
            </div>

            <div id="ticker-container">
                </div>
        </section>

        <aside class="portfolio-section">
            <div class="port-header">
                <span>💼 Portofolio Saya</span>
                <small style="font-weight:400; color:var(--text-muted);"><?= count($myPortfolio); ?> Emiten</small>
            </div>
            
            <?php if(empty($myPortfolio)): ?>
                <div style="text-align:center; padding: 2rem 0; color: var(--text-muted);">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">📉</div>
                    <p>Belum ada aset saham.</p>
                </div>
            <?php else: ?>
                <?php foreach($myPortfolio as $saham): ?>
                    <div class="port-item" id="port-<?= $saham['kode']; ?>" data-avg="<?= $saham['avg_price']; ?>" data-lot="<?= $saham['lot']; ?>">
                        <div class="port-row">
                            <span style="font-weight:800; font-size:1.1rem; color:var(--text-main);"><?= $saham['kode']; ?></span>
                            <span class="lbl" style="background: white; padding: 2px 8px; border-radius: 4px; border: 1px solid #eee;"><?= $saham['lot']; ?> Lot</span>
                        </div>
                        <div class="port-row">
                            <span class="lbl">Avg Price</span>
                            <span class="val">Rp <?= number_format($saham['avg_price']); ?></span>
                        </div>
                        <div class="port-row">
                            <span class="lbl">Market Price</span>
                            <span class="val cur-price-display">...</span>
                        </div>
                        <div class="port-row" style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #cbd5e1;">
                            <span class="lbl">Floating P/L</span>
                            <span class="pnl-val">...</span>
                        </div>
                        
                        <div class="btn-action-group">
                            <button class="btn-sell" onclick="openSellModal('<?= $saham['kode']; ?>', <?= $saham['lot']; ?>)">JUAL (Sell)</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </aside>

    </main>

    <div id="buyModal" class="modal-overlay">
        <div class="modal-box" style="position:relative;">
            <button class="btn-close" onclick="closeModal('buyModal')">✕</button>
            <div class="modal-title">Beli Saham <span id="buyStockCode" style="color:var(--accent);"></span></div>
            
            <form action="proses_beli_saham.php" method="POST">
                <input type="hidden" name="kode_saham" id="inputKodeBuy">
                <input type="hidden" name="harga_saat_ini" id="inputHargaBuy">

                <div class="form-group">
                    <label>Harga Pasar Saat Ini</label>
                    <input type="text" id="displayHargaBuy" readonly style="background: #e2e8f0; color: var(--text-muted);">
                </div>

                <div class="form-group">
                    <label>Jumlah Lot (1 Lot = 100 Lbr)</label>
                    <input type="number" name="jumlah_lot" id="inputLotBuy" min="1" value="1" oninput="hitungTotalBuy()" required>
                </div>

                <div class="form-group" style="background: #f0fdf4; padding: 1rem; border-radius: 12px; border: 1px solid #bbf7d0;">
                    <label style="color: #166534;">Estimasi Total Bayar</label>
                    <div id="totalBayarDisplay" style="font-size: 1.5rem; font-weight: 800; color: #166534;">Rp 0</div>
                </div>

                <button type="submit" class="btn-confirm btn-buy-confirm">KONFIRMASI BELI</button>
            </form>
        </div>
    </div>

    <div id="sellModal" class="modal-overlay">
        <div class="modal-box" style="position:relative;">
            <button class="btn-close" onclick="closeModal('sellModal')">✕</button>
            <div class="modal-title">Jual Saham <span id="sellStockCode" style="color:var(--red-loss);"></span></div>
            
            <form action="proses_jual_saham.php" method="POST">
                <input type="hidden" name="kode_saham" id="inputKodeSell">
                <input type="hidden" name="harga_jual" id="inputHargaSell">
                <input type="hidden" name="jumlah_lot" id="inputLotSellHidden">

                <div class="form-group">
                    <label>Harga Jual (Market Price)</label>
                    <input type="text" id="displayHargaSell" readonly style="background: #e2e8f0;">
                </div>

                <div class="form-group">
                    <label>Lot yang Dimiliki</label>
                    <input type="text" id="displayLotSell" readonly style="background: #e2e8f0;">
                </div>

                <div class="form-group" style="background: #fef2f2; padding: 1rem; border-radius: 12px; border: 1px solid #fecaca;">
                    <label style="color: #991b1b;">Total Uang Diterima (Termasuk Profit/Loss)</label>
                    <div id="totalTerimaDisplay" style="font-size: 1.5rem; font-weight: 800; color: #991b1b;">Rp 0</div>
                    <small style="display:block; margin-top:5px; color:#7f1d1d;">*Dana akan langsung masuk ke Saldo Mandiri</small>
                </div>

                <button type="submit" class="btn-confirm btn-sell-confirm">KONFIRMASI JUAL</button>
            </form>
        </div>
    </div>

    <script>
        // --- DATA & LOGIC ---
        const stocks = [
            { code: "BBCA", name: "Bank Central Asia Tbk", price: 9800 },
            { code: "BBRI", name: "Bank Rakyat Indonesia", price: 5600 },
            { code: "BMRI", name: "Bank Mandiri Persero", price: 6100 },
            { code: "TLKM", name: "Telkom Indonesia", price: 3800 },
            { code: "ASII", name: "Astra International", price: 5200 },
            { code: "GOTO", name: "GoTo Gojek Tokopedia", price: 82 },
            { code: "UNVR", name: "Unilever Indonesia", price: 3400 },
            { code: "ICBP", name: "Indofood CBP", price: 11200 }
        ];

        let livePrices = {};

        // 1. Render Ticker
        const container = document.getElementById('ticker-container');
        stocks.forEach(s => {
            livePrices[s.code] = s.price;
            const div = document.createElement('div');
            div.className = 'ticker-card';
            div.onclick = () => openBuyModal(s.code);
            div.innerHTML = `
                <div>
                    <div class="stock-code">${s.code}</div>
                    <div class="stock-name">${s.name}</div>
                </div>
                <div>
                    <div class="stock-price" id="price-${s.code}">Rp ${s.price.toLocaleString()}</div>
                    <div class="stock-change" id="change-${s.code}">0.00%</div>
                </div>
            `;
            container.appendChild(div);
        });

        // 2. Realtime Simulation
        setInterval(() => {
            stocks.forEach(s => {
                let volatility = s.price * 0.005; 
                let change = (Math.random() * volatility * 2) - volatility;
                let newPrice = Math.floor(livePrices[s.code] + change);
                if(newPrice < 50) newPrice = 50; 

                livePrices[s.code] = newPrice;
                let percent = ((newPrice - s.price) / s.price) * 100;

                // Update UI Ticker
                const elPrice = document.getElementById(`price-${s.code}`);
                const elChange = document.getElementById(`change-${s.code}`);
                
                elPrice.innerText = "Rp " + newPrice.toLocaleString();
                elChange.innerText = percent.toFixed(2) + "%";
                
                if(percent >= 0) {
                    elChange.className = "stock-change up";
                    elPrice.style.color = "var(--green-profit)";
                    elChange.innerText = "▲ " + elChange.innerText;
                } else {
                    elChange.className = "stock-change down";
                    elPrice.style.color = "var(--red-loss)";
                    elChange.innerText = "▼ " + elChange.innerText;
                }

                updatePortfolio(s.code, newPrice);
                updateModals(s.code, newPrice);
            });
        }, 1500);

        function updatePortfolio(code, currentPrice) {
            const portItem = document.getElementById(`port-${code}`);
            if(portItem) {
                const avgPrice = parseFloat(portItem.getAttribute('data-avg'));
                const lot = parseFloat(portItem.getAttribute('data-lot'));
                const lembar = lot * 100;

                // Update Display
                portItem.querySelector('.cur-price-display').innerText = "Rp " + currentPrice.toLocaleString();

                const pnl = (currentPrice - avgPrice) * lembar;
                const pnlPercent = ((currentPrice - avgPrice) / avgPrice) * 100;
                const pnlEl = portItem.querySelector('.pnl-val');
                
                let sign = pnl >= 0 ? "+" : "";
                pnlEl.innerText = `${sign}Rp ${Math.abs(pnl).toLocaleString()} (${pnlPercent.toFixed(2)}%)`;
                pnlEl.style.color = pnl >= 0 ? "var(--green-profit)" : "var(--red-loss)";
            }
        }

        function updateModals(code, price) {
            // Update Buy Modal jika terbuka
            if(document.getElementById('buyModal').style.display === 'flex' && document.getElementById('buyStockCode').innerText === code) {
                document.getElementById('inputHargaBuy').value = price;
                document.getElementById('displayHargaBuy').value = "Rp " + price.toLocaleString();
                hitungTotalBuy();
            }
            // Update Sell Modal jika terbuka
            if(document.getElementById('sellModal').style.display === 'flex' && document.getElementById('sellStockCode').innerText === code) {
                document.getElementById('inputHargaSell').value = price;
                document.getElementById('displayHargaSell').value = "Rp " + price.toLocaleString();
                hitungTotalSell();
            }
        }

        // --- MODAL FUNCTIONS ---
        function openBuyModal(code) {
            const price = livePrices[code];
            document.getElementById('buyStockCode').innerText = code;
            document.getElementById('inputKodeBuy').value = code;
            document.getElementById('inputHargaBuy').value = price;
            document.getElementById('displayHargaBuy').value = "Rp " + price.toLocaleString();
            document.getElementById('inputLotBuy').value = 1;
            hitungTotalBuy();
            document.getElementById('buyModal').style.display = 'flex';
        }

        function openSellModal(code, lot) {
            const price = livePrices[code];
            document.getElementById('sellStockCode').innerText = code;
            document.getElementById('inputKodeSell').value = code;
            document.getElementById('inputHargaSell').value = price;
            document.getElementById('inputLotSellHidden').value = lot;
            
            document.getElementById('displayHargaSell').value = "Rp " + price.toLocaleString();
            document.getElementById('displayLotSell').value = lot + " Lot (" + (lot*100) + " Lembar)";
            
            hitungTotalSell();
            document.getElementById('sellModal').style.display = 'flex';
        }

        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function hitungTotalBuy() {
            const harga = parseInt(document.getElementById('inputHargaBuy').value);
            const lot = parseInt(document.getElementById('inputLotBuy').value);
            const total = harga * lot * 100;
            document.getElementById('totalBayarDisplay').innerText = "Rp " + total.toLocaleString();
        }

        function hitungTotalSell() {
            const harga = parseInt(document.getElementById('inputHargaSell').value);
            const lot = parseInt(document.getElementById('inputLotSellHidden').value);
            const total = harga * lot * 100;
            document.getElementById('totalTerimaDisplay').innerText = "Rp " + total.toLocaleString();
        }

        // --- URL PARAMETER HANDLING (ERROR MESSAGE) ---
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('status') === 'gagal_saldo') {
            const alertBox = document.getElementById('alertError');
            alertBox.style.display = 'block';
            setTimeout(() => { alertBox.style.display = 'none'; }, 4000);
            // Hapus parameter agar tidak muncul terus saat refresh
            window.history.replaceState(null, null, window.location.pathname);
        } else if(urlParams.get('status') === 'sukses_beli' || urlParams.get('status') === 'sukses_jual') {
            const alertBox = document.getElementById('alertSuccess');
            if(urlParams.get('status') === 'sukses_jual') alertBox.innerText = "✅ Berhasil Menjual Saham & Menarik Dana!";
            alertBox.style.display = 'block';
            setTimeout(() => { alertBox.style.display = 'none'; }, 4000);
            window.history.replaceState(null, null, window.location.pathname);
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        }
        
        // Keyframes for Pulse Animation
        const styleSheet = document.createElement("style");
        styleSheet.innerText = `
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
                100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
            }
        `;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>