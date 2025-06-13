<?php
// Include your database configuration file
require_once "./database.php"; 

// Get form data
$name = $_POST["name"];
$review = $_POST["review"];

// Handle image upload
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
  // ... (rest of your image upload and database insertion code) ... 
} else {
  // ... (database insertion code without image) ...
}

$conn->close();
?>