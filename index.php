<?php
// Include database connection
require_once "database/config.php";

// Start session for login check
session_start();

// Define base URL to prevent path issues - simplified approach
$server_name = $_SERVER['SERVER_NAME'];
$is_localhost = ($server_name === 'localhost' || $server_name === '127.0.0.1');

// Set a consistent base URL for all pages
if ($is_localhost) {
    $base_url = "/easyrent/";
} else {
    $base_url = "/";
}

// Get featured properties (limit to 3)
$query = "SELECT * FROM properties WHERE featured = 1 ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($query);
$featured_properties = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_properties[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyRent - House Rental System</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add active class script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get current path
            const path = window.location.pathname;
            const page = path.split("/").pop();
            
            // Set active class based on current page
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href) {
                    if ((page === '' || page === 'index.php') && href.includes('index.php')) {
                        link.classList.add('active');
                    } else if (href.includes(page)) {
                        link.classList.add('active');
                    }
                }
            });

            // Debug for image loading
            console.log('Base URL: <?php echo $base_url; ?>');
            const images = document.querySelectorAll('img');
            images.forEach((img, index) => {
                console.log(`Image ${index} src: ${img.src}, complete: ${img.complete}`);
                img.addEventListener('error', function() {
                    console.error(`Failed to load image: ${this.src}`);
                    this.style.border = '2px solid red';
                });
            });
        });
    </script>
</head>
<body>
    <!-- Include Navigation -->
    <?php include_once "pages/includes/navbar.php"; ?>

    <!-- Hero Section -->
    <div class="hero-section py-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x600?text=EasyRent+Hero+Background') no-repeat center center; background-size: cover; min-height: 500px;">
        <div class="container py-5 text-center text-white">
            <h1 class="display-4 fw-bold mt-5">Find Your Perfect Home</h1>
            <p class="lead mb-4">Discover the ideal rental property for your needs with EasyRent</p>
            <div class="search-box p-3 bg-white rounded shadow-lg mx-auto" style="max-width: 700px;">
                <form action="<?php echo $base_url; ?>pages/properties/all_properties.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-lg" placeholder="Search by location, property type, or price...">
                        <button type="submit" class="btn btn-primary btn-lg">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Featured Properties -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Featured Properties</h2>
        <div class="row">
            <?php if (count($featured_properties) > 0): ?>
                <?php foreach ($featured_properties as $property): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
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
                            <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo $property['title']; ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $property['title']; ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $property['location']; ?>
                                </p>
                                <p class="card-text">
                                    <strong>TK <?php echo number_format($property['price']); ?></strong> / month
                                </p>
                                <p class="card-text">
                                    <?php echo substr($property['description'], 0, 100); ?>...
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="<?php echo $base_url; ?>pages/properties/property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No featured properties at the moment. Check back soon!</p>
                    <a href="<?php echo $base_url; ?>pages/properties/all_properties.php" class="btn btn-primary">View All Properties</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Features -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-4">Why Choose EasyRent?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                            <h4>Easy Search</h4>
                            <p>Find properties that match your requirements with our advanced search filters.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-home fa-3x text-primary mb-3"></i>
                            <h4>Verified Properties</h4>
                            <p>All our listed properties are verified to ensure you get what you see.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                            <h4>Secure Booking</h4>
                            <p>Book properties safely with our secure booking system and tenant protection.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>EasyRent</h5>
                    <p>Your trusted platform for finding and renting properties.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo $base_url; ?>pages/properties/all_properties.php" class="text-white">Properties</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/auth/login.php" class="text-white">Login</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/auth/register.php" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Rental Street, Dhaka, Bangladesh</p>
                        <p><i class="fas fa-phone me-2"></i> +880 1234-567890</p>
                        <p><i class="fas fa-envelope me-2"></i> info@easyrent.com</p>
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> EasyRent. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>assets/js/script.js"></script>
</body>
</html> 
