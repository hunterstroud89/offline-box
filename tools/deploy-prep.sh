#!/bin/bash
# Deployment Preparation Script for OfflineBox v8

echo "=== OfflineBox v8 Deployment Preparation ==="
echo ""

# Check current environment
echo "1. Current Environment Check:"
echo "   - Running on: $(uname -s) $(uname -m)"
echo "   - PHP Version: $(php -v | head -n1)"
echo ""

# Check for hardcoded references (excluding config system)
echo "2. Hardcoded Reference Check:"
LOCALHOST_REFS=$(grep -r "localhost" . --exclude-dir=emulator.js --exclude-dir=.git --exclude="*.log" --exclude="config.php" --exclude="*.sh" | grep -v "apps.json" | wc -l)
echo "   - Localhost references (excluding apps.json): $LOCALHOST_REFS"
if [ $LOCALHOST_REFS -gt 0 ]; then
    echo "     WARNING: Found hardcoded localhost references:"
    grep -r "localhost" . --exclude-dir=emulator.js --exclude-dir=.git --exclude="*.log" --exclude="config.php" --exclude="*.sh" | grep -v "apps.json"
fi

# Check file permissions for JSON data files
echo ""
echo "3. File Permissions Check:"
echo "   - data/json/ directory: $(ls -ld data/json/ | cut -d' ' -f1)"
for json_file in data/json/*.json; do
    if [ -f "$json_file" ]; then
        echo "   - $(basename $json_file): $(ls -l $json_file | cut -d' ' -f1)"
    fi
done

# Create backup of current configuration
echo ""
echo "4. Configuration Backup:"
BACKUP_DIR="data/json/backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp data/json/*.json "$BACKUP_DIR/"
echo "   - Backup created: $BACKUP_DIR"

# Test config system
echo ""
echo "5. Config System Test:"
cd data/helpers
php -r "
require_once 'config.php';
echo '   - Environment: ' . config('environment') . PHP_EOL;
echo '   - Base URL: ' . base_url() . PHP_EOL;
echo '   - Kiwix URL: ' . config()->getKiwixUrl() . PHP_EOL;
"
cd ../..

# Check required services
echo ""
echo "6. Service Check:"
echo "   - Kiwix binary: $([ -f 'pages/apps/kiwix/kiwix-serve' ] && echo 'Found' || echo 'Missing')"
echo "   - Portainer access: $(curl -s -k -m 2 https://10.0.0.224:9443 >/dev/null 2>&1 && echo 'Accessible' || echo 'Not accessible')"

echo ""
echo "=== Deployment Checklist for Pi ==="
echo "□ Copy project files to Pi"
echo "□ Set file ownership: sudo chown -R www-data:www-data /var/www/html/v8"
echo "□ Set permissions: sudo chmod 664 /var/www/html/v8/data/json/*.json"
echo "□ Start Kiwix service"
echo "□ Configure Apache virtual host"
echo "□ Test from Pi's IP address"
echo "□ Verify all apps work in production"
echo ""
echo "Ready for deployment! 🚀"
