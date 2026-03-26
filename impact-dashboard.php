<?php
// ═══════════════════════════════════════════════════════════════════
//  LifeShare — Impact Dashboard
//  Uses Post/Redirect/Get (PRG) pattern to prevent duplicate
//  submissions on page refresh.
// ═══════════════════════════════════════════════════════════════════

session_start();

// ── Database ────────────────────────────────────────────────────────
try {
    $pdo = new PDO('mysql:host=localhost;dbname=lifeshare_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE,    PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ── Helpers ─────────────────────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ── Handle POST (PRG) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['submit_type'] ?? '';

    // ── Make Connection ─────────────────────────────────────────────
    if ($type === 'make_connection') {
        try {
            $connType     = trim($_POST['connection_type']   ?? '');
            $bloodDonorId = !empty($_POST['blood_donor_id'])    ? (int)$_POST['blood_donor_id']    : null;
            $organDonorId = !empty($_POST['organ_donor_id'])    ? (int)$_POST['organ_donor_id']    : null;
            $urgentReqId  = !empty($_POST['urgent_request_id']) ? (int)$_POST['urgent_request_id'] : null;
            $recipientName = trim($_POST['recipient_name']     ?? '');
            $recipientLoc  = trim($_POST['recipient_location'] ?? '');
            $recipientCase = trim($_POST['recipient_case']     ?? '');
            $livesSaved    = (int)($_POST['lives_saved']       ?? 0);
            $impactLabel   = trim($_POST['impact_label']       ?? '');
            $isPending     = isset($_POST['is_pending']) ? 1 : 0;
            $connDate      = $_POST['connection_date'] ?? date('Y-m-d');

            if (!$connType || !$recipientName) {
                throw new Exception('Connection type and recipient name are required.');
            }

            $donorName  = 'Unknown';
            $donorBG    = null;
            $donorMl    = null;
            $donorOrgan = null;
            $donorStatus= null;

            if ($connType === 'blood' && $bloodDonorId) {
                $st = $pdo->prepare('SELECT full_name, blood_group FROM blood_donors WHERE id = ?');
                $st->execute([$bloodDonorId]);
                if ($row = $st->fetch()) {
                    $donorName  = $row['full_name'];
                    $donorBG    = $row['blood_group'];
                    $donorMl    = !empty($_POST['donor_amount_ml']) ? (int)$_POST['donor_amount_ml'] : null;
                    $donorStatus= $donorMl ? $donorMl . 'ml donated' : 'Donated';
                }
            } elseif ($connType === 'organ' && $organDonorId) {
                $st = $pdo->prepare('SELECT full_name, blood_group, organs FROM organ_donors WHERE id = ?');
                $st->execute([$organDonorId]);
                if ($row = $st->fetch()) {
                    $donorName   = $row['full_name'];
                    $donorBG     = $row['blood_group'];
                    $donorOrgan  = $row['organs'];
                    $donorStatus = 'Active pledge';
                }
            }

            if (!$impactLabel) {
                $impactLabel = $livesSaved > 0
                    ? '🌟 Saved ' . $livesSaved . ' ' . ($livesSaved === 1 ? 'life' : 'lives')
                    : ($isPending ? '⏳ Pledge Active' : 'Connection Made');
            }

            $pdo->prepare('
                INSERT INTO donor_connections
                    (connection_type, blood_donor_id, organ_donor_id, urgent_request_id,
                     donor_name, donor_blood_type, donor_amount_ml, donor_organ, donor_status,
                     recipient_name, recipient_location, recipient_case,
                     lives_saved, impact_label, is_pending, connection_date)
                VALUES
                    (:ct,:bd,:od,:ur,:dn,:dbt,:ml,:dorg,:ds,:rn,:rl,:rc,:ls,:il,:ip,:cd)
            ')->execute([
                ':ct'  => $connType,     ':bd'  => $bloodDonorId, ':od'  => $organDonorId,
                ':ur'  => $urgentReqId,  ':dn'  => $donorName,    ':dbt' => $donorBG,
                ':ml'  => $donorMl,      ':dorg'=> $donorOrgan,   ':ds'  => $donorStatus,
                ':rn'  => $recipientName,':rl'  => $recipientLoc, ':rc'  => $recipientCase,
                ':ls'  => $livesSaved,   ':il'  => $impactLabel,  ':ip'  => $isPending,
                ':cd'  => $connDate,
            ]);

            if ($connType === 'blood' && $bloodDonorId && $livesSaved > 0) {
                $pdo->prepare('
                    UPDATE donation_history
                    SET lives_saved = lives_saved + ?,
                        status = "used",
                        status_label = CONCAT("Used - Saved ", lives_saved + ?, " lives")
                    WHERE blood_donor_id = ? AND donation_type = "blood"
                    ORDER BY id DESC LIMIT 1
                ')->execute([$livesSaved, $livesSaved, $bloodDonorId]);
            }

            if ($urgentReqId) {
                $col = $connType === 'blood' ? 'matched_blood_donor_id' : 'matched_organ_donor_id';
                $val = $connType === 'blood' ? $bloodDonorId : $organDonorId;
                $pdo->prepare("UPDATE urgent_requests SET status='matched', {$col}=? WHERE id=?")
                    ->execute([$val, $urgentReqId]);
            }

            flash('success', 'Connection created successfully! Donor and recipient are now linked.');
            flash('section', 'connections');

        } catch (Exception $e) {
            flash('error', $e->getMessage());
            flash('section', 'connections');
        }

        redirect('impact-dashboard.php');
    }

    // ── Blood Request ───────────────────────────────────────────────
    if ($type === 'blood') {
        try {
            $required = ['patientName','bloodGroup','units','hospital','city','urgency','contact'];
            foreach ($required as $f) {
                if (empty($_POST[$f])) throw new Exception('Please fill in all required fields.');
            }

            $pdo->prepare('
                INSERT INTO urgent_requests
                    (request_type, patient_name, blood_group, units, hospital, city, urgency, contact, details, status)
                VALUES ("blood",:pn,:bg,:u,:h,:c,:urg,:con,:det,"pending")
            ')->execute([
                ':pn'  => trim($_POST['patientName']),
                ':bg'  => $_POST['bloodGroup'],
                ':u'   => (int)$_POST['units'],
                ':h'   => trim($_POST['hospital']),
                ':c'   => trim($_POST['city']),
                ':urg' => $_POST['urgency'],
                ':con' => trim($_POST['contact']),
                ':det' => trim($_POST['details'] ?? ''),
            ]);

            $pdo->prepare('
                INSERT INTO blood_donors (full_name, email, phone, blood_group, city)
                VALUES (:fn, NULL, :ph, :bg, :city)
            ')->execute([
                ':fn'  => trim($_POST['patientName']),
                ':ph'  => trim($_POST['contact']),
                ':bg'  => $_POST['bloodGroup'],
                ':city'=> trim($_POST['city']),
            ]);

            $pdo->prepare('
                INSERT INTO donation_history
                    (donation_type, blood_donor_id, donor_name, blood_type, location, donation_date, lives_saved, status, status_label)
                VALUES ("blood",:did,:dn,:bg,:loc,CURDATE(),0,"pending","Pending")
            ')->execute([
                ':did' => $pdo->lastInsertId(),
                ':dn'  => trim($_POST['patientName']),
                ':bg'  => $_POST['bloodGroup'],
                ':loc' => trim($_POST['hospital']) . ', ' . trim($_POST['city']),
            ]);

            flash('success', 'Blood request submitted successfully! We will match you with a donor soon.');
            flash('section', 'request');
            flash('tab',     'blood');

        } catch (Exception $e) {
            flash('error',   $e->getMessage());
            flash('section', 'request');
            flash('tab',     'blood');
        }

        redirect('impact-dashboard.php');
    }

    // ── Organ Request ───────────────────────────────────────────────
    if ($type === 'organ') {
        try {
            $required = ['patientName','bloodGroup','organ','condition','hospital','city','urgency','contact'];
            foreach ($required as $f) {
                if (empty($_POST[$f])) throw new Exception('Please fill in all required fields.');
            }

            $pdo->prepare('
                INSERT INTO urgent_requests
                    (request_type, patient_name, blood_group, organ, medical_condition,
                     hospital, city, urgency, contact, doctor_name, details, status)
                VALUES ("organ",:pn,:bg,:org,:cond,:h,:c,:urg,:con,:doc,:det,"pending")
            ')->execute([
                ':pn'  => trim($_POST['patientName']),
                ':bg'  => $_POST['bloodGroup'],
                ':org' => $_POST['organ'],
                ':cond'=> trim($_POST['condition']),
                ':h'   => trim($_POST['hospital']),
                ':c'   => trim($_POST['city']),
                ':urg' => $_POST['urgency'],
                ':con' => trim($_POST['contact']),
                ':doc' => trim($_POST['doctor']  ?? ''),
                ':det' => trim($_POST['details'] ?? ''),
            ]);

            $pdo->prepare('
                INSERT INTO organ_donors (full_name, email, phone, blood_group, city, organs)
                VALUES (:fn, NULL, :ph, :bg, :city, :organs)
            ')->execute([
                ':fn'    => trim($_POST['patientName']),
                ':ph'    => trim($_POST['contact']),
                ':bg'    => $_POST['bloodGroup'],
                ':city'  => trim($_POST['city']),
                ':organs'=> $_POST['organ'],
            ]);

            $pdo->prepare('
                INSERT INTO donation_history
                    (donation_type, organ_donor_id, donor_name, blood_type, organ_type, donation_date, lives_saved, status, status_label)
                VALUES ("organ",:did,:dn,:bg,:org,CURDATE(),0,"active_pledge","Pledge Active")
            ')->execute([
                ':did' => $pdo->lastInsertId(),
                ':dn'  => trim($_POST['patientName']),
                ':bg'  => $_POST['bloodGroup'],
                ':org' => $_POST['organ'],
            ]);

            flash('success', 'Organ request submitted! We will match you with a donor soon.');
            flash('section', 'request');
            flash('tab',     'organ');

        } catch (Exception $e) {
            flash('error',   $e->getMessage());
            flash('section', 'request');
            flash('tab',     'organ');
        }

        redirect('impact-dashboard.php');
    }
}

