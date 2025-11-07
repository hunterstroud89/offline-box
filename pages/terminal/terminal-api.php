<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in JSON response

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class WebTerminal {
    private $sessionDir;
    private $allowedCommands = [];
    private $bannedCommands = ['rm -rf /', 'dd if=/dev/zero', 'fork bomb', ':(){ :|:& };:'];
    
    public function __construct() {
        // Create session directory for storing terminal state
        $this->sessionDir = '/tmp/webterminal_sessions';
        if (!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir, 0755, true);
        }
    }
    
    public function handleRequest() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['command'])) {
                throw new Exception('Invalid request');
            }
            
            $command = trim($input['command']);
            $cwd = $input['cwd'] ?? '/home/hunter';
            $session = $input['session'] ?? 'default';
            
            // Validate session ID
            $session = preg_replace('/[^a-zA-Z0-9]/', '', $session);
            
            // Security check
            if ($this->isCommandBanned($command)) {
                throw new Exception('Command not allowed for security reasons');
            }
            
            $result = $this->executeCommand($command, $cwd, $session);
            
            // Add current working directory to result
            $result['cwd'] = $this->getCurrentWorkingDirectory($command, $cwd);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function executeCommand($command, $workingDir) {
        // Special handling for interactive commands
        $needsTTY = (strpos($command, 'apt ') !== false || 
                     strpos($command, 'sudo ') !== false ||
                     strpos($command, 'nano ') !== false ||
                     strpos($command, 'vim ') !== false);
        
        if ($needsTTY) {
            // Set environment variables for better apt output
            $envVars = 'DEBIAN_FRONTEND=noninteractive TERM=xterm-256color';
            $fullCommand = "sudo -u hunter bash -c 'export $envVars && cd " . escapeshellarg($workingDir) . " && " . $command . " 2>&1'";
        } else {
            $fullCommand = "sudo -u hunter bash -c 'cd " . escapeshellarg($workingDir) . " && " . $command . "'";
        }
        
        $descriptorSpec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );
        
        $process = proc_open($fullCommand, $descriptorSpec, $pipes);
        
        if (is_resource($process)) {
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            
            // For TTY commands, combine output and error
            if ($needsTTY) {
                $combinedOutput = $output;
                if (!empty($error)) {
                    $combinedOutput .= $error;
                }
                return array(
                    'output' => $combinedOutput,
                    'error' => '',
                    'returnCode' => $returnCode
                );
            }
            
            return array(
                'output' => $output,
                'error' => $error,
                'returnCode' => $returnCode
            );
        }
        
        return array(
            'output' => '',
            'error' => 'Failed to execute command',
            'returnCode' => 1
        );
    }
    
    private function isBuiltinCommand($command) {
        $builtins = ['clear', 'help', 'history', 'exit'];
        $cmd = explode(' ', trim($command))[0];
        return in_array($cmd, $builtins);
    }
    
    private function handleBuiltinCommand($command, $cwd, $session) {
        $parts = explode(' ', trim($command));
        $cmd = $parts[0];
        
        switch ($cmd) {
            case 'clear':
                return ['output' => '', 'cwd' => $cwd, 'clear' => true];
            
            case 'help':
                $help = "Web Terminal Help:\n\n";
                $help .= "Available commands:\n";
                $help .= "  • All standard Linux commands (ls, cd, mkdir, etc.)\n";
                $help .= "  • Text editors (nano, vim)\n";
                $help .= "  • System tools (top, ps, df, free)\n";
                $help .= "  • Package management (apt, dpkg)\n";
                $help .= "  • Network tools (curl, wget, ping)\n";
                $help .= "  • File operations (cp, mv, rm, chmod)\n";
                $help .= "  • sudo for administrative tasks\n\n";
                $help .= "Keyboard shortcuts:\n";
                $help .= "  • Ctrl+C: Interrupt command\n";
                $help .= "  • Ctrl+L: Clear screen\n";
                $help .= "  • Up/Down: Command history\n";
                return ['output' => $help, 'cwd' => $cwd];
            
            case 'history':
                // TODO: Implement session-based history
                return ['output' => 'Command history not yet implemented', 'cwd' => $cwd];
            
            case 'exit':
                return ['output' => 'Session ended. Refresh to start new session.', 'cwd' => $cwd];
            
            default:
                return ['output' => "Unknown builtin command: $cmd", 'cwd' => $cwd];
        }
    }
    
    private function isCommandBanned($command) {
        foreach ($this->bannedCommands as $banned) {
            if (strpos($command, $banned) !== false) {
                return true;
            }
        }
        
        // Additional safety checks
        if (preg_match('/rm\s+.*-rf\s+\/[^\/\s]*$/', $command)) {
            return true;
        }
        
        return false;
    }
    
    private function validateDirectory($dir) {
        // For root access, allow any directory
        // Use sudo to check if directory exists
        $checkCmd = sprintf('sudo -n test -d %s', escapeshellarg($dir));
        exec($checkCmd, $output, $returnCode);
        
        if ($returnCode === 0) {
            return $dir;
        }
        
        // Default to hunter home if directory doesn't exist
        return '/home/hunter';
    }
    
    private function getCurrentDirectory($currentCwd, $command) {
        // Check if command was a cd command
        if (preg_match('/^cd\s+(.*)$/', trim($command), $matches)) {
            $targetDir = trim($matches[1]);
            
            if (empty($targetDir) || $targetDir === '~') {
                return '/home/hunter'; // Hunter home directory
            }
            
            if ($targetDir === '-') {
                // TODO: Implement previous directory tracking
                return $currentCwd;
            }
            
            if (substr($targetDir, 0, 1) === '/') {
                // Absolute path
                $newPath = $targetDir;
            } else {
                // Relative path
                $newPath = $currentCwd . '/' . $targetDir;
            }
            
            // Resolve the path using sudo
            $checkCmd = sprintf('sudo -n bash -c "cd %s && pwd" 2>/dev/null', escapeshellarg($newPath));
            $realPath = trim(shell_exec($checkCmd));
            if ($realPath && !empty($realPath)) {
                return $realPath;
            }
        }
        
        return $currentCwd;
    }
    
    private function getCurrentWorkingDirectory($command, $currentCwd) {
        // Check if command was a cd command
        if (preg_match('/^cd\s*(.*)$/', trim($command), $matches)) {
            $targetDir = trim($matches[1]);
            
            if (empty($targetDir) || $targetDir === '~') {
                return '/home/hunter'; // Home directory
            }
            
            if ($targetDir === '-') {
                // TODO: Implement previous directory tracking
                return $currentCwd;
            }
            
            if (substr($targetDir, 0, 1) === '/') {
                // Absolute path
                $newPath = $targetDir;
            } else {
                // Relative path
                $newPath = $currentCwd . '/' . $targetDir;
            }
            
            // Get the real path using sudo
            $checkCmd = sprintf('sudo -u hunter bash -c "cd %s 2>/dev/null && pwd"', escapeshellarg($newPath));
            $realPath = trim(shell_exec($checkCmd));
            if ($realPath && !empty($realPath) && $realPath !== '/') {
                return $realPath;
            }
        }
        
        return $currentCwd;
    }

    private function setSessionCwd($session, $cwd) {
        $sessionFile = $this->sessionDir . '/' . $session . '_cwd';
        file_put_contents($sessionFile, $cwd);
    }
    
    private function getSessionCwd($session) {
        $sessionFile = $this->sessionDir . '/' . $session . '_cwd';
        if (file_exists($sessionFile)) {
            return trim(file_get_contents($sessionFile));
        }
        return '/home/hunter';
    }
}

// Handle the request
$terminal = new WebTerminal();
$terminal->handleRequest();
?>
