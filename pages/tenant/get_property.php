<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get tenant information
$tenant_id = $_SESSION['user_id'];

// Check if property ID is provided
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Property ID is required']);
    exit();
}

$property_id = $_GET['id'];

// Get property details
$property_query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email,
                  (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.id AND f.tenant_id = ?) as is_favorite
                  FROM properties p
                  JOIN users u ON p.landlord_id = u.id
                  WHERE p.id = ? AND p.status = 'available'";
$stmt = $conn->prepare($property_query);
$stmt->bind_param("ii", $tenant_id, $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit();
}

$property = $result->fetch_assoc();

// Get property images
$images_query = "SELECT image_url FROM property_images WHERE property_id = ?";
$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$property['images'] = $images;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'property' => $property
]); 