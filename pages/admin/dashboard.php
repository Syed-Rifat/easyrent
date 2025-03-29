<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];

// Get counts from database
$user_count_query = "SELECT COUNT(*) as total FROM users";
$property_count_query = "SELECT COUNT(*) as total FROM properties";
$booking_count_query = "SELECT COUNT(*) as total FROM bookings";

$user_count = $conn->query($user_count_query)->fetch_assoc()['total'];
$property_count = $conn->query($property_count_query)->fetch_assoc()['total'];
$booking_count = $conn->query($booking_count_query)->fetch_assoc()['total'];

// Get recent user registrations
$recent_users_query = "SELECT id, full_name, email, user_type, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users = $conn->query($recent_users_query);

// Get recent properties
$recent_properties_query = "SELECT p.id, p.title, p.price, p.location, p.status, u.full_name as landlord_name 
                           FROM properties p 
                           JOIN users u ON p.landlord_id = u.id 
                           ORDER BY p.created_at DESC LIMIT 5";
$recent_properties = $conn->query($recent_properties_query);
?>

<?php include_once "../includes/header.php"; ?>


<div class="container-fluid my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Manage Users
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Manage Properties
                    </a>
                    <a href="bookings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i> Manage Bookings
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Total Users</h6>
                                    <h2 class="mb-0"><?php echo $user_count; ?></h2>
                                </div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="users.php" class="text-white text-decoration-none small">View Details</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Total Properties</h6>
                                    <h2 class="mb-0"><?php echo $property_count; ?></h2>
                                </div>
                                <i class="fas fa-home fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="properties.php" class="text-white text-decoration-none small">View Details</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Total Bookings</h6>
                                    <h2 class="mb-0"><?php echo $booking_count; ?></h2>
                                </div>
                                <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="bookings.php" class="text-white text-decoration-none small">View Details</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent User Registrations</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_users->num_rows > 0): ?>
                                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($user['user_type'] == 'admin') ? 'bg-danger' : (($user['user_type'] == 'landlord') ? 'bg-primary' : 'bg-success'); ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent registrations</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="users.php" class="btn btn-sm btn-primary">View All Users</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Properties</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_properties->num_rows > 0): ?>
                                            <?php while ($property = $recent_properties->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($property['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($property['location']); ?></td>
                                                    <td>৳<?php echo number_format($property['price']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($property['status'] == 'available') ? 'bg-success' : (($property['status'] == 'rented') ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo ucfirst($property['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent properties</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="properties.php" class="btn btn-sm btn-primary">View All Properties</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 