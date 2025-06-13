<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $partner = $_POST['partner'];
    $vehicle = $_POST['vehicle'];

    $stmt = $conn->prepare("INSERT INTO bookings (origin, destination, date, time, partner_preference, vehicle) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $origin, $destination, $date, $time, $partner, $vehicle);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
