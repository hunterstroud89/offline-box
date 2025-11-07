<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'update_apps':
            $apps = $input['apps'] ?? [];
            
            // Validate apps data
            if (!is_array($apps)) {
                throw new Exception('Apps data must be an array');
            }
            
            // Save to apps.json
            $appsFile = '../../data/json/apps.json';
            if (!file_put_contents($appsFile, json_encode($apps, JSON_PRETTY_PRINT))) {
                throw new Exception('Failed to write apps file');
            }
            
            echo json_encode(['success' => true, 'message' => 'Apps updated successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
