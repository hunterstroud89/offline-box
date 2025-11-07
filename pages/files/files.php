<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Load apps to find files app icon
$apps = json_decode(file_get_contents(__DIR__ . '/../../data/json/apps.json'), true) ?: [];

// Load recent files
$recents_path = __DIR__ . '/../../data/json/recents.json';
$recents = [];
if (file_exists($recents_path)) {
  $recents_json = file_get_contents($recents_path);
  $recents = json_decode($recents_json, true) ?: [];
}

// Find files app for hero icon
$files_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && $app['id'] === 'files') {
    $files_app = $app;
    break;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Files - OfflineBox</title>
  <link rel="stylesheet" href="files.css?v=<?= time() ?>">
</head>
<body>
  <div class="container">
    <div class="section">
      <div class="section-header">
        <a class="back-link" href="../home/home.php" title="Back to Home" aria-label="Back to Home">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <h2 class="section-title">Files</h2>
      </div>

      <div class="hero-card">
        <div class="hero-content">
          <div class="hero-left">
            <div class="hero-icon">
              <?php
                $icon = '/data/icons/files.svg'; // fallback
                if ($files_app && isset($files_app['icon'])) {
                  $icon = $files_app['icon'];
                }
                if (strpos($icon, '/data/') === 0) {
                  $icon = '../..' . $icon;
                }
              ?>
              <img src="<?= htmlspecialchars($icon) ?>" alt="Files" width="20" height="20" />
            </div>
            <h3>File Browser</h3>
          </div>
          <div class="hero-actions">
            <a href="files-browse.php" class="hero-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Browse
            </a>
          </div>
        </div>
        <div class="hero-subtext">
          <p>Browse and access files offline.</p>
        </div>
      </div>

    </div>

    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Offline Box Files</h2>
      </div>
      <?php
      $offlineBoxPath = '/media/hunter/OFFLINEBOX';
      if (is_dir($offlineBoxPath) && is_readable($offlineBoxPath)) {
        $files = @scandir($offlineBoxPath);
        if ($files === false) {
          echo '<p class="muted">Error reading external drive</p>';
        } else {
          $directories = [];
          $fileCount = 0;
          
          // Function to format dates
          function formatDate($timestamp) {
            return date('Y-m-d', $timestamp);
          }
          
          foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            // hide all dot files (hidden files)
            if (substr($file, 0, 1) === '.') continue;
            $fullPath = $offlineBoxPath . '/' . $file;
            if (is_dir($fullPath)) {
              $directories[] = $file;
            } else {
              $fileCount++;
            }
          }
          
          if (!empty($directories)) {
            echo '<div class="grid">';
            foreach ($directories as $dir) {
              $dirPath = urlencode($offlineBoxPath . '/' . $dir);
              $fullDirPath = $offlineBoxPath . '/' . $dir;
              $lastModified = @filemtime($fullDirPath) ?: time();
              $formattedDate = formatDate($lastModified);
              
              echo '<a href="files-browse.php?path=' . $dirPath . '">';
              echo '<div class="icon"><img src="icons/folder.png" alt="Folder" width="40" height="40" /></div>';
              echo '<div class="label">' . htmlspecialchars($dir) . '</div>';
              echo '<div class="meta">' . $formattedDate . '</div>';
              echo '</a>';
            }
            echo '</div>';
          }
        }
      } else {
        echo '<p class="muted">External drive not connected</p>';
      }
      ?>
    </div>

    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Recent Files</h2>
      </div>
      
      <div class="card">
        <div id="recent">
          <?php if (!empty($recents)): ?>
            <?php foreach (array_slice($recents, 0, 5) as $recent): ?>
              <?php
              $name = htmlspecialchars($recent['name']);
              $path = $recent['path'];
              $when = htmlspecialchars($recent['when'] ?? $recent['modified'] ?? '');
              
              // Try to get extension from name first, then from path if name has no extension
              $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
              
              // Check if the extension from name is a valid file extension
              $validExtensions = ['pdf', 'zim', 'txt', 'md', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tif', 'tiff', 
                                  'mp4', 'avi', 'mov', 'webm', 'ogg', 'ogv', 'mkv', 'mp3', 'wav', 'm4a', 'flac', 'aac',
                                  'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'doc', 'docx', 'rtf', 'odt', 'xls', 'xlsx', 'csv', 'ods',
                                  'gba', 'snes', 'smc', 'sfc', 'nes', 'gb', 'gbc', 'n64', 'z64', 'v64', 'rom', 'bin', 'iso'];
              
              if (empty($ext) || !in_array($ext, $validExtensions)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
              }
              
              $isImg = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','tif','tiff']);
              $isVid = in_array($ext, ['mp4','m4v','mov','webm','ogg','ogv','mkv']);
              $isAudio = in_array($ext, ['mp3','wav','m4a','flac','aac','ogg']);
              
              // Determine the link based on file type
              $currentPage = $_SERVER['REQUEST_URI'];
              
              if ($isImg || $isVid || $isAudio) {
                $link = "files-browse.php?path=" . urlencode($path) . "&view=1&back=" . urlencode($currentPage);
              } else {
                $link = "files-browse.php?path=" . urlencode($path) . "&raw=1";
              }
              ?>
              <div class="recent-row">
                <div class="recent-left">
                  <div class="recent-icon">
                    <?php
                    switch ($ext) {
                      case 'pdf': case 'zim': 
                        echo '<img src="icons/pdf.png" alt="PDF" width="16" height="16" />'; 
                        break;
                      case 'txt': case 'md': 
                        echo '<img src="icons/text.png" alt="Text" width="16" height="16" />'; 
                        break;
                      case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': case 'bmp': case 'svg': case 'tif': case 'tiff': 
                        echo '<img src="icons/image.png" alt="Image" width="16" height="16" />'; 
                        break;
                      case 'mp4': case 'avi': case 'mov': case 'webm': case 'ogg': case 'ogv': case 'mkv': 
                        echo '<img src="icons/video.png" alt="Video" width="16" height="16" />'; 
                        break;
                      case 'mp3': case 'wav': case 'm4a': case 'flac': case 'aac': 
                        echo '<img src="icons/audio.png" alt="Audio" width="16" height="16" />'; 
                        break;
                      case 'zip': case 'rar': case '7z': case 'tar': case 'gz': case 'bz2': 
                        echo '<img src="icons/archive.png" alt="Archive" width="16" height="16" />'; 
                        break;
                      case 'doc': case 'docx': case 'rtf': case 'odt': 
                        echo '<img src="icons/document.png" alt="Document" width="16" height="16" />'; 
                        break;
                      case 'xls': case 'xlsx': case 'csv': case 'ods': 
                        echo '<img src="icons/spreadsheet.png" alt="Spreadsheet" width="16" height="16" />'; 
                        break;
                      case 'gba': case 'snes': case 'smc': case 'sfc': case 'nes': case 'gb': case 'gbc': case 'n64': case 'z64': case 'v64': case 'rom': case 'bin': case 'iso': 
                        echo '<img src="icons/rom.png" alt="ROM" width="16" height="16" />'; 
                        break;
                      default: 
                        echo '<img src="icons/default.png" alt="File" width="16" height="16" />'; 
                        break;
                    }
                    ?>
                  </div>
                  <a class="recent-name" href="<?= $link ?>"><?= $name ?></a>
                </div>
                <div class="recent-actions">
                  <div class="recent-meta"><?= $when ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div>No recent files</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

