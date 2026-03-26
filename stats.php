<?php
header("Content-Type: application/json");
require_once "config.php";

$response = [
    "success" => false,
    "bloodDonors" => 0,
    "organPledges" => 0,
    "livesSaved" => 0
];

try {

    $conn = getDBConnection();

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Blood donors count
    $bloodResult = $conn->query("SELECT COUNT(*) AS total FROM blood_donors");
    if ($bloodResult) {
        $row = $bloodResult->fetch_assoc();
        $response["bloodDonors"] = (int)$row["total"];
    }

    // Organ donors count
    $organResult = $conn->query("SELECT COUNT(*) AS total FROM organ_donors");
    if ($organResult) {
        $row = $organResult->fetch_assoc();
        $response["organPledges"] = (int)$row["total"];
    }

    // Lives saved calculation
    $response["livesSaved"] =
        ($response["bloodDonors"] * 3) +
        ($response["organPledges"] * 8);

    $response["success"] = true;

    $conn->close();

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

echo json_encode($response);
?>
