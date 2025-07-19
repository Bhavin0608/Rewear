<?php
// --- PHP LOGIC FIRST ---

require_once '../php/db.php';

// Start the session at the very top of the script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ReWear</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --primary-color: #43cea2;
            --primary-glow: rgba(67, 206, 162, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
        font-family: 'Poppins', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        height: 100vh;
        
        /* NEW MESH GRADIENT BACKGROUND */
        background-color: #0c1e35; /* Dark navy base color */
        background-image: 
            radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.4) 0%, transparent 40%),
            radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.5) 0%, transparent 40%),
            radial-gradient(circle at 90% 20%, rgba(142, 68, 173, 0.4) 0%, transparent 35%);
        background-size: 100% 100%;
        background-repeat: no-repeat;
        }
        
        /* Internal Header Styling */
        .page-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 2rem;
        }
        .page-header .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        /* The "Glassmorphism" Card */
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2.5rem 3rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
            color: white;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .login-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .login-card .subtitle {
            font-size: 1rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }
        
        /* Floating Label Form Styles */
        .login-form .form-group {
            position: relative;
            margin-bottom: 2rem;
        }

        .login-form .form-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
            transition: color 0.3s;
        }
        
        .login-form .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: background 0.3s, border-color 0.3s;
        }

        .login-form .form-control::placeholder {
            color: transparent;
        }

        .login-form .form-label {
            position: absolute;
            top: 1rem;
            left: 3rem;
            color: rgba(255, 255, 255, 0.7);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .login-form .form-control:focus + .form-label,
        .login-form .form-control:not(:placeholder-shown) + .form-label {
            top: -10px;
            left: 2.5rem;
            font-size: 0.8rem;
            background: #1e415a; /* Darker color from background for contrast */
            padding: 0 5px;
            border-radius: 4px;
        }

        .login-form .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--primary-color);
            box-shadow: 0 0 15px var(--primary-glow);
        }
        
        .login-form .form-control:focus ~ .form-icon {
            color: var(--primary-color);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            color: white;
            background: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px var(--primary-glow);
        }
        
        .login-card .signup-link {
            margin-top: 2rem;
            font-size: 0.9rem;
        }
        .login-card .signup-link a {
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color 0.3s, border-color 0.3s;
        }
        .login-card .signup-link a:hover {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        /* Internal Footer Styling */
        .page-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

    </style>
</head>
<body>

    <header class="page-header">
        <a href="index.php" class="logo">ReWear</a>
    </header>

    <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to the ReWear community.</p>
        
        <?php
        // Show flash messages if they exist.
        if (isset($_SESSION['flash'])) {
            // We'll just use a generic style for flash messages here
            echo '<p style="background: #ffcdd2; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">' . htmlspecialchars($_SESSION['flash']) . '</p>';
            unset($_SESSION['flash'], $_SESSION['flash_type']);
        }
        ?>
        
        <form action="../php/auth.php" method="POST" class="login-form">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <i class="fa-solid fa-envelope form-icon"></i>
                <input type="email" id="email" name="email" class="form-control" placeholder=" " required>
                <label for="email" class="form-label">Email</label>
            </div>
            
            <div class="form-group">
                <i class="fa-solid fa-lock form-icon"></i>
                <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                <label for="password" class="form-label">Password</label>
            </div>
            
            <button type="submit" class="login-btn">Login Securely</button>
        </form>
        
        <p class="signup-link">Don't have an account? <a href="register.php">Sign up</a></p>
    </div>

    <footer class="page-footer">
        &copy; <?php echo date("Y"); ?> ReWear. All rights reserved.
    </footer>

</body>
</html>