<?php
session_start();
require_once '../includes/db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get emergency details from form
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $emergencyType = isset($_POST['emergency_type']) ? $_POST['emergency_type'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    
    // Get user ID if logged in
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($emergencyType) || empty($message)) {
        $_SESSION['emergency_error'] = "All fields are required. Please fill out the form completely.";
        header('Location: my_account.php'); // Redirect back to the form
        exit;
    }
    
    try {
        // Insert emergency request into database
        $sql = "INSERT INTO emergency_support (user_id, user_name, user_email, phone, message, location, emergency_type) 
                VALUES (:user_id, :user_name, :user_email, :phone, :message, :location, :emergency_type)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_name' => $name,
            ':user_email' => $email,
            ':phone' => $phone,
            ':message' => $message,
            ':location' => $location,
            ':emergency_type' => $emergencyType
        ]);
        
        // Set success message
        $_SESSION['emergency_success'] = true;
        
        // Send emergency notification email to support team (optional)
        $to = "emergency@travelutsav.com";
        $subject = "EMERGENCY REQUEST: " . $emergencyType;
        $emailMessage = "
            Emergency Request Details:
            
            Name: $name
            Email: $email
            Phone: $phone
            Location: $location
            Emergency Type: $emergencyType
            
            Message:
            $message
            
            This is an urgent request. Please respond immediately.
        ";
        
        $headers = "From: noreply@travelutsav.com" . "\r\n";
        
        // Send email (commented out as we don't have actual email server)
        // mail($to, $subject, $emailMessage, $headers);
        
        // Redirect to confirmation page
        header('Location: emergency_confirmation.php');
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['emergency_error'] = "An error occurred: " . $e->getMessage();
        header('Location: my_account.php');
        exit;
    }
} else {
    // Redirect if accessed directly without form submission
    header('Location: my_account.php');
    exit;
}
?> 