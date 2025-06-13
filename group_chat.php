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
    SELECT g.*, d.name as destination_name, d.image_url
    FROM groups g
    JOIN destinations d ON g.destination_id = d.destination_id
    WHERE g.group_id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    redirect('dashboard.php');
}

// Get group members
$members = getGroupMembers($pdo, $groupId);

// Handle AJAX message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = sanitize($_POST['message'] ?? '');
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$groupId, $userId, $message]);
            
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
            exit;
        } catch (PDOException $e) {
            // Return error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit;
    }
}

// Handle AJAX message retrieval
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $lastId = filter_var($_GET['last_id'] ?? 0, FILTER_VALIDATE_INT);
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_image
            FROM messages m
            JOIN users u ON m.user_id = u.user_id
            WHERE m.group_id = ? AND m.message_id > ?
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute([$groupId, $lastId]);
        $messages = $stmt->fetchAll();
        
        // Return messages as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    } catch (PDOException $e) {
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get initial messages
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.profile_image
    FROM messages m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.group_id = ?
    ORDER BY m.sent_at ASC
    LIMIT 50
");
$stmt->execute([$groupId]);
$initialMessages = $stmt->fetchAll();

// Get the last message ID for polling
$lastMessageId = 0;
if (!empty($initialMessages)) {
    $lastMessageId = end($initialMessages)['message_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Chat - <?php echo htmlspecialchars($group['group_name']); ?> - Travel Utsav</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="Images/home.css">
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
        
        .chat-container {
            display: flex;
            gap: 2rem;
            height: 70vh;
        }
        
        .chat-sidebar {
            width: 300px;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }
        
        .chat-main {
            flex: 1;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .group-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            margin-bottom: 1.5rem;
        }
        
        .group-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .group-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .group-details h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .group-details p {
            font-size: 1.4rem;
            color: #666;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .member-list {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex: 1;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #219150;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-size: 1.6rem;
            color: #333;
        }
        
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-title {
            font-size: 2rem;
            color: #333;
        }
        
        .chat-actions a {
            color: #219150;
            font-size: 1.6rem;
            margin-left: 1.5rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .message {
            display: flex;
            gap: 1rem;
            max-width: 80%;
        }
        
        .message.own-message {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #219150;
            flex-shrink: 0;
        }
        
        .message-content {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 1rem;
            font-size: 1.6rem;
            color: #333;
            position: relative;
        }
        
        .own-message .message-content {
            background: #e6f7ef;
        }
        
        .message-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }
        
        .message-sender {
            font-weight: 600;
            color: #219150;
        }
        
        .message-time {
            color: #999;
        }
        
        .message-text {
            line-height: 1.5;
        }
        
        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 1rem;
        }
        
        .chat-input input {
            flex: 1;
            padding: 1rem;
            font-size: 1.6rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            outline: none;
        }
        
        .chat-input button {
            padding: 1rem 2rem;
            background: #219150;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .chat-input button:hover {
            background: #1a7b42;
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
            text-decoration: none;
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
        
        .loading {
            text-align: center;
            padding: 2rem;
            font-size: 1.6rem;
            color: #666;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #219150;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: auto;
            }
            
            .chat-sidebar {
                width: 100%;
                height: auto;
                margin-bottom: 2rem;
            }
            
            .chat-main {
                height: 60vh;
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
            <h1 class="page-title">Group Chat</h1>
            <a href="group_details.php?id=<?php echo $groupId; ?>" class="btn">Back to Group Details</a>
        </div>
        
        <div class="chat-container">
            <div class="chat-sidebar">
                <div class="group-info">
                    <div class="group-image">
                        <img src="<?php echo htmlspecialchars($group['image_url']); ?>" alt="<?php echo htmlspecialchars($group['group_name']); ?>">
                    </div>
                    <div class="group-details">
                        <h3><?php echo htmlspecialchars($group['group_name']); ?></h3>
                        <p><?php echo htmlspecialchars($group['destination_name']); ?></p>
                    </div>
                </div>
                
                <h3 class="section-title">Members (<?php echo count($members); ?>)</h3>
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
                                <div class="member-name"><?php echo htmlspecialchars($member['username']); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="chat-main">
                <div class="chat-header">
                    <h2 class="chat-title"><?php echo htmlspecialchars($group['group_name']); ?> - Chat</h2>
                    <div class="chat-actions">
                        <a href="#" id="refresh-chat"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <?php if (empty($initialMessages)): ?>
                        <div style="text-align: center; padding: 3rem; color: #666; font-size: 1.6rem;">
                            No messages yet. Be the first to send a message!
                        </div>
                    <?php else: ?>
                        <?php foreach ($initialMessages as $message): ?>
                            <div class="message <?php echo $message['user_id'] == $userId ? 'own-message' : ''; ?>" data-id="<?php echo $message['message_id']; ?>">
                                <div class="message-avatar">
                                    <?php if ($message['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($message['profile_image']); ?>" alt="<?php echo htmlspecialchars($message['username']); ?>">
                                    <?php else: ?>
                                        <?php echo substr($message['username'], 0, 1); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-info">
                                        <span class="message-sender"><?php echo htmlspecialchars($message['username']); ?></span>
                                        <span class="message-time"><?php echo date('M d, H:i', strtotime($message['sent_at'])); ?></span>
                                    </div>
                                    <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <input type="text" id="message-input" placeholder="Type your message here...">
                    <button id="send-button">Send</button>
                </div>
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
    <script src="Images/home.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            const refreshButton = document.getElementById('refresh-chat');
            
            let lastMessageId = <?php echo $lastMessageId; ?>;
            let isPolling = false;
            
            // Scroll to bottom of chat
            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Initial scroll
            scrollToBottom();
            
            // Send message
            function sendMessage() {
                const message = messageInput.value.trim();
                
                if (message === '') {
                    return;
                }
                
                // Disable input and button while sending
                messageInput.disabled = true;
                sendButton.disabled = true;
                
                // Send message to server
                fetch('group_chat.php?id=<?php echo $groupId; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear input
                        messageInput.value = '';
                        
                        // Get new messages (including the one just sent)
                        getNewMessages();
                    } else {
                        console.error('Error sending message:', data.error);
                        alert('Failed to send message. Please try again.');
                    }
                    
                    // Re-enable input and button
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to send message. Please try again.');
                    
                    // Re-enable input and button
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                });
            }
            
            // Get new messages
            function getNewMessages() {
                if (isPolling) {
                    return;
                }
                
                isPolling = true;
                
                fetch(`group_chat.php?id=<?php echo $groupId; ?>&action=get_messages&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        // Add new messages to chat
                        data.messages.forEach(message => {
                            const isOwnMessage = message.user_id == <?php echo $userId; ?>;
                            const messageElement = document.createElement('div');
                            messageElement.className = `message ${isOwnMessage ? 'own-message' : ''}`;
                            messageElement.dataset.id = message.message_id;
                            
                            const avatarContent = message.profile_image 
                                ? `<img src="${message.profile_image}" alt="${message.username}">`
                                : message.username.substring(0, 1);
                            
                            messageElement.innerHTML = `
                                <div class="message-avatar">${avatarContent}</div>
                                <div class="message-content">
                                    <div class="message-info">
                                        <span class="message-sender">${message.username}</span>
                                        <span class="message-time">${new Date(message.sent_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' })}</span>
                                    </div>
                                    <div class="message-text">${message.message.replace(/\n/g, '<br>')}</div>
                                </div>
                            `;
                            
                            chatMessages.appendChild(messageElement);
                            
                            // Update last message ID
                            lastMessageId = message.message_id;
                        });
                        
                        // Scroll to bottom
                        scrollToBottom();
                    }
                    
                    isPolling = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    isPolling = false;
                });
            }
            
            // Send message on button click
            sendButton.addEventListener('click', sendMessage);
            
            // Send message on Enter key
            messageInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Refresh chat on button click
            refreshButton.addEventListener('click', function(event) {
                event.preventDefault();
                getNewMessages();
            });
            
            // Poll for new messages every 5 seconds
            setInterval(getNewMessages, 5000);
        });
    </script>
</body>
</html> 