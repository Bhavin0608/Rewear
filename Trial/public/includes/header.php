<?php
// Always start the session to check for a logged-in user
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReWear – Community Clothing Exchange</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* ===== Global & Layout ===== */
        :root {
            --primary-color: #43cea2;
            --secondary-color: #185a9d;
            --accent-color: #e91e63;
            --error-color: #c62828;
            --success-color: #388e3c;
            --light-bg: #f7f9fc;
            --text-dark: #333;
            --text-light: #666;
            --white: #ffffff;
            --border-radius-md: 15px;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main { flex-grow: 1; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .card { background: var(--white); border-radius: var(--border-radius-md); padding: 1.5rem; box-shadow: var(--shadow); }
        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            font-size: 1rem;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
        footer { text-align: center; padding: 2rem 1rem; background-color: var(--white); color: var(--text-light); margin-top: auto; }

        /* ===== Header & Navigation ===== */
        .main-header {
            padding: 1rem 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .logo { font-size: 1.8rem; font-weight: 700; color: var(--secondary-color); text-decoration: none; }
        .main-nav { display: flex; align-items: center; gap: 1.5rem; }
        .main-nav a { font-weight: 600; color: var(--text-dark); text-decoration: none; display: flex; align-items: center; }
        .main-nav a i { margin-right: 8px; width: 20px; text-align: center; }
        .main-nav a.btn-logout { color: var(--accent-color); }
        .user-points-display {
            display: flex;
            align-items: center;
            background-color: #e8f5e9;
            color: var(--success-color);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .user-points-display i { color: var(--primary-color); margin-right: 6px; }

        /* --- Add all your other CSS rules for dashboard, forms, etc. here --- */

    </style>
</head>
<body>

    <header class="main-header">
        <div class="nav-container">
            <a href="index.php" class="logo">ReWear</a>
            <nav class="main-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                        // User is logged in, so fetch their points right here.
                        require_once __DIR__ . '/../../php/db.php';
                        $user_id_nav = $_SESSION['user_id'];
                        $stmt_nav = $conn->prepare("SELECT points FROM users WHERE id = ?");
                        $stmt_nav->bind_param("i", $user_id_nav);
                        $stmt_nav->execute();
                        $result_nav = $stmt_nav->get_result()->fetch_assoc();
                        $user_points_nav = $result_nav['points'] ?? 0; // Safely get points
                        $stmt_nav->close();
                    ?>
                    <div class="user-points-display">
                        <i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($user_points_nav); ?> Points
                    </div>
                    <a href="dashboard.php">
                        <i class="fa-solid fa-table-columns"></i> Dashboard
                    </a>
                    <a href="logout.php" class="btn-logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>