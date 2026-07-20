<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$msg = ''; $msgType = '';
if (isset($_GET['deleted']))       { $msg = 'Movie deleted successfully.'; $msgType = 'green'; }
if (isset($_GET['error']))         { $msg = 'Failed to delete movie.';     $msgType = 'red';   }

$search = trim($_GET['search'] ?? '');
$genre_f = trim($_GET['genre'] ?? '');

$sql = "SELECT * FROM movies WHERE 1";
if ($search) $sql .= " AND title LIKE '%" . $conn->real_escape_string($search) . "%'";
if ($genre_f) $sql .= " AND genre = '" . $conn->real_escape_string($genre_f) . "'";
$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);

$genres_res = $conn->query("SELECT DISTINCT genre FROM movies ORDER BY genre");
$genres = [];
while ($g = $genres_res->fetch_assoc()) $genres[] = $g['genre'];

$activePage = 'movies';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Movies | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Movies</h1>
            <p>Manage your movie catalog</p>
        </div>
        <a href="create.php" class="adm-btn adm-btn-green">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add Movie
        </a>
    </header>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div id="flashMsg" class="adm-toast <?= $msgType ?> show" style="position:relative;bottom:auto;left:auto;transform:none;margin-bottom:18px;border-radius:10px;text-align:left;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="adm-card">
            <!-- Search / Filter -->
            <div class="adm-card-header" style="flex-wrap:wrap;gap:10px;">
                <div>
                    <div class="adm-card-title">All Movies <span style="color:var(--gray-5);font-weight:500;font-size:.85rem;">(<?= $result ? $result->num_rows : 0 ?>)</span></div>
                </div>
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <div class="adm-search-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search title…" class="adm-input adm-search" style="width:200px;padding:8px 12px 8px 34px;"/>
                    </div>
                    <select name="genre" class="adm-select" style="width:150px;padding:8px 12px;">
                        <option value="">All Genres</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $g===$genre_f?'selected':'' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="adm-btn adm-btn-green" style="padding:8px 16px;">Filter</button>
                    <?php if ($search || $genre_f): ?>
                        <a href="index.php" class="adm-btn adm-btn-gray" style="padding:8px 14px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>ID</th>
                        <th>Title / Director</th>
                        <th>Genre</th>
                        <th>Year</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0):
                    while ($movie = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="../uploads/<?= htmlspecialchars($movie['image']) ?>"
                                 alt="" class="adm-poster-thumb"
                                 onerror="this.style.background='#f3f4f6';this.src=''"/>
                        </td>
                        <td style="color:var(--gray-5);font-weight:600;font-size:.8rem;">
                            <?= str_pad($movie['id'], 3, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td>
                            <div style="font-weight:700;color:var(--gray-1);"><?= htmlspecialchars($movie['title']) ?></div>
                            <div style="font-size:.78rem;color:var(--gray-5);margin-top:2px;">Dir. <?= htmlspecialchars($movie['director']) ?></div>
                        </td>
                        <td><span class="adm-badge adm-badge-gray"><?= htmlspecialchars($movie['genre']) ?></span></td>
                        <td style="font-weight:600;"><?= $movie['release_year'] ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="edit.php?id=<?= $movie['id'] ?>" class="adm-btn adm-btn-gray adm-btn-sm adm-btn-icon" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <a href="delete.php?id=<?= $movie['id'] ?>"
                                   onclick="return confirm('Delete \'<?= addslashes($movie['title']) ?>\'?')"
                                   class="adm-btn adm-btn-red adm-btn-sm adm-btn-icon" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6">
                        <div class="adm-empty">
                            <div class="adm-empty-icon">🎬</div>
                            <h3>No movies found</h3>
                            <p style="margin-top:6px;font-size:.85rem;">
                                <?= ($search||$genre_f) ? 'Try different filters' : 'Add your first movie above' ?>
                            </p>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>
