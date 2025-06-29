<?php
require_once '../../config/config.php';
requireLogin();

$labs = getAllLabs($pdo);

$page_title = 'View Laboratories';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';

// Handle success/error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Laboratories</h1>
        <?php if (hasRole(ROLE_LAB_TO) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
            <a href="add_lab.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Laboratory
            </a>
        <?php endif; ?>
    </div>
    
    
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

    <?php if (hasRole(ROLE_STUDENT)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You are viewing laboratories in read-only mode. Contact your instructor for booking requests.
        </div>
    <?php endif; ?>
    
    <div class="labs-grid">
        <?php foreach ($labs as $lab): ?>
            <div class="lab-card">
                <div class="lab-header">
                    <h3><?php echo htmlspecialchars($lab['type']); ?></h3>
                    <?php echo getStatusBadge($lab['availability']); ?>
                </div>
                
                <div class="lab-info">
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span>Capacity: <?php echo $lab['capacity']; ?> students</span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-hashtag"></i>
                        <span>Lab ID: <?php echo $lab['lab_id']; ?></span>
                    </div>
                </div>
                
                <div class="lab-actions">
                    <?php if (hasRole(ROLE_STUDENT)): ?>
                        <!-- Students can only view equipment -->
                        <a href="../equipment/view_equipment.php?lab_id=<?php echo $lab['lab_id']; ?>" 
                           class="btn btn-sm btn-info">
                            <i class="fas fa-tools"></i> View Equipment
                        </a>
                    <?php else: ?>
                        <!-- Other roles have full access -->
                        <a href="../equipment/view_equipment.php?lab_id=<?php echo $lab['lab_id']; ?>" 
                           class="btn btn-sm btn-info">
                            <i class="fas fa-tools"></i> Equipment
                        </a>
                        
                        <?php if ($lab['availability'] == 'available' && (hasRole(ROLE_INSTRUCTOR) || hasRole(ROLE_LECTURE_IN_CHARGE))): ?>
                            <a href="../bookings/create_booking.php?lab_id=<?php echo $lab['lab_id']; ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-calendar-plus"></i> Book
                            </a>
                        <?php endif; ?>
                        
                        <?php if (hasRole(ROLE_LAB_TO) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                            <a href="edit_lab.php?id=<?php echo $lab['lab_id']; ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

