<?php
// Start session
session_start();

// Set base path
$base_path = "../";

// Initialize variables
$name = $email = $subject = $message = "";
$name_err = $email_err = $subject_err = $message_err = "";
$success_message = $error_message = "";

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate subject
    if (empty(trim($_POST["subject"]))) {
        $subject_err = "Please enter a subject.";
    } else {
        $subject = trim($_POST["subject"]);
    }
    
    // Validate message
    if (empty(trim($_POST["message"]))) {
        $message_err = "Please enter your message.";
    } else {
        $message = trim($_POST["message"]);
    }
    
    // Check if no errors
    if (empty($name_err) && empty($email_err) && empty($subject_err) && empty($message_err)) {
        // In a real application, you would send an email here
        // For demo purposes, we'll just show a success message
        $success_message = "Your message has been sent successfully! We'll get back to you soon.";
        
        // Clear form fields after successful submission
        $name = $email = $subject = $message = "";
    } else {
        $error_message = "Please fix the errors in the form.";
    }
}
?>

<?php include_once "includes/header.php"; ?>
<?php include_once "includes/navbar.php"; ?>

<!-- Hero Section with Background Image -->
<div class="hero-section py-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x600?text=Contact+EasyRent') no-repeat center center; background-size: cover; min-height: 300px;">
    <div class="container py-5 text-center text-white">
        <h1 class="display-4 fw-bold mt-3">Contact Us</h1>
        <p class="lead mb-0">We're waiting to hear from you</p>
    </div>
</div>

<div class="container my-5">
    <!-- Back button -->
    <div class="row mb-3">
        <div class="col-md-8 mx-auto">
            <a href="<?php echo $base_url; ?>index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0">Contact Us</h3>
                </div>
                <div class="card-body">
                    <?php if(!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                            <div class="invalid-feedback"><?php echo $name_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                            <div class="invalid-feedback"><?php echo $email_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $subject; ?>">
                            <div class="invalid-feedback"><?php echo $subject_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea name="message" rows="5" class="form-control <?php echo (!empty($message_err)) ? 'is-invalid' : ''; ?>"><?php echo $message; ?></textarea>
                            <div class="invalid-feedback"><?php echo $message_err; ?></div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8 mx-auto">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0">Get In Touch</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h4 class="text-primary"><i class="fas fa-map-marker-alt text-primary me-2"></i> Our Location</h4>
                            <p>
                                123 Rental Street<br>
                                Gulshan, Dhaka 1212<br>
                                Bangladesh
                            </p>
                            <h4 class="text-primary"><i class="fas fa-phone text-primary me-2"></i> Phone</h4>
                            <p>
                                +880 1234-567890<br>
                                +880 1987-654321
                            </p>
                            <h4 class="text-primary"><i class="fas fa-envelope text-primary me-2"></i> Email</h4>
                            <p>
                                info@easyrent.com<br>
                                support@easyrent.com
                            </p>
                            <h4 class="text-primary"><i class="fas fa-clock text-primary me-2"></i> Business Hours</h4>
                            <p>
                                Monday - Friday: 9:00 AM - 6:00 PM<br>
                                Saturday: 10:00 AM - 4:00 PM<br>
                                Sunday: Closed
                            </p>
                        </div>
                        <div class="col-md-6">
                            <div class="ratio ratio-16x9 shadow-sm">
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14602.25897402477!2d90.4115813!3d23.7905555!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c7a0f70deb73%3A0x30c36498f90fe23!2sGulshan%2C%20Dhaka!5e0!3m2!1sen!2sbd!4v1625580000000!5m2!1sen!2sbd" allowfullscreen="" loading="lazy" class="border rounded shadow-sm"></iframe>
                            </div>
                            <div class="mt-4">
                                <h4 class="text-primary"><i class="fas fa-share-alt text-primary me-2"></i> Connect With Us</h4>
                                <div class="social-icons">
                                    <a href="#" class="btn btn-outline-primary me-2 mb-2"><i class="fab fa-facebook-f"></i> Facebook</a>
                                    <a href="#" class="btn btn-outline-info me-2 mb-2"><i class="fab fa-twitter"></i> Twitter</a>
                                    <a href="#" class="btn btn-outline-danger me-2 mb-2"><i class="fab fa-instagram"></i> Instagram</a>
                                    <a href="#" class="btn btn-outline-dark mb-2"><i class="fab fa-linkedin-in"></i> LinkedIn</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?> 