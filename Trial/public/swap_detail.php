<?php
require_once '../php/db.php';
session_start();

// Security: If user is not logged in, redirect them
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Security: If no swap ID is provided, redirect
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$swap_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'];

// Prepare a query to get all details of the swap
$sql = "SELECT 
            s.id, s.status, s.type, s.created_at,
            i.title, i.image, i.points,
            owner.name as owner_name, s.owner_id,
            requester.name as requester_name, s.requester_id
        FROM swaps s
        JOIN items i ON s.item_id = i.id
        JOIN users owner ON s.owner_id = owner.id
        JOIN users requester ON s.requester_id = requester.id
        WHERE s.id = ? AND (s.owner_id = ? OR s.requester_id = ?)";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $swap_id, $current_user_id, $current_user_id);
$stmt->execute();
$swap = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$swap) {
    // If swap not found, we can show a styled error page
    // For simplicity, we'll redirect with a flash message
    $_SESSION['flash'] = "Swap details not found or you do not have permission to view it.";
    $_SESSION['flash_type'] = "error";
    header('Location: dashboard.php');
    exit;
}

// Determine who the "other user" is
$other_user_name = ($current_user_id == $swap['owner_id']) ? $swap['requester_name'] : $swap['owner_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swap Details - ReWear</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
       :root {
    --primary-color: #43cea2;
    --primary-glow: rgba(67, 206, 162, 0.4);
    --white-glass: rgba(255, 255, 255, 0.1);
    --white-border: rgba(255, 255, 255, 0.2);
    --status-completed-bg: rgba(67, 206, 162, 0.2);
    --status-completed-text: #50ffc1;
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
    background-image: 
        radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.3) 0%, transparent 40%),
        radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.4) 0%, transparent 40%);
    background-attachment: fixed;
    background-size: cover;
    padding: 2rem;
    min-height: 100vh;
}

/* Internal Header */
.page-header {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 2rem;
    z-index: 10;
}

.page-header .logo { 
    font-size: 1.8rem; 
    font-weight: 700; 
    color: white; 
    text-decoration: none; 
}

/* Main Content Card */
.container { 
    max-width: 800px; 
    margin: 4rem auto 2rem auto; 
    padding: 0 1rem;
}

