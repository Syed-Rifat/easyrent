<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Fixed Sidebar -->
<div class="col-md-3 position-fixed" style="width: 25%; height: 100vh; overflow-y: auto;">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Admin Dashboard</h5>
        </div>
        <div class="card-body text-center mb-3">
            <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
            <h5><?php echo htmlspecialchars($admin_name); ?></h5>
            <p class="text-muted">Administrator</p>
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="users.php" class="list-group-item list-group-item-action <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
            <a href="properties.php" class="list-group-item list-group-item-action <?php echo $current_page === 'properties.php' ? 'active' : ''; ?>">
                <i class="fas fa-home me-2"></i> Manage Properties
            </a>
            <a href="bookings.php" class="list-group-item list-group-item-action <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i> Manage Bookings
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i> Settings
            </a>
            <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
</div>
<!-- Spacer for fixed sidebar -->
<div class="col-md-3"></div> 