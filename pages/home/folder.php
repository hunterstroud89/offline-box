<?php
$folderName = $_GET['folder'] ?? '';
if (empty($folderName)) {
    header('Location: home.php');
    exit;
}

// Load apps data
$appsFile = '../../data/json/apps.json';
$apps = [];
if (file_exists($appsFile)) {
    $apps = json_decode(file_get_contents($appsFile), true) ?? [];
}

// Filter apps for this folder
$folderApps = array_filter($apps, function($app) use ($folderName) {
    return ($app['folder'] ?? '') === $folderName && ($app['visible'] ?? true);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($folderName) ?> - OfflineBox</title>
    <link rel="stylesheet" href="home.css?v=<?= time() ?>">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            text-decoration: none;
            padding: 0;
            background: transparent;
            border: none;
            box-shadow: none;
            transition: all 0.12s ease;
            cursor: pointer;
            margin-right: 10px;
            transform: translateY(-4px);
        }
        .back-link:hover {
            color: var(--accent);
        }
        .back-link svg {
            display: block;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: flex-start;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            color: var(--fg);
        }
        .grid {
            justify-content: flex-start;
            justify-items: start;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="section">
            <div class="section-header">
                <a class="back-link" href="home.php" title="Back to Home" aria-label="Back to Home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h2 class="section-title"><?= htmlspecialchars($folderName) ?></h2>
            </div>
            
            <div class="grid" id="folderApps">
                <?php foreach ($folderApps as $app): ?>
                    <?php
                    $url = $app['url'] ?? '';
                    $icon = $app['icon'] ?? '';
                    $label = $app['label'] ?? '';
                    
                    // Adjust paths for relative URLs
                    if (strpos($url, '/pages/') === 0) {
                        $url = '..' . substr($url, 6);
                    }
                    if (strpos($icon, '/data/') === 0) {
                        $icon = '../..' . $icon;
                    }
                    ?>
                    <a class="card" href="<?= htmlspecialchars($url) ?>">
                        <img class="icon" src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($label) ?>">
                        <div class="label"><?= htmlspecialchars($label) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($folderApps)): ?>
                <div style="text-align: center; color: var(--muted); margin-top: 40px;">
                    <p>This folder is empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
