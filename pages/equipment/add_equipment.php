<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$labs = getAllLabs($pdo);
$success = '';
$error = '';
$selected_lab = isset($_GET['lab_id']) ? $_GET['lab_id'] : '';

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
                INSERT INTO lab_equipments (name, lab_id, quantity, description, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$name, $lab_id, $quantity, $description, $status])) {
                $equipment_id = $pdo->lastInsertId();
                
                // Send notification to Lab TOs about new equipment
                notifyLabTOsAboutEquipmentUpdate($pdo, $equipment_id, 'added');
                
                logUserActivity($_SESSION['user_id'], 'add_equipment', $lab_id);
                $success = "Equipment added successfully!";
                
                // Clear form data
                $name = $quantity = $description = '';
                $status = 'working';
            } else {
                $error = "Failed to add equipment!";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = 'Add Equipment';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> Add New Equipment</h1>
        <a href="view_equipment.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Equipment
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
        <form method="POST" id="equipmentForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> Equipment Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                           placeholder="e.g., Digital Oscilloscope" required>
                </div>
                
                <div class="form-group">
                    <label for="lab_id"><i class="fas fa-building"></i> Laboratory *</label>
                    <select id="lab_id" name="lab_id" required>
                        <option value="">Select Laboratory</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?php echo $lab['lab_id']; ?>" 
                                    <?php echo ($selected_lab == $lab['lab_id'] || ($lab_id ?? '') == $lab['lab_id']) ? 'selected' : ''; ?>>
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
                           value="<?php echo htmlspecialchars($quantity ?? '1'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                    <select id="status" name="status">
                        <option value="working" <?php echo ($status ?? 'working') == 'working' ? 'selected' : ''; ?>>Working</option>
                        <option value="under repair" <?php echo ($status ?? '') == 'under repair' ? 'selected' : ''; ?>>Under Repair</option>
                        <option value="not working" <?php echo ($status ?? '') == 'not working' ? 'selected' : ''; ?>>Not Working</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Additional details about the equipment..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Equipment
                </button>
                <a href="view_equipment.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('equipmentForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const quantity = document.getElementById('quantity').value;
    const labId = document.getElementById('lab_id').value;
    
    if (!name) {
        e.preventDefault();
        alert('Please enter equipment name');
        return;
    }
    
    if (!labId) {
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
