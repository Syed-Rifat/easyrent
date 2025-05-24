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

if (isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    
    // Get property details
    $query = "SELECT p.*, u.full_name as landlord_name 
              FROM properties p 
              JOIN users u ON p.landlord_id = u.id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $property = $result->fetch_assoc();
        
        // Get property images
        $images_query = "SELECT id, image_url FROM property_images WHERE property_id = ?";
        $images_stmt = $conn->prepare($images_query);
        $images_stmt->bind_param("i", $property_id);
        $images_stmt->execute();
        $images_result = $images_stmt->get_result();
        
        $images = [];
        while ($image = $images_result->fetch_assoc()) {
            $images[] = $image;
        }
        
        $property['images'] = $images;
        
        header('Content-Type: application/json');
        echo json_encode($property);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Property not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Property ID not provided']);
} 