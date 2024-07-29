<?php
session_start();

$client_id = '361297376499-ks4d1fchftp2it3r9c2irlj2dvvrq46h.apps.googleusercontent.com';
$id_token = $_POST['idtoken'];

// Validate the ID token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/tokeninfo?id_token=$id_token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($response, true);

if ($userinfo['aud'] == $client_id) {
    // Token is valid
    $google_id = $userinfo['sub'];
    $name = $userinfo['name'];
    $email = $userinfo['email'];
    $picture = $userinfo['picture'];

    // Connect to the database
    $conn = new mysqli('localhost', 'username', 'password', 'database');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, update details
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, picture = ? WHERE google_id = ?");
        $stmt->bind_param("ssss", $name, $email, $picture, $google_id);
    } else {
        // New user, insert details
        $stmt = $conn->prepare("INSERT INTO users (google_id, name, email, picture) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $google_id, $name, $email, $picture);
    }
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Send user info back to
}