// ── Read flash messages (GET) ────────────────────────────────────────
$successMsg    = getFlash('success');
$errorMsg      = getFlash('error');
$activeSection = getFlash('section') ?: 'impact';
$activeTab     = getFlash('tab')     ?: 'blood';

// ── Data Queries ─────────────────────────────────────────────────────
$bloodCount   = (int)$pdo->query('SELECT COUNT(*) FROM blood_donors')->fetchColumn();
$organCount   = (int)$pdo->query('SELECT COUNT(*) FROM organ_donors')->fetchColumn();
$totalLives   = (int)$pdo->query('SELECT COALESCE(SUM(lives_saved),0) FROM donation_history')->fetchColumn();
$totalBloodMl = (int)$pdo->query('SELECT COALESCE(SUM(amount_ml),0) FROM donation_history WHERE donation_type="blood"')->fetchColumn();

$lastDate = $pdo->query('SELECT MAX(last_donation) FROM blood_donors WHERE last_donation IS NOT NULL')->fetchColumn();
$daysAgo  = $lastDate ? floor((time() - strtotime($lastDate)) / 86400) . ' days ago' : 'N/A';

$eligibleBlood = (int)$pdo->query('SELECT COUNT(*) FROM eligible_blood_donors WHERE is_eligible=1')->fetchColumn();
$eligibleOrgan = (int)$pdo->query('SELECT COUNT(*) FROM eligible_organ_donors WHERE is_eligible=1')->fetchColumn();

