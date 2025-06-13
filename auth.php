<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$loginErrors = [];
$registerErrors = [];
$registerSuccess = false;

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $loginErrors[] = "Username is required";
    }
    
    if (empty($password)) {
        $loginErrors[] = "Password is required";
    }
    
    // Check credentials
    if (empty($loginErrors)) {
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
            $loginErrors[] = "Invalid username or password";
        }
    }
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Validate input
    $username = sanitize($_POST['reg_username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    
    // Check if username is empty
    if (empty($username)) {
        $registerErrors[] = "Username is required";
    }
    
    // Check if email is valid
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerErrors[] = "Valid email is required";
    }
    
    // Check if password is valid
    if (empty($password) || strlen($password) < 8) {
        $registerErrors[] = "Password must be at least 8 characters long";
    }
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $registerErrors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    if (empty($registerErrors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $registerErrors[] = "Username or email already exists";
        }
    }
    
    // If no errors, register the user
    if (empty($registerErrors)) {
        try {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName]);
            
            // Set success message
            $registerSuccess = true;
            
            // Redirect to login tab after 3 seconds
            echo "<script>setTimeout(function() { document.getElementById('login-tab').click(); }, 3000);</script>";
            
        } catch (PDOException $e) {
            $registerErrors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

// Determine which tab to show by default
$activeTab = 'login';
if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
    $activeTab = 'register';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
    <style>
        .auth-container {
            max-width: 600px;
            margin: 8rem auto 3rem;
            background: #fff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .tab-container {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            font-size: 1.8rem;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #219150;
            border-bottom: 3px solid #219150;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-list {
            margin: 0;
            padding-left: 2rem;
        }
        
        .social-login {
            margin-top: 2rem;
            text-align: center;
        }
        
        .social-login p {
            font-size: 1.6rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            color: #fff;
            font-size: 1.8rem;
            transition: transform 0.3s;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
        }
        
        .facebook {
            background: #3b5998;
        }
        
        .google {
            background: #dd4b39;
        }
        
        .twitter {
            background: #1da1f2;
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
            <a href="Images/packages.html">packages</a>
            <a href="Images/home.html#reviews">reviews</a>
            <a href="Images/home.html#blogs">blogs</a>
            <a href="Images/chat.html">AI Assistant</a>
        </nav>
        <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <div id="search-btn" class="fas fa-search"></div>
            <a href="Images/shop.html" class="fas fa-shopping-cart"></a>
            <a href="Images/travelPage.html" class="fa-solid fa-motorcycle"></a>
            <a href="group_chat.php" class="fa-solid fa-comments"></a>
            <a href="auth.php" class="fa-regular fa-user"></a>
        </div>
    </header>

    <div class="search-form">
        <div id="close-search" class="fas fa-times"></div>
        <form action="Images/search-results.html" method="GET">
            <input type="search" name="query" placeholder="search here..." id="search-box">
            <label for="search-box" class="fas fa-search"></label>
        </form>
    </div>

    <div class="auth-container">
        <div class="tab-container">
            <div id="login-tab" class="tab <?php echo $activeTab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">Login</div>
            <div id="register-tab" class="tab <?php echo $activeTab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">Register</div>
        </div>
        
        <!-- Login Tab Content -->
        <div id="login-content" class="tab-content <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
            <?php if (!empty($loginErrors)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($loginErrors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="auth.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-submit" name="login">Login</button>
            </form>
            
            <div class="social-login">
                <p>Or login with</p>
                <div class="social-icons">
                    <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon google"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Register Tab Content -->
        <div id="register-content" class="tab-content <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
            <?php if ($registerSuccess): ?>
                <div class="alert alert-success">
                    Registration successful! You will be redirected to the login tab shortly.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($registerErrors)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($registerErrors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$registerSuccess): ?>
                <form action="auth.php?tab=register" method="POST">
                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <input type="text" class="form-control" id="reg_username" name="reg_username" value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit" name="register">Register</button>
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
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab and content
            document.getElementById(tabName + '-tab').classList.add('active');
            document.getElementById(tabName + '-content').classList.add('active');
        }
    </script>
</body>
</html> 