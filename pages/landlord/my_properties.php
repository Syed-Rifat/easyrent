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

// Handle property deletion
if (isset($_POST['delete_property'])) {
    $property_id = $_POST['property_id'];
    
    // Delete property images first
    $delete_images_query = "DELETE FROM property_images WHERE property_id = ?";
    $stmt = $conn->prepare($delete_images_query);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    
    // Delete property
    $delete_property_query = "DELETE FROM properties WHERE id = ? AND landlord_id = ?";
    $stmt = $conn->prepare($delete_property_query);
    $stmt->bind_param("ii", $property_id, $landlord_id);
    
    if ($stmt->execute()) {
        $success_message = "Property deleted successfully!";
    } else {
        $error_message = "Error deleting property!";
    }
}

// Get all properties with booking counts
$properties_query = "SELECT p.id, p.title, p.location, p.price, p.property_type, p.status, p.created_at,
                    (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count
                    FROM properties p 
                    WHERE p.landlord_id = ? 
                    ORDER BY p.created_at DESC";
$stmt = $conn->prepare($properties_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$properties = $stmt->get_result();
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
                    <a href="my_properties.php" class="list-group-item list-group-item-action active">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Properties</h5>
                    <a href="add_property.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Property
                    </a>
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
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Price</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Bookings</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($properties->num_rows > 0): ?>
                                    <?php while ($property = $properties->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($property['title']); ?></td>
                                            <td><?php echo htmlspecialchars($property['location']); ?></td>
                                            <td>৳<?php echo number_format($property['price']); ?></td>
                                            <td><?php echo ucfirst($property['property_type'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($property['status'] == 'available') ? 'bg-success' : 
                                                                        (($property['status'] == 'rented') ? 'bg-danger' : 'bg-warning'); ?>">
                                                    <?php echo ucfirst($property['status'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $property['booking_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_property.php?id=<?php echo $property['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewProperty(<?php echo $property['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this property?');">
                                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                        <button type="submit" name="delete_property" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No properties found</td>
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

<!-- View Property Modal -->
<div class="modal fade" id="viewPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="propertyDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewProperty(propertyId) {
    fetch(`get_property.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const property = data.property;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                `;
                
                if (property.images && property.images.length > 0) {
                    property.images.forEach((image, index) => {
                        html += `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                <img src="${image.image_url}" class="d-block w-100" alt="Property Image">
                            </div>
                        `;
                    });
                } else {
                    html += `
                        <div class="carousel-item active">
                            <img src="../../assets/images/no-image.jpg" class="d-block w-100" alt="No Image">
                        </div>
                    `;
                }
                
                html += `
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4>${property.title}</h4>
                            <p><strong>Location:</strong> ${property.location}</p>
                            <p><strong>Address:</strong> ${property.address}</p>
                            <p><strong>Price:</strong> ৳${property.price.toLocaleString()}</p>
                            <p><strong>Type:</strong> ${property.type}</p>
                            <p><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                            <p><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                            <p><strong>Area:</strong> ${property.area} sq ft</p>
                            <p><strong>Status:</strong> 
                                <span class="badge ${property.status === 'available' ? 'bg-success' : 
                                                    (property.status === 'rented' ? 'bg-danger' : 'bg-warning')}">
                                    ${property.status}
                                </span>
                            </p>
                            <p><strong>Description:</strong> ${property.description}</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('propertyDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewPropertyModal')).show();
            } else {
                alert('Error loading property details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading property details');
        });
}
</script>

<?php include_once "../includes/footer.php"; ?> 