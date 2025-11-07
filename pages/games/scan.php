<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'download-manager.php';
require_once 'config/paths.php';

$baseMount = GamesPaths::getExternalRomsDir();

// Initialize download manager
$downloadManager = new RomDownloadManager();

// Define supported systems and their file extensions
$systems = [
    'gba' => ['gba'],
    'gb' => ['gb'],
    'gbc' => ['gbc'],
    'nes' => ['nes'],
    'snes' => ['sfc', 'smc'],
    'nds' => ['nds'],
    'n64' => ['n64'],
    'genesis' => ['bin'],
    'pce' => ['pce']
];

$allowed = ['gba','gb','gbc','nes','sfc','smc','iso','bin','zip','nds','n64','pce'];

$games = [];
$availableSystems = [];

// Function to scan a system directory
function scanSystemPath($systemPath, $systemName, $extensions, $downloadManager) {
    global $allowed;
    $out = [];
    
    if (!is_dir($systemPath)) return $out;
    
    $files = scandir($systemPath);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (strpos($file, '._') === 0) continue; // Skip macOS metadata files
        
        $fullPath = $systemPath . '/' . $file;
        if (!is_file($fullPath)) continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        
        $size = filesize($fullPath);
        $isDownloaded = $downloadManager->isDownloaded($fullPath);
        
        // If game is downloaded, use the downloaded path for serving
        $servePath = $fullPath; // Default to external path
        if ($isDownloaded) {
            $downloadedPath = $downloadManager->getDownloadedPath($fullPath);
            if ($downloadedPath && file_exists($downloadedPath)) {
                $servePath = $downloadedPath;
            }
        }
        
        $out[] = [
            'name' => pathinfo($file, PATHINFO_FILENAME),
            'path' => $servePath, // Use local path if downloaded, otherwise external
            'originalPath' => $fullPath, // Keep original for download management
            'file' => $file,
            'console' => $systemName,
            'size' => $size,
            'sizeFormatted' => humanSize($size),
            'external' => !$isDownloaded, // Mark as not external if downloaded
            'downloaded' => $isDownloaded
        ];
    }
    return $out;
}

function humanSize($s){ 
    if($s > 1024*1024*1024) return round($s/(1024*1024*1024),1).' GB';
    if($s > 1024*1024) return round($s/(1024*1024),1).' MB'; 
    if($s > 1024) return round($s/1024,1).' KB'; 
    return $s.' B'; 
}

// Check what system directories exist and scan them
$debugInfo = [];
$debugInfo['baseMountPath'] = $baseMount;
$debugInfo['baseMountExists'] = is_dir($baseMount);
$debugInfo['baseMountReadable'] = is_readable($baseMount);
if (is_dir($baseMount)) {
    $debugInfo['mountExists'] = true;
    $dirs = scandir($baseMount);
    $debugInfo['allDirs'] = $dirs;
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $systemPath = $baseMount . '/' . $dir;
        if (!is_dir($systemPath)) continue;
        
        $debugInfo['foundDir'] = $dir;
        
        // Check if this directory name matches any of our supported systems
        $systemName = strtolower($dir);
        if (array_key_exists($systemName, $systems)) {
            $availableSystems[] = $systemName;
            $debugInfo['matchedSystem'] = $systemName;
            $games = array_merge($games, scanSystemPath($systemPath, $systemName, $systems[$systemName], $downloadManager));
        } else {
            $debugInfo['unmatchedDir'] = $dir;
        }
    }
} else {
    // If base mount doesn't exist, scan downloaded games directory instead
    $downloadedDir = GamesPaths::getDownloadedGamesDir();
    $debugInfo['mountExists'] = false;
    $debugInfo['downloadedDir'] = $downloadedDir;
    $debugInfo['downloadedDirExists'] = is_dir($downloadedDir);
    
    if (is_dir($downloadedDir)) {
        $debugInfo['scanningDownloaded'] = true;
        // Scan downloaded games directly
        foreach ($systems as $systemName => $extensions) {
            foreach ($extensions as $ext) {
                $pattern = $downloadedDir . '/*.' . $ext;
                $files = glob($pattern);
                foreach ($files as $file) {
                    $basename = basename($file);
                    if (strpos($basename, '._') === 0) continue; // Skip macOS metadata files
                    // Extract game name from filename (remove hash prefix if present)
                    $name = preg_replace('/^[a-f0-9]{32}_/', '', $basename);
                    $name = pathinfo($name, PATHINFO_FILENAME);
                    
                    $games[] = [
                        'name' => $name,
                        'path' => $file, // Local downloaded path
                        'console' => $systemName,
                        'size' => filesize($file),
                        'lastModified' => date('Y-m-d H:i:s', filemtime($file)),
                        'icon' => '',
                        'external' => false, // Not external since it's local
                        'downloaded' => true // Already downloaded
                    ];
                    
                    if (!in_array($systemName, $availableSystems)) {
                        $availableSystems[] = $systemName;
                    }
                }
            }
        }
        $debugInfo['downloadedGamesCount'] = count($games);
    } else {
        $debugInfo['error'] = "Neither ROMs directory ($baseMount) nor downloaded directory ($downloadedDir) found";
        error_log("Neither ROMs directory ($baseMount) nor downloaded directory ($downloadedDir) found");
    }
}

usort($games, function($a,$b){ return strcasecmp($a['name'],$b['name']); });

echo json_encode([
    'success' => true,
    'count' => count($games),
    'games' => $games,
    'systems' => $availableSystems,
    'baseMount' => $baseMount,
    'mountExists' => is_dir($baseMount),
    'debug' => $debugInfo
], JSON_PRETTY_PRINT);
