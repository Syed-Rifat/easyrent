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

// Get and validate input data
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$property_type = isset($_POST['property_type']) ? trim($_POST['property_type']) : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$bedrooms = isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 0;
$bathrooms = isset($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 0;
$landlord_id = isset($_POST['landlord_id']) ? (int)$_POST['landlord_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (empty($title) || empty($location) || empty($property_type) || 
    $price <= 0 || empty($description) || $bedrooms <= 0 || 
    $bathrooms <= 0 || $landlord_id <= 0 || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid required fields']);
    exit();
}

// Verify landlord exists
$landlord_query = "SELECT id FROM users WHERE id = ? AND user_type = 'landlord'";
$stmt = $conn->prepare($landlord_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid landlord ID']);
    exit();
}

// Insert property
$insert_query = "INSERT INTO properties (title, location, property_type, price, description, 
                bedrooms, bathrooms, landlord_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ssdsiiss", $title, $location, $property_type, $price, $description, 
                 $bedrooms, $bathrooms, $landlord_id, $status);

if ($stmt->execute()) {
    $property_id = $conn->insert_id;
    
    // Handle image uploads if any
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = "../../uploads/properties/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Process each uploaded image
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_type = $_FILES['images']['type'][$key];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types)) {
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                // Insert image record in database
                $image_query = "INSERT INTO property_images (property_id, image_path) VALUES (?, ?)";
                $stmt = $conn->prepare($image_query);
                $stmt->bind_param("is", $property_id, $new_filename);
                $stmt->execute();
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Property created successfully',
        'property_id' => $property_id
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to create property']);
} 