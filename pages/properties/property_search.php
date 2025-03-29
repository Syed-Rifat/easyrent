<?php
// Include database connection
require_once "../../database/config.php";

// Define filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$bathrooms = isset($_GET['bathrooms']) ? $_GET['bathrooms'] : '';

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$limit = 6; // Items per page
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($location)) {
    $where_conditions[] = "location LIKE ?";
    $location_param = "%" . $location . "%";
    $params[] = $location_param;
    $types .= "s";
}

if (!empty($property_type)) {
    $where_conditions[] = "property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}

if (!empty($min_price)) {
    $where_conditions[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $where_conditions[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($bedrooms)) {
    $where_conditions[] = "bedrooms >= ?";
    $params[] = $bedrooms;
    $types .= "i";
}

if (!empty($bathrooms)) {
    $where_conditions[] = "bathrooms >= ?";
    $params[] = $bathrooms;
    $types .= "i";
}

// Add status condition to show only available properties
$where_conditions[] = "status = 'available'";

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM properties $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$total_rows = $result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get properties
$query = "SELECT * FROM properties $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

// Add limit and offset params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$properties = $stmt->get_result();

// Get locations for dropdown
$locations_query = "SELECT DISTINCT location FROM properties ORDER BY location";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row['location'];
}
?>

<?php include_once "../includes/header.php"; ?>
<?php include_once "../includes/navbar.php"; ?>

<div class="container my-5">
    <h2>Search Properties</h2>
    
    <!-- Advanced Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Keywords</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search keywords" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-select" id="location" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc; ?>" <?php echo ($location == $loc) ? 'selected' : ''; ?>>
                                <?php echo $loc; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="property_type" class="form-label">Property Type</label>
                    <select class="form-select" id="property_type" name="property_type">
                        <option value="">All Types</option>
                        <option value="apartment" <?php echo ($property_type == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                        <option value="house" <?php echo ($property_type == 'house') ? 'selected' : ''; ?>>House</option>
                        <option value="room" <?php echo ($property_type == 'room') ? 'selected' : ''; ?>>Room</option>
                        <option value="office" <?php echo ($property_type == 'office') ? 'selected' : ''; ?>>Office</option>
                        <option value="commercial" <?php echo ($property_type == 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="min_price" class="form-label">Min Price (৳)</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" placeholder="Minimum price" value="<?php echo htmlspecialchars($min_price); ?>">
                </div>
                <div class="col-md-3">
                    <label for="max_price" class="form-label">Max Price (৳)</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" placeholder="Maximum price" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
                <div class="col-md-3">
                    <label for="bedrooms" class="form-label">Bedrooms</label>
                    <select class="form-select" id="bedrooms" name="bedrooms">
                        <option value="">Any</option>
                        <option value="1" <?php echo ($bedrooms == '1') ? 'selected' : ''; ?>>1+</option>
                        <option value="2" <?php echo ($bedrooms == '2') ? 'selected' : ''; ?>>2+</option>
                        <option value="3" <?php echo ($bedrooms == '3') ? 'selected' : ''; ?>>3+</option>
                        <option value="4" <?php echo ($bedrooms == '4') ? 'selected' : ''; ?>>4+</option>
                        <option value="5" <?php echo ($bedrooms == '5') ? 'selected' : ''; ?>>5+</option>
                    </select>
                </div>
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary px-4">Search</button>
                    <a href="property_search.php" class="btn btn-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results -->
    <div class="mb-3">
        <p><?php echo $total_rows; ?> properties found</p>
    </div>
    
    <div class="row">
        <?php if ($properties->num_rows > 0): ?>
            <?php while ($property = $properties->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($property['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($property['title']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                            </p>
                            <p class="card-text">
                                <strong>৳<?php echo number_format($property['price']); ?></strong> / month
                            </p>
                            <p class="card-text">
                                <span class="me-2"><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> bed</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> bath</span>
                            </p>
                            <p class="card-text">
                                <?php echo substr(htmlspecialchars($property['description']), 0, 100); ?>...
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No properties found matching your criteria. Try adjusting your search filters.</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&property_type=<?php echo urlencode($property_type); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&bedrooms=<?php echo urlencode($bedrooms); ?>&bathrooms=<?php echo urlencode($bathrooms); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include_once "../includes/footer.php"; ?> 