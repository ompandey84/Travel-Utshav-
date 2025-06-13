<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

// Update user location
$locationUpdated = false;
$locationError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_location'])) {
    $latitude = filter_var($_POST['latitude'] ?? '', FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? '', FILTER_VALIDATE_FLOAT);
    
    if ($latitude === false || $longitude === false) {
        $locationError = "Invalid location coordinates";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE user_id = ?");
            $result = $stmt->execute([$latitude, $longitude, $userId]);
            
            if ($result) {
                $locationUpdated = true;
                // Update user data
                $user = getUserById($pdo, $userId);
            } else {
                $locationError = "Failed to update location";
            }
        } catch (PDOException $e) {
            $locationError = "Error: " . $e->getMessage();
        }
    }
}

// Get available destinations
$stmt = $pdo->query("SELECT * FROM destinations ORDER BY name");
$destinations = $stmt->fetchAll();

// Get user's groups
$stmt = $pdo->prepare("
    SELECT g.*, d.name as destination_name, d.image_url,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'accepted') as member_count
    FROM groups g
    JOIN destinations d ON g.destination_id = d.destination_id
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ? AND gm.status = 'accepted'
    ORDER BY g.departure_date
");
$stmt->execute([$userId]);
$userGroups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 8rem auto 3rem;
            padding: 0 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }
        
        .dashboard-title {
            font-size: 2.8rem;
            color: #219150;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #219150;
        }
        
        .user-details h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .user-details p {
            font-size: 1.4rem;
            color: #666;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .dashboard-sidebar {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .dashboard-main {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .section-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .location-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            outline: none;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: #219150;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #1a7b42;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .map-container {
            height: 300px;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .destination-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .destination-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .destination-item:last-child {
            border-bottom: none;
        }
        
        .destination-name {
            font-size: 1.6rem;
            color: #333;
        }
        
        .group-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .group-card {
            background: #f9f9f9;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.3rem 0.5rem rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .group-card:hover {
            transform: translateY(-5px);
        }
        
        .group-image {
            height: 180px;
            overflow: hidden;
        }
        
        .group-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .group-details {
            padding: 1.5rem;
        }
        
        .group-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .group-destination {
            font-size: 1.4rem;
            color: #219150;
            margin-bottom: 1rem;
        }
        
        .group-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            color: #666;
        }
        
        .group-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 1.4rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .group-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="Images/home.html" class="logo"><i class="fas fa-hiking"></i>travel Utsav</a>
        
        <nav class="navbar">
            <div id="nav-close" class="fas fa-times"></div>
            <a href="Images/home.html">home</a>
            <a href="Images/home.html#about">about</a>
            <a href="Images/shop.html">shop</a>
            <a href="Images/packages.html">packages</a>
            <a href="Images/home.html#reviews">reviews</a>
            <a href="Images/home.html#blogs">blogs</a>
            <a href="Images/chat.html">AI Assistant</a>
        </nav>
        <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <a href="Images/shop.html" class="fas fa-shopping-cart"></a>
            <div id="search-btn" class="fas fa-search"></div>
            <a href="Images/travelPage.html" class="fa-solid fa-motorcycle"></a>
            <a href="logout.php" class="fa-solid fa-sign-out-alt"></a>
        </div>
    </header>

    <div class="search-form">
        <div id="close-search" class="fas fa-times"></div>
        <form action="">
            <input type="search" name="" placeholder="search here..." id="search-box">
            <label for="search-box" class="fas fa-search"></label>
        </form>
    </div>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Travel Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="dashboard-sidebar">
                <h2 class="section-title">Your Location</h2>
                
                <?php if ($locationUpdated): ?>
                    <div class="alert alert-success">
                        Location updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($locationError): ?>
                    <div class="alert alert-danger">
                        <?php echo $locationError; ?>
                    </div>
                <?php endif; ?>
                
                <div class="map-container" id="user-map"></div>
                
                <form class="location-form" action="dashboard.php" method="POST">
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $user['latitude'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $user['longitude'] ?? ''; ?>" required>
                    </div>
                    
                    <button type="button" class="btn btn-secondary btn-block" id="get-location">Get Current Location</button>
                    <button type="submit" class="btn btn-block" name="update_location" style="margin-top: 1rem;">Update Location</button>
                </form>
                
                <h2 class="section-title">Available Destinations</h2>
                <ul class="destination-list">
                    <?php foreach ($destinations as $destination): ?>
                        <li class="destination-item">
                            <span class="destination-name"><?php echo htmlspecialchars($destination['name']); ?></span>
                            <a href="join_group.php?destination=<?php echo $destination['destination_id']; ?>" class="btn btn-sm">Join/Create Group</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="dashboard-main">
                <h2 class="section-title">Your Travel Groups</h2>
                
                <?php if (empty($userGroups)): ?>
                    <p style="font-size: 1.6rem; color: #666; text-align: center; padding: 2rem;">
                        You haven't joined any travel groups yet. Select a destination to join or create a group!
                    </p>
                <?php else: ?>
                    <div class="group-cards">
                        <?php foreach ($userGroups as $group): ?>
                            <div class="group-card">
                                <div class="group-image">
                                    <img src="<?php echo htmlspecialchars($group['image_url']); ?>" alt="<?php echo htmlspecialchars($group['destination_name']); ?>">
                                </div>
                                <div class="group-details">
                                    <h3 class="group-title"><?php echo htmlspecialchars($group['group_name']); ?></h3>
                                    <p class="group-destination">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($group['destination_name']); ?>
                                    </p>
                                    
                                    <div class="group-info">
                                        <span>
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('M d, Y', strtotime($group['departure_date'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-users"></i> 
                                            <?php echo $group['member_count']; ?> members
                                        </span>
                                    </div>
                                    
                                    <?php if ($group['meeting_point_lat'] && $group['meeting_point_long']): ?>
                                        <p style="font-size: 1.4rem; color: #219150; margin-bottom: 1rem;">
                                            <i class="fas fa-map-pin"></i> Meeting point set
                                        </p>
                                    <?php else: ?>
                                        <p style="font-size: 1.4rem; color: #dc3545; margin-bottom: 1rem;">
                                            <i class="fas fa-exclamation-circle"></i> No meeting point yet
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="group-actions">
                                        <a href="group_details.php?id=<?php echo $group['group_id']; ?>" class="btn btn-sm">View Details</a>
                                        <a href="group_chat.php?id=<?php echo $group['group_id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="footer">
        <div class="box-container">
            <div class="box">
                <h3>Quick Links</h3>
                <a href="Images/home.html">home</a>
                <a href="Images/home.html#about">about</a>
                <a href="Images/shop.html">shop</a>
                <a href="Images/packages.html">packages</a>
                <a href="Images/home.html#reviews">reviews</a>
                <a href="Images/home.html#blogs">blogs</a>
                <a href="Images/chat.html">AI Assistant</a>
            </div>
            <div class="box">
                <h3>Extra Links</h3>
                <a href="#">My Account</a>
                <a href="#">My Order</a>
                <a href="#">Wishlist</a>
                <a href="#">Any Question </a>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="box">
                <h3>Contact Information</h3>
                <a href="#"><i class="fas fa-phone"></i>+91-7241142006</a>
                <a href="#"><i class="fas fa-phone"></i>+91-9705745856</a>
                <a href="#"><i class="fas fa-envelope"></i>travel_utsav@gmail.com</a>
                <a href="#"><i class="fas fa-map"></i>indore, Madhya Pradesh - 451010</a>
            </div>
            <div class="box">
                <a href="#"><i class="fab fa-facebook-f"></i>facebook</a>
                <a href="#"><i class="fab fa-twitter"></i>Twitter</a>
                <a href="#"><i class="fab fa-instagram"></i>Instagram</a>
                <a href="#"><i class="fab fa-github"></i>Github</a>
            </div>
        </div>
        <div class="credit">Created By <span>Travel Utsav Team</span> | All Rights Reserved</div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="Images/home.js"></script>
    <script>
        // Initialize map
        const userMap = L.map('user-map').setView([22.7196, 75.8577], 13); // Default to Indore
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(userMap);
        
        // Add marker for user's location if available
        <?php if ($user['latitude'] && $user['longitude']): ?>
            const userMarker = L.marker([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>]).addTo(userMap);
            userMarker.bindPopup("Your current location").openPopup();
            userMap.setView([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>], 13);
        <?php endif; ?>
        
        // Get current location button
        document.getElementById('get-location').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    document.getElementById('latitude').value = latitude;
                    document.getElementById('longitude').value = longitude;
                    
                    // Update map
                    userMap.setView([latitude, longitude], 13);
                    
                    // Clear existing markers
                    userMap.eachLayer(function(layer) {
                        if (layer instanceof L.Marker) {
                            userMap.removeLayer(layer);
                        }
                    });
                    
                    // Add new marker
                    const newMarker = L.marker([latitude, longitude]).addTo(userMap);
                    newMarker.bindPopup("Your current location").openPopup();
                    
                }, function(error) {
                    alert("Error getting location: " + error.message);
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });
    </script>
</body>
</html> 