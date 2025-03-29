<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>index.php">EasyRent</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/properties/all_properties.php">Properties</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/contact.php">Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['loggedin'] === true): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/admin/dashboard.php">Dashboard</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'landlord'): ?>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/landlord/dashboard.php">Dashboard</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'tenant'): ?>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/tenant/dashboard.php">Dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>pages/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>pages/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 