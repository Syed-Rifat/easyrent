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
    $user_id = (int)$_GET['id'];
    
    // Get user details
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Format dates
        $user['created_at'] = date('M d, Y', strtotime($user['created_at']));
        
        // Remove sensitive information
        unset($user['password']);
        
        header('Content-Type: application/json');
        echo json_encode($user);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID not provided']);
} 