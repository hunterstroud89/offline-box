// Modern AI Chat Interface

let chatHistory = [];
let isGenerating = false;
let messageIdCounter = 0;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    
    if (!userInput || !sendBtn) return;
    
    // Handle Enter key (Shift+Enter for new line)
    userInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Auto-resize textarea
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        
        // Update character count
        updateCharacterCount();
    });
    
    // Initialize character count
    updateCharacterCount();
});

function updateCharacterCount() {
    const userInput = document.getElementById('user-input');
    const charCount = document.querySelector('.character-count');
    
    if (userInput && charCount) {
        const count = userInput.value.length;
        charCount.textContent = `${count} / 4000`;
        
        if (count > 3800) {
            charCount.style.color = 'var(--error)';
        } else {
            charCount.style.color = 'var(--text-muted)';
        }
    }
}

function sendQuickMessage(message) {
    const userInput = document.getElementById('user-input');
    if (userInput) {
        userInput.value = message;
        sendMessage();
    }
}

async function sendMessage() {
    const userInput = document.getElementById('user-input');
    const message = userInput.value.trim();
    
    if (!message || isGenerating) return;
    
    const modelSelect = document.getElementById('model-select');
    const selectedModel = modelSelect?.value;
    
    if (!selectedModel) {
        showNotification('Please select a model first', 'error');
        return;
    }
    
    // Hide welcome screen and show chat
    hideWelcomeScreen();
    
    // Add user message
    addMessage(message, 'user');
    chatHistory.push({ role: 'user', content: message });
    
    // Clear input and reset height
    userInput.value = '';
    userInput.style.height = 'auto';
    updateCharacterCount();
    
    // Show thinking indicator
    const thinkingId = addMessage('Thinking...', 'assistant', true);
    setGenerating(true);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                model: selectedModel,
                messages: chatHistory,
                stream: false
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Remove thinking message
        removeMessage(thinkingId);
        
        if (data.error) {
            addMessage(`Error: ${data.error}`, 'assistant', false, true);
        } else if (data.message && data.message.content) {
            const assistantMessage = data.message.content;
            addMessage(assistantMessage, 'assistant');
            chatHistory.push({ role: 'assistant', content: assistantMessage });
        } else {
            addMessage('Sorry, I received an unexpected response.', 'assistant', false, true);
        }
        
    } catch (error) {
        console.error('Error:', error);
        removeMessage(thinkingId);
        addMessage(`Connection error: ${error.message}`, 'assistant', false, true);
    } finally {
        setGenerating(false);
        userInput.focus();
    }
}

function hideWelcomeScreen() {
    const welcomeScreen = document.getElementById('welcome-screen');
    const chatMessages = document.getElementById('chat-messages');
    
    if (welcomeScreen && chatMessages) {
        welcomeScreen.style.display = 'none';
        chatMessages.style.display = 'flex';
    }
}

function addMessage(content, role, isThinking = false, isError = false) {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) return null;
    
    const messageId = ++messageIdCounter;
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role}`;
    messageDiv.setAttribute('data-message-id', messageId);
    
    if (isThinking) {
        messageDiv.classList.add('thinking');
    }
    if (isError) {
        messageDiv.classList.add('error');
    }
    
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    avatar.textContent = role === 'user' ? 'You' : 'AI';
    
    const messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    
    if (role === 'assistant' && !isThinking) {
        messageContent.innerHTML = formatMessageContent(content);
    } else {
        messageContent.textContent = content;
    }
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(messageContent);
    messagesContainer.appendChild(messageDiv);
    
    // Scroll to bottom
    scrollToBottom();
    
    return messageId;
}

function removeMessage(messageId) {
    if (!messageId) return;
    
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.remove();
    }
}

function formatMessageContent(content) {
    // Basic markdown-like formatting
    let formatted = content;
    
    // Code blocks
    formatted = formatted.replace(/```(\w+)?\n([\s\S]*?)\n```/g, '<pre><code>$2</code></pre>');
    
    // Inline code
    formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
    
    // Bold text
    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Line breaks
    formatted = formatted.replace(/\n/g, '<br>');
    
    return formatted;
}

function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

function setGenerating(generating) {
    isGenerating = generating;
    const sendBtn = document.getElementById('send-btn');
    const sendIcon = document.getElementById('send-icon');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    if (sendBtn && sendIcon && loadingSpinner) {
        sendBtn.disabled = generating;
        sendIcon.style.display = generating ? 'none' : 'block';
        loadingSpinner.style.display = generating ? 'flex' : 'none';
    }
}

function startNewChat() {
    chatHistory = [];
    
    const messagesContainer = document.getElementById('messages-container');
    const welcomeScreen = document.getElementById('welcome-screen');
    const chatMessages = document.getElementById('chat-messages');
    
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    if (welcomeScreen && chatMessages) {
        welcomeScreen.style.display = 'flex';
        chatMessages.style.display = 'none';
    }
    
    const userInput = document.getElementById('user-input');
    if (userInput) {
        userInput.focus();
    }
}

function loadChat(chatId) {
    // Placeholder for loading different chat sessions
    console.log('Loading chat:', chatId);
}

function showNotification(message, type = 'info') {
    // Create a simple notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    if (type === 'error') {
        notification.style.background = 'var(--error)';
    } else {
        notification.style.background = 'var(--accent)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS for notification animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
