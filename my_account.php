<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']) ? true : false;
$userId = $loggedIn ? $_SESSION['user_id'] : null;
$userEmail = $loggedIn ? $_SESSION['email'] : '';
$userName = $loggedIn ? $_SESSION['name'] : '';

// Fetch user's payment history
$payments = [];
if ($loggedIn) {
    try {
        $sql = "SELECT * FROM payments WHERE user_email = :email ORDER BY payment_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
        $payments = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Handle error
    }
}

// Fetch user's orders
$orders = [];
if ($loggedIn) {
    try {
        $sql = "SELECT * FROM orders WHERE user_email = :email ORDER BY order_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
        $orders = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Handle error
    }
}

// Fetch user's wishlist
$wishlist = [];
if ($loggedIn) {
    try {
        $sql = "SELECT * FROM wishlist WHERE user_email = :email ORDER BY added_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
        $wishlist = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Handle error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .account-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .greeting {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .greeting h1 {
            font-size: 3rem;
            color: #219150;
            margin-bottom: 10px;
        }
        
        .greeting p {
            font-size: 1.6rem;
            color: #666;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: none;
            border: none;
            font-size: 1.6rem;
            font-weight: bold;
            color: #555;
            cursor: pointer;
            position: relative;
        }
        
        .tab-btn:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #219150;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .tab-btn.active {
            color: #219150;
        }
        
        .tab-btn.active:after {
            transform: scaleX(1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .profile-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .profile-pic {
            text-align: center;
        }
        
        .profile-pic img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #219150;
        }
        
        .profile-info {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 15px;
        }
        
        .info-label {
            flex: 1;
            font-weight: bold;
            color: #555;
            font-size: 1.5rem;
        }
        
        .info-value {
            flex: 2;
            color: #333;
            font-size: 1.5rem;
        }
        
        .table-container {
            overflow-x: auto;
            margin-bottom: 40px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 1.5rem;
        }
        
        .data-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 1.2rem;
            color: white;
            display: inline-block;
        }
        
        .status-completed {
            background-color: #28a745;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #333;
        }
        
        .status-cancelled {
            background-color: #dc3545;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1.3rem;
            cursor: pointer;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: #007bff;
        }
        
        .btn-delete {
            background-color: #dc3545;
        }
        
        .login-box {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 10px;
            text-align: center;
        }
        
        .login-box p {
            font-size: 1.6rem;
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn-login {
            display: inline-block;
            padding: 12px 25px;
            background-color: #219150;
            color: white;
            border-radius: 5px;
            font-size: 1.6rem;
            text-decoration: none;
            margin-top: 15px;
        }
        
        .emergency-box {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            border-left: 5px solid #dc3545;
            margin-top: 40px;
        }
        
        .emergency-title {
            font-size: 2.2rem;
            color: #dc3545;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .emergency-title i {
            margin-right: 10px;
        }
        
        .emergency-desc {
            font-size: 1.6rem;
            color: #555;
            margin-bottom: 20px;
        }
        
        .emergency-contact {
            padding: 15px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .emergency-contact p {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .emergency-contact strong {
            color: #dc3545;
        }
        
        .emergency-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #dc3545;
            color: white;
            border-radius: 5px;
            font-size: 1.6rem;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .profile-section {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- header section -->
    <header class="header">
        <a href="home.html" class="logo"><i class="fas fa-paper-plane"></i> Travel Utsav</a>
        <nav class="navbar">
            <a href="home.html">Home</a>
            <a href="about.html">About</a>
            <a href="packages.html">Packages</a>
            <a href="adventure-ideas.html">Adventures</a>
            <a href="shop.html">Shop</a>
            <a href="blogs.html">Blogs</a>
            <a href="reviews.html">Reviews</a>
            <a href="chat.html">AI Assistant</a>
        </nav>
        <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <a href="shop.html" class="fas fa-shopping-cart"></a>
            <div id="search-btn" class="fas fa-search"></div>
        </div>
        <form action="" class="search-form">
            <input type="search" name="" placeholder="search here..." id="search-box">
            <label for="search-box" class="fas fa-search"></label>
        </form>
    </header>

    <!-- My Account Section -->
    <div class="account-container">
        <?php if ($loggedIn): ?>
            <div class="greeting">
                <h1>Welcome, <?php echo $userName; ?>!</h1>
                <p>Manage your account, view your bookings, and track your orders all in one place.</p>
            </div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('profile')">My Profile</button>
                <button class="tab-btn" onclick="showTab('payments')">Payment History</button>
                <button class="tab-btn" onclick="showTab('orders')">My Orders</button>
                <button class="tab-btn" onclick="showTab('wishlist')">My Wishlist</button>
                <button class="tab-btn" onclick="showTab('emergency')">Emergency Support</button>
            </div>
            
            <!-- Profile Tab -->
            <div class="tab-content active" id="profile">
                <div class="profile-section">
                    <div class="profile-pic">
                        <img src="../ImagesFinal/user-profile.jpg" alt="Profile Picture">
                        <p style="margin-top: 15px; font-size: 1.5rem;">Member since: January 2024</p>
                    </div>
                    
                    <div class="profile-info">
                        <div class="info-row">
                            <div class="info-label">Name:</div>
                            <div class="info-value"><?php echo $userName; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo $userEmail; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">+91-9XXXXXXXXX</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Address:</div>
                            <div class="info-value">123 Main Street, Indore, Madhya Pradesh - 452001</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Membership:</div>
                            <div class="info-value">Silver Traveler</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Points Earned:</div>
                            <div class="info-value">1,250 points</div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="#" class="action-btn btn-view">Edit Profile</a>
                    <a href="../logout.php" class="action-btn btn-delete">Logout</a>
                </div>
            </div>
            
            <!-- Payments Tab -->
            <div class="tab-content" id="payments">
                <h2 style="margin-bottom: 20px; font-size: 2.2rem; color: #333;">Payment History</h2>
                
                <?php if (count($payments) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Date</th>
                                    <th>Package/Product</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['payment_id']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo $payment['package_name']; ?></td>
                                        <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo $payment['payment_method']; ?></td>
                                        <td>
                                            <span class="status-badge status-completed">
                                                <?php echo $payment['payment_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_receipt.php?id=<?php echo $payment['payment_id']; ?>" class="action-btn btn-view">View Receipt</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; font-size: 1.6rem; color: #666;">No payment history available.</p>
                <?php endif; ?>
            </div>
            
            <!-- Orders Tab -->
            <div class="tab-content" id="orders">
                <h2 style="margin-bottom: 20px; font-size: 2.2rem; color: #333;">My Orders</h2>
                
                <?php if (count($orders) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo $order['item_name']; ?></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td>₹<?php echo number_format($order['amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($order['order_status']) === 'pending' ? 'status-pending' : 'status-completed'; ?>">
                                                <?php echo $order['order_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="track_order.php?id=<?php echo $order['order_id']; ?>" class="action-btn btn-view">Track</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; font-size: 1.6rem; color: #666;">No orders available.</p>
                <?php endif; ?>
            </div>
            
            <!-- Wishlist Tab -->
            <div class="tab-content" id="wishlist">
                <h2 style="margin-bottom: 20px; font-size: 2.2rem; color: #333;">My Wishlist</h2>
                
                <?php if (count($wishlist) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Added Date</th>
                                    <th>Package/Product</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wishlist as $item): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($item['added_date'])); ?></td>
                                        <td><?php echo $item['package_name']; ?></td>
                                        <td>
                                            <a href="payment.html?name=<?php echo urlencode($item['package_name']); ?>&amount=300" class="action-btn btn-view">Book Now</a>
                                            <a href="remove_wishlist.php?id=<?php echo $item['id']; ?>" class="action-btn btn-delete">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; font-size: 1.6rem; color: #666;">Your wishlist is empty.</p>
                <?php endif; ?>
            </div>
            
            <!-- Emergency Tab -->
            <div class="tab-content" id="emergency">
                <div class="emergency-box">
                    <h2 class="emergency-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Emergency Support
                    </h2>
                    
                    <p class="emergency-desc">
                        If you're experiencing an emergency during your trip, please contact us immediately using one of the methods below. Our 24/7 support team is ready to assist you.
                    </p>
                    
                    <div class="emergency-contact">
                        <p><strong>Emergency Hotline:</strong> +91-800-123-4567 (Available 24/7)</p>
                        <p><strong>Emergency Email:</strong> emergency@travelutsav.com</p>
                        <p><strong>WhatsApp Support:</strong> +91-724-114-2006</p>
                    </div>
                    
                    <p style="font-size: 1.5rem; margin-bottom: 20px;">
                        For non-urgent matters or to submit a detailed emergency request, please use the form below:
                    </p>
                    
                    <form action="submit_emergency.php" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Full Name *</label>
                                <input type="text" name="name" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" value="<?php echo $userName; ?>" required>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Email *</label>
                                <input type="email" name="email" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" value="<?php echo $userEmail; ?>" required>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Phone Number *</label>
                                <input type="tel" name="phone" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" required>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Current Location *</label>
                                <input type="text" name="location" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" required>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Emergency Type *</label>
                            <select name="emergency_type" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" required>
                                <option value="">Select emergency type</option>
                                <option value="Medical">Medical Emergency</option>
                                <option value="Transportation">Transportation Issue</option>
                                <option value="Lost">Lost Items/Documents</option>
                                <option value="Weather">Weather-related Issue</option>
                                <option value="Safety">Safety Concern</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 1.4rem;">Describe your emergency *</label>
                            <textarea name="message" rows="5" style="width: 100%; padding: 10px; font-size: 1.4rem; border: 1px solid #ddd; border-radius: 5px;" required></textarea>
                        </div>
                        
                        <button type="submit" class="emergency-btn">Submit Emergency Request</button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <div class="login-box">
                <h2 style="font-size: 2.5rem; color: #219150; margin-bottom: 20px;">My Account</h2>
                <p>Please login to access your account details, view your bookings, track orders, and more.</p>
                <a href="../auth.php" class="btn-login">Login Now</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- footer section -->
    <section class="footer">
        <div class="box-container">
            <div class="box">
                <h3>Quick Links</h3>
                <a href="home.html"><i class="fas fa-angle-right"></i>Home</a>
                <a href="about.html"><i class="fas fa-angle-right"></i>About</a>
                <a href="packages.html"><i class="fas fa-angle-right"></i>Packages</a>
                <a href="shop.html"><i class="fas fa-angle-right"></i>Shop</a>
                <a href="reviews.html"><i class="fas fa-angle-right"></i>Reviews</a>
                <a href="blogs.html"><i class="fas fa-angle-right"></i>Blogs</a>
            </div>
            <div class="box">
                <h3>Extra Links</h3>
                <a href="my_account.php"><i class="fas fa-angle-right"></i>My Account</a>
                <a href="my_orders.php"><i class="fas fa-angle-right"></i>My Orders</a>
                <a href="my_wishlist.php"><i class="fas fa-angle-right"></i>My Wishlist</a>
                <a href="support.php"><i class="fas fa-angle-right"></i>Ask Questions</a>
                <a href="terms.php"><i class="fas fa-angle-right"></i>Terms of Use</a>
                <a href="privacy.php"><i class="fas fa-angle-right"></i>Privacy Policy</a>
            </div>
            <div class="box">
                <h3>Contact Info</h3>
                <a href="#"><i class="fas fa-phone"></i>+91-7241142006</a>
                <a href="#"><i class="fas fa-phone"></i>+91-9705745856</a>
                <a href="#"><i class="fas fa-envelope"></i>travelutsav@gmail.com</a>
                <a href="#"><i class="fas fa-map"></i>Indore, Madhya Pradesh - 452001</a>
            </div>
            <div class="box">
                <h3>Follow Us</h3>
                <a href="#"><i class="fab fa-facebook-f"></i>Facebook</a>
                <a href="#"><i class="fab fa-twitter"></i>Twitter</a>
                <a href="#"><i class="fab fa-instagram"></i>Instagram</a>
                <a href="#"><i class="fab fa-linkedin"></i>LinkedIn</a>
                <a href="#"><i class="fab fa-github"></i>Github</a>
            </div>
        </div>
        <div class="credit">Created by <span>Travel Utsav</span> | All Rights Reserved</div>
    </section>

    <!-- custom js file link -->
    <script>
        // Menu button functionality
        document.querySelector('#menu-btn').onclick = () => {
            document.querySelector('.navbar').classList.toggle('active');
        }

        // Search button functionality
        document.querySelector('#search-btn').onclick = () => {
            document.querySelector('.search-form').classList.toggle('active');
        }
        
        // Tab functionality
        function showTab(tabId) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked button
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html> 