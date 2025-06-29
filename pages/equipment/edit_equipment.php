<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can edit equipment
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$equipment_id = isset($_GET['id']) ? $_GET['id'] : null;
$success = '';
$error = '';

if (!$equipment_id) {
    header('Location: view_equipment.php');
    exit;
}

// Get equipment data
try {
    $stmt = $pdo->prepare("
        SELECT e.*, l.type as lab_type 
        FROM lab_equipments e 
        JOIN labs l ON e.lab_id = l.lab_id 
        WHERE e.equipment_id = ?
    ");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch();
    
    if (!$equipment) {
        header('Location: view_equipment.php?error=Equipment not found');
        exit;
    }
} catch (Exception $e) {
    header('Location: view_equipment.php?error=Database error');
    exit;
}

// Get all labs for dropdown
$labs = getAllLabs($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $lab_id = $_POST['lab_id'];
    $quantity = $_POST['quantity'];
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    if (empty($name) || empty($lab_id) || empty($quantity)) {
        $error = "Please fill in all required fields!";
    } elseif ($quantity < 1) {
        $error = "Quantity must be at least 1!";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE lab_equipments 
                SET name = ?, lab_id = ?, quantity = ?, description = ?, status = ? 
                WHERE equipment_id = ?
            ");
            
            if ($stmt->execute([$name, $lab_id, $quantity, $description, $status, $equipment_id])) {
                // Send notification to Lab TOs about equipment update
                notifyLabTOsAboutEquipmentUpdate($pdo, $equipment_id, 'updated');
                
                logUserActivity($_SESSION['user_id'], 'edit_equipment', $lab_id);
                $success = "Equipment updated successfully!";
                
                // Refresh equipment data
                $stmt = $pdo->prepare("
                    SELECT e.*, l.type as lab_type 
                    FROM lab_equipments e 
                    JOIN labs l ON e.lab_id = l.lab_id 
                    WHERE e.equipment_id = ?
                ");
                $stmt->execute([$equipment_id]);
                $equipment = $stmt->fetch();
            } else {
                $error = "Failed to update equipment!";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Edit Equipment';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Equipment</h1>
        <div class="header-actions">
            <a href="view_equipment.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Equipment
            </a>
            <a href="delete_equipment.php?id=<?php echo $equipment['equipment_id']; ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this equipment?')">
                <i class="fas fa-trash"></i> Delete Equipment
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
    
    <!-- Equipment Info Card -->
    <div class="equipment-info-card">
        <h3><i class="fas fa-info-circle"></i> Equipment Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <strong>Equipment ID:</strong> <?php echo $equipment['equipment_id']; ?>
            </div>
            <div class="info-item">
                <strong>Current Lab:</strong> <?php echo htmlspecialchars($equipment['lab_type']); ?>
            </div>
            <div class="info-item">
                <strong>Current Status:</strong> <?php echo getStatusBadge($equipment['status']); ?>
            </div>
            <div class="info-item">
                <strong>Current Quantity:</strong> <?php echo $equipment['quantity']; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <form method="POST" id="editEquipmentForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> Equipment Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($equipment['name']); ?>" 
                           placeholder="e.g., Digital Oscilloscope" required>
                </div>
                
                <div class="form-group">
                    <label for="lab_id"><i class="fas fa-building"></i> Laboratory *</label>
                    <select id="lab_id" name="lab_id" required>
                        <option value="">Select Laboratory</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?php echo $lab['lab_id']; ?>" 
                                    <?php echo $equipment['lab_id'] == $lab['lab_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lab['type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quantity"><i class="fas fa-hashtag"></i> Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" 
                           value="<?php echo htmlspecialchars($equipment['quantity']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                    <select id="status" name="status">
                        <option value="working" <?php echo $equipment['status'] == 'working' ? 'selected' : ''; ?>>Working</option>
                        <option value="under repair" <?php echo $equipment['status'] == 'under repair' ? 'selected' : ''; ?>>Under Repair</option>
                        <option value="not working" <?php echo $equipment['status'] == 'not working' ? 'selected' : ''; ?>>Not Working</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Additional details about the equipment..."><?php echo htmlspecialchars($equipment['description']); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Equipment
                </button>
                <a href="view_equipment.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editEquipmentForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const quantity = document.getElementById('quantity').value;
    const lab_id = document.getElementById('lab_id').value;
    
    if (!name) {
        e.preventDefault();
        alert('Please enter equipment name');
        return;
    }
    
    if (!lab_id) {
        e.preventDefault();
        alert('Please select a laboratory');
        return;
    }
    
    if (quantity < 1) {
        e.preventDefault();
        alert('Quantity must be at least 1');
        return;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
