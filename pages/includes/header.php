<?php
// Set base path for consistent navigation
$base_path = isset($base_path) ? $base_path : "../";

// Define base URL to prevent path issues
// Check if accessing from localhost - adapt base URL accordingly
$server_name = $_SERVER['SERVER_NAME'];
$is_localhost = ($server_name === 'localhost' || $server_name === '127.0.0.1');

// Simplified base URL detection
if ($is_localhost) {
    $base_url = "/easyrent/";
} else {
    $base_url = "/";
}

// Determine current page for active class in navbar
$current_page = basename($_SERVER['PHP_SELF']);
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
                    if (href.includes(page)) {
                        link.classList.add('active');
                    } else if ((page === 'index.php' || page === '') && href.includes('index.php')) {
                        link.classList.add('active');
                    } else if (href.includes('all_properties.php') && page.includes('property')) {
                        link.classList.add('active');
                    }
                }
            });
        });
    </script>
</head>
<body>
    <div class="container-fluid"> 