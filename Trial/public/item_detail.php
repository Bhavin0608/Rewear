<?php
require_once '../php/db.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$item_id = (int)$_GET['id'];
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Fetch item details and owner's name
$stmt = $conn->prepare("SELECT i.*, u.name as owner_name FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: index.php'); exit;
}

// If user is logged in, fetch their data for the header and the swap dropdown
$user_items_for_swap = [];
$current_user_data = null;
if ($current_user_id) {
    // Fetch user points for the header display
    $stmt_user = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $current_user_id);
    $stmt_user->execute();
    $current_user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    
    // Fetch user's available items for the swap dropdown
    $stmt_user_items = $conn->prepare("SELECT id, title FROM items WHERE user_id = ? AND status = 'available'");
    $stmt_user_items->bind_param("i", $current_user_id);
    $stmt_user_items->execute();
    $result = $stmt_user_items->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_items_for_swap[] = $row;
    }
    $stmt_user_items->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - ReWear</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
    --primary-color: #43cea2;
    --primary-glow: rgba(67, 206, 162, 0.4);
    --secondary-color: #185a9d;
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
    background-color: #0c1e35;
    background-image: radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.3) 0%, transparent 40%), radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.4) 0%, transparent 40%);
    background-attachment: fixed;
    background-size: cover;
    min-height: 100vh;
}

.btn {
    display: block; 
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
    text-decoration: none; 
    text-align: center;
}

.btn:hover { 
    transform: translateY(-3px); 
    box-shadow: 0 6px 20px var(--primary-glow); 
}

/* Glassmorphism Header */
.page-header { 
    padding: 1rem 2rem; 
    position: sticky; 
    top: 0; 
    z-index: 100; 
    background: rgba(12, 30, 53, 0.5); 
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
    flex-wrap: wrap;
    gap: 1rem;
}

.logo { 
    font-size: 1.8rem; 
    font-weight: 700; 
    color: #fff; 
    text-decoration: none; 
}

.main-nav { 
    display: flex; 
    align-items: center; 
    gap: 1.5rem; 
    flex-wrap: wrap;
}

.main-nav a { 
    font-weight: 600; 
    color: #fff; 
    text-decoration: none; 
    display: flex; 
    align-items: center; 
    opacity: 0.9; 
    transition: opacity 0.3s;
    white-space: nowrap;
}

.main-nav a:hover { 
    opacity: 1; 
}

.main-nav a i { 
    margin-right: 8px; 
}

.main-nav a.btn-nav { 
    background: var(--primary-color); 
    padding: 0.6rem 1.2rem; 
    border-radius: 50px; 
}

.main-nav a.btn-nav:hover { 
    transform: scale(1.05); 
}

.user-points-display { 
    display: flex; 
    align-items: center; 
    background-color: var(--white-glass); 
    font-weight: 600; 
    padding: 0.4rem 1rem; 
    border-radius: 20px; 
    font-size: 0.9rem; 
}

.user-points-display i { 
    color: var(--primary-color); 
    margin-right: 6px; 
}

/* Page Content */
.container { 
    max-width: 900px; 
    margin: 2rem auto; 
    padding: 0 1rem;
}

.glass-card { 
    background: var(--white-glass); 
    backdrop-filter: blur(20px); 
    border: 1px solid var(--white-border); 
    border-radius: 20px; 
    padding: 2.5rem; 
}

.item-grid { 
    display: grid; 
    grid-template-columns: 300px 1fr; 
    gap: 2.5rem; 
}

.item-image img { 
    width: 100%; 
    height: auto; 
    border-radius: 15px; 
    border: 1px solid var(--white-border); 
}

.item-details h1 { 
    font-size: 2.5rem; 
    line-height: 1.2; 
}

.item-meta { 
    margin: 1rem 0; 
    opacity: 0.8; 
}

.item-description { 
    margin-bottom: 1.5rem; 
}

.item-points { 
    font-size: 1.8rem; 
    font-weight: 700; 
    color: var(--primary-color); 
    margin-bottom: 1.5rem; 
}

.action-box { 
    margin-top: 1.5rem; 
    padding-top: 1.5rem; 
    border-top: 1px solid var(--white-border); 
}

.action-box h3 { 
    margin-bottom: 1rem; 
}

select {
    width: 100%; 
    padding: 0.8rem 1rem; 
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--white-border); 
    border-radius: 8px; 
    color: white;
    font-size: 1rem; 
    font-family: 'Poppins', sans-serif; 
    margin-bottom: 1rem;
}

select option { 
    background-color: #0c1e35; 
}

/* Responsive Design */

