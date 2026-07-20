<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$msg = ''; $msgType = '';

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'change_password') {
        $cur   = $_POST['current_password'];
        $new   = $_POST['new_password'];
        $conf  = $_POST['confirm_password'];

        $uid   = (int)$_SESSION['user_id'];
        $row   = $conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();

        if (!password_verify($cur, $row['password'])) {
            $msg = "Current password is incorrect."; $msgType = 'red';
        } elseif (strlen($new) < 6) {
            $msg = "New password must be at least 6 characters."; $msgType = 'red';
        } elseif ($new !== $conf) {
            $msg = "New passwords do not match."; $msgType = 'red';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $uid);
            $stmt->execute();
            $msg = "Password changed successfully!"; $msgType = 'green';
        }
    }

    if ($_POST['action'] === 'change_username') {
        $new_uname = trim($_POST['new_username']);
        $uid = (int)$_SESSION['user_id'];
        if ($new_uname) {
            $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
            $stmt->bind_param("si", $new_uname, $uid);
            if ($stmt->execute()) {
                $_SESSION['username'] = $new_uname;
                $msg = "Username updated to '$new_uname'."; $msgType = 'green';
            } else {
                $msg = "Username already taken."; $msgType = 'red';
            }
        }
    }
}

// DB Stats
$db_movies   = $conn->query("SELECT COUNT(*) AS c FROM movies")->fetch_assoc()['c'];
$db_users    = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$db_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$db_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) AS r FROM bookings WHERE payment_status='completed'")->fetch_assoc()['r'];

$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Settings | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Settings</h1>
            <p>Manage your account and system preferences</p>
        </div>
    </header>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div class="adm-toast <?= $msgType ?> show"
             style="position:relative;bottom:auto;left:auto;transform:none;margin-bottom:18px;border-radius:10px;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="adm-grid-2">

            <!-- Change Password -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div>
                        <div class="adm-card-title">🔐 Change Password</div>
                        <div class="adm-card-sub">Update your admin password</div>
                    </div>
                </div>
                <div style="padding:22px;">
                    <form method="POST" class="adm-form-grid" style="gap:14px;">
                        <input type="hidden" name="action" value="change_password"/>
                        <div>
                            <label class="adm-label">Current Password</label>
                            <input type="password" name="current_password" required class="adm-input" placeholder="Enter current password"/>
                        </div>
                        <div>
                            <label class="adm-label">New Password</label>
                            <input type="password" name="new_password" required class="adm-input" placeholder="Min 6 characters"/>
                        </div>
                        <div>
                            <label class="adm-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="adm-input" placeholder="Repeat new password"/>
                        </div>
                        <button type="submit" class="adm-btn adm-btn-green" style="width:fit-content;">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Username -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div>
                        <div class="adm-card-title">✏️ Change Username</div>
                        <div class="adm-card-sub">Current: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
                    </div>
                </div>
                <div style="padding:22px;">
                    <form method="POST" class="adm-form-grid" style="gap:14px;">
                        <input type="hidden" name="action" value="change_username"/>
                        <div>
                            <label class="adm-label">New Username</label>
                            <input type="text" name="new_username" required class="adm-input"
                                   placeholder="Enter new username"
                                   value="<?= htmlspecialchars($_SESSION['username']) ?>"/>
                        </div>
                        <button type="submit" class="adm-btn adm-btn-green" style="width:fit-content;">
                            Update Username
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Info -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">📊 System Overview</div>
                </div>
                <div style="padding:22px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <?php
                        $stats = [
                            ['🎬','Movies in Catalog', $db_movies, 'adm-badge-green'],
                            ['👥','Total Users',       $db_users,   'adm-badge-blue'],
                            ['🎟️','Total Bookings',   $db_bookings,'adm-badge-purple'],
                            ['💰','Total Revenue',    '₹'.number_format($db_revenue), 'adm-badge-orange'],
                        ];
                        foreach ($stats as $s): ?>
                        <div style="background:var(--gray-8);border:1px solid var(--gray-6);border-radius:10px;padding:14px 16px;">
                            <div style="font-size:1.3rem;margin-bottom:4px;"><?= $s[0] ?></div>
                            <div style="font-size:.75rem;color:var(--gray-5);font-weight:600;text-transform:uppercase;letter-spacing:.3px;"><?= $s[1] ?></div>
                            <div style="font-size:1.4rem;font-weight:800;color:var(--gray-1);margin-top:2px;"><?= $s[2] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">🔒 Account Info</div>
                </div>
                <div style="padding:22px;">
                    <?php
                    $me = $conn->query("SELECT * FROM users WHERE id=" . (int)$_SESSION['user_id'])->fetch_assoc();
                    $rows = [
                        ['Username',  $me['username']],
                        ['Email',     $me['email'] ?? '—'],
                        ['Role',      strtoupper($me['role'])],
                        ['User ID',   '#' . str_pad($me['id'],4,'0',STR_PAD_LEFT)],
                        ['PHP Version', phpversion()],
                        ['Server',    $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'],
                    ];
                    ?>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($rows as $r): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--gray-8);border-radius:8px;border:1px solid var(--gray-6);">
                            <span style="font-size:.82rem;font-weight:700;color:var(--gray-5);text-transform:uppercase;letter-spacing:.3px;"><?= $r[0] ?></span>
                            <span style="font-weight:600;color:var(--gray-1);font-size:.9rem;"><?= htmlspecialchars($r[1]) ?></span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div><!-- /grid -->

    </div>
</div>

</body>
</html>
