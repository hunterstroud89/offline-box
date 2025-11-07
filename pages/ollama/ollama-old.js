// Modern AI Chat Interface

let chatHistory = [];
let isGenerating = false;
let messageIdCounter = 0;
let currentChatId = null;
let allChats = [];

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
    
    // Initialize mobile sidebar state
    initializeMobileSidebar();
    
    // Load existing chats
    loadChatList();
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

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        console.log('Sidebar toggled, collapsed:', sidebar.classList.contains('collapsed'));
    }
}

function initializeMobileSidebar() {
    // Check if we're on a mobile screen
    if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebar) {
            // Start with sidebar collapsed on mobile
            sidebar.classList.add('collapsed');
            console.log('Mobile detected: Sidebar initialized as collapsed');
        }
    }
    
    // Add click-to-close functionality on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            const mobileToggle = document.querySelector('.mobile-only.sidebar-toggle');
            
            // Close sidebar if clicking outside of it (but not on the toggle button)
            if (sidebar && !sidebar.classList.contains('collapsed') && 
                !sidebar.contains(e.target) && 
                mobileToggle && !mobileToggle.contains(e.target)) {
                sidebar.classList.add('collapsed');
                console.log('Sidebar closed by clicking outside');
            }
        }
    });
    
    // Listen for window resize to handle orientation changes
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth <= 768) {
            // Mobile: ensure sidebar is collapsed
            if (sidebar && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                console.log('Resized to mobile: Sidebar collapsed');
            }
        } else {
            // Desktop: show sidebar
            if (sidebar) {
                sidebar.classList.remove('collapsed');
                console.log('Resized to desktop: Sidebar expanded');
            }
        }
    });
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
    
    // Save chat after adding user message (for new chats)
    if (chatHistory.length === 1) {
        saveCurrentChat().catch(error => {
            console.error('Failed to save new chat:', error);
        });
    }
    
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
            
            // Save chat after receiving response
            saveCurrentChat().catch(error => {
                console.error('Failed to save chat after response:', error);
            });
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
    currentChatId = null;
    
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
    
    // Update chat list to remove active state
    updateChatListActiveState(null);
}

// Chat Storage Functions
async function loadChatList() {
    console.log('Loading chat list...');
    try {
        const response = await fetch('chat-storage.php');
        const data = await response.json();
        
        console.log('Chat list response:', data);
        
        if (data.chats) {
            allChats = data.chats;
            console.log('Loaded', allChats.length, 'chats');
            updateChatHistoryUI();
        }
    } catch (error) {
        console.error('Failed to load chat list:', error);
    }
}

async function saveCurrentChat() {
    if (chatHistory.length === 0) {
        console.log('No chat history to save');
        return;
    }
    
    console.log('Saving chat with', chatHistory.length, 'messages');
    
    try {
        const chatData = {
            id: currentChatId,
            messages: chatHistory
        };
        
        console.log('Sending chat data:', chatData);
        
        const response = await fetch('chat-storage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(chatData)
        });
        
        const result = await response.json();
        console.log('Save response:', result);
        
        if (result.success && result.chat) {
            currentChatId = result.chat.id;
            console.log('Chat saved with ID:', currentChatId);
            await loadChatList(); // Refresh chat list
        } else {
            console.error('Failed to save chat:', result);
        }
    } catch (error) {
        console.error('Failed to save chat:', error);
    }
}

async function loadChat(chatId) {
    try {
        const response = await fetch('chat-storage.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: chatId })
        });
        
        const data = await response.json();
        
        if (data.chat) {
            currentChatId = chatId;
            chatHistory = data.chat.messages || [];
            
            // Hide welcome screen and show chat
            hideWelcomeScreen();
            
            // Clear and rebuild messages
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                messageIdCounter = 0;
                
                // Rebuild messages
                chatHistory.forEach(message => {
                    addMessage(message.content, message.role);
                });
            }
            
            // Update active state
            updateChatListActiveState(chatId);
        }
    } catch (error) {
        console.error('Failed to load chat:', error);
        showNotification('Failed to load chat', 'error');
    }
}

async function deleteChat(chatId) {
    try {
        const response = await fetch('chat-storage.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: chatId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // If deleting current chat, start new chat
            if (currentChatId === chatId) {
                startNewChat();
            }
            
            await loadChatList(); // Refresh chat list
            showNotification('Chat deleted', 'info');
        }
    } catch (error) {
        console.error('Failed to delete chat:', error);
        showNotification('Failed to delete chat', 'error');
    }
}

function updateChatHistoryUI() {
    const chatHistoryContainer = document.querySelector('.chat-history');
    if (!chatHistoryContainer) return;
    
    chatHistoryContainer.innerHTML = '';
    
    if (allChats.length === 0) {
        chatHistoryContainer.innerHTML = '<div class="no-chats">No previous chats</div>';
        return;
    }
    
    allChats.forEach(chat => {
        const chatItem = document.createElement('div');
        chatItem.className = `chat-item ${currentChatId === chat.id ? 'active' : ''}`;
        chatItem.onclick = () => loadChat(chat.id);
        
        const timeDiff = getTimeAgo(chat.updated_at);
        
        chatItem.innerHTML = `
            <div class="chat-preview">
                <span class="chat-title">${escapeHtml(chat.title)}</span>
                <span class="chat-time">${timeDiff}</span>
            </div>
            <button class="delete-chat-btn" onclick="event.stopPropagation(); deleteChat('${chat.id}')" title="Delete chat">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" stroke="currentColor" stroke-width="2"/>
                </svg>
            </button>
        `;
        
        chatHistoryContainer.appendChild(chatItem);
    });
}

function updateChatListActiveState(activeId) {
    const chatItems = document.querySelectorAll('.chat-item');
    chatItems.forEach(item => {
        item.classList.remove('active');
    });
    
    if (activeId) {
        const activeItem = document.querySelector(`[onclick*="${activeId}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
