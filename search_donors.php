<?php
header("Content-Type: application/json");
require_once "config.php";

$conn = getDBConnection();

$bloodGroup  = $_GET['bloodGroup']  ?? '';   // filters blood table only
$organFilter = $_GET['organ']       ?? '';   // filters organ table only

// ════════════════════════════════════════════════════════════════════
//  BLOOD DONORS — filtered by blood group only
// ════════════════════════════════════════════════════════════════════
$bloodSQL    = "SELECT id, 'Blood Donor' AS donor_type, full_name, email, phone, blood_group, city, NULL AS organs FROM blood_donors WHERE 1=1";
$bloodParams = [];
$bloodTypes  = "";

if (!empty($bloodGroup)) {
    $bloodSQL     .= " AND blood_group = ?";
    $bloodParams[] = $bloodGroup;
    $bloodTypes   .= "s";
}

$stmtBlood = $conn->prepare($bloodSQL);
if (!empty($bloodParams)) {
    $stmtBlood->bind_param($bloodTypes, ...$bloodParams);
}
$stmtBlood->execute();
$bloodData = $stmtBlood->get_result()->fetch_all(MYSQLI_ASSOC);

// ════════════════════════════════════════════════════════════════════
//  ORGAN DONORS — filtered by organ type only
// ════════════════════════════════════════════════════════════════════
$organSQL    = "SELECT id, 'Organ Donor' AS donor_type, full_name, email, phone, blood_group, city, organs FROM organ_donors WHERE 1=1";
$organParams = [];
$organTypes  = "";

if (!empty($organFilter)) {
    $organSQL     .= " AND LOWER(organs) LIKE LOWER(?)";
    $organParams[] = "%" . $organFilter . "%";
    $organTypes   .= "s";
}

$stmtOrgan = $conn->prepare($organSQL);
if (!empty($organParams)) {
    $stmtOrgan->bind_param($organTypes, ...$organParams);
}
$stmtOrgan->execute();
$organData = $stmtOrgan->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Merge both sets into one data array ─────────────────────────────
$data = array_merge($bloodData, $organData);

// ════════════════════════════════════════════════════════════════════
//  MATCHED BLOOD — connections already made (non-pending), by blood group
// ════════════════════════════════════════════════════════════════════
$matchedBloodSQL = "
    SELECT bd.blood_group, COUNT(*) AS matched_count
    FROM donor_connections dc
    JOIN blood_donors bd ON dc.blood_donor_id = bd.id
    WHERE dc.connection_type = 'blood' AND dc.is_pending = 0
    GROUP BY bd.blood_group
";
$matchedBlood = [];
$mbResult = $conn->query($matchedBloodSQL);
if ($mbResult) {
    while ($row = $mbResult->fetch_assoc()) {
        $matchedBlood[$row['blood_group']] = (int)$row['matched_count'];
    }
}

// ════════════════════════════════════════════════════════════════════
//  MATCHED ORGANS — connections already made (non-pending), by organ type
// ════════════════════════════════════════════════════════════════════
$matchedOrganSQL = "
    SELECT TRIM(organ_part) AS organ_type, COUNT(*) AS matched_count
    FROM (
        SELECT dc.id,
               SUBSTRING_INDEX(SUBSTRING_INDEX(od.organs, ',', numbers.n), ',', -1) AS organ_part
        FROM donor_connections dc
        JOIN organ_donors od ON dc.organ_donor_id = od.id
        JOIN (
            SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
        ) numbers ON CHAR_LENGTH(od.organs) - CHAR_LENGTH(REPLACE(od.organs, ',', '')) >= numbers.n - 1
        WHERE dc.connection_type = 'organ' AND dc.is_pending = 0
    ) expanded
    GROUP BY organ_type
";
$matchedOrgans = [];
$moResult = $conn->query($matchedOrganSQL);
if ($moResult) {
    while ($row = $moResult->fetch_assoc()) {
        $matchedOrgans[trim($row['organ_type'])] = (int)$row['matched_count'];
    }
}

echo json_encode([
    "success"       => true,
    "data"          => $data,
    "matchedBlood"  => $matchedBlood,
    "matchedOrgans" => $matchedOrgans,
]);

$conn->close();
?>