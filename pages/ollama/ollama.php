<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Check if Ollama is running
function check_ollama_status() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:11434/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}

// Get available models
function get_ollama_models() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:11434/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        return $data['models'] ?? [];
    }
    return [];
}

$ollama_running = check_ollama_status();
$models = $ollama_running ? get_ollama_models() : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat - OfflineBox</title>
    <link rel="stylesheet" href="ollama.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Main Chat Interface -->
    <div class="chat-app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="header-actions">
                    <div class="header-left">
                        <a href="../home/home.php" class="back-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </div>
                    <div class="header-right">
                        <button class="new-chat-btn" onclick="startNewChat()" title="New chat">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                        <button class="sidebar-collapse-btn" onclick="toggleSidebar()" title="Toggle sidebar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="chat-history">
                <!-- Chat history will be populated by JavaScript -->
            </div>
            
            <div class="sidebar-footer">
                <div class="model-selector">
                    <label for="model-select">Model</label>
                    <select id="model-select">
                        <?php if (!empty($models)): ?>
                            <?php foreach ($models as $model): ?>
                                <option value="<?= htmlspecialchars($model['name']) ?>">
                                    <?= htmlspecialchars(explode(':', $model['name'])[0]) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No models available</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="status-bar">
                    <div class="status-indicator <?= $ollama_running ? 'online' : 'offline' ?>">
                        <div class="status-dot"></div>
                        <span><?= $ollama_running ? 'Connected' : 'Disconnected' ?></span>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Main Chat Area -->
        <div class="main-content">
            <!-- Top Bar (shows when sidebar is collapsed) -->
            <div class="main-content-header">
                <a href="../home/home.php" class="back-btn-main">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
            
            <?php if (!$ollama_running): ?>
                <div class="error-state">
                    <div class="error-icon">⚠️</div>
                    <h2>Ollama Not Connected</h2>
                    <p>Please start the Ollama service to begin chatting.</p>
                    <div class="code-suggestion">
                        <code>sudo systemctl start ollama</code>
                    </div>
                </div>
            <?php elseif (empty($models)): ?>
                <div class="error-state">
                    <div class="error-icon">📦</div>
                    <h2>No Models Available</h2>
                    <p>Download a model to start chatting.</p>
                    <div class="code-suggestion">
                        <code>ollama pull llama3.2:1b</code>
                    </div>
                </div>
            <?php else: ?>
                <!-- Welcome Screen -->
                <div class="welcome-screen" id="welcome-screen">
                    <div class="welcome-content">
                        <div class="ai-logo">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                <path d="M9 9h.01M15 9h.01" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                        <h1>Hello! I'm your AI assistant</h1>
                        <p>Ask me anything, and I'll do my best to help you. What would you like to know?</p>
                        
                        <div class="quick-actions">
                            <button class="quick-action" onclick="sendQuickMessage('Explain quantum computing in simple terms')">
                                <span class="action-icon">🔬</span>
                                Explain quantum computing
                            </button>
                            <button class="quick-action" onclick="sendQuickMessage('Write a Python function to calculate fibonacci numbers')">
                                <span class="action-icon">💻</span>
                                Help with coding
                            </button>
                            <button class="quick-action" onclick="sendQuickMessage('What are some good productivity tips?')">
                                <span class="action-icon">📈</span>
                                Productivity tips
                            </button>
                            <button class="quick-action" onclick="sendQuickMessage('Tell me an interesting fact about space')">
                                <span class="action-icon">🚀</span>
                                Space facts
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages" id="chat-messages" style="display: none;">
                    <div class="messages-container" id="messages-container"></div>
                </div>
            <?php endif; ?>
            
            <!-- Chat Input (only show if Ollama is running and models are available) -->
            <?php if ($ollama_running && !empty($models)): ?>
                <div class="chat-input-area">
                    <div class="input-container">
                        <div class="input-wrapper">
                            <textarea 
                                id="user-input" 
                                placeholder="Message AI assistant..."
                                rows="1"
                                maxlength="4000"
                            ></textarea>
                            <button id="send-btn" class="send-button" onclick="sendMessage()">
                                <svg id="send-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 13L11 13" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                                <div id="loading-spinner" class="spinner" style="display: none;">
                                    <div class="spinner-dot"></div>
                                    <div class="spinner-dot"></div>
                                    <div class="spinner-dot"></div>
                                </div>
                            </button>
                        </div>
                        <div class="input-footer">
                            <span class="character-count">0 / 4000</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="ollama.js?v=<?= time() ?>"></script>
</body>
</html>
