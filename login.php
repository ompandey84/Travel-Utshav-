<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Check credentials
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to dashboard
            redirect('dashboard.php');
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 8rem auto 3rem;
            background: #fff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            text-align: center;
            font-size: 2.5rem;
            color: #219150;
            margin-bottom: 2rem;
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
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #219150;
        }
        
        .btn-submit {
            display: block;
            width: 100%;
            padding: 1.2rem;
            background: #219150;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.8rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #1a7b42;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 1.6rem;
            color: #666;
        }
        
        .form-footer a {
            color: #219150;
            text-decoration: none;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-size: 1.6rem;
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
            <a href="login.php" class="fa-regular fa-user"></a>
        </div>
    </header>

    <div class="search-form">
        <div id="close-search" class="fas fa-times"></div>
        <form action="">
            <input type="search" name="" placeholder="search here..." id="search-box">
            <label for="search-box" class="fas fa-search"></label>
        </form>
    </div>

    <div class="form-container">
        <h2 class="form-title">Login to Your Account</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-submit">Login</button>
        </form>
        
        <div class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
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
</body>
</html> 