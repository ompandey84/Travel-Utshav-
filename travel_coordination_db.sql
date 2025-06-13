-- Travel Coordination Platform Database Schema

-- Users table to store user information
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_image VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Destinations table to store available travel destinations
CREATE TABLE destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groups table to store travel groups
CREATE TABLE groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    group_name VARCHAR(100) NOT NULL,
    description TEXT,
    departure_date DATETIME,
    return_date DATETIME,
    meeting_point_lat DECIMAL(10, 8),
    meeting_point_long DECIMAL(11, 8),
    meeting_point_name VARCHAR(255),
    max_members INT DEFAULT 10,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Group members table to store the many-to-many relationship between users and groups
CREATE TABLE group_members (
    group_member_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    FOREIGN KEY (group_id) REFERENCES groups(group_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_group_member (group_id, user_id)
);

-- Messages table to store group chat messages
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(group_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Points of Interest table to store nearby meeting points
CREATE TABLE points_of_interest (
    poi_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample destinations
INSERT INTO destinations (name, description, latitude, longitude, image_url) VALUES
('Ujjain', 'Experience the spiritual heritage of Ujjain, one of India\'s seven sacred cities.', 23.1765, 75.7885, '../ImagesFinal/ujjain.jpg'),
('Pachmarhi', 'Explore the only hill station in Madhya Pradesh, known as "Queen of the Satpura Range".', 22.4675, 78.4329, '../ImagesFinal/pachmari.jpg'),
('Khajuraho', 'Discover the architectural marvels of Madhya Pradesh with UNESCO World Heritage site.', 24.8318, 79.9199, '../ImagesFinal/khajuraho.jpg'),
('Mandu', 'Visit the ancient fort city known for its Afghan architecture.', 22.3335, 75.4229, '../ImagesFinal/mandu.jpg'),
('Omkareshwar', 'Sacred island shaped like Om on the Narmada river.', 22.2433, 76.1519, '../ImagesFinal/omkareshwar.jpg'); 