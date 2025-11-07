# Games Path Configuration System (Pi Only)

## Overview
A centralized path configuration system for the games module designed specifically for Raspberry Pi deployment.

## Key Features

### Pi-Only Configuration
- All paths configured for Raspberry Pi (`/var/www/html`)
- No environment detection needed
- Simple, straightforward configuration

### Centralized Path Management
All paths are managed through `config/paths.php` with the `GamesPaths` class:

#### Core Paths
- `getWebRoot()` - Base web directory
- `getDataDir()` - Data directory for JSON files
- `getFilesDir()` - Files directory for local ROMs
- `getExternalRomsDir()` - External USB drive ROM directory
- `getDownloadedGamesDir()` - Local downloaded games directory

#### EmulatorJS Paths
- `getEmulatorJsDataDir()` - EmulatorJS data directory (file system)
- `getEmulatorJsDataUrl()` - EmulatorJS data URL (web accessible)
- `getEmulatorJsLoaderUrl()` - EmulatorJS loader script URL

#### API URLs
- `getRomServeUrl()` - ROM serving endpoint
- `getScanApiUrl()` - Game scanning endpoint  
- `getDownloadApiUrl()` - Download management endpoint
- `getGameIconUrl($console)` - Console-specific game icons

#### Security & Validation
- `getAllowedRomPaths()` - Whitelisted ROM directories for security

## Updated Files

### Core Game Files
1. **games.php** - Main games interface
   - Uses centralized URLs for all API calls
   - Dynamic ROM path display
   - Centralized icon path management

2. **play.php** - Game player interface
   - EmulatorJS path configuration
   - ROM serving URL management
   - Loader script path management

3. **player.php** - Simple player interface
   - ROM serving configuration
   - EmulatorJS integration paths

4. **scan.php** - Game scanning API
   - Uses centralized ROM directories
   - Download status integration

5. **rom-serve.php** - ROM file serving
   - Security validation with allowed paths
   - Range request support

6. **download-manager.php** - Download system
   - Local storage path management
   - Download index file handling

7. **search-api.php** - Search functionality
   - File system search paths
   - ROM directory scanning

## Benefits

### Maintainability
- Single place to change all paths for Pi deployment
- No scattered hardcoded paths
- Simple Pi-only configuration

### Pi Deployment
- All paths correctly configured for `/var/www/html`
- Works with OFFLINEBOX USB mount at `/media/hunter/OFFLINEBOX/roms`
- No complex environment switching needed

### Security
- Centralized path validation
- Whitelisted ROM directories
- Consistent security policies

## Usage

Simply include the configuration in any game file:
```php
require_once __DIR__ . '/config/paths.php';

// Use centralized paths
$romsDir = GamesPaths::getExternalRomsDir();
$downloadDir = GamesPaths::getDownloadedGamesDir();
$apiUrl = GamesPaths::getScanApiUrl();
```

## Testing

Run the test script to verify configuration:
```bash
cd pages/games/config
php test-paths.php
```

This will show:
- Environment detection results
- All configured paths
- File system validation
- URL path mappings

## Pi Deployment

### Raspberry Pi Production
- Web root: `/var/www/html`
- External ROMs: `/media/hunter/OFFLINEBOX/roms`
- Downloaded games: `/var/www/html/pages/games/downloaded`
- All paths correctly configured for Pi environment

The system is designed specifically for Pi deployment with no environment switching complexity.
