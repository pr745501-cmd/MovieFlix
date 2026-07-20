<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

// Stats
$total_movies   = $conn->query("SELECT COUNT(*) AS c FROM movies")->fetch_assoc()['c'];
$total_users    = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE payment_status='completed'")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) AS r FROM bookings WHERE payment_status='completed'")->fetch_assoc()['r'];

// Monthly revenue (last 6 months)
$monthly = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month, SUM(amount) AS total
    FROM bookings WHERE payment_status='completed'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(created_at), DATE_FORMAT(created_at,'%b')
    ORDER BY MIN(created_at)
");
$months = []; $revenues = [];
while ($r = $monthly->fetch_assoc()) { $months[] = $r['month']; $revenues[] = $r['total']; }

// Recent bookings
$recent = $conn->query("
    SELECT b.id, b.name, b.seats, b.amount, b.show_date, b.show_time,
           b.screen, b.seat_numbers, b.payment_status, b.created_at,
           m.title AS movie_title, u.username
    FROM bookings b
    JOIN movies m ON b.movie_id = m.id
    JOIN users  u ON b.user_id  = u.id
    ORDER BY b.created_at DESC LIMIT 8
");

// Top movies by bookings
$top_movies = $conn->query("
    SELECT m.title, m.genre, COUNT(b.id) AS booking_count, SUM(b.amount) AS revenue
    FROM movies m
    LEFT JOIN bookings b ON m.id = b.movie_id AND b.payment_status='completed'
    GROUP BY m.id, m.title, m.genre
    ORDER BY booking_count DESC LIMIT 5
");

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Dashboard | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <!-- Header -->
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Here's what's happening.</p>
        </div>
        <div class="adm-header-right">
            <span style="font-size:.82rem;color:var(--gray-4);"><?= date('D, d M Y') ?></span>
            <a href="create.php" class="adm-btn adm-btn-green">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Movie
            </a>
        </div>
    </header>

    <div class="adm-content">

        <!-- Stat Cards -->
        <div class="adm-stats-grid">
            <div class="adm-stat-card green">
                <div class="adm-stat-icon green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4"/></svg>
                </div>
                <div class="adm-stat-label">Total Movies</div>
                <div class="adm-stat-value"><?= $total_movies ?></div>
                <div class="adm-stat-sub">In catalog</div>
            </div>
            <div class="adm-stat-card blue">
                <div class="adm-stat-icon blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/></svg>
                </div>
                <div class="adm-stat-label">Registered Users</div>
                <div class="adm-stat-value"><?= $total_users ?></div>
                <div class="adm-stat-sub">Active accounts</div>
            </div>
            <div class="adm-stat-card purple">
                <div class="adm-stat-icon purple">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <div class="adm-stat-label">Total Bookings</div>
                <div class="adm-stat-value"><?= $total_bookings ?></div>
                <div class="adm-stat-sub">Confirmed tickets</div>
            </div>
            <div class="adm-stat-card orange">
                <div class="adm-stat-icon orange">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="adm-stat-label">Total Revenue</div>
                <div class="adm-stat-value">₹<?= number_format($total_revenue) ?></div>
                <div class="adm-stat-sub">All time earnings</div>
            </div>
        </div>

        <!-- Recent Bookings + Top Movies -->
        <div class="adm-grid-2" style="margin-bottom:24px;">

            <!-- Recent Bookings -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div>
                        <div class="adm-card-title">Recent Bookings</div>
                        <div class="adm-card-sub">Latest 8 confirmed bookings</div>
                    </div>
                    <a href="bookings.php" class="adm-btn adm-btn-gray adm-btn-sm">View All</a>
                </div>
                <table class="adm-table">
                    <thead><tr>
                        <th>User</th><th>Movie</th><th>Seats</th><th>Amount</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php if ($recent && $recent->num_rows > 0):
                        while ($b = $recent->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($b['username']) ?></div>
                                <div style="font-size:.75rem;color:var(--gray-5);"><?= date('d M', strtotime($b['created_at'])) ?></div>
                            </td>
                            <td style="max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($b['movie_title']) ?>">
                                <?= htmlspecialchars(substr($b['movie_title'],0,16)) ?>…
                            </td>
                            <td><?= $b['seats'] ?></td>
                            <td style="font-weight:700;color:var(--green);">₹<?= $b['amount'] ?></td>
                            <td><span class="adm-badge adm-badge-green">Paid</span></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="adm-empty"><div class="adm-empty-icon">🎟️</div><h3>No bookings yet</h3></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Movies -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div>
                        <div class="adm-card-title">Top Movies</div>
                        <div class="adm-card-sub">By number of bookings</div>
                    </div>
                    <a href="index.php" class="adm-btn adm-btn-gray adm-btn-sm">All Movies</a>
                </div>
                <table class="adm-table">
                    <thead><tr><th>#</th><th>Title</th><th>Genre</th><th>Bookings</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php if ($top_movies && $top_movies->num_rows > 0):
                        $rank = 1;
                        while ($m = $top_movies->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:800;color:var(--gray-5);"><?= $rank++ ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($m['title']) ?></td>
                            <td><span class="adm-badge adm-badge-gray"><?= htmlspecialchars($m['genre']) ?></span></td>
                            <td><?= $m['booking_count'] ?></td>
                            <td style="color:var(--green);font-weight:700;">₹<?= number_format($m['revenue'] ?? 0) ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="adm-empty"><div class="adm-empty-icon">🎬</div><h3>No data yet</h3></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

</body>
</html>
