<?php
session_start();
require_once 'db.php';

function set_flash($msg, $type = 'error') {
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            set_flash('Invalid input. Name must be at least 2 chars, valid email, password at least 6 chars.');
            header('Location: ../public/register.php');
            exit();
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        if (!$stmt) {
            set_flash('Database error.');
            header('Location: ../public/register.php');
            exit();
        }
        $stmt->bind_param('sss', $name, $email, $hash);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_admin'] = 0;
            set_flash('Registration successful!', 'success');
            header('Location: ../public/dashboard.php');
            exit();
        } else {
            set_flash('Registration failed: Email may already be registered.');
            header('Location: ../public/register.php');
            exit();
        }
    }
    // Login
    elseif (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            set_flash('Database error.');
            header('Location: ../public/login.php');
            exit();
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if (isset($user['status']) && $user['status'] === 'blocked') {
                    // If blocked, set a message and stop the login
                    set_flash('Your account has been suspended. Please contact support.');
                    header('Location: ../public/login.php');
                    exit();
                }
                // Login successful
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                set_flash('Login successful!', 'success');
                header('Location: ../public/dashboard.php');
                exit();
            }
             else {
                set_flash('Invalid password.');
                header('Location: ../public/login.php');
                exit();
            }
        } else {
            set_flash('User not found.');
            header('Location: ../public/login.php');
            exit();
        }
    }
}
?> 