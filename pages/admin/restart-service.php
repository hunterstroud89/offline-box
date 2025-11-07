<?php
header('Content-Type: application/json');

function restartKiwix() {
    // Kill existing kiwix-serve processes
    exec('sudo pkill -9 -f kiwix-serve 2>/dev/null');
    
    // Wait for processes to stop
    sleep(3);
    
    // Start kiwix with all ZIM files as hunter user
    $zimDir = '/media/hunter/OFFLINEBOX/wikipedia';
    exec("sudo -u hunter bash -c 'cd $zimDir && nohup kiwix-serve --port=8082 *.zim > kiwix.log 2>&1 &'");
    
    // Wait for startup
    sleep(4);
    
    // Check if the service started successfully
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8082');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $isRunning = ($httpCode >= 200 && $httpCode < 400);
    
    return $isRunning;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'restart-kiwix') {
        throw new Exception('Invalid action');
    }
    
    $success = restartKiwix();
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kiwix restarted successfully and is now serving all ZIM files'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Kiwix restart command executed but service may not be running'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
