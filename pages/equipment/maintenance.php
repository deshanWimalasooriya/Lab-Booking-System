<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

// Handle status updates
if (isset($_GET['update']) && isset($_GET['id']) && isset($_GET['status'])) {
    $equipment_id = $_GET['id'];
    $new_status = $_GET['status'];
    
    $allowed_statuses = ['working', 'under repair', 'not working'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $pdo->prepare("UPDATE lab_equipments SET status = ? WHERE equipment_id = ?");
        $stmt->execute([$new_status, $equipment_id]);
        
        logUserActivity($_SESSION['user_id'], 'update_equipment_status');
        header('Location: maintenance.php?success=Equipment status updated successfully');
        exit;
    }
}

// Get equipment needing maintenance
$stmt = $pdo->query("
    SELECT e.*, l.type as lab_type 
    FROM lab_equipments e 
    JOIN labs l ON e.lab_id = l.lab_id 
    WHERE e.status IN ('under repair', 'not working')
    ORDER BY 
        CASE e.status 
            WHEN 'not working' THEN 1 
            WHEN 'under repair' THEN 2 
            ELSE 3 
        END, l.type, e.name
");
$maintenance_equipment = $stmt->fetchAll();

$page_title = 'Equipment Maintenance';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';

// Handle quick status changes from edit page
if (isset($_GET['action']) && isset($_GET['id'])) {
    $equipment_id = $_GET['id'];
    $action = $_GET['action'];
    
    $status_map = [
        'repair' => 'under repair',
        'fixed' => 'working',
        'broken' => 'not working'
    ];
    
    if (array_key_exists($action, $status_map)) {
        $new_status = $status_map[$action];
        
        $stmt = $pdo->prepare("UPDATE lab_equipments SET status = ? WHERE equipment_id = ?");
        if ($stmt->execute([$new_status, $equipment_id])) {
            logUserActivity($_SESSION['user_id'], 'update_equipment_status');
            header('Location: edit_equipment.php?id=' . $equipment_id . '&success=Equipment status updated successfully');
            exit;
        }
    }
}

?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-wrench"></i> Equipment Maintenance</h1>
        <a href="view_equipment.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Equipment
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <?php if (empty($maintenance_equipment)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>All Equipment Working</h3>
                <p>Great! All equipment is currently in working condition.</p>
                <a href="view_equipment.php" class="btn btn-primary">
                    <i class="fas fa-tools"></i> View All Equipment
                </a>
            </div>
        <?php else: ?>
            <div class="maintenance-header">
                <h2>Equipment Requiring Attention</h2>
                <p>Total items: <?php echo count($maintenance_equipment); ?></p>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Laboratory</th>
                            <th>Quantity</th>
                            <th>Current Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance_equipment as $item): ?>
                        <tr class="<?php echo $item['status'] == 'not working' ? 'priority-high' : 'priority-medium'; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <br><small>ID: #<?php echo $item['equipment_id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['lab_type']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo getStatusBadge($item['status']); ?></td>
                            <td><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($item['status'] == 'not working'): ?>
                                        <a href="?update=1&id=<?php echo $item['equipment_id']; ?>&status=under repair" 
                                           class="btn btn-sm btn-warning"
                                           onclick="return confirm('Mark this equipment as under repair?')">
                                            <i class="fas fa-wrench"></i> Start Repair
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($item['status'] == 'under repair'): ?>
                                        <a href="?update=1&id=<?php echo $item['equipment_id']; ?>&status=working" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Mark this equipment as working?')">
                                            <i class="fas fa-check"></i> Mark Fixed
                                        </a>
                                        
                                        <a href="?update=1&id=<?php echo $item['equipment_id']; ?>&status=not working" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Mark this equipment as not working?')">
                                            <i class="fas fa-times"></i> Cannot Fix
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="edit_equipment.php?id=<?php echo $item['equipment_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Stats -->
    <div class="maintenance-stats">
        <?php
        $not_working = array_filter($maintenance_equipment, function($item) { return $item['status'] == 'not working'; });
        $under_repair = array_filter($maintenance_equipment, function($item) { return $item['status'] == 'under repair'; });
        ?>
        
        <div class="stat-card priority-high">
            <h3><?php echo count($not_working); ?></h3>
            <p>Not Working</p>
        </div>
        
        <div class="stat-card priority-medium">
            <h3><?php echo count($under_repair); ?></h3>
            <p>Under Repair</p>
        </div>
    </div>

    
</div>

<?php include '../../includes/footer.php'; ?>
