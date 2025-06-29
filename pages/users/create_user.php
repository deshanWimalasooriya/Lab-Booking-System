<?php
require_once '../../config/config.php';
requireLogin();

// Only Lecture in Charge can create users
if (!hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetch()['count'];
            
            if ($exists > 0) {
                $error = "Email address already exists!";
            } else {
                // Check if created_at column exists
                $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'")->fetchAll();
                $has_created_at = !empty($columns);
                
                if ($has_created_at) {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role) 
                        VALUES (?, ?, ?, ?)
                    ");
                }
                
                if ($stmt->execute([$name, $email, $password, $role])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Create notification for the new user
                    createNotification(
                        $pdo,
                        $user_id,
                        'system',
                        'Welcome to Lab Booking System',
                        'Your account has been created successfully. You can now access the system with your credentials.',
                        null
                    );
                    
                    logUserActivity($_SESSION['user_id'], 'create_user');
                    header('Location: manage_users.php?success=User created successfully');
                    exit;
                } else {
                    $error = "Failed to create user!";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Create User';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-plus"></i> Create New User</h1>
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
    
    <div class="card">
        <form method="POST" id="createUserForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                           placeholder="Enter full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                           placeholder="Enter email address" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter password (min 6 characters)" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm password" required minlength="6">
                </div>
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="student" <?php echo ($role ?? '') == 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="instructor" <?php echo ($role ?? '') == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                    <option value="lab_to" <?php echo ($role ?? '') == 'lab_to' ? 'selected' : ''; ?>>Lab Technical Officer</option>
                    <option value="lecture_in_charge" <?php echo ($role ?? '') == 'lecture_in_charge' ? 'selected' : ''; ?>>Lecture in Charge</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <!-- Role Information -->
    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Role Permissions</h3>
        <div class="role-info">
            <div class="role-item">
                <strong>Student:</strong> View lab schedules and laboratory information only
            </div>
            <div class="role-item">
                <strong>Instructor:</strong> Create and manage lab bookings
            </div>
            <div class="role-item">
                <strong>Lab TO:</strong> Approve bookings, manage equipment and maintenance
            </div>
            <div class="role-item">
                <strong>Lecture in Charge:</strong> Full system access including user management
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const email = document.getElementById('email').value;
    const name = document.getElementById('name').value.trim();
    const role = document.getElementById('role').value;
    
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
    
    if (!role) {
        e.preventDefault();
        alert('Please select a role!');
        return;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
