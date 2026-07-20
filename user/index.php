<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$username = $_SESSION['username'] ?? 'User';
$search   = trim($_GET['search'] ?? '');
$genre_f  = trim($_GET['genre']  ?? '');
$sort     = trim($_GET['sort']   ?? 'newest');

// Genres
$genres_res = $conn->query("SELECT DISTINCT genre FROM movies ORDER BY genre");
$genres = [];
while ($g = $genres_res->fetch_assoc()) $genres[] = $g['genre'];

// Build query
$sql = "SELECT m.*, COALESCE(b.cnt,0) AS bookings
        FROM movies m
        LEFT JOIN (SELECT movie_id, COUNT(*) AS cnt FROM bookings WHERE payment_status='completed' GROUP BY movie_id) b
        ON m.id = b.movie_id WHERE 1";
if ($search)  $sql .= " AND m.title LIKE '%" . $conn->real_escape_string($search) . "%'";
if ($genre_f) $sql .= " AND m.genre = '" . $conn->real_escape_string($genre_f) . "'";
$sql .= match($sort) {
    'popular' => " ORDER BY bookings DESC",
    'title'   => " ORDER BY m.title ASC",
    'year'    => " ORDER BY m.release_year DESC",
    default   => " ORDER BY m.id DESC",
};
$result = $conn->query($sql);
$total  = $result ? $result->num_rows : 0;

// User's own bookings count
$uid = (int)$_SESSION['user_id'];
$my_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE user_id=$uid AND payment_status='completed'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Movie Catalog | MovieFlix</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
:root{--green:#059669;--green-d:#047857;--green-l:#d1fae5;--green-bg:#f0fdf4;
    --gray-1:#111827;--gray-2:#1f2937;--gray-3:#374151;--gray-4:#6b7280;
    --gray-5:#9ca3af;--gray-6:#e5e7eb;--gray-7:#f3f4f6;--white:#ffffff;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Outfit',sans-serif;background:var(--gray-7);color:var(--gray-1);min-height:100vh;}

/* Navbar */
.cat-nav{background:linear-gradient(135deg,var(--green) 0%,var(--green-d) 100%);
    position:sticky;top:0;z-index:100;box-shadow:0 4px 20px rgba(5,150,105,.3);}
.cat-nav-inner{max-width:1280px;margin:0 auto;height:62px;padding:0 24px;
    display:flex;align-items:center;justify-content:space-between;gap:16px;}
.cat-logo{font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:8px;}
.cat-nav-right{display:flex;align-items:center;gap:10px;}
.cat-welcome{font-size:.85rem;color:rgba(255,255,255,.8);font-weight:500;}
.cat-home-btn{padding:7px 16px;background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);
    border-radius:7px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;}
.cat-home-btn:hover{background:rgba(255,255,255,.25);}
.cat-logout{padding:7px 16px;background:#fff;color:var(--green);border-radius:7px;
    font-size:.85rem;font-weight:700;text-decoration:none;transition:all .2s;}
.cat-logout:hover{background:var(--green-l);}

/* Page header */
.cat-page-head{background:linear-gradient(135deg,rgba(5,150,105,.1) 0%,transparent 100%);
    border-bottom:1px solid var(--gray-6);padding:28px 24px;}
.cat-page-head-inner{max-width:1280px;margin:0 auto;}
.cat-page-title{font-size:1.7rem;font-weight:800;color:var(--gray-1);margin-bottom:4px;}
.cat-page-sub{font-size:.9rem;color:var(--gray-4);}
.cat-user-stats{display:flex;gap:20px;margin-top:16px;flex-wrap:wrap;}
.cat-user-stat{display:flex;align-items:center;gap:8px;background:var(--white);
    border:1px solid var(--gray-6);padding:8px 16px;border-radius:9px;}
.cat-user-stat-icon{font-size:1rem;}
.cat-user-stat-val{font-weight:700;color:var(--gray-1);font-size:.9rem;}
.cat-user-stat-lbl{font-size:.75rem;color:var(--gray-5);}

/* Filter bar */
.cat-filter-bar{background:var(--white);border-bottom:1px solid var(--gray-6);
    position:sticky;top:62px;z-index:90;padding:12px 24px;}
.cat-filter-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.cat-search-wrap{position:relative;flex:1;min-width:200px;max-width:320px;}
.cat-search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);
    width:16px;height:16px;color:var(--gray-5);}
.cat-search{width:100%;padding:9px 12px 9px 36px;border:1.5px solid var(--gray-6);
    border-radius:8px;font-size:.88rem;font-family:'Outfit',sans-serif;
    background:var(--gray-7);outline:none;transition:all .2s;}
.cat-search:focus{border-color:var(--green);background:var(--white);box-shadow:0 0 0 3px rgba(5,150,105,.1);}
.cat-select{padding:9px 12px;border:1.5px solid var(--gray-6);border-radius:8px;
    font-size:.88rem;font-family:'Outfit',sans-serif;background:var(--gray-7);
    color:var(--gray-1);outline:none;transition:all .2s;cursor:pointer;}
