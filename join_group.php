<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if destination ID is provided
if (!isset($_GET['destination'])) {
    redirect('dashboard.php');
}

$destinationId = filter_var($_GET['destination'], FILTER_VALIDATE_INT);
if ($destinationId === false) {
    redirect('dashboard.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

// Get destination data
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE destination_id = ?");
$stmt->execute([$destinationId]);
$destination = $stmt->fetch();

if (!$destination) {
    redirect('dashboard.php');
}

// Get existing groups for this destination
$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'accepted') as member_count,
           (SELECT status FROM group_members WHERE group_id = g.group_id AND user_id = ?) as user_status
    FROM groups g
    WHERE g.destination_id = ? AND g.departure_date >= CURDATE()
    ORDER BY g.departure_date
");
$stmt->execute([$userId, $destinationId]);
$existingGroups = $stmt->fetchAll();

// Process group creation
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $groupName = sanitize($_POST['group_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $departureDate = $_POST['departure_date'] ?? '';
    $returnDate = $_POST['return_date'] ?? '';
    $maxMembers = filter_var($_POST['max_members'] ?? 10, FILTER_VALIDATE_INT);
    
    // Validate input
    if (empty($groupName)) {
        $errors[] = "Group name is required";
    }
    
    if (empty($departureDate)) {
        $errors[] = "Departure date is required";
    }
    
    if ($maxMembers === false || $maxMembers < 2 || $maxMembers > 50) {
        $errors[] = "Max members must be between 2 and 50";
    }
    
    // Create group if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert group
            $stmt = $pdo->prepare("
                INSERT INTO groups (destination_id, group_name, description, departure_date, return_date, max_members, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$destinationId, $groupName, $description, $departureDate, $returnDate, $maxMembers, $userId]);
            
            $groupId = $pdo->lastInsertId();
            
            // Add creator as a member
            $stmt = $pdo->prepare("
                INSERT INTO group_members (group_id, user_id, status)
                VALUES (?, ?, 'accepted')
            ");
            $stmt->execute([$groupId, $userId]);
            
            // Calculate meeting point if user has location
            if ($user['latitude'] && $user['longitude']) {
                updateMeetingPoint($pdo, $groupId, $user['latitude'], $user['longitude']);
            }
            
            $pdo->commit();
            $success = true;
            
            // Redirect to group details
            header("refresh:2;url=group_details.php?id=$groupId");
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to create group: " . $e->getMessage();
        }
    }
}

// Process join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group'])) {
    $groupId = filter_var($_POST['group_id'] ?? '', FILTER_VALIDATE_INT);
    
    if ($groupId === false) {
        $errors[] = "Invalid group ID";
    } else {
        try {
            // Check if user is already a member
            $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $membership = $stmt->fetch();
            
            if ($membership) {
                if ($membership['status'] === 'pending') {
                    $errors[] = "You already have a pending request for this group";
                } else if ($membership['status'] === 'accepted') {
                    $errors[] = "You are already a member of this group";
                } else {
                    // Update status to pending
                    $stmt = $pdo->prepare("UPDATE group_members SET status = 'pending' WHERE group_id = ? AND user_id = ?");
                    $stmt->execute([$groupId, $userId]);
                    $success = true;
                    $successMessage = "Join request sent successfully!";
                }
            } else {
                // Check if group is full
                $stmt = $pdo->prepare("
                    SELECT g.max_members, 
                           (SELECT COUNT(*) FROM group_members WHERE group_id = ? AND status = 'accepted') as member_count
                    FROM groups g
                    WHERE g.group_id = ?
                ");
                $stmt->execute([$groupId, $groupId]);
                $groupInfo = $stmt->fetch();
                
                if ($groupInfo['member_count'] >= $groupInfo['max_members']) {
                    $errors[] = "This group is already full";
                } else {
                    // Add join request
                    $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'pending')");
                    $stmt->execute([$groupId, $userId]);
                    $success = true;
                    $successMessage = "Join request sent successfully!";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to join group: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Group - <?php echo htmlspecialchars($destination['name']); ?> - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
    <style>
        .container {
            max-width: 1000px;
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
        
        .destination-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }
        
        .destination-image {
            width: 150px;
            height: 150px;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .destination-details {
            flex: 1;
        }
        
        .destination-name {
            font-size: 2.4rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .destination-description {
            font-size: 1.6rem;
            color: #666;
            line-height: 1.6;
        }
        
        .section-title {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
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
        
        .error-list {
            margin: 0;
            padding-left: 2rem;
        }
        
        .group-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .group-item:last-child {
            border-bottom: none;
        }
        
        .group-info {
            flex: 1;
        }
        
        .group-name {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .group-meta {
            display: flex;
            gap: 2rem;
            font-size: 1.4rem;
            color: #666;
        }
        
        .group-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .group-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 1.4rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        
        .status-declined {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-message {
            text-align: center;
            padding: 3rem;
            font-size: 1.6rem;
            color: #666;
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
            <h1 class="page-title">Join Travel Group</h1>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
        
        <div class="destination-info">
            <div class="destination-image">
                <img src="<?php echo htmlspecialchars($destination['image_url']); ?>" alt="<?php echo htmlspecialchars($destination['name']); ?>">
            </div>
            <div class="destination-details">
                <h2 class="destination-name"><?php echo htmlspecialchars($destination['name']); ?></h2>
                <p class="destination-description"><?php echo htmlspecialchars($destination['description']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success && isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="section-title">Available Groups</h2>
            
            <?php if (empty($existingGroups)): ?>
                <div class="empty-message">
                    <p>No groups available for this destination. Create a new group below!</p>
                </div>
            <?php else: ?>
                <ul class="group-list">
                    <?php foreach ($existingGroups as $group): ?>
                        <li class="group-item">
                            <div class="group-info">
                                <h3 class="group-name"><?php echo htmlspecialchars($group['group_name']); ?></h3>
                                <div class="group-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($group['departure_date'])); ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo $group['member_count']; ?>/<?php echo $group['max_members']; ?> members</span>
                                </div>
                            </div>
                            <div class="group-actions">
                                <?php if ($group['user_status'] === 'accepted'): ?>
                                    <span class="status-badge status-accepted">Member</span>
                                    <a href="group_details.php?id=<?php echo $group['group_id']; ?>" class="btn btn-sm">View</a>
                                <?php elseif ($group['user_status'] === 'pending'): ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php else: ?>
                                    <form action="join_group.php?destination=<?php echo $destinationId; ?>" method="POST">
                                        <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                        <button type="submit" name="join_group" class="btn btn-sm">Join Group</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 class="section-title">Create New Group</h2>
            
            <?php if ($success && isset($_POST['create_group'])): ?>
                <div class="alert alert-success">
                    Group created successfully! You will be redirected to the group details page.
                </div>
            <?php else: ?>
                <form action="join_group.php?destination=<?php echo $destinationId; ?>" method="POST">
                    <div class="form-group">
                        <label for="group_name">Group Name</label>
                        <input type="text" class="form-control" id="group_name" name="group_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="departure_date">Departure Date</label>
                        <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="return_date">Return Date</label>
                        <input type="date" class="form-control" id="return_date" name="return_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_members">Maximum Members</label>
                        <input type="number" class="form-control" id="max_members" name="max_members" min="2" max="50" value="10" required>
                    </div>
                    
                    <button type="submit" class="btn btn-block" name="create_group">Create Group</button>
                </form>
            <?php endif; ?>
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
    <script src="Images/home.js"></script>
    <script>
        // Set minimum date for departure and return date inputs
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('departure_date').setAttribute('min', today);
        document.getElementById('return_date').setAttribute('min', today);
        
        // Update return date min value when departure date changes
        document.getElementById('departure_date').addEventListener('change', function() {
            document.getElementById('return_date').setAttribute('min', this.value);
        });
    </script>
</body>
</html> 