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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Events table with all current columns
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    venue VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    max_capacity INT NOT NULL,
    available_tickets INT,
    total_tickets INT,
    current_bookings INT DEFAULT 0,
    image VARCHAR(500),
    organizer_name VARCHAR(100),
    organizer VARCHAR(100),
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
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    service_fee DECIMAL(10,2) DEFAULT 0,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip VARCHAR(20),
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'confirmed',
    payment_status ENUM('pending', 'completed', 'refunded') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Booking Items table
CREATE TABLE booking_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id)
);

-- Create admin user (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, is_admin) 
VALUES ('admin', 'admin@eventbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', TRUE);

-- Insert Cameroon-focused sample data
INSERT INTO events (title, description, venue, location, event_date, event_time, price, max_capacity, available_tickets, total_tickets, current_bookings, organizer_name, organizer, organizer_contact, is_featured, status, image) VALUES
('Cameroon Tech Summit 2025', 'Annual technology summit showcasing digital innovation and entrepreneurship across Central Africa, featuring local startups and international tech leaders.', 'Hilton Yaoundé', 'Hilton Yaoundé', '2025-07-15', '08:30:00', 45000.00, 800, 800, 800, 0, 'Cameroon Tech Hub', 'Cameroon Tech Hub', 'info@camtechsummit.cm', TRUE, 'active', 'assets/images/tech-summit.jpg'),
('Ngondo Cultural Festival', 'Traditional Sawa cultural celebration featuring authentic Cameroonian music, dance, and the famous Ngondo water ceremonies along the Wouri River.', 'Bonanjo Waterfront, Douala', 'Bonanjo Waterfront, Douala', '2025-12-05', '16:00:00', 8000.00, 5000, 5000, 5000, 0, 'Sawa Cultural Association', 'Sawa Cultural Association', 'contact@ngondo.cm', TRUE, 'active', 'assets/images/ngondo-festival.jpg'),
('African Business Leaders Conference', 'Premier networking event bringing together business leaders, investors, and entrepreneurs from across Africa to discuss economic growth and partnerships.', 'Djeuga Palace Hotel, Yaoundé', 'Djeuga Palace Hotel, Yaoundé', '2025-09-22', '09:00:00', 75000.00, 300, 300, 300, 0, 'African Business Network', 'African Business Network', 'info@africanbusiness.org', TRUE, 'active', 'assets/images/business-conference.jpg'),
('Mount Cameroon Marathon', 'International marathon event taking runners through the scenic routes around Mount Cameroon, Africa\'s second highest peak.', 'Buea Stadium', 'Buea Stadium', '2025-11-08', '06:00:00', 15000.00, 2000, 2000, 2000, 0, 'Cameroon Athletics Federation', 'Cameroon Athletics Federation', 'marathon@athletics-cm.org', FALSE, 'active', 'assets/images/mount-cameroon-marathon.jpg'),
('Yaoundé International Film Festival', 'Celebrating African cinema with screenings of films from across the continent, workshops with renowned filmmakers, and awards ceremony.', 'Palais des Congrès, Yaoundé', 'Palais des Congrès, Yaoundé', '2025-10-12', '18:30:00', 12000.00, 1200, 1200, 1200, 0, 'Cameroon Film Society', 'Cameroon Film Society', 'festival@yaounde-film.cm', TRUE, 'active', 'assets/images/film-festival.jpg'),
('Central African Food & Craft Fair', 'Exhibition showcasing traditional Cameroonian cuisine, local crafts, and agricultural products from across Central Africa.', 'Parc des Expositions, Douala', 'Parc des Expositions, Douala', '2025-08-18', '10:00:00', 5000.00, 3000, 3000, 3000, 0, 'Chamber of Commerce Douala', 'Chamber of Commerce Douala', 'events@ccdd.cm', FALSE, 'active', 'assets/images/food-craft-fair.jpg'),
('Cameroon Music Awards Night', 'Annual celebration of Cameroonian music featuring performances by top artists including Makossa, Bikutsi, and contemporary African music.', 'Palais Polyvalent des Sports, Yaoundé', 'Palais Polyvalent des Sports, Yaoundé', '2025-06-28', '20:00:00', 25000.00, 8000, 8000, 8000, 0, 'Cameroon Music Industry', 'Cameroon Music Industry', 'awards@camindustry.cm', TRUE, 'active', 'assets/images/music-awards.jpg'),
('Bamenda Highlands Agriculture Expo', 'Agricultural exhibition featuring innovations in farming, sustainable agriculture practices, and showcase of crops from the Northwest Region.', 'Commercial Avenue Grounds, Bamenda', 'Commercial Avenue Grounds, Bamenda', '2025-05-14', '08:00:00', 3000.00, 1500, 1500, 1500, 0, 'Northwest Farmers Association', 'Northwest Farmers Association', 'expo@nwfarmers.cm', FALSE, 'active', 'assets/images/agriculture-expo.jpg'),
('Limbe Beach Festival', 'Coastal celebration featuring water sports, beach volleyball tournaments, seafood festival, and live entertainment by the Atlantic Ocean.', 'Limbe Beach Resort', 'Limbe Beach Resort', '2025-08-02', '14:00:00', 10000.00, 4000, 4000, 4000, 0, 'Southwest Tourism Board', 'Southwest Tourism Board', 'info@limbebeach.cm', TRUE, 'active', 'assets/images/beach-festival.jpg'),
('Cameroon Unity Day Celebration', 'National celebration commemorating Cameroon\'s reunification with cultural performances, parades, and traditional ceremonies from all 10 regions.', 'Boulevard du 20 Mai, Yaoundé', 'Boulevard du 20 Mai, Yaoundé', '2025-05-20', '09:00:00', 0.00, 10000, 10000, 10000, 0, 'Ministry of Culture', 'Ministry of Culture', 'unity@culture.gov.cm', TRUE, 'active', 'assets/images/unity-day.jpg'),
('Garoua International Trade Fair', 'Northern Cameroon\'s largest commercial exhibition featuring trade opportunities, livestock showcase, and cultural displays from the Sahel region.', 'Garoua Exhibition Center', 'Garoua Exhibition Center', '2025-03-15', '08:00:00', 7500.00, 2500, 2500, 2500, 0, 'Garoua Chamber of Commerce', 'Garoua Chamber of Commerce', 'fair@garouacc.cm', FALSE, 'active', 'assets/images/trade-fair.jpg'),
('Kribi Seafood & Tourism Festival', 'Coastal festival celebrating Cameroon\'s maritime heritage with fresh seafood, fishing competitions, and traditional Bulu cultural performances.', 'Kribi Beach Resort', 'Kribi Beach Resort', '2025-09-07', '11:00:00', 12500.00, 1800, 1800, 1800, 0, 'South Region Tourism', 'South Region Tourism', 'festival@kribitourism.cm', FALSE, 'active', 'assets/images/seafood-festival.jpg');