/* Tablet Styles */
@media (max-width: 768px) {
    .page-header {
        padding: 1rem;
    }
    
    .nav-container {
        gap: 0.5rem;
    }
    
    .logo {
        font-size: 1.5rem;
    }
    
    .main-nav {
        gap: 1rem;
    }
    
    .main-nav a {
        font-size: 0.9rem;
    }
    
    .user-points-display {
        font-size: 0.8rem;
        padding: 0.3rem 0.8rem;
    }
    
    .container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .glass-card {
        padding: 1.5rem;
    }
    
    .item-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .item-image {
        order: -1;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .item-details h1 {
        font-size: 2rem;
    }
    
    .item-points {
        font-size: 1.5rem;
    }
}

/* Mobile Styles */
@media (max-width: 480px) {
    .page-header {
        padding: 0.8rem;
    }
    
    .nav-container {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .logo {
        text-align: center;
        font-size: 1.4rem;
    }
    
    .main-nav {
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.8rem;
    }
    
    .main-nav a {
        font-size: 0.8rem;
        padding: 0.5rem 0.8rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid var(--white-border);
        min-width: fit-content;
    }
    
    .main-nav a.btn-nav {
        background: var(--primary-color);
    }
    
    .user-points-display {
        justify-content: center;
        font-size: 0.9rem;
    }
    
    .container {
        margin: 0.5rem auto;
        padding: 0 0.5rem;
    }
    
    .glass-card {
        padding: 1rem;
        border-radius: 15px;
    }
    
    .item-grid {
        gap: 1rem;
    }
    
    .item-details h1 {
        font-size: 1.8rem;
    }
    
    .item-meta {
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .item-description {
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .item-points {
        font-size: 1.3rem;
    }
    
    .action-box {
        margin-top: 1rem;
        padding-top: 1rem;
    }
    
    .action-box h3 {
        font-size: 1.1rem;
    }
    
    .btn {
        font-size: 1rem;
        padding: 0.8rem;
    }
    
    select {
        font-size: 0.9rem;
        padding: 0.7rem;
    }
}

/* Extra small devices */
@media (max-width: 320px) {
    .glass-card {
        padding: 0.8rem;
    }
    
    .item-details h1 {
        font-size: 1.5rem;
    }
    
    .item-meta {
        font-size: 0.8rem;
    }
    
    .item-description {
        font-size: 0.8rem;
    }
    
    .item-points {
        font-size: 1.2rem;
    }
    
    .btn {
        font-size: 0.9rem;
        padding: 0.7rem;
    }
}
    </style>
</head>
<body>

    <header class="page-header">
        <div class="nav-container">
            <a href="index.php" class="logo">ReWear</a>
            <nav class="main-nav">
                <?php if ($current_user_data): ?>
                    <div class="user-points-display">
                        <i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($current_user_data['points']); ?> Points
                    </div>
                    <a href="dashboard.php">
                        <i class="fa-solid fa-table-columns"></i> Dashboard
                    </a>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-nav">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="glass-card">
                <div class="item-grid">
                    <div class="item-image">
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    </div>
                    <div class="item-details">
                        <h1><?php echo htmlspecialchars($item['title']); ?></h1>
                        <p class="item-meta">Listed by: <?php echo htmlspecialchars($item['owner_name']); ?> | Size: <?php echo htmlspecialchars($item['size']); ?> | Condition: <?php echo htmlspecialchars($item['condition']); ?></p>
                        <p class="item-description"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        <div class="item-points"><i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($item['points']); ?> Points</div>

                        <div class="action-box">
                            <?php if ($current_user_id): ?>
                                <?php if ($item['user_id'] == $current_user_id): ?>
                                    <p><em>This is your item. You cannot swap for it.</em></p>
                                <?php elseif ($item['status'] !== 'available'): ?>
                                    <p><em>This item is no longer available for swapping.</em></p>
                                <?php else: ?>
                                    <form action="../php/process_swap.php" method="POST" style="margin-bottom: 1.5rem;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="action" value="redeem_points" class="btn">Redeem with Points</button>
                                    </form>
                                    <?php if (!empty($user_items_for_swap)): ?>
                                        <h3>Or, Request a Direct Swap</h3>
                                        <form action="../php/process_swap.php" method="POST">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <label for="offered_item_id">Offer one of your items:</label>
                                            <select name="offered_item_id" id="offered_item_id" required>
                                                <option value="">-- Select your item --</option>
                                                <?php foreach ($user_items_for_swap as $user_item): ?>
                                                    <option value="<?php echo $user_item['id']; ?>"><?php echo htmlspecialchars($user_item['title']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="action" value="request_swap" class="btn" style="background: var(--secondary-color);">Request Swap</button>
                                        </form>
                                    <?php else: ?>
                                        <p style="opacity: 0.7; font-size: 0.9rem;">You have no available items to offer for a direct swap. <a href="add_item.php" style="color:var(--primary-color);">List an item</a> to start swapping!</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <p style="font-weight:600;"><a href="login.php" style="color:var(--primary-color);">Login or Sign Up</a> to make a swap request.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>