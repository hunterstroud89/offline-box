<?php
header('Content-Type: application/json');

function checkService($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Consider any 2xx or 3xx response as running
    return ($httpCode >= 200 && $httpCode < 400);
}

try {
    $servicesFile = '../../data/json/services.json';
    if (!file_exists($servicesFile)) {
        throw new Exception('Services file not found');
    }
    
    $services = json_decode(file_get_contents($servicesFile), true);
    if (!$services) {
        throw new Exception('Invalid services file');
    }
    
    foreach ($services as &$service) {
        $isRunning = checkService($service['url']);
        $service['status'] = $isRunning ? 'running' : 'stopped';
    }
    
    echo json_encode(['success' => true, 'services' => $services]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
