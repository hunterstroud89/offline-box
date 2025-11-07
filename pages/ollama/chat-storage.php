<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$chatsDir = __DIR__ . '/chats/';

// Ensure chats directory exists
if (!is_dir($chatsDir)) {
    mkdir($chatsDir, 0755, true);
}

function generateChatId() {
    return 'chat_' . date('Y-m-d_H-i-s') . '_' . substr(md5(uniqid(rand(), true)), 0, 8);
}

function getChatTitle($messages) {
    if (empty($messages)) {
        return 'New Chat';
    }
    
    $firstUserMessage = '';
    foreach ($messages as $message) {
        if ($message['role'] === 'user') {
            $firstUserMessage = $message['content'];
            break;
        }
    }
    
    if (empty($firstUserMessage)) {
        return 'New Chat';
    }
    
    // Create a title from the first user message (max 50 characters)
    $title = trim($firstUserMessage);
    if (strlen($title) > 50) {
        $title = substr($title, 0, 47) . '...';
    }
    
    return $title;
}

switch ($method) {
    case 'GET':
        // List all chats
        $chats = [];
        $files = glob($chatsDir . '*.json');
        
        foreach ($files as $file) {
            $chatData = json_decode(file_get_contents($file), true);
            if ($chatData) {
                $chats[] = [
                    'id' => $chatData['id'],
                    'title' => $chatData['title'],
                    'created_at' => $chatData['created_at'],
                    'updated_at' => $chatData['updated_at'],
                    'message_count' => count($chatData['messages'])
                ];
            }
        }
        
        // Sort by updated_at (newest first)
        usort($chats, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        
        echo json_encode(['chats' => $chats]);
        break;
        
    case 'POST':
        // Create new chat or save existing chat
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['messages'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }
        
        $chatId = $input['id'] ?? generateChatId();
        $messages = $input['messages'];
        $title = $input['title'] ?? getChatTitle($messages);
        
        $chatData = [
            'id' => $chatId,
            'title' => $title,
            'messages' => $messages,
            'created_at' => $input['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $filename = $chatsDir . $chatId . '.json';
        if (file_put_contents($filename, json_encode($chatData, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'chat' => $chatData]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save chat']);
        }
        break;
        
    case 'PUT':
        // Load specific chat
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID required']);
            exit;
        }
        
        $chatId = $input['id'];
        $filename = $chatsDir . $chatId . '.json';
        
        if (file_exists($filename)) {
            $chatData = json_decode(file_get_contents($filename), true);
            echo json_encode(['chat' => $chatData]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Chat not found']);
        }
        break;
        
    case 'DELETE':
        // Delete specific chat
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID required']);
            exit;
        }
        
        $chatId = $input['id'];
        $filename = $chatsDir . $chatId . '.json';
        
        if (file_exists($filename)) {
            if (unlink($filename)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete chat']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Chat not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
