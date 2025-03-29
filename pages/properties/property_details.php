<?php
// Start session to check if user is logged in
session_start();

// Include database connection
require_once "../../database/config.php";

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: all_properties.php");
    exit();
}

$property_id = $_GET['id'];

// Get property details
$query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email, u.phone as landlord_phone 
          FROM properties p 
          LEFT JOIN users u ON p.landlord_id = u.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Property not found
    header("Location: all_properties.php");
    exit();
}

$property = $result->fetch_assoc();

// Get property images
$images_query = "SELECT * FROM property_images WHERE property_id = ?";
$images_stmt = $conn->prepare($images_query);
$images_stmt->bind_param("i", $property_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$property_images = [];

while ($image = $images_result->fetch_assoc()) {
    $property_images[] = $image;
}

// If no additional images, use the main image
if (count($property_images) === 0 && !empty($property['image_url'])) {
    $property_images[] = ['image_url' => $property['image_url']];
}

// Process booking request
$booking_message = '';
$booking_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_property'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $booking_message = '<div class="alert alert-danger">Please <a href="../auth/login.php">login</a> to book this property.</div>';
    } else if ($_SESSION['user_type'] !== 'tenant') {
        $booking_message = '<div class="alert alert-danger">Only tenants can book properties.</div>';
    } else {
        $tenant_id = $_SESSION['user_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        // Calculate total months and amount
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $months = ($interval->y * 12) + $interval->m;
        if ($interval->d > 0) $months += 1; // Count partial month
        if ($months < 1) $months = 1; // Minimum 1 month
        
        $total_amount = $property['price'] * $months;
        
        // Insert booking
        $booking_query = "INSERT INTO bookings (property_id, tenant_id, start_date, end_date, total_amount, status) 
                          VALUES (?, ?, ?, ?, ?, 'pending')";
        $booking_stmt = $conn->prepare($booking_query);
        $booking_stmt->bind_param("iissd", $property_id, $tenant_id, $start_date, $end_date, $total_amount);
        
        if ($booking_stmt->execute()) {
            $booking_success = true;
            $booking_message = '<div class="alert alert-success">Your booking request has been submitted successfully. The landlord will contact you soon.</div>';
        } else {
            $booking_message = '<div class="alert alert-danger">Failed to submit booking request. Please try again later.</div>';
        }
    }
}
?>

<?php include_once "../includes/header.php"; ?>
<?php include_once "../includes/navbar.php"; ?>

<div class="container my-5">
    <!-- Booking message if any -->
    <?php if (!empty($booking_message)): ?>
        <div class="row">
            <div class="col-12">
                <?php echo $booking_message; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Property Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h2>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                    
                    <!-- Property Images -->
                    <div class="property-images mb-4">
                        <div class="main-image mb-3">
                            <img id="propertyMainImage" src="<?php echo htmlspecialchars($property['image_url']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="img-fluid w-100" style="height: 400px; object-fit: cover;">
                        </div>
                        <?php if (count($property_images) > 1): ?>
                            <div class="thumbnail-images" id="propertyGallery">
                                <div class="row">
                                    <?php foreach ($property_images as $image): ?>
                                        <div class="col-md-3 col-sm-4 col-6 mb-2">
                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="" class="img-thumbnail cursor-pointer" style="height: 80px; object-fit: cover; cursor: pointer;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Property Details -->
                    <div class="property-details">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4 class="property-price"><strong>৳<?php echo number_format($property['price']); ?></strong> / month</h4>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge bg-primary"><?php echo ucfirst($property['property_type']); ?></span>
                                <span class="badge bg-success"><?php echo ucfirst($property['status']); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-bed fa-lg text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Bedrooms</div>
                                        <div class="fw-bold"><?php echo $property['bedrooms']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-bath fa-lg text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Bathrooms</div>
                                        <div class="fw-bold"><?php echo $property['bathrooms']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ruler-combined fa-lg text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Area</div>
                                        <div class="fw-bold"><?php echo $property['area']; ?> sq.ft</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h4 class="mb-3">Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                        
                        <h4 class="mb-3">Address</h4>
                        <p><?php echo htmlspecialchars($property['address']); ?></p>
                        
                        <?php if (!empty($property['amenities'])): ?>
                            <h4 class="mb-3">Amenities</h4>
                            <div class="property-amenities mb-4">
                                <?php 
                                $amenities = explode(',', $property['amenities']);
                                foreach ($amenities as $amenity): 
                                    $amenity = trim($amenity);
                                    if (!empty($amenity)):
                                ?>
                                    <span class="badge bg-light text-dark me-2 mb-2"><?php echo htmlspecialchars($amenity); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Landlord Info -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Contact Landlord</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <i class="fas fa-user-circle fa-5x text-secondary mb-3"></i>
                        <h5><?php echo htmlspecialchars($property['landlord_name']); ?></h5>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($property['landlord_email']); ?>
                    </div>
                    <?php if (!empty($property['landlord_phone'])): ?>
                        <div class="mb-3">
                            <i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($property['landlord_phone']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Booking Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Book This Property</h5>
                </div>
                <div class="card-body">
                    <?php if ($booking_success): ?>
                        <div class="alert alert-success">Booking request submitted successfully!</div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Move-in Date</label>
                                <input type="date" id="startDate" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Move-out Date</label>
                                <input type="date" id="endDate" name="end_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="book_property" class="btn btn-primary">Request Booking</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 