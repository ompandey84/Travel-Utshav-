<?php
session_start();
require_once '../includes/db_connect.php';

// Generate a unique payment ID
function generatePaymentID() {
    return 'PAY' . date('Ymd') . rand(1000, 9999);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get payment details from form
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Credit Card';
    $packageName = isset($_POST['package_name']) ? $_POST['package_name'] : 'Unknown Package';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $userName = $_POST['name'];
    $userEmail = $_POST['email'];
    
    // Additional fields depending on payment method
    $cardNumber = isset($_POST['card_number']) ? substr($_POST['card_number'], -4) : '';
    $upiId = isset($_POST['upi_id']) ? $_POST['upi_id'] : '';
    $bankName = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
    
    // Generate a unique payment ID
    $paymentId = generatePaymentID();
    
    try {
        // Insert payment details into database
        $sql = "INSERT INTO payments (payment_id, user_name, user_email, package_name, amount, payment_method) 
                VALUES (:payment_id, :user_name, :user_email, :package_name, :amount, :payment_method)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':user_name' => $userName,
            ':user_email' => $userEmail,
            ':package_name' => $packageName,
            ':amount' => $amount,
            ':payment_method' => $paymentMethod
        ]);
        
        // Mark receipt as generated
        $sql = "UPDATE payments SET receipt_generated = TRUE WHERE payment_id = :payment_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        
        // Store payment details in session for receipt generation
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_id'] = $paymentId;
        $_SESSION['package_name'] = $packageName;
        $_SESSION['amount'] = $amount;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['payment_method'] = $paymentMethod;
        $_SESSION['payment_date'] = date('Y-m-d H:i:s');
        
        // Add payment method specific details
        if ($paymentMethod == 'Credit Card' || $paymentMethod == 'Debit Card') {
            $_SESSION['card_number'] = $cardNumber;
        } elseif ($paymentMethod == 'UPI') {
            $_SESSION['upi_id'] = $upiId;
        } elseif ($paymentMethod == 'Net Banking') {
            $_SESSION['bank_name'] = $bankName;
        }
        
        // Redirect to receipt page
        header('Location: payment_receipt.php');
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['payment_error'] = "Payment failed: " . $e->getMessage();
        header('Location: payment.html');
        exit;
    }
} else {
    // Redirect if accessed directly without form submission
    header('Location: payment.html');
    exit;
}
?> 