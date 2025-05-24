<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];

// Initialize status message
$status_message = '';

// Handle property creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    // Check if this is a duplicate submission
    if (isset($_SESSION['last_property_submission']) && 
        (time() - $_SESSION['last_property_submission']) < 5) {
        $status_message = '<div class="alert alert-warning">Please wait a few seconds before adding another property.</div>';
    } else {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $property_type = $_POST['property_type'];
        $price = $_POST['price'];
        $bedrooms = $_POST['bedrooms'];
        $bathrooms = $_POST['bathrooms'];
        $area = $_POST['area'];
        $location = $_POST['location'];
        $address = $_POST['address'];
        $landlord_id = $_POST['landlord_id'];
        $status = 'available';
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert property
            $insert_query = "INSERT INTO properties (landlord_id, title, description, property_type, price, 
                            bedrooms, bathrooms, area, location, address, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isssdiiisss", $landlord_id, $title, $description, $property_type, $price, 
                              $bedrooms, $bathrooms, $area, $location, $address, $status);
            
            if ($stmt->execute()) {
                $property_id = $conn->insert_id;
                
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = "../../uploads/properties/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_name = $_FILES['images']['name'][$key];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            // Validate file type
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                            if (in_array($file_ext, $allowed_types)) {
                                $new_file_name = uniqid() . '.' . $file_ext;
                                $target_path = $upload_dir . $new_file_name;
                                
                                if (move_uploaded_file($tmp_name, $target_path)) {
                                    // Insert image record
                                    $image_query = "INSERT INTO property_images (property_id, image_url) VALUES (?, ?)";
                                    $image_stmt = $conn->prepare($image_query);
                                    $image_stmt->bind_param("is", $property_id, $new_file_name);
                                    $image_stmt->execute();
                                }
                            }
                        }
                    }
                }
                
                $conn->commit();
                $_SESSION['last_property_submission'] = time();
                $_SESSION['status_message'] = '<div class="alert alert-success">Property added successfully!</div>';
                header("Location: properties.php");
                exit();
            } else {
                throw new Exception("Failed to add property.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $status_message = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
        }
    }
}

