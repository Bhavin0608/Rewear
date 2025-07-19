<?php
session_start();
require_once 'db.php';

// 1. SECURITY & VALIDATION
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['item_id']) || !is_numeric($_POST['item_id'])) {
    header('Location: ../public/index.php');
    exit;
}

$requester_id = $_SESSION['user_id'];
$item_id = (int)$_POST['item_id'];
$action = $_POST['action']; // 'redeem_points' or 'request_swap'

// 2. FETCH ITEM DATA
$stmt = $conn->prepare("SELECT user_id as owner_id, points, status FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

// 3. LOGIC CHECKS
if (!$item || $item['status'] !== 'available') {
    $_SESSION['flash'] = "Sorry, this item is no longer available.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/item_detail.php?id=$item_id");
    exit;
}

if ($item['owner_id'] == $requester_id) {
    $_SESSION['flash'] = "You cannot swap for your own item.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/item_detail.php?id=$item_id");
    exit;
}

//==============================================
// HANDLE "REDEEM WITH POINTS" ACTION
//==============================================
if ($action === 'redeem_points') {
    $conn->begin_transaction();
    try {
        $stmt_user = $conn->prepare("SELECT points FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $requester_id);
        $stmt_user->execute();
        $requester = $stmt_user->get_result()->fetch_assoc();

        if ($requester['points'] < $item['points']) {
            throw new Exception("You do not have enough points to get this item.");
        }

        $owner_id = $item['owner_id'];
        $points_cost = $item['points'];

        // a) Deduct points from requester
        $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?")->execute([$points_cost, $requester_id]);
        // b) Add points to owner
        $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?")->execute([$points_cost, $owner_id]);
        // c) Mark the item as swapped
        $conn->prepare("UPDATE items SET status = 'swapped' WHERE id = ?")->execute([$item_id]);
        // d) Log the transaction
        $stmt_log = $conn->prepare("INSERT INTO swaps (item_id, requester_id, owner_id, status, type) VALUES (?, ?, ?, 'completed', 'points')");
        $stmt_log->bind_param("iii", $item_id, $requester_id, $owner_id);
        $stmt_log->execute();

        $conn->commit();
        $_SESSION['flash'] = "Swap successful! The item is yours.";
        $_SESSION['flash_type'] = "success";
        header('Location: ../public/dashboard.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash'] = $e->getMessage();
        $_SESSION['flash_type'] = "error";
        header("Location: ../public/item_detail.php?id=$item_id");
        exit;
    }
}

//==============================================
// HANDLE "REQUEST DIRECT SWAP" ACTION
//==============================================
elseif ($action === 'request_swap') {
    if (!isset($_POST['offered_item_id']) || !is_numeric($_POST['offered_item_id'])) {
        $_SESSION['flash'] = "You must select one of your items to offer.";
        $_SESSION['flash_type'] = "error";
        header("Location: ../public/item_detail.php?id=$item_id");
        exit;
    }
    $offered_item_id = (int)$_POST['offered_item_id'];
    $owner_id = $item['owner_id'];

    // Log the pending swap request
    $stmt = $conn->prepare("INSERT INTO swaps (item_id, requester_id, owner_id, offered_item_id, status, type) VALUES (?, ?, ?, ?, 'pending', 'direct')");
    $stmt->bind_param("iiii", $item_id, $requester_id, $owner_id, $offered_item_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash'] = "Swap request sent successfully! The owner will be notified.";
        $_SESSION['flash_type'] = "success";
        header('Location: ../public/dashboard.php');
        exit;
    } else {
        $_SESSION['flash'] = "Error sending swap request.";
        $_SESSION['flash_type'] = "error";
        header("Location: ../public/item_detail.php?id=$item_id");
        exit;
    }
}

// Fallback for unknown action
header("Location: ../public/item_detail.php?id=$item_id");
exit;
?>