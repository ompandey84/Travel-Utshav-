# Travel-Utshav-
A personal repository for Travel Uthsav, capturing ideas, explorations, and journeys
# Travel Utsav - Location-Based Travel Coordination Platform

Travel Utsav is a comprehensive travel planning website with a location-based travel coordination platform that helps users find travel companions for destinations and automatically calculates optimal meeting points.

## Features

- **User Authentication**: Register and login system with secure password storage
- **Location Tracking**: Store and update user geolocation coordinates
- **Group Formation**: Create or join travel groups for specific destinations
- **Meeting Point Calculation**: Automatically calculate optimal meeting points using:
  - Centroid formula (average of all member locations)
  - Integration with map services to find nearby points of interest
- **Real-time Group Chat**: Communicate with group members through an AJAX-powered chat interface
- **Interactive Maps**: Visualize member locations and meeting points
- **Travel Shop**: Browse and purchase travel gear and equipment
- **Travel Packages**: Explore curated travel packages from Indore, Madhya Pradesh
- **AI Assistant**: Get travel advice using the integrated Google Gemini AI chatbot

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or similar PHP development environment)
- Web browser with JavaScript enabled

### Installation Steps

1. **Clone or download the repository**
   - Place the files in your XAMPP htdocs directory (e.g., `C:\xampp\htdocs\Travel-Utsav`)

2. **Set up the database**
   - Start XAMPP and ensure MySQL service is running
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `travel_coordination`
   - Import the `travel_coordination_db.sql` file to create the required tables and sample data

3. **Configure database connection**
   - Open `includes/db_connect.php`
   - Update the database connection parameters if needed (username, password, etc.)

4. **Set up Google Maps API (optional)**
   - If you want to use the Google Maps integration for finding nearby points of interest:
     - Get a Google Maps API key from the Google Cloud Platform Console
     - Replace `YOUR_GOOGLE_MAPS_API_KEY` in the `group_details.php` file with your actual API key

5. **Start the application**
   - Open your web browser and navigate to `http://localhost/Travel-Utsav/Images/home.html`
   - For the coordination platform features, register an account and log in

### File Structure

- `Images/` - Contains HTML pages for the main website
  - `home.html` - Homepage
  - `shop.html` - Travel gear shop
  - `packages.html` - Travel packages
  - `chat.html` - AI Assistant powered by Google Gemini
- `includes/` - Contains PHP helper files
  - `db_connect.php` - Database connection
  - `functions.php` - Utility functions
- `*.php` - PHP files for the travel coordination platform
  - `register.php` - User registration
  - `login.php` - User login
  - `dashboard.php` - User dashboard
  - `join_group.php` - Join or create travel groups
  - `group_details.php` - View group details and manage meeting points
  - `group_chat.php` - Group chat interface

## Usage

1. **Register and log in**
   - Create an account using the registration page
   - Log in with your credentials

2. **Update your location**
   - On the dashboard, use the "Get Current Location" button or manually enter coordinates
   - Click "Update Location" to save your position

3. **Join or create a travel group**
   - Select a destination from the available options
   - Join an existing group or create a new one

4. **View group details**
   - See group information, members, and the meeting point
   - Use the map to visualize member locations and the calculated meeting point

5. **Chat with group members**
   - Use the group chat to communicate with other members
   - Plan your trip and coordinate details

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Maps**: Leaflet.js (with optional Google Maps API integration)
- **AJAX**: For real-time chat and dynamic content
- **AI Integration**: Google Gemini API

## Credits

- Created by Travel Utsav Team
- Based in Indore, Madhya Pradesh, India 
