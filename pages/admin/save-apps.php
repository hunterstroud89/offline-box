<?php
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the JSON data from the request body
$json = file_get_contents('php://input');
$apps = json_decode($json, true);

// Validate the data
if (!is_array($apps)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data format']);
    exit;
}

// Normalize each app and ensure minimal required fields
foreach ($apps as &$app) {
    // Ensure id exists (fallback to sanitized label)
    if (!isset($app['id']) || !$app['id']) {
        if (isset($app['label']) && $app['label']) {
            $app['id'] = preg_replace('/[^a-z0-9]/', '', strtolower($app['label']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required label']);
            exit;
        }
    }
    // Ensure label exists
    if (!isset($app['label']) || !$app['label']) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required label']);
        exit;
    }
    // Provide sensible defaults for optional fields (preserve existing values)
    if (!isset($app['icon'])) $app['icon'] = '';
    if (!isset($app['url'])) $app['url'] = '';
    // Preserve all other properties like 'folder', 'visible', 'hardcoded_url', etc.
    // No need to explicitly set them - just ensure they're preserved in the array
}
unset($app);

// Save to the apps.json file
$appsFile = __DIR__ . '/../../data/json/apps.json';
$result = file_put_contents($appsFile, json_encode($apps, JSON_PRETTY_PRINT));

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Apps saved successfully']);
?>
