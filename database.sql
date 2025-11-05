-- PG/Flat Finder Database Schema - Enhanced Version
-- MySQL/MariaDB Compatible
-- Drop and recreate database for fresh installation

DROP DATABASE IF EXISTS pg_finder;
CREATE DATABASE pg_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pg_finder;

-- Users Table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  role ENUM('tenant','landlord','admin') DEFAULT 'tenant',
  is_active BOOLEAN DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listings Table
CREATE TABLE listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  rent DECIMAL(10,2) NOT NULL,
  address VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  gender ENUM('male','female','any') DEFAULT 'any',
  furnished BOOLEAN DEFAULT 0,
  amenities TEXT,
  available_from DATE,
  is_active BOOLEAN DEFAULT 1,
  views INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_city (city),
  INDEX idx_rent (rent),
  INDEX idx_user_id (user_id),
  INDEX idx_gender (gender)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listing Images Table
CREATE TABLE listing_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary BOOLEAN DEFAULT 0,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  INDEX idx_listing_id (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites Table
CREATE TABLE favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  listing_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  UNIQUE KEY unique_favorite (user_id, listing_id),
  INDEX idx_user_id (user_id),
  INDEX idx_listing_id (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inquiries Table
CREATE TABLE inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  message TEXT,
  status ENUM('pending','responded','closed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_listing_id (listing_id),
  INDEX idx_user_id (user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Users (password for all: pass123)
INSERT INTO users (name, email, phone, password, role) VALUES 
('Admin User', 'admin@pgfinder.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Landlord', 'landlord@test.com', '9876543211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'landlord'),
('Jane Tenant', 'tenant@test.com', '9876543212', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant'),
('Rajesh Kumar', 'rajesh.landlord@gmail.com', '9876543213', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'landlord'),
('Priya Sharma', 'priya.tenant@gmail.com', '9876543214', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant'),
('Amit Patel', 'amit.tenant@gmail.com', '9876543215', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant'),
('Sneha Desai', 'sneha.tenant@gmail.com', '9876543216', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant');

-- Insert Sample Listings with Rich Data
INSERT INTO listings (user_id, title, description, rent, address, city, gender, furnished, amenities, available_from) VALUES 
(2, 'Luxury PG Near IT Park', 'Premium single room with AC, attached bathroom, and modern amenities. Located in the heart of IT hub with excellent connectivity.', 8500.00, '301 Tech Boulevard, SG Highway', 'Ahmedabad', 'male', 1, 'WiFi,AC,Food,Laundry,Parking,Security,Gym', '2025-11-15'),
(2, 'Affordable Girls PG in Satellite', 'Safe and secure PG exclusively for working women. Includes meals, housekeeping, and 24x7 security with CCTV surveillance.', 7500.00, '45 Satellite Road, Near ISRO', 'Ahmedabad', 'female', 1, 'WiFi,Food,Security,Laundry,Water,AC', '2025-11-10'),
(2, 'Spacious 2BHK Flat', 'Well-maintained 2BHK flat with modern amenities. Perfect for small families or working professionals. Semi-furnished with sofa and beds.', 15000.00, '78 Prahlad Nagar, Near Judges Bungalow', 'Ahmedabad', 'any', 1, 'WiFi,Parking,Gym,Security,Water', '2025-12-01'),
(2, 'Budget PG for Students', 'Economical PG accommodation perfect for college students. Close to multiple universities and colleges. Clean and hygienic environment.', 5500.00, '12 Paldi Main Road, Near Gujarat University', 'Ahmedabad', 'male', 0, 'WiFi,Food,Laundry,Water', '2025-11-20'),
(2, 'Premium 3BHK Apartment', 'Luxurious 3BHK fully furnished apartment in prime location. All modern amenities with covered parking and 24x7 security.', 25000.00, '501 Sindhu Bhavan Marg, Bodakdev', 'Ahmedabad', 'any', 1, 'WiFi,AC,Parking,Gym,Security,Water', '2025-12-10'),
(2, 'Cozy PG for Working Women', 'Comfortable PG with homely atmosphere. Nutritious meals provided. Walking distance from metro station.', 7000.00, '89 Ashram Road, Ellis Bridge', 'Ahmedabad', 'female', 1, 'WiFi,Food,Security,Laundry,AC', '2025-11-25'),
(2, 'Single Room PG Near Airport', 'Clean single occupancy rooms with all basic amenities. Ideal for professionals working near airport area.', 6000.00, '234 Airport Road, Hansol', 'Ahmedabad', 'any', 0, 'WiFi,Parking,Water,Security', '2025-11-18'),
(2, '1BHK Flat in Maninagar', 'Compact 1BHK flat perfect for bachelors or small families. Semi-furnished with kitchen and balcony.', 9000.00, '67 Maninagar East', 'Ahmedabad', 'any', 1, 'WiFi,Parking,Water', '2025-12-05'),
(2, 'Executive PG with Meals', 'Premium PG for working professionals. Includes breakfast, lunch, and dinner. Fully AC rooms with attached bathrooms.', 9500.00, '156 CG Road, Navrangpura', 'Ahmedabad', 'male', 1, 'WiFi,AC,Food,Laundry,Parking,Gym', '2025-11-22'),
(2, 'Girls Hostel Near College', 'Safe hostel accommodation for female students. Strict security measures with warden supervision. Study room available.', 6500.00, '28 Law Garden Road', 'Ahmedabad', 'female', 0, 'WiFi,Food,Security,Laundry,Water', '2025-11-12'),
(2, 'Duplex Villa for Rent', 'Spacious 4BHK duplex villa with garden. Perfect for large families. Independent house with parking for 3 cars.', 35000.00, '12 Shilaj Circle, Near Shilaj', 'Ahmedabad', 'any', 1, 'WiFi,AC,Parking,Gym,Security,Water', '2025-12-15'),
(2, 'Shared Room PG Economy', 'Budget-friendly shared accommodation for 2-3 people. Basic amenities provided. Near bus stand for easy commute.', 4000.00, '45 Naroda Road', 'Ahmedabad', 'male', 0, 'WiFi,Water,Laundry', '2025-11-16'),
(2, 'Studio Apartment SG Road', 'Modern studio apartment with kitchenette. Fully furnished with contemporary interiors. Ideal for single professionals.', 12000.00, '789 SG Highway, Near Rajpath Club', 'Ahmedabad', 'any', 1, 'WiFi,AC,Parking,Gym,Security', '2025-12-08'),
(2, 'Premium PG with Swimming Pool', 'Luxury PG accommodation with swimming pool, gym, and all premium facilities. Chef-prepared meals included.', 11000.00, '23 Vastrapur Lake, Near IIM', 'Ahmedabad', 'male', 1, 'WiFi,AC,Food,Laundry,Parking,Gym,Security,Water', '2025-11-28'),
(2, 'Family PG Accommodation', 'Spacious rooms suitable for families. 2 rooms with common kitchen facility. Safe neighborhood.', 13000.00, '67 Ghatlodia Circle', 'Ahmedabad', 'any', 1, 'WiFi,Parking,Water,Security', '2025-12-12');

-- Insert Images using Web URLs (Unsplash - free high-quality images)
INSERT INTO listing_images (listing_id, image_path, is_primary, display_order) VALUES 
-- Listing 1: Luxury PG Near IT Park
(1, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=800', 1, 1),
(1, 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800', 0, 2),
(1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 0, 3),

-- Listing 2: Girls PG
(2, 'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=800', 1, 1),
(2, 'https://images.unsplash.com/photo-1560185007-5f0bb1866cab?w=800', 0, 2),

-- Listing 3: 2BHK Flat
(3, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800', 1, 1),
(3, 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800', 0, 2),
(3, 'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800', 0, 3),

-- Listing 4: Budget PG
(4, 'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800', 1, 1),
(4, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=800', 0, 2),

-- Listing 5: Premium 3BHK
(5, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800', 1, 1),
(5, 'https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=800', 0, 2),
(5, 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?w=800', 0, 3),

-- Listing 6: Working Women PG
(6, 'https://images.unsplash.com/photo-1556020685-ae41abfc9365?w=800', 1, 1),
(6, 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=800', 0, 2),

-- Listing 7: Single Room PG
(7, 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=800', 1, 1),
(7, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=800', 0, 2),

-- Listing 8: 1BHK Flat
(8, 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800', 1, 1),
(8, 'https://images.unsplash.com/photo-1556912172-45b7abe8b7e1?w=800', 0, 2),

-- Listing 9: Executive PG
(9, 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=800', 1, 1),
(9, 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800', 0, 2),
(9, 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=800', 0, 3),

-- Listing 10: Girls Hostel
(10, 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=800', 1, 1),
(10, 'https://images.unsplash.com/photo-1595515106969-1ce29566ff1c?w=800', 0, 2),

-- Listing 11: Duplex Villa
(11, 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800', 1, 1),
(11, 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800', 0, 2),
(11, 'https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=800', 0, 3),

-- Listing 12: Shared Room
(12, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800', 1, 1),

-- Listing 13: Studio Apartment
(13, 'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?w=800', 1, 1),
(13, 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=800', 0, 2),

-- Listing 14: Premium PG with Pool
(14, 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800', 1, 1),
(14, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800', 0, 2),
(14, 'https://images.unsplash.com/photo-1600566753151-384129cf4e3e?w=800', 0, 3),

-- Listing 15: Family PG
(15, 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=800', 1, 1),
(15, 'https://images.unsplash.com/photo-1600210491892-03d54c0aaf87?w=800', 0, 2);

-- Insert Sample Favorites
INSERT INTO favorites (user_id, listing_id) VALUES 
(3, 1), (3, 2), (3, 5),
(5, 3), (5, 6),
(6, 2), (6, 6), (6, 10);

-- Insert Sample Inquiries
INSERT INTO inquiries (listing_id, user_id, name, email, phone, message, status) VALUES 
(1, 3, 'Jane Tenant', 'tenant@test.com', '9876543212', 'I am interested in this PG. Can I visit tomorrow?', 'pending'),
(2, 5, 'Priya Sharma', 'priya.tenant@gmail.com', '9876543214', 'Is food included in the rent? What are the meal timings?', 'responded'),
(3, 6, 'Amit Patel', 'amit.tenant@gmail.com', '9876543215', 'Looking for a flat for my small family. Is it available from December 1st?', 'pending'),
(5, 3, 'Jane Tenant', 'tenant@test.com', '9876543212', 'What is the maintenance cost? Is parking included?', 'pending'),
(6, 7, 'Sneha Desai', 'sneha.tenant@gmail.com', '9876543216', 'I am a working professional. Can I schedule a visit this weekend?', 'responded'),
(9, 5, 'Priya Sharma', 'priya.tenant@gmail.com', '9876543214', 'Is the PG near any metro station? What time is dinner served?', 'pending'),
(10, 7, 'Sneha Desai', 'sneha.tenant@gmail.com', '9876543216', 'I am a college student. Do you have any discounts for students?', 'pending');

-- Display Summary
SELECT 'Database created successfully!' as Status;
SELECT COUNT(*) as Total_Users FROM users;
SELECT COUNT(*) as Total_Listings FROM listings;
SELECT COUNT(*) as Total_Images FROM listing_images;
SELECT COUNT(*) as Total_Favorites FROM favorites;
SELECT COUNT(*) as Total_Inquiries FROM inquiries;
