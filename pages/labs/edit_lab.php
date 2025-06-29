<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can edit labs
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$lab_id = isset($_GET['id']) ? $_GET['id'] : null;
$success = '';
$error = '';

if (!$lab_id) {
    header('Location: view_labs.php');
    exit;
}

// Get lab data
try {
    $stmt = $pdo->prepare("SELECT * FROM labs WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    $lab = $stmt->fetch();
    
    if (!$lab) {
        header('Location: view_labs.php?error=Lab not found');
        exit;
    }
} catch (Exception $e) {
    header('Location: view_labs.php?error=Database error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = trim($_POST['type']);
    $capacity = $_POST['capacity'];
    $availability = $_POST['availability'];
    
    if (empty($type) || empty($capacity)) {
        $error = "Please fill in all required fields!";
    } elseif ($capacity < 1) {
        $error = "Capacity must be at least 1!";
    } else {
        try {
            // Check if lab type already exists (excluding current lab)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM labs WHERE type = ? AND lab_id != ?");
            $stmt->execute([$type, $lab_id]);
            $exists = $stmt->fetch()['count'];
            
            if ($exists > 0) {
                $error = "A laboratory with this type already exists!";
            } else {
                $stmt = $pdo->prepare("UPDATE labs SET type = ?, capacity = ?, availability = ? WHERE lab_id = ?");
                
                if ($stmt->execute([$type, $capacity, $availability, $lab_id])) {
                    // Notify Lab TOs about lab update
                    notifyLabTOsAboutLabUpdate($pdo, $lab_id, 'updated');
                    
                    logUserActivity($_SESSION['user_id'], 'edit_lab', $lab_id);
                    $success = "Laboratory updated successfully!";
                    
                    // Refresh lab data
                    $stmt = $pdo->prepare("SELECT * FROM labs WHERE lab_id = ?");
                    $stmt->execute([$lab_id]);
                    $lab = $stmt->fetch();
                } else {
                    $error = "Failed to update laboratory!";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Edit Laboratory';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Laboratory</h1>
        <div class="header-actions">
            <a href="view_labs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Laboratories
            </a>
            <a href="delete_lab.php?id=<?php echo $lab['lab_id']; ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this laboratory? This will also delete all associated bookings and equipment!')">
                <i class="fas fa-trash"></i> Delete Lab
            </a>
        </div>
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
    
    <div class="lab-info-card">
        <h3><i class="fas fa-info-circle"></i> Laboratory Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <strong>Lab ID:</strong> <?php echo $lab['lab_id']; ?>
            </div>
            <div class="info-item">
                <strong>Current Status:</strong> <?php echo getStatusBadge($lab['availability']); ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <form method="POST" id="editLabForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="type"><i class="fas fa-tag"></i> Laboratory Type *</label>
                    <input type="text" id="type" name="type" 
                           value="<?php echo htmlspecialchars($lab['type']); ?>" required>
                    <small class="form-text">Laboratory name/type</small>
                </div>
                
                <div class="form-group">
                    <label for="capacity"><i class="fas fa-users"></i> Capacity *</label>
                    <input type="number" id="capacity" name="capacity" min="1" max="100"
                           value="<?php echo htmlspecialchars($lab['capacity']); ?>" required>
                    <small class="form-text">Maximum number of students</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="availability"><i class="fas fa-info-circle"></i> Availability Status</label>
                <select id="availability" name="availability">
                    <option value="available" <?php echo $lab['availability'] == 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="unavailable" <?php echo $lab['availability'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
                <small class="form-text">Change availability status</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Laboratory
                </button>
                <a href="view_labs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editLabForm').addEventListener('submit', function(e) {
    const type = document.getElementById('type').value.trim();
    const capacity = document.getElementById('capacity').value;
    
    if (!type) {
        e.preventDefault();
        alert('Please enter laboratory type');
        return;
    }
    
    if (capacity < 1 || capacity > 100) {
        e.preventDefault();
        alert('Capacity must be between 1 and 100');
        return;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
