class LogoutPopup {
    constructor() {
        this.popup = document.getElementById('logoutPopup');
        this.confirmBtn = document.getElementById('confirmLogout');
        this.cancelBtn = document.getElementById('cancelLogout');
        this.overlay = document.querySelector('.logout-popup-overlay');
        
        this.init();
    }
    
    init() {
        // Only initialize if popup elements exist
        if (!this.popup || !this.confirmBtn || !this.cancelBtn) {
            console.warn('Logout popup elements not found');
            return;
        }
        
        // Attach event listeners to logout links
        this.attachLogoutListeners();
        
        // Attach popup button listeners
        this.confirmBtn.addEventListener('click', () => this.confirmLogout());
        this.cancelBtn.addEventListener('click', () => this.hidePopup());
        this.overlay.addEventListener('click', () => this.hidePopup());
        
        // ESC key to close popup
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.popup.classList.contains('show')) {
                this.hidePopup();
            }
        });
    }
    
    attachLogoutListeners() {
        // Find all logout links (more specific selector to avoid conflicts)
        const logoutLinks = document.querySelectorAll('a[href*="logout.php"], a[onclick*="logout"]');
        
        logoutLinks.forEach(link => {
            // Remove existing onclick handlers that might conflict
            link.removeAttribute('onclick');
            
            // Add new click handler
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.showPopup();
            });
        });
    }
    
    showPopup() {
        // Add show class with animation
        this.popup.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Focus on cancel button for accessibility
        setTimeout(() => {
            this.cancelBtn.focus();
        }, 300);
    }
    
    hidePopup() {
        // Remove show class
        this.popup.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    confirmLogout() {
        // Show loading state
        this.confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
        this.confirmBtn.disabled = true;
        this.cancelBtn.disabled = true;
        
        // Add fade out animation
        this.popup.classList.add('logging-out');
        
        // Proceed to logout after animation
        setTimeout(() => {
            // Determine correct logout path
            const logoutPath = this.getLogoutPath();
            window.location.href = logoutPath;
        }, 1000);
    }
    
    getLogoutPath() {
        const currentPath = window.location.pathname;
        
        // Determine the correct path to logout.php based on current location
        if (currentPath.includes('/pages/')) {
            return '../../logout.php';
        }
        return 'logout.php';
    }
}

// Initialize logout popup when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new LogoutPopup();
});