$urgentRequests = $pdo->query('SELECT * FROM urgent_requests ORDER BY created_at DESC')->fetchAll();

$connections = $pdo->query('
    SELECT dc.*,
           COALESCE(bd.full_name, od.full_name, dc.donor_name) AS display_donor_name,
           COALESCE(bd.blood_group, od.blood_group, dc.donor_blood_type) AS display_blood_type,
           COALESCE(od.organs, dc.donor_organ) AS display_organ
    FROM donor_connections dc
    LEFT JOIN blood_donors bd ON dc.blood_donor_id = bd.id
    LEFT JOIN organ_donors od ON dc.organ_donor_id = od.id
    ORDER BY dc.connection_date DESC
')->fetchAll();

$allBloodDonors = $pdo->query('SELECT id, full_name, blood_group, city FROM blood_donors ORDER BY full_name ASC')->fetchAll();
$allOrganDonors = $pdo->query('SELECT id, full_name, blood_group, organs, city FROM organ_donors ORDER BY full_name ASC')->fetchAll();
$pendingRequests= $pdo->query('SELECT id, request_type, patient_name, blood_group, hospital, city FROM urgent_requests WHERE status="pending" ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impact Dashboard — LifeShare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/impact-dashboard.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; }

    /* ── Urgent Requests Table ────────────────────────────────── */
    .requests-wrap        { overflow-x: auto; margin-top: 20px; }
    .requests-table       { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
    .requests-table thead { background: linear-gradient(135deg, #e53935, #b71c1c); color: #fff; }
    .requests-table th    { padding: 14px 16px; text-align: left; font-size: .88rem; font-weight: 600; letter-spacing: .3px; white-space: nowrap; }
    .requests-table td    { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; font-size: .9rem; color: #444; vertical-align: middle; }
    .requests-table tr:last-child td { border-bottom: none; }
    .requests-table tr:hover td      { background: #fafafa; }

    /* ── Badges ───────────────────────────────────────────────── */
    .badge          { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600; }
    .badge-critical { background: #ffe0e0; color: #c41e3a; }
    .badge-urgent   { background: #fff3cd; color: #856404; }
    .badge-moderate { background: #d4edda; color: #155724; }
    .badge-pending  { background: #fff3cd; color: #856404; }
    .badge-matched  { background: #d4edda; color: #155724; }
    .badge-fulfilled{ background: #cce5ff; color: #004085; }
    .badge-cancelled{ background: #f8d7da; color: #721c24; }

    /* ── Flash banners ────────────────────────────────────────── */
    .flash         { padding: 14px 20px; border-radius: 10px; margin-bottom: 22px; font-weight: 500; text-align: center; font-size: 1rem; }
    .flash-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .flash-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* ── Empty state ──────────────────────────────────────────── */
    .empty-state      { text-align: center; padding: 48px 20px; color: #aaa; }
    .empty-state .icon{ font-size: 2.4rem; display: block; margin-bottom: 10px; }

    /* ── Quick-connect button ─────────────────────────────────── */
    .btn-quick-connect {
        padding: 4px 14px; border: 1.5px solid #e53935; border-radius: 20px;
        background: #fff; color: #e53935; font-size: .8rem; font-weight: 600;
        cursor: pointer; transition: all .2s; white-space: nowrap;
    }
    .btn-quick-connect:hover { background: #e53935; color: #fff; }

    /* ── Connections header ───────────────────────────────────── */
    .connections-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 14px; margin-bottom: 10px;
    }
    .btn-new-connection {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 22px; background: linear-gradient(135deg, #e53935, #b71c1c);
        color: #fff; border: none; border-radius: 25px; font-size: .95rem;
        font-weight: 700; cursor: pointer;
        box-shadow: 0 4px 14px rgba(229,57,53,.35); transition: all .25s;
    }
    .btn-new-connection:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(229,57,53,.45); }

    /* ── Modal ────────────────────────────────────────────────── */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.55); z-index: 9999;
        align-items: center; justify-content: center;
        padding: 20px; backdrop-filter: blur(3px);
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #fff; border-radius: 18px; width: 100%; max-width: 680px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
        animation: modalSlide .3s ease;
    }
    @keyframes modalSlide {
        from { transform: translateY(28px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .modal-header {
        background: linear-gradient(135deg, #e53935, #b71c1c); color: #fff;
        padding: 22px 28px; border-radius: 18px 18px 0 0;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 1;
    }
    .modal-header h3 { margin: 0; font-size: 1.15rem; font-weight: 700; }
    .modal-close {
        background: rgba(255,255,255,.2); border: none; color: #fff;
        width: 34px; height: 34px; border-radius: 50%; font-size: 1.1rem;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .2s;
    }
    .modal-close:hover { background: rgba(255,255,255,.35); }
    .modal-body        { padding: 28px; }
    .modal-footer {
        padding: 18px 28px; border-top: 1px solid #f0f0f0;
        display: flex; gap: 12px; justify-content: flex-end;
        position: sticky; bottom: 0; background: #fff;
        border-radius: 0 0 18px 18px;
    }

    /* ── Modal form ───────────────────────────────────────────── */
    .modal-body .form-group       { margin-bottom: 18px; }
    .modal-body .form-group label { display: block; font-weight: 600; font-size: .87rem; color: #555; margin-bottom: 6px; }
    .modal-body .form-group input,
    .modal-body .form-group select,
    .modal-body .form-group textarea {
        width: 100%; padding: 10px 14px; border: 1.5px solid #e0e0e0;
        border-radius: 8px; font-size: .95rem; color: #333;
        transition: border-color .2s; box-sizing: border-box; background: #fff;
    }
    .modal-body .form-group input:focus,
    .modal-body .form-group select:focus { outline: none; border-color: #e53935; }
    .modal-body .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .modal-body .hint     { font-size: .77rem; color: #aaa; margin-top: 4px; display: block; }
    @media (max-width: 540px) { .modal-body .form-row { grid-template-columns: 1fr; } }

    /* ── Conn type tabs ───────────────────────────────────────── */
    .conn-type-tabs { display: flex; gap: 10px; margin-bottom: 22px; }
    .conn-type-btn  {
        flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px;
        background: #f9f9f9; font-size: .95rem; font-weight: 600; cursor: pointer;
        transition: all .25s; color: #555; text-align: center;
    }
    .conn-type-btn:hover  { border-color: #e53935; color: #e53935; background: #fff5f5; }
    .conn-type-btn.active {
        background: linear-gradient(135deg, #e53935, #b71c1c);
        border-color: transparent; color: #fff;
    }

    /* ── Donor preview ────────────────────────────────────────── */
    .donor-preview {
        background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px;
        padding: 10px 14px; margin-top: 8px; font-size: .87rem; color: #555;
        display: none;
    }
    .donor-preview.show { display: block; }

    /* ── Arrow visual ─────────────────────────────────────────── */
    .conn-visual {
        display: flex; align-items: center; gap: 12px;
        margin: 18px 0; padding: 14px 18px;
        background: linear-gradient(135deg, #fff5f5, #fff);
        border: 1px dashed #e53935; border-radius: 12px;
    }
    .conn-visual .side        { flex: 1; text-align: center; font-size: .85rem; color: #666; }
    .conn-visual .side strong { display: block; font-size: .95rem; color: #333; margin-bottom: 2px; }
    .conn-visual .arrow-icon  { font-size: 1.6rem; color: #e53935; flex-shrink: 0; }

    /* ── Modal buttons ────────────────────────────────────────── */
    .btn-cancel {
        padding: 10px 24px; border: 2px solid #e0e0e0; border-radius: 8px;
        background: #fff; font-size: .95rem; font-weight: 600; cursor: pointer;
        color: #666; transition: all .2s;
    }
    .btn-cancel:hover { border-color: #999; color: #333; }
    .btn-submit {
        padding: 10px 28px; border: none; border-radius: 8px;
        background: linear-gradient(135deg, #e53935, #b71c1c); color: #fff;
        font-size: .95rem; font-weight: 700; cursor: pointer; transition: opacity .2s;
    }
    .btn-submit:hover { opacity: .9; }

    /* ── Eligibility bar ──────────────────────────────────────── */
    .eligibility-bar {
        background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px;
        padding: 16px 24px; margin-bottom: 30px;
        display: flex; gap: 30px; flex-wrap: wrap; font-size: .95rem;
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════════ -->
<nav class="navbar">
    <div class="container">
        <div class="nav-brand"><h1>❤️ LifeShare</h1></div>
        <ul class="nav-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="blood-donation.php">Blood Donation</a></li>
            <li><a href="organ-donation.php">Organ Donation</a></li>
            <li><a href="search.php">Search Donors</a></li>
            <li><a href="impact-dashboard.php" class="active">Impact Dashboard</a></li>
            <li><a href="about.php">About</a></li>
        </ul>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════════════ -->
<section class="page-header">
    <div class="container">
        <div class="page-header-inner">
            <div class="page-header-icon">📊</div>
            <h1>Impact Dashboard</h1>
            <p>Track donations, view donor-recipient connections, and make urgent requests</p>
            <div class="page-header-stats">
                <div class="header-stat">
                    <span class="header-stat-num"><?= $bloodCount ?></span>
                    <span class="header-stat-label">Blood Donors</span>
                </div>
                <div class="header-stat-divider"></div>
                <div class="header-stat">
                    <span class="header-stat-num"><?= $organCount ?></span>
                    <span class="header-stat-label">Organ Pledges</span>
                </div>
                <div class="header-stat-divider"></div>
                <div class="header-stat">
                    <span class="header-stat-num"><?= $totalLives ?></span>
                    <span class="header-stat-label">Lives Saved</span>
                </div>
                <div class="header-stat-divider"></div>
                <div class="header-stat">
                    <span class="header-stat-num"><?= count($urgentRequests) ?></span>
                    <span class="header-stat-label">Active Requests</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     QUICK ACTIONS
══════════════════════════════════════════════════════════════ -->
<section class="quick-actions">
    <div class="container">
        <div class="action-cards">
            <div class="action-card">
                <div class="action-icon">📊</div>
                <h3>View My Impact</h3>
                <p>See how your donations have saved lives</p>
                <button class="btn btn-primary" onclick="showSection('impact')">View Impact</button>
            </div>
            <div class="action-card">
                <div class="action-icon">🆘</div>
                <h3>Make a Request</h3>
                <p>Request blood or organs urgently</p>
                <button class="btn btn-secondary" onclick="showSection('request')">Make Request</button>
            </div>
            <div class="action-card">
                <div class="action-icon">🔗</div>
                <h3>Donor Connections</h3>
                <p>View donor-recipient relationships</p>
                <button class="btn btn-primary" onclick="showSection('connections')">View Connections</button>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     SECTION: IMPACT
══════════════════════════════════════════════════════════════ -->
<section id="impactSection" class="content-section">
    <div class="container">
        <h2>Your Donation Impact</h2>

        <div class="impact-summary">
            <div class="impact-card">
                <div class="impact-number"><?= $bloodCount + $organCount ?></div>
                <div class="impact-label">Total Donations</div>
                <div class="impact-type"><?= $bloodCount ?> Blood • <?= $organCount ?> Organ Pledges</div>
            </div>
            <div class="impact-card">
                <div class="impact-number"><?= $totalLives ?></div>
                <div class="impact-label">Lives Potentially Saved</div>
                <div class="impact-type">Through your generosity</div>
            </div>
            <div class="impact-card">
                <div class="impact-number"><?= $totalBloodMl ?>ml</div>
                <div class="impact-label">Blood Donated</div>
                <div class="impact-type">Last donation: <?= $daysAgo ?></div>
            </div>
        </div>

        <div class="eligibility-bar">
            <div>✅ <strong><?= $eligibleBlood ?></strong> blood donors currently eligible to donate</div>
            <div>✅ <strong><?= $eligibleOrgan ?></strong> organ donors with active pledges</div>
        </div>

        <!-- All Urgent Requests Table -->
        <div style="margin-top:10px;">
            <h3>📋 All Urgent Requests
                <span style="font-size:1rem;font-weight:400;color:#888;margin-left:8px;">
                    (<?= count($urgentRequests) ?> total)
                </span>
            </h3>

            <?php if (empty($urgentRequests)): ?>
                <div class="empty-state"><span class="icon">📭</span>No urgent requests submitted yet.</div>
            <?php else: ?>
                <div class="requests-wrap">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Type</th><th>Patient</th><th>Blood Group</th>
                                <th>Need</th><th>Hospital</th><th>City</th><th>Contact</th>
                                <th>Urgency</th><th>Status</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($urgentRequests as $r): ?>
                            <tr>
                                <td><?= $r['id'] ?></td>
                                <td><?= $r['request_type'] === 'blood' ? '🩸 Blood' : '🫀 Organ' ?></td>
                                <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($r['blood_group']) ?></td>
                                <td><?= $r['request_type'] === 'blood' ? $r['units'] . ' unit(s)' : htmlspecialchars($r['organ']) ?></td>
                                <td><?= htmlspecialchars($r['hospital']) ?></td>
                                <td><?= htmlspecialchars($r['city']) ?></td>
                                <td><?= htmlspecialchars($r['contact']) ?></td>
                                <td><span class="badge badge-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span></td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <button class="btn-quick-connect" onclick="openConnectionModal(
                                            '<?= $r['request_type'] ?>',
                                            <?= $r['id'] ?>,
                                            '<?= addslashes($r['patient_name']) ?>',
                                            '<?= addslashes($r['hospital'] . ', ' . $r['city']) ?>'
                                        )">🔗 Connect</button>
                                    <?php else: ?>
                                        <span style="color:#bbb;font-size:.82rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     SECTION: REQUEST
══════════════════════════════════════════════════════════════ -->
<section id="requestSection" class="content-section" style="display:none;">
    <div class="container">
        <h2>Make an Urgent Request</h2>
        <p class="section-subtitle">Submit a request for blood or organs. Our team will match you with available donors immediately.</p>

        <?php if ($successMsg && $activeSection === 'request'): ?>
            <div class="flash flash-success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg && $activeSection === 'request'): ?>
            <div class="flash flash-error">❌ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="request-form-container">

            <!-- Blood Request Form -->
            <form id="bloodForm" method="POST" action="impact-dashboard.php"
                  style="display:<?= $activeTab === 'blood' ? 'block' : 'none' ?>;">
                <div class="form-tabs">
                    <button type="button" class="tab-btn <?= $activeTab === 'blood' ? 'active' : '' ?>" onclick="switchForm('blood')">Blood Request</button>
                    <button type="button" class="tab-btn <?= $activeTab === 'organ' ? 'active' : '' ?>" onclick="switchForm('organ')">Organ Request</button>
                </div>
                <input type="hidden" name="submit_type" value="blood">
                <h3>Blood Donation Request</h3>

                <div class="form-group">
                    <label>Patient Name *</label>
                    <input type="text" name="patientName" placeholder="Enter patient's full name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Group Required *</label>
                        <select name="bloodGroup" required>
                            <option value="">Select Blood Group</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Packets Required *</label>
                        <input type="number" name="units" min="1" max="10" placeholder="Number of units" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Hospital Name *</label>
                        <input type="text" name="hospital" placeholder="Hospital name" required>
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" placeholder="City" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Urgency Level *</label>
                    <select name="urgency" required>
                        <option value="">Select Urgency</option>
                        <option value="critical">Critical — Within 24 hours</option>
                        <option value="urgent">Urgent — Within 48 hours</option>
                        <option value="moderate">Moderate — Within a week</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="tel" name="contact" placeholder="+91 9876543210" required>
                </div>
                <div class="form-group">
                    <label>Additional Details</label>
                    <textarea name="details" rows="4" placeholder="Any additional information..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-large">Submit Blood Request</button>
            </form>

            <!-- Organ Request Form -->
            <form id="organForm" method="POST" action="impact-dashboard.php"
                  style="display:<?= $activeTab === 'organ' ? 'block' : 'none' ?>;">
                <div class="form-tabs">
                    <button type="button" class="tab-btn <?= $activeTab === 'blood' ? 'active' : '' ?>" onclick="switchForm('blood')">Blood Request</button>
                    <button type="button" class="tab-btn <?= $activeTab === 'organ' ? 'active' : '' ?>" onclick="switchForm('organ')">Organ Request</button>
                </div>
                <input type="hidden" name="submit_type" value="organ">
                <h3>Organ Transplant Request</h3>

                <div class="form-group">
                    <label>Patient Name *</label>
                    <input type="text" name="patientName" placeholder="Enter patient's full name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Organ Required *</label>
                        <select name="organ" required>
                            <option value="">Select Organ</option>
                            <?php foreach (['kidney','liver','heart','lung','pancreas','cornea'] as $o): ?>
                                <option value="<?= $o ?>"><?= ucfirst($o) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Group *</label>
                        <select name="bloodGroup" required>
                            <option value="">Select Blood Group</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Hospital Name *</label>
                        <input type="text" name="hospital" placeholder="Hospital name" required>
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" placeholder="City" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Medical Condition *</label>
                    <input type="text" name="condition" placeholder="Brief description of condition" required>
                </div>
                <div class="form-group">
                    <label>Urgency Level *</label>
                    <select name="urgency" required>
                        <option value="">Select Urgency</option>
                        <option value="critical">Critical — Immediate</option>
                        <option value="urgent">Urgent — Within a month</option>
                        <option value="moderate">Moderate — Within 6 months</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="tel" name="contact" placeholder="+91 9876543210" required>
                </div>
                <div class="form-group">
                    <label>Doctor's Name</label>
                    <input type="text" name="doctor" placeholder="Attending physician">
                </div>
                <div class="form-group">
                    <label>Additional Medical Information</label>
                    <textarea name="details" rows="4" placeholder="Any additional medical information..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-large">Submit Organ Request</button>
            </form>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     SECTION: CONNECTIONS
══════════════════════════════════════════════════════════════ -->
<section id="connectionsSection" class="content-section" style="display:none;">
    <div class="container">

        <?php if ($successMsg && $activeSection === 'connections'): ?>
            <div class="flash flash-success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg && $activeSection === 'connections'): ?>
            <div class="flash flash-error">❌ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="connections-header">
            <div>
                <h2 style="margin:0 0 6px;">Donor-Recipient Connections</h2>
                <p class="section-subtitle" style="margin:0;">See the connections between donors and recipients</p>
            </div>
            <button class="btn-new-connection" onclick="openConnectionModal()">🤝 Make New Connection</button>
        </div>

        <div class="connection-filters" style="margin-top:16px;">
            <button class="filter-btn active" onclick="filterConnections('all',this)">All</button>
            <button class="filter-btn" onclick="filterConnections('blood',this)">🩸 Blood Donations</button>
            <button class="filter-btn" onclick="filterConnections('organ',this)">🫀 Organ Donations</button>
        </div>

        <div class="connections-grid">
            <?php if (empty($connections)): ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <span class="icon">🔗</span>
                    No connections yet. Click <strong>Make New Connection</strong> to link a donor with a recipient.
                </div>
            <?php else: ?>
                <?php foreach ($connections as $c): ?>
                <div class="connection-card" data-type="<?= $c['connection_type'] ?>">
                    <div class="connection-header">
                        <span class="connection-type <?= $c['connection_type'] ?>">
                            <?= $c['connection_type'] === 'blood' ? '🩸 Blood Donation' : '🫀 Organ Pledge' ?>
                        </span>
                        <span class="connection-date"><?= date('M j, Y', strtotime($c['connection_date'])) ?></span>
                    </div>
                    <div class="connection-body">
                        <div class="connection-donor">
                            <div class="avatar">👤</div>
                            <div class="person-info">
                                <h4><?= htmlspecialchars($c['display_donor_name']) ?> (Donor)</h4>
                                <?php if ($c['connection_type'] === 'blood'): ?>
                                    <p><?= htmlspecialchars($c['display_blood_type']) ?> Blood Type</p>
                                    <p><?= htmlspecialchars($c['donor_status']) ?></p>
                                <?php else: ?>
                                    <p><?= htmlspecialchars($c['display_organ']) ?> Pledge</p>
                                    <p><?= htmlspecialchars($c['donor_status']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="connection-arrow">→</div>
                        <div class="connection-recipient">
                            <div class="avatar"><?= $c['is_pending'] ? '⏳' : '🏥' ?></div>
                            <div class="person-info">
                                <h4><?= htmlspecialchars($c['recipient_name']) ?></h4>
                                <p><?= htmlspecialchars($c['recipient_location']) ?></p>
                                <p><?= htmlspecialchars($c['recipient_case']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="connection-impact">
                        <span class="impact-badge <?= $c['is_pending'] ? 'pending' : '' ?>">
                            <?= htmlspecialchars($c['impact_label']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: MAKE CONNECTION
══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="connectionModal" onclick="handleOverlayClick(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h3>🤝 Make a Donor-Recipient Connection</h3>
            <button class="modal-close" onclick="closeModal()" type="button">✕</button>
        </div>

        <form method="POST" action="impact-dashboard.php">
            <input type="hidden" name="submit_type" value="make_connection">
            <div class="modal-body">

                <!-- Connection Type -->
                <div class="form-group">
                    <label>Connection Category *</label>
                    <div class="conn-type-tabs">
                        <button type="button" class="conn-type-btn active" id="tab_blood" onclick="switchConnType('blood')">🩸 Blood Donation</button>
                        <button type="button" class="conn-type-btn"        id="tab_organ" onclick="switchConnType('organ')">🫀 Organ Pledge</button>
                    </div>
                    <input type="hidden" name="connection_type" id="connection_type_input" value="blood">
                </div>

                <!-- Blood Donor Section -->
                <div id="blood_donor_section">
                    <div class="form-group">
                        <label>Select Blood Donor *</label>
                        <select name="blood_donor_id" id="blood_donor_select" onchange="previewDonor('blood')">
                            <option value="">— Choose a blood donor —</option>
                            <?php foreach ($allBloodDonors as $bd): ?>
                            <option value="<?= $bd['id'] ?>"
                                    data-name="<?= htmlspecialchars($bd['full_name']) ?>"
                                    data-bg="<?= htmlspecialchars($bd['blood_group']) ?>"
                                    data-city="<?= htmlspecialchars($bd['city']) ?>">
                                <?= htmlspecialchars($bd['full_name']) ?> — <?= $bd['blood_group'] ?> (<?= htmlspecialchars($bd['city']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="donor-preview" id="blood_preview"></div>
                    </div>
                    <div class="form-group">
                        <label>Amount Donated (ml)</label>
                        <input type="number" name="donor_amount_ml" min="100" max="1000" placeholder="e.g. 350">
                        <span class="hint">Typical donation: 350–500 ml</span>
                    </div>
                </div>

                <!-- Organ Donor Section -->
                <div id="organ_donor_section" style="display:none;">
                    <div class="form-group">
                        <label>Select Organ Donor *</label>
                        <select name="organ_donor_id" id="organ_donor_select" onchange="previewDonor('organ')">
                            <option value="">— Choose an organ donor —</option>
                            <?php foreach ($allOrganDonors as $od): ?>
                            <option value="<?= $od['id'] ?>"
                                    data-name="<?= htmlspecialchars($od['full_name']) ?>"
                                    data-bg="<?= htmlspecialchars($od['blood_group']) ?>"
                                    data-organs="<?= htmlspecialchars($od['organs']) ?>"
                                    data-city="<?= htmlspecialchars($od['city']) ?>">
                                <?= htmlspecialchars($od['full_name']) ?> — <?= htmlspecialchars($od['organs']) ?> (<?= htmlspecialchars($od['city']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="donor-preview" id="organ_preview"></div>
                    </div>
                </div>

                <!-- Link to Urgent Request -->
                <div class="form-group">
                    <label>Link to Urgent Request <span style="color:#aaa;font-weight:400;">(optional)</span></label>
                    <select name="urgent_request_id" id="urgent_req_select">
                        <option value="">— None (manual connection) —</option>
                        <?php foreach ($pendingRequests as $pr): ?>
                        <option value="<?= $pr['id'] ?>"
                                data-type="<?= $pr['request_type'] ?>"
                                data-name="<?= htmlspecialchars($pr['patient_name']) ?>"
                                data-loc="<?= htmlspecialchars($pr['hospital'] . ', ' . $pr['city']) ?>">
                            #<?= $pr['id'] ?> — <?= ucfirst($pr['request_type']) ?> — <?= htmlspecialchars($pr['patient_name']) ?> (<?= htmlspecialchars($pr['city']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Linking will automatically mark the urgent request as "matched"</span>
                </div>

                <!-- Visual connector -->
                <div class="conn-visual">
                    <div class="side"><strong id="visual_donor">Select a donor</strong><span>Donor</span></div>
                    <div class="arrow-icon">→</div>
                    <div class="side"><strong id="visual_recipient">Enter recipient below</strong><span>Recipient / Patient</span></div>
                </div>

                <!-- Recipient Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Recipient / Patient Name *</label>
                        <input type="text" name="recipient_name" id="recipient_name_input"
                               placeholder="Full name" required
                               oninput="document.getElementById('visual_recipient').textContent=this.value||'Enter recipient below'">
                    </div>
                    <div class="form-group">
                        <label>Recipient Location</label>
                        <input type="text" name="recipient_location" id="recipient_loc_input" placeholder="Hospital, City">
                    </div>
                </div>
                <div class="form-group">
                    <label>Recipient's Case / Condition</label>
                    <input type="text" name="recipient_case" placeholder="e.g. Surgery patient, Trauma case">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lives Saved</label>
                        <input type="number" name="lives_saved" min="0" max="20" value="1">
                    </div>
                    <div class="form-group">
                        <label>Connection Date</label>
                        <input type="date" name="connection_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Impact Label <span style="color:#aaa;font-weight:400;">(auto-generated if blank)</span></label>
                        <input type="text" name="impact_label" placeholder="e.g. 🌟 Saved 1 life">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:26px;">
                        <input type="checkbox" name="is_pending" id="is_pending_check" style="width:18px;height:18px;cursor:pointer;">
                        <label for="is_pending_check" style="margin:0;font-weight:500;cursor:pointer;font-size:.9rem;">
                            Mark as Pending / Pledge
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">🔗 Create Connection</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2024 LifeShare. All rights reserved. | Saving lives through donation</p>
    </div>
</footer>

<!-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ -->
<script>
// ── Section visibility ──────────────────────────────────────────
function showSection(name) {
    ['impact','request','connections'].forEach(id => {
        document.getElementById(id + 'Section').style.display = (id === name) ? 'block' : 'none';
    });
}

// ── Request form tab switch ─────────────────────────────────────
function switchForm(type) {
    document.getElementById('bloodForm').style.display = type === 'blood' ? 'block' : 'none';
    document.getElementById('organForm').style.display = type === 'organ' ? 'block' : 'none';
}

// ── Connection filter ───────────────────────────────────────────
function filterConnections(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.querySelectorAll('.connection-card').forEach(card => {
        card.style.display = (type === 'all' || card.dataset.type === type) ? '' : 'none';
    });
}

// ── Modal ───────────────────────────────────────────────────────
function openConnectionModal(connType, reqId, recipientName, recipientLoc) {
    document.getElementById('connectionModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    showSection('connections');

    if (connType)      switchConnType(connType);
    if (reqId)         document.getElementById('urgent_req_select').value = reqId;
    if (recipientName) {
        document.getElementById('recipient_name_input').value = recipientName;
        document.getElementById('visual_recipient').textContent = recipientName;
    }
    if (recipientLoc)  document.getElementById('recipient_loc_input').value = recipientLoc;
}

function closeModal() {
    document.getElementById('connectionModal').classList.remove('active');
    document.body.style.overflow = '';
}

function handleOverlayClick(e) {
    if (e.target.id === 'connectionModal') closeModal();
}

// ── Connection type switch ──────────────────────────────────────
function switchConnType(type) {
    document.getElementById('connection_type_input').value = type;
    document.getElementById('tab_blood').classList.toggle('active', type === 'blood');
    document.getElementById('tab_organ').classList.toggle('active', type === 'organ');
    document.getElementById('blood_donor_section').style.display = type === 'blood' ? 'block' : 'none';
    document.getElementById('organ_donor_section').style.display = type === 'organ' ? 'block' : 'none';
    document.getElementById('blood_preview').classList.remove('show');
    document.getElementById('organ_preview').classList.remove('show');
    document.getElementById('visual_donor').textContent = 'Select a donor';
}

// ── Donor preview ───────────────────────────────────────────────
function previewDonor(type) {
    const isBlood = type === 'blood';
    const sel     = document.getElementById(isBlood ? 'blood_donor_select' : 'organ_donor_select');
    const preview = document.getElementById(isBlood ? 'blood_preview'      : 'organ_preview');
    const opt     = sel.options[sel.selectedIndex];

    if (sel.value) {
        preview.innerHTML = isBlood
            ? `<strong>${opt.dataset.name}</strong> &nbsp;|&nbsp; 🩸 ${opt.dataset.bg} &nbsp;|&nbsp; 📍 ${opt.dataset.city}`
            : `<strong>${opt.dataset.name}</strong> &nbsp;|&nbsp; 🫀 ${opt.dataset.organs} &nbsp;|&nbsp; 🩸 ${opt.dataset.bg} &nbsp;|&nbsp; 📍 ${opt.dataset.city}`;
        preview.classList.add('show');
        document.getElementById('visual_donor').textContent = opt.dataset.name;
    } else {
        preview.classList.remove('show');
        document.getElementById('visual_donor').textContent = 'Select a donor';
    }
}

// ── Auto-fill from urgent request select ───────────────────────
document.getElementById('urgent_req_select').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (!this.value || !opt.dataset.name) return;

    document.getElementById('recipient_name_input').value = opt.dataset.name;
    document.getElementById('visual_recipient').textContent = opt.dataset.name;
    if (opt.dataset.loc)  document.getElementById('recipient_loc_input').value = opt.dataset.loc;
    if (opt.dataset.type) switchConnType(opt.dataset.type);
});

// ── Auto-show correct section on page load (after PRG redirect) ─
(function () {
    const section = <?= json_encode($activeSection) ?>;
    showSection(section);

    <?php if ($activeTab === 'organ'): ?>
    switchForm('organ');
    <?php endif; ?>
})();
</script>

<script src="js/impact-dashboard.js"></script>
<script src="js/chatbot.js"></script>
</body>
</html>