<?php
require_once __DIR__ . '/config/paths.php';
/**
 * Clean EmulatorJS Game Player
 * Pure offline gaming - no internet dependencies
 */

require_once __DIR__ . '/config/paths.php';

// Get parameters
$rom = $_GET['rom'] ?? '';
$console = $_GET['console'] ?? '';
$game = $_GET['game'] ?? '';

if (empty($rom) || empty($console)) {
    http_response_code(400);
    die('Missing required parameters');
}

// Security: validate ROM path
$romsDir = GamesPaths::getFilesDir();

// Allow absolute filesystem paths (external) or relative paths under files/
if (preg_match('#^/#', $rom)) {
    $fullRomPath = realpath($rom);
    $isExternal = true;
    // Use centralized path configuration for fallback paths
    if (!$fullRomPath || !is_file($fullRomPath)) {
        // Try alternative paths from config
        foreach (GamesPaths::getAllowedRomPaths() as $allowedPath) {
            $testPath = $allowedPath . '/' . basename($rom);
            if (is_file($testPath)) {
                $fullRomPath = realpath($testPath);
                break;
            }
        }
    }
    if (!$fullRomPath || !is_file($fullRomPath)) {
        http_response_code(404);
        die('ROM file not found');
    }
} else {
    $fullRomPath = realpath($romsDir . '/' . $rom);
    $isExternal = false;
    if (!$fullRomPath || strpos($fullRomPath, realpath($romsDir)) !== 0) {
        http_response_code(403);
        die('Access denied');
    }
    if (!file_exists($fullRomPath)) {
        http_response_code(404);
        die('ROM file not found');
    }
}

// Console to core mapping
$coreMap = [
    'nes' => 'fceumm',
    'snes' => 'snes9x',
    'gb' => 'gambatte', 
    'gba' => 'mgba',
    'genesis' => 'genesis_plus_gx'
];

$core = $coreMap[$console] ?? 'unknown';

// If console was not provided or is unknown, try to guess from file extension
if ($core === 'unknown') {
    $ext = strtolower(pathinfo($fullRomPath, PATHINFO_EXTENSION));
    $extToConsole = [
        'nes' => 'nes',
        'smc' => 'snes',
        'sfc' => 'snes',
        'gb' => 'gb',
        'gbc' => 'gbc',
        'gba' => 'gba',
        'n64' => 'n64',
        'z64' => 'n64',
        'md' => 'genesis',
        'gen' => 'genesis',
        'smd' => 'genesis',
        'zip' => 'arcade'
    ];

    if (isset($extToConsole[$ext])) {
        $console = $extToConsole[$ext];
        $core = $coreMap[$console] ?? 'unknown';
    }
}

// If no game display name provided, use filename
if (empty($game)) {
    $game = pathinfo($fullRomPath, PATHINFO_FILENAME);
}

// Check emulator loader availability (EmulatorJS is packaged under emulator.js/data)
$loaderRel = GamesPaths::getEmulatorJsLoaderUrl();
$loaderFull = GamesPaths::getEmulatorJsDataDir() . '/loader.js';
$loaderExists = file_exists($loaderFull);