.glass-card {
    background: var(--white-glass);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--white-border);
    border-radius: 20px;
    padding: 2.5rem;
    animation: fadeIn 1s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.glass-card h2 {
    font-size: 2.2rem;
    margin-bottom: 2rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

/* Status Banner */
.status-banner {
    padding: 0.8rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.1rem;
    text-align: center;
    margin-bottom: 2rem;
}

.status-banner.completed { 
    background-color: var(--status-completed-bg); 
    color: var(--status-completed-text); 
}

.status-banner.pending { 
    background-color: rgba(251, 192, 45, 0.2); 
    color: #fbc02d; 
}

/* Details Layout */
.detail-grid { 
    display: grid; 
    grid-template-columns: 200px 1fr; 
    gap: 2rem; 
    align-items: center; 
}

.detail-grid img { 
    width: 100%; 
    border-radius: 15px; 
    border: 1px solid var(--white-border); 
}

.detail-list .detail-item {
    display: flex;
    flex-direction: column;
    margin-bottom: 1.2rem;
    padding-bottom: 1.2rem;
    border-bottom: 1px solid var(--white-border);
}

.detail-list .detail-item:last-child { 
    margin-bottom: 0; 
    padding-bottom: 0; 
    border-bottom: none; 
}

.detail-item .label {
    font-size: 0.9rem;
    opacity: 0.7;
    margin-bottom: 0.25rem;
}

.detail-item .value {
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item .value .fa-coins { 
    color: var(--primary-color); 
}

/* Back Button */
.back-link {
    display: inline-block;
    margin-top: 2.5rem;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    background: var(--white-glass);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: 1px solid var(--white-border);
    transition: all 0.3s ease;
}

.back-link:hover {
    background: var(--primary-color);
    color: #0c1e35;
    border-color: var(--primary-color);
}

/* Responsive Design */

/* Tablet Styles */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    .page-header {
        padding: 1rem;
    }
    
    .page-header .logo {
        font-size: 1.5rem;
    }
    
    .container {
        margin: 3rem auto 1rem auto;
        padding: 0;
    }
    
    .glass-card {
        padding: 2rem;
    }
    
    .glass-card h2 {
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }
    
    .detail-grid {
        grid-template-columns: 150px 1fr;
        gap: 1.5rem;
    }
    
    .status-banner {
        font-size: 1rem;
        padding: 0.7rem 1rem;
    }
    
    .detail-item .value {
        font-size: 1rem;
    }
    
    .back-link {
        margin-top: 2rem;
        padding: 0.6rem 1.2rem;
    }
}

/* Mobile Styles */
@media (max-width: 480px) {
    body {
        padding: 0.5rem;
    }
    
    .page-header {
        padding: 1rem 0.5rem;
        position: relative;
        margin-bottom: 1rem;
    }
    
    .page-header .logo {
        font-size: 1.4rem;
        text-align: center;
        display: block;
    }
    
    .container {
        margin: 0 auto;
    }
    
    .glass-card {
        padding: 1.5rem;
        border-radius: 15px;
    }
    
    .glass-card h2 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .glass-card h2 i {
        font-size: 1.2rem;
    }
    
    .status-banner {
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
        margin-bottom: 1.5rem;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        text-align: center;
    }
    
    .detail-grid img {
        max-width: 200px;
        margin: 0 auto;
    }
    
    .detail-list {
        text-align: left;
    }
    
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
    
    .detail-item .label {
        font-size: 0.8rem;
    }
    
    .detail-item .value {
        font-size: 0.95rem;
        flex-wrap: wrap;
    }
    
    .back-link {
        margin-top: 1.5rem;
        padding: 0.7rem 1.2rem;
        font-size: 0.9rem;
        display: block;
        text-align: center;
        width: 100%;
        border-radius: 10px;
    }
}

/* Extra Small Devices */
@media (max-width: 320px) {
    .glass-card {
        padding: 1rem;
    }
    
    .glass-card h2 {
        font-size: 1.3rem;
    }
    
    .status-banner {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    .detail-grid img {
        max-width: 150px;
    }
    
    .detail-item .label {
        font-size: 0.75rem;
    }
    
    .detail-item .value {
        font-size: 0.9rem;
    }
    
    .back-link {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
}

/* Landscape Mobile Orientation */
@media (max-height: 500px) and (orientation: landscape) {
    body {
        padding: 0.5rem;
    }
    
    .page-header {
        position: relative;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .container {
        margin: 0 auto;
    }
    
    .glass-card {
        padding: 1rem;
    }
    
    .glass-card h2 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    
    .detail-grid {
        grid-template-columns: 120px 1fr;
        gap: 1rem;
    }
    
    .status-banner {
        margin-bottom: 1rem;
        padding: 0.5rem;
    }
    
    .detail-item {
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
    }
    
    .back-link {
        margin-top: 1rem;
    }
}
    </style>
</head>
<body>

    <header class="page-header">
        <a href="index.php" class="logo">ReWear</a>
    </header>

    <div class="container">
        <div class="glass-card">
            <h2><i class="fa-solid fa-receipt"></i> Swap Receipt</h2>

            <div class="status-banner <?php echo htmlspecialchars($swap['status']); ?>">
                Status: <?php echo ucfirst(htmlspecialchars($swap['status'])); ?>
            </div>

            <div class="detail-grid">
                <img src="../uploads/<?php echo htmlspecialchars($swap['image']); ?>" alt="<?php echo htmlspecialchars($swap['title']); ?>">
                <div class="detail-list">
                    <div class="detail-item">
                        <span class="label">Item</span>
                        <span class="value"><?php echo htmlspecialchars($swap['title']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Swapped With</span>
                        <span class="value"><?php echo htmlspecialchars($other_user_name); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Transaction Type</span>
                        <span class="value">
                            <?php if ($swap['type'] == 'points'): ?>
                                <i class="fa-solid fa-coins"></i> Redeemed for <?php echo htmlspecialchars($swap['points']); ?> Points
                            <?php else: ?>
                                <i class="fa-solid fa-right-left"></i> Direct Swap
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Date</span>
                        <span class="value"><?php echo date("F j, Y, g:i a", strtotime($swap['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        </div>
    </div>

</body>
</html>