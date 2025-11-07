<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

$title = "Jellyfin Media Server";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - OfflineBox</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #121212;
            color: #d0d0d0;
        }
        
        .top-bar {
            background: #242424;
            border-bottom: 1px solid #2a2a2a;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            height: 50px;
        }
        
        .home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #d0d0d0;
            padding: 6px 12px;
            border-radius: 6px;
            background: transparent;
            border: 1px solid #2a2a2a;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .home-btn:hover {
            background: #2a2a2a;
            border-color: #808080;
            color: #ffffff;
        }
        
        .home-btn img {
            width: 16px;
            height: 16px;
        }
        
        .jellyfin-frame {
            width: 100%;
            height: calc(100vh - 50px);
            border: none;
            display: block;
        }
        
        .page-title {
            flex: 1;
            font-size: 16px;
            font-weight: 500;
            color: #d0d0d0;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="../home/home.php" class="home-btn">
            <img src="../../data/icons/home.png" alt="Home">
            Home
        </a>
    </div>
    
    <iframe 
        src="http://offlinebox.local:8096" 
        class="jellyfin-frame"
        title="Jellyfin Media Server">
    </iframe>
</body>
</html>
