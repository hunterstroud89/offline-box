<?php
/**
 * Debug version - detect environment for testing
 * This allows testing on development machine before Pi deployment
 */

require_once __DIR__ . '/paths.php';

class DebugGamesPaths extends GamesPaths {
    
    // Override for development testing
    public static function getWebRoot() {
        if (is_dir('/var/www/html')) {
            return '/var/www/html';  // Pi
        } else {
            return '/Volumes/html';  // Development
        }
    }
    
    public static function getExternalRomsDir() {
        if (is_dir('/media/hunter/OFFLINEBOX/roms')) {
            return '/media/hunter/OFFLINEBOX/roms';  // Pi
        } else {
            return '/Volumes/html/test-roms';  // Development fallback
        }
    }
    
    // Override all methods to use dynamic web root
    public static function getGamesDir() {
        return self::getWebRoot() . '/pages/games';
    }
    
    public static function getDownloadedGamesDir() {
        return self::getWebRoot() . '/pages/games/downloaded';
    }
    
    public static function getDataDir() {
        return self::getWebRoot() . '/data';
    }
    
    public static function getFilesDir() {
        return self::getWebRoot() . '/files';
    }
    
    public static function getFilesRomsDir() {
        return self::getWebRoot() . '/files/roms';
    }
    
    public static function getEmulatorJsDataDir() {
        return self::getWebRoot() . '/pages/games/emulator.js/data';
    }
    
    public static function getAllowedRomPaths() {
        return [
            self::getExternalRomsDir(),
            self::getDownloadedGamesDir(),
            self::getFilesRomsDir()
        ];
    }
    
    public static function getDownloadIndexFile() {
        return self::getDownloadedGamesDir() . '/downloads_index.json';
    }
}
?>
