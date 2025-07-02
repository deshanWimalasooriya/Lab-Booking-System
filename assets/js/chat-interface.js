class ChatInterface {
    constructor() {
        this.currentChat = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadUsers();
        this.loadConversations();
    }
    
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // Send message
        document.getElementById('sendBtn')?.addEventListener('click', () => {
            this.sendMessage();
        });
        
        document.getElementById('messageInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        
        // Close chat
        document.getElementById('closeChatBtn')?.addEventListener('click', () => {
            this.closeChat();
        });
    }
    
    switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
        
        if (tab === 'recent') {
            document.getElementById('recentChats').classList.remove('hidden');
            document.getElementById('allUsers').classList.add('hidden');
        } else {
            document.getElementById('recentChats').classList.add('hidden');
            document.getElementById('allUsers').classList.remove('hidden');
        }
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
            }
        });
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
        });
    }
    
    renderUsers(users) {
        const container = document.getElementById('allUsers');
        container.innerHTML = users.map(user => `
            <div class="user-item" data-user-id="${user.user_id}" data-user-name="${user.name}">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h4>${user.name}</h4>
                    <span class="user-status">Online</span>
                </div>
            </div>
        `).join('');
        
        container.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(item.dataset.userId, item.dataset.userName);
            });
        });
    }
    
    renderConversations(conversations) {
        const container = document.getElementById('recentChats');
        
        if (conversations.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <small>Start a new conversation to begin chatting</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = conversations.map(conv => `
            <div class="user-item" data-user-id="${conv.other_user_id}" data-user-name="${conv.other_user_name}">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h4>${conv.other_user_name}</h4>
                    <div class="last-message">${conv.last_message.substring(0, 30)}...</div>
                </div>
                ${conv.unread_count > 0 ? `<div class="unread-count">${conv.unread_count}</div>` : ''}
            </div>
        `).join('');
        
        container.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(item.dataset.userId, item.dataset.userName);
            });
        });
    }
    
    openChat(userId, userName) {
        this.currentChat = { id: userId, name: userName };
        
        document.getElementById('chatWelcome').classList.add('hidden');
        document.getElementById('activeChat').classList.remove('hidden');
        document.getElementById('chatUserName').textContent = userName;
        
        this.loadMessages(userId);
    }
    
    closeChat() {
        this.currentChat = null;
        document.getElementById('activeChat').classList.add('hidden');
        document.getElementById('chatWelcome').classList.remove('hidden');
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
        });
    }
    
    renderMessages(messages) {
        const container = document.getElementById('messagesArea');
        
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
            <div class="message ${msg.sender_id == window.currentUserId ? 'sent' : 'received'}">
                <div class="message-bubble">
                    <div class="message-text">${msg.message}</div>
                    <div class="message-time">${this.formatTime(msg.created_at)}</div>
                </div>
            </div>
        `).join('');
        
        container.scrollTop = container.scrollHeight;
    }
    
    sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || !this.currentChat) return;
        
        const sendBtn = document.getElementById('sendBtn');
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
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    new ChatInterface();
});
