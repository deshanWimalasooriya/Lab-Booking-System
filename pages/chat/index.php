<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Chat System';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>
<link rel="stylesheet" href="../../assets/css/chat-styles.css">

<div class="chat-page-container">
    <div class="chat-interface">
        <!-- Left Sidebar - User List -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h3>Lab Chat</h3>
                <button class="new-chat-btn" id="newChatBtn">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <div class="search-section">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users..." id="userSearch">
                </div>
            </div>
            
            <div class="chat-tabs">
                <button class="tab-button active" data-tab="recent">Recent</button>
                <button class="tab-button" data-tab="all">All Users</button>
            </div>
            
            <div class="users-container">
                <div class="user-list" id="recentChats">
                    <!-- Recent conversations will load here -->
                </div>
                <div class="user-list hidden" id="allUsers">
                    <!-- All users will load here -->
                </div>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Welcome Screen -->
            <div class="chat-welcome" id="chatWelcome">
                <div class="welcome-content">
                    <div class="welcome-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2>Welcome to Lab Chat</h2>
                    <p>Select a conversation to start messaging</p>
                    <p class="subtitle">Connect with instructors, students, and lab staff</p>
                </div>
            </div>
            
            <!-- Active Chat Area -->
            <div class="active-chat hidden" id="activeChat">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="chat-user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h4 id="chatUserName">User Name</h4>
                            <span class="user-status">Online</span>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="chat-action-btn" title="Video Call">
                            <i class="fas fa-video"></i>
                        </button>
                        <button class="chat-action-btn" title="Voice Call">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="chat-action-btn close-chat" id="closeChatBtn" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="messages-area" id="messagesArea">
                    <!-- Messages will be loaded here -->
                </div>
                
                <!-- Message Input Section -->
                <div class="message-input-area">
                    <div class="input-wrapper">
                        <button class="attach-btn" title="Attach File">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="text" id="messageInput" placeholder="Type a message..." maxlength="1000">
                        <button class="emoji-btn" title="Emoji">
                            <i class="fas fa-smile"></i>
                        </button>
                        <button class="send-btn" id="sendBtn" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar - User Profile -->
        <div class="user-profile" id="userProfile">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3 id="profileUserName">User Name</h3>
                <span class="profile-role" id="profileUserRole">Role</span>
            </div>
            
            <div class="profile-actions">
                <button class="profile-btn primary">
                    <i class="fas fa-comment"></i>
                    Message
                </button>
                <button class="profile-btn">
                    <i class="fas fa-video"></i>
                    Video Call
                </button>
            </div>
            
            <div class="profile-info">
                <h4>Contact Info</h4>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span id="profileUserEmail">email@example.com</span>
                </div>
                
                <h4>Shared Files</h4>
                <div class="shared-files">
                    <div class="file-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Lab_Report.pdf</span>
                    </div>
                    <div class="file-item">
                        <i class="fas fa-image"></i>
                        <span>Equipment_Photo.jpg</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Set current user data -->
<script>
    window.currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    window.currentUserName = "<?php echo htmlspecialchars($_SESSION['name']); ?>";
</script>
<script src="../../assets/js/chat-interface.js"></script>

<?php include '../../includes/footer.php'; ?>
