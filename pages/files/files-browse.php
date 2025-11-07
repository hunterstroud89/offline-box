<?php
declare(strict_types=1);

require_once __DIR__ . '/../../data/helpers/auto-config.php';
require_once __DIR__ . '/update-recents.php';

// Load apps to find home app icon
$apps = load_apps_json(__DIR__ . '/../../data/json/apps.json');

// Find home app for home button icon
$home_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && $app['id'] === 'home') {
    $home_app = $app;
    break;
  }
}

/**
 * File Explorer - Real file system browsing
 */

$DEFAULT_START = '/';

// If a specific directory is requested via URL parameter, use that
// Otherwise default to root filesystem

/* ------------------------------- helpers -------------------------------- */

function safe_real(string $p): string|false {
    $r = @realpath($p);
    if ($r === false) return false;
    return $r === '/' ? '/' : rtrim($r, '/');
}

function is_abs(string $p): bool { 
    return isset($p[0]) && $p[0] === '/'; 
}

function formatDateBrowser(int $timestamp): string {
    return date('Y-m-d', $timestamp);
}

function join_child(string $parent, string $name): string {
    return $parent === '/' ? '/' . $name : $parent . '/' . $name;
}

function breadcrumb_links(string $absPath): array {
    $out = [['/', '/']];
    if ($absPath === '/') return $out;
    $parts = array_values(array_filter(explode('/', trim($absPath, '/')), 'strlen'));
    $acc = '';
    foreach ($parts as $seg) { 
        $acc .= '/' . $seg; 
        $out[] = [$seg, $acc]; 
    }
    return $out;
}

/* ------------------------------- routing -------------------------------- */

$reqPath = $_GET['path'] ?? '';
$start   = $DEFAULT_START;

if ($reqPath === '' || $reqPath === null) {
    $abs = safe_real($start) ?: '/';
} else {
    $candidate = is_abs($reqPath) ? $reqPath : ('/' . ltrim((string)$reqPath, '/'));
    $abs = safe_real($candidate) ?: '/';
}

$isDir  = is_dir($abs);
$isFile = is_file($abs);

/* --------------------------------- actions ------------------------------ */

// Raw stream (images/videos/any file) with Range
if (isset($_GET['raw']) && $isFile) {
    // Update recents when file is accessed
    updateRecents($abs, basename($abs));
    
    $size = filesize($abs);
    $mime = @mime_content_type($abs) ?: 'application/octet-stream';
    $startB = 0; $endB = $size - 1;

    header('Accept-Ranges: bytes');
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        $startB = (int)$m[1];
        if ($m[2] !== '') $endB = (int)$m[2];
        if ($startB > $endB || $startB < 0 || $endB >= $size) { 
            header('HTTP/1.1 416 Requested Range Not Satisfiable'); 
            exit; 
        }
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $startB-$endB/$size");
    } else {
        header('HTTP/1.1 200 OK');
    }

    $length = $endB - $startB + 1;
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $length);

    $fp = fopen($abs, 'rb');
    if ($fp !== false) {
        fseek($fp, $startB);
        $buf = 8192;
        while (!feof($fp) && ($pos = ftell($fp)) <= $endB) {
            $read = $buf;
            if ($pos + $read - 1 > $endB) $read = $endB - $pos + 1;
            echo fread($fp, $read);
            @ob_flush(); @flush();
        }
        fclose($fp);
    }
    exit;
}

