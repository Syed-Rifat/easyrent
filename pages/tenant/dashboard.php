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
?>

<?php include_once "../includes/header.php"; ?>


<div class="container my-5">
    <h2>Tenant Dashboard</h2>
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="my_bookings.php" class="list-group-item list-group-item-action">My Bookings</a>
                <a href="property_search.php" class="list-group-item list-group-item-action">Search Properties</a>
                <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4>Recent Activities</h4>
                </div>
                <div class="card-body">
                    <!-- Dashboard content will go here -->
                    <p>Welcome to your tenant dashboard. Here you can manage your bookings, search for properties, and update your profile.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 