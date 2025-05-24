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

// Handle booking status update
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    $update_query = "UPDATE bookings SET status = ? WHERE id = ? AND EXISTS (
                    SELECT 1 FROM properties WHERE id = bookings.property_id AND landlord_id = ?)";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $booking_id, $landlord_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Error updating booking status!";
    }
}

// Get all bookings for landlord's properties
$bookings_query = "SELECT b.*, p.title as property_title, p.location as property_location,
                  u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
                  FROM bookings b
                  JOIN properties p ON b.property_id = p.id
                  JOIN users u ON b.tenant_id = u.id
                  WHERE p.landlord_id = ?
                  ORDER BY b.created_at DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $landlord_id);
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
                    <h5 class="mb-0"><?php echo htmlspecialchars($landlord_name); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my_properties.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> My Properties
                    </a>
                    <a href="add_property.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Add New Property
                    </a>
                    <a href="booking_requests.php" class="list-group-item list-group-item-action active">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Booking Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>Dates</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings->num_rows > 0): ?>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['tenant_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['tenant_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('d M Y', strtotime($booking['end_date'])); ?>
                                            </td>
                                            <td>৳<?php echo number_format($booking['total_amount']); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($booking['status'] == 'confirmed') ? 'bg-success' : 
                                                                        (($booking['status'] == 'pending') ? 'bg-warning' : 
                                                                        (($booking['status'] == 'cancelled') ? 'bg-danger' : 'bg-secondary')); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <div class="btn-group">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Confirm
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No booking requests found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                            <p><strong>Location:</strong> ${booking.property_location}</p>
                            <p><strong>Price:</strong> ৳${booking.total_amount.toLocaleString()}</p>
                        </div>
                        <div class="col-md-6">
                            <h4>Tenant Details</h4>
                            <p><strong>Name:</strong> ${booking.tenant_name}</p>
                            <p><strong>Email:</strong> ${booking.tenant_email}</p>
                            <p><strong>Phone:</strong> ${booking.tenant_phone}</p>
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