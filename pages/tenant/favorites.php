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

// Handle favorite removal
if (isset($_POST['remove_favorite'])) {
    $property_id = $_POST['property_id'];
    
    $delete_query = "DELETE FROM favorites WHERE tenant_id = ? AND property_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $tenant_id, $property_id);
    
    if ($stmt->execute()) {
        $success_message = "Property removed from favorites!";
    } else {
        $error_message = "Error removing property from favorites!";
    }
}

// Get favorite properties
$favorites_query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email
                   FROM favorites f
                   JOIN properties p ON f.property_id = p.id
                   JOIN users u ON p.landlord_id = u.id
                   WHERE f.tenant_id = ?
                   ORDER BY f.created_at DESC";
$stmt = $conn->prepare($favorites_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$favorites = $stmt->get_result();
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
                    <a href="favorites.php" class="list-group-item list-group-item-action active">
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
                    <h5 class="mb-0">My Favorite Properties</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php if ($favorites->num_rows > 0): ?>
                            <?php while ($property = $favorites->fetch_assoc()): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <?php
                                        // Get property image
                                        $image_query = "SELECT image_url FROM property_images WHERE property_id = ? LIMIT 1";
                                        $stmt = $conn->prepare($image_query);
                                        $stmt->bind_param("i", $property['id']);
                                        $stmt->execute();
                                        $image = $stmt->get_result()->fetch_assoc();
                                        ?>
                                        <img src="<?php echo !empty($image) ? '../../' . $image['image_url'] : '../../assets/images/no-image.jpg'; ?>" 
                                             class="card-img-top" alt="Property Image" style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                    <button type="submit" name="remove_favorite" class="btn btn-link text-danger p-0"
                                                            onclick="return confirm('Are you sure you want to remove this property from favorites?');">
                                                        <i class="fas fa-heart text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <p class="card-text">
                                                <i class="fas fa-map-marker-alt text-primary"></i> 
                                                <?php echo htmlspecialchars($property['location']); ?>
                                            </p>
                                            <div class="mb-3">
                                                <span class="badge bg-primary"><?php echo ucfirst($property['property_type']); ?></span>
                                                <span class="badge bg-success">৳<?php echo number_format($property['price']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                                <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-primary" 
                                                        onclick="viewProperty(<?php echo $property['id']; ?>)">
                                                    View Details
                                                </button>
                                                <a href="book_property.php?property_id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-success">
                                                    Book Now
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No favorite properties found. 
                                    <a href="property_search.php" class="alert-link">Search for properties</a> to add them to your favorites.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Property Details Modal -->
<div class="modal fade" id="propertyModal" tabindex="-1">
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
                                <img src="../../${image.image_url}" class="d-block w-100" alt="Property Image">
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
                            <p><strong>Price:</strong> ৳${property.price.toLocaleString()}</p>
                            <p><strong>Type:</strong> ${property.property_type}</p>
                            <p><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                            <p><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                            <p><strong>Area:</strong> ${property.area} sq ft</p>
                            <p><strong>Landlord:</strong> ${property.landlord_name}</p>
                            <p><strong>Description:</strong> ${property.description}</p>
                            <form action="book_property.php" method="GET" class="mt-3">
                                <input type="hidden" name="property_id" value="${property.id}">
                                <button type="submit" class="btn btn-primary">Book Now</button>
                            </form>
                        </div>
                    </div>
                `;
                
                document.getElementById('propertyDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('propertyModal')).show();
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