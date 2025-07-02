class ModernChatSystem {
    constructor() {
        this.currentChat = null;
        this.pollingInterval = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadConversations();
        this.loadAllUsers();
        this.startPolling();
    }
    
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // New chat modal
        document.getElementById('newChatBtn')?.addEventListener('click', () => {
            this.showNewChatModal();
        });
        
        document.getElementById('modalCloseBtn')?.addEventListener('click', () => {
            this.hideNewChatModal();
        });
        
        document.querySelector('.modal-backdrop')?.addEventListener('click', () => {
            this.hideNewChatModal();
        });
        
        // Chat actions
        document.getElementById('closeChatBtn')?.addEventListener('click', () => {
            this.closeChat();
        });
        
        document.getElementById('sendMessageBtn')?.addEventListener('click', () => {
            this.sendMessage();
        });
        
        document.getElementById('messageInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        
        // Search functionality
        document.getElementById('userSearch')?.addEventListener('input', (e) => {
            this.searchConversations(e.target.value);
        });
    }
    
    switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
        
        // Show/hide lists
        if (tab === 'recent') {
            document.getElementById('conversationsList').classList.remove('hidden');
            document.getElementById('usersList').classList.add('hidden');
        } else {
            document.getElementById('conversationsList').classList.add('hidden');
            document.getElementById('usersList').classList.remove('hidden');
        }
    }
    
    loadConversations() {
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_conversations'
        })
        .then(response => response.json())
        .then(data => {
            if (data.conversations) {
                this.renderConversations(data.conversations);
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
    }
    
    loadAllUsers() {
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_users'
        })
        .then(response => response.json())
        .then(data => {
            if (data.users) {
                this.renderAllUsers(data.users);
                this.renderModalUsers(data.users);
            }
        })
        .catch(error => console.error('Error loading users:', error));
    }
    
    renderConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        if (conversations.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="welcome-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4>No conversations yet</h4>
                    <p>Start a new conversation to begin chatting</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = conversations.map(conv => `
            <div class="conversation-item" data-user-id="${conv.other_user_id}" data-user-name="${this.escapeHtml(conv.other_user_name)}">
                <div class="user-avatar medium">
                    <i class="fas fa-user"></i>
                    ${conv.unread_count > 0 ? '<div class="online-status"></div>' : ''}
                </div>
                <div class="conversation-info">
                    <div class="conversation-header">
                        <h4>${this.escapeHtml(conv.other_user_name)}</h4>
                        <span class="conversation-time">${this.formatTime(conv.last_message_time)}</span>
                    </div>
                    <div class="last-message">
                        ${conv.unread_count > 0 ? '<div class="unread-indicator"></div>' : ''}
                        <span>${this.escapeHtml(conv.last_message.substring(0, 40))}${conv.last_message.length > 40 ? '...' : ''}</span>
                        ${conv.unread_count > 0 ? `<div class="message-count">${conv.unread_count}</div>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(item.dataset.userId, item.dataset.userName);
                this.setActiveConversation(item);
            });
        });
    }
    
    renderAllUsers(users) {
        const container = document.getElementById('usersList');
        if (!container) return;
        
        container.innerHTML = users.map(user => `
            <div class="user-item" data-user-id="${user.user_id}" data-user-name="${this.escapeHtml(user.name)}">
                <div class="user-avatar medium">
                    <i class="fas fa-user"></i>
                    <div class="online-status"></div>
                </div>
                <div class="conversation-info">
                    <div class="conversation-header">
                        <h4>${this.escapeHtml(user.name)}</h4>
                        <span class="user-status online">Online</span>
                    </div>
                    <div class="last-message">
                        <span class="profile-role role-${user.role}">${user.role.replace('_', ' ')}</span>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(item.dataset.userId, item.dataset.userName);
                this.setActiveConversation(item);
            });
        });
    }
    
    renderModalUsers(users) {
        const container = document.getElementById('modalUsersList');
        if (!container) return;
        
        container.innerHTML = users.map(user => `
            <div class="modal-user-item" data-user-id="${user.user_id}" data-user-name="${this.escapeHtml(user.name)}">
                <div class="user-avatar medium">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h4>${this.escapeHtml(user.name)}</h4>
                    <span class="profile-role role-${user.role}">${user.role.replace('_', ' ')}</span>
                    <small>${this.escapeHtml(user.email)}</small>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.modal-user-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(item.dataset.userId, item.dataset.userName);
                this.hideNewChatModal();
            });
        });
    }
    
    openChat(userId, userName) {
        this.currentChat = { id: userId, name: userName };
        
        // Hide welcome, show chat
        document.getElementById('chatWelcome')?.classList.add('hidden');
        document.getElementById('activeChat')?.classList.remove('hidden');
        
        // Update chat header
        document.getElementById('activeChatUserName').textContent = userName;
        document.getElementById('activeChatUserStatus').textContent = 'Online';
        
        // Load messages
        this.loadMessages(userId);
    }
    
    closeChat() {
        this.currentChat = null;
        
        // Show welcome, hide chat
        document.getElementById('activeChat')?.classList.add('hidden');
        document.getElementById('chatWelcome')?.classList.remove('hidden');
        
        // Remove active states
        document.querySelectorAll('.conversation-item, .user-item').forEach(item => {
            item.classList.remove('active');
        });
    }
    
    setActiveConversation(element) {
        // Remove active from all
        document.querySelectorAll('.conversation-item, .user-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active to current
        element.classList.add('active');
    }
    
    loadMessages(userId) {
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_messages&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                this.renderMessages(data.messages);
            }
        })
        .catch(error => console.error('Error loading messages:', error));
    }
    
    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        if (messages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="welcome-icon">
                        <i class="fas fa-comment"></i>
                    </div>
                    <h4>No messages yet</h4>
                    <p>Start the conversation by sending a message</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id == window.currentUserId ? 'sent' : 'received'}">
                <div class="message-bubble">
                    <div class="message-text">${this.escapeHtml(msg.message)}</div>
                    <span class="message-time">${this.formatTime(msg.created_at)}</span>
                </div>
            </div>
        `).join('');
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }
    
    sendMessage() {
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        
        if (!input || !this.currentChat) return;
        
        const message = input.value.trim();
        if (!message) return;
        
        // Disable input
        input.disabled = true;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&receiver_id=${this.currentChat.id}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                this.loadMessages(this.currentChat.id);
                this.loadConversations();
            }
        })
        .catch(error => console.error('Error sending message:', error))
        .finally(() => {
            input.disabled = false;
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            input.focus();
        });
    }
    
    showNewChatModal() {
        document.getElementById('newChatModal').style.display = 'flex';
    }
    
    hideNewChatModal() {
        document.getElementById('newChatModal').style.display = 'none';
    }
    
    searchConversations(query) {
        const items = document.querySelectorAll('.conversation-item, .user-item');
        items.forEach(item => {
            const name = item.querySelector('h4').textContent.toLowerCase();
            if (name.includes(query.toLowerCase())) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    startPolling() {
        this.pollingInterval = setInterval(() => {
            if (this.currentChat) {
                this.loadMessages(this.currentChat.id);
            }
            this.loadConversations();
        }, 3000);
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) {
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays <= 7) {
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize modern chat system
document.addEventListener('DOMContentLoaded', function() {
    new ModernChatSystem();
});
