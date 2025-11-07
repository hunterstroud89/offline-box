<?php
/**
 * OfflineBox Games - Path Configuration (Pi Only)
 * 
 * Centralized path configuration for all games-related functionality.
 * Simplified for Pi-only deployment.
 */

class GamesPaths {
    
    // Base web directory (Pi only)
    public static function getWebRoot() {
        return '/var/www/html';
    }
    
    // Games directory paths
    public static function getGamesDir() {
        return '/var/www/html/pages/games';
    }
    
    // Downloaded games storage
    public static function getDownloadedGamesDir() {
        return '/var/www/html/pages/games/downloaded';
    }
    
    // External ROM storage (OFFLINEBOX)
    public static function getExternalRomsDir() {
        return '/media/hunter/OFFLINEBOX/roms';
    }
    
    // Game saves directory
    public static function getSavesDir() {
        return '/var/www/html/pages/games/saves';
    }
    
    // Game icons directory
    public static function getGameIconsDir() {
        return '/var/www/html/pages/games/game-icons';
    }
    
    // EmulatorJS directory
    public static function getEmulatorJsDir() {
        return '/var/www/html/pages/games/emulator.js';
    }
    
    // Assets directory
    public static function getAssetsDir() {
        return '/var/www/html/pages/games/assets';
    }
    
    // Config directory
    public static function getConfigDir() {
        return '/var/www/html/pages/games/config';
    }
    
    // Data directory (auto-config, apps.json, etc.)
    public static function getDataDir() {
        return '/var/www/html/data';
    }
    
    // Data helpers directory
    public static function getDataHelpersDir() {
        return '/var/www/html/data/helpers';
    }
    
    // Data JSON directory
    public static function getDataJsonDir() {
        return '/var/www/html/data/json';
    }
    
    // Files directory (for local file ROMs)
    public static function getFilesDir() {
        return '/var/www/html/files';
    }
    
    // Files ROMs directory
    public static function getFilesRomsDir() {
        return '/var/www/html/files/roms';
    }
    
    // Files update-recents.php path
    public static function getUpdateRecentsPath() {
        return '/var/www/html/pages/files/update-recents.php';
    }
    
    // Frontend themes directory
    public static function getFrontendThemesDir() {
        return '/var/www/html/frontend/themes';
    }
    
    // Home page path
    public static function getHomePagePath() {
        return '/var/www/html/pages/home/home.php';
    }
    
    // EmulatorJS data directory
    public static function getEmulatorJsDataDir() {
        return '/var/www/html/pages/games/emulator.js/data';
    }
    
    // EmulatorJS loader path
    public static function getEmulatorJsLoaderPath() {
        return '/var/www/html/pages/games/emulator.js/data/loader.js';
    }
    
    // Get all allowed ROM serving paths
    public static function getAllowedRomPaths() {
        return [
            '/media/hunter/OFFLINEBOX/roms',
            '/var/www/html/pages/games/downloaded',
            '/var/www/html/files/roms'
        ];
    }
    
    // Get download index file path
    public static function getDownloadIndexFile() {
        return '/var/www/html/pages/games/downloaded/downloads_index.json';
    }
    
    // Web URL paths (for browser access)
    public static function getRomServeUrl() {
        return '/pages/games/rom-serve.php';
    }
    
    public static function getScanApiUrl() {
        return '/pages/games/scan.php';
    }
    
    public static function getDownloadApiUrl() {
        return '/pages/games/download-api.php';
    }
    
    public static function getGameIconUrl($console) {
        $extension = ($console === 'gba') ? 'png' : 'svg';
        return "/pages/games/game-icons/{$console}.{$extension}";
    }
    
    public static function getEmulatorJsDataUrl() {
        return '/pages/games/emulator.js/data/';
    }
    
    public static function getEmulatorJsLoaderUrl() {
        return '/pages/games/emulator.js/data/loader.js';
    }
    
    public static function getFilesRomsUrl() {
        return '/files/roms/';
    }
    
    // Get environment info for debugging
    public static function getEnvironmentInfo() {
        return [
            'environment' => self::isRaspberryPi() ? 'Raspberry Pi' : 'Development',
            'web_root' => self::getWebRoot(),
            'games_dir' => self::getGamesDir(),
            'downloaded_games_dir' => self::getDownloadedGamesDir(),
            'external_roms_dir' => self::getExternalRomsDir(),
            'php_uname_m' => php_uname('m'),
            'web_root_exists' => is_dir(self::getWebRoot()),
            'downloaded_dir_exists' => is_dir(self::getDownloadedGamesDir()),
            'external_roms_exists' => is_dir(self::getExternalRomsDir())
        ];
    }
}
?>