.cat-select:focus{border-color:var(--green);}
.cat-filter-btn{padding:9px 20px;background:var(--green);color:#fff;border:none;
    border-radius:8px;font-size:.88rem;font-weight:700;font-family:'Outfit',sans-serif;
    cursor:pointer;transition:all .2s;}
.cat-filter-btn:hover{background:var(--green-d);}
.cat-clear-btn{padding:9px 16px;background:var(--gray-7);color:var(--gray-3);border:1.5px solid var(--gray-6);
    border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;}
.cat-clear-btn:hover{border-color:var(--green);color:var(--green);}
.cat-results-count{margin-left:auto;font-size:.82rem;color:var(--gray-5);white-space:nowrap;}

/* Genre pills below filter */
.cat-genres{max-width:1280px;margin:0 auto;padding:16px 24px 0;display:flex;gap:8px;flex-wrap:wrap;}
.cat-genre-pill{padding:6px 16px;border:1.5px solid var(--gray-6);border-radius:20px;
    font-size:.82rem;font-weight:600;color:var(--gray-3);text-decoration:none;
    background:var(--white);transition:all .2s;}
.cat-genre-pill:hover{border-color:var(--green);color:var(--green);background:var(--green-bg);}
.cat-genre-pill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* Main grid */
.cat-main{max-width:1280px;margin:0 auto;padding:24px;}
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:18px;}

/* Movie card */
.cat-card{background:var(--white);border:1px solid var(--gray-6);border-radius:13px;
    overflow:hidden;transition:all .3s;animation:fadeUp .35s ease both;}
.cat-card:hover{transform:translateY(-5px);border-color:var(--green);
    box-shadow:0 14px 36px rgba(5,150,105,.14);}
.cat-card-poster{position:relative;overflow:hidden;}
.cat-card-poster img{width:100%;aspect-ratio:2/3;object-fit:contain;background:var(--gray-7);
    display:block;transition:transform .4s;}
.cat-card:hover .cat-card-poster img{transform:scale(1.05);}
.cat-card-genre{position:absolute;top:8px;left:8px;
    background:rgba(5,150,105,.9);color:#fff;font-size:.65rem;font-weight:700;
    padding:3px 9px;border-radius:12px;text-transform:uppercase;letter-spacing:.3px;}
.cat-card-overlay{position:absolute;inset:0;background:linear-gradient(transparent 55%,rgba(0,0,0,.7));
    opacity:0;transition:opacity .3s;}
.cat-card:hover .cat-card-overlay{opacity:1;}
.cat-card-hover-btn{position:absolute;bottom:10px;left:10px;right:10px;
    background:var(--green);color:#fff;text-align:center;padding:8px;
    border-radius:7px;font-size:.82rem;font-weight:700;text-decoration:none;
    transform:translateY(6px);opacity:0;transition:all .3s;}
