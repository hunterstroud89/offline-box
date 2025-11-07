<?php
header('Content-Type: application/json');

// Check if Ollama is running
function check_ollama_status() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:11434/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error || $http_code !== 200) {
        return [
            'status' => 'offline',
            'error' => $curl_error ?: 'HTTP ' . $http_code,
            'models' => []
        ];
    }
    
    $data = json_decode($response, true);
    return [
        'status' => 'online',
        'models' => $data['models'] ?? [],
        'model_count' => count($data['models'] ?? [])
    ];
}

$status = check_ollama_status();
echo json_encode($status);
?>
