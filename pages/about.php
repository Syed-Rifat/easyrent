<?php
// Start session
session_start();

// Set base path
$base_path = "../";
?>

<?php include_once "includes/header.php"; ?>
<?php include_once "includes/navbar.php"; ?>

<!-- Hero Section with Background Image -->
<div class="hero-section py-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x600?text=About+EasyRent') no-repeat center center; background-size: cover; min-height: 300px;">
    <div class="container py-5 text-center text-white">
        <h1 class="display-4 fw-bold mt-3">About Us</h1>
        <p class="lead mb-0">EasyRent - Your Trusted Rental Platform</p>
    </div>
</div>

<div class="container my-5">
    <!-- Back button -->
    <div class="row mb-3">
        <div class="col-md-10 mx-auto">
            <a href="<?php echo $base_url; ?>index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0">About EasyRent</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-5">
                            <!-- Simplest approach with online placeholder images -->
                            <img src="https://via.placeholder.com/450x300?text=About+EasyRent" alt="About EasyRent" class="img-fluid rounded shadow-sm mb-3">
                            
                            <!-- Property Images from online source as placeholder -->
                            <div class="row mt-3">
                                <div class="col-6">
                                    <img src="https://via.placeholder.com/200x150?text=Property+1" alt="Property 1" class="img-fluid rounded mb-2 shadow-sm">
                                </div>
                                <div class="col-6">
                                    <img src="https://via.placeholder.com/200x150?text=Property+2" alt="Property 2" class="img-fluid rounded mb-2 shadow-sm">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h4 class="text-primary">Your Trusted Rental Platform in Bangladesh</h4>
                            <p>
                                Welcome to EasyRent, the premier property rental platform in Bangladesh. 
                                Established in 2023, we've quickly become the go-to solution for property owners and 
                                tenants looking for a seamless rental experience.
                            </p>
                            <p>
                                At EasyRent, we believe that finding a home or renting out your property should be 
                                simple, secure, and stress-free. Our platform connects landlords with potential tenants 
                                through a user-friendly interface and transparent processes.
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-4 p-3 bg-light rounded">
                        <h4 class="text-primary">Our Mission</h4>
                        <p>
                            Our mission is to revolutionize the property rental experience in Bangladesh by providing 
                            a platform that empowers both landlords and tenants. We aim to bring transparency, 
                            efficiency, and security to the rental process, making it easier for people to find their 
                            perfect home and for property owners to find reliable tenants.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-primary">What We Offer</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3 border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5><i class="fas fa-user-tie text-primary me-2"></i> For Landlords</h5>
                                        <ul>
                                            <li>Easy property listing management</li>
                                            <li>Tenant screening and verification</li>
                                            <li>Secure payment collection</li>
                                            <li>Increased visibility for your properties</li>
                                            <li>Reduced vacancy periods</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3 border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5><i class="fas fa-home text-primary me-2"></i> For Tenants</h5>
                                        <ul>
                                            <li>Extensive property listings</li>
                                            <li>Advanced search filters</li>
                                            <li>Verified property information</li>
                                            <li>Secure booking process</li>
                                            <li>Direct communication with landlords</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-primary">Our Team</h4>
                        <p>
                            EasyRent is powered by a dedicated team of professionals with expertise in real estate, 
                            technology, and customer service. We're committed to continuously improving our platform 
                            based on user feedback and evolving market needs.
                        </p>
                        <div class="row mt-4">
                            <div class="col-lg-3 col-md-6 mb-4 text-center">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                                        <h5>Syed Rifat</h5>
                                        <p class="text-muted">CEO & Founder</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4 text-center">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                                        <h5>Mahfuja Khatun</h5>
                                        <p class="text-muted">Chief Technology Officer</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4 text-center">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                                        <h5>Kamal Hasan</h5>
                                        <p class="text-muted">Head of Operations</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4 text-center">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                                        <h5>Nusrat Jahan</h5>
                                        <p class="text-muted">Customer Relations</p>
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

<?php include_once "includes/footer.php"; ?> 