// Inline viewer (images and videos)
if (isset($_GET['view']) && $isFile) {
    // Update recents when file is viewed
    updateRecents($abs, basename($abs));
    
    $mime = @mime_content_type($abs) ?: '';
    $title = htmlspecialchars(basename($abs), ENT_QUOTES);
    $rawUrl = '?path=' . urlencode($abs) . '&raw=1';
    
    // Use custom back URL if provided, otherwise default to parent directory
    if (isset($_GET['back']) && !empty($_GET['back'])) {
        $back = $_GET['back'];
    } else {
        $back = '?path=' . urlencode(dirname($abs) ?: '/');
    }

    echo "<!doctype html><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>$title</title>
    <style>
      body{margin:0;background:#000;color:#fff;font-family:system-ui;min-height:100vh;display:flex;flex-direction:column}
      header{position:sticky;top:0;display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:rgba(0,0,0,.6);backdrop-filter:blur(6px)}
      a.btn{padding:6px 10px;border-radius:10px;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.15)}
      main{flex:1;display:flex;align-items:center;justify-content:center;padding:8px}
      img,video{max-width:100%;max-height:85vh;display:block}
      audio{width:100%;max-width:500px;margin:20px 0}
      .audio-container{text-align:center;padding:40px 20px}
      .audio-info{margin-bottom:20px;opacity:0.8}
    </style>
    <header>
      <a class='btn' href='$back'>← back</a>
      <div style='opacity:.85'>$title</div>
      <span style='width:68px'><!-- spacer --></span>
    </header>
    <main>";

    if (str_starts_with($mime, 'image/')) {
        echo "<img src='" . htmlspecialchars($rawUrl, ENT_QUOTES) . "' alt='$title'>";
    } elseif (str_starts_with($mime, 'video/')) {
        echo "<video controls autoplay src='" . htmlspecialchars($rawUrl, ENT_QUOTES) . "'></video>";
    } elseif (str_starts_with($mime, 'audio/')) {
        echo "<div class='audio-container'>";
        echo "<div class='audio-info'>";
        echo "<h3 style='margin:0 0 10px 0'>🎵 " . htmlspecialchars(basename($abs), ENT_QUOTES) . "</h3>";
        echo "<p style='margin:0;opacity:0.7'>Audio File</p>";
        echo "</div>";
        echo "<audio controls autoplay src='" . htmlspecialchars($rawUrl, ENT_QUOTES) . "'></audio>";
        echo "</div>";
    } else {
        echo "<div style='opacity:.75'>no inline preview for this file type</div>";
    }
    echo "</main>";
    exit;
}

/* --------------------------- directory listing -------------------------- */

