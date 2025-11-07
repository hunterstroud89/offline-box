<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$model = $input['model'] ?? 'llama3.2:1b';
$messages = $input['messages'] ?? [];
$stream = $input['stream'] ?? false;

if (empty($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'Messages array is required']);
    exit;
}

// Prepare the request to Ollama
$ollama_url = 'http://127.0.0.1:11434/api/chat';
$ollama_data = [
    'model' => $model,
    'messages' => $messages,
    'stream' => $stream,
    'options' => [
        'temperature' => 0.7,
        'top_p' => 0.9,
        'num_ctx' => 2048
    ]
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ollama_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollama_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes for generation

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($curl_error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to connect to Ollama: ' . $curl_error,
        'details' => 'Make sure Ollama is running on port 11434'
    ]);
    exit;
}

// Handle HTTP errors
if ($http_code !== 200) {
    http_response_code(500); // Always return 500 for client to handle consistently
    $error_data = json_decode($response, true);
    
    // Get the full error message
    $error_message = 'Ollama API error';
    $error_details = 'Unknown error';
    
    if ($error_data && isset($error_data['error'])) {
        $error_details = $error_data['error'];
        
        // Handle specific common errors with user-friendly messages
        if (strpos($error_details, 'model requires more system memory') !== false) {
            $error_message = 'Insufficient Memory';
            $error_details = 'The selected model requires more system memory than is available. Try using a smaller model or freeing up system memory.';
        } elseif (strpos($error_details, 'model not found') !== false) {
            $error_message = 'Model Not Found';
            $error_details = 'The requested model is not available. Please select a different model or download the required model.';
        }
    }
    
    echo json_encode([
        'error' => $error_message,
        'details' => $error_details,
        'http_code' => $http_code
    ]);
    exit;
}

// Parse and return the response
$ollama_response = json_decode($response, true);

if ($ollama_response === null) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from Ollama',
        'raw_response' => substr($response, 0, 500) // First 500 chars for debugging
    ]);
    exit;
}

// Return the successful response
echo json_encode($ollama_response);
?>
