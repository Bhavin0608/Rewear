<?php
session_start();
require_once 'db.php';

function set_flash($msg, $type = 'error') {
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    set_flash('Access denied.');
    header('Location: ../public/admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    if (isset($_POST['approve'])) {
        $stmt = $conn->prepare("UPDATE items SET status = 'available' WHERE id = ?");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        set_flash('Item approved!', 'success');
    } elseif (isset($_POST['reject'])) {
        $stmt = $conn->prepare("UPDATE items SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        set_flash('Item rejected.', 'success');
    } elseif (isset($_POST['remove'])) {
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        set_flash('Item removed.', 'success');
    }
    header('Location: ../public/admin.php');
    exit();
}
?> 