$items = [];
if ($isDir) {
    $scan = @scandir($abs) ?: [];
    foreach ($scan as $name) {
        if ($name === '.' || $name === '..') continue;
        // hide all dot files (hidden files)
        if (substr($name, 0, 1) === '.') continue;
        // hide macOS junk
        if (substr($name, 0, 2) === '._') continue;

        $child = join_child($abs, $name);
        if (!is_readable($child)) continue;

        $items[] = [
            'name'   => $name,
            'abs'    => $child,
            'is_dir' => is_dir($child),
            'modified' => @filemtime($child) ?: time(),
        ];
    }
    usort($items, function ($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Files - OfflineBox</title>
  <link rel="stylesheet" href="files.css">
</head>
<body>
  <div class="container">
    <div class="section">
      <div class="section-header">
        <div class="header-left">
          <a class="back-link" href="files.php" title="Back to Files" aria-label="Back to Files">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <a class="home-link" href="../home/home.php" title="Home" aria-label="Home">
            <?php
              $home_icon = '/data/icons/home.svg'; // fallback
              if ($home_app && isset($home_app['icon'])) {
                $home_icon = $home_app['icon'];
              }
              if (strpos($home_icon, '/data/') === 0) {
                $home_icon = '../..' . $home_icon;
              }
            ?>
            <img src="<?= htmlspecialchars($home_icon) ?>" alt="Home" width="18" height="18" />
          </a>
        </div>
        <h2 class="section-title">File Explorer</h2>
        <div class="header-actions">
          <button class="icon-btn" title="Refresh" onclick="location.reload()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
      </div>
      
      <?php if ($abs === '/' && !empty($recents)): ?>
      <!-- Recent Files Section - only show at root -->
      <div class="recent-files-section">
        <h3 class="recent-title">Recent Files</h3>
        <div class="grid">
          <?php foreach (array_slice($recents, 0, 8) as $recent): ?>
            <?php
            $name = $recent['name'];
            $path = $recent['path'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $isImg = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','tif','tiff']);
            $isVid = in_array($ext, ['mp4','m4v','mov','webm','ogg','ogv','mkv']);
            $isAudio = in_array($ext, ['mp3','wav','m4a','flac','aac','ogg']);
            
            // Get current page URL for back parameter
            $currentPage = $_SERVER['REQUEST_URI'];
            
            // Determine the link based on file type
            if ($isImg || $isVid || $isAudio) {
              $link = "?path=" . urlencode($path) . "&view=1&back=" . urlencode($currentPage);
            } else {
              $link = "?path=" . urlencode($path) . "&raw=1";
            }
            ?>
            <a class="card" href="<?= $link ?>">
              <div class="icon">
                <?php
                // File type icon based on extension
                switch ($ext) {
                  case 'pdf': case 'zim': 
                    echo '<img src="icons/pdf.png" alt="PDF" width="20" height="20" />'; 
                    break;
                  case 'txt': case 'md': 
                    echo '<img src="icons/text.png" alt="Text" width="20" height="20" />'; 
                    break;
                  case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': case 'bmp': case 'svg': case 'tif': case 'tiff': 
                    echo '<img src="icons/image.png" alt="Image" width="20" height="20" />'; 
                    break;
                  case 'mp4': case 'avi': case 'mov': case 'webm': case 'ogg': case 'ogv': case 'mkv': 
                    echo '<img src="icons/video.png" alt="Video" width="20" height="20" />'; 
                    break;
                  case 'mp3': case 'wav': case 'm4a': case 'flac': case 'aac': 
                    echo '<img src="icons/audio.png" alt="Audio" width="20" height="20" />'; 
                    break;
                  case 'zip': case 'rar': case '7z': case 'tar': case 'gz': case 'bz2': 
                    echo '<img src="icons/archive.png" alt="Archive" width="20" height="20" />'; 
                    break;
                  case 'doc': case 'docx': case 'rtf': case 'odt': 
                    echo '<img src="icons/document.png" alt="Document" width="20" height="20" />'; 
                    break;
                  case 'xls': case 'xlsx': case 'csv': case 'ods': 
                    echo '<img src="icons/spreadsheet.png" alt="Spreadsheet" width="20" height="20" />'; 
                    break;
                  case 'gba': case 'snes': case 'smc': case 'sfc': case 'nes': case 'gb': case 'gbc': case 'n64': case 'z64': case 'v64': case 'rom': case 'bin': case 'iso': 
                    echo '<img src="icons/rom.png" alt="ROM" width="20" height="20" />'; 
                    break;
                  default: 
                    echo '<img src="icons/default.png" alt="File" width="20" height="20" />'; 
                    break;
                }
                ?>
              </div>
              <div class="label"><?= htmlspecialchars($recent['name']) ?></div>
              <div class="meta"><?= htmlspecialchars($recent['modified'] ?? 'Unknown') ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="breadcrumbs">
        <?php
          $crumbs = breadcrumb_links($abs);
          $first = true;
          foreach ($crumbs as [$label, $link]) {
            if (!$first) echo ' / ';
            $first = false;
            $disp = $label === '/' ? '/' : htmlspecialchars($label);
            echo '<a href="?path=' . urlencode($link) . '">' . $disp . '</a>';
          }
        ?>
      </div>
      
      <div class="card">
        <div class="file-browser-header">
          <div class="header-icon"></div>
          <div class="header-name">Name</div>
          <div class="header-modified">Modified</div>
        </div>

        <?php if ($abs !== '/'): ?>
        <div class="file-item folder">
          <div class="file-icon"><img src="icons/folder.png" alt="Folder" width="20" height="20" /></div>
          <div class="file-info">
            <a class="file-name" href="?path=<?= urlencode(dirname($abs) ?: '/') ?>">.. (Parent Directory)</a>
          </div>
          <div class="file-modified"></div>
        </div>
        <?php endif; ?>

        <?php foreach ($items as $it): ?>
        <?php
          $ext   = strtolower(pathinfo($it['name'], PATHINFO_EXTENSION));
          $isImg = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','tif','tiff']);
          $isVid = in_array($ext, ['mp4','m4v','mov','webm','ogg','ogv','mkv']);
          $isAudio = in_array($ext, ['mp3','wav','m4a','flac','aac','ogg']);
          $link  = '?path=' . urlencode($it['abs']);
          $raw   = $link . '&raw=1';
          $view  = $link . '&view=1&back=' . urlencode($_SERVER['REQUEST_URI']);
        ?>
        <div class="file-item <?= $it['is_dir'] ? 'folder' : 'file' ?>">
          <div class="file-icon">
            <?php if ($it['is_dir']): ?>
              <img src="icons/folder.png" alt="Folder" width="20" height="20" />
            <?php else: 
              $ext = strtolower(pathinfo($it['name'], PATHINFO_EXTENSION));
              switch ($ext) {
                case 'pdf': case 'zim': 
                  echo '<img src="icons/pdf.png" alt="PDF" width="20" height="20" />'; 
                  break;
                case 'txt': case 'md': 
                  echo '<img src="icons/text.png" alt="Text" width="20" height="20" />'; 
                  break;
                case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': case 'bmp': case 'svg': case 'tif': case 'tiff': 
                  echo '<img src="icons/image.png" alt="Image" width="20" height="20" />'; 
                  break;
                case 'mp4': case 'avi': case 'mov': case 'webm': case 'ogg': case 'ogv': case 'mkv': 
                  echo '<img src="icons/video.png" alt="Video" width="20" height="20" />'; 
                  break;
                case 'mp3': case 'wav': case 'm4a': case 'flac': case 'aac': 
                  echo '<img src="icons/audio.png" alt="Audio" width="20" height="20" />'; 
                  break;
                case 'zip': case 'rar': case '7z': case 'tar': case 'gz': case 'bz2': 
                  echo '<img src="icons/archive.png" alt="Archive" width="20" height="20" />'; 
                  break;
                case 'doc': case 'docx': case 'rtf': case 'odt': 
                  echo '<img src="icons/document.png" alt="Document" width="20" height="20" />'; 
                  break;
                case 'xls': case 'xlsx': case 'csv': case 'ods': 
                  echo '<img src="icons/spreadsheet.png" alt="Spreadsheet" width="20" height="20" />'; 
                  break;
                case 'gba': case 'snes': case 'smc': case 'sfc': case 'nes': case 'gb': case 'gbc': case 'n64': case 'z64': case 'v64': case 'rom': case 'bin': case 'iso': 
                  echo '<img src="icons/rom.png" alt="ROM" width="20" height="20" />'; 
                  break;
                default: 
                  echo '<img src="icons/default.png" alt="File" width="20" height="20" />'; 
                  break;
              }
            endif; ?>
          </div>
          <div class="file-info">
            <?php if ($it['is_dir']): ?>
              <a class="file-name" href="<?= $link ?>"><?= htmlspecialchars($it['name']) ?></a>
            <?php else: ?>
              <?php if ($isImg || $isVid || $isAudio): ?>
                <a class="file-name" href="<?= $view ?>"><?= htmlspecialchars($it['name']) ?></a>
              <?php else: ?>
                <a class="file-name" href="<?= $raw ?>" download><?= htmlspecialchars($it['name']) ?></a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div class="file-modified"><?= formatDateBrowser((int)$it['modified']) ?></div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($items) && $isDir): ?>
          <div class="browser-empty">This folder is empty</div>
        <?php elseif (!$isDir): ?>
          <div class="browser-empty">This is a file</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
