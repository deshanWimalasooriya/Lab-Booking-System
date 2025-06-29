class NotificationSystem {
    constructor() {
        this.panel = document.getElementById('notificationPanel');
        this.toggle = document.getElementById('notificationToggle');
        this.badge = document.getElementById('notificationBadge');
        this.list = document.getElementById('notificationList');
        this.markAllBtn = document.getElementById('markAllRead');
        
        if (!this.panel || !this.toggle || !this.badge || !this.list) {
            return;
        }
        
        this.init();
        this.preventTestButtons(); // Add this line
    }
    
    init() {
        // Toggle notification panel
        this.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.togglePanel();
        });
        
        // Mark all as read
        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
        
        // Close panel when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-dropdown')) {
                this.closePanel();
            }
        });
        
        // Load notifications on page load
        this.loadNotifications();
        
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }
    
    // Prevent test buttons from appearing
    preventTestButtons() {
        // Override any potential test button creation
        const originalCreateElement = document.createElement;
        document.createElement = function(tagName) {
            const element = originalCreateElement.call(document, tagName);
            
            if (tagName.toLowerCase() === 'button') {
                // Monitor for test button characteristics
                const originalSetAttribute = element.setAttribute;
                element.setAttribute = function(name, value) {
                    if (name === 'style' && value.includes('position: fixed') && value.includes('top: 10px')) {
                        return; // Don't set the style
                    }
                    return originalSetAttribute.call(this, name, value);
                };
                
                // Monitor text content
                Object.defineProperty(element, 'textContent', {
                    set: function(value) {
                        if (value && value.includes('Test Notification')) {
                            return; // Don't set test notification text
                        }
                        this.innerHTML = value;
                    },
                    get: function() {
                        return this.innerHTML;
                    }
                });
            }
            
            return element;
        };
        
        // Remove any existing test buttons
        setInterval(() => {
            const testButtons = document.querySelectorAll('button');
            testButtons.forEach(button => {
                if (button.textContent.includes('Test Notification') || 
                    button.textContent.includes('Test') && button.style.position === 'fixed') {
                    button.remove();
                }
            });
        }, 1000);
    }
    
    togglePanel() {
        if (this.panel.classList.contains('show')) {
            this.closePanel();
        } else {
            this.openPanel();
        }
    }
    
    openPanel() {
        this.panel.classList.add('show');
        this.loadNotifications();
    }
    
    closePanel() {
        this.panel.classList.remove('show');
    }
    
    loadNotifications() {
        const basePath = this.getBasePath();
        
        this.list.innerHTML = '<li class="notification-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</li>';
        
        fetch(`${basePath}api/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_notifications'
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                
                if (data.error) {
                    this.list.innerHTML = `<li class="notification-item no-notifications">
                                             <div class="notification-content">
                                                 <i class="fas fa-exclamation-triangle"></i>
                                                 <p>Error loading notifications</p>
                                             </div>
                                           </li>`;
                } else if (data.notifications) {
                    this.list.innerHTML = data.notifications;
                    this.updateBadge(data.unread_count);
                    this.attachItemListeners();
                }
            } catch (e) {
                this.list.innerHTML = `<li class="notification-item no-notifications">
                                         <div class="notification-content">
                                             <i class="fas fa-exclamation-triangle"></i>
                                             <p>Error parsing notifications</p>
                                         </div>
                                       </li>`;
            }
        })
        .catch(error => {
            this.list.innerHTML = `<li class="notification-item no-notifications">
                                     <div class="notification-content">
                                         <i class="fas fa-exclamation-triangle"></i>
                                         <p>Network error</p>
                                     </div>
                                   </li>`;
        });
    }
    
    updateBadge(count) {
        if (count > 0) {
            this.badge.textContent = count > 99 ? '99+' : count;
            this.badge.classList.remove('hidden');
        } else {
            this.badge.classList.add('hidden');
        }
    }
    
    attachItemListeners() {
        const items = this.list.querySelectorAll('.notification-item[data-id]');
        items.forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.id;
                this.markAsRead(notificationId, item);
            });
        });
    }
    
    markAsRead(notificationId, item) {
        if (item.classList.contains('read')) return;
        
        const basePath = this.getBasePath();
        
        fetch(`${basePath}api/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_as_read&notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                item.classList.remove('unread');
                item.classList.add('read');
                this.updateBadgeCount(-1);
            }
        });
    }
    
    markAllAsRead() {
        const basePath = this.getBasePath();
        
        fetch(`${basePath}api/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
            }
        });
    }
    
    updateBadgeCount(change) {
        const currentCount = parseInt(this.badge.textContent) || 0;
        const newCount = Math.max(0, currentCount + change);
        this.updateBadge(newCount);
    }
    
    getBasePath() {
        const path = window.location.pathname;
        if (path.includes('/pages/')) {
            return '../../';
        }
        return '';
    }
}

// Initialize notification system and prevent test buttons
document.addEventListener('DOMContentLoaded', function() {
    new NotificationSystem();
    
    // Additional safety: Remove any test buttons that might exist
    setInterval(() => {
        document.querySelectorAll('button').forEach(button => {
            if (button.textContent.includes('Test Notification') || 
                (button.style.position === 'fixed' && button.style.top === '10px')) {
                button.style.display = 'none';
                button.remove();
            }
        });
    }, 500);
});
