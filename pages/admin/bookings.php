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

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $status_message = '<div class="alert alert-success">Booking status updated successfully!</div>';
    } else {
        $status_message = '<div class="alert alert-danger">Failed to update booking status.</div>';
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM bookings";
$total_records = $conn->query($total_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get bookings with pagination
$bookings_query = "SELECT b.*, p.title as property_title, p.location as property_location,
                   u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
                   FROM bookings b
                   JOIN properties p ON b.property_id = p.id
                   JOIN users u ON b.tenant_id = u.id
                   ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<?php include_once "../includes/header.php"; ?>

<div class="container-fluid my-5">
    <div class="row">
        <?php include_once "includes/sidebar.php"; ?>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Bookings</h2>
            </div>
            
            <?php if (isset($status_message)) echo $status_message; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Property</th>
                                    <th>Location</th>
                                    <th>Tenant</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Total Amount</th>
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
                                            <td><?php echo htmlspecialchars($booking['property_location']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['tenant_name']); ?><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($booking['tenant_email']); ?><br>
                                                    <?php echo htmlspecialchars($booking['tenant_phone']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($booking['start_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($booking['end_date'])); ?></td>
                                            <td>৳<?php echo number_format($booking['total_amount']); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo ($booking['status'] == 'approved') ? 'bg-success' : 
                                                        (($booking['status'] == 'pending') ? 'bg-warning' : 
                                                        (($booking['status'] == 'rejected') ? 'bg-danger' : 
                                                        (($booking['status'] == 'canceled') ? 'bg-secondary' : 'bg-info'))); 
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal<?php echo $booking['id']; ?>">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                                
                                                <!-- Status Update Modal -->
                                                <div class="modal fade" id="statusModal<?php echo $booking['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Booking Status</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Current Status</label>
                                                                        <input type="text" class="form-control" value="<?php echo ucfirst($booking['status']); ?>" readonly>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">New Status</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="pending">Pending</option>
                                                                            <option value="approved">Approved</option>
                                                                            <option value="rejected">Rejected</option>
                                                                            <option value="canceled">Canceled</option>
                                                                            <option value="completed">Completed</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
