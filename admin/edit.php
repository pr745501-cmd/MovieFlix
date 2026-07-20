<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

if (!isset($_GET['id'])) { header('Location: index.php'); exit(); }
$id = (int)$_GET['id'];

$movie = $conn->query("SELECT * FROM movies WHERE id=$id")->fetch_assoc();
if (!$movie) { header('Location: index.php'); exit(); }

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($conn->real_escape_string($_POST['title']));
    $director = trim($conn->real_escape_string($_POST['director']));
    $year     = (int)$_POST['year'];
    $genre    = $conn->real_escape_string($_POST['genre']);

    // Handle optional new image upload
    $img_name = $movie['image']; // keep existing by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allow = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allow)) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_img = time() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_img)) {
                $img_name = $new_img;
            }
        }
    }

    $conn->query("UPDATE movies SET title='$title', director='$director', release_year=$year, genre='$genre', image='$img_name' WHERE id=$id");
    $msg = "Movie updated successfully!"; $msgType = 'green';
    // Reload movie data
    $movie = $conn->query("SELECT * FROM movies WHERE id=$id")->fetch_assoc();
}

$activePage = 'movies';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Edit Movie | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Edit Movie</h1>
            <p>Update details for: <strong><?= htmlspecialchars($movie['title']) ?></strong></p>
        </div>
        <a href="index.php" class="adm-btn adm-btn-gray">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Movies
        </a>
    </header>

    <div class="adm-content">
        <div style="max-width:680px;margin:0 auto;">

            <?php if ($msg): ?>
            <div class="adm-toast <?= $msgType ?> show"
                 style="position:relative;bottom:auto;left:auto;transform:none;margin-bottom:18px;border-radius:10px;">
                <?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">Movie Details</div>
                    <span class="adm-badge adm-badge-gray">ID: <?= str_pad($movie['id'],3,'0',STR_PAD_LEFT) ?></span>
                </div>
                <div style="padding:28px;">
                    <form method="POST" enctype="multipart/form-data" class="adm-form-grid" style="gap:18px;">

                        <div>
                            <label class="adm-label">Movie Title *</label>
                            <input type="text" name="title" required class="adm-input"
                                   value="<?= htmlspecialchars($movie['title']) ?>"/>
                        </div>
                        <div>
                            <label class="adm-label">Director *</label>
                            <input type="text" name="director" required class="adm-input"
                                   value="<?= htmlspecialchars($movie['director']) ?>"/>
                        </div>

                        <div class="adm-form-grid adm-form-grid-2">
                            <div>
                                <label class="adm-label">Release Year *</label>
                                <input type="number" name="year" required
                                       min="1900" max="<?= date('Y')+2 ?>"
                                       value="<?= $movie['release_year'] ?>"
                                       class="adm-input"/>
                            </div>
                            <div>
                                <label class="adm-label">Genre *</label>
                                <select name="genre" required class="adm-select">
                                    <?php foreach (['Action','Drama','Romance','Comedy','Sci-Fi','Adventure','Thriller','Horror','Love'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $movie['genre']===$g?'selected':'' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Current Poster + New Upload -->
                        <div>
                            <label class="adm-label">Movie Poster</label>
                            <div style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap;">
                                <!-- Current -->
                                <div style="text-align:center;">
                                    <div style="font-size:.72rem;color:var(--gray-5);margin-bottom:6px;font-weight:600;">CURRENT</div>
                                    <img src="../uploads/<?= htmlspecialchars($movie['image']) ?>"
                                         id="currentPoster"
                                         style="width:80px;height:112px;object-fit:cover;border-radius:8px;border:2px solid var(--gray-6);"
                                         onerror="this.style.background='#f3f4f6'"/>
                                </div>
                                <!-- Upload new -->
                                <div style="flex:1;min-width:200px;">
                                    <div class="adm-file-zone" style="padding:16px;">
                                        <input type="file" name="image" accept="image/*" id="fileInput"
                                               style="display:none;" onchange="previewNew(this)"/>
                                        <label for="fileInput" style="cursor:pointer;display:block;text-align:center;">
                                            <div id="uploadPrompt">
                                                <div style="font-size:1.6rem;">📁</div>
                                                <p style="font-size:.85rem;font-weight:600;color:var(--gray-3);">Click to replace poster</p>
                                                <p style="font-size:.75rem;color:var(--gray-5);">Leave blank to keep current</p>
                                            </div>
                                            <img id="newPreview" src="" alt=""
                                                 style="display:none;max-height:120px;border-radius:8px;margin:0 auto;"/>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" class="adm-btn adm-btn-green" style="flex:1;justify-content:center;padding:12px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:17px;height:17px;"><path stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                            <a href="index.php" class="adm-btn adm-btn-gray" style="padding:12px 20px;">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function previewNew(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('uploadPrompt').style.display = 'none';
            const img = document.getElementById('newPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            // Also update current poster preview
            document.getElementById('currentPoster').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
