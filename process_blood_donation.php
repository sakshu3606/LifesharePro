<?php
header("Content-Type: application/json");
require_once "config.php";

$conn = getDBConnection();

// Get form values EXACTLY matching HTML names
$fullName = $_POST['fullName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? '';
$bloodGroup = $_POST['bloodGroup'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$lastDonation = $_POST['lastDonation'] ?? null;

// Basic validation
if (empty($fullName) || empty($email) || empty($phone)) {
    echo json_encode(["success" => false, "message" => "Required fields missing"]);
    exit;
}

// Insert using prepared statement
$stmt = $conn->prepare("
    INSERT INTO blood_donors 
    (full_name, email, phone, dob, gender, blood_group, address, city, state, last_donation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssssss",
    $fullName,
    $email,
    $phone,
    $dob,
    $gender,
    $bloodGroup,
    $address,
    $city,
    $state,
    $lastDonation
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Donor registered successfully 🩸"]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
