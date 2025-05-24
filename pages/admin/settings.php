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

// Handle profile update
$update_message = '';
$update_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    if (empty($full_name) || empty($email)) {
        $update_message = '<div class="alert alert-danger">Name and email are required fields.</div>';
    } else {
        // Update admin profile
        $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $full_name, $email, $phone, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $update_success = true;
            $update_message = '<div class="alert alert-success">Profile updated successfully!</div>';
        } else {
            $update_message = '<div class="alert alert-danger">Failed to update profile: ' . $conn->error . '</div>';
        }
    }
}

// Handle password change
$password_message = '';
$password_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_message = '<div class="alert alert-danger">All password fields are required.</div>';
    } else if ($new_password !== $confirm_password) {
        $password_message = '<div class="alert alert-danger">New passwords do not match.</div>';
    } else if (strlen($new_password) < 6) {
        $password_message = '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
    } else {
        // Verify current password
        $password_query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $admin_id);
            
            if ($stmt->execute()) {
                $password_success = true;
                $password_message = '<div class="alert alert-success">Password updated successfully!</div>';
            } else {
                $password_message = '<div class="alert alert-danger">Failed to update password: ' . $conn->error . '</div>';
            }
        } else {
            $password_message = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}

// Handle system settings update
$system_message = '';
$system_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system'])) {
    $site_name = trim($_POST['site_name']);
    $site_email = trim($_POST['site_email']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
    $enable_email_notifications = isset($_POST['enable_email_notifications']) ? 1 : 0;
    
    // Update system settings
    $update_query = "UPDATE system_settings SET 
                    site_name = ?, 
                    site_email = ?, 
                    maintenance_mode = ?, 
                    enable_notifications = ?, 
                    enable_email_notifications = ? 
                    WHERE id = 1";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssiii", $site_name, $site_email, $maintenance_mode, $enable_notifications, $enable_email_notifications);
    
    if ($stmt->execute()) {
        $system_success = true;
        $system_message = '<div class="alert alert-success">System settings updated successfully!</div>';
    } else {
        $system_message = '<div class="alert alert-danger">Failed to update system settings: ' . $conn->error . '</div>';
    }
}

// Get admin details
$admin_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

// Get system settings
$settings_query = "SELECT * FROM system_settings WHERE id = 1";
$settings_result = $conn->query($settings_query);
$system_settings = $settings_result->fetch_assoc();
?>

<?php include_once "../includes/header.php"; ?>

<div class="container-fluid my-5">
    <div class="row">
        <?php include_once "includes/sidebar.php"; ?>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <h2 class="mb-4">Account Settings</h2>
            
            <!-- Profile Settings -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php echo $update_message; ?>
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($admin_data['phone'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <!-- Password Settings -->
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <?php echo $password_message; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
                    </form>
                </div>
            </div>
            
            <!-- System Settings -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">System Settings</h5>
                </div>
                <div class="card-body">
                    <?php echo $system_message; ?>
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($system_settings['site_name'] ?? 'EasyRent'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="site_email" class="form-label">Site Email</label>
                                <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($system_settings['site_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                    <small class="form-text text-muted d-block">Enable this to put the site in maintenance mode</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo ($system_settings['enable_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_notifications">Enable Notifications</label>
                                    <small class="form-text text-muted d-block">Enable system notifications</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_email_notifications" name="enable_email_notifications" <?php echo ($system_settings['enable_email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_email_notifications">Enable Email Notifications</label>
                                    <small class="form-text text-muted d-block">Enable email notifications for system events</small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_system" class="btn btn-info">Update System Settings</button>
                    </form>
                </div>
            </div>
            
            <!-- Backup & Maintenance -->
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Backup & Maintenance</h5>
                </div>
                <div class="card-body">
                    <?php 
                    if (isset($_SESSION['backup_message'])) {
                        echo $_SESSION['backup_message'];
                        unset($_SESSION['backup_message']);
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Database Backup</h5>
                                    <p class="card-text">Create a backup of your database.</p>
                                    <form method="POST" action="backup.php">
                                        <button type="submit" name="create_backup" class="btn btn-outline-warning">
                                            <i class="fas fa-database me-2"></i> Backup Now
                                        </button>
                                    </form>
                                    
                                    <?php
                                    // Get list of existing backups
                                    $backups = array();
                                    $backup_dir = "../../backups";
                                    if (file_exists($backup_dir)) {
                                        $files = scandir($backup_dir);
                                        foreach ($files as $file) {
                                            if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) == "sql") {
                                                $backups[] = $file;
                                            }
                                        }
                                        rsort($backups); // Sort backups by date (newest first)
                                    }
                                    
                                    if (!empty($backups)): ?>
                                        <div class="mt-3">
                                            <h6>Recent Backups:</h6>
                                            <ul class="list-group">
                                                <?php foreach (array_slice($backups, 0, 5) as $backup): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?php echo $backup; ?>
                                                        <a href="backup.php?download=<?php echo urlencode($backup); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">System Logs</h5>
                                    <p class="card-text">View and manage system logs.</p>
                                    <?php
                                    $log_file = "../../logs/system.log";
                                    $logs = array();
                                    
                                    if (file_exists($log_file)) {
                                        $logs = array_slice(file($log_file), -50); // Get last 50 lines
                                        $logs = array_reverse($logs); // Show newest first
                                    }
                                    ?>
                                    
                                    <div class="log-container" style="max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($logs)): ?>
                                            <ul class="list-group">
                                                <?php foreach ($logs as $log): ?>
                                                    <li class="list-group-item">
                                                        <small class="text-muted"><?php echo htmlspecialchars($log); ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No logs available.</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <form method="POST" action="backup.php">
                                            <button type="submit" name="clear_logs" class="btn btn-outline-danger">
                                                <i class="fas fa-trash me-2"></i> Clear Logs
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Remove the old JavaScript functions since we're using forms now
</script>

<?php include_once "../includes/footer.php"; ?> 