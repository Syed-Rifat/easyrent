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

// Get search parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$bathrooms = isset($_GET['bathrooms']) ? $_GET['bathrooms'] : '';

// Build search query
$where_conditions = ["p.status = 'available'"];
$params = [];
$types = "";

if (!empty($location)) {
    $where_conditions[] = "p.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

if (!empty($property_type)) {
    $where_conditions[] = "p.property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}

if (!empty($min_price)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($bedrooms)) {
    $where_conditions[] = "p.bedrooms >= ?";
    $params[] = $bedrooms;
    $types .= "i";
}

if (!empty($bathrooms)) {
    $where_conditions[] = "p.bathrooms >= ?";
    $params[] = $bathrooms;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Get properties
$properties_query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email,
                    (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.id AND f.tenant_id = ?) as is_favorite
                    FROM properties p
                    JOIN users u ON p.landlord_id = u.id
                    WHERE $where_clause
                    ORDER BY p.created_at DESC";

$stmt = $conn->prepare($properties_query);
$params = array_merge([$tenant_id], $params);
$types = "i" . $types;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$properties = $stmt->get_result();

// Get distinct locations for dropdown
$locations_query = "SELECT DISTINCT location FROM properties WHERE status = 'available' ORDER BY location";
$locations = $conn->query($locations_query);

// Handle favorite toggle
if (isset($_POST['toggle_favorite'])) {
    $property_id = $_POST['property_id'];
    
    // Check if already favorite
    $check_query = "SELECT id FROM favorites WHERE tenant_id = ? AND property_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $tenant_id, $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Remove from favorites
        $delete_query = "DELETE FROM favorites WHERE tenant_id = ? AND property_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $tenant_id, $property_id);
        $stmt->execute();
    } else {
        // Add to favorites
        $insert_query = "INSERT INTO favorites (tenant_id, property_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $tenant_id, $property_id);
        $stmt->execute();
    }
    
    // Redirect to maintain search parameters
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
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
                    <a href="property_search.php" class="list-group-item list-group-item-action active">
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
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Search Properties</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-select" id="location" name="location">
                                <option value="">All Locations</option>
                                <?php while ($loc = $locations->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($loc['location']); ?>"
                                            <?php echo ($location == $loc['location']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['location']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="property_type" class="form-label">Property Type</label>
                            <select class="form-select" id="property_type" name="property_type">
                                <option value="">All Types</option>
                                <option value="apartment" <?php echo ($property_type == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                                <option value="house" <?php echo ($property_type == 'house') ? 'selected' : ''; ?>>House</option>
                                <option value="villa" <?php echo ($property_type == 'villa') ? 'selected' : ''; ?>>Villa</option>
                                <option value="office" <?php echo ($property_type == 'office') ? 'selected' : ''; ?>>Office</option>
                                <option value="room" <?php echo ($property_type == 'room') ? 'selected' : ''; ?>>Room</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="bedrooms" class="form-label">Min Bedrooms</label>
                            <select class="form-select" id="bedrooms" name="bedrooms">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($bedrooms == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>+
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="bathrooms" class="form-label">Min Bathrooms</label>
                            <select class="form-select" id="bathrooms" name="bathrooms">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($bathrooms == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>+
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="min_price" class="form-label">Min Price</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" 
                                   value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Min Price">
                        </div>
                        <div class="col-md-4">
                            <label for="max_price" class="form-label">Max Price</label>
                            <input type="number" class="form-control" id="max_price" name="max_price" 
                                   value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Max Price">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="property_search.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Properties Grid -->
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php if ($properties->num_rows > 0): ?>
                    <?php while ($property = $properties->fetch_assoc()): ?>
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
                                            <button type="submit" name="toggle_favorite" class="btn btn-link text-danger p-0">
                                                <i class="fas fa-heart <?php echo $property['is_favorite'] ? 'text-danger' : 'text-muted'; ?>"></i>
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
                                    <button type="button" class="btn btn-primary w-100" 
                                            onclick="viewProperty(<?php echo $property['id']; ?>)">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No properties found matching your criteria.</div>
                    </div>
                <?php endif; ?>
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