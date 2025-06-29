<?php
require_once '../../config/config.php';
requireLogin();

// Only Lecture in Charge can edit users
if (!hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$user_id = isset($_GET['id']) ? $_GET['id'] : null;
$success = '';
$error = '';

if (!$user_id || !is_numeric($user_id)) {
    header('Location: manage_users.php?error=Invalid user ID');
    exit;
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: manage_users.php?error=User not found');
        exit;
    }
} catch (Exception $e) {
    header('Location: manage_users.php?error=Database error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($role)) {
        $error = "Please fill in all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        try {
            // Check if email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            $exists = $stmt->fetch()['count'];
            
            if ($exists > 0) {
                $error = "Email address already exists!";
            } else {
                if (!empty($new_password)) {
                    // Update with new password
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE user_id = ?");
                    $result = $stmt->execute([$name, $email, $role, $new_password, $user_id]);
                } else {
                    // Update without password change
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
                    $result = $stmt->execute([$name, $email, $role, $user_id]);
                }
                
                if ($result) {
                    // Create notification for the updated user
                    createNotification(
                        $pdo,
                        $user_id,
                        'system',
                        'Account Updated',
                        'Your account information has been updated by the administrator.',
                        null
                    );
                    
                    logUserActivity($_SESSION['user_id'], 'edit_user');
                    header('Location: manage_users.php?success=User updated successfully');
                    exit;
                } else {
                    $error = "Failed to update user!";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Edit User';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-edit"></i> Edit User</h1>
        <a href="manage_users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- User Info Card -->
    <div class="user-info-card">
        <h3><i class="fas fa-info-circle"></i> User Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <strong>User ID:</strong> <?php echo $user['user_id']; ?>
            </div>
            <div class="info-item">
                <strong>Current Role:</strong> 
                <span class="role-badge role-<?php echo $user['role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                </span>
            </div>
            <div class="info-item">
                <strong>Created:</strong> 
                <?php 
                if (isset($user['created_at']) && $user['created_at']) {
                    echo date('M d, Y H:i', strtotime($user['created_at']));
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
            <div class="info-item">
                <strong>Status:</strong> 
                <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                    <span class="badge badge-info">Current User</span>
                <?php else: ?>
                    <span class="badge badge-success">Active</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <form method="POST" id="editUserForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                    <option value="lab_to" <?php echo $user['role'] == 'lab_to' ? 'selected' : ''; ?>>Lab Technical Officer</option>
                    <option value="lecture_in_charge" <?php echo $user['role'] == 'lecture_in_charge' ? 'selected' : ''; ?>>Lecture in Charge</option>
                </select>
            </div>
            
            <hr>
            
            <h4><i class="fas fa-lock"></i> Change Password (Optional)</h4>
            <p class="text-muted">Leave blank to keep current password</p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    
    if (!name) {
        e.preventDefault();
        alert('Please enter full name!');
        return;
    }
    
    if (!email || !email.includes('@')) {
        e.preventDefault();
        alert('Please enter a valid email address!');
        return;
    }
    
    if (newPassword && newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
    
    if (newPassword && newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
