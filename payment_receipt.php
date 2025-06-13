<?php
session_start();

// Redirect if payment details not found
if (!isset($_SESSION['payment_success']) || $_SESSION['payment_success'] !== true) {
    header('Location: payment.html');
    exit;
}

// Get payment details from session
$paymentId = $_SESSION['payment_id'];
$packageName = $_SESSION['package_name'];
$amount = $_SESSION['amount'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$paymentMethod = $_SESSION['payment_method'];
$paymentDate = $_SESSION['payment_date'];

// Format the date for display
$formattedDate = date('F j, Y, g:i a', strtotime($paymentDate));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-logo {
            font-size: 2.5rem;
            color: #219150;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .receipt-logo i {
            margin-right: 10px;
        }
        
        .receipt-title {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .receipt-subtitle {
            color: #666;
            font-size: 1.4rem;
        }
        
        .receipt-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        
        .detail-label {
            flex: 1;
            font-weight: bold;
            color: #555;
            font-size: 1.6rem;
        }
        
        .detail-value {
            flex: 2;
            color: #333;
            font-size: 1.6rem;
        }
        
        .amount-row {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 30px;
            display: flex;
        }
        
        .amount-label {
            flex: 1;
            font-weight: bold;
            color: #219150;
            font-size: 1.8rem;
        }
        
        .amount-value {
            flex: 2;
            color: #219150;
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 1.4rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .receipt-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .receipt-btn {
            padding: 12px 25px;
            background-color: #219150;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.6rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .receipt-btn.secondary {
            background-color: #6c757d;
        }
        
        .receipt-btn.download {
            background-color: #007bff;
        }
        
        .receipt-btn:hover {
            opacity: 0.9;
        }
        
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            header, .footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- header section -->
    <header class="header no-print">
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

    <!-- Receipt Section -->
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <div class="receipt-logo">
                <i class="fas fa-paper-plane"></i>
                <span>Travel Utsav</span>
            </div>
            <h1 class="receipt-title">Payment Receipt</h1>
            <p class="receipt-subtitle">Thank you for your payment!</p>
            <div class="success-badge">
                <i class="fas fa-check-circle"></i> Payment Successful
            </div>
        </div>
        
        <div class="receipt-details">
            <div class="detail-row">
                <div class="detail-label">Receipt Number:</div>
                <div class="detail-value"><?php echo $paymentId; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Date & Time:</div>
                <div class="detail-value"><?php echo $formattedDate; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Customer Name:</div>
                <div class="detail-value"><?php echo $userName; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div class="detail-value"><?php echo $userEmail; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Package/Product:</div>
                <div class="detail-value"><?php echo $packageName; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Payment Method:</div>
                <div class="detail-value"><?php echo $paymentMethod; ?>
                    <?php 
                    if ($paymentMethod == 'Credit Card' || $paymentMethod == 'Debit Card') {
                        echo " (ending with " . $_SESSION['card_number'] . ")";
                    } elseif ($paymentMethod == 'UPI') {
                        echo " (" . $_SESSION['upi_id'] . ")";
                    } elseif ($paymentMethod == 'Net Banking') {
                        echo " (" . $_SESSION['bank_name'] . ")";
                    }
                    ?>
                </div>
            </div>
            
            <div class="amount-row">
                <div class="amount-label">Total Amount Paid:</div>
                <div class="amount-value">₹<?php echo number_format($amount, 2); ?></div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>This is a computer-generated receipt and does not require a physical signature.</p>
            <p>For any queries, please contact us at: <strong>travelutsav@gmail.com</strong> or <strong>+91-7241142006</strong></p>
            <p>&copy; <?php echo date('Y'); ?> Travel Utsav. All rights reserved.</p>
        </div>
        
        <div class="receipt-actions no-print">
            <button class="receipt-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
            <a href="my_account.php" class="receipt-btn secondary"><i class="fas fa-user"></i> My Account</a>
            <a href="home.html" class="receipt-btn download"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <!-- Footer section (won't show in print) -->
    <section class="footer no-print">
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

    <!-- Custom JS file link -->
    <script>
        document.querySelector('#menu-btn').onclick = () => {
            document.querySelector('.navbar').classList.toggle('active');
        }

        document.querySelector('#search-btn').onclick = () => {
            document.querySelector('.search-form').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
// Clear payment session variables after generating receipt
// Don't clear immediately to allow for printing/viewing
// They'll be cleared when user navigates to another page or refreshes
?> 