<?php
require_once 'auth_check.php';

// --- FUNGSI PENYEDOT BERITA ANTI-BLOKIR ---
function fetchRSS($url) {
    // 1. Menyamar sebagai Browser Chrome agar tidak diblokir (Fix Error 403)
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    
    // 2. Coba ambil konten mentah dulu
    // Menggunakan @ untuk menyembunyikan warning jika masih gagal (supaya tampilan web tidak rusak)
    $content = @file_get_contents($url, false, $context);
    
    if ($content === FALSE) {
        return null; // Gagal ambil data
    }

    // 3. Parsing XML dari konten yang didapat
    $rss = @simplexml_load_string($content);
    return $rss;
}

// --- KONFIGURASI SUMBER BERITA (RSS FEEDS) ---
// URL sudah diperbarui ke yang paling stabil
$feedSources = [
    [
        'name' => 'CNBC Indonesia',
        'url'  => 'https://www.cnbcindonesia.com/market/rss', // Biasanya paling stabil
        'color'=> '#002855',
        'text_color' => '#fff',
        'icon' => '📈'
    ],
    [
        'name' => 'Kontan.co.id',
        'url'  => 'https://www.kontan.co.id/feed', // URL Feed Utama Kontan (Lebih jarang 403)
        'color'=> '#da291c',
        'text_color' => '#fff',
        'icon' => '💰'
    ],
    [
        'name' => 'CNN Ekonomi',
        'url'  => 'https://www.cnnindonesia.com/ekonomi/rss',
        'color'=> '#cc0000',
        'text_color' => '#fff',
        'icon' => '🌏'
    ],
    [
        'name' => 'Republika Ekonomi', // Ganti Bisnis.com (sering error) dengan Republika
        'url'  => 'https://www.republika.co.id/rss/ekonomi', 
        'color'=> '#1e293b',
        'text_color' => '#fff',
        'icon' => '🕋'
    ]
];

// --- PROSES PENGAMBILAN BERITA ---
$allNews = [];
foreach ($feedSources as $src) {
    $rss = fetchRSS($src['url']);
    
    if ($rss) {
        $count = 0;
        foreach ($rss->channel->item as $item) {
            if ($count >= 5) break; // Ambil 5 berita per sumber
            
            // Bersihkan Deskripsi
            $desc = strip_tags((string)$item->description);
            $desc = mb_strimwidth($desc, 0, 110, "...");

            // Cari Gambar (Thumbnail)
            $img = 'https://via.placeholder.com/300x160?text=No+Image'; // Default
            
            // Cek standar RSS (enclosure)
            if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                $img = (string)$item->enclosure['url'];
            } 
            // Cek standar Media (biasa di CNN/CNBC)
            elseif ($item->children('media', true)->content) {
                $attr = $item->children('media', true)->content->attributes();
                if ($attr['url']) $img = (string)$attr['url'];
            }
            // Cek gambar dalam deskripsi HTML
            elseif (preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', (string)$item->description, $image)) {
                $img = $image['src'];
            }

            $allNews[] = [
                'title' => (string)$item->title,
                'link'  => (string)$item->link,
                'date'  => date('d M, H:i', strtotime((string)$item->pubDate)),
                'desc'  => $desc,
                'img'   => $img,
                'source'=> $src['name'],
                'color' => $src['color'],
                'text_color' => $src['text_color']
            ];
            $count++;
        }
    }
}

// Acak urutan biar tidak monoton per sumber
shuffle($allNews);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Keuangan - PadangOrigins</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f8fafc; font-family: 'Manrope', sans-serif; }
        
        .news-header {
            background: white; padding: 2rem; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        
        .news-grid {
            max-width: 1200px; margin: 2rem auto; padding: 0 1rem;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .news-card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;
            transition: 0.3s; display: flex; flex-direction: column; height: 100%;
        }
        .news-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }

        .news-img {
            height: 180px; width: 100%; object-fit: cover; background: #eee;
        }

        .news-content { padding: 1.2rem; flex: 1; display: flex; flex-direction: column; }
        
        .news-meta {
            font-size: 0.75rem; margin-bottom: 0.5rem; display: flex; 
            justify-content: space-between; align-items: center;
        }
        .badge-source {
            padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
        }

        .news-title {
            font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.8rem;
            line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        
        .news-desc {
            font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem; flex: 1;
            line-height: 1.6;
        }

        .btn-read {
            text-decoration: none; font-weight: 700; font-size: 0.9rem; color: #2962ff;
            display: inline-flex; align-items: center; gap: 5px; margin-top: auto;
        }
        .btn-read:hover { text-decoration: underline; }

        .back-btn {
            text-decoration: none; color: #64748b; font-weight: 600; 
            display: flex; align-items: center; gap: 5px; transition: 0.2s;
            padding: 8px 16px; border-radius: 50px; background: #f1f5f9;
        }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }

        /* Sumber Filter */
        .sources-bar {
            max-width: 1200px; margin: 1rem auto 0 auto; padding: 0 1rem;
            display: flex; gap: 10px; overflow-x: auto;
        }
        .source-pill {
            display: flex; align-items: center; gap: 5px; padding: 6px 12px; 
            background: white; border-radius: 50px; font-size: 0.85rem; font-weight: 600;
            border: 1px solid #e2e8f0; color: #475569; white-space: nowrap;
        }
    </style>
</head>
<body>

    <header class="news-header">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #0f172a;">🇮🇩 Indo Financial News</h1>
            <p style="color: #64748b;">Agregator berita ekonomi & saham terpercaya.</p>
        </div>
        <a href="index.php" class="back-btn">← Dashboard</a>
    </header>

    <div class="sources-bar">
        <?php foreach($feedSources as $src): ?>
            <div class="source-pill">
                <span><?= $src['icon']; ?></span> <?= $src['name']; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <main class="news-grid">
        
        <?php if(empty($allNews)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem;">
                <h3 style="color: #94a3b8;">Sedang memuat berita atau koneksi terbatas...</h3>
                <p style="color: #cbd5e1;">Pastikan server localhost Anda terhubung ke internet.</p>
            </div>
        <?php else: ?>
            <?php foreach($allNews as $news): ?>
                <article class="news-card">
                    <img src="<?= $news['img']; ?>" alt="News Image" class="news-img" onerror="this.src='https://via.placeholder.com/300x160?text=News+Update'">
                    <div class="news-content">
                        <div class="news-meta">
                            <span class="badge-source" style="background: <?= $news['color']; ?>; color: <?= $news['text_color']; ?>;">
                                <?= $news['source']; ?>
                            </span>
                            <span style="color: #94a3b8;"><?= $news['date']; ?></span>
                        </div>
                        <h3 class="news-title"><?= $news['title']; ?></h3>
                        <p class="news-desc"><?= $news['desc']; ?></p>
                        <a href="<?= $news['link']; ?>" target="_blank" class="btn-read">
                            Baca Selengkapnya ➔
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <footer style="text-align:center; padding: 2rem; color: #94a3b8; font-size: 0.85rem;">
        &copy; <?= date('Y'); ?> PadangOrigins Financial AI Core. Powered by RSS Feeds.
    </footer>

</body>
</html>