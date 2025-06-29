    </main>
    
    <!-- Logout Confirmation Popup -->
    <div id="logoutPopup" class="logout-popup">
        <div class="logout-popup-overlay"></div>
        <div class="logout-popup-content">
            <div class="logout-popup-header">
                <i class="fas fa-sign-out-alt logout-icon"></i>
                <h3>Confirm Logout</h3>
            </div>
            <div class="logout-popup-body">
                <p>Are you sure you want to logout from Lab Booking System?</p>
                <small>You will need to login again to access the system.</small>
            </div>
            <div class="logout-popup-buttons">
                <button id="confirmLogout" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </button>
                <button id="cancelLogout" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; 2025 Lab Booking System. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="<?php echo isset($js_path) ? $js_path : 'assets/js/'; ?>main.js"></script>
    <script src="<?php echo isset($js_path) ? $js_path : 'assets/js/'; ?>notifications.js"></script>
    <script src="<?php echo isset($js_path) ? $js_path : 'assets/js/'; ?>logout-popup.js"></script>
</body>
</html>
