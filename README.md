# Travel Utsav – Location-Based Travel Coordination Platform

**Travel Utsav** is a comprehensive travel planning platform designed to help users find travel companions, form groups based on destinations, and automatically calculate optimal meeting points using real-time geolocation data.

---

## Features

* **User Authentication**
  Secure user registration and login with encrypted password storage.

* **Location Tracking**
  Store and update user geolocation coordinates for smart recommendations and grouping.

* **Group Formation**
  Create or join travel groups based on selected destinations.

* **Meeting Point Calculation**

  * Calculate group meeting points using the centroid formula (average of all member locations).
  * Integrate with Google Maps API to find nearby points of interest.

* **Real-Time Group Chat**
  AJAX-powered chat interface for live communication among group members.

* **Interactive Maps**
  Visualize user locations and meeting points using Leaflet.js or Google Maps.

* **Travel Shop**
  Browse and buy essential travel gear and equipment.

* **Travel Packages**
  Explore curated packages, especially from Indore, Madhya Pradesh.

* **AI Assistant**
  Get travel recommendations through an integrated AI chatbot powered by Google Gemini.

---

## Setup Instructions

### Prerequisites

* PHP 7.4 or higher
* MySQL 5.7 or higher
* XAMPP / WAMP / MAMP / LAMP (for local development)
* Modern web browser (JavaScript enabled)

### Installation Steps

1. **Clone or Download the Repository**
   Place the project folder in your XAMPP `htdocs` directory.

   **Examples:**

   * Windows: `C:\xampp\htdocs\Travel-Utsav`
   * macOS: `/Applications/XAMPP/htdocs/Travel-Utsav`
   * Linux: `/opt/lampp/htdocs/Travel-Utsav`

2. **Set Up the Database**

   * Start Apache and MySQL using XAMPP Control Panel.
   * Open [phpMyAdmin](http://localhost/phpmyadmin).
   * Create a database named `travel_coordination`.
   * Import the `travel_coordination_db.sql` file.

3. **Configure Database Connection**

   * Open `includes/db_connect.php`.
   * Update database credentials as needed (e.g., username: `root`, password: `''`).

4. **(Optional) Configure Google Maps API**

   * Generate a Maps API key from [Google Cloud Console](https://console.cloud.google.com/).
   * Replace `YOUR_GOOGLE_MAPS_API_KEY` in `group_details.php` with your API key.

5. **Start the Application**

   * Open your browser and visit:
     `http://localhost/Travel-Utsav/Images/home.html`
   * Use `login.php` to access the platform's group and chat features.

---

## File Structure

```
Travel-Utsav/
├── Images/
│   ├── home.html           # Homepage layout
│   ├── shop.html           # Travel gear shop interface
│   ├── packages.html       # Curated travel packages display
│   └── chat.html           # AI chatbot interface
│
├── includes/
│   ├── db_connect.php      # Database connection configuration
│   └── functions.php       # Utility and helper functions
│
├── register.php            # User registration
├── login.php               # User login
├── dashboard.php           # User dashboard
├── join_group.php          # Group creation and joining
├── group_details.php       # View group members and meeting point
├── group_chat.php          # AJAX-based real-time group chat
└── travel_coordination_db.sql   # Database structure and sample data
```

---

## Usage

1. **Register and Log In**

   * Visit `register.php` to create an account.
   * Log in using `login.php`.

2. **Set Your Location**

   * On the dashboard, use “Get Current Location” or manually enter coordinates.
   * Click "Update Location" to save.

3. **Join or Create a Travel Group**

   * Choose a destination and either join an existing group or create a new one.

4. **View Group Details**

   * Check member locations and meeting point via an interactive map.

5. **Group Chat**

   * Use the chat interface to discuss and coordinate travel plans.

---

## Technologies Used

* **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
* **Backend:** PHP (Core PHP)
* **Database:** MySQL
* **Mapping Tools:** Leaflet.js, Google Maps API (optional)
* **AJAX:** For real-time chat and dynamic content
* **AI Integration:** Google Gemini API for chatbot assistant

---

## Credits

* **Project Name:** Travel Utsav
* **Location:** Indore, Madhya Pradesh, India
* **AI Chatbot:** Google Gemini
* **Mapping Tools:** Leaflet.js, Google Maps API
* **Development Tools:** XAMPP, Visual Studio Code, GitHub

---

Let me know if you'd like this version exported as a `.md`, `.pdf`, or `.docx` file.
