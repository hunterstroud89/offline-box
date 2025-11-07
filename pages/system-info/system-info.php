<?php
// System Information for Raspberry Pi
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, $precision) . ' ' . $units[$i];
}

function getSystemUptime() {
    $uptime = shell_exec('uptime -p') ?: '';
    return trim($uptime ?: 'Unknown');
}

function getCPUInfo() {
    $cpuinfo = shell_exec('cat /proc/cpuinfo | grep "model name" | head -1 | cut -d: -f2') ?: '';
    if (!$cpuinfo) {
        $cpuinfo = shell_exec('cat /proc/cpuinfo | grep "Model" | head -1 | cut -d: -f2') ?: '';
    }
    return trim($cpuinfo ?: 'Unknown');
}

function getCPUTemperature() {
    $temp = shell_exec('cat /sys/class/thermal/thermal_zone0/temp 2>/dev/null') ?: '';
    if ($temp) {
        return round($temp / 1000, 1) . '°C';
    }
    return 'N/A';
}

function getCPUUsage() {
    $load = shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1") ?: '';
    if (!$load) {
        $load = shell_exec("top -bn1 | grep 'load average' | awk '{print $10}' | cut -d',' -f1") ?: '';
    }
    return trim($load ?: '0') . '%';
}

function getMemoryInfo() {
    $meminfo = shell_exec('cat /proc/meminfo') ?: '';
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
    
    $totalMem = isset($total[1]) ? $total[1] * 1024 : 0;
    $availableMem = isset($available[1]) ? $available[1] * 1024 : 0;
    $usedMem = $totalMem - $availableMem;
    
    return [
        'total' => $totalMem,
        'used' => $usedMem,
        'available' => $availableMem,
        'percentage' => $totalMem > 0 ? round(($usedMem / $totalMem) * 100, 1) : 0
    ];
}

function getDiskInfo() {
    $disks = [];
    
    // Get all mounted filesystems
    $df = shell_exec('df -h --output=source,size,used,avail,pcent,target | grep -E "^/dev/"') ?: '';
    $lines = explode("\n", trim($df));
    
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 6) {
            $device = $parts[0];
            $size = $parts[1];
            $used = $parts[2];
            $available = $parts[3];
            $percentage = rtrim($parts[4], '%');
            $mountpoint = $parts[5];
            
            // Determine disk type
            $type = 'Unknown';
            if (strpos($device, 'mmcblk') !== false) {
                $type = 'SD Card';
            } elseif (strpos($device, 'sda') !== false || strpos($device, 'sdb') !== false) {
                $type = 'USB/External';
            } elseif (strpos($device, 'nvme') !== false) {
                $type = 'NVMe SSD';
            }
            
            $disks[] = [
                'device' => $device,
                'type' => $type,
                'size' => $size,
                'used' => $used,
                'available' => $available,
                'percentage' => intval($percentage),
                'mountpoint' => $mountpoint
            ];
        }
    }
    
    return $disks;
}

