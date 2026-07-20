<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit(); }
include 'config.php';

$username = $_SESSION['username'] ?? 'Guest';

// Featured movie (latest)
$featured = $conn->query("SELECT * FROM movies ORDER BY id DESC LIMIT 1")->fetch_assoc();

// All movies with booking count
$movies = $conn->query("
    SELECT m.*, COALESCE(b.cnt,0) AS bookings
    FROM movies m
    LEFT JOIN (SELECT movie_id, COUNT(*) AS cnt FROM bookings WHERE payment_status='completed' GROUP BY movie_id) b
    ON m.id = b.movie_id
    ORDER BY m.id DESC
");

// Genre list
$genres_res = $conn->query("SELECT DISTINCT genre FROM movies ORDER BY genre");
$genres = [];
while ($g = $genres_res->fetch_assoc()) $genres[] = $g['genre'];

// Stats
$total_movies   = $conn->query("SELECT COUNT(*) AS c FROM movies")->fetch_assoc()['c'];
$total_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE payment_status='completed'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>MovieFlix – Your Cinema, Anytime</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
:root {
    --green:#059669;--green-d:#047857;--green-l:#d1fae5;--green-bg:#f0fdf4;
    --gray-1:#111827;--gray-2:#1f2937;--gray-3:#374151;--gray-4:#6b7280;
    --gray-5:#9ca3af;--gray-6:#e5e7eb;--gray-7:#f3f4f6;--white:#ffffff;
    --radius:14px;--shadow:0 4px 20px rgba(0,0,0,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Outfit',sans-serif;background:var(--gray-7);color:var(--gray-1);min-height:100vh;}

/* ── Navbar ─────────────────────────────────── */
.hm-nav{position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.85);
    backdrop-filter:blur(14px);border-bottom:1px solid var(--gray-6);height:64px;}
.hm-nav-inner{max-width:1280px;margin:0 auto;height:100%;padding:0 28px;
    display:flex;align-items:center;justify-content:space-between;}
.hm-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.hm-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--green),var(--green-d));
    border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
.hm-logo-text{font-size:1.4rem;font-weight:800;color:var(--gray-1);}
.hm-nav-links{display:flex;align-items:center;gap:6px;}
.hm-nav-link{padding:7px 16px;border-radius:8px;font-size:.88rem;font-weight:600;
    color:var(--gray-3);text-decoration:none;transition:all .2s;}
