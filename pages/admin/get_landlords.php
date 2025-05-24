<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get active landlords
$query = "SELECT id, full_name, email, phone, status 
          FROM users 
          WHERE user_type = 'landlord' 
          AND status = 'active' 
          ORDER BY full_name";
$result = $conn->query($query);

if ($result) {
    $landlords = $result->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($landlords);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch landlords']);
} 