<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can add labs
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$success = '';
$error = '';

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
            // Check if lab type already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM labs WHERE type = ?");
            $stmt->execute([$type]);
            $exists = $stmt->fetch()['count'];
            
            if ($exists > 0) {
                $error = "A laboratory with this type already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO labs (type, capacity, availability) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$type, $capacity, $availability])) {
                    $lab_id = $pdo->lastInsertId();
                    
                    // Notify Lab TOs about new lab
                    notifyLabTOsAboutLabUpdate($pdo, $lab_id, 'added');
                    
                    logUserActivity($_SESSION['user_id'], 'add_lab');
                    $success = "Laboratory added successfully!";
                    
                    // Clear form data
                    $type = $capacity = '';
                    $availability = 'available';
                } else {
                    $error = "Failed to add laboratory!";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Add Laboratory';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> Add New Laboratory</h1>
        <a href="view_labs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Laboratories
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
        <form method="POST" id="labForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="type"><i class="fas fa-tag"></i> Laboratory Type *</label>
                    <input type="text" id="type" name="type" 
                           value="<?php echo htmlspecialchars($type ?? ''); ?>" 
                           placeholder="e.g., Electronics Lab, Computer Lab" required>
                    <small class="form-text">Enter a unique name for the laboratory</small>
                </div>
                
                <div class="form-group">
                    <label for="capacity"><i class="fas fa-users"></i> Capacity *</label>
                    <input type="number" id="capacity" name="capacity" min="1" max="100"
                           value="<?php echo htmlspecialchars($capacity ?? '20'); ?>" required>
                    <small class="form-text">Maximum number of students</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="availability"><i class="fas fa-info-circle"></i> Availability Status</label>
                <select id="availability" name="availability">
                    <option value="available" <?php echo ($availability ?? 'available') == 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="unavailable" <?php echo ($availability ?? '') == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
                <small class="form-text">Set initial availability status</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Laboratory
                </button>
                <a href="view_labs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lab Types Suggestions -->
    <div class="card">
        <h3><i class="fas fa-lightbulb"></i> Common Laboratory Types</h3>
        <div class="lab-suggestions">
            <span class="suggestion-tag" onclick="fillLabType('Electronics Lab')">Electronics Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Computer Lab')">Computer Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Physics Lab')">Physics Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Chemistry Lab')">Chemistry Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Biology Lab')">Biology Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Robotics Lab')">Robotics Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Networking Lab')">Networking Lab</span>
            <span class="suggestion-tag" onclick="fillLabType('Mechanics Lab')">Mechanics Lab</span>
        </div>
    </div>
</div>

<script>
function fillLabType(type) {
    document.getElementById('type').value = type;
}

document.getElementById('labForm').addEventListener('submit', function(e) {
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
