<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OfflineBox Terminal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Liberation Mono', monospace;
            background: #000000;
            color: #00ff00;
            height: 100vh;
            overflow: hidden;
            font-size: 16px;
            line-height: 1.4;
        }

        #terminal {
            width: 100%;
            height: calc(100vh - 30px);
            padding: 15px;
            background: #000000;
            color: #00ff00;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .terminal-line {
            margin: 2px 0;
            line-height: 1.4;
        }

        .prompt {
            color: #00ff00;
            font-weight: bold;
        }

        .command {
            color: #ffffff;
        }

        .output {
            color: #cccccc;
            white-space: pre-wrap;
        }

        .error {
            color: #ff6666;
        }

        .input-line {
            display: flex;
            align-items: center;
            margin-top: 3px;
        }

        #command-input {
            background: transparent;
            border: none;
            color: #ffffff;
            font-family: 'Courier New', 'Liberation Mono', monospace;
            font-size: 16px;
            outline: none;
            flex: 1;
            caret-color: #00ff00;
            margin-left: 0;
        }

        .cursor {
            background: #00ff00;
            width: 10px;
            height: 20px;
            display: inline-block;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        .status-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #333333;
            color: #ffffff;
            padding: 5px 15px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            z-index: 1000;
        }

        .loading {
            color: #ffff00;
        }

        /* Scrollbar styling */
        #terminal::-webkit-scrollbar {
            width: 12px;
        }

        #terminal::-webkit-scrollbar-track {
            background: #111111;
        }

        #terminal::-webkit-scrollbar-thumb {
            background: #444444;
            border-radius: 4px;
        }

        #terminal::-webkit-scrollbar-thumb:hover {
            background: #666666;
        }

        /* Selection styling */
        ::selection {
            background: #444444;
            color: #ffffff;
        }

        ::-moz-selection {
            background: #444444;
            color: #ffffff;
        }

        /* Ensure text is selectable */
        #terminal {
            user-select: text;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
        }

        .terminal-line, .output, .prompt, .command {
            user-select: text;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
        }

        /* Welcome message styling */
        .welcome {
            color: #00ff00;
        }
    </style>
