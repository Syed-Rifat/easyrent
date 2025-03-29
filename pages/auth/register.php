<?php
// Start session
session_start();

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
$full_name = $email = $password = $confirm_password = $phone = $address = $user_type = "";
$full_name_err = $email_err = $password_err = $confirm_password_err = $user_type_err = "";
$registration_success = false;

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate full name
    if (empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate user type
    if (empty($_POST["user_type"])) {
        $user_type_err = "Please select account type.";
    } else {
        $user_type = $_POST["user_type"];
    }
    
    // Get other form data
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    $address = !empty($_POST["address"]) ? trim($_POST["address"]) : "";
    
    // Check input errors before inserting in database
    if (empty($full_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($user_type_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (full_name, email, password, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?)";
         
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssssss", $param_full_name, $param_email, $param_password, $param_phone, $param_address, $param_user_type);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_phone = $phone;
            $param_address = $address;
            $param_user_type = $user_type;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Registration successful
                $registration_success = true;
                
                // Reset form
                $full_name = $email = $password = $confirm_password = $phone = $address = $user_type = "";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
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
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Register an Account</h4>
                </div>
                <div class="card-body">
                    <?php 
                    if ($registration_success) {
                        echo '<div class="alert alert-success">Registration successful! You can now <a href="login.php">login</a>.</div>';
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>" required>
                            <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
                        </div>    
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                            <div class="invalid-feedback"><?php echo $email_err; ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" required>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>" required>
                                <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo $phone; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo $address; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <div class="d-flex">
                                <div class="form-check me-4">
                                    <input class="form-check-input <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" type="radio" name="user_type" id="tenant" value="tenant" <?php echo ($user_type == "tenant") ? "checked" : ""; ?> required>
                                    <label class="form-check-label" for="tenant">
                                        Tenant (I want to rent a property)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" type="radio" name="user_type" id="landlord" value="landlord" <?php echo ($user_type == "landlord") ? "checked" : ""; ?> required>
                                    <label class="form-check-label" for="landlord">
                                        Landlord (I want to rent out my property)
                                    </label>
                                </div>
                                <div class="invalid-feedback"><?php echo $user_type_err; ?></div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?> 