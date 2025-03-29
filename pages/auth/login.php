<?php
// Start session
session_start();

// Define DEBUG_MODE constant (false by default)
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $user_type = $_SESSION['user_type'];
    if ($user_type === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($user_type === 'landlord') {
        header("Location: ../landlord/dashboard.php");
    } elseif ($user_type === 'tenant') {
        header("Location: ../tenant/dashboard.php");
    }
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Define variables and set to empty values
$email = $password = $user_type = "";
$email_err = $password_err = $user_type_err = $login_err = "";
$debug_info = ""; // Debug info for troubleshooting

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check if user_type is selected
    if (empty($_POST["user_type"])) {
        $user_type_err = "Please select account type.";
    } else {
        $user_type = trim($_POST["user_type"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err) && empty($user_type_err)) {
        // Prepare a select statement with user_type filter
        $sql = "SELECT id, full_name, email, password, user_type FROM users WHERE email = ? AND user_type = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $param_email, $param_user_type);
            
            // Set parameters
            $param_email = $email;
            $param_user_type = $user_type;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if email exists for the selected role, if yes then verify password
                if ($stmt->num_rows == 1) {                    
                    // Bind result variables
                    $stmt->bind_result($id, $full_name, $email, $hashed_password, $user_type);
                    if ($stmt->fetch()) {
                        // For debugging - add password verification details
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            $debug_info = "Password verification: " . 
                                          (password_verify($password, $hashed_password) ? "Success" : "Failed");
                        }
                        
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["user_type"] = $user_type;
                            
                            // Redirect user to appropriate dashboard
                            if ($user_type === 'admin') {
                                header("location: ../admin/dashboard.php");
                            } elseif ($user_type === 'landlord') {
                                header("location: ../landlord/dashboard.php");
                            } elseif ($user_type === 'tenant') {
                                header("location: ../tenant/dashboard.php");
                            }
                            exit(); // Always exit after redirect
                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist for the selected role
                    $login_err = "No account found with this email for the selected role.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<?php include_once "../includes/header.php"; ?>
<?php include_once "../includes/navbar.php"; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php 
                    if(!empty($login_err)){
                        echo '<div class="alert alert-danger">' . $login_err . '</div>';
                    }
                    if(!empty($debug_info)){
                        echo '<div class="alert alert-info">' . $debug_info . '</div>';
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>    
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                                <span class="input-group-text">
                                    <i class="fas fa-eye toggle-password" toggle="#password"></i>
                                </span>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Role</label>
                            <div class="d-flex flex-column flex-md-row">
                                <div class="form-check me-4 mb-2">
                                    <input class="form-check-input <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" type="radio" name="user_type" id="admin" value="admin" <?php echo ($user_type == "admin") ? "checked" : ""; ?> required>
                                    <label class="form-check-label" for="admin">Admin</label>
                                </div>
                                <div class="form-check me-4 mb-2">
                                    <input class="form-check-input <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" type="radio" name="user_type" id="landlord" value="landlord" <?php echo ($user_type == "landlord") ? "checked" : ""; ?> required>
                                    <label class="form-check-label" for="landlord">Landlord</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" type="radio" name="user_type" id="tenant" value="tenant" <?php echo ($user_type == "tenant") ? "checked" : ""; ?> required>
                                    <label class="form-check-label" for="tenant">Tenant</label>
                                </div>
                            </div>
                            <?php if(!empty($user_type_err)): ?>
                                <div class="text-danger small mt-1"><?php echo $user_type_err; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <!-- Demo Accounts for Quick Testing -->
                    <div class="mt-4">
                        <h6 class="text-center mb-3">Test Accounts (For Demo Only)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>User Type</th>
                                        <th>Email</th>
                                        <th>Password</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Admin</td>
                                        <td>admin@easyrent.com</td>
                                        <td>admin123</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="fillLoginForm('admin@easyrent.com', 'admin123', 'admin')">
                                                Auto Fill
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Landlord</td>
                                        <td>landlord@easyrent.com</td>
                                        <td>admin123</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary"
                                                onclick="fillLoginForm('landlord@easyrent.com', 'admin123', 'landlord')">
                                                Auto Fill
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tenant</td>
                                        <td>tenant@easyrent.com</td>
                                        <td>admin123</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary"
                                                onclick="fillLoginForm('tenant@easyrent.com', 'admin123', 'tenant')">
                                                Auto Fill
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <script>
                                function fillLoginForm(email, password, userType) {
                                    document.querySelector('input[name="email"]').value = email;
                                    document.querySelector('input[name="password"]').value = password;
                                    document.querySelector(`input[name="user_type"][value="${userType}"]`).checked = true;
                                }
                            </script>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Sign up now</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 