// Public URL to the ROM file for download
$romPublicUrl = $isExternal ? (GamesPaths::getRomServeUrl() . '?file=' . urlencode($fullRomPath)) : ('../../files/' . ltrim($rom, '/'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game) ?> - Retro Gaming</title>
    <link rel="stylesheet" href="../../frontend/themes/modern/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .game-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--panel, #141414);
            padding: 10px 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            border-bottom: 1px solid var(--line, rgba(255,255,255,0.04));
        }
        
        .game-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e8e8e8;
        }
        
        .console-badge {
            background: var(--line, rgba(255,255,255,0.06));
            color: var(--fg, #e8e8e8);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.9;
        }
        
    /* use project .btn styles where available; provide small adjustments for header */
    .game-controls { display:flex; gap:10px; align-items:center; position: absolute; right: 14px; }
    .game-controls .btn { padding: 6px 10px; font-size: 13px; border-radius: 8px; }
    .back-left { position: absolute; left: 14px; top: 8px; z-index: 1001; }
    .back-left .btn, .back-left { text-decoration: none; }
        
        .game-container {
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        
    .loading-screen {
            text-align: center;
            padding: 40px;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .loading-details {
            font-size: 14px;
            opacity: 0.7;
            margin-top: 10px;
        }
        
        /* compact error badge (doesn't take over layout) */
        .error-screen {
            position: fixed;
            left: 14px;
            bottom: 18px;
            width: 260px;
            text-align: left;
            padding: 16px;
            color: #ff6b6b;
            background: rgba(0,0,0,0.7);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 8px;
            display: none;
            z-index: 1100;
        }
        
        .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        #game-canvas {
            max-width: 100%;
            max-height: 100%;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            display: none;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .game-header {
                padding: 10px 15px;
            }
            
            .game-title {
                font-size: 16px;
            }
            
            .control-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .game-container {
                top: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="game-header">
        <a class="btn back-left" href="javascript:void(0)" onclick="goBack()">← Back</a>
        <div class="game-title">
            🎮 <?= htmlspecialchars($game) ?>
            <span class="console-badge"><?= strtoupper($console) ?></span>
        </div>
        <div class="game-controls">
            <button class="btn" onclick="toggleFullscreen()">📺 Fullscreen</button>
            <button class="btn" onclick="saveState()">💾 Save</button>
            <button class="btn" onclick="loadState()">📁 Load</button>
            <button id="debug-toggle" class="btn" onclick="toggleDiagnostics()" title="Show diagnostics">⚙︎</button>
        </div>
    </div>
    
    <div class="game-container">
        <div id="loading" class="loading-screen">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading <?= htmlspecialchars($game) ?></div>
            <div class="loading-details">
                Console: <?= strtoupper($console) ?> | Core: <?= $core ?>
            </div>
        </div>
        
        <div id="error" class="error-screen">
            <div class="error-icon">⚠️</div>
            <h3>Failed to Load Game</h3>
            <p>Could not initialize the emulator.</p>
            <div class="loading-details">
                Check if ROM file is compatible with <?= strtoupper($console) ?>
            </div>
        </div>
        
    <div id="diagnostics" style="display: none; color: #ddd; font-size: 13px; padding: 16px; max-width: 900px; margin: 20px auto; text-align: left;">
            <h4 style="margin-top:0;">Diagnostics</h4>
            <ul>
                <li>Resolved ROM path: <code><?= htmlspecialchars($fullRomPath) ?></code></li>
                <li>Public ROM URL: <a href="<?= htmlspecialchars($romPublicUrl) ?>" target="_blank" style="color: #9bd">Download</a></li>
                <li>Detected console param: <strong><?= htmlspecialchars($console) ?></strong></li>
                <li>Detected emulator core: <strong><?= htmlspecialchars($core) ?></strong></li>
                <li>Emulator loader file present: <strong><?= $loaderExists ? 'yes' : 'no' ?></strong></li>
            </ul>
            <?php if ($core === 'unknown'): ?>
                <div style="color: #ffcccb;">No emulator core could be selected for this ROM. Try using a different ROM or place it under the correct console folder (e.g., <code>/files/roms/gba/</code>).</div>
            <?php endif; ?>
            <?php if (!$loaderExists): ?>
                <div style="color: #ffcccb; margin-top:8px;">Emulator files are missing from <code>backend/games/emulator/</code>. Ensure the EmulatorJS distribution is present.</div>
            <?php endif; ?>
        </div>
        
        <div id="game-display"></div>
    </div>
    
    <script>
        // Game configuration
        const gameConfig = {
            rom: <?= json_encode($romPublicUrl) ?>,
            core: "<?= $core ?>",
            name: "<?= htmlspecialchars($game) ?>",
            console: "<?= $console ?>"
        };
        
        let gameInstance = null;
    let _errorTimer = null; // retained for compatibility but not used for auto-show
    const ERROR_DELAY = 60000; // not used for automatic error showing; user requested indefinite wait
    let failureCount = 0;
    const FAILURE_THRESHOLD = 3; // require this many consecutive failures before showing diagnostics
    let diagnosticsVisible = false;
        
        // Initialize EmulatorJS
        function initializeEmulator() {
            console.log('🎮 Starting game:', gameConfig);
            
            // Set EmulatorJS configuration
            window.EJS_player = '#game-display';
            window.EJS_gameUrl = gameConfig.rom;
            window.EJS_core = gameConfig.core;
            window.EJS_gameName = gameConfig.name;
            // Point to the packaged EmulatorJS data folder
            window.EJS_pathtodata = '<?php echo GamesPaths::getEmulatorJsDataUrl(); ?>';
            window.EJS_startOnLoaded = true;
            window.EJS_fullscreenOnLoaded = false;
            
            // Disable online features
            window.EJS_disableDatabases = true;
            window.EJS_threads = false;
            
            // Callbacks
            window.EJS_onGameStart = function() {
                console.log('🎯 Game started successfully!');
                // Cancel any pending error UI display
                if (_errorTimer) { clearTimeout(_errorTimer); _errorTimer = null; }
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'none';
                document.getElementById('game-display').style.display = 'block';
                // Hide diagnostics when the emulator starts
                const diag = document.getElementById('diagnostics');
                if (diag) diag.style.display = 'none';
            };
            
            // When the loader reports an internal load error, do not show the
            // error UI automatically. The emulator can still initialize after
            // transient internal errors on some platforms. Log and count
            // failures for diagnostics, but do not take over the UI.
            window.EJS_onLoadError = function(error) {
                console.warn('❌ Game load error reported by loader (logged only):', error);
                failureCount++;
                // diagnostics can be revealed manually with the Debug ⚙︎ button
            };
            
            // Load EmulatorJS
            const script = document.createElement('script');
            script.src = '<?php echo GamesPaths::getEmulatorJsLoaderUrl(); ?>';
            script.onload = () => {
                console.log('✅ EmulatorJS loaded');
                // Poll for the emulator to become available. Some loader builds initialize
                // asynchronously and can take multiple seconds (5-10s) on slow machines.
                // Wait up to ~12s before treating as failure.
                const interval = 300;
                const maxAttempts = Math.ceil(12000 / interval);
                let attempts = 0;
                const poll = setInterval(() => {
                    attempts++;
                        // The EmulatorJS loader typically assigns an instance object to
                        // window.EJS_emulator (not a function). Accept both styles:
                        // - modern: window.EJS_emulator is an object instance
                        // - older builds: window.EJS_emulator may be a factory/function
                        if (typeof window.EJS_emulator !== 'undefined' && window.EJS_emulator !== null) {
                            clearInterval(poll);
                            if (_errorTimer) { clearTimeout(_errorTimer); _errorTimer = null; }
                            try {
                                if (typeof window.EJS_emulator === 'function') {
                                    // older factory-style loader
                                    gameInstance = window.EJS_emulator();
                                } else {
                                    // modern loader returns an instance object
                                    gameInstance = window.EJS_emulator;
                                }
                                console.log('🔧 gameInstance assigned:', gameInstance);
                                // Update the diagnostics panel with a short summary
                                try { if (typeof updateDiagnosticsPanel === 'function') updateDiagnosticsPanel(); } catch(e){}
                            } catch (e) {
                                console.error('❌ Error creating emulator instance:', e);
                                showError();
                            }
                            return;
                        }
                    if (attempts >= maxAttempts) {
                        clearInterval(poll);
                        // If the emulator never appeared, show an error (debounced)
                        if (_errorTimer) clearTimeout(_errorTimer);
                        _errorTimer = setTimeout(() => {
                            _errorTimer = null;
                            console.error('❌ Emulator did not initialize after loader');
                            showError();
                        }, ERROR_DELAY);
                    }
                }, interval);
            };
            script.onerror = () => {
                // If the script fails to load (e.g., file missing), log but do not
                // automatically show the diagnostics. The user asked to let the
                // loading page take as long as it needs; we preserve manual
                // diagnostics via the Debug button.
                console.warn('❌ Failed to load EmulatorJS script (logged only)');
                failureCount++;
            };
            document.head.appendChild(script);

            // Watch for the emulator actually rendering into the page. Some builds
            // create canvas/elements without firing the game-start callback; detect
            // that and cancel any pending error display. Give the renderer extra time
            // (up to ~15s) before concluding it's failed.
            (function startRenderWatcher(){
                const detectInterval = 250;
                const maxDetect = 15000; // ms
                let elapsed = 0;
                const watcher = setInterval(() => {
                    elapsed += detectInterval;
                    const container = document.getElementById('game-display');
                    if (container) {
                        // look for likely emulator render targets
                        if (container.querySelector('canvas, iframe, video, img') || container.children.length > 0) {
                            console.log('🔍 Detected emulator content in DOM, cancelling error UI');
                            if (_errorTimer) { clearTimeout(_errorTimer); _errorTimer = null; }
                            document.getElementById('loading').style.display = 'none';
                            document.getElementById('error').style.display = 'none';
                            container.style.display = 'block';
                            const diag = document.getElementById('diagnostics'); if (diag) diag.style.display = 'none';
                            failureCount = 0; // reset aggregated failures on success
                            clearInterval(watcher);
                            return;
                        }
                    }
                    if (elapsed >= maxDetect) {
                        clearInterval(watcher);
                    }
                }, detectInterval);
            })();
        }
        
        function showError() {
            // Do nothing by default. Error UI must be shown explicitly via
            // the Debug (⚙︎) button.
            console.log('showError called (noop)');
        }

        function toggleDiagnostics() {
            diagnosticsVisible = !diagnosticsVisible;
            const diag = document.getElementById('diagnostics');
            if (!diag) return;
            diag.style.display = diagnosticsVisible ? 'block' : 'none';
            if (diagnosticsVisible && typeof updateDiagnosticsPanel === 'function') updateDiagnosticsPanel();
            // when enabling diagnostics, also reveal the compact error panel
            // so the developer can see any logged failures; hide it when
            // diagnostics are hidden.
            const err = document.getElementById('error');
            if (err) err.style.display = diagnosticsVisible ? 'block' : 'none';
        }

        function updateDiagnosticsPanel() {
            const diag = document.getElementById('diagnostics');
            if (!diag) return;
            let info = '';
            try {
                if (!gameInstance) {
                    info = '<div style="color:#ffd">No emulator instance created yet.</div>';
                } else {
                    const cname = gameInstance && gameInstance.constructor ? (gameInstance.constructor.name || 'anonymous') : typeof gameInstance;
                    const hasStart = !!(gameInstance && (gameInstance.start || (gameInstance.on && typeof gameInstance.on === 'function')));
                    const hasSave = !!(gameInstance && (gameInstance.saveState || gameInstance.save));
                    info = `<div style="color:#9bd">Emulator instance detected: <strong>${cname}</strong></div>` +
                           `<div style="color:#ddd; margin-top:8px">start-like API: ${hasStart ? 'yes' : 'no'} | save-like API: ${hasSave ? 'yes' : 'no'}</div>`;
                }
            } catch(e) {
                info = '<div style="color:#ffcccb">Diagnostics failed: ' + String(e) + '</div>';
            }
            // insert or replace a diagnostics runtime area
            let runtime = document.getElementById('diag-runtime');
            if (!runtime) {
                runtime = document.createElement('div');
                runtime.id = 'diag-runtime';
                runtime.style.marginTop = '12px';
                runtime.style.fontFamily = 'monospace';
                diag.appendChild(runtime);
            }
            runtime.innerHTML = info;
        }
        
        // Control functions
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
        
        function saveState() {
            if (gameInstance && gameInstance.saveState) {
                gameInstance.saveState();
                showNotification('💾 Game saved!');
            }
        }
        
        function loadState() {
            if (gameInstance && gameInstance.loadState) {
                gameInstance.loadState();
                showNotification('📁 Game loaded!');
            }
        }
        
        function goBack() {
            if (confirm('Exit game and return to game library?')) {
                window.location.href = '../../frontend/pages/games.php';
            }
        }
        
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 90px;
                right: 20px;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 14px;
                z-index: 1001;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') goBack();
            if (e.key === 'F11') { e.preventDefault(); toggleFullscreen(); }
        });
        
        // Before starting the emulator, perform a small fetch test to ensure the ROM URL is reachable
        async function preflightRomCheck() {
            const diagEl = document.getElementById('diagnostics');
            try {
                console.log('🔎 Preflight: checking ROM URL', gameConfig.rom);
                // Try a small ranged request so we don't download the whole ROM
                const res = await fetch(gameConfig.rom, { headers: { Range: 'bytes=0-1023' } });
                if (!res.ok && res.status !== 206) {
                    console.error('ROM preflight failed', res.status, res.statusText);
                    if (diagEl) diagEl.style.display = 'block';
                    if (diagEl) diagEl.innerHTML += `<div style="color:#ffcccb;">ROM preflight failed: ${res.status} ${res.statusText}</div>`;
                    return;
                }
                const ct = res.headers.get('Content-Type') || 'unknown';
                const cl = res.headers.get('Content-Length') || 'unknown';
                console.log('ROM preflight success', { ct, cl });
                if (diagEl) diagEl.style.display = 'none';
                // proceed to initialize the emulator
                initializeEmulator();
            } catch (err) {
                console.error('ROM preflight exception', err);
                if (diagEl) { diagEl.style.display = 'block'; diagEl.innerHTML += `<div style="color:#ffcccb;">ROM fetch error: ${err}</div>`; }
            }
        }

        // Start with preflight check
        preflightRomCheck();

        // If the emulator hasn't initialized after a short wait, open the
        // diagnostics panel automatically and populate runtime info to help
        // debugging the blank screen. This saves the developer from having to
        // copy console logs in many cases.
        setTimeout(async () => {
            if (gameInstance) return; // already initialized
            try {
                // Reveal the diagnostics panel
                diagnosticsVisible = true;
                const diag = document.getElementById('diagnostics');
                if (diag) diag.style.display = 'block';
                // Add helpful runtime checks
                let infoHtml = '<h4>Runtime auto-diagnostics</h4>';
                infoHtml += `<div>Emulator loader present: <strong>${typeof window.EJS_emulator !== 'undefined'}</strong></div>`;
                infoHtml += `<div>Player target selector: <strong>${typeof window.EJS_player !== 'undefined'}</strong></div>`;
                infoHtml += `<div>Pathtodata: <strong>${String(window.EJS_pathtodata || window.EJS_pathtodata === undefined ? window.EJS_pathtodata : 'unset')}</strong></div>`;
                // Try a HEAD request to the ROM URL to show status and headers
                try {
                    const head = await fetch(gameConfig.rom, { method: 'GET', headers: { Range: 'bytes=0-15' } });
                    infoHtml += `<div style="margin-top:8px">ROM request status: <strong>${head.status} ${head.statusText}</strong></div>`;
                    const ct = head.headers.get('Content-Type') || 'unknown';
                    const ar = head.headers.get('Accept-Ranges') || 'none';
                    const cl = head.headers.get('Content-Length') || 'unknown';
                    infoHtml += `<div style="font-family:monospace; color:#ddd; margin-top:6px">Content-Type: ${ct}<br>Accept-Ranges: ${ar}<br>Content-Length: ${cl}</div>`;
                } catch (e) {
                    infoHtml += `<div style="color:#ffcccb; margin-top:8px">ROM fetch error: ${String(e)}</div>`;
                }

                // Insert or append to diagnostics
                const runtime = document.getElementById('diag-runtime');
                if (runtime) {
                    runtime.innerHTML = (runtime.innerHTML || '') + infoHtml;
                } else if (diag) {
                    const wrap = document.createElement('div');
                    wrap.innerHTML = infoHtml;
                    wrap.style.marginTop = '12px';
                    diag.appendChild(wrap);
                }
            } catch (e) {
                console.error('Auto-diagnostics failed', e);
            }
        }, 8000);
    </script>
    
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</body>
</html>
