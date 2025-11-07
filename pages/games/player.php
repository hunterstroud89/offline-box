<?php
require_once __DIR__ . '/config/paths.php';
// Clean player page for EmulatorJS
$rom = $_GET['rom'] ?? '';
$console = strtolower($_GET['console'] ?? '');
$game = $_GET['game'] ?? '';

if (!$rom || !$console) { http_response_code(400); die('missing'); }

// Update recents when game is played
require_once '../files/update-recents.php';
if ($rom && $game) {
    updateRecents($rom, $game);
}

// Determine public URL for the ROM
if (strpos($rom, '/') === 0) {
        // Absolute path on server -> use rom-serve
        $public = GamesPaths::getRomServeUrl() . '?file=' . urlencode($rom);
} else {
        // Relative path in files/roms
        $public = GamesPaths::getFilesRomsUrl() . ltrim($rom, '/');
}

// Map console -> emulator core slug (matches emulator.js/data/cores)
$coreMap = [
        'nes' => 'fceumm',
        'snes' => 'snes9x',
        'smc' => 'snes9x',
        'sfc' => 'snes9x',
        'gb' => 'gambatte',
        'gbc' => 'gambatte',
        'gba' => 'mgba',
        'nds' => 'melonds',
        'n64' => 'mupen64plus_next',
        'genesis' => 'genesis_plus_gx'
];

$core = $coreMap[$console] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($game ?: basename($rom)) ?></title>
        <link rel="stylesheet" href="/assets/style.css?v=2">
        <style>body{background:#0b0f12;color:var(--fg);font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;margin:0} #emulator-root{width:100%;height:100vh;display:flex;align-items:center;justify-content:center;background:#111}</style>
</head>
<body>
        <div id="emulator-root">Loading emulator…</div>

    <script>
        // Diagnostics to help with missing core issues
        console.log('Player init', { rom: <?= json_encode($rom) ?>, console: <?= json_encode($console) ?>, core: <?= json_encode($core) ?>, publicUrl: <?= json_encode($public) ?> });

        // Set EmulatorJS globals
        window.EJS_player = '#emulator-root';
        window.EJS_gameUrl = <?= json_encode($public) ?>;
        // If core is unknown, leave empty so loader will report a clear error
        window.EJS_core = <?= json_encode($core) ?>;
        window.EJS_pathtodata = '<?php echo GamesPaths::getEmulatorJsDataUrl(); ?>';
        window.EJS_startOnLoaded = true;

        // Small visual diagnostics if something goes wrong
        function showError(msg){
            const el = document.getElementById('emulator-root');
            el.innerHTML = '<div style="color:#ff6666;padding:20px;border-radius:8px;background:#220000;">' + msg + '</div>';
        }

        // Add loader script
        const s = document.createElement('script');
        s.src = '<?php echo GamesPaths::getEmulatorJsLoaderUrl(); ?>';
        s.onload = () => console.log('loader.js loaded');
        s.onerror = () => showError('Failed to load emulator loader.js — check <?php echo GamesPaths::getEmulatorJsLoaderUrl(); ?>');
        document.head.appendChild(s);

        // Additional runtime check: if core is empty, show helpful message
        if (!window.EJS_core) {
            showError('No emulator core selected for console <?= htmlspecialchars($console) ?> — contact admin or update core mapping.');
            console.error('No core for console', <?= json_encode($console) ?>);
        }
    </script>
</body>
</html>
