<?php
declare(strict_types=1);

/**
 * OfflineBox — Simple Files (video viewer, minimal chrome)
 * - Starts at /mnt/offlinebox if it exists, but you can browse the whole /
 * - Absolute breadcrumbs from /
 * - Inline viewer for images AND videos
 * - No upload UI, no actions column, no modified column, no left path badge
 */

$DEFAULT_START = '/mnt/offlinebox';

/* ------------------------------- helpers -------------------------------- */

function safe_real(string $p): string|false {
    $r = @realpath($p);
    if ($r === false) return false;
    return $r === '/' ? '/' : rtrim($r, '/');
}
function is_abs(string $p): bool { return isset($p[0]) && $p[0] === '/'; }
function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
function join_child(string $parent, string $name): string {
    return $parent === '/' ? '/' . $name : $parent . '/' . $name;
}
function breadcrumb_links(string $absPath): array {
    $out = [['/', '/']];
    if ($absPath === '/') return $out;
    $parts = array_values(array_filter(explode('/', trim($absPath, '/')), 'strlen'));
    $acc = '';
    foreach ($parts as $seg) { $acc .= '/' . $seg; $out[] = [$seg, $acc]; }
    return $out;
}

/* ------------------------------- routing -------------------------------- */

$reqPath = $_GET['path'] ?? '';
$start   = (is_dir($DEFAULT_START) && is_readable($DEFAULT_START)) ? $DEFAULT_START : '/';

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
    $size = filesize($abs);
    $mime = @mime_content_type($abs) ?: 'application/octet-stream';
    $startB = 0; $endB = $size - 1;

    header('Accept-Ranges: bytes');
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        $startB = (int)$m[1];
        if ($m[2] !== '') $endB = (int)$m[2];
        if ($startB > $endB || $startB < 0 || $endB >= $size) { header('HTTP/1.1 416 Requested Range Not Satisfiable'); exit; }
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
    $mime = @mime_content_type($abs) ?: '';
    $title = htmlspecialchars(basename($abs), ENT_QUOTES);
    $rawUrl = '?path=' . urlencode($abs) . '&raw=1';
    $back   = '?path=' . urlencode(dirname($abs) ?: '/');

    echo "<!doctype html><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>$title</title>
    <style>
      body{margin:0;background:#000;color:#fff;font-family:system-ui;min-height:100vh;display:flex;flex-direction:column}
      header{position:sticky;top:0;display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:rgba(0,0,0,.6);backdrop-filter:blur(6px)}
      a.btn{padding:6px 10px;border-radius:10px;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.15)}
      main{flex:1;display:flex;align-items:center;justify-content:center;padding:8px}
      img,video{max-width:100%;max-height:85vh;display:block}
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
        // plays with Range support via &raw=1; most browsers will stream/scrub
        echo "<video controls autoplay src='" . htmlspecialchars($rawUrl, ENT_QUOTES) . "'></video>";
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
        // hide macOS junk
        if ($name === '.fseventsd' || $name === '.Spotlight-V100' || $name === '.DS_Store') continue;
        if (substr($name, 0, 2) === '._') continue;

        $child = join_child($abs, $name);
        if (!is_readable($child)) continue;

        $items[] = [
            'name'   => $name,
            'abs'    => $child,
            'is_dir' => is_dir($child),
            'size'   => is_file($child) ? (@filesize($child) ?: 0) : 0,
        ];
    }
    usort($items, function ($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

/* --------------------------------- view --------------------------------- */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>OfflineBox File Browser</title>
<link rel="stylesheet" href="../assets/style.css?v=5">
<style>
:root{--bg:#121212;--fg:#d0d0d0;--muted:#909090;--line:#2a2a2a;--panel:#1f1f1f;--panel2:#242424;--accent:#cfcfcf}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
.wrap{max-width:900px;margin:0 auto;min-height:100vh;padding:40px 24px}
.status{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin:0 0 12px}
a.btn{padding:6px 10px;border-radius:10px;background:var(--panel2);color:var(--accent);text-decoration:none;border:1px solid rgba(255,255,255,.05)}
a.btn:hover{background:rgba(255,255,255,.05);color:var(--fg)}
.breadcrumbs{display:flex;align-items:center;gap:8px;margin:8px 0 16px;color:var(--muted);font-size:14px}
.breadcrumbs a{color:var(--accent);text-decoration:none} .breadcrumbs a:hover{color:var(--fg)}
.card{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.18)}
.head{background:rgba(255,255,255,.03);padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;color:var(--muted);font-weight:600;font-size:14px}
.row{display:flex;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line)} .row:last-child{border-bottom:none}
.icon{width:30px;margin-right:12px} .name{flex:1}
.name a{color:var(--fg);text-decoration:none} .name a:hover{color:var(--accent)}
.size{width:120px;text-align:right;color:var(--muted);font-size:14px}
.empty{padding:60px 20px;text-align:center;color:var(--muted)}
</style>
</head>
<body>
<div class="wrap">

  <div class="status">
    <?php if (is_dir($DEFAULT_START)): ?>
      <a class="btn" href="?path=<?= urlencode($DEFAULT_START) ?>">/mnt/offlinebox</a>
    <?php endif; ?>
  </div>

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
    <div class="head">
      <div class="icon"></div>
      <div class="name">Name</div>
      <div class="size">Size</div>
    </div>

    <?php if ($abs !== '/'): ?>
    <div class="row">
      <div class="icon">📁</div>
      <div class="name">
        <a href="?path=<?= urlencode(dirname($abs) ?: '/') ?>">.. (Parent Directory)</a>
      </div>
      <div class="size"></div>
    </div>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
    <?php
      $ext   = strtolower(pathinfo($it['name'], PATHINFO_EXTENSION));
      $isImg = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','tif','tiff']);
      $isVid = in_array($ext, ['mp4','m4v','mov','webm','ogg','ogv','mkv']);
      $link  = '?path=' . urlencode($it['abs']);
      $raw   = $link . '&raw=1';
      $view  = $link . '&view=1';
    ?>
    <div class="row">
      <div class="icon"><?= $it['is_dir'] ? '📁' : '📄' ?></div>
      <div class="name">
        <?php if ($it['is_dir']): ?>
          <a href="<?= $link ?>"><?= htmlspecialchars($it['name']) ?></a>
        <?php else: ?>
          <?php if ($isImg || $isVid): ?>
            <a href="<?= $view ?>"><?= htmlspecialchars($it['name']) ?></a>
          <?php else: ?>
            <a href="<?= $link ?>"><?= htmlspecialchars($it['name']) ?></a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="size"><?= $it['is_dir'] ? '' : formatFileSize((int)$it['size']) ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($items) && $isDir): ?>
      <div class="empty">This folder is empty</div>
    <?php elseif (!$isDir): ?>
      <div class="empty">This is a file. Append <code>&raw=1</code> or use the name link for preview when available.</div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>