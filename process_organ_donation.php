<?php
header("Content-Type: application/json");
require_once "config.php";

$conn = getDBConnection();

$fullName = $_POST['fullName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? '';
$bloodGroup = $_POST['bloodGroup'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$organs = isset($_POST['organs']) ? implode(",", $_POST['organs']) : '';
$emergencyContact = $_POST['emergencyContact'] ?? '';
$emergencyPhone = $_POST['emergencyPhone'] ?? '';

$stmt = $conn->prepare("
    INSERT INTO organ_donors 
    (full_name, email, phone, dob, gender, blood_group, address, city, state, organs, emergency_contact, emergency_phone)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssssssss",
    $fullName,
    $email,
    $phone,
    $dob,
    $gender,
    $bloodGroup,
    $address,
    $city,
    $state,
    $organs,
    $emergencyContact,
    $emergencyPhone
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Organ pledge successful 🫀"]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
