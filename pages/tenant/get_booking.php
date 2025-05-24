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

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$booking_id = $_GET['id'];

// Get booking details
$booking_query = "SELECT b.*, p.title as property_title, p.location, p.property_type,
                 p.price, u.full_name as landlord_name, u.phone as landlord_phone,
                 u.email as landlord_email
                 FROM bookings b
                 JOIN properties p ON b.property_id = p.id
                 JOIN users u ON p.landlord_id = u.id
                 WHERE b.id = ? AND b.tenant_id = ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("ii", $booking_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

$booking = $result->fetch_assoc();

// Format dates
$booking['start_date'] = date('Y-m-d', strtotime($booking['start_date']));
$booking['end_date'] = date('Y-m-d', strtotime($booking['end_date']));
$booking['created_at'] = date('Y-m-d H:i:s', strtotime($booking['created_at']));

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'booking' => $booking
]); 