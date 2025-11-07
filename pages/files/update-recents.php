<?php
/**
 * Helper to update recents.json when files are accessed
 */

function updateRecents(string $filePath, string $fileName = ''): bool {
    $recentsFile = __DIR__ . '/../../data/json/recents.json';
    
    // Get file info if not provided
    if (empty($fileName)) {
        $fileName = basename($filePath);
    }
    
    // Load existing recents
    $recents = [];
    if (file_exists($recentsFile)) {
        $recentsJson = file_get_contents($recentsFile);
        if ($recentsJson !== false) {
            $recents = json_decode($recentsJson, true) ?: [];
        }
    }
    
    // Create new recent entry
    $newRecent = [
        'name' => $fileName,
        'path' => $filePath,
        'modified' => date('Y-m-d')
    ];
    
    // Remove existing entry for this file if it exists
    $recents = array_filter($recents, function($item) use ($filePath) {
        return isset($item['path']) && $item['path'] !== $filePath;
    });
    
    // Add new entry at the beginning
    array_unshift($recents, $newRecent);
    
    // Keep only the most recent 20 items
    $recents = array_slice($recents, 0, 20);
    
    // Save back to file
    $json = json_encode($recents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    
    return file_put_contents($recentsFile, $json) !== false;
}

// If called directly with parameters, update recents
if (basename($_SERVER['PHP_SELF']) === 'update-recents.php') {
    if (isset($_GET['path']) && !empty($_GET['path'])) {
        $path = $_GET['path'];
        $name = $_GET['name'] ?? basename($path);
        $size = isset($_GET['size']) ? (int)$_GET['size'] : 0;
        
        if (updateRecents($path, $name, $size)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update recents']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST requests with JSON data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['path'])) {
            $path = $input['path'];
            $name = $input['name'] ?? basename($path);
            $size = $input['size'] ?? 0;
            
            if (updateRecents($path, $name, $size)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update recents']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing path parameter']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No path provided']);
    }
}
?>
