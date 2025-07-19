<?php
// delete_item.php
require_once 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $user_id = $_SESSION['user_id'];
    
    // First, verify that the item belongs to the current user
    $stmt = $conn->prepare("SELECT image FROM items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Item doesn't exist or doesn't belong to user
        header('Location: ../public/dashboard.php?error=unauthorized');
        exit;
    }
    
    $item = $result->fetch_assoc();
    $stmt->close();
    
    // Check if item is involved in any pending swaps
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM swaps WHERE (item_id = ? OR offered_item_id = ?) AND status = 'pending'");
    $stmt->bind_param("ii", $item_id, $item_id);
    $stmt->execute();
    $swap_check = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($swap_check['count'] > 0) {
        // Item has pending swaps, cannot delete
        header('Location: ../public/dashboard.php?error=pending_swaps');
        exit;
    }
    
    // Delete the item from database
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    
    if ($stmt->execute()) {
        // If database deletion successful, try to delete the image file
        if ($item['image'] && file_exists('../uploads/' . $item['image'])) {
            unlink('../uploads/' . $item['image']);
        }
        
        header('Location: ../public/dashboard.php?success=item_deleted');
    } else {
        header('Location: ../public/dashboard.php?error=delete_failed');
    }
    
    $stmt->close();
} else {
    header('Location: ../public/dashboard.php');
}

$conn->close();
?>