.hm-nav-link:hover{background:var(--gray-7);color:var(--gray-1);}
.hm-nav-user{display:flex;align-items:center;gap:10px;font-size:.88rem;}
.hm-avatar{width:34px;height:34px;border-radius:9px;
    background:linear-gradient(135deg,var(--green),var(--green-d));
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:.95rem;}
.hm-username{font-weight:600;color:var(--gray-2);}
.hm-logout{padding:7px 16px;border-radius:8px;font-size:.88rem;font-weight:600;
    background:#fef2f2;color:#ef4444;border:1px solid #fecaca;text-decoration:none;transition:all .2s;}
.hm-logout:hover{background:#fee2e2;}

/* ── Hero ────────────────────────────────────── */
.hm-hero{margin-top:64px;position:relative;overflow:hidden;
    background:linear-gradient(135deg,#0f172a 0%,#1e3a2f 50%,#064e3b 100%);
    min-height:520px;display:flex;align-items:center;}
.hm-hero-bg{position:absolute;inset:0;opacity:.18;}
.hm-hero-bg img{width:100%;height:100%;object-fit:cover;}
.hm-hero-overlay{position:absolute;inset:0;
    background:linear-gradient(90deg,rgba(15,23,42,.95) 0%,rgba(15,23,42,.6) 60%,transparent 100%);}
.hm-hero-inner{position:relative;z-index:2;max-width:1280px;margin:0 auto;padding:60px 28px;
    display:grid;grid-template-columns:1fr 400px;gap:48px;align-items:center;}
.hm-hero-tag{display:inline-flex;align-items:center;gap:6px;
    background:rgba(5,150,105,.2);border:1px solid rgba(5,150,105,.4);
    color:#6ee7b7;font-size:.78rem;font-weight:700;padding:5px 14px;
    border-radius:20px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;}
.hm-hero-title{font-size:3.2rem;font-weight:900;color:#fff;line-height:1.1;margin-bottom:16px;}
.hm-hero-title span{color:#34d399;}
.hm-hero-sub{font-size:1.05rem;color:rgba(255,255,255,.7);margin-bottom:28px;line-height:1.6;max-width:480px;}
.hm-hero-btns{display:flex;gap:12px;flex-wrap:wrap;}
.hm-btn-primary{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;
    background:linear-gradient(135deg,var(--green),var(--green-d));color:#fff;
    border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;
    box-shadow:0 6px 20px rgba(5,150,105,.4);transition:all .25s;}
.hm-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(5,150,105,.5);}
.hm-btn-outline{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;
    background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.3);
    border-radius:10px;font-weight:600;font-size:.95rem;text-decoration:none;transition:all .25s;backdrop-filter:blur(6px);}
.hm-btn-outline:hover{background:rgba(255,255,255,.18);}
.hm-hero-stats{display:flex;gap:28px;margin-top:32px;}
.hm-stat{text-align:left;}
.hm-stat-val{font-size:1.8rem;font-weight:800;color:#fff;}
.hm-stat-lbl{font-size:.78rem;color:rgba(255,255,255,.5);font-weight:500;text-transform:uppercase;letter-spacing:.4px;}

/* Featured poster */
.hm-hero-poster{position:relative;}
.hm-hero-poster img{width:100%;border-radius:16px;box-shadow:0 30px 60px rgba(0,0,0,.5);
    border:3px solid rgba(255,255,255,.1);aspect-ratio:2/3;object-fit:contain;background:#1e3a2f;}
.hm-now-showing{position:absolute;top:14px;left:14px;
    background:var(--green);color:#fff;font-size:.72rem;font-weight:700;
    padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:.5px;}
.hm-poster-info{position:absolute;bottom:0;left:0;right:0;padding:20px 16px;
    background:linear-gradient(transparent,rgba(0,0,0,.85));border-radius:0 0 14px 14px;}
.hm-poster-title{color:#fff;font-weight:800;font-size:1rem;margin-bottom:4px;}
.hm-poster-meta{color:rgba(255,255,255,.6);font-size:.78rem;}

/* ── Stats Bar ───────────────────────────────── */
.hm-statsbar{background:var(--white);border-bottom:1px solid var(--gray-6);}
.hm-statsbar-inner{max-width:1280px;margin:0 auto;padding:18px 28px;
    display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.hm-statsbar-item{display:flex;align-items:center;gap:10px;}
.hm-statsbar-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;}
.hm-statsbar-icon.green{background:var(--green-bg);color:var(--green);}
.hm-statsbar-icon.blue{background:#eff6ff;color:#3b82f6;}
.hm-statsbar-icon.purple{background:#f5f3ff;color:#8b5cf6;}
.hm-statsbar-val{font-size:1.05rem;font-weight:800;color:var(--gray-1);}
.hm-statsbar-lbl{font-size:.75rem;color:var(--gray-5);font-weight:500;}

/* ── Section Wrapper ─────────────────────────── */
.hm-section{max-width:1280px;margin:0 auto;padding:44px 28px;}
.hm-section-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;gap:12px;flex-wrap:wrap;}
.hm-section-title{font-size:1.5rem;font-weight:800;color:var(--gray-1);}
.hm-section-sub{font-size:.88rem;color:var(--gray-4);margin-top:3px;}
.hm-view-all{font-size:.85rem;font-weight:600;color:var(--green);text-decoration:none;
    display:flex;align-items:center;gap:4px;white-space:nowrap;}
.hm-view-all:hover{color:var(--green-d);}

/* ── Genre Filter Pills ──────────────────────── */
.hm-genre-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;}
.hm-genre-pill{padding:7px 18px;border:1.5px solid var(--gray-6);border-radius:25px;
    font-size:.85rem;font-weight:600;color:var(--gray-3);background:var(--white);
    cursor:pointer;transition:all .2s;text-decoration:none;}
.hm-genre-pill:hover{border-color:var(--green);color:var(--green);background:var(--green-bg);}
.hm-genre-pill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── Movie Grid ──────────────────────────────── */
.hm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:20px;}
.hm-movie-card{background:var(--white);border:1px solid var(--gray-6);border-radius:var(--radius);
    overflow:hidden;transition:all .3s;cursor:pointer;animation:fadeUp .4s ease both;}
.hm-movie-card:hover{transform:translateY(-6px);border-color:var(--green);
    box-shadow:0 16px 40px rgba(5,150,105,.15);}
.hm-card-poster{position:relative;overflow:hidden;}
.hm-card-poster img{width:100%;aspect-ratio:2/3;object-fit:contain;background:var(--gray-7);
    transition:transform .4s ease;display:block;}
.hm-movie-card:hover .hm-card-poster img{transform:scale(1.06);}
.hm-card-genre{position:absolute;top:10px;left:10px;
    background:rgba(5,150,105,.9);color:#fff;font-size:.68rem;font-weight:700;
    padding:3px 10px;border-radius:15px;text-transform:uppercase;letter-spacing:.4px;backdrop-filter:blur(4px);}
.hm-card-overlay{position:absolute;inset:0;background:linear-gradient(transparent 50%,rgba(0,0,0,.75));
    opacity:0;transition:opacity .3s;}
.hm-movie-card:hover .hm-card-overlay{opacity:1;}
.hm-card-quick-btn{position:absolute;bottom:12px;left:12px;right:12px;
    background:var(--green);color:#fff;border:none;border-radius:8px;
    padding:9px;font-size:.85rem;font-weight:700;cursor:pointer;
    font-family:'Outfit',sans-serif;text-decoration:none;text-align:center;
    transform:translateY(8px);opacity:0;transition:all .3s;}
.hm-movie-card:hover .hm-card-quick-btn{transform:translateY(0);opacity:1;}
.hm-card-body{padding:13px 14px;}
.hm-card-title{font-size:.92rem;font-weight:700;color:var(--gray-1);margin-bottom:3px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hm-card-director{font-size:.76rem;color:var(--gray-4);margin-bottom:10px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hm-card-footer{display:flex;align-items:center;justify-content:space-between;}
.hm-card-year{font-size:.75rem;font-weight:600;color:var(--gray-5);}
.hm-card-book{display:block;width:100%;text-align:center;padding:8px;
    background:linear-gradient(135deg,var(--green),var(--green-d));color:#fff;
    border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;
    transition:all .2s;margin-top:8px;}
.hm-card-book:hover{background:var(--green-d);transform:translateY(-1px);}

/* ── Search Bar ──────────────────────────────── */
.hm-search-wrap{position:relative;max-width:520px;}
.hm-search-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);
    width:18px;height:18px;color:var(--gray-5);}
.hm-search{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--gray-6);
    border-radius:10px;font-size:.92rem;font-family:'Outfit',sans-serif;
    background:var(--white);color:var(--gray-1);outline:none;transition:all .2s;}
.hm-search:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(5,150,105,.1);}

/* ── Empty State ─────────────────────────────── */
.hm-empty{text-align:center;padding:60px 20px;color:var(--gray-4);}
.hm-empty-icon{font-size:3rem;margin-bottom:12px;}
.hm-empty h3{font-size:1.1rem;font-weight:700;color:var(--gray-2);margin-bottom:4px;}

/* ── Footer ──────────────────────────────────── */
.hm-footer{background:var(--gray-2);color:rgba(255,255,255,.7);}
.hm-footer-inner{max-width:1280px;margin:0 auto;padding:44px 28px 28px;}
.hm-footer-grid{display:grid;grid-template-columns:1.5fr repeat(3,1fr);gap:36px;margin-bottom:36px;}
.hm-footer-brand{font-size:1.3rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.hm-footer-desc{font-size:.85rem;line-height:1.6;color:rgba(255,255,255,.5);}
.hm-footer-heading{font-size:.8rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;}
.hm-footer-link{display:block;font-size:.85rem;color:rgba(255,255,255,.5);text-decoration:none;margin-bottom:8px;transition:color .2s;}
.hm-footer-link:hover{color:#fff;}
.hm-footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:20px;
    display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;
    font-size:.82rem;color:rgba(255,255,255,.35);}

/* ── Animations ──────────────────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
.hm-movie-card:nth-child(1){animation-delay:.04s}
.hm-movie-card:nth-child(2){animation-delay:.08s}
.hm-movie-card:nth-child(3){animation-delay:.12s}
.hm-movie-card:nth-child(4){animation-delay:.16s}
.hm-movie-card:nth-child(5){animation-delay:.20s}
.hm-movie-card:nth-child(n+6){animation-delay:.24s}

/* ── Responsive ──────────────────────────────── */
@media(max-width:900px){
    .hm-hero-inner{grid-template-columns:1fr;}.hm-hero-poster{display:none;}
    .hm-hero-title{font-size:2.2rem;}
    .hm-footer-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:600px){
    .hm-nav-links{display:none;}
    .hm-hero-title{font-size:1.8rem;}
    .hm-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
    .hm-section{padding:28px 16px;}
    .hm-footer-grid{grid-template-columns:1fr;}
    .hm-statsbar-inner{gap:14px;}
}
    </style>
</head>
<body>

<!-- ═══════════ NAVBAR ═══════════ -->
<nav class="hm-nav">
    <div class="hm-nav-inner">
        <a href="homepage.php" class="hm-logo">
            <div class="hm-logo-icon">🎬</div>
            <span class="hm-logo-text">MovieFlix</span>
        </a>
        <div class="hm-nav-links">
            <a href="#movies" class="hm-nav-link">Browse</a>
            <a href="user/index.php" class="hm-nav-link">Catalog</a>
        </div>
        <div class="hm-nav-user">
            <div class="hm-avatar"><?= strtoupper(substr($username,0,1)) ?></div>
            <span class="hm-username"><?= htmlspecialchars($username) ?></span>
            <a href="auth/logout.php" class="hm-logout">Sign Out</a>
        </div>
    </div>
</nav>

<!-- ═══════════ HERO ═══════════ -->
<section class="hm-hero">
    <?php if ($featured): ?>
    <div class="hm-hero-bg">
        <img src="uploads/<?= htmlspecialchars($featured['image']) ?>" alt=""/>
    </div>
    <?php endif; ?>
    <div class="hm-hero-overlay"></div>

    <div class="hm-hero-inner">
        <div>
            <div class="hm-hero-tag">🎟️ Now Booking</div>
            <h1 class="hm-hero-title">
                Your Next Favourite<br/>
                <span>Movie Awaits You</span>
            </h1>
            <p class="hm-hero-sub">
                Discover the latest blockbusters, book your seats in seconds,
                and enjoy a premium cinema experience — all from one place.
            </p>
            <div class="hm-hero-btns">
                <a href="#movies" class="hm-btn-primary">
                    🎬 Browse Movies
                </a>
                <a href="user/index.php" class="hm-btn-outline">
                    🔍 Full Catalog
                </a>
            </div>
            <div class="hm-hero-stats">
                <div class="hm-stat">
                    <div class="hm-stat-val"><?= $total_movies ?>+</div>
                    <div class="hm-stat-lbl">Movies</div>
                </div>
                <div class="hm-stat">
                    <div class="hm-stat-val"><?= $total_bookings ?>+</div>
                    <div class="hm-stat-lbl">Tickets Booked</div>
                </div>
                <div class="hm-stat">
                    <div class="hm-stat-val">4</div>
                    <div class="hm-stat-lbl">Screens</div>
                </div>
            </div>
        </div>

        <?php if ($featured): ?>
        <div class="hm-hero-poster">
            <span class="hm-now-showing">🔴 Now Showing</span>
            <img src="uploads/<?= htmlspecialchars($featured['image']) ?>"
                 alt="<?= htmlspecialchars($featured['title']) ?>"/>
            <div class="hm-poster-info">
                <div class="hm-poster-title"><?= htmlspecialchars($featured['title']) ?></div>
                <div class="hm-poster-meta">
                    <?= htmlspecialchars($featured['genre']) ?> &nbsp;·&nbsp; <?= $featured['release_year'] ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════ STATS BAR ═══════════ -->
<div class="hm-statsbar">
    <div class="hm-statsbar-inner">
        <div class="hm-statsbar-item">
            <div class="hm-statsbar-icon green">🎬</div>
            <div>
                <div class="hm-statsbar-val"><?= $total_movies ?> Movies</div>
                <div class="hm-statsbar-lbl">In our catalog</div>
            </div>
        </div>
        <div class="hm-statsbar-item">
            <div class="hm-statsbar-icon blue">🎟️</div>
            <div>
                <div class="hm-statsbar-val"><?= $total_bookings ?> Tickets</div>
                <div class="hm-statsbar-lbl">Already booked</div>
            </div>
        </div>
        <div class="hm-statsbar-item">
            <div class="hm-statsbar-icon purple">📺</div>
            <div>
                <div class="hm-statsbar-val">4 Screens</div>
                <div class="hm-statsbar-lbl">Available daily</div>
            </div>
        </div>
        <div style="font-size:.85rem;color:var(--gray-4);">
            Welcome back, <strong style="color:var(--green);"><?= htmlspecialchars($username) ?></strong> 👋
        </div>
    </div>
</div>

<!-- ═══════════ MOVIES SECTION ═══════════ -->
<section id="movies">
<div class="hm-section">

    <div class="hm-section-head">
        <div>
            <div class="hm-section-title">🎞️ All Movies</div>
            <div class="hm-section-sub">Click any movie to book your seats</div>
        </div>
        <a href="user/index.php" class="hm-view-all">
            View Full Catalog
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <!-- Genre Filter -->
    <div class="hm-genre-bar">
        <a href="homepage.php" class="hm-genre-pill <?= !isset($_GET['genre']) ? 'active' : '' ?>">All</a>
        <?php foreach ($genres as $g): ?>
            <a href="?genre=<?= urlencode($g) ?>"
               class="hm-genre-pill <?= (isset($_GET['genre']) && $_GET['genre']===$g) ? 'active' : '' ?>">
                <?= htmlspecialchars($g) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Movie Grid -->
    <div class="hm-grid" id="movieGrid">
    <?php
    $movies->data_seek(0);
    $genre_filter = $_GET['genre'] ?? '';
    $found = false;
    while ($movie = $movies->fetch_assoc()):
        if ($genre_filter && $movie['genre'] !== $genre_filter) continue;
        $found = true;
    ?>
        <div class="hm-movie-card">
            <div class="hm-card-poster">
                <img src="uploads/<?= htmlspecialchars($movie['image']) ?>"
                     alt="<?= htmlspecialchars($movie['title']) ?>"
                     loading="lazy"
                     onerror="this.style.background='#f3f4f6'"/>
                <span class="hm-card-genre"><?= htmlspecialchars($movie['genre']) ?></span>
                <div class="hm-card-overlay"></div>
                <a href="user/book_ticket.php?movie_id=<?= $movie['id'] ?>" class="hm-card-quick-btn">
                    🎟️ Book Now
                </a>
            </div>
            <div class="hm-card-body">
                <div class="hm-card-title" title="<?= htmlspecialchars($movie['title']) ?>">
                    <?= htmlspecialchars($movie['title']) ?>
                </div>
                <div class="hm-card-director"><?= htmlspecialchars($movie['director']) ?></div>
                <div class="hm-card-footer">
                    <span class="hm-card-year">📅 <?= $movie['release_year'] ?></span>
                    <?php if ($movie['bookings'] > 0): ?>
                    <span style="font-size:.72rem;color:var(--green);font-weight:600;">
                        🔥 <?= $movie['bookings'] ?> booked
                    </span>
                    <?php endif; ?>
                </div>
                <a href="user/book_ticket.php?movie_id=<?= $movie['id'] ?>" class="hm-card-book">
                    Book Ticket
                </a>
            </div>
        </div>
    <?php endwhile; ?>
    <?php if (!$found): ?>
        <div class="hm-empty" style="grid-column:1/-1;">
            <div class="hm-empty-icon">🎬</div>
            <h3>No movies in this genre yet</h3>
            <p><a href="homepage.php" style="color:var(--green);">View all movies</a></p>
        </div>
    <?php endif; ?>
    </div>

</div>
</section>

<!-- ═══════════ FOOTER ═══════════ -->
<footer class="hm-footer">
    <div class="hm-footer-inner">
        <div class="hm-footer-grid">
            <div>
                <div class="hm-footer-brand">🎬 MovieFlix</div>
                <p class="hm-footer-desc">Your premium movie booking platform. Discover, book, and enjoy the best cinema experience from the comfort of your browser.</p>
            </div>
            <div>
                <div class="hm-footer-heading">Browse</div>
                <a href="#movies" class="hm-footer-link">All Movies</a>
                <a href="user/index.php" class="hm-footer-link">Full Catalog</a>
                <?php foreach (array_slice($genres,0,4) as $g): ?>
                    <a href="?genre=<?= urlencode($g) ?>" class="hm-footer-link"><?= htmlspecialchars($g) ?></a>
                <?php endforeach; ?>
            </div>
            <div>
                <div class="hm-footer-heading">Account</div>
                <a href="user/index.php" class="hm-footer-link">My Bookings</a>
                <a href="auth/logout.php" class="hm-footer-link">Sign Out</a>
            </div>
            <div>
                <div class="hm-footer-heading">Showtimes</div>
                <a href="#" class="hm-footer-link">10:00 AM</a>
                <a href="#" class="hm-footer-link">1:00 PM</a>
                <a href="#" class="hm-footer-link">4:00 PM</a>
                <a href="#" class="hm-footer-link">7:00 PM</a>
                <a href="#" class="hm-footer-link">10:00 PM</a>
            </div>
        </div>
        <div class="hm-footer-bottom">
            <span>© <?= date('Y') ?> MovieFlix. All rights reserved.</span>
            <span>Made with ❤️ for cinema lovers</span>
        </div>
    </div>
</footer>

</body>
</html>
