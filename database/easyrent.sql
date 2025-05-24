-- Create database
CREATE DATABASE IF NOT EXISTS easyrent;
USE easyrent;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('admin', 'landlord', 'tenant') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create properties table
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    landlord_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    property_type ENUM('apartment', 'house', 'room', 'office', 'commercial') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    bedrooms INT,
    bathrooms INT,
    area DECIMAL(10,2),
    location VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    amenities TEXT,
    image_url VARCHAR(255),
    status ENUM('available', 'rented', 'maintenance', 'pending') NOT NULL DEFAULT 'available',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create property_images table
CREATE TABLE IF NOT EXISTS property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    tenant_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'canceled', 'completed') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'mobile_banking') NOT NULL,
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    notes TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    tenant_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (tenant_id, property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create system_settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL DEFAULT 'EasyRent',
    site_email VARCHAR(255) NOT NULL,
    maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
    enable_notifications TINYINT(1) NOT NULL DEFAULT 1,
    enable_email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert demo admin user
INSERT INTO users (full_name, email, password, user_type) VALUES 
('Admin User', 'admin@easyrent.com', '$2y$10$oOP8mihmIh5wzXoib1.bJut/RGD91dhs/VkrT6f9fYetcNTrjHKh.', 'admin');
-- Password is 'admin123'

-- Insert demo landlord
INSERT INTO users (full_name, email, password, phone, address, user_type) VALUES 
('Landlord Demo', 'landlord@easyrent.com', '$2y$10$oOP8mihmIh5wzXoib1.bJut/RGD91dhs/VkrT6f9fYetcNTrjHKh.', '+8801712345678', 'Dhaka, Bangladesh', 'landlord');
-- Password is 'admin123'

-- Insert demo tenant
INSERT INTO users (full_name, email, password, phone, address, user_type) VALUES 
('Tenant Demo', 'tenant@easyrent.com', '$2y$10$oOP8mihmIh5wzXoib1.bJut/RGD91dhs/VkrT6f9fYetcNTrjHKh.', '+8801698765432', 'Dhaka, Bangladesh', 'tenant');
-- Password is 'admin123'

-- Insert demo properties
INSERT INTO properties (landlord_id, title, description, property_type, price, bedrooms, bathrooms, area, location, address, amenities, image_url, featured) VALUES
(2, 'Luxury Apartment in Gulshan', 'Beautiful apartment with modern amenities in a prime location.', 'apartment', 50000.00, 3, 2, 1500.00, 'Gulshan, Dhaka', 'House #5, Road #7, Gulshan-1, Dhaka-1212', 'Air Conditioning, WiFi, Parking, Swimming Pool, Gym', 'assets/images/properties/property1.jpg', TRUE),
(2, 'Cozy Studio in Dhanmondi', 'Perfect studio apartment for students or young professionals.', 'apartment', 20000.00, 1, 1, 700.00, 'Dhanmondi, Dhaka', 'House #15, Road #2, Dhanmondi, Dhaka-1209', 'WiFi, Balcony, Kitchen, Washing Machine', 'assets/images/properties/property2.jpg', TRUE),
(2, 'Family House in Uttara', 'Spacious house ideal for families, close to schools and shopping centers.', 'house', 35000.00, 4, 3, 2200.00, 'Uttara, Dhaka', 'House #7, Sector #3, Uttara, Dhaka-1230', 'Garden, Garage, WiFi, Air Conditioning, Backup Generator', 'assets/images/properties/property3.jpg', TRUE),
(2, 'Office Space in Banani', 'Modern office space perfect for startups and small businesses.', 'office', 45000.00, 0, 2, 1000.00, 'Banani, Dhaka', 'Plot #17, Road #11, Banani, Dhaka-1213', 'Reception Area, Conference Room, High Speed Internet, Parking', 'assets/images/properties/property4.jpg', FALSE),
(2, 'Bachelor Pad in Mirpur', 'Affordable room for bachelors with basic amenities.', 'room', 8000.00, 1, 1, 300.00, 'Mirpur, Dhaka', 'House #12, Road #5, Mirpur-10, Dhaka-1216', 'WiFi, Shared Kitchen, Water Supply', 'assets/images/properties/property5.jpg', FALSE);

-- Add status column to users table
ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active';

-- Insert default settings
INSERT INTO system_settings (id, site_name, site_email) VALUES (1, 'EasyRent', 'admin@easyrent.com');
