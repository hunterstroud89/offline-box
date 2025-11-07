<?php
header('Content-Type: application/json');

// Notes API - handles CRUD operations for notes
$notes_file = __DIR__ . '/notes.json';

function loadNotes() {
    global $notes_file;
    if (!file_exists($notes_file)) {
        return [];
    }
    $json = file_get_contents($notes_file);
    return json_decode($json, true) ?: [];
}

function saveNotes($notes) {
    global $notes_file;
    $json = json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($notes_file, $json) !== false;
}

function generateId() {
    return uniqid(mt_rand(), true);
}

function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get a specific note
        if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
            $notes = loadNotes();
            $noteId = $_GET['id'];
            
            foreach ($notes as $note) {
                if ($note['id'] === $noteId) {
                    echo json_encode(['success' => true, 'note' => $note]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'error' => 'Note not found']);
        } else {
            // List all notes
            $notes = loadNotes();
            echo json_encode(['success' => true, 'notes' => $notes]);
        }
    } elseif ($method === 'POST') {
        // Handle create, update, delete operations
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['action'])) {
            throw new Exception('Invalid request data');
        }
        
        $action = $input['action'];
        $notes = loadNotes();
        
        switch ($action) {
            case 'create':
                $title = trim($input['title'] ?? '');
                $content = trim($input['content'] ?? '');
                
                if (empty($title) && empty($content)) {
                    throw new Exception('Title or content is required');
                }
                
                $newNote = [
                    'id' => generateId(),
                    'title' => $title,
                    'content' => $content,
                    'created' => getCurrentTimestamp(),
                    'updated' => getCurrentTimestamp()
                ];
                
                $notes[] = $newNote;
                
                if (!saveNotes($notes)) {
                    throw new Exception('Failed to save note');
                }
                
                echo json_encode(['success' => true, 'note' => $newNote]);
                break;
                
            case 'update':
                if (!isset($input['id'])) {
                    throw new Exception('Note ID is required');
                }
                
                $noteId = $input['id'];
                $title = trim($input['title'] ?? '');
                $content = trim($input['content'] ?? '');
                
                if (empty($title) && empty($content)) {
                    throw new Exception('Title or content is required');
                }
                
                $found = false;
                for ($i = 0; $i < count($notes); $i++) {
                    if ($notes[$i]['id'] === $noteId) {
                        $notes[$i]['title'] = $title;
                        $notes[$i]['content'] = $content;
                        $notes[$i]['updated'] = getCurrentTimestamp();
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    throw new Exception('Note not found');
                }
                
                if (!saveNotes($notes)) {
                    throw new Exception('Failed to update note');
                }
                
                echo json_encode(['success' => true]);
                break;
                
            case 'delete':
                if (!isset($input['id'])) {
                    throw new Exception('Note ID is required');
                }
                
                $noteId = $input['id'];
                $originalCount = count($notes);
                $notes = array_filter($notes, function($note) use ($noteId) {
                    return $note['id'] !== $noteId;
                });
                
                if (count($notes) === $originalCount) {
                    throw new Exception('Note not found');
                }
                
                // Reindex array
                $notes = array_values($notes);
                
                if (!saveNotes($notes)) {
                    throw new Exception('Failed to delete note');
                }
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                throw new Exception('Unknown action: ' . $action);
        }
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