function getNetworkInfo() {
    $interfaces = [];
    
    // Get network interfaces
    $ifconfig = shell_exec('ip addr show') ?: '';
    $blocks = preg_split('/^\d+: /m', $ifconfig, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($blocks as $block) {
        if (preg_match('/^(\w+):/', $block, $matches)) {
            $interface = $matches[1];
            
            // Skip loopback
            if ($interface === 'lo') continue;
            
            $ip = 'Not connected';
            if (preg_match('/inet (\d+\.\d+\.\d+\.\d+)/', $block, $ipMatch)) {
                $ip = $ipMatch[1];
            }
            
            $status = strpos($block, 'state UP') !== false ? 'Connected' : 'Disconnected';
            
            // Get MAC address
            $mac = 'Unknown';
            if (preg_match('/link\/ether ([a-f0-9:]{17})/', $block, $macMatch)) {
                $mac = strtoupper($macMatch[1]);
            }
            
            $interfaces[] = [
                'name' => $interface,
                'ip' => $ip,
                'status' => $status,
                'mac' => $mac
            ];
        }
    }
    
    return $interfaces;
}

function getRaspberryPiModel() {
    $model = shell_exec('cat /proc/device-tree/model 2>/dev/null') ?: '';
    if (!$model) {
        $model = shell_exec('cat /proc/cpuinfo | grep "Model" | cut -d: -f2') ?: '';
    }
    return trim($model ?: 'Unknown Raspberry Pi');
}

function getKernelVersion() {
    return trim(shell_exec('uname -r') ?: 'Unknown');
}

function getOSInfo() {
    $os = shell_exec('cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2 | tr -d \'"\'') ?: '';
    return trim($os ?: 'Linux');
}

function getLoadAverage() {
    $load = shell_exec('cat /proc/loadavg | cut -d\' \' -f1-3') ?: '';
    return trim($load ?: '0.00 0.00 0.00');
}

function getRunningProcesses() {
    $count = shell_exec('ps aux | wc -l') ?: '0';
    return intval($count) - 1; // Subtract header line
}

function getConnectedUSBDevices() {
    $usb = shell_exec('lsusb | wc -l') ?: '0';
    return intval($usb ?: 0);
}

// Collect all system information
$systemInfo = [
    'model' => getRaspberryPiModel(),
    'os' => getOSInfo(),
    'kernel' => trim(getKernelVersion()),
    'uptime' => getSystemUptime(),
    'cpu' => [
        'model' => getCPUInfo(),
        'temperature' => getCPUTemperature(),
        'usage' => getCPUUsage(),
        'load_average' => getLoadAverage()
    ],
    'memory' => getMemoryInfo(),
    'disks' => getDiskInfo(),
    'network' => getNetworkInfo(),
    'processes' => getRunningProcesses(),
    'usb_devices' => getConnectedUSBDevices()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Information - OfflineBox</title>
    <link rel="stylesheet" href="system-info.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <div class="section">
            <div class="section-header">
                <a class="back-link" href="../home/home.php" title="Back to Home" aria-label="Back to Home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h2 class="section-title">System Information</h2>
            </div>

            <!-- System Overview -->
            <div class="info-card">
                <h3 class="card-title">System Overview</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Device</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['model']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Operating System</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['os']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kernel Version</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['kernel']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Uptime</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['uptime']) ?></div>
                    </div>
                </div>
            </div>

            <!-- CPU Information -->
            <div class="info-card">
                <h3 class="card-title">CPU Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Processor</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['cpu']['model']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Temperature</div>
                        <div class="info-value temperature <?= floatval($systemInfo['cpu']['temperature']) > 70 ? 'hot' : '' ?>"><?= htmlspecialchars($systemInfo['cpu']['temperature']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Current Usage</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['cpu']['usage']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Load Average</div>
                        <div class="info-value"><?= htmlspecialchars($systemInfo['cpu']['load_average']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Memory Information -->
            <div class="info-card">
                <h3 class="card-title">Memory Usage</h3>
                <div class="memory-bar">
                    <div class="memory-used" style="width: <?= $systemInfo['memory']['percentage'] ?>%"></div>
                    <div class="memory-text">
                        <?= formatBytes($systemInfo['memory']['used']) ?> / <?= formatBytes($systemInfo['memory']['total']) ?> 
                        (<?= $systemInfo['memory']['percentage'] ?>% used)
                    </div>
                </div>
                <div class="info-grid" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="info-label">Total RAM</div>
                        <div class="info-value"><?= formatBytes($systemInfo['memory']['total']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Available</div>
                        <div class="info-value"><?= formatBytes($systemInfo['memory']['available']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Storage Information -->
            <div class="info-card">
                <h3 class="card-title">Storage Devices</h3>
                <?php foreach ($systemInfo['disks'] as $disk): ?>
                <div class="storage-device">
                    <div class="storage-header">
                        <div class="storage-info">
                            <div class="storage-name"><?= htmlspecialchars($disk['device']) ?> (<?= htmlspecialchars($disk['type']) ?>)</div>
                            <div class="storage-mount"><?= htmlspecialchars($disk['mountpoint']) ?></div>
                        </div>
                        <div class="storage-size"><?= htmlspecialchars($disk['used']) ?> / <?= htmlspecialchars($disk['size']) ?></div>
                    </div>
                    <div class="storage-bar">
                        <div class="storage-used <?= $disk['percentage'] > 90 ? 'critical' : ($disk['percentage'] > 75 ? 'warning' : '') ?>" 
                             style="width: <?= $disk['percentage'] ?>%"></div>
                        <div class="storage-text"><?= $disk['percentage'] ?>% used (<?= htmlspecialchars($disk['available']) ?> free)</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Network Information -->
            <div class="info-card">
                <h3 class="card-title">Network Interfaces</h3>
                <?php foreach ($systemInfo['network'] as $interface): ?>
                <div class="network-interface">
                    <div class="network-header">
                        <div class="network-name"><?= htmlspecialchars($interface['name']) ?></div>
                        <div class="network-status <?= strtolower($interface['status']) ?>"><?= htmlspecialchars($interface['status']) ?></div>
                    </div>
                    <div class="network-details">
                        <div class="network-detail">
                            <span class="detail-label">IP Address:</span>
                            <span class="detail-value"><?= htmlspecialchars($interface['ip']) ?></span>
                        </div>
                        <div class="network-detail">
                            <span class="detail-label">MAC Address:</span>
                            <span class="detail-value"><?= htmlspecialchars($interface['mac']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- System Stats -->
            <div class="info-card">
                <h3 class="card-title">System Statistics</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Running Processes</div>
                        <div class="info-value"><?= $systemInfo['processes'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">USB Devices</div>
                        <div class="info-value"><?= $systemInfo['usb_devices'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?= PHP_VERSION ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value" id="lastUpdated"><?= date('Y-m-d H:i:s') ?></div>
                    </div>
                </div>
            </div>

            <div class="refresh-controls">
                <button class="refresh-btn" onclick="refreshData()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Refresh Data
                </button>
                <label class="auto-refresh">
                    <input type="checkbox" id="autoRefresh">
                    Auto-refresh every 30s
                </label>
            </div>
        </div>
    </div>

    <script>
        let refreshInterval = null;

        function refreshData() {
            location.reload();
        }

        document.getElementById('autoRefresh').addEventListener('change', function() {
            if (this.checked) {
                refreshInterval = setInterval(refreshData, 30000);
            } else {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            }
        });

        // Update last updated time every second
        setInterval(function() {
            const now = new Date();
            document.getElementById('lastUpdated').textContent = now.toLocaleString();
        }, 1000);
    </script>
</body>
</html>
