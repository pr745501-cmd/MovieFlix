<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$msg = ''; $msgType = '';

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $uname = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role'] === 'admin' ? 'admin' : 'user';

    if ($uname && $email && $pass) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $uname, $email, $hashed, $role);
        if ($stmt->execute()) {
            $msg = "User '$uname' added successfully."; $msgType = 'green';
        } else {
            $msg = "Username or email already exists."; $msgType = 'red';
        }
    } else {
        $msg = "Please fill in all fields."; $msgType = 'red';
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== (int)$_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$del_id");
        $msg = "User deleted."; $msgType = 'green';
    } else {
        $msg = "You cannot delete your own account."; $msgType = 'red';
    }
}

// Change role
if (isset($_GET['toggle_role'])) {
    $tid = (int)$_GET['toggle_role'];
    $cur = $conn->query("SELECT role FROM users WHERE id=$tid")->fetch_assoc()['role'];
    $new = $cur === 'admin' ? 'user' : 'admin';
    $conn->query("UPDATE users SET role='$new' WHERE id=$tid");
    $msg = "Role updated to $new."; $msgType = 'green';
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$activePage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Users | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Users</h1>
            <p>Manage registered accounts</p>
        </div>
        <button onclick="document.getElementById('addUserPanel').classList.toggle('hidden')"
                class="adm-btn adm-btn-green">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add User
        </button>
    </header>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div class="adm-toast <?= $msgType ?> show"
             style="position:relative;bottom:auto;left:auto;transform:none;margin-bottom:18px;border-radius:10px;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <!-- Add User Panel -->
        <div id="addUserPanel" class="adm-card hidden" style="margin-bottom:22px;">
            <div class="adm-card-header">
                <div class="adm-card-title">Add New User</div>
                <button onclick="document.getElementById('addUserPanel').classList.add('hidden')"
                        class="adm-btn adm-btn-gray adm-btn-sm">✕ Close</button>
            </div>
            <div style="padding:22px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add"/>
                    <div class="adm-form-grid adm-form-grid-2" style="margin-bottom:16px;">
                        <div>
                            <label class="adm-label">Username *</label>
                            <input type="text" name="username" required class="adm-input" placeholder="e.g. john_doe"/>
                        </div>
                        <div>
                            <label class="adm-label">Email *</label>
                            <input type="email" name="email" required class="adm-input" placeholder="john@email.com"/>
                        </div>
                        <div>
                            <label class="adm-label">Password *</label>
                            <input type="password" name="password" required class="adm-input" placeholder="Min 6 characters"/>
                        </div>
                        <div>
                            <label class="adm-label">Role *</label>
                            <select name="role" class="adm-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-green">Create User</button>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div>
                    <div class="adm-card-title">All Users</div>
                    <div class="adm-card-sub"><?= $users->num_rows ?> registered accounts</div>
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Bookings</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $users->data_seek(0);
                while ($u = $users->fetch_assoc()):
                    $bcount = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE user_id={$u['id']} AND payment_status='completed'")->fetch_assoc()['c'];
                ?>
                <tr>
                    <td style="color:var(--gray-5);font-weight:600;font-size:.8rem;"><?= str_pad($u['id'],3,'0',STR_PAD_LEFT) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--green),var(--green-d));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0;">
                                <?= strtoupper(substr($u['username'],0,1)) ?>
                            </div>
                            <span style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></span>
                        </div>
                    </td>
                    <td style="color:var(--gray-4);"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td>
                        <span class="adm-badge <?= $u['role']==='admin' ? 'adm-badge-orange' : 'adm-badge-blue' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td style="font-weight:600;"><?= $bcount ?></td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="users.php?toggle_role=<?= $u['id'] ?>"
                               class="adm-btn adm-btn-gray adm-btn-sm"
                               title="Toggle role"
                               onclick="return confirm('Change role for <?= addslashes($u['username']) ?>?')">
                               ⇄ Role
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?= $u['id'] ?>"
                               class="adm-btn adm-btn-red adm-btn-sm adm-btn-icon"
                               onclick="return confirm('Delete user <?= addslashes($u['username']) ?>?')"
                               title="Delete">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<style>
.hidden { display: none !important; }
</style>

</body>
</html>
