<?php
require_once '../../config/config.php';
requireLogin();

$lab_id = isset($_GET['lab_id']) ? $_GET['lab_id'] : null;
$labs = getAllLabs($pdo);

// Get equipment based on lab filter
if ($lab_id) {
    $equipment = getEquipmentByLab($pdo, $lab_id);
    $stmt = $pdo->prepare("SELECT type FROM labs WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    $lab_name = $stmt->fetch()['type'] ?? 'Unknown Lab';
} else {
    $stmt = $pdo->query("
        SELECT e.*, l.type as lab_type 
        FROM lab_equipments e 
        JOIN labs l ON e.lab_id = l.lab_id 
        ORDER BY l.type, e.name
    ");
    $equipment = $stmt->fetchAll();
    $lab_name = 'All Laboratories';
}

$page_title = 'Equipment Management';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-tools"></i> Equipment - <?php echo htmlspecialchars($lab_name); ?></h1>
        <div class="header-actions">
            <?php if (hasRole(ROLE_LAB_TO) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                <a href="add_equipment.php<?php echo $lab_id ? '?lab_id=' . $lab_id : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Equipment
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (hasRole(ROLE_STUDENT)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You are viewing equipment in read-only mode. For equipment issues, contact the Lab Technical Officer.
        </div>
    <?php endif; ?>

    <?php
// Handle success/error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

    
    <!-- Lab Filter -->
    <div class="card">
        <div class="filter-section">
            <label for="lab_filter"><i class="fas fa-filter"></i> Filter by Laboratory:</label>
            <select id="lab_filter" onchange="filterByLab(this.value)">
                <option value="">All Laboratories</option>
                <?php foreach ($labs as $lab): ?>
                    <option value="<?php echo $lab['lab_id']; ?>" 
                            <?php echo $lab_id == $lab['lab_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lab['type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="card">
        <?php if (empty($equipment)): ?>
            <div class="empty-state">
                <i class="fas fa-tools"></i>
                <h3>No Equipment Found</h3>
                <p>No equipment available in the selected laboratory.</p>
                <?php if (hasRole(ROLE_LAB_TO) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                    <a href="add_equipment.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Equipment
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="equipment-grid">
                <?php foreach ($equipment as $item): ?>
                    <div class="equipment-card">
                        <div class="equipment-header">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <?php echo getStatusBadge($item['status']); ?>
                        </div>
                        
                        <div class="equipment-info">
                            <?php if (!$lab_id): ?>
                                <div class="info-item">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo htmlspecialchars($item['lab_type']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <i class="fas fa-hashtag"></i>
                                <span>Quantity: <?php echo $item['quantity']; ?></span>
                            </div>
                            
                            <?php if ($item['description']): ?>
                                <div class="info-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span><?php echo htmlspecialchars($item['description']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (hasRole(ROLE_LAB_TO) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                            <div class="equipment-actions">
                                <a href="edit_equipment.php?id=<?php echo $item['equipment_id']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                
                                <?php if ($item['status'] == 'working'): ?>
                                    <a href="maintenance.php?id=<?php echo $item['equipment_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-wrench"></i> Maintenance
                                    </a>
                                <?php endif; ?>
                                
                                <a href="delete_equipment.php?id=<?php echo $item['equipment_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this equipment?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByLab(labId) {
    if (labId) {
        window.location.href = 'view_equipment.php?lab_id=' + labId;
    } else {
        window.location.href = 'view_equipment.php';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
