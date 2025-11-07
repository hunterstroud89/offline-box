<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Create comics directory if it doesn't exist
$comics_dir = __DIR__ . '/../../data/comics';
if (!is_dir($comics_dir)) {
    mkdir($comics_dir, 0755, true);
}

// Get all comic files
function getComicFiles($dir) {
    $comics = [];
    $allowed_extensions = ['cbr', 'cbz', 'zip', 'rar', 'pdf'];
    
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $full_path = $dir . '/' . $file;
                if (is_dir($full_path)) {
                    $comics = array_merge($comics, getComicFiles($full_path));
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_extensions)) {
                        $comics[] = [
                            'name' => pathinfo($file, PATHINFO_FILENAME),
                            'path' => $full_path,
                            'relative_path' => str_replace($dir . '/', '', $full_path),
                            'extension' => $ext,
                            'size' => filesize($full_path),
                            'modified' => filemtime($full_path)
                        ];
                    }
                }
            }
        }
    }
    
    // Sort by name
    usort($comics, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $comics;
}

$comics = getComicFiles($comics_dir);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comic Book Reader</title>
    <link rel="stylesheet" href="comics.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Comic Book Reader</h1>
            <a href="../home/home.php" class="back-btn">← Back to Home</a>
        </header>

        <div class="upload-section">
            <h2>Upload Comics</h2>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <input type="file" name="comic_file" accept=".cbr,.cbz,.zip,.rar,.pdf" multiple>
                <button type="submit">Upload Comics</button>
            </form>
            <p class="help-text">Supported formats: CBR, CBZ, ZIP, RAR, PDF</p>
        </div>

        <div class="comics-grid">
            <?php if (empty($comics)): ?>
                <div class="no-comics">
                    <p>No comics found. Upload some comic files to get started!</p>
                    <p>Place your comic files (.cbr, .cbz, .zip, .rar, .pdf) in the comics directory.</p>
                </div>
            <?php else: ?>
                <?php foreach ($comics as $comic): ?>
                    <div class="comic-card">
                        <div class="comic-thumbnail">
                            <div class="comic-icon">📖</div>
                        </div>
                        <div class="comic-info">
                            <h3><?php echo htmlspecialchars($comic['name']); ?></h3>
                            <p class="comic-meta">
                                <?php echo strtoupper($comic['extension']); ?> • 
                                <?php echo number_format($comic['size'] / 1024 / 1024, 1); ?> MB
                            </p>
                            <div class="comic-actions">
                                <a href="reader.php?comic=<?php echo urlencode($comic['relative_path']); ?>" class="read-btn">Read</a>
                                <a href="download.php?comic=<?php echo urlencode($comic['relative_path']); ?>" class="download-btn">Download</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
