<?php
// Include database connection
include ('database.php');

// SQL to create table
$sql = "CREATE TABLE users ("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "name VARCHAR(100) NOT NULL,"
    . "email VARCHAR(100) NOT NULL UNIQUE,"
    . "phone VARCHAR(15) NOT NULL UNIQUE,"
    . "password VARCHAR(255) NOT NULL,"
    . "address TEXT NOT NULL"
    . ");";

// Execute SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table users created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();
?>
