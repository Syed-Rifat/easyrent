<?php
// Include database connection
require_once "../../database/config.php";

// Get all properties with pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$limit = 6; // Items per page
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';

if (!empty($search)) {
    $search = "%{$search}%";
    $where = "WHERE title LIKE ? OR location LIKE ? OR description LIKE ?";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM properties $where";
$stmt = $conn->prepare($count_query);

if (!empty($search)) {
    $stmt->bind_param("sss", $search, $search, $search);
}

$stmt->execute();
$result = $stmt->get_result();
$total_rows = $result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get properties
$query = "SELECT * FROM properties $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $stmt->bind_param("ssii", $search, $search, $search, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$properties = $stmt->get_result();
?>

<?php include_once "../includes/header.php"; ?>
<?php include_once "../includes/navbar.php"; ?>

<!-- Hero Section with Background Image -->
<div class="hero-section py-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x600?text=Available+Properties') no-repeat center center; background-size: cover; min-height: 300px;">
    <div class="container py-5 text-center text-white">
        <h1 class="display-4 fw-bold mt-3">All Properties</h1>
        <p class="lead mb-0">Find your preferred property</p>
    </div>
</div>

<div class="container my-5">
    <div class="row mb-3">
        <div class="col-md-10 mx-auto">
            <a href="<?php echo $base_url; ?>index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h3 class="mb-0">Available Properties</h3>
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" action="" class="my-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control form-control-lg" placeholder="Search by title, location, or description" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button type="submit" class="btn btn-primary btn-lg">Search</button>
                </div>
            </form>
            
            <div class="row">
                <?php if ($properties->num_rows > 0): ?>
                    <?php while ($property = $properties->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <?php
                                // Use placeholder images for consistent display
                                $placeholders = [
                                    'https://via.placeholder.com/600x400?text=Luxury+Apartment',
                                    'https://via.placeholder.com/600x400?text=Cozy+Studio',
                                    'https://via.placeholder.com/600x400?text=Family+House'
                                ];
                                $placeholder_index = $property['id'] % count($placeholders);
                                $image_url = $placeholders[$placeholder_index];
                                ?>
                                <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($property['title']); ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                                    </p>
                                    <p class="card-text">
                                        <strong>TK <?php echo number_format($property['price']); ?></strong> / month
                                    </p>
                                    <p class="card-text">
                                        <?php echo substr(htmlspecialchars($property['description']), 0, 100); ?>...
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No properties found</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 