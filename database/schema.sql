CREATE DATABASE IF NOT EXISTS event_booking_system;
USE event_booking_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    venue VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    max_capacity INT NOT NULL,
    current_bookings INT DEFAULT 0,
    image VARCHAR(500),
    organizer_name VARCHAR(100),
    organizer_contact VARCHAR(100),
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'cancelled', 'full') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO events (title, description, venue, event_date, event_time, price, max_capacity, organizer_name, organizer_contact, is_featured, image) VALUES
('Tech Conference 2025', 'A comprehensive technology conference featuring the latest innovations and trends in the tech industry.', 'Convention Center Downtown', '2025-06-15', '09:00:00', 99.99, 500, 'Tech Events Inc.', 'info@techevents.com', TRUE, 'assets/images/tech-conference.jpg'),
('Summer Music Festival', 'Three days of amazing music performances from local and international artists.', 'Central Park Amphitheater', '2025-07-20', '18:00:00', 75.00, 2000, 'Music Productions', 'contact@musicfest.com', TRUE, 'assets/images/music-festival.jpg'),
('Business Networking Event', 'Connect with professionals from various industries and expand your business network.', 'Hilton Hotel Conference Room', '2025-06-01', '19:00:00', 25.00, 100, 'Business Network Group', 'hello@businessnet.com', FALSE, 'assets/images/networking.jpg'),
('Art Exhibition Opening', 'Grand opening of contemporary art exhibition featuring works from emerging artists.', 'Modern Art Gallery', '2025-05-30', '17:00:00', 15.00, 200, 'Art Gallery', 'info@artgallery.com', TRUE, 'assets/images/art-exhibition.jpg'),
('Food & Wine Festival', 'Taste exquisite cuisine and wines from renowned chefs and wineries.', 'Riverside Park', '2025-08-10', '12:00:00', 45.00, 800, 'Culinary Events', 'events@foodwine.com', FALSE, 'assets/images/food-festival.jpg');