<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

$apps = load_apps_json(__DIR__ . '/../../data/json/apps.json');
$sections_file = __DIR__ . '/../../data/json/home_sections.json';
$sections_data = [];
if (file_exists($sections_file)) {
  $sections_data = json_decode(file_get_contents($sections_file), true) ?: [];
}

// Load background configuration
$background_file = __DIR__ . '/../../data/json/background.json';
$background_config = 'default';
if (file_exists($background_file)) {
  $bg_data = json_decode(file_get_contents($background_file), true);
  $background_config = $bg_data['background'] ?? 'default';
}

// Convert to associative array and sort by order
$home_sections = [];
if (is_array($sections_data)) {
  foreach ($sections_data as $section) {
    if (isset($section['key']) && isset($section['visible'])) {
      $home_sections[$section['key']] = $section['visible'];
    }
  }
  // Sort sections by order field
  usort($sections_data, function($a, $b) { 
    return ($a['order'] ?? 999) - ($b['order'] ?? 999); 
  });
} else {
  // Fallback to old format for backward compatibility
  $home_sections = is_array($sections_data) ? $sections_data : ['hero'=>true,'favorites'=>true,'recent_files'=>true];
}

// Ensure defaults exist
$home_sections = array_merge(['hero'=>true,'favorites'=>true,'recent_files'=>true], $home_sections);
$sections_ordered = $sections_data ?: [
  ['key' => 'hero', 'visible' => true, 'order' => 1],
  ['key' => 'favorites', 'visible' => true, 'order' => 2], 
  ['key' => 'recent_files', 'visible' => true, 'order' => 3]
];

