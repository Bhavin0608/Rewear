<?php
// --- PHP LOGIC AT THE VERY TOP ---

require_once '../php/db.php'; // Your database connection

// Start the session to check login status
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_data = null; // Default to null

// If the user is logged in, fetch their data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT name, points FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch the 8 most recently added items that have been APPROVED for swapping
$recent_items_sql = "SELECT * FROM items WHERE status = 'approved' ORDER BY created_at DESC LIMIT 8";
$recent_items = $conn->query($recent_items_sql);

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
        :root {
            --primary-color: #43cea2;
            --secondary-color: #185a9d;
            --accent-color: #e91e63;
            --primary-glow: rgba(67, 206, 162, 0.5);
            --white-glass: rgba(255, 255, 255, 0.1);
            --white-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #fff;
            
            /* Modern Mesh Gradient Background */
            background-color: #0c1e35;
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.5) 0%, transparent 40%),
                radial-gradient(circle at 90% 20%, rgba(142, 68, 173, 0.4) 0%, transparent 35%);
            background-size: 100% 100%;
            background-attachment: fixed;
        }
        
        /* Glassmorphism Header */
        .page-header {
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(12, 30, 53, 0.5); /* Darker glass for header */
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--white-border);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }

        .main-nav { display: flex; align-items: center; gap: 1.5rem; }
        .main-nav a { font-weight: 600; color: #fff; text-decoration: none; display: flex; align-items: center; opacity: 0.9; transition: opacity 0.3s; }
        .main-nav a:hover { opacity: 1; }
        .main-nav a i { margin-right: 8px; }
        .main-nav a.btn { background: var(--primary-color); padding: 0.6rem 1.2rem; border-radius: 50px; }
        .main-nav a.btn:hover { transform: scale(1.05); }

        .user-points-display {
            display: flex;
            align-items: center;
            background-color: var(--white-glass);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .user-points-display i { color: var(--primary-color); margin-right: 6px; }

        /* Main Content Layout */
        .page-content {
            padding: 3rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 3rem;
        }

        /* Glassmorphism Section Card */
        .section-glass {
            background: var(--white-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--white-border);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .section-header h2 { font-size: 2.5rem; margin-bottom: 0.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .section-header p { font-size: 1.1rem; opacity: 0.8; max-width: 600px; margin: 0 auto; }
        
        /* Hero Section Redesign */
        .hero-content {
            text-align: center;
        }
        .hero-content h1 { font-size: 4rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .hero-content .subtitle { font-size: 1.4rem; max-width: 700px; margin: 1rem auto 2rem auto; opacity: 0.9; }
        .hero-buttons { display: flex; justify-content: center; gap: 1.5rem; }
        .hero-buttons .btn { font-size: 1rem; padding: 1rem 2.5rem; }
        .hero-buttons .btn-primary { background: var(--primary-color); }
        .hero-buttons .btn-secondary { background: var(--white-glass); border: 1px solid var(--white-border); }
        .hero-buttons .btn:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 6px 20px var(--primary-glow); }
        
        /* Item Cards Redesign */
        .item-carousel {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 280px;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1rem;
            margin: 0 -1rem; /* Hide scrollbar visually */
        }
        /* Custom scrollbar */
        .item-carousel::-webkit-scrollbar { height: 8px; }
        .item-carousel::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 10px; }
        .item-carousel::-webkit-scrollbar-track { background: var(--white-glass); border-radius: 10px; }
        
        .item-card {
            background: var(--white-glass);
            border: 1px solid var(--white-border);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-color: var(--primary-color);
        }
        .item-card img { width: 100%; height: 220px; object-fit: cover; display: block; }
        .item-content { padding: 1.5rem; }
        .item-content h4 { font-size: 1.2rem; margin-bottom: 0.5rem; }
        .item-meta { font-size: 0.9rem; opacity: 0.8; margin-bottom: 1rem; }
        .item-footer { display: flex; justify-content: space-between; align-items: center; }
        .item-points { font-weight: 700; color: var(--primary-color); }
        .item-footer .btn { padding: 0.5rem 1rem; font-size: 0.9rem; }

        /* How it Works Redesign */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .step-card {
            background: var(--white-glass);
            border: 1px solid var(--white-border);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
        }
        .step-card .icon { font-size: 3rem; margin-bottom: 1rem; color: var(--primary-color); }

        /* Footer */
        .page-footer {
            padding: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2rem;
            background: rgba(12, 30, 53, 0.5);
            border-top: 1px solid var(--white-border);
        }
        @media (max-width: 1024px) {
        .hero-content h1 {
            font-size: 2.5rem;
        }
        .hero-buttons {
            flex-direction: column;
            gap: 1rem;
        }
        .main-nav {
            gap: 1rem;
        }
    }

    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            align-items: flex-start;

        }

        .main-nav {
            /* flex-direction: column; */
            width: 100%;
            align-items: flex-start;
            justify-content: center;
            gap: rem;
            margin-top: 1rem;
        }
        .page-header {
            padding: 1rem;
        }

        .hero-content h1 {
            font-size: 2rem;
        }

        .hero-content .subtitle {
            font-size: 1rem;
        }

        .item-carousel {
            grid-auto-columns: 80%;
        }

        .item-card img {
            height: 180px;
        }

        .section-header h2 {
            font-size: 2rem;
        }

        .section-glass {
            padding: 2rem 1rem;
        }
    }

    @media (max-width: 480px) {
        .hero-buttons .btn {
            font-size: 0.9rem;
            padding: 0.8rem 1.5rem;
        }

        .item-card img {
            height: 150px;
        }

        .item-content h4 {
            font-size: 1rem;
        }

        .item-meta {
            font-size: 0.8rem;
        }

        .item-footer .btn {
            font-size: 0.8rem;
        }

        .step-card h3 {
            font-size: 1.1rem;
        }

        .step-card p {
            font-size: 0.9rem;
        }
    }

    </style>
</head>
<body>

    <header class="page-header">
        <div class="nav-container">
            <a href="index.php" class="logo">ReWear</a>
            <nav class="main-nav">
                <?php if ($user_data): ?>
                    <div class="user-points-display">
                        <i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($user_data['points']); ?> Points
                    </div>
                    <a href="dashboard.php">
                        <i class="fa-solid fa-table-columns"></i> Dashboard
                    </a>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="page-content">
        <section class="hero-content">
            <h1>Fashion's Second Chance</h1>
            <p class="subtitle">Join a community dedicated to sustainable style. Swap pre-loved clothes, reduce waste, and discover unique pieces without spending a dime.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">🚀 Get Started</a>
                <a href="#how-it-works" class="btn btn-secondary">Learn More</a>
            </div>
        </section>

        <section class="section-glass">
            <div class="section-header">
                <h2>👋 Just In</h2>
                <p>Be the first to discover the latest additions to the ReWear community!</p>
            </div>
            <div class="item-carousel">
                <?php if ($recent_items && $recent_items->num_rows > 0): ?>
                    <?php while($item = $recent_items->fetch_assoc()): ?>
                        <div class="item-card">
                            <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="item-content">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="item-meta">Size: <?php echo htmlspecialchars($item['size']); ?> | <?php echo htmlspecialchars($item['condition']); ?></p>
                                <div class="item-footer">
                                    <span class="item-points">💎 <?php echo htmlspecialchars($item['points']); ?> Points</span>
                                    <a href="item_detail.php?id=<?php echo $item['id']; ?>" class="btn">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; opacity:0.8;">No new items have been added recently. Check back soon!</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-glass">
            <div class="section-header">
                <h2>How It Works</h2>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="icon"><i class="fa-solid fa-camera-retro"></i></div>
                    <h3>1. List Your Items</h3>
                    <p>Snap a photo of your pre-loved clothes and earn points instantly.</p>
                </div>
                <div class="step-card">
                    <div class="icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>2. Discover Gems</h3>
                    <p>Browse thousands of unique items from closets just like yours.</p>
                </div>
                <div class="step-card">
                    <div class="icon"><i class="fa-solid fa-right-left"></i></div>
                    <h3>3. Swap or Redeem</h3>
                    <p>Use your points or arrange a direct swap to get your new favorite piece.</p>
                </div>
            </div>
        </section>

    </div> <footer class="page-footer">
        &copy; <?php echo date("Y"); ?> ReWear - Giving Your Wardrobe a Second Life.
    </footer>

</body>
</html>