</head>
<body>
    <div id="terminal"></div>
    <div class="status-bar">
        <div id="status-left">OfflineBox Terminal - Ready</div>
        <div id="status-right">Ctrl+C: Interrupt | Ctrl+L: Clear | ↑↓: History</div>
    </div>

    <script>
        class WebTerminal {
            constructor() {
                console.log('WebTerminal constructor called');
                this.terminal = document.getElementById('terminal');
                console.log('Terminal element:', this.terminal);
                this.currentDir = '/home/hunter';
                this.commandHistory = [];
                this.historyIndex = -1;
                this.currentProcess = null;
                this.sessionId = Date.now().toString(36);
                
                if (!this.terminal) {
                    throw new Error('Terminal element not found');
                }
                
                this.init();
            }

            init() {
                console.log('Init called');
                try {
                    this.showWelcome();
                    console.log('Welcome shown');
                } catch (e) {
                    console.error('Error in showWelcome:', e);
                }
                
                try {
                    this.createInputLine();
                    console.log('Input line created');
                } catch (e) {
                    console.error('Error in createInputLine:', e);
                }
                
                try {
                    this.setupEventListeners();
                    console.log('Event listeners set up');
                } catch (e) {
                    console.error('Error in setupEventListeners:', e);
                }
                
                this.getCurrentDirectory();
            }

            showWelcome() {
                const welcome = `
┌─────────────────────────────────────────────────────────────────┐
│                    OfflineBox Web Terminal                      │
│                          Version 2.0                           │
│                                                                 │
│  Welcome to the OfflineBox terminal interface!                 │
│                                                                 │
│  Available commands:                                            │
│  • All standard Linux commands (ls, cd, mkdir, etc.)          │
│  • Text editors (nano, vim)                                    │
│  • System tools (htop, ps, df, free)                          │
│  • Package management (sudo apt install/update/upgrade)        │
│  • Network tools (curl, wget, ping)                           │
│  • File operations (cp, mv, rm, chmod)                        │
│  • sudo for administrative tasks                              │
│                                                                 │
│  Keyboard shortcuts:                                            │
│  • Ctrl+C: Interrupt command                                  │
│  • Ctrl+L: Clear screen                                       │
│  • Up/Down: Command history                                   │
│  • Tab: Auto-completion (when available)                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

`;
                this.addOutput(welcome, 'welcome');
            }

            createInputLine() {
                const inputLine = document.createElement('div');
                inputLine.className = 'input-line';
                
                const prompt = document.createElement('span');
                prompt.className = 'prompt';
                prompt.textContent = this.getPrompt();
                
                const input = document.createElement('input');
                input.type = 'text';
                input.id = 'command-input';
                input.autocomplete = 'off';
                input.spellcheck = false;
                
                inputLine.appendChild(prompt);
                inputLine.appendChild(input);
                
                this.terminal.appendChild(inputLine);
                input.focus();
                
                this.scrollToBottom();
                return input;
            }

            getPrompt() {
                const user = 'hunter'; // You can make this dynamic
                const host = 'offlinebox';
                const dir = this.currentDir.replace(/^\/home\/hunter/, '~');
                return `${user}@${host}:${dir}$ `;
            }

            setupEventListeners() {
                document.addEventListener('keydown', (e) => {
                    const input = document.getElementById('command-input');
                    if (!input) return;

                    // Only focus input if not selecting text and not in an input field
                    const hasSelection = window.getSelection().toString().length > 0;
                    const isInputFocused = document.activeElement === input;
                    
                    if (!hasSelection && !isInputFocused && e.target.tagName !== 'INPUT') {
                        input.focus();
                    }

                    switch(e.key) {
                        case 'Enter':
                            e.preventDefault();
                            this.executeCommand(input.value);
                            break;
                        
                        case 'ArrowUp':
                            e.preventDefault();
                            this.navigateHistory(-1);
                            break;
                        
                        case 'ArrowDown':
                            e.preventDefault();
                            this.navigateHistory(1);
                            break;
                        
                        case 'l':
                            if (e.ctrlKey) {
                                e.preventDefault();
                                this.clearScreen();
                            }
                            break;
                        
                        case 'c':
                            if (e.ctrlKey) {
                                e.preventDefault();
                                this.interruptCommand();
                            }
                            break;
                        
                        case 'Tab':
                            e.preventDefault();
                            this.autoComplete(input.value);
                            break;
                    }
                });

                // Click to focus input, but allow text selection
                document.addEventListener('click', (e) => {
                    const input = document.getElementById('command-input');
                    if (!input) return;
                    
                    // Only focus if clicking in empty space or on the terminal itself
                    // Don't interfere with text selection
                    if (e.target === this.terminal && !window.getSelection().toString()) {
                        setTimeout(() => input.focus(), 0);
                    }
                });
            }

            async executeCommand(command) {
                if (!command.trim()) {
                    this.createInputLine();
                    return;
                }

                // Add to history
                this.commandHistory.push(command);
                this.historyIndex = this.commandHistory.length;

                // Show command in terminal
                this.addOutput(this.getPrompt() + command, 'command');

                // Remove current input line
                const currentInput = document.getElementById('command-input');
                if (currentInput) {
                    currentInput.parentElement.remove();
                }

                // Show loading status
                this.setStatus('Executing command...', 'loading');

                try {
                    const response = await fetch('terminal-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            command: command,
                            cwd: this.currentDir,
                            session: this.sessionId
                        })
                    });

                    const result = await response.json();

                    if (result.error) {
                        this.addOutput(result.error, 'error');
                    } else {
                        if (result.output) {
                            this.addOutput(result.output, 'output');
                        }
                        if (result.cwd) {
                            this.currentDir = result.cwd;
                        }
                    }

                } catch (error) {
                    this.addOutput(`Error: ${error.message}`, 'error');
                }

                this.setStatus('Ready');
                this.createInputLine();
            }

            addOutput(text, className = '') {
                const line = document.createElement('div');
                line.className = `terminal-line ${className}`;
                line.textContent = text;
                this.terminal.appendChild(line);
                this.scrollToBottom();
            }

            scrollToBottom() {
                this.terminal.scrollTop = this.terminal.scrollHeight;
            }

            navigateHistory(direction) {
                const input = document.getElementById('command-input');
                if (!input) return;

                this.historyIndex += direction;

                if (this.historyIndex < 0) {
                    this.historyIndex = 0;
                    return;
                }

                if (this.historyIndex >= this.commandHistory.length) {
                    this.historyIndex = this.commandHistory.length;
                    input.value = '';
                    return;
                }

                input.value = this.commandHistory[this.historyIndex];
                // Move cursor to end
                setTimeout(() => {
                    input.setSelectionRange(input.value.length, input.value.length);
                }, 0);
            }

            clearScreen() {
                this.terminal.innerHTML = '';
                this.createInputLine();
            }

            interruptCommand() {
                // For now, just show interrupted message
                this.addOutput('^C', 'error');
                const currentInput = document.getElementById('command-input');
                if (currentInput) {
                    currentInput.parentElement.remove();
                }
                this.createInputLine();
            }

            async autoComplete(partial) {
                // Basic tab completion for files/directories
                try {
                    const response = await fetch('terminal-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            command: 'ls -1a',
                            cwd: this.currentDir,
                            session: this.sessionId,
                            autocomplete: partial
                        })
                    });

                    const result = await response.json();
                    // Simple implementation - just log for now
                    console.log('Autocomplete:', result);
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
            }

            setStatus(message, className = '') {
                const statusLeft = document.getElementById('status-left');
                statusLeft.textContent = `OfflineBox Terminal - ${message}`;
                statusLeft.className = className;
            }

            async getCurrentDirectory() {
                try {
                    const response = await fetch('terminal-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            command: 'pwd',
                            session: this.sessionId
                        })
                    });

                    const result = await response.json();
                    if (result.output) {
                        this.currentDir = result.output.trim();
                    }
                } catch (error) {
                    console.error('Failed to get current directory:', error);
                }
            }
        }

        // Initialize terminal when page loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, initializing terminal...');
            try {
                const terminal = new WebTerminal();
                console.log('Terminal initialized successfully');
            } catch (error) {
                console.error('Failed to initialize terminal:', error);
                // Fallback: show a simple message
                document.getElementById('terminal').innerHTML = 'Error initializing terminal. Check console for details.';
            }
        });
    </script>
</body>
</html>