.cat-card:hover .cat-card-hover-btn{transform:translateY(0);opacity:1;}
.cat-card-body{padding:12px 13px;}
.cat-card-title{font-size:.9rem;font-weight:700;color:var(--gray-1);margin-bottom:2px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cat-card-dir{font-size:.74rem;color:var(--gray-5);margin-bottom:8px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cat-card-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;}
.cat-card-year{font-size:.73rem;font-weight:600;color:var(--gray-5);}
.cat-card-hot{font-size:.72rem;color:var(--green);font-weight:600;}
.cat-book-btn{display:block;width:100%;text-align:center;padding:8px;
    background:linear-gradient(135deg,var(--green),var(--green-d));color:#fff;
    border-radius:7px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;}
.cat-book-btn:hover{background:var(--green-d);}

/* Empty */
.cat-empty{text-align:center;padding:60px 20px;grid-column:1/-1;}
.cat-empty-icon{font-size:3rem;margin-bottom:12px;}
.cat-empty h3{font-size:1.1rem;font-weight:700;color:var(--gray-2);margin-bottom:6px;}
.cat-empty p{color:var(--gray-4);font-size:.88rem;}

/* Footer */
.cat-footer{background:var(--gray-2);color:rgba(255,255,255,.5);text-align:center;
    padding:20px;font-size:.82rem;margin-top:20px;}
.cat-footer strong{color:var(--green);}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
.cat-card:nth-child(1){animation-delay:.03s}.cat-card:nth-child(2){animation-delay:.06s}
.cat-card:nth-child(3){animation-delay:.09s}.cat-card:nth-child(4){animation-delay:.12s}
.cat-card:nth-child(n+5){animation-delay:.15s}

@media(max-width:640px){
    .cat-filter-inner{gap:8px;}
    .cat-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
    .cat-welcome{display:none;}
    .cat-main{padding:16px;}
    .cat-genres{padding:12px 16px 0;}
}
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="cat-nav">
    <div class="cat-nav-inner">
        <a href="../homepage.php" class="cat-logo">🎬 MovieFlix</a>
        <div class="cat-nav-right">
            <span class="cat-welcome">👤 <?= htmlspecialchars($username) ?></span>
            <a href="../homepage.php" class="cat-home-btn">🏠 Home</a>
            <a href="../auth/logout.php" class="cat-logout">Sign Out</a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="cat-page-head">
    <div class="cat-page-head-inner">
        <div class="cat-page-title">🎞️ Movie Catalog</div>
        <div class="cat-page-sub">Browse and book from our full collection</div>
        <div class="cat-user-stats">
            <div class="cat-user-stat">
                <span class="cat-user-stat-icon">🎬</span>
                <div>
                    <div class="cat-user-stat-val"><?= $total ?></div>
                    <div class="cat-user-stat-lbl">Movies shown</div>
                </div>
            </div>
            <div class="cat-user-stat">
                <span class="cat-user-stat-icon">🎟️</span>
                <div>
                    <div class="cat-user-stat-val"><?= $my_bookings ?></div>
                    <div class="cat-user-stat-lbl">My bookings</div>
                </div>
            </div>
            <div class="cat-user-stat">
                <span class="cat-user-stat-icon">🎭</span>
                <div>
                    <div class="cat-user-stat-val"><?= count($genres) ?></div>
                    <div class="cat-user-stat-lbl">Genres</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar (sticky) -->
<div class="cat-filter-bar">
    <form method="GET" class="cat-filter-inner">
        <div class="cat-search-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search movies…" class="cat-search"/>
        </div>
        <select name="genre" class="cat-select">
            <option value="">All Genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $g===$genre_f?'selected':'' ?>><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" class="cat-select">
            <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
            <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
            <option value="title" <?= $sort==='title'?'selected':'' ?>>A–Z Title</option>
            <option value="year" <?= $sort==='year'?'selected':'' ?>>By Year</option>
        </select>
        <button type="submit" class="cat-filter-btn">Search</button>
        <?php if ($search || $genre_f): ?>
            <a href="index.php" class="cat-clear-btn">✕ Clear</a>
        <?php endif; ?>
        <span class="cat-results-count"><?= $total ?> result<?= $total!=1?'s':'' ?></span>
    </form>
</div>

<!-- Genre Pills -->
<div class="cat-genres">
    <a href="index.php<?= $sort?'?sort='.$sort:'' ?>"
       class="cat-genre-pill <?= !$genre_f?'active':'' ?>">All</a>
    <?php foreach ($genres as $g): ?>
        <a href="?genre=<?= urlencode($g) ?><?= $sort?'&sort='.$sort:'' ?>"
           class="cat-genre-pill <?= $g===$genre_f?'active':'' ?>">
            <?= htmlspecialchars($g) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Grid -->
<div class="cat-main">
    <div class="cat-grid">
    <?php if ($result && $result->num_rows > 0):
        while ($movie = $result->fetch_assoc()): ?>
        <div class="cat-card">
            <div class="cat-card-poster">
                <img src="../uploads/<?= htmlspecialchars($movie['image']) ?>"
                     alt="<?= htmlspecialchars($movie['title']) ?>"
                     loading="lazy"
                     onerror="this.style.background='#f3f4f6'"/>
                <span class="cat-card-genre"><?= htmlspecialchars($movie['genre']) ?></span>
                <div class="cat-card-overlay"></div>
                <a href="book_ticket.php?movie_id=<?= $movie['id'] ?>" class="cat-card-hover-btn">🎟️ Book Now</a>
            </div>
            <div class="cat-card-body">
                <div class="cat-card-title" title="<?= htmlspecialchars($movie['title']) ?>">
                    <?= htmlspecialchars($movie['title']) ?>
                </div>
                <div class="cat-card-dir"><?= htmlspecialchars($movie['director']) ?></div>
                <div class="cat-card-row">
                    <span class="cat-card-year">📅 <?= $movie['release_year'] ?></span>
                    <?php if ($movie['bookings'] > 0): ?>
                        <span class="cat-card-hot">🔥 <?= $movie['bookings'] ?></span>
                    <?php endif; ?>
                </div>
                <a href="book_ticket.php?movie_id=<?= $movie['id'] ?>" class="cat-book-btn">
                    Book Ticket
                </a>
            </div>
        </div>
    <?php endwhile; else: ?>
        <div class="cat-empty">
            <div class="cat-empty-icon">🔍</div>
            <h3>No movies found</h3>
            <p><?= ($search||$genre_f) ? 'Try a different search or filter' : 'No movies added yet' ?></p>
            <?php if ($search||$genre_f): ?>
                <a href="index.php" style="color:var(--green);font-weight:600;font-size:.88rem;">View all movies</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<footer class="cat-footer">
    © <?= date('Y') ?> <strong>MovieFlix</strong>. All rights reserved.
</footer>

</body>
</html>
