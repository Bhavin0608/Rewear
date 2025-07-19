<?php
// update_item.php - Alternative handler if you want to separate the update logic
require_once 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_id = intval($_POST['item_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $size = $_POST['size'];
    $condition = $_POST['condition'];
    
    // Validate input
    if (empty($title) || empty($description) || empty($category) || empty($size) || empty($condition)) {
        header('Location: edit_item.php?id=' . $item_id . '&error=missing_fields');
        exit;
    }
    
    // Verify that the item belongs to the current user
    $stmt = $conn->prepare("SELECT image FROM items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../public/dashboard.php?error=unauthorized');
        exit;
    }
    
    $item = $result->fetch_assoc();
    $stmt->close();
    
    // Handle image upload if new image is provided
    $image_name = $item['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image_name = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/' . $new_image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists
                if ($item['image'] && file_exists('../uploads/' . $item['image'])) {
                    unlink('../uploads/' . $item['image']);
                }
                $image_name = $new_image_name;
            } else {
                header('Location: edit_item.php?id=' . $item_id . '&error=upload_failed');
                exit;
            }
        } else {
            header('Location: edit_item.php?id=' . $item_id . '&error=invalid_file_type');
            exit;
        }
    }
    
    // Update item in database
    $stmt = $conn->prepare("UPDATE items SET title = ?, description = ?, category = ?, size = ?, condition_item = ?, image = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssssssii", $title, $description, $category, $size, $condition, $image_name, $item_id, $user_id);
    
    if ($stmt->execute()) {
        header('Location: ../public/dashboard.php?success=item_updated');
    } else {
        header('Location: edit_item.php?id=' . $item_id . '&error=update_failed');
    }
    
    $stmt->close();
} else {
    header('Location: ../public/dashboard.php');
}

$conn->close();
?>