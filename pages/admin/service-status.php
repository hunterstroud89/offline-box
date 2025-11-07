<?php
header('Content-Type: application/json');

// Common services to check
$commonServices = [
    'nginx',
    'apache2', 
    'mysql',
    'mariadb',
    'postgresql',
    'php-fpm',
    'php8.1-fpm',
    'php8.2-fpm', 
    'php8.3-fpm',
    'redis',
    'redis-server',
    'docker',
    'ssh',
    'sshd',
    'fail2ban',
    'cron',
    'ollama'
];

function checkServiceStatus($serviceName) {
    // Try systemctl first (systemd)
    $output = shell_exec("systemctl is-active $serviceName 2>/dev/null");
    if ($output !== null) {
        $status = trim($output);
        if ($status === 'active') {
            return 'running';
        } elseif ($status === 'inactive' || $status === 'failed') {
            return 'stopped';
        }
    }
    
    // Try service command (SysV)
    $output = shell_exec("service $serviceName status 2>/dev/null");
    if ($output !== null && strpos($output, 'running') !== false) {
        return 'running';
    }
    
    // Try ps command as fallback
    $output = shell_exec("ps aux | grep -v grep | grep $serviceName 2>/dev/null");
    if ($output !== null && trim($output) !== '') {
        return 'running';
    }
    
    // Check if service exists but is stopped
    $output = shell_exec("systemctl list-unit-files | grep $serviceName 2>/dev/null");
    if ($output !== null && trim($output) !== '') {
        return 'stopped';
    }
    
    return null; // Service not found
}

function controlService($serviceName, $operation) {
    $allowedOperations = ['start', 'stop', 'restart', 'reload'];
    if (!in_array($operation, $allowedOperations)) {
        return ['success' => false, 'error' => 'Invalid operation'];
    }
    
    // Security check - only allow known services
    global $commonServices;
    if (!in_array($serviceName, $commonServices)) {
        return ['success' => false, 'error' => 'Service not allowed'];
    }
    
    // Try systemctl first
    $command = "systemctl $operation $serviceName 2>&1";
    $output = shell_exec($command);
    $exitCode = 0;
    
    // Check if command was successful
    exec($command, $outputArray, $exitCode);
    
    if ($exitCode === 0) {
        return ['success' => true, 'message' => "Service $operation completed successfully"];
    } else {
        // Try service command as fallback
        $command = "service $serviceName $operation 2>&1";
        exec($command, $outputArray2, $exitCode2);
        
        if ($exitCode2 === 0) {
            return ['success' => true, 'message' => "Service $operation completed successfully"];
        } else {
            return ['success' => false, 'error' => "Failed to $operation service: " . implode("\n", $outputArray2)];
        }
    }
}

// Handle POST requests for service control
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    if ($input['action'] === 'control_service') {
        $serviceName = $input['service'] ?? '';
        $operation = $input['operation'] ?? '';
        
        if (!$serviceName || !$operation) {
            echo json_encode(['success' => false, 'error' => 'Missing service name or operation']);
            exit;
        }
        
        $result = controlService($serviceName, $operation);
        echo json_encode($result);
        exit;
    }
}

// Handle GET requests for service status
try {
    $services = [];
    
    foreach ($commonServices as $serviceName) {
        $status = checkServiceStatus($serviceName);
        
        // Only include services that exist on the system
        if ($status !== null) {
            $services[] = [
                'name' => $serviceName,
                'status' => $status
            ];
        }
    }
    
    // Sort services by name
    usort($services, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
