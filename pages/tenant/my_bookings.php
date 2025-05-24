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

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    $cancel_query = "UPDATE bookings SET status = 'cancelled' 
                    WHERE id = ? AND tenant_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($cancel_query);
    $stmt->bind_param("ii", $booking_id, $tenant_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking cancelled successfully!";
    } else {
        $error_message = "Error cancelling booking!";
    }
}

// Get all bookings for the tenant
$bookings_query = "SELECT b.*, p.title as property_title, p.location, p.property_type,
                   p.price, u.full_name as landlord_name, u.phone as landlord_phone,
                   u.email as landlord_email
                   FROM bookings b
                   JOIN properties p ON b.property_id = p.id
                   JOIN users u ON p.landlord_id = u.id
                   WHERE b.tenant_id = ?
                   ORDER BY b.created_at DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$bookings = $stmt->get_result();
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my_bookings.php" class="list-group-item list-group-item-action active">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">My Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <?php if ($bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Location</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
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
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" name="cancel_booking" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Booking Modal -->
<div class="modal fade" id="viewBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="bookingDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewBooking(bookingId) {
    fetch(`get_booking.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.booking;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Property Details</h4>
                            <p><strong>Title:</strong> ${booking.property_title}</p>
                            <p><strong>Location:</strong> ${booking.location}</p>
                            <p><strong>Type:</strong> ${booking.property_type}</p>
                            <p><strong>Price:</strong> ৳${booking.price.toLocaleString()}</p>
                        </div>
                        <div class="col-md-6">
                            <h4>Landlord Details</h4>
                            <p><strong>Name:</strong> ${booking.landlord_name}</p>
                            <p><strong>Email:</strong> ${booking.landlord_email}</p>
                            <p><strong>Phone:</strong> ${booking.landlord_phone}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h4>Booking Details</h4>
                            <p><strong>Booking ID:</strong> ${booking.id}</p>
                            <p><strong>Start Date:</strong> ${new Date(booking.start_date).toLocaleDateString()}</p>
                            <p><strong>End Date:</strong> ${new Date(booking.end_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge ${booking.status === 'confirmed' ? 'bg-success' : 
                                                    (booking.status === 'pending' ? 'bg-warning' : 'bg-danger')}">
                                    ${booking.status}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h4>Payment Details</h4>
                            <p><strong>Total Amount:</strong> ৳${booking.total_amount.toLocaleString()}</p>
                            <p><strong>Payment Status:</strong> ${booking.payment_status}</p>
                            <p><strong>Booking Date:</strong> ${new Date(booking.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('bookingDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewBookingModal')).show();
            } else {
                alert('Error loading booking details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading booking details');
        });
}
</script>

<?php include_once "../includes/footer.php"; ?> 