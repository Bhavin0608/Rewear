<?php
// php/upload.php

// STEP 0: ENABLE DETAILED ERROR REPORTING (Crucial for debugging)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php'; // Use a more reliable path to the DB connection

//=========================================================
// 1. PRELIMINARY CHECKS
//=========================================================

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If not, redirect to the homepage
    header('Location: ../public/index.php');
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "You must be logged in to list an item.";
    $_SESSION['flash_type'] = "error";
    header('Location: ../public/login.php');
    exit;
}

//=========================================================
// 2. FILE UPLOAD HANDLING & VALIDATION
//=========================================================

// Check if image file is present and was uploaded without errors
if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
    // Handle different upload errors with specific messages
    $error_message = 'An unknown error occurred with the file upload.';
    if (isset($_FILES["image"]["error"])) {
        switch ($_FILES["image"]["error"]) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = 'The file is larger than the server allows (php.ini).';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'The file is larger than the form allows.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was selected for upload.';
                break;
        }
    }
    $_SESSION['flash'] = $error_message;
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

$target_dir = dirname(__DIR__) . "/uploads/"; // Robust path to the uploads folder

// Check if the /uploads/ directory exists and is writable
if (!is_dir($target_dir) || !is_writable($target_dir)) {
    $_SESSION['flash'] = "Server configuration error: The uploads directory does not exist or is not writable.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

// Validate file size (max 5MB)
if ($_FILES["image"]["size"] > 5000000) {
    $_SESSION['flash'] = "Sorry, your file is too large. Maximum size is 5MB.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

// Validate file type using MIME type for better security
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($_FILES['image']['tmp_name']);
$allowed_mime_types = ['image/jpeg', 'image/png'];

if (!in_array($mime_type, $allowed_mime_types)) {
    $_SESSION['flash'] = "Sorry, only JPG and PNG files are allowed.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

// Create a unique filename to prevent overwriting
$image_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
$unique_image_name = uniqid('item_', true) . '.' . $image_extension;
$target_file = $target_dir . $unique_image_name;

// Try to move the uploaded file
if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    $_SESSION['flash'] = "Sorry, there was a critical error uploading your file.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

//=========================================================
// 3. DATABASE INSERTION
//=========================================================

// All file checks passed, now prepare data for the database
$user_id = $_SESSION['user_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$category = $_POST['category'];
$type = trim($_POST['type']);
$condition = $_POST['condition'];
$size = trim($_POST['size']);
$points = (int)$_POST['points'];
$tags = trim($_POST['tags']);

// The SQL query must exactly match your database structure
$sql = "INSERT INTO items 
        (user_id, title, description, category, `type`, `condition`, size, points, tags, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// Check if the SQL statement was prepared correctly
if ($stmt === false) {
    // This error means your SQL query is wrong or columns don't match the DB
    $_SESSION['flash'] = "Database error: Could not prepare the statement. Check table/column names.";
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

// Bind the parameters. The type string "issssssiss" must match the data types.
// i = integer, s = string
$stmt->bind_param("issssssiss", $user_id, $title, $description, $category, $type, $condition, $size, $points, $tags, $unique_image_name);

// Execute the statement and check for success
if ($stmt->execute()) {
    // Success!
    $_SESSION['flash'] = "Your item has been listed successfully!";
    $_SESSION['flash_type'] = "success";
    header("Location: ../public/dashboard.php");
    exit;
} else {
    // This error means the data could not be inserted
    $_SESSION['flash'] = "Database execution error: " . $stmt->error;
    $_SESSION['flash_type'] = "error";
    header("Location: ../public/add_item.php");
    exit;
}

$stmt->close();
$conn->close();

?>