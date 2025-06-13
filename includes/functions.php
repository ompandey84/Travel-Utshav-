<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect user to a specific page
 * @param string $location
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Sanitize user input
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate the geographic center (centroid) of multiple coordinates
 * @param array $coordinates Array of arrays containing 'latitude' and 'longitude' keys
 * @return array Associative array with 'latitude' and 'longitude' keys
 */
function calculateCentroid($coordinates) {
    if (empty($coordinates)) {
        return null;
    }
    
    $numCoords = count($coordinates);
    $X = 0.0;
    $Y = 0.0;
    $Z = 0.0;
    
    foreach ($coordinates as $coord) {
        $lat = deg2rad($coord['latitude']);
        $lon = deg2rad($coord['longitude']);
        
        // Convert lat/lon to Cartesian coordinates
        $X += cos($lat) * cos($lon);
        $Y += cos($lat) * sin($lon);
        $Z += sin($lat);
    }
    
    // Calculate average
    $X /= $numCoords;
    $Y /= $numCoords;
    $Z /= $numCoords;
    
    // Convert back to lat/lon
    $lon = atan2($Y, $X);
    $hyp = sqrt($X * $X + $Y * $Y);
    $lat = atan2($Z, $hyp);
    
    return [
        'latitude' => rad2deg($lat),
        'longitude' => rad2deg($lon)
    ];
}

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param float $lat1 Latitude of first point
 * @param float $lon1 Longitude of first point
 * @param float $lat2 Latitude of second point
 * @param float $lon2 Longitude of second point
 * @return float Distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the Earth in kilometers
    
    $latDiff = deg2rad($lat2 - $lat1);
    $lonDiff = deg2rad($lon2 - $lon1);
    
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDiff / 2) * sin($lonDiff / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Find nearby points of interest using Google Places API
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @param int $radius Search radius in meters
 * @param string $apiKey Google Maps API key
 * @return array Array of nearby places
 */
function findNearbyPlaces($lat, $lng, $radius = 500, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

/**
 * Get user data by ID
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all members of a group
 * @param PDO $pdo Database connection
 * @param int $groupId Group ID
 * @return array Array of group members
 */
function getGroupMembers($pdo, $groupId) {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_image, 
               u.latitude, u.longitude, gm.joined_at, gm.status
        FROM users u
        JOIN group_members gm ON u.user_id = gm.user_id
        WHERE gm.group_id = ?
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll();
}

/**
 * Update meeting point for a group
 * @param PDO $pdo Database connection
 * @param int $groupId Group ID
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @param string $name Name of the meeting point
 * @return bool Success status
 */
function updateMeetingPoint($pdo, $groupId, $lat, $lng, $name = null) {
    $stmt = $pdo->prepare("
        UPDATE groups 
        SET meeting_point_lat = ?, meeting_point_long = ?, meeting_point_name = ? 
        WHERE group_id = ?
    ");
    return $stmt->execute([$lat, $lng, $name, $groupId]);
}
?> 