// Handle property update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_property'])) {
    $property_id = $_POST['property_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $property_type = $_POST['property_type'];
    $price = $_POST['price'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $area = $_POST['area'];
    $location = $_POST['location'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update property
        $update_query = "UPDATE properties SET 
                        title = ?, description = ?, property_type = ?, price = ?,
                        bedrooms = ?, bathrooms = ?, area = ?, location = ?, 
                        address = ?, status = ?
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssdiiisssi", $title, $description, $property_type, $price,
                          $bedrooms, $bathrooms, $area, $location, $address, $status, $property_id);
        
        if ($stmt->execute()) {
            // Handle new image uploads
            if (!empty($_FILES['new_images']['name'][0])) {
                $upload_dir = "../../uploads/properties/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['new_images']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Validate file type
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($file_ext, $allowed_types)) {
                            $new_file_name = uniqid() . '.' . $file_ext;
                            $target_path = $upload_dir . $new_file_name;
                            
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                // Insert image record
                                $image_query = "INSERT INTO property_images (property_id, image_url) VALUES (?, ?)";
                                $image_stmt = $conn->prepare($image_query);
                                $image_stmt->bind_param("is", $property_id, $new_file_name);
                                $image_stmt->execute();
                            }
                        }
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['status_message'] = '<div class="alert alert-success">Property updated successfully!</div>';
            header("Location: properties.php");
            exit();
        } else {
            throw new Exception("Failed to update property.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $status_message = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id = $_POST['property_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if property has any bookings
        $check_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE property_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $property_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();
        
        if ($counts['booking_count'] > 0) {
            throw new Exception("Cannot delete property with associated bookings.");
        }
        
        // Get property images
        $images_query = "SELECT image_url FROM property_images WHERE property_id = ?";
        $stmt = $conn->prepare($images_query);
        $stmt->bind_param("i", $property_id);
        $stmt->execute();
        $images = $stmt->get_result();
        
        // Delete image files
        while ($image = $images->fetch_assoc()) {
            $file_path = "../../uploads/properties/" . $image['image_url'];
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
            $conn->commit();
            $_SESSION['status_message'] = '<div class="alert alert-success">Property deleted successfully!</div>';
            header("Location: properties.php");
            exit();
        } else {
            throw new Exception("Failed to delete property.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $status_message = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Check for session messages
if (isset($_SESSION['status_message'])) {
    $status_message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM properties";
$total_records = $conn->query($total_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get properties with pagination
$properties_query = "SELECT p.*, u.full_name as landlord_name 
                    FROM properties p 
                    JOIN users u ON p.landlord_id = u.id 
                    ORDER BY p.created_at DESC 
                    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($properties_query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$properties = $stmt->get_result();

// Get landlords for dropdown
$landlords_query = "SELECT id, full_name FROM users WHERE user_type = 'landlord' ORDER BY full_name";
$landlords = $conn->query($landlords_query);

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $status_message = '<div class="alert alert-success">Property added successfully!</div>';
}
?>

<?php include_once "../includes/header.php"; ?>

<div class="container-fluid my-5">
    <div class="row">
        <?php include_once "includes/sidebar.php"; ?>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Properties</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                    <i class="fas fa-plus me-2"></i> Add New Property
                </button>
            </div>
            
            <?php echo $status_message; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Landlord</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($properties->num_rows > 0): ?>
                                    <?php while ($property = $properties->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $property['id']; ?></td>
                                            <td><?php echo htmlspecialchars($property['title']); ?></td>
                                            <td><?php echo htmlspecialchars($property['location']); ?></td>
                                            <td><?php echo ucfirst($property['property_type']); ?></td>
                                            <td>৳<?php echo number_format($property['price']); ?></td>
                                            <td><?php echo htmlspecialchars($property['landlord_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($property['status'] == 'active') ? 'bg-success' : 
                                                                        (($property['status'] == 'pending') ? 'bg-warning' : 'bg-danger'); ?>">
                                                    <?php echo ucfirst($property['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewProperty(<?php echo $property['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="editProperty(<?php echo $property['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteProperty(<?php echo $property['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No properties found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Property Modal -->
<div class="modal fade" id="viewPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="propertyDetails">
                    <div class="row">
                        <div class="col-md-6">
                            <div id="propertyImages" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner" id="propertyCarousel">
                                    <!-- Images will be loaded here -->
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyImages" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyImages" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="propertyInfo">
                                <!-- Property details will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Property Modal -->
<div class="modal fade" id="editPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="property_id" id="edit_property_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Property Type</label>
                            <select class="form-select" name="property_type" id="edit_property_type" required>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="room">Room</option>
                                <option value="office">Office</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Price (৳)</label>
                            <input type="number" class="form-control" name="price" id="edit_price" required min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" class="form-control" name="bedrooms" id="edit_bedrooms" required min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" class="form-control" name="bathrooms" id="edit_bathrooms" required min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area (sq ft)</label>
                            <input type="number" class="form-control" name="area" id="edit_area" required min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="edit_location" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="edit_address" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Add New Images</label>
                        <input type="file" class="form-control" name="new_images[]" multiple accept="image/*">
                        <small class="text-muted">You can select multiple images</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Images</label>
                        <div id="current_images" class="row"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_property" class="btn btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Property Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this property? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deletePropertyForm">
                    <input type="hidden" name="property_id" id="delete_property_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_property" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Property Modal -->
<div class="modal fade" id="addPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Property Type</label>
                            <select class="form-select" name="property_type" required>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="room">Room</option>
                                <option value="office">Office</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Price (৳)</label>
                            <input type="number" class="form-control" name="price" required min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Landlord</label>
                            <select class="form-select" name="landlord_id" required>
                                <option value="">Select Landlord</option>
                                <?php while ($landlord = $landlords->fetch_assoc()): ?>
                                    <option value="<?php echo $landlord['id']; ?>">
                                        <?php echo htmlspecialchars($landlord['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" class="form-control" name="bedrooms" required min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" class="form-control" name="bathrooms" required min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area (sq ft)</label>
                            <input type="number" class="form-control" name="area" required min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Property Images</label>
                        <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                        <small class="text-muted">You can select multiple images</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_property" class="btn btn-primary">Add Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewProperty(propertyId) {
    // Fetch property details
    fetch(`get_property.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Display property images
            const carousel = document.getElementById('propertyCarousel');
            carousel.innerHTML = '';
            
            if (data.images && data.images.length > 0) {
                data.images.forEach((image, index) => {
                    const div = document.createElement('div');
                    div.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                    div.innerHTML = `
                        <img src="../../uploads/properties/${image.image_url}" 
                             class="d-block w-100" 
                             alt="Property Image"
                             style="max-height: 400px; object-fit: cover;">
                    `;
                    carousel.appendChild(div);
                });
            } else {
                // Show default image if no images available
                carousel.innerHTML = `
                    <div class="carousel-item active">
                        <img src="../../assets/images/no-image.jpg" 
                             class="d-block w-100" 
                             alt="No Image Available"
                             style="max-height: 400px; object-fit: cover;">
                    </div>
                `;
            }
            
            // Display property information
            const propertyInfo = document.getElementById('propertyInfo');
            propertyInfo.innerHTML = `
                <h4 class="mb-3">${data.title}</h4>
                <p><strong>Type:</strong> ${data.property_type}</p>
                <p><strong>Price:</strong> ৳${data.price.toLocaleString()}</p>
                <p><strong>Location:</strong> ${data.location}</p>
                <p><strong>Address:</strong> ${data.address}</p>
                <p><strong>Bedrooms:</strong> ${data.bedrooms}</p>
                <p><strong>Bathrooms:</strong> ${data.bathrooms}</p>
                <p><strong>Area:</strong> ${data.area} sq ft</p>
                <p><strong>Status:</strong> 
                    <span class="badge ${data.status === 'available' ? 'bg-success' : 
                                      (data.status === 'rented' ? 'bg-danger' : 
                                      (data.status === 'maintenance' ? 'bg-warning' : 'bg-info'))}">
                        ${data.status}
                    </span>
                </p>
                <p><strong>Description:</strong></p>
                <p>${data.description}</p>
                <p><strong>Landlord:</strong> ${data.landlord_name}</p>
                <p><strong>Created At:</strong> ${new Date(data.created_at).toLocaleDateString()}</p>
            `;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('viewPropertyModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load property details');
        });
}

function editProperty(propertyId) {
    // Fetch property details
    fetch(`get_property.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
            // Populate form fields
            document.getElementById('edit_property_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_property_type').value = data.property_type;
            document.getElementById('edit_price').value = data.price;
            document.getElementById('edit_bedrooms').value = data.bedrooms;
            document.getElementById('edit_bathrooms').value = data.bathrooms;
            document.getElementById('edit_area').value = data.area;
            document.getElementById('edit_location').value = data.location;
            document.getElementById('edit_address').value = data.address;
            document.getElementById('edit_status').value = data.status;
            
            // Display current images
            const imagesContainer = document.getElementById('current_images');
            imagesContainer.innerHTML = '';
            data.images.forEach(image => {
                imagesContainer.innerHTML += `
                    <div class="col-md-3 mb-2">
                        <div class="position-relative">
                            <img src="../../uploads/properties/${image.image_url}" class="img-fluid rounded" alt="Property Image">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                                    onclick="deleteImage(${image.id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            // Show modal
            new bootstrap.Modal(document.getElementById('editPropertyModal')).show();
        });
}

function deleteProperty(propertyId) {
    document.getElementById('delete_property_id').value = propertyId;
    new bootstrap.Modal(document.getElementById('deletePropertyModal')).show();
}

function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        fetch('delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `image_id=${imageId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove image from display
                const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
                if (imageElement) {
                    imageElement.closest('.col-md-3').remove();
                }
            } else {
                alert('Failed to delete image: ' + data.message);
            }
        });
    }
}

// Add CSRF token to forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.querySelector('input[name="csrf_token"]')) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
            form.appendChild(csrfInput);
        }
    });
});
</script>

<?php include_once "../includes/footer.php"; ?> 