<?php
/**
 * Test script to verify centralized path configuration
 * Run this to check if all paths are working correctly
 */

require_once __DIR__ . '/paths.php';

echo "=== Games Path Configuration Test ===\n\n";

echo "Configuration (Pi Only):\n";
echo "- Web Root: " . GamesPaths::getWebRoot() . "\n\n";

echo "Data Directories:\n";
echo "- Data Dir: " . GamesPaths::getDataDir() . "\n";
echo "- Files Dir: " . GamesPaths::getFilesDir() . "\n";
echo "- External ROMs: " . GamesPaths::getExternalRomsDir() . "\n";
echo "- Downloaded Games: " . GamesPaths::getDownloadedGamesDir() . "\n";
echo "- EmulatorJS Data: " . GamesPaths::getEmulatorJsDataDir() . "\n\n";

echo "URL Paths:\n";
echo "- ROM Serve URL: " . GamesPaths::getRomServeUrl() . "\n";
echo "- Scan API URL: " . GamesPaths::getScanApiUrl() . "\n";
echo "- Download API URL: " . GamesPaths::getDownloadApiUrl() . "\n";
echo "- EmulatorJS Data URL: " . GamesPaths::getEmulatorJsDataUrl() . "\n";
echo "- EmulatorJS Loader URL: " . GamesPaths::getEmulatorJsLoaderUrl() . "\n\n";

echo "Game Icon URLs:\n";
echo "- GBA Icon: " . GamesPaths::getGameIconUrl('gba') . "\n";
echo "- NES Icon: " . GamesPaths::getGameIconUrl('nes') . "\n";
echo "- SNES Icon: " . GamesPaths::getGameIconUrl('snes') . "\n\n";

echo "File System Checks:\n";
$checks = [
    'Data Dir' => GamesPaths::getDataDir(),
    'EmulatorJS Loader' => GamesPaths::getEmulatorJsDataDir() . '/loader.js',
    'Download Index' => GamesPaths::getDownloadIndexFile()
];

foreach ($checks as $name => $path) {
    $exists = file_exists($path);
    $type = is_dir($path) ? "(directory)" : (is_file($path) ? "(file)" : "");
    echo "- $name: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . " $type\n";
    echo "  Path: $path\n";
}

echo "\nAllowed ROM Paths:\n";
foreach (GamesPaths::getAllowedRomPaths() as $i => $path) {
    $exists = is_dir($path);
    echo "- Path " . ($i + 1) . ": " . ($exists ? "✅" : "❌") . " $path\n";
}

echo "\n=== Test Complete ===\n";
?>
