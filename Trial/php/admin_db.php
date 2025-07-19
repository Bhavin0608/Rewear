<?php
// =================================================================
// ReWear: Admin-Specific Database Connection File
// Location: /php/admin_db.php
// Purpose: This file is intended to be used ONLY by the admin dashboard.
// In a production environment, you would create a separate database user
// with full privileges and use those credentials here for enhanced security.
// =================================================================

// --- Admin Database Credentials ---
// For local development, these can be the same as your main db.php file.
$host = 'localhost';
$dbname = 'rewear';       // The name of your database
$user = 'root';         // Your database username (e.g., 'rewear_admin')
$password = '';         // The password for the admin database user
$charset = 'utf8mb4';

// --- Data Source Name (DSN) ---
// This string tells PDO how to connect.
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// --- PDO Connection Options ---
// These options are recommended for security and error handling.
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

// --- Create the PDO instance ---
// This is the core of the connection. It creates the $pdo object.
try {
    // The $pdo variable is created here.
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // If the connection fails, stop the script and show a clear error.
    die('Admin database connection failed: ' . $e->getMessage());
}

// The $pdo variable is now created and ready for use in the admin panel.
?>
