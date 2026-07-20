<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$msg = ''; $msgType = '';

// Delete booking
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    $conn->query("DELETE FROM bookings WHERE id=$del");
    $msg = "Booking #" . str_pad($del,6,'0',STR_PAD_LEFT) . " deleted.";
    $msgType = 'green';
}

// Filters
$search   = trim($_GET['search'] ?? '');
$status_f = trim($_GET['status'] ?? '');
$movie_f  = trim($_GET['movie'] ?? '');

$sql = "
    SELECT b.*, m.title AS movie_title, m.image, u.username
    FROM bookings b
    JOIN movies m ON b.movie_id = m.id
    JOIN users  u ON b.user_id  = u.id
    WHERE 1
";
if ($search)   $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($search) . "%' OR b.name LIKE '%" . $conn->real_escape_string($search) . "%')";
if ($status_f) $sql .= " AND b.payment_status = '" . $conn->real_escape_string($status_f) . "'";
if ($movie_f)  $sql .= " AND b.movie_id = " . (int)$movie_f;
$sql .= " ORDER BY b.created_at DESC";

$bookings = $conn->query($sql);

// Stats
$total_rev = $conn->query("SELECT COALESCE(SUM(amount),0) AS r FROM bookings WHERE payment_status='completed'")->fetch_assoc()['r'];
$pending   = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE payment_status='pending'")->fetch_assoc()['c'];
$movies_list = $conn->query("SELECT id, title FROM movies ORDER BY title");

$activePage = 'bookings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Bookings | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Bookings</h1>
            <p>View and manage all ticket bookings</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:.82rem;color:var(--gray-4);">Total Revenue: <strong style="color:var(--green);">₹<?= number_format($total_rev) ?></strong></span>
        </div>
    </header>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div class="adm-toast <?= $msgType ?> show"
             style="position:relative;bottom:auto;left:auto;transform:none;margin-bottom:18px;border-radius:10px;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="adm-card">
            <!-- Filter Bar -->
            <div class="adm-card-header" style="flex-wrap:wrap;gap:10px;">
                <div>
                    <div class="adm-card-title">All Bookings
                        <span style="color:var(--gray-5);font-size:.85rem;font-weight:500;">(<?= $bookings->num_rows ?>)</span>
                    </div>
                </div>
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <div class="adm-search-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search user…" class="adm-input adm-search" style="width:180px;padding:8px 12px 8px 34px;"/>
                    </div>
                    <select name="status" class="adm-select" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="completed" <?= $status_f==='completed'?'selected':'' ?>>Completed</option>
                        <option value="pending"   <?= $status_f==='pending'  ?'selected':'' ?>>Pending</option>
                    </select>
                    <select name="movie" class="adm-select" style="width:160px;padding:8px 12px;">
                        <option value="">All Movies</option>
                        <?php $movies_list->data_seek(0); while ($mv = $movies_list->fetch_assoc()): ?>
                            <option value="<?= $mv['id'] ?>" <?= $movie_f==$mv['id']?'selected':'' ?>>
                                <?= htmlspecialchars(substr($mv['title'],0,20)) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="adm-btn adm-btn-green" style="padding:8px 16px;">Filter</button>
                    <?php if ($search||$status_f||$movie_f): ?>
                        <a href="bookings.php" class="adm-btn adm-btn-gray" style="padding:8px 14px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="overflow-x:auto;">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Movie</th>
                        <th>User</th>
                        <th>Name</th>
                        <th>Screen</th>
                        <th>Date / Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($bookings->num_rows > 0):
                    $bookings->data_seek(0);
                    while ($b = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td style="font-family:'Courier New';font-weight:700;color:var(--green);font-size:.85rem;">
                            #<?= str_pad($b['id'],6,'0',STR_PAD_LEFT) ?>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <img src="../uploads/<?= htmlspecialchars($b['image']) ?>"
                                     class="adm-poster-thumb"
                                     onerror="this.style.display='none'"/>
                                <span style="font-weight:600;font-size:.85rem;">
                                    <?= htmlspecialchars(substr($b['movie_title'],0,18)) ?>
                                </span>
                            </div>
                        </td>
                        <td style="color:var(--gray-4);"><?= htmlspecialchars($b['username']) ?></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($b['name']) ?></td>
                        <td><span class="adm-badge adm-badge-gray"><?= htmlspecialchars($b['screen']) ?></span></td>
                        <td>
                            <div style="font-weight:600;"><?= date('d M Y', strtotime($b['show_date'])) ?></div>
                            <div style="font-size:.75rem;color:var(--gray-5);"><?= htmlspecialchars($b['show_time']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700;"><?= $b['seats'] ?></div>
                            <div style="font-size:.72rem;color:var(--gray-5);" title="<?= htmlspecialchars($b['seat_numbers']) ?>">
                                <?= htmlspecialchars(substr($b['seat_numbers'],0,14)) ?>…
                            </div>
                        </td>
                        <td style="font-weight:700;color:var(--green);">₹<?= number_format($b['amount']) ?></td>
                        <td>
                            <?php
                            $sc = match($b['payment_status']) {
                                'completed' => 'adm-badge-green',
                                'pending'   => 'adm-badge-orange',
                                default     => 'adm-badge-gray'
                            };
                            ?>
                            <span class="adm-badge <?= $sc ?>"><?= ucfirst($b['payment_status']) ?></span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="../user/download_ticket.php?booking_id=<?= $b['id'] ?>"
                                   target="_blank"
                                   class="adm-btn adm-btn-gray adm-btn-sm adm-btn-icon" title="View Ticket">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="bookings.php?delete=<?= $b['id'] ?>"
                                   onclick="return confirm('Delete booking #<?= str_pad($b['id'],6,'0',STR_PAD_LEFT) ?>?')"
                                   class="adm-btn adm-btn-red adm-btn-sm adm-btn-icon" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="10">
                        <div class="adm-empty">
                            <div class="adm-empty-icon">🎟️</div>
                            <h3>No bookings found</h3>
                            <p style="margin-top:6px;font-size:.85rem;">Try different filters</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>
