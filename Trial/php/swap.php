<?php
session_start();
require_once 'db.php';

function set_flash($msg, $type = 'error') {
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $item_id = intval($_POST['item_id']);
    $redeem = isset($_POST['redeem']) ? 1 : 0;

    // Get item owner
    $stmt = $conn->prepare("SELECT user_id FROM items WHERE id = ?");
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $item = $result->fetch_assoc();
        $owner_id = $item['user_id'];
        if ($redeem) {
            // Redeem via points (deduct points, mark as swapped)
            $stmt1 = $conn->prepare("UPDATE users SET points = points - 50 WHERE id = ?");
            $stmt1->bind_param('i', $user_id);
            $stmt1->execute();
            $stmt2 = $conn->prepare("UPDATE items SET status = 'swapped' WHERE id = ?");
            $stmt2->bind_param('i', $item_id);
            $stmt2->execute();
            $stmt3 = $conn->prepare("INSERT INTO swaps (item_id, requester_id, owner_id, status) VALUES (?, ?, ?, 'completed')");
            $stmt3->bind_param('iii', $item_id, $user_id, $owner_id);
            $stmt3->execute();
            set_flash('Item redeemed via points!', 'success');
        } else {
            // Swap request (pending)
            $stmt1 = $conn->prepare("INSERT INTO swaps (item_id, requester_id, owner_id, status) VALUES (?, ?, ?, 'pending')");
            $stmt1->bind_param('iii', $item_id, $user_id, $owner_id);
            $stmt1->execute();
            $stmt2 = $conn->prepare("UPDATE items SET status = 'pending' WHERE id = ?");
            $stmt2->bind_param('i', $item_id);
            $stmt2->execute();
            set_flash('Swap request sent!', 'success');
        }
        header('Location: ../public/dashboard.php');
        exit();
    } else {
        set_flash('Item not found.');
        header('Location: ../public/dashboard.php');
        exit();
    }
}
?> 