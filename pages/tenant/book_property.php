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

// Check if property ID is provided
if (!isset($_GET['property_id'])) {
    header("Location: property_search.php");
    exit();
}

$property_id = $_GET['property_id'];

// Get property details
$property_query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email,
                  u.phone as landlord_phone
                  FROM properties p
                  JOIN users u ON p.landlord_id = u.id
                  WHERE p.id = ? AND p.status = 'available'";
$stmt = $conn->prepare($property_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: property_search.php");
    exit();
}

$property = $result->fetch_assoc();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_amount = $_POST['total_amount'];
    
    // Check if dates are available
    $check_query = "SELECT COUNT(*) as count FROM bookings 
                   WHERE property_id = ? AND status != 'cancelled'
                   AND ((start_date BETWEEN ? AND ?) 
                   OR (end_date BETWEEN ? AND ?))";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("issss", $property_id, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error_message = "Selected dates are not available!";
    } else {
        // Create booking
        $insert_query = "INSERT INTO bookings (tenant_id, property_id, start_date, end_date, 
                        total_amount, status, payment_status) 
                        VALUES (?, ?, ?, ?, ?, 'pending', 'pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iissd", $tenant_id, $property_id, $start_date, $end_date, $total_amount);
        
        if ($stmt->execute()) {
            header("Location: my_bookings.php");
            exit();
        } else {
            $error_message = "Error creating booking!";
        }
    }
}
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Book Property</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Property Details -->
                        <div class="col-md-6">
                            <h4><?php echo htmlspecialchars($property['title']); ?></h4>
                            <p>
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <?php echo htmlspecialchars($property['location']); ?>
                            </p>
                            <div class="mb-3">
                                <span class="badge bg-primary"><?php echo ucfirst($property['property_type']); ?></span>
                                <span class="badge bg-success">৳<?php echo number_format($property['price']); ?> / month</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                            </div>
                            <hr>
                            <h5>Landlord Details</h5>
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($property['landlord_name']); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($property['landlord_email']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($property['landlord_phone']); ?></p>
                        </div>

                        <!-- Booking Form -->
                        <div class="col-md-6">
                            <form method="POST" id="bookingForm">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Duration</label>
                                    <p id="duration" class="form-text">Please select dates</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="text" class="form-control" id="total_amount" name="total_amount" 
                                               readonly required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Confirm Booking</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const propertyPrice = <?php echo $property['price']; ?>;

function calculateTotal() {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    
    if (startDate && endDate && startDate <= endDate) {
        // Calculate months difference
        const months = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                      (endDate.getMonth() - startDate.getMonth());
        const totalAmount = propertyPrice * months;
        
        document.getElementById('duration').textContent = months + (months === 1 ? ' month' : ' months');
        document.getElementById('total_amount').value = totalAmount.toFixed(2);
    } else {
        document.getElementById('duration').textContent = 'Please select valid dates';
        document.getElementById('total_amount').value = '';
    }
}

document.getElementById('start_date').addEventListener('change', calculateTotal);
document.getElementById('end_date').addEventListener('change', calculateTotal);

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    
    if (startDate >= endDate) {
        e.preventDefault();
        alert('End date must be after start date');
    }
});
</script>

<?php include_once "../includes/footer.php"; ?> 