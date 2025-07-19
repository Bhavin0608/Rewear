<?php
session_start();
require_once 'db.php';

// Security: User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

// Security: Check if form data is sent
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['swap_id']) || !isset($_POST['action'])) {
    header('Location: ../public/dashboard.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$swap_id = (int)$_POST['swap_id'];
$action = $_POST['action']; // 'accept' or 'reject'

// --- Fetch the swap to ensure the current user is the owner and it's pending ---
$stmt = $conn->prepare("SELECT * FROM swaps WHERE id = ? AND owner_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $swap_id, $current_user_id);
$stmt->execute();
$swap = $stmt->get_result()->fetch_assoc();

if (!$swap) {
    $_SESSION['flash'] = "Swap request not found or you don't have permission to modify it.";
    $_SESSION['flash_type'] = "error";
    header('Location: ../public/dashboard.php');
    exit;
}

// --- Process the Action ---
if ($action === 'reject') {
    // Simply update the swap status to 'rejected'
    $stmt_reject = $conn->prepare("UPDATE swaps SET status = 'rejected' WHERE id = ?");
    $stmt_reject->bind_param("i", $swap_id);
    $stmt_reject->execute();

    $_SESSION['flash'] = "Swap request has been rejected.";
    $_SESSION['flash_type'] = "success";
    header('Location: ../public/dashboard.php');
    exit;
} 
elseif ($action === 'accept') {
    // This is a transaction: all steps must succeed or none will.
    $conn->begin_transaction();
    try {
        $requested_item_id = $swap['item_id'];
        $offered_item_id = $swap['offered_item_id'];

        // 1. Mark the swap as 'completed'
        $conn->prepare("UPDATE swaps SET status = 'completed' WHERE id = ?")->execute([$swap_id]);

        // 2. Mark BOTH items as 'swapped' so they can't be offered again
        $conn->prepare("UPDATE items SET status = 'swapped' WHERE id = ? OR id = ?")->execute([$requested_item_id, $offered_item_id]);

        // (Optional) Reject any other pending offers for these two items, since they are now swapped.
        $conn->prepare("UPDATE swaps SET status = 'rejected' WHERE (item_id = ? OR offered_item_id = ?) AND status = 'pending'")->execute([$requested_item_id, $requested_item_id]);
        $conn->prepare("UPDATE swaps SET status = 'rejected' WHERE (item_id = ? OR offered_item_id = ?) AND status = 'pending'")->execute([$offered_item_id, $offered_item_id]);

        // If all steps succeed, commit the transaction
        $conn->commit();
        $_SESSION['flash'] = "Swap accepted! The items have been exchanged.";
        $_SESSION['flash_type'] = "success";
        header('Location: ../public/dashboard.php');
        exit;

    } catch (Exception $e) {
        // If anything fails, undo all database changes
        $conn->rollback();
        $_SESSION['flash'] = "An error occurred while accepting the swap. Please try again.";
        $_SESSION['flash_type'] = "error";
        header('Location: ../public/dashboard.php');
        exit;
    }
}
?>