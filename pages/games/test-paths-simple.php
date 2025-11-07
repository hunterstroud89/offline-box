<?php
require_once __DIR__ . '/config/paths.php';
echo "Testing GamesPaths:\n";
echo "- Scan API: " . GamesPaths::getScanApiUrl() . "\n";
echo "- External ROMs: " . GamesPaths::getExternalRomsDir() . "\n";
echo "- Download API: " . GamesPaths::getDownloadApiUrl() . "\n";
?>
