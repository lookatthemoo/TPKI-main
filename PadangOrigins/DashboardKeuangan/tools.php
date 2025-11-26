<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Tools - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f1f5f9; font-family: 'Manrope', sans-serif; }
        
        .tools-header {
            background: white; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .back-btn {
            text-decoration: none; color: #64748b; font-weight: 600; 
            padding: 8px 16px; border-radius: 50px; background: #f8fafc; transition:0.2s;
        }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }

        .grid-container {
            max-width: 1400px; margin: 2rem auto; padding: 0 1rem;
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
        }

        .widget-card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;
            display: flex; flex-direction: column;
        }
        
        .widget-header {
            padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9;
            font-weight: 800; font-size: 1.1rem; color: #0f172a; display:flex; align-items:center; gap:10px;
        }

        .full-width { grid-column: 1 / -1; }

        @media (max-width: 1024px) { .grid-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="tools-header">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">🛠️ Pro Market Tools</h1>
            <p style="color: #64748b; font-size: 0.9rem;">Pusat analisa teknikal dan fundamental otomatis.</p>
        </div>
        <a href="index.php" class="back-btn">← Dashboard</a>
    </header>

    <main class="grid-container">

        <div class="widget-card full-width">
            <div class="widget-header">🌍 Komoditas & Mata Uang Global</div>
            <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-mini-symbol-overview.js" async>
                {
                "symbol": "FX_IDC:USDIDR",
                "width": "100%",
                "height": 220,
                "locale": "id",
                "dateRange": "12M",
                "colorTheme": "light",
                "isTransparent": false,
                "autosize": false,
                "largeChartUrl": ""
                }
                </script>
            </div>
            </div>

        <div class="widget-card">
            <div class="widget-header">🔍 Top Gainers & Losers (IHSG)</div>
            <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-hotlists.js" async>
                {
                "colorTheme": "light",
                "dateRange": "12M",
                "exchange": "IDX",
                "showChart": true,
                "locale": "id",
                "largeChartUrl": "",
                "isTransparent": false,
                "showSymbolLogo": true,
                "showFloatingTooltip": false,
                "width": "100%",
                "height": "600",
                "plotLineColorGrowing": "rgba(41, 98, 255, 1)",
                "plotLineColorFalling": "rgba(41, 98, 255, 1)",
                "gridLineColor": "rgba(240, 243, 250, 0)",
                "scaleFontColor": "rgba(106, 109, 120, 1)",
                "belowLineFillColorGrowing": "rgba(41, 98, 255, 0.12)",
                "belowLineFillColorFalling": "rgba(41, 98, 255, 0.12)",
                "belowLineFillColorGrowingBottom": "rgba(41, 98, 255, 0)",
                "belowLineFillColorFallingBottom": "rgba(41, 98, 255, 0)",
                "symbolActiveColor": "rgba(41, 98, 255, 0.12)"
                }
                </script>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">📅 Kalender Ekonomi</div>
            <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-events.js" async>
                {
                "colorTheme": "light",
                "isTransparent": false,
                "width": "100%",
                "height": "600",
                "locale": "id",
                "importanceFilter": "0,1",
                "currencyFilter": "IDR,USD,EUR"
                }
                </script>
            </div>
        </div>

        <div class="widget-card full-width">
            <div class="widget-header">₿ Pasar Crypto (Top Assets)</div>
            <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-screener.js" async>
                {
                "width": "100%",
                "height": 500,
                "defaultColumn": "overview",
                "defaultScreen": "general",
                "market": "crypto",
                "showToolbar": true,
                "colorTheme": "light",
                "locale": "id"
                }
                </script>
            </div>
        </div>

    </main>

</body>
</html>