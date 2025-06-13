<?php
session_start();

// Check if the emergency request was successful
if (!isset($_SESSION['emergency_success']) || $_SESSION['emergency_success'] !== true) {
    header('Location: my_account.php');
    exit;
}

// Clear the success flag
$_SESSION['emergency_success'] = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Request Submitted - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .confirmation-title {
            font-size: 2.8rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .confirmation-message {
            font-size: 1.6rem;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .contact-info {
            background-color: rgba(220, 53, 69, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .contact-info p {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .contact-info strong {
            color: #dc3545;
        }
        
        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1.6rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #219150;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
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

    <!-- Confirmation Container -->
    <div class="confirmation-container">
        <i class="fas fa-exclamation-circle confirmation-icon"></i>
        <h1 class="confirmation-title">Emergency Request Submitted</h1>
        
        <p class="confirmation-message">
            Your emergency request has been successfully submitted. Our support team has been notified and will contact you as soon as possible. In case of urgent matters, please use the following direct contact methods:
        </p>
        
        <div class="contact-info">
            <p><strong>Emergency Hotline:</strong> +91-800-123-4567 (Available 24/7)</p>
            <p><strong>Emergency Email:</strong> emergency@travelutsav.com</p>
            <p><strong>WhatsApp Support:</strong> +91-724-114-2006</p>
        </div>
        
        <p class="confirmation-message">
            Your safety is our top priority. Please stay in a secure location and keep your phone accessible. Our team will reach out to you shortly.
        </p>
        
        <div class="buttons">
            <a href="my_account.php" class="btn btn-secondary">Back to My Account</a>
            <a href="home.html" class="btn btn-primary">Return to Home</a>
        </div>
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
    </script>
</body>
</html> 