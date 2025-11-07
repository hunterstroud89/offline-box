<?php
/**
 * Auto-include this file at the top of any page that needs environment configuration
 * This file automatically loads the config system and makes it available globally
 */

// Only include config once per request
if (!class_exists('Config')) {
    require_once __DIR__ . '/config.php';
}

// Make config globally available
$GLOBALS['app_config'] = Config::getInstance();

// Helper function to get resolved URLs for apps.json data
function resolve_app_urls($apps) {
    if (!is_array($apps)) return $apps;
    
    foreach ($apps as &$app) {
        if (isset($app['url'])) {
            $isHardcoded = isset($app['hardcoded_url']) ? $app['hardcoded_url'] : false;
            $app['url'] = resolve_app_url($app['url'], $isHardcoded);
        }
    }
    return $apps;
}

// Auto-resolve apps.json if it's being loaded
function load_apps_json($file_path) {
    if (!file_exists($file_path)) return [];
    
    $apps = json_decode(file_get_contents($file_path), true) ?: [];
    return resolve_app_urls($apps);
}
?>
