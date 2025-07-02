class ChatSystem {
    constructor() {
        this.currentChatUser = null;
        this.messagePollingInterval = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadConversations();
        this.loadUsers();
        this.startMessagePolling();
    }
    
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.chat-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // New chat button
        const newChatBtn = document.getElementById('newChatBtn');
        if (newChatBtn) {
            newChatBtn.addEventListener('click', () => {
                this.showNewChatModal();
            });
        }
        
        // Close chat
        const closeChatBtn = document.getElementById('closeChatBtn');
        if (closeChatBtn) {
            closeChatBtn.addEventListener('click', () => {
                this.closeChat();
            });
        }
        
        // Send message
        const sendBtn = document.getElementById('sendMessageBtn');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => {
                this.sendMessage();
            });
        }
        
        // Enter key to send message
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });
        }
        
        // Search users
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('input', (e) => {
                this.searchUsers(e.target.value);
            });
        }
        
        // Modal close
        const modalClose = document.querySelector('.modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', () => {
                this.hideNewChatModal();
            });
        }
        
        // Click outside modal to close
        const modal = document.getElementById('newChatModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideNewChatModal();
                }
            });
        }
    }
    
    switchTab(tab) {
        document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
        const activeTab = document.querySelector(`[data-tab="${tab}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }
        
        document.querySelectorAll('.chat-list').forEach(list => list.classList.add('hidden'));
        const targetList = document.getElementById(tab === 'conversations' ? 'conversationsList' : 'usersList');
        if (targetList) {
            targetList.classList.remove('hidden');
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
        .catch(error => {
            console.error('Error loading conversations:', error);
        });
    }
    
    loadUsers() {
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_users'
        })
        .then(response => response.json())
        .then(data => {
            if (data.users) {
                this.renderUsers(data.users);
                this.renderNewChatUsers(data.users);
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
    }
    
    renderConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        if (conversations.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <small>Start a new chat to begin messaging</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = conversations.map(conv => `
            <div class="conversation-item" data-user-id="${conv.other_user_id}">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="conversation-info">
                    <div class="conversation-header">
                        <h4>${this.escapeHtml(conv.other_user_name)}</h4>
                        <span class="message-time">${this.formatTime(conv.last_message_time)}</span>
                    </div>
                    <div class="last-message">
                        <p>${this.escapeHtml(conv.last_message.substring(0, 50))}${conv.last_message.length > 50 ? '...' : ''}</p>
                        ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                const userName = item.querySelector('h4').textContent;
                this.openChat(userId, userName);
                
                // Mark as active
                container.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
    }
    
    renderUsers(users) {
        const container = document.getElementById('usersList');
        if (!container) return;
        
        container.innerHTML = users.map(user => `
            <div class="user-item" data-user-id="${user.user_id}">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h4>${this.escapeHtml(user.name)}</h4>
                    <span class="user-role role-${user.role}">${user.role.replace('_', ' ')}</span>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                const userName = item.querySelector('h4').textContent;
                this.openChat(userId, userName);
                
                // Mark as active
                container.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
    }
    
    renderNewChatUsers(users) {
        const container = document.getElementById('newChatUserList');
        if (!container) return;
        
        container.innerHTML = users.map(user => `
            <div class="user-select-item" data-user-id="${user.user_id}">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h4>${this.escapeHtml(user.name)}</h4>
                    <span class="user-role role-${user.role}">${user.role.replace('_', ' ')}</span>
                    <small>${this.escapeHtml(user.email)}</small>
                </div>
            </div>
        `).join('');
        
        // Bind click events
        container.querySelectorAll('.user-select-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                const userName = item.querySelector('h4').textContent;
                this.openChat(userId, userName);
                this.hideNewChatModal();
            });
        });
    }
    
    openChat(userId, userName) {
        this.currentChatUser = { id: userId, name: userName };
        
        const welcomeEl = document.getElementById('chatWelcome');
        const chatAreaEl = document.getElementById('chatArea');
        const userNameEl = document.getElementById('chatUserName');
        
        if (welcomeEl) welcomeEl.classList.add('hidden');
        if (chatAreaEl) chatAreaEl.classList.remove('hidden');
        if (userNameEl) userNameEl.textContent = userName;
        
        this.loadMessages(userId);
    }
    
    closeChat() {
        this.currentChatUser = null;
        
        const welcomeEl = document.getElementById('chatWelcome');
        const chatAreaEl = document.getElementById('chatArea');
        
        if (chatAreaEl) chatAreaEl.classList.add('hidden');
        if (welcomeEl) welcomeEl.classList.remove('hidden');
        
        // Remove active states
        document.querySelectorAll('.conversation-item, .user-item').forEach(item => {
            item.classList.remove('active');
        });
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
        .catch(error => {
            console.error('Error loading messages:', error);
        });
    }
    
    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        if (messages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comment"></i>
                    <p>No messages yet</p>
                    <small>Start the conversation!</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id == this.getCurrentUserId() ? 'sent' : 'received'}">
                <div class="message-content">
                    <p>${this.escapeHtml(msg.message)}</p>
                    <small class="message-time">${this.formatTime(msg.created_at)}</small>
                </div>
            </div>
        `).join('');
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }
    
    sendMessage() {
        const input = document.getElementById('messageInput');
        if (!input) return;
        
        const message = input.value.trim();
        
        if (!message || !this.currentChatUser) return;
        
        // Disable input while sending
        input.disabled = true;
        const sendBtn = document.getElementById('sendMessageBtn');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        fetch('../../api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&receiver_id=${this.currentChatUser.id}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                this.loadMessages(this.currentChatUser.id);
                this.loadConversations();
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        })
        .finally(() => {
            // Re-enable input
            input.disabled = false;
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
            input.focus();
        });
    }
    
    showNewChatModal() {
        const modal = document.getElementById('newChatModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    hideNewChatModal() {
        const modal = document.getElementById('newChatModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    searchUsers(query) {
        const users = document.querySelectorAll('#usersList .user-item');
        users.forEach(user => {
            const name = user.querySelector('h4').textContent.toLowerCase();
            if (name.includes(query.toLowerCase())) {
                user.style.display = 'flex';
            } else {
                user.style.display = 'none';
            }
        });
    }
    
    startMessagePolling() {
        this.messagePollingInterval = setInterval(() => {
            if (this.currentChatUser) {
                this.loadMessages(this.currentChatUser.id);
            }
            this.loadConversations();
        }, 5000); // Poll every 5 seconds
    }
    
    getCurrentUserId() {
        return window.currentUserId || 0;
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

// Initialize chat system
document.addEventListener('DOMContentLoaded', function() {
    new ChatSystem();
});
