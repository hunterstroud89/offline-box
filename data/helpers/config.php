<?php
/**
 * Environment Configuration
 * Auto-detects Mac development vs Pi production environment
 */

class Config {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        $this->detectEnvironment();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }
    
    private function detectEnvironment() {
        // Detect if we're on Mac (development) or Pi (production)
        $is_mac = (strpos(php_uname(), 'Darwin') !== false);
        $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $is_development = $is_mac || (strpos($current_host, '10.0.0.161') !== false);
        
        if ($is_development) {
            // Mac/Development configuration
            $this->config = [
                'environment' => 'development',
                'base_url' => 'http://10.0.0.161:8880/v8',
                'server_ip' => '10.0.0.161',
                'server_port' => '8880',
                'kiwix_port' => '8082',
                'portainer_port' => '9443',
                'portainer_ip' => '10.0.0.224' // Keep existing Pi IP for Portainer
            ];
        } else {
            // Pi/Production configuration
            $pi_ip = $this->detectPiIP();
            $pi_mode = $this->detectPiMode($pi_ip);
            
            $this->config = [
                'environment' => 'production',
                'pi_mode' => $pi_mode, // 'client' or 'ap'
                'base_url' => "http://{$pi_ip}",
                'server_ip' => $pi_ip,
                'server_port' => '80',
                'kiwix_port' => '8082',
                'portainer_port' => '9443',
                'portainer_ip' => $pi_ip
            ];
        }
    }
    
    private function detectPiMode($ip) {
        // Detect if Pi is in AP mode or Client mode based on IP range
        if (strpos($ip, '192.168.4.') === 0 || strpos($ip, '10.42.0.') === 0) {
            return 'ap'; // Access Point mode - Pi is creating its own network
        } else {
            return 'client'; // Client mode - Pi connected to existing network
        }
    }
    
    private function detectPiIP() {
        // Try to detect Pi's IP address
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
        // Fallback to common Pi IP
        return '10.0.0.224';
    }
    
    public function get($key) {
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
    
    public function getBaseUrl() {
        return $this->config['base_url'];
    }
    
    public function getKiwixUrl() {
        return "http://{$this->config['server_ip']}:{$this->config['kiwix_port']}";
    }
    
    public function getPortainerUrl() {
        return "https://{$this->config['portainer_ip']}:{$this->config['portainer_port']}";
    }
    
    public function isDevelopment() {
        return $this->config['environment'] === 'development';
    }
    
    public function isProduction() {
        return $this->config['environment'] === 'production';
    }
    
    public function getPiMode() {
        return isset($this->config['pi_mode']) ? $this->config['pi_mode'] : null;
    }
    
    public function isAPMode() {
        return $this->getPiMode() === 'ap';
    }
    
    public function isClientMode() {
        return $this->getPiMode() === 'client';
    }
    
    // Helper method to convert app URLs based on environment
    public function resolveAppUrl($url, $isHardcoded = false) {
        // Skip resolution for hardcoded URLs
        if ($isHardcoded) {
            return $url;
        }
        
        // Handle localhost URLs in development
        if ($this->isDevelopment()) {
            if (strpos($url, 'http://localhost:8082') === 0) {
                return $this->getKiwixUrl();
            }
            if (strpos($url, 'https://10.0.0.224:9443') === 0) {
                return $this->getPortainerUrl();
            }
        } else {
            // In production, replace any development URLs with Pi equivalents
            $url = str_replace('10.0.0.161:8880/v8', $this->config['server_ip'], $url);
            $url = str_replace('localhost', $this->config['server_ip'], $url);
        }
        
        return $url;
    }
}

// Convenience functions
function config($key = null) {
    $config = Config::getInstance();
    return $key ? $config->get($key) : $config;
}

function base_url() {
    return Config::getInstance()->getBaseUrl();
}

function is_development() {
    return Config::getInstance()->isDevelopment();
}

function resolve_app_url($url, $isHardcoded = false) {
    return Config::getInstance()->resolveAppUrl($url, $isHardcoded);
}
