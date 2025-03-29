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
?>

<?php include_once "../includes/header.php"; ?>


<div class="container my-5">
    <h2>Landlord Dashboard</h2>
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="my_properties.php" class="list-group-item list-group-item-action">My Properties</a>
                <a href="add_property.php" class="list-group-item list-group-item-action">Add New Property</a>
                <a href="booking_requests.php" class="list-group-item list-group-item-action">Booking Requests</a>
                <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4>Property Overview</h4>
                </div>
                <div class="card-body">
                    <!-- Dashboard content will go here -->
                    <p>Welcome to your landlord dashboard. Here you can manage your properties, view booking requests, and update your profile.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 