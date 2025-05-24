<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get landlord information
$landlord_id = $_SESSION['user_id'];
$landlord_name = $_SESSION['full_name'];

// Get counts from database
$property_count_query = "SELECT COUNT(*) as total FROM properties WHERE landlord_id = ?";
$stmt = $conn->prepare($property_count_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$property_count = $stmt->get_result()->fetch_assoc()['total'];

$booking_count_query = "SELECT COUNT(*) as total FROM bookings b 
                       JOIN properties p ON b.property_id = p.id 
                       WHERE p.landlord_id = ?";
$stmt = $conn->prepare($booking_count_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$booking_count = $stmt->get_result()->fetch_assoc()['total'];

$total_earnings_query = "SELECT SUM(b.total_amount) as total FROM bookings b 
                        JOIN properties p ON b.property_id = p.id 
                        WHERE p.landlord_id = ? AND b.status = 'confirmed'";
$stmt = $conn->prepare($total_earnings_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$total_earnings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Get recent properties
$recent_properties_query = "SELECT p.*, 
                           (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count
                           FROM properties p 
                           WHERE p.landlord_id = ? 
                           ORDER BY p.created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_properties_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$recent_properties = $stmt->get_result();

// Get recent bookings
$recent_bookings_query = "SELECT b.*, p.title as property_title, p.location as property_location,
                         u.full_name as tenant_name, u.email as tenant_email
                         FROM bookings b
                         JOIN properties p ON b.property_id = p.id
                         JOIN users u ON b.tenant_id = u.id
                         WHERE p.landlord_id = ?
                         ORDER BY b.created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_bookings_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$recent_bookings = $stmt->get_result();
?>

<?php include_once "../includes/header.php"; ?>

<div class="container-fluid my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo htmlspecialchars($landlord_name); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my_properties.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> My Properties
                    </a>
                    <a href="add_property.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Add New Property
                    </a>
                    <a href="booking_requests.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i> Booking Requests
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
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white h-100">
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
                            <a href="my_properties.php" class="text-white text-decoration-none small">View Properties</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white h-100">
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
                            <a href="booking_requests.php" class="text-white text-decoration-none small">View Bookings</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Total Earnings</h6>
                                    <h2 class="mb-0">৳<?php echo number_format($total_earnings); ?></h2>
                                </div>
                                <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="booking_requests.php" class="text-white text-decoration-none small">View Details</a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Properties -->
            <div class="row">
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
                                            <th>Bookings</th>
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
                                                        <span class="badge <?php echo ($property['status'] == 'available') ? 'bg-success' : 
                                                                                (($property['status'] == 'rented') ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo ucfirst($property['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $property['booking_count']; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No properties found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="my_properties.php" class="btn btn-sm btn-primary">View All Properties</a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Bookings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Tenant</th>
                                            <th>Dates</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_bookings->num_rows > 0): ?>
                                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
                                                    <td>
                                                        <?php echo date('d M', strtotime($booking['start_date'])); ?> - 
                                                        <?php echo date('d M', strtotime($booking['end_date'])); ?>
                                                    </td>
                                                    <td>৳<?php echo number_format($booking['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($booking['status'] == 'confirmed') ? 'bg-success' : 
                                                                                (($booking['status'] == 'pending') ? 'bg-warning' : 
                                                                                (($booking['status'] == 'cancelled') ? 'bg-danger' : 'bg-secondary')); ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No recent bookings</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="booking_requests.php" class="btn btn-sm btn-primary">View All Bookings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 