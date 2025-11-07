<?php
require_once 'download-manager.php';
require_once 'config/paths.php';

// ROM server with download support
if (!isset($_GET['file'])) { http_response_code(400); die('file required'); }
$file = $_GET['file'];

// Initialize download manager
$downloadManager = new RomDownloadManager();

// Only allow files under these roots
$allowed = GamesPaths::getAllowedRomPaths();
$resolved = realpath($file);
if (!$resolved) { http_response_code(404); die('not found'); }

$ok = false;
foreach($allowed as $a){
    $ra = realpath($a) ?: $a;
    if (strpos($resolved, $ra) === 0) { $ok = true; break; }
}
if (!$ok) { http_response_code(403); die('forbidden'); }

// Check if file is downloaded locally (fastest option)
$downloadedPath = $downloadManager->getDownloadedPath($resolved);
$servePath = $downloadedPath ?: $resolved;

$size = filesize($servePath);
$f = fopen($servePath, 'rb');
if ($f === false) { http_response_code(500); die('cannot open'); }

// Add download status header
header('X-ROM-Downloaded: ' . ($downloadedPath ? 'true' : 'false'));
header('X-ROM-Source: ' . ($downloadedPath ? 'local' : 'external'));

// MIME
$mime = 'application/octet-stream';
header('Content-Type: '.$mime);
header('Accept-Ranges: bytes');

$start = 0; $end = $size - 1;
if (isset($_SERVER['HTTP_RANGE'])){
    if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)){
        if ($m[1] !== '') $start = intval($m[1]);
        if ($m[2] !== '') $end = intval($m[2]);
    }
    if ($start > $end || $start >= $size) { http_response_code(416); die(); }
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
    $length = $end - $start + 1;
    header('Content-Length: '.$length);
    fseek($f, $start);
    $buflen = 8192;
    while ($length > 0 && !feof($f)){
        $read = fread($f, min($buflen, $length));
        echo $read; flush();
        $length -= strlen($read);
    }
    fclose($f);
    exit;
}

header('Content-Length: '.$size);
// stream entire file
while (!feof($f)) { echo fread($f, 8192); flush(); }
fclose($f);
