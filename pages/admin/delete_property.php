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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

// Get property ID from request
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

if ($property_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid property ID']);
    exit();
}

// Check if property has any bookings
$check_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE property_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc();

if ($counts['booking_count'] > 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cannot delete property with associated bookings']);
    exit();
}

// Get property images
$images_query = "SELECT image_path FROM property_images WHERE property_id = ?";
$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$images = $stmt->get_result();

// Delete image files
while ($image = $images->fetch_assoc()) {
    $file_path = "../../uploads/properties/" . $image['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Delete property images from database
$delete_images_query = "DELETE FROM property_images WHERE property_id = ?";
$stmt = $conn->prepare($delete_images_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();

// Delete property
$delete_query = "DELETE FROM properties WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $property_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Property deleted successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to delete property']);
} 