// Find admin app for admin link
$admin_app = null;
$home_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && $app['id'] === 'admin') {
    $admin_app = $app;
  }
  if (isset($app['id']) && $app['id'] === 'home') {
    $home_app = $app;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Home</title>
  <link rel="stylesheet" href="home.css?v=<?= time() ?>">
  <?php if ($background_config !== 'default'): ?>
    <style>
      body {
        background-image: url('../../data/backgrounds/<?= htmlspecialchars($background_config) ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
      }
      body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(18, 18, 18, 0.85);
        z-index: -1;
      }
    </style>
  <?php endif; ?>
</head>
<body>
  <div class="container">
    <?php foreach ($sections_ordered as $section): ?>
      <?php if (!$section['visible']) continue; ?>
      
      <?php if ($section['key'] === 'hero'): ?>
        <!-- Hero Section -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">OfflineBox</h2>
            <div class="header-actions">
              <?php if ($admin_app): ?>
                <?php
                  $admin_url = $admin_app['url'];
                  // Convert relative paths from web root to work from current page context
                  if (strpos($admin_url, '/pages/') === 0) {
                    $admin_url = '../..' . $admin_url;
                  }
                ?>
                <a href="<?= htmlspecialchars($admin_url) ?>" class="admin-link" title="<?= htmlspecialchars($admin_app['label']) ?>">
                  <?php
                    $admin_icon = $admin_app['icon'];
                    if (strpos($admin_icon, '/data/') === 0) {
                      $admin_icon = '../..' . $admin_icon;
                    }
                  ?>
                  <img src="<?= htmlspecialchars($admin_icon) ?>" alt="<?= htmlspecialchars($admin_app['label']) ?>" width="18" height="18" />
                </a>
              <?php endif; ?>
            </div>
          </div>
          <div class="hero-card">
            <div class="hero-content">
              <div class="hero-header">
                <div class="hero-icon">
                  <?php
                    $icon = '/data/icons/home.svg'; // fallback
                    if ($home_app && isset($home_app['icon'])) {
                      $icon = $home_app['icon'];
                    }
                    if (strpos($icon, '/data/') === 0) {
                      $icon = '../..' . $icon;
                    }
                  ?>
                  <img src="<?= htmlspecialchars($icon) ?>" alt="Home" width="20" height="20" />
                </div>
                <h3 class="hero-title">Local library &amp; tools</h3>
              </div>
              <p class="hero-subtext">If your device says "No Internet", that's normal on this network.</p>
            </div>
            <div class="search-container" style="margin-top: 20px;">
              <div class="search-input-container">
                <input type="text" id="search" class="search-input" placeholder="Search files, Wikipedia, and web archives..." autocomplete="off" />
                <button class="search-button" type="button" id="searchButton">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </button>
              </div>
              <div id="searchSuggestions" class="search-suggestions"></div>
            </div>
          </div>
        </div>

      <?php elseif ($section['key'] === 'favorites'): ?>
        <!-- Favorites Section -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">Favorites</h2>
            <div class="header-actions">
              <button id="toggleAllBtn" class="icon-btn" title="Show all applications" aria-pressed="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>
              </button>
            </div>
          </div>
          <div id="favorites" class="grid">
            <?php foreach ($apps as $a): ?>
              <?php if (isset($a['visible']) && $a['visible'] === false) continue; ?>
              <?php
              $url = $a['url'] ?? '';
              $icon = $a['icon'] ?? '';
              
              // Handle relative page URLs (apps.json URLs are already resolved by auto-config)
              if (strpos($url, '/pages/') === 0) {
                $url = '..' . substr($url, 6);
              }
              // Handle icon paths
              if (strpos($icon, '/data/') === 0) {
                $icon = '../..' . $icon;
              }
              ?>
              <a class="card" href="<?= htmlspecialchars($url) ?>">
                <img class="icon" src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($a['label']) ?>" />
                <div class="label"><?= htmlspecialchars($a['label']) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
          <div id="favorites-extra" style="display:none;">
            <div id="favorites-extra-header" class="favorites-extra-header"></div>
            <div id="hiddenFavorites" class="grid"></div>
          </div>
        </div>

      <?php elseif ($section['key'] === 'recent_files'): ?>
        <!-- Recent Files Section -->
        <div class="section">
          <h2 class="section-title">Recent Files</h2>
          <div class="card">
            <div id="recent">
              <?php
              $recents_file = __DIR__ . '/../../data/json/recents.json';
              if (file_exists($recents_file)) {
                $recents = json_decode(file_get_contents($recents_file), true);
                if ($recents && is_array($recents)) {
                  $maxShow = 5;
                  foreach (array_slice($recents, 0, $maxShow) as $item) {
                    $name = htmlspecialchars($item['name'] ?? 'Unknown');
                    $path = $item['path'] ?? $item['name'] ?? '';
                    $when = htmlspecialchars($item['when'] ?? $item['modified'] ?? '');
                    
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
                    
                    // Determine file type and create appropriate URL
                    $isImg = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','tif','tiff']);
                    $isVid = in_array($ext, ['mp4','m4v','mov','webm','ogg','ogv','mkv']);
                    $currentPage = $_SERVER['REQUEST_URI'];
                    
                    if ($isImg || $isVid) {
                      $url = '../files/files-browse.php?path=' . urlencode($path) . '&view=1&back=' . urlencode($currentPage);
                    } else {
                      $url = '../files/files-browse.php?path=' . urlencode($path) . '&raw=1';
                    }
                    
                    echo '<div class="recent-row">';
                    echo '<div class="recent-left">';
                    echo '<div class="recent-icon">';
                    
                    // File type icon based on extension
                    switch ($ext) {
                      case 'pdf': case 'zim': 
                        echo '<img src="../files/icons/pdf.png" alt="PDF" width="16" height="16" />'; 
                        break;
                      case 'txt': case 'md': 
                        echo '<img src="../files/icons/text.png" alt="Text" width="16" height="16" />'; 
                        break;
                      case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': case 'bmp': case 'svg': case 'tif': case 'tiff': 
                        echo '<img src="../files/icons/image.png" alt="Image" width="16" height="16" />'; 
                        break;
                      case 'mp4': case 'avi': case 'mov': case 'webm': case 'ogg': case 'ogv': case 'mkv': 
                        echo '<img src="../files/icons/video.png" alt="Video" width="16" height="16" />'; 
                        break;
                      case 'mp3': case 'wav': case 'm4a': case 'flac': case 'aac': 
                        echo '<img src="../files/icons/audio.png" alt="Audio" width="16" height="16" />'; 
                        break;
                      case 'zip': case 'rar': case '7z': case 'tar': case 'gz': case 'bz2': 
                        echo '<img src="../files/icons/archive.png" alt="Archive" width="16" height="16" />'; 
                        break;
                      case 'doc': case 'docx': case 'rtf': case 'odt': 
                        echo '<img src="../files/icons/document.png" alt="Document" width="16" height="16" />'; 
                        break;
                      case 'xls': case 'xlsx': case 'csv': case 'ods': 
                        echo '<img src="../files/icons/spreadsheet.png" alt="Spreadsheet" width="16" height="16" />'; 
                        break;
                      case 'gba': case 'snes': case 'smc': case 'sfc': case 'nes': case 'gb': case 'gbc': case 'n64': case 'z64': case 'v64': case 'rom': case 'bin': case 'iso': 
                        echo '<img src="../files/icons/rom.png" alt="ROM" width="16" height="16" />'; 
                        break;
                      default: 
                        echo '<img src="../files/icons/default.png" alt="File" width="16" height="16" />'; 
                        break;
                    }
                    
                    echo '</div>';
                    echo '<a class="recent-name" href="' . htmlspecialchars($url) . '">' . $name . '</a>';
                    echo '</div>';
                    echo '<div class="recent-actions">';
                    echo '<div class="recent-meta">' . $when . '</div>';
                    echo '</div>';
                    echo '</div>';
                  }
                } else {
                  echo '<div>No recent files</div>';
                }
              } else {
                echo '<div>No recent files</div>';
              }
              ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <script>
    // Minimal search UI wiring (suggestions hidden by default)
    document.addEventListener("DOMContentLoaded", function() {
      const searchInput = document.getElementById("search");
      const searchButton = document.getElementById("searchButton");
      const searchSuggestions = document.getElementById("searchSuggestions");
      if (!searchInput) return;

      function hideSuggestions(){ 
        if (searchSuggestions) {
          searchSuggestions.style.display = 'none'; 
          searchSuggestions.innerHTML = '';
        }
      }

      document.addEventListener('click', function(e){
        if (!e.target.closest('.search-container')) hideSuggestions();
      });

      // Search functionality
      let searchTimeout;
      
      function performSearch() {
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
          hideSuggestions();
          return;
        }
        
        // Show loading state
        if (searchSuggestions) {
          searchSuggestions.innerHTML = '<div class="search-loading">Searching files and Wikipedia...</div>';
          searchSuggestions.style.display = 'block';
        }
        
        // Search both files and Kiwix content with cache busting
        const cacheBuster = Date.now();
        const searchUrl = `search-api.php?q=${encodeURIComponent(query)}&type=all&limit=8&_=${cacheBuster}`;
        
        fetch(searchUrl, {
          method: 'GET',
          headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
          },
          cache: 'no-store'
        })
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            // Only update if this is still the current query
            if (searchInput.value.trim() === query) {
              displaySearchResults(data.results || [], query);
            }
          })
          .catch(error => {
            console.error('Search error:', error);
            if (searchSuggestions && searchInput.value.trim() === query) {
              searchSuggestions.innerHTML = '<div class="search-error">Search temporarily unavailable</div>';
            }
          });
      }
      
      function displaySearchResults(results, query) {
        if (!searchSuggestions) return;
        
        if (results.length === 0) {
          searchSuggestions.innerHTML = '<div class="search-no-results">No results found</div>';
          return;
        }
        
        let html = '<div class="search-results">';
        results.forEach(result => {
          const icon = getResultIcon(result);
          let snippetContent = '';
          
          if (result.snippet && result.type !== 'kiwix') {
            // Only show snippet for non-Kiwix results
            snippetContent = `<div class="search-result-snippet">${escapeHtml(result.snippet).substring(0, 100)}...</div>`;
          }
          
          // Different meta content for different result types
          let metaContent = '';
          if (result.type === 'kiwix') {
            const bookName = result.source || 'Wikipedia';
            metaContent = `
              <div class="search-result-meta">${bookName}</div>
              <div class="search-result-meta">Kiwix</div>
            `;
          } else {
            // File results: show folder then "Files"
            const folderName = result.folder || 'Unknown';
            metaContent = `
              <div class="search-result-meta">${folderName}</div>
              <div class="search-result-meta">Files</div>
            `;
          }
          
          html += `
            <a href="${result.url}" class="search-result-item" ${result.type === 'kiwix' ? 'target="_blank"' : ''}>
              <div class="search-result-icon">${icon}</div>
              <div class="search-result-content">
                <div class="search-result-name">${escapeHtml(result.name)}</div>
                ${metaContent}
                ${snippetContent}
              </div>
            </a>
          `;
        });
        html += '</div>';
        
        searchSuggestions.innerHTML = html;
        searchSuggestions.style.display = 'block';
      }
      
      function getResultIcon(result) {
        if (result.type === 'kiwix') {
          return '📖'; // Wikipedia/Kiwix icon
        }
        return getFileIcon(result.type, result.extension);
      }
      
      function getFileIcon(type, extension) {
        // Convert extension to lowercase for consistent matching
        const ext = extension ? extension.toLowerCase() : '';
        
        // Determine icon based on extension first, then type
        switch (ext) {
          case 'pdf':
          case 'zim':
            return '<img src="../files/icons/pdf.png" alt="PDF" width="16" height="16" />';
          case 'txt':
          case 'md':
            return '<img src="../files/icons/text.png" alt="Text" width="16" height="16" />';
          case 'jpg':
          case 'jpeg':
          case 'png':
          case 'gif':
          case 'webp':
          case 'bmp':
          case 'svg':
          case 'tif':
          case 'tiff':
            return '<img src="../files/icons/image.png" alt="Image" width="16" height="16" />';
          case 'mp4':
          case 'avi':
          case 'mov':
          case 'webm':
          case 'ogg':
          case 'ogv':
          case 'mkv':
            return '<img src="../files/icons/video.png" alt="Video" width="16" height="16" />';
          case 'mp3':
          case 'wav':
          case 'm4a':
          case 'flac':
          case 'aac':
            return '<img src="../files/icons/audio.png" alt="Audio" width="16" height="16" />';
          case 'zip':
          case 'rar':
          case '7z':
          case 'tar':
          case 'gz':
          case 'bz2':
            return '<img src="../files/icons/archive.png" alt="Archive" width="16" height="16" />';
          case 'doc':
          case 'docx':
          case 'rtf':
          case 'odt':
            return '<img src="../files/icons/document.png" alt="Document" width="16" height="16" />';
          case 'xls':
          case 'xlsx':
          case 'csv':
          case 'ods':
            return '<img src="../files/icons/spreadsheet.png" alt="Spreadsheet" width="16" height="16" />';
          case 'gba':
          case 'snes':
          case 'smc':
          case 'sfc':
          case 'nes':
          case 'gb':
          case 'gbc':
          case 'n64':
          case 'z64':
          case 'v64':
          case 'rom':
          case 'bin':
          case 'iso':
            return '<img src="../files/icons/rom.png" alt="ROM" width="16" height="16" />';
          default:
            // Fallback to type-based icons
            switch (type) {
              case 'image':
                return '<img src="../files/icons/image.png" alt="Image" width="16" height="16" />';
              case 'video':
                return '<img src="../files/icons/video.png" alt="Video" width="16" height="16" />';
              case 'audio':
                return '<img src="../files/icons/audio.png" alt="Audio" width="16" height="16" />';
              case 'document':
                return '<img src="../files/icons/pdf.png" alt="Document" width="16" height="16" />';
              case 'archive':
                return '<img src="../files/icons/archive.png" alt="Archive" width="16" height="16" />';
              default:
                return '<img src="../files/icons/default.png" alt="File" width="16" height="16" />';
            }
        }
      }
      
      function getResultSubtitle(result) {
        if (result.type === 'kiwix') {
          // Show source then "Kiwix" label
          let subtitle = result.source || 'Wikipedia';
          subtitle += ' • Kiwix';
          return subtitle;
        }
        const folderInfo = result.folder ? ` • ${result.folder}` : '';
        return `${result.modified}${folderInfo}`;
      }
      
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
      });

      searchInput.addEventListener('keydown', function(e){ 
        if (e.key === 'Enter') { 
          e.preventDefault(); 
          clearTimeout(searchTimeout);
          performSearch();
        } 
      });
      
      searchButton && searchButton.addEventListener('click', function(){ 
        performSearch();
      });
    });
    
    // Favorites show-all toggle
    (function(){
      const apps = <?= json_encode($apps) ?>;
      let showAll = false;
      const favoritesEl = document.getElementById('favorites');
      const toggleBtn = document.getElementById('toggleAllBtn');

      function renderFavorites(){
        if (!favoritesEl) return; // favorites section disabled server-side
        const hiddenContainer = document.getElementById('hiddenFavorites');
        const extras = document.getElementById('favorites-extra');
        favoritesEl.innerHTML = '';
        if (hiddenContainer) hiddenContainer.innerHTML = '';

        const visible = [];
        const hidden = [];
        apps.forEach(a=>{
          if (a.visible === false) hidden.push(a); else visible.push(a);
        });

        function makeItem(a){
          let url = a.url || '';
          let icon = a.icon || '';
          if (typeof url === 'string' && url.indexOf('/pages/') === 0) url = '..' + url.substr(6);
          if (typeof icon === 'string' && icon.indexOf('/data/') === 0) icon = '../..' + icon;
          const item = document.createElement('a');
          item.className = 'card';
          item.href = url || '#';
          item.innerHTML = `<img class="icon" src="${icon}" alt="${(a.label||'')}"><div class="label">${(a.label||'')}</div>`;
          return item;
        }

        // Group by folders
        const folders = {};
        const noFolder = [];
        
        visible.forEach(app => {
          if (app.folder) {
            if (!folders[app.folder]) {
              folders[app.folder] = [];
            }
            folders[app.folder].push(app);
          } else {
            noFolder.push(app);
          }
        });

        function makeFolderIcon(apps) {
          const gridSize = 2; // 2x2 grid for simplicity
          let iconHtml = '<div class="folder-icon-grid">';
          
          for (let i = 0; i < 4; i++) {
            if (i < apps.length) {
              let icon = apps[i].icon || '';
              if (typeof icon === 'string' && icon.indexOf('/data/') === 0) icon = '../..' + icon;
              iconHtml += `<div class="folder-icon-slot"><img src="${icon}" alt=""></div>`;
            } else {
              iconHtml += '<div class="folder-icon-slot empty-slot"></div>';
            }
          }
          
          iconHtml += '</div>';
          return iconHtml;
        }

        // Render items without folders first
        noFolder.forEach(v => favoritesEl.appendChild(makeItem(v)));

        // Render folders as cards
        Object.keys(folders).sort().forEach(folderName => {
          const folderCard = document.createElement('a');
          folderCard.className = 'card folder-card';
          folderCard.href = `folder.php?folder=${encodeURIComponent(folderName)}`;
          
          folderCard.innerHTML = `
            ${makeFolderIcon(folders[folderName])}
            <div class="label">${folderName}</div>
          `;
          
          favoritesEl.appendChild(folderCard);
        });

        const headerEl = document.getElementById('favorites-extra-header');
        if (showAll && hidden.length && hiddenContainer && extras) {
          extras.style.display = '';
          if (headerEl) headerEl.textContent = `Hidden apps (${hidden.length})`;
          hidden.forEach(h=> hiddenContainer.appendChild(makeItem(h)));
        } else if (extras) {
          extras.style.display = 'none';
          if (headerEl) headerEl.textContent = '';
        }
      }

      if (toggleBtn) {
        toggleBtn.addEventListener('click', function(){
    showAll = !showAll;
    toggleBtn.title = showAll ? 'Show only visible' : 'Show all applications';
    // visual state
    toggleBtn.setAttribute('aria-pressed', showAll ? 'true' : 'false');
    if (showAll) toggleBtn.classList.add('active'); else toggleBtn.classList.remove('active');
    renderFavorites();
        });
      }

      // initial render already printed server-side, but re-render to normalize client state
      renderFavorites();
    })();

    // Listen for home sections updates from the admin page and reload to reflect changes
    (function(){
      function handleUpdate(){
        console.info('Home sections updated elsewhere, reloading to pick up changes');
        window.location.reload();
      }

      function handleBackgroundChange(){
        console.info('Background changed elsewhere, reloading to pick up changes');
        window.location.reload();
      }

      if (typeof BroadcastChannel !== 'undefined') {
        try {
          const bc = new BroadcastChannel('offlinebox');
          bc.addEventListener('message', (m)=>{ 
            if (m.data && m.data.type === 'home_sections_saved') {
              handleUpdate();
            } else if (m.data && m.data.type === 'background_changed') {
              handleBackgroundChange();
            }
          });
        }catch(e){}
      }
    })();
  </script>
</body>
</html>
