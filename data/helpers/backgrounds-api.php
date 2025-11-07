<?php
/**
 * Backgrounds API for OfflineBox
 * Handles background image management
 */

// Only set headers if not in CLI mode
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

class BackgroundsAPI {
    
    private $backgroundsDir;
    private $configFile;
    private $allowedExtensions;
    
    public function __construct() {
        $this->backgroundsDir = __DIR__ . '/../backgrounds/';
        $this->configFile = __DIR__ . '/../json/background.json';
        $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        // Ensure directories exist
        if (!is_dir($this->backgroundsDir)) {
            mkdir($this->backgroundsDir, 0755, true);
        }
        if (!is_dir(dirname($this->configFile))) {
            mkdir(dirname($this->configFile), 0755, true);
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        switch ($method) {
            case 'GET':
                $this->getBackgrounds();
                break;
            case 'POST':
                if (isset($_POST['action']) && $_POST['action'] === 'upload') {
                    $this->uploadBackground();
                } else {
                    $this->saveBackground();
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function getBackgrounds() {
        $backgrounds = [];
        $currentBackground = $this->getCurrentBackground();
        
        // Scan backgrounds directory
        if (is_dir($this->backgroundsDir)) {
            $files = scandir($this->backgroundsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filePath = $this->backgroundsDir . $file;
                if (!is_file($filePath)) continue;
                
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->allowedExtensions)) continue;
                
                $backgrounds[] = [
                    'filename' => $file,
                    'name' => $this->getDisplayName($file),
                    'size' => filesize($filePath),
                    'modified' => date('M j, Y', filemtime($filePath))
                ];
            }
        }
        
        // Sort by name
        usort($backgrounds, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        $this->sendSuccess([
            'backgrounds' => $backgrounds,
            'current' => $currentBackground
        ]);
    }
    
    private function saveBackground() {
        $background = $_POST['background'] ?? '';
        
        if (empty($background)) {
            $this->sendError('Background not specified');
            return;
        }
        
        // Validate background exists (or is 'default')
        if ($background !== 'default') {
            $filePath = $this->backgroundsDir . $background;
            if (!file_exists($filePath)) {
                $this->sendError('Background file not found');
                return;
            }
        }
        
        // Save configuration
        $config = ['background' => $background];
        if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT))) {
            $this->sendSuccess(['message' => 'Background saved successfully']);
        } else {
            $this->sendError('Failed to save background configuration');
        }
    }
    
    private function uploadBackground() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->sendError('No file uploaded or upload error');
            return;
        }
        
        $file = $_FILES['file'];
        $originalName = $file['name'];
        $tmpPath = $file['tmp_name'];
        
        // Validate file type
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions)) {
            $this->sendError('File type not allowed. Supported: ' . implode(', ', $this->allowedExtensions));
            return;
        }
        
        // Validate file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->sendError('File size too large. Maximum 10MB allowed.');
            return;
        }
        
        // Generate unique filename
        $filename = $this->generateFilename($originalName);
        $targetPath = $this->backgroundsDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmpPath, $targetPath)) {
            // Set appropriate permissions
            chmod($targetPath, 0644);
            
            $this->sendSuccess([
                'message' => 'Background uploaded successfully',
                'filename' => $filename
            ]);
        } else {
            $this->sendError('Failed to save uploaded file');
        }
    }
    
    private function getCurrentBackground() {
        if (!file_exists($this->configFile)) {
            return 'default';
        }
        
        $config = json_decode(file_get_contents($this->configFile), true);
        return $config['background'] ?? 'default';
    }
    
    private function getDisplayName($filename) {
        // Remove extension and clean up name
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        return $name;
    }
    
    private function generateFilename($originalName) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Clean basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = preg_replace('/_+/', '_', $basename);
        $basename = trim($basename, '_');
        
        if (empty($basename)) {
            $basename = 'background_' . date('YmdHis');
        }
        
        $filename = $basename . '.' . $ext;
        
        // Ensure unique filename
        $counter = 1;
        while (file_exists($this->backgroundsDir . $filename)) {
            $filename = $basename . '_' . $counter . '.' . $ext;
            $counter++;
        }
        
        return $filename;
    }
    
    private function sendSuccess($data) {
        echo json_encode(['success' => true] + $data);
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
    }
}

// Handle the request
try {
    $api = new BackgroundsAPI();
    $api->handleRequest();
} catch (Exception $e) {
    error_log("BackgroundsAPI: Fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
