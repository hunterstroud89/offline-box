<?php
require_once 'download-manager.php';
header('Content-Type: application/json');

$downloadManager = new RomDownloadManager();

$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'status':
        $stats = $downloadManager->getStats();
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        break;
        
    case 'downloaded':
        $downloaded = $downloadManager->getDownloadedGames();
        echo json_encode([
            'success' => true,
            'downloaded' => $downloaded
        ]);
        break;
        
    case 'download':
        $romPath = $_POST['romPath'] ?? $_GET['romPath'] ?? '';
        if (!$romPath) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ROM path required']);
            break;
        }
        
        $result = $downloadManager->downloadRom($romPath);
        echo json_encode($result);
        break;
        
    case 'remove':
        $romPath = $_POST['romPath'] ?? $_GET['romPath'] ?? '';
        if (!$romPath) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ROM path required']);
            break;
        }
        
        $result = $downloadManager->removeDownload($romPath);
        echo json_encode($result);
        break;
        
    case 'clear':
        $cleared = $downloadManager->clearAllDownloads();
        echo json_encode([
            'success' => true,
            'message' => "Cleared $cleared downloaded games",
            'cleared' => $cleared
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}
?>
