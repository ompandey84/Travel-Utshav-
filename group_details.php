<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if group ID is provided
if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$groupId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($groupId === false) {
    redirect('dashboard.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

// Check if user is a member of the group
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'accepted'");
$stmt->execute([$groupId, $userId]);
$membership = $stmt->fetch();

if (!$membership) {
    // Redirect if not a member
    redirect('dashboard.php');
}

// Get group data
$stmt = $pdo->prepare("
    SELECT g.*, d.name as destination_name, d.description as destination_description, 
           d.latitude as destination_lat, d.longitude as destination_long, d.image_url,
           u.username as creator_name
    FROM groups g
    JOIN destinations d ON g.destination_id = d.destination_id
    JOIN users u ON g.created_by = u.user_id
    WHERE g.group_id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    redirect('dashboard.php');
}

// Get group members
$members = getGroupMembers($pdo, $groupId);

// Process meeting point update
$meetingPointUpdated = false;
$meetingPointError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meeting_point'])) {
    $latitude = filter_var($_POST['latitude'] ?? '', FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? '', FILTER_VALIDATE_FLOAT);
    $name = sanitize($_POST['location_name'] ?? '');
    
    if ($latitude === false || $longitude === false) {
        $meetingPointError = "Invalid location coordinates";
    } else {
        try {
            $result = updateMeetingPoint($pdo, $groupId, $latitude, $longitude, $name);
            
            if ($result) {
                $meetingPointUpdated = true;
                // Reload group data
                $stmt = $pdo->prepare("SELECT * FROM groups WHERE group_id = ?");
                $stmt->execute([$groupId]);
                $group = array_merge($group, $stmt->fetch());
            } else {
                $meetingPointError = "Failed to update meeting point";
            }
        } catch (PDOException $e) {
            $meetingPointError = "Error: " . $e->getMessage();
        }
    }
}

// Calculate centroid for all members with locations
$memberLocations = array_filter($members, function($member) {
    return !empty($member['latitude']) && !empty($member['longitude']);
});

$memberCoordinates = array_map(function($member) {
    return [
        'latitude' => $member['latitude'],
        'longitude' => $member['longitude']
    ];
}, $memberLocations);

$centroid = calculateCentroid($memberCoordinates);

// Google Maps API key - in a real application, this should be stored securely
$googleMapsApiKey = "YOUR_GOOGLE_MAPS_API_KEY";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Details - <?php echo htmlspecialchars($group['group_name']); ?> - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .container {
            max-width: 1200px;
            margin: 8rem auto 3rem;
            padding: 0 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            font-size: 2.8rem;
            color: #219150;
        }
        
        .group-info {
            display: flex;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .group-sidebar {
            flex: 1;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 350px;
        }
        
        .group-main {
            flex: 2;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .group-image {
            width: 100%;
            height: 200px;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .group-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .group-meta {
            margin-bottom: 2rem;
        }
        
        .group-meta-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 1.6rem;
            color: #666;
        }
        
        .group-meta-item i {
            width: 20px;
            color: #219150;
        }
        
        .section-title {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .map-container {
            height: 400px;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .meeting-point-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-size: 1.6rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            font-size: 1.6rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
        }
        
        .form-row .form-group {
            flex: 1;
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
            font-size: 1.6rem;
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
        
        .member-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #219150;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .member-status {
            font-size: 1.4rem;
            color: #666;
        }
        
        .creator-badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            background: #219150;
            color: #fff;
            border-radius: 2rem;
            font-size: 1.2rem;
            margin-left: 1rem;
        }
        
        .actions-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .group-info {
                flex-direction: column;
            }
            
            .group-sidebar {
                max-width: none;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
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

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?php echo htmlspecialchars($group['group_name']); ?></h1>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
        
        <div class="group-info">
            <div class="group-sidebar">
                <div class="group-image">
                    <img src="<?php echo htmlspecialchars($group['image_url']); ?>" alt="<?php echo htmlspecialchars($group['destination_name']); ?>">
                </div>
                
                <div class="group-meta">
                    <div class="group-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($group['destination_name']); ?></span>
                    </div>
                    
                    <div class="group-meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Departure: <?php echo date('M d, Y', strtotime($group['departure_date'])); ?></span>
                    </div>
                    
                    <?php if ($group['return_date']): ?>
                        <div class="group-meta-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Return: <?php echo date('M d, Y', strtotime($group['return_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="group-meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo count($members); ?>/<?php echo $group['max_members']; ?> members</span>
                    </div>
                    
                    <div class="group-meta-item">
                        <i class="fas fa-user-shield"></i>
                        <span>Created by: <?php echo htmlspecialchars($group['creator_name']); ?></span>
                    </div>
                </div>
                
                <h3 class="section-title">Group Members</h3>
                <ul class="member-list">
                    <?php foreach ($members as $member): ?>
                        <li class="member-item">
                            <div class="member-avatar">
                                <?php if ($member['profile_image']): ?>
                                    <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="<?php echo htmlspecialchars($member['username']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name">
                                    <?php echo htmlspecialchars($member['username']); ?>
                                    <?php if ($member['user_id'] == $group['created_by']): ?>
                                        <span class="creator-badge">Creator</span>
                                    <?php endif; ?>
                                </div>
                                <div class="member-status">
                                    <?php echo $member['first_name'] ? htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) : ''; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="actions-section">
                    <div class="action-buttons">
                        <a href="group_chat.php?id=<?php echo $groupId; ?>" class="btn">
                            <i class="fas fa-comments"></i> Group Chat
                        </a>
                        <?php if ($userId == $group['created_by']): ?>
                            <a href="edit_group.php?id=<?php echo $groupId; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Edit Group
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="group-main">
                <h2 class="section-title">Meeting Point</h2>
                
                <?php if ($meetingPointUpdated): ?>
                    <div class="alert alert-success">
                        Meeting point updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($meetingPointError): ?>
                    <div class="alert alert-danger">
                        <?php echo $meetingPointError; ?>
                    </div>
                <?php endif; ?>
                
                <div class="map-container" id="meeting-map"></div>
                
                <form class="meeting-point-form" action="group_details.php?id=<?php echo $groupId; ?>" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $group['meeting_point_lat'] ?? ($centroid['latitude'] ?? $group['destination_lat']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $group['meeting_point_long'] ?? ($centroid['longitude'] ?? $group['destination_long']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location_name">Location Name</label>
                        <input type="text" class="form-control" id="location_name" name="location_name" value="<?php echo $group['meeting_point_name'] ?? ''; ?>" placeholder="e.g. Central Park, Main Square, etc.">
                    </div>
                    
                    <div class="form-row">
                        <button type="button" class="btn btn-secondary" id="use-centroid">Use Group Centroid</button>
                        <button type="button" class="btn btn-secondary" id="use-destination">Use Destination</button>
                        <button type="submit" class="btn" name="update_meeting_point">Update Meeting Point</button>
                    </div>
                </form>
                
                <h2 class="section-title">About This Trip</h2>
                <p style="font-size: 1.6rem; line-height: 1.6; color: #666; margin-bottom: 2rem;">
                    <?php echo $group['description'] ? nl2br(htmlspecialchars($group['description'])) : 'No description provided.'; ?>
                </p>
                
                <h3 class="section-title">Destination Information</h3>
                <p style="font-size: 1.6rem; line-height: 1.6; color: #666;">
                    <?php echo nl2br(htmlspecialchars($group['destination_description'])); ?>
                </p>
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
        const meetingMap = L.map('meeting-map').setView([
            <?php echo $group['meeting_point_lat'] ?? ($centroid['latitude'] ?? $group['destination_lat']); ?>,
            <?php echo $group['meeting_point_long'] ?? ($centroid['longitude'] ?? $group['destination_long']); ?>
        ], 13);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(meetingMap);
        
        // Add marker for meeting point
        const meetingPointMarker = L.marker([
            <?php echo $group['meeting_point_lat'] ?? ($centroid['latitude'] ?? $group['destination_lat']); ?>,
            <?php echo $group['meeting_point_long'] ?? ($centroid['longitude'] ?? $group['destination_long']); ?>
        ], {
            draggable: true
        }).addTo(meetingMap);
        
        // Update form when marker is dragged
        meetingPointMarker.on('dragend', function(event) {
            const marker = event.target;
            const position = marker.getLatLng();
            document.getElementById('latitude').value = position.lat;
            document.getElementById('longitude').value = position.lng;
        });
        
        // Add markers for members with locations
        <?php foreach ($memberLocations as $member): ?>
            L.marker([<?php echo $member['latitude']; ?>, <?php echo $member['longitude']; ?>], {
                icon: L.divIcon({
                    className: 'member-marker',
                    html: '<div style="background-color: #219150; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 12px;"><?php echo substr($member['username'], 0, 1); ?></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(meetingMap).bindPopup("<?php echo htmlspecialchars($member['username']); ?>'s location");
        <?php endforeach; ?>
        
        // Add marker for destination
        L.marker([<?php echo $group['destination_lat']; ?>, <?php echo $group['destination_long']; ?>], {
            icon: L.divIcon({
                className: 'destination-marker',
                html: '<div style="background-color: #dc3545; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fas fa-map-marker-alt"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(meetingMap).bindPopup("<?php echo htmlspecialchars($group['destination_name']); ?>");
        
        // Add centroid marker if available
        <?php if ($centroid): ?>
            L.marker([<?php echo $centroid['latitude']; ?>, <?php echo $centroid['longitude']; ?>], {
                icon: L.divIcon({
                    className: 'centroid-marker',
                    html: '<div style="background-color: #ffc107; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fas fa-crosshairs"></i></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(meetingMap).bindPopup("Group centroid (average of all member locations)");
        <?php endif; ?>
        
        // Use centroid button
        document.getElementById('use-centroid').addEventListener('click', function() {
            <?php if ($centroid): ?>
                document.getElementById('latitude').value = <?php echo $centroid['latitude']; ?>;
                document.getElementById('longitude').value = <?php echo $centroid['longitude']; ?>;
                meetingPointMarker.setLatLng([<?php echo $centroid['latitude']; ?>, <?php echo $centroid['longitude']; ?>]);
                meetingMap.setView([<?php echo $centroid['latitude']; ?>, <?php echo $centroid['longitude']; ?>], 13);
            <?php else: ?>
                alert("Cannot calculate centroid. Not enough members have shared their location.");
            <?php endif; ?>
        });
        
        // Use destination button
        document.getElementById('use-destination').addEventListener('click', function() {
            document.getElementById('latitude').value = <?php echo $group['destination_lat']; ?>;
            document.getElementById('longitude').value = <?php echo $group['destination_long']; ?>;
            meetingPointMarker.setLatLng([<?php echo $group['destination_lat']; ?>, <?php echo $group['destination_long']; ?>]);
            meetingMap.setView([<?php echo $group['destination_lat']; ?>, <?php echo $group['destination_long']; ?>], 13);
        });
    </script>
</body>
</html> 