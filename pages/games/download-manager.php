<?php
// ROM Download Manager - handles downloading games to local storage for fast access

require_once __DIR__ . '/config/paths.php';

class RomDownloadManager {
    private $downloadDir;
    private $downloadIndexFile;
    private $maxDownloadSize; // in MB
    
    public function __construct() {
        // Use Pi-compatible paths
        $this->downloadDir = GamesPaths::getDownloadedGamesDir();
        $this->downloadIndexFile = GamesPaths::getDownloadIndexFile();
        $this->maxDownloadSize = 2000; // 2GB max downloads
        
        // Create downloads directory if it doesn't exist
        if (!is_dir($this->downloadDir)) {
            @mkdir($this->downloadDir, 0755, true);
        }
    }
    
    // Check if a ROM is downloaded locally
    public function isDownloaded($romPath) {
        $downloadKey = $this->getDownloadKey($romPath);
        $downloadedFile = $this->downloadDir . '/' . $downloadKey;
        return file_exists($downloadedFile);
    }
    
    // Get downloaded ROM path or null if not downloaded
    public function getDownloadedPath($romPath) {
        if (!$this->isDownloaded($romPath)) {
            return null;
        }
        
        $downloadKey = $this->getDownloadKey($romPath);
        $downloadedFile = $this->downloadDir . '/' . $downloadKey;
        
        // Update access time in index
        $this->updateAccessTime($romPath);
        
        return $downloadedFile;
    }
    
    // Download a ROM file to local storage
    public function downloadRom($romPath) {
        if (!file_exists($romPath)) {
            return ['success' => false, 'error' => 'Source ROM file not found'];
        }
        
        $downloadKey = $this->getDownloadKey($romPath);
        $downloadedFile = $this->downloadDir . '/' . $downloadKey;
        
        // Don't download if already exists
        if (file_exists($downloadedFile)) {
            $this->updateAccessTime($romPath);
            return ['success' => true, 'message' => 'Already downloaded', 'path' => $downloadedFile];
        }
        
        // Check space before downloading
        $romSize = filesize($romPath);
        $stats = $this->getStats();
        if (($stats['size'] + $romSize) > ($this->maxDownloadSize * 1024 * 1024)) {
            return ['success' => false, 'error' => 'Not enough space. Clear some downloads first.'];
        }
        
        // Copy file to downloads directory
        if (copy($romPath, $downloadedFile)) {
            $this->addToIndex($romPath, $downloadKey, $romSize);
            return ['success' => true, 'message' => 'Game downloaded successfully', 'path' => $downloadedFile];
        }
        
        return ['success' => false, 'error' => 'Failed to download game'];
    }
    
    // Remove a downloaded ROM
    public function removeDownload($romPath) {
        $downloadKey = $this->getDownloadKey($romPath);
        $downloadedFile = $this->downloadDir . '/' . $downloadKey;
        
        if (file_exists($downloadedFile)) {
            if (unlink($downloadedFile)) {
                $this->removeFromIndex($romPath);
                return ['success' => true, 'message' => 'Download removed successfully'];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to remove download'];
    }
    
    // Generate download key from ROM path
    private function getDownloadKey($romPath) {
        $filename = basename($romPath);
        $hash = md5($romPath);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return $hash . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($filename, PATHINFO_FILENAME)) . '.' . $ext;
    }
    
    // Add ROM to download index
    private function addToIndex($romPath, $downloadKey, $fileSize) {
        $index = $this->loadIndex();
        
        $index[$romPath] = [
            'downloadKey' => $downloadKey,
            'downloadedAt' => time(),
            'lastAccessed' => time(),
            'accessCount' => 1,
            'originalPath' => $romPath,
            'fileSize' => $fileSize,
            'filename' => basename($romPath),
            'console' => $this->getConsoleFromPath($romPath)
        ];
        
        $this->saveIndex($index);
    }
    
    // Remove ROM from download index
    private function removeFromIndex($romPath) {
        $index = $this->loadIndex();
        unset($index[$romPath]);
        $this->saveIndex($index);
    }
    
    // Update access time for a downloaded ROM
    private function updateAccessTime($romPath) {
        $index = $this->loadIndex();
        
        if (isset($index[$romPath])) {
            $index[$romPath]['lastAccessed'] = time();
            $index[$romPath]['accessCount'] = ($index[$romPath]['accessCount'] ?? 0) + 1;
            $this->saveIndex($index);
        }
    }
    
    // Extract console name from ROM path
    private function getConsoleFromPath($romPath) {
        $pathParts = explode('/', $romPath);
        // Assume console is second to last part of path (before filename)
        return $pathParts[count($pathParts) - 2] ?? 'unknown';
    }
    
    // Load download index
    private function loadIndex() {
        if (!file_exists($this->downloadIndexFile)) {
            return [];
        }
        
        $content = file_get_contents($this->downloadIndexFile);
        return json_decode($content, true) ?: [];
    }
    
    // Save download index
    private function saveIndex($index) {
        file_put_contents($this->downloadIndexFile, json_encode($index, JSON_PRETTY_PRINT));
    }
    
    // Get download statistics
    public function getStats() {
        $index = $this->loadIndex();
        $totalSize = 0;
        $totalFiles = 0;
        
        foreach ($index as $info) {
            $downloadedFile = $this->downloadDir . '/' . $info['downloadKey'];
            if (file_exists($downloadedFile)) {
                $totalSize += filesize($downloadedFile);
                $totalFiles++;
            }
        }
        
        return [
            'files' => $totalFiles,
            'size' => $totalSize,
            'sizeFormatted' => $this->formatBytes($totalSize),
            'maxSize' => $this->maxDownloadSize * 1024 * 1024,
            'maxSizeFormatted' => $this->maxDownloadSize . ' MB',
            'percentUsed' => $totalSize > 0 ? round(($totalSize / ($this->maxDownloadSize * 1024 * 1024)) * 100, 1) : 0
        ];
    }
    
    // Get all downloaded games
    public function getDownloadedGames() {
        $index = $this->loadIndex();
        $downloaded = [];
        
        foreach ($index as $romPath => $info) {
            $downloadedFile = $this->downloadDir . '/' . $info['downloadKey'];
            if (file_exists($downloadedFile)) {
                $downloaded[] = [
                    'originalPath' => $romPath,
                    'downloadedPath' => $downloadedFile,
                    'filename' => $info['filename'],
                    'console' => $info['console'],
                    'size' => $info['fileSize'],
                    'sizeFormatted' => $this->formatBytes($info['fileSize']),
                    'downloadedAt' => $info['downloadedAt'],
                    'lastAccessed' => $info['lastAccessed'],
                    'accessCount' => $info['accessCount']
                ];
            }
        }
        
        // Sort by last accessed (most recent first)
        usort($downloaded, function($a, $b) {
            return $b['lastAccessed'] - $a['lastAccessed'];
        });
        
        return $downloaded;
    }
    
    // Format bytes for display
    private function formatBytes($bytes) {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
    
    // Clear all downloads
    public function clearAllDownloads() {
        $files = glob($this->downloadDir . '/*');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'downloads_index.json') {
                if (unlink($file)) {
                    $cleared++;
                }
            }
        }
        
        // Clear the index
        $this->saveIndex([]);
        
        return $cleared;
    }
}
?>
