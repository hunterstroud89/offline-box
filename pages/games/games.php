<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';
require_once __DIR__ . '/config/paths.php';

// Games UI — index-style, tailored: single hero, no search bar, filter + refresh
// Load apps list to pick up icon for 'games' app if available
$apps_list = load_apps_json(__DIR__ . '/../../data/json/apps.json');

// find games app (by id or label)
$games_icon = '';
foreach ($apps_list as $a) {
    if (isset($a['id']) && strtolower($a['id']) === 'games') { $games_icon = $a['icon'] ?? ''; break; }
    if (isset($a['label']) && strtolower($a['label']) === 'games') { $games_icon = $a['icon'] ?? ''; break; }
}

// make icon path relative to this page if it begins with /data/
if ($games_icon && strpos($games_icon, '/data/') === 0) {
    $games_icon = '../..' . $games_icon;
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Games - OfflineBox</title>
    <link rel="stylesheet" href="games.css?v=<?= time() ?>">
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
                                <h2 class="section-title">Games Library</h2>
                        </div>
                        <div class="hero-card">
                                                <div class="hero-content">
                                                    <div class="hero-icon">
                                                        <?php if ($games_icon): ?>
                                                            <img src="<?= htmlspecialchars($games_icon) ?>" width="20" height="20" alt="Games" />
                                                        <?php else: ?>
                                                            <svg viewBox="0 0 20 20" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.8 10.4c0-1.76 1.44-3.2 3.2-3.2h3.6c1.76 0 3.2 1.44 3.2 3.2v.8a2.4 2.4 0 0 1-2.4 2.4H7.2A2.4 2.4 0 0 1 4.8 11.2v-.8Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 9.6a.7.7 0 1 0 0-1.399.7.7 0 0 0 0 1.399Zm5 2.8a.7.7 0 1 0 0-1.4.7.7 0 0 0 0 1.4Zm-2.5 0a.7.7 0 1 0 0-1.4.7.7 0 0 0 0 1.4Z" fill="currentColor"/></svg>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="hero-text">
                                                        <h3>Pure offline gaming</h3>
                                                        <p>Games are scanned from <code id="roms-path"><?php echo GamesPaths::getExternalRomsDir(); ?></code>.</p>
                                                    </div>
                                                </div>
            </div>
                                    <div class="filters-row">
                                        <div class="filters-label" style="color:var(--muted);">Filters:</div>
                                        <div class="hero-actions">
                                            <div id="filter-buttons" style="display:flex;gap:8px;align-items:center;"></div>
                                        </div>
                                    </div>
        </div>

            <div class="section">
                <h2 class="section-title">Games</h2>
                <div class="card" id="favorites">Loading…</div>
            </div>

        <div class="section">
            <h2 class="section-title">Download Storage</h2>
            <div class="card"><div id="download-status">Loading…</div></div>
        </div>
    </div>

    <script>
        let _games = [];
        let _systems = [];
        let _apiResponse = null;
        
        function getGameIconPath(console) {
            const extension = (console === 'gba') ? 'png' : 'svg';
            return `/pages/games/game-icons/${console}.${extension}`;
        }

        async function loadGames() {
            // The refresh button was removed from the UI; guard access in case it's absent
            const _refreshEl = document.getElementById('refresh');
            if (_refreshEl) _refreshEl.disabled = true;
            try{
                const res = await fetch('<?php echo GamesPaths::getScanApiUrl(); ?>');
                if(!res.ok) throw new Error('Scan API ' + res.status);
                const json = await res.json();
                _apiResponse = json; // Store full response for debugging
                _games = json.games || [];
                _systems = json.systems || [];
                
                console.log('API Response:', json);
                console.log('Found systems:', _systems);
                console.log('Found games:', _games.length);
                console.log('Debug info:', json.debug);
                console.log('Mount exists:', json.mountExists);
                console.log('Base mount:', json.baseMount);
                
                buildFilters();
                renderGames();
                loadDownloadStatus();
            }catch(e){
                console.error('Load games error:', e);
                document.getElementById('favorites').innerHTML = `
                    <div class="muted">Failed to load games</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 8px;">
                        Check console for details. Path: <?php echo GamesPaths::getExternalRomsDir(); ?>
                    </div>
                `;
                // Removed reference to non-existent 'recent' element
            }
            // refresh button removed; no element to re-enable
        }

            function renderGames(){
                const activeBtn = document.querySelector('#filter-buttons .filter-btn.active');
                    const f = activeBtn ? activeBtn.getAttribute('data-console') : '';
                const fav = document.getElementById('favorites');
                if (!fav) return; // nothing to render into
                if (!Array.isArray(_games)) _games = [];
                fav.innerHTML = '';
            const list = _games.filter(g=>{
                if(!f) return true;
                if(f === 'snes') return g.console === 'smc' || g.console === 'sfc' || g.console === 'snes';
                return g.console === f;
            });
            if(!list.length) { fav.innerHTML = '<div class="muted">No games found</div>'; }
            list.forEach(g=>{
                const item = document.createElement('div'); 
                item.style.cssText = `
                    display: flex;
                    align-items: center;
                    padding: 12px;
                    border-bottom: 1px solid var(--border);
                    transition: background-color 0.2s;
                `;
                item.onmouseover = () => item.style.backgroundColor = 'var(--hover)';
                item.onmouseout = () => item.style.backgroundColor = 'transparent';
                
                const gameLink = document.createElement('a');
                gameLink.href = `player.php?rom=${encodeURIComponent(g.path)}&console=${encodeURIComponent(g.console)}&game=${encodeURIComponent(g.name)}`;
                gameLink.style.cssText = `
                    text-decoration: none;
                    color: inherit;
                    flex: 1;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                `;
                
                const icon = document.createElement('div');
                const img = document.createElement('img'); 
                // Use console-specific icon from game-icons folder
                const iconPath = getGameIconPath(g.console);
                img.src = g.icon || iconPath;
                img.alt = g.name; 
                img.loading='lazy';
                img.style.cssText = `
                    width: 32px;
                    height: 32px;
                    object-fit: contain;
                `;
                icon.appendChild(img);
                
                const label = document.createElement('div'); 
                label.innerHTML = `
                    <div style="font-weight: 500;">${g.name}</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">${g.console.toUpperCase()} • ${g.sizeFormatted || ''}</div>
                `;
                
                gameLink.appendChild(icon); 
                gameLink.appendChild(label);
                
                // Dynamic download/remove button based on download status
                if (g.downloaded) {
                    // Create container for "Downloaded" text and trash icon
                    const downloadedContainer = document.createElement('div');
                    downloadedContainer.style.cssText = `
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        margin-left: 8px;
                    `;
                    
                    // "Downloaded" text (not clickable)
                    const downloadedText = document.createElement('span');
                    downloadedText.innerHTML = 'Downloaded';
                    downloadedText.style.cssText = `
                        color: var(--muted);
                        font-size: 12px;
                    `;
                    
                    // Trash icon (clickable)
                    const trashBtn = document.createElement('button');
                    trashBtn.innerHTML = `
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7ZM9 8V17H11V8H9ZM13 8V17H15V8H13Z"/>
                        </svg>
                    `;
                    trashBtn.title = 'Remove download';
                    trashBtn.style.cssText = `
                        background: transparent;
                        color: var(--muted);
                        border: none;
                        cursor: pointer;
                        padding: 2px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;
                    trashBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        removeDownload(g.path, g.name);
                    };
                    
                    downloadedContainer.appendChild(downloadedText);
                    downloadedContainer.appendChild(trashBtn);
                    
                    item.appendChild(gameLink);
                    item.appendChild(downloadedContainer);
                } else {
                    // Download button for non-downloaded games
                    const actionBtn = document.createElement('button');
                    actionBtn.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 20H19V18H5V20ZM19 9H15V3H9V9H5L12 16L19 9Z"/>
                        </svg>
                    `;
                    actionBtn.title = 'Download to system';
                    actionBtn.style.cssText = `
                        background: transparent;
                        color: var(--accent);
                        border: none;
                        border-radius: 50%;
                        width: 32px;
                        height: 32px;
                        cursor: pointer;
                        margin-left: 8px;
                        flex-shrink: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;
                    actionBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        downloadGame(g.path, g.name, actionBtn);
                    };
                    
                    item.appendChild(gameLink);
                    item.appendChild(actionBtn);
                }
                fav.appendChild(item);
            });

            // Removed recent section - no 'recent' element exists in HTML
        }

        function buildFilters(){
            const buttonsRoot = document.getElementById('filter-buttons');
            if (!buttonsRoot) return;
            buttonsRoot.innerHTML = '';
            
            // Use systems from API response, or collect from games as fallback
            let consoles = new Set();
            if (_systems && _systems.length > 0) {
                _systems.forEach(s => consoles.add(s));
            } else {
                _games.forEach(g => { if (g && g.console) consoles.add(g.console); });
            }

            // helper to normalize console display and matching
            const consoleLabel = (c)=>{
                if (!c) return 'All';
                if (c === 'gba') return 'GBA';
                if (c === 'gb') return 'GB';
                if (c === 'gbc') return 'GBC';
                if (c === 'nds') return 'NDS';
                if (c === 'nes') return 'NES';
                if (c === 'snes' || c === 'smc' || c === 'sfc') return 'SNES';
                if (c === 'n64') return 'N64';
                if (c === 'genesis') return 'Genesis';
                if (c === 'pce') return 'PC Engine';
                return c.toUpperCase();
            };

            // add All button
            const addBtn = (consoleKey, label, active=false)=>{
                const b = document.createElement('button');
                b.className = 'filter-btn' + (active? ' active':'');
                b.setAttribute('data-console', consoleKey);
                b.textContent = label;
                b.addEventListener('click', ()=>{
                    document.querySelectorAll('#filter-buttons .filter-btn').forEach(x=>x.classList.remove('active'));
                    b.classList.add('active');
                    renderGames();
                });
                buttonsRoot.appendChild(b);
            };

            addBtn('', 'All', true);

            // Add buttons for available systems
            const consolesArr = Array.from(consoles);
            console.log('Building filters for consoles:', consolesArr);
            
            consolesArr.sort().forEach(console => {
                if (console) {
                    addBtn(console, consoleLabel(console));
                }
            });
        }

        // Load download status
        async function loadDownloadStatus() {
            try {
                const res = await fetch('<?php echo GamesPaths::getDownloadApiUrl(); ?>?action=status');
                const json = await res.json();
                
                if (json.success) {
                    const stats = json.stats;
                    const statusEl = document.getElementById('download-status');
                    statusEl.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <strong>Local Storage</strong>
                                <div style="color: var(--muted); font-size: 14px;">
                                    ${stats.files} games downloaded (${stats.sizeFormatted})
                                </div>
                            </div>
                            <button onclick="clearDownloads()" style="padding: 4px 8px; font-size: 12px; background: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer;">
                                Clear All
                            </button>
                        </div>
                        <div style="background: var(--bg-secondary); border-radius: 4px; height: 8px; overflow: hidden;">
                            <div style="background: var(--accent); height: 100%; width: ${stats.percentUsed}%; transition: width 0.3s;"></div>
                        </div>
                        <div style="color: var(--muted); font-size: 12px; margin-top: 4px;">
                            ${stats.sizeFormatted} / ${stats.maxSizeFormatted} used (${stats.percentUsed}%)
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Failed to load download status:', e);
                document.getElementById('download-status').innerHTML = '<div class="muted">Download info unavailable</div>';
            }
        }

        // Download a game
        async function downloadGame(romPath, gameName, button) {
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            button.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('romPath', romPath);
                
                const res = await fetch('<?php echo GamesPaths::getDownloadApiUrl(); ?>?action=download', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                
                if (json.success) {
                    button.innerHTML = '✅';
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 2000);
                    
                    // Refresh download status and games list
                    loadDownloadStatus();
                    loadGames(); // Reload games to get updated download status
                } else {
                    alert('Download failed: ' + (json.error || 'Unknown error'));
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (e) {
                console.error('Download failed:', e);
                alert('Download failed: Network error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Remove a download
        async function removeDownload(romPath, gameName) {
            if (!confirm(`Remove "${gameName}" from downloads?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('romPath', romPath);
                
                const res = await fetch('<?php echo GamesPaths::getDownloadApiUrl(); ?>?action=remove', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                
                if (json.success) {
                    loadDownloadStatus();
                    loadGames(); // Reload games to get updated download status
                } else {
                    alert('Remove failed: ' + (json.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Remove failed:', e);
                alert('Remove failed: Network error');
            }
        }

        // Clear all downloads
        async function clearDownloads() {
            if (!confirm('Remove all downloaded games? This cannot be undone.')) {
                return;
            }
            
            try {
                const res = await fetch('<?php echo GamesPaths::getDownloadApiUrl(); ?>?action=clear');
                const json = await res.json();
                
                if (json.success) {
                    loadDownloadStatus();
                    loadGames(); // Reload games to get updated download status
                    alert(json.message);
                } else {
                    alert('Clear failed: ' + (json.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Clear failed:', e);
                alert('Clear failed: Network error');
            }
        }

        // initial load
        loadGames();
    </script>
</body>
</html>
