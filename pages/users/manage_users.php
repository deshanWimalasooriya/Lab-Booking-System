<?php
require_once '../../config/config.php';
requireLogin();

// Only Lecture in Charge can manage users
if (!hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$success = '';
$error = '';

// Handle success/error messages from URL
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent deleting self
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                if ($stmt->execute([$user_id])) {
                    logUserActivity($_SESSION['user_id'], 'delete_user');
                    header('Location: manage_users.php?success=User deleted successfully');
                    exit;
                } else {
                    $error = "Failed to delete user!";
                }
            } else {
                $error = "User not found!";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY user_id DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Failed to load users: " . $e->getMessage();
    $users = [];
}

$page_title = 'Manage Users';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
        <a href="create_user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New User
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
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Users Found</h3>
                <p>No users available in the system.</p>
                <a href="create_user.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create First User
                </a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if (isset($user['created_at']) && $user['created_at']) {
                                    echo date('M d, Y', strtotime($user['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete user: <?php echo htmlspecialchars($user['name']); ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- User Statistics -->
    <?php if (!empty($users)): ?>
    <div class="user-stats">
        <?php
        $role_counts = [];
        foreach ($users as $user) {
            $role_counts[$user['role']] = ($role_counts[$user['role']] ?? 0) + 1;
        }
        ?>
        
        <div class="stat-card">
            <h3><?php echo count($users); ?></h3>
            <p>Total Users</p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $role_counts['instructor'] ?? 0; ?></h3>
            <p>Instructors</p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $role_counts['student'] ?? 0; ?></h3>
            <p>Students</p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $role_counts['lab_to'] ?? 0; ?></h3>
            <p>Lab TOs</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
