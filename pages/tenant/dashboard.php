<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get tenant information
$tenant_id = $_SESSION['user_id'];
$tenant_name = $_SESSION['full_name'];

// Get booking statistics
$total_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ?";
$stmt = $conn->prepare($total_bookings_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];

// Get active bookings
$active_bookings_query = "SELECT COUNT(*) as total FROM bookings 
                         WHERE tenant_id = ? AND status = 'confirmed' 
                         AND end_date >= CURRENT_DATE()";
$stmt = $conn->prepare($active_bookings_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$active_bookings = $stmt->get_result()->fetch_assoc()['total'];

// Get recent bookings
$recent_bookings_query = "SELECT b.*, p.title as property_title, p.location, p.price,
                         u.full_name as landlord_name
                         FROM bookings b
                         JOIN properties p ON b.property_id = p.id
                         JOIN users u ON p.landlord_id = u.id
                         WHERE b.tenant_id = ?
                         ORDER BY b.created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_bookings_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$recent_bookings = $stmt->get_result();

// Get favorite properties count
$favorites_query = "SELECT COUNT(*) as total FROM favorites WHERE tenant_id = ?";
$stmt = $conn->prepare($favorites_query);
if (!$stmt) {
    die("Prepare failed for favorites_query: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['total'];
?>

<?php include_once "../includes/header.php"; ?>

<div class="container-fluid my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo htmlspecialchars($tenant_name); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my_bookings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i> My Bookings
                    </a>
                    <a href="property_search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Search Properties
                    </a>
                    <a href="favorites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i> Favorites
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Bookings</h5>
                            <h2 class="mb-0"><?php echo $total_bookings; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Bookings</h5>
                            <h2 class="mb-0"><?php echo $active_bookings; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Favorite Properties</h5>
                            <h2 class="mb-0"><?php echo $total_favorites; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Location</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['location']); ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> -
                                                <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                            </td>
                                            <td>৳<?php echo number_format($booking['total_amount']); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($booking['status'] == 'confirmed') ? 'bg-success' : 
                                                    (($booking['status'] == 'pending') ? 'bg-warning' : 'bg-danger'); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 