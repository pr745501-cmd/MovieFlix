<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit();
}
include '../config.php';

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($conn->real_escape_string($_POST['title']));
    $director = trim($conn->real_escape_string($_POST['director']));
    $year     = (int)$_POST['release_year'];
    $genre    = $conn->real_escape_string($_POST['genre']);

    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allow = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allow)) {
            $msg = "Only JPG, PNG, GIF, WEBP images allowed."; $msgType = 'red';
        } else {
            $img_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $img_name)) {
                $conn->query("INSERT INTO movies (title,director,release_year,genre,image) VALUES ('$title','$director',$year,'$genre','$img_name')");
                $msg = "Movie '$title' added successfully!"; $msgType = 'green';
            } else {
                $msg = "Failed to upload image."; $msgType = 'red';
            }
        }
    } else {
        $msg = "Please select a valid image file."; $msgType = 'red';
    }
}

$activePage = 'movies';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Add Movie | MovieFlix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="adm-main">
    <header class="adm-header">
        <div class="adm-header-left">
            <h1>Add Movie</h1>
            <p>Add a new movie to the catalog</p>
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
                </div>
                <div style="padding:28px;">
                    <form method="POST" enctype="multipart/form-data" class="adm-form-grid" style="gap:18px;">

                        <div>
                            <label class="adm-label">Movie Title *</label>
                            <input type="text" name="title" required class="adm-input" placeholder="e.g. Avengers: Endgame"/>
                        </div>
                        <div>
                            <label class="adm-label">Director *</label>
                            <input type="text" name="director" required class="adm-input" placeholder="e.g. Anthony Russo"/>
                        </div>

                        <div class="adm-form-grid adm-form-grid-2">
                            <div>
                                <label class="adm-label">Release Year *</label>
                                <input type="number" name="release_year" required
                                       min="1900" max="<?= date('Y')+2 ?>"
                                       value="<?= date('Y') ?>"
                                       class="adm-input"/>
                            </div>
                            <div>
                                <label class="adm-label">Genre *</label>
                                <select name="genre" required class="adm-select">
                                    <option value="">— Select —</option>
                                    <?php foreach (['Action','Drama','Romance','Comedy','Sci-Fi','Adventure','Thriller','Horror','Love'] as $g): ?>
                                        <option value="<?= $g ?>"><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label class="adm-label">Movie Poster *</label>
                            <div class="adm-file-zone" id="dropZone">
                                <input type="file" name="image" accept="image/*" required id="fileInput"
                                       style="display:none;" onchange="previewImage(this)"/>
                                <label for="fileInput" style="cursor:pointer;">
                                    <div id="uploadPrompt">
                                        <div style="font-size:2.2rem;margin-bottom:8px;">🖼️</div>
                                        <p style="font-weight:600;color:var(--gray-3);">Click to upload poster</p>
                                        <p style="font-size:.8rem;color:var(--gray-5);margin-top:4px;">JPG, PNG, WEBP — max 10MB</p>
                                    </div>
                                    <img id="previewImg" src="" alt="" style="display:none;max-height:200px;border-radius:8px;margin:0 auto;"/>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="adm-btn adm-btn-green" style="padding:12px;justify-content:center;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Add Movie to Catalog
                        </button>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('uploadPrompt').style.display = 'none';
            const img = document.getElementById('previewImg');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
