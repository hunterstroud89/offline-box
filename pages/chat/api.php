<?php
header('Content-Type: application/json; charset=utf-8');
$mount = '/mnt/offlinebox/chats';
$base = __DIR__ . '/chats';

// Ensure app-local base exists
if (!is_dir($base)) {
    $created = @mkdir($base, 0775, true);
    if (!$created) {
        echo json_encode(['success'=>false,'error'=>'base_missing','message'=>'Chat storage path not available: ' . $base]);
        http_response_code(500);
        exit;
    }
}

// If mount has existing messages, attempt a one-time migration into the app folder
if (is_dir($mount) && is_readable($mount)){
    foreach (glob($mount . '/*.json') as $f){
        $dest = $base . '/' . basename($f);
        if (!file_exists($dest)) {
            @rename($f, $dest);
        }
    }
}

if (!is_writable($base)){
    echo json_encode(['success'=>false,'error'=>'base_unwritable','message'=>'Chat storage path is not writable: ' . $base]);
    http_response_code(500);
    exit;
}

$room = isset($_REQUEST['room']) ? preg_replace('/[^a-zA-Z0-9_\-]/','', $_REQUEST['room']) : 'default';
$path = $base . '/' . $room . '.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET'){
    if (isset($_GET['clear'])){
        if (file_exists($path)) @unlink($path);
        echo json_encode(['success'=>true,'messages'=>[]]);
        exit;
    }
    $messages = [];
    if (file_exists($path)){
        $txt = file_get_contents($path);
        $messages = json_decode($txt, true) ?: [];
    }
    echo json_encode(['success'=>true,'messages'=>$messages]);
    exit;
}

// POST - add message
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$text = isset($input['text']) ? trim($input['text']) : '';
$from = isset($input['from']) ? $input['from'] : 'anon';
if ($text === ''){ echo json_encode(['success'=>false,'error'=>'empty']); exit; }

$messages = [];
if (file_exists($path)){
    $messages = json_decode(file_get_contents($path), true) ?: [];
}
$messages[] = ['when'=>date('Y-m-d H:i:s'),'from'=>$from,'text'=>$text];
$written = @file_put_contents($path, json_encode($messages, JSON_PRETTY_PRINT));
if ($written === false){
    echo json_encode(['success'=>false,'error'=>'write_failed','message'=>'Failed to write messages to ' . $path]);
    http_response_code(500);
    exit;
}

echo json_encode(['success'=>true,'messages'=>$messages]);
