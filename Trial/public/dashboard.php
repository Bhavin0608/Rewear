<?php
require_once '../php/db.php';

// Start the session to access login data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Guard clause: If user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Securely Fetch All Necessary Data ---

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, points, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's items
$stmt = $conn->prepare("SELECT id, title, category, size, status, image FROM items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_items = $stmt->get_result();
$stmt->close();

// Fetch user's swaps
$stmt = $conn->prepare("SELECT s.id, s.status, s.created_at, i.title FROM swaps s JOIN items i ON s.item_id = i.id WHERE s.requester_id = ? OR s.owner_id = ? ORDER BY s.created_at DESC LIMIT 5");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$my_swaps = $stmt->get_result();
$stmt->close();

// Add this query to the top of dashboard.php
$stmt_req = $conn->prepare(
    "SELECT s.id as swap_id, 
    req_item.title as requested_title, req_item.image as requested_image,
    off_item.title as offered_title, off_item.image as offered_image,
    requester.name as requester_name
    FROM swaps s
    JOIN items req_item ON s.item_id = req_item.id
    JOIN items off_item ON s.offered_item_id = off_item.id
    JOIN users requester ON s.requester_id = requester.id
    WHERE s.owner_id = ? AND s.status = 'pending' AND s.type = 'direct'"
);
$stmt_req->bind_param("i", $user_id);
$stmt_req->execute();
$incoming_requests = $stmt_req->get_result();
$stmt_req->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ReWear</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --primary-color: #43cea2;
            --primary-glow: rgba(67, 206, 162, 0.4);
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
                radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.3) 0%, transparent 40%),
                radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.4) 0%, transparent 40%);
            background-attachment: fixed;
            background-size: cover;
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
            gap: 2rem; 
        }
        
        .main-nav a { 
            font-weight: 600; 
            color: #fff; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            opacity: 0.9; 
            transition: all 0.3s; 
        }
        
        .main-nav a:hover { 
            opacity: 1; 
            color: var(--primary-color); 
        }
        
        .main-nav a i { 
            margin-right: 8px; 
        }
        
        .main-nav a.btn { 
            background: var(--primary-color); 
            padding: 0.6rem 1.2rem; 
            border-radius: 50px; 
        }
        
        .main-nav a.btn:hover { 
            transform: scale(1.05); 
            color: #fff; 
        }

        /* Main Content Layout - Responsive */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            align-items: flex-start;
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: var(--white-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--white-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }
        
        /* Sidebar Styles */
        .sidebar { 
            position: sticky; 
            top: 100px; 
            text-align: center; 
        }
        
        .profile-avatar { 
            border-radius: 50%; 
            width: 100px; 
            height: 100px; 
            margin-bottom: 1rem; 
            border: 3px solid var(--primary-color); 
        }
        
        .sidebar h3 { 
            font-size: 1.5rem; 
        }
        
        .sidebar p { 
            opacity: 0.8; 
            font-size: 0.9rem; 
            margin-top: -0.25rem; 
        }
        
        .points-display { 
            margin: 1.5rem 0; 
            background: rgba(12, 30, 53, 0.5); 
            padding: 1rem; 
            border-radius: 15px; 
        }
        
        .points-display .label { 
            font-size: 1rem; 
            opacity: 0.8; 
        }
        
        .points-display .value { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: var(--primary-color); 
            line-height: 1.2; 
        }
        
        .sidebar .btn { 
            width: 100%; 
        }

        /* Main Content Styles */
        .main-content h2 { 
            font-size: 2rem; 
            margin-bottom: 1.5rem; 
            padding-bottom: 0.5rem; 
            border-bottom: 1px solid var(--white-border); 
        }
        
        .main-content .glass-card { 
            margin-bottom: 2rem; 
        }
        
        .item-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 1.5rem; 
        }
        
        .dashboard-item-card {
            background: var(--white-glass);
            border: 1px solid var(--white-border);
            border-radius: 15px;
            text-align: center;
            padding: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .dashboard-item-card:hover { 
            transform: translateY(-5px); 
            border-color: var(--primary-color); 
            box-shadow: 0 0 20px var(--primary-glow); 
        }
        
        .dashboard-item-card img { 
            width: 100%; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 10px; 
            margin-bottom: 1rem; 
        }
        
        .dashboard-item-card strong { 
            font-size: 1.1rem; 
        }
        
        .item-meta { 
            font-size: 0.8rem; 
            opacity: 0.7; 
        }
        
        /* Item Actions */
        .item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            justify-content: center;
        }
        
        .item-actions .btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-edit {
            background: var(--primary-color);
            color: #fff;
        }
        
        .btn-edit:hover {
            background: #369d78;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: #fff;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .swap-list { 
            display: flex; 
            flex-direction: column; 
            gap: 1rem; 
        }
        
        .swap-card { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: var(--white-glass); 
            border-radius: 10px; 
            padding: 1rem; 
        }
        
        .item-status { 
            font-weight: 600; 
            padding: 0.2rem 0.8rem; 
            border-radius: 20px; 
            font-size: 0.8rem; 
        }
        
        .status-available, .status-completed { 
            color: #2e7d32; 
            background-color: #e8f5e9; 
        }
        
        .status-pending { 
            color: #fbc02d; 
            background-color: #fffde7; 
        }
        
        .status-swapped, .status-rejected { 
            color: #616161; 
            background-color: #f5f5f5; 
        }

        .empty-state { 
            border: 2px dashed var(--white-border); 
            text-align: center; 
            padding: 3rem 1.5rem; 
            border-radius: 15px; 
        }
        
        .empty-state a { 
            color: var(--primary-color); 
            font-weight: 600; 
            text-decoration: none; 
        }

        a.swap-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
        }

        a.swap-card-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .request-card {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--white-glass);
            border-radius: 15px;
            margin-bottom: 1rem;
        }
        
        .request-item { 
            text-align: center; 
        }
        
        .request-item img { 
            width: 80px; 
            height: 80px; 
            object-fit: cover; 
            border-radius: 10px; 
        }
        
        .request-swap-icon { 
            font-size: 2rem; 
            color: var(--primary-color); 
        }
        
        .request-actions { 
            grid-column: 1 / -1; 
            display: flex; 
            gap: 1rem; 
            margin-top: 1rem; 
        }
        
        .request-actions .btn { 
            width: 100%; 
            padding: 0.75rem; 
        }
        
        .btn-accept { 
            background: var(--primary-color); 
        }
        
        .btn-reject { 
            background: #6c757d; 
        }
        
        /* Delete Confirmation Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: var(--white-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--white-border);
            margin: 15% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .modal-actions .btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm {
            background: #dc3545;
            color: #fff;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: #fff;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */
        
        /* Tablet Landscape (1024px and below) */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 250px 1fr;
                gap: 1.5rem;
                padding: 1.5rem;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            
            .sidebar h3 {
                font-size: 1.3rem;
            }
            
            .points-display .value {
                font-size: 2rem;
            }
            
            .item-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 1rem;
            }
            
            .main-content h2 {
                font-size: 1.7rem;
            }
        }

        /* Tablet Portrait (768px and below) */
        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-nav {
                gap: 1rem;
            }
            
            .main-nav a {
                font-size: 0.9rem;
            }
            
            .main-nav a.btn {
                padding: 0.5rem 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
                gap: 1rem;
            }
            
            .sidebar {
                position: static;
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .sidebar .glass-card {
                display: flex;
                align-items: center;
                gap: 1rem;
                text-align: left;
            }
            
            .profile-section {
                display: flex;
                align-items: center;
                gap: 1rem;
                flex: 1;
            }
            
            .profile-avatar {
                width: 60px;
                height: 60px;
                margin-bottom: 0;
            }
            
            .points-display {
                margin: 0;
                min-width: 120px;
                text-align: center;
            }
            
            .points-display .value {
                font-size: 1.5rem;
            }
            
            .item-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .request-card {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 0.5rem;
            }
            
            .request-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                justify-content: center;
            }
            
            .request-item img {
                width: 60px;
                height: 60px;
            }
            
            .request-swap-icon {
                transform: rotate(90deg);
                font-size: 1.5rem;
                margin: 0.5rem 0;
            }
            
            .request-actions {
                grid-column: 1;
                flex-direction: column;
            }
            
            .main-content h2 {
                font-size: 1.5rem;
            }
        }

        /* Mobile (480px and below) */
        @media (max-width: 480px) {
            .page-header {
                padding: 0.75rem;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .nav-container {
                gap: 0.75rem;
            }
            
            .main-nav {
                gap: 0.75rem;
            }
            
            .main-nav a i {
                display: none;
            }
            
            .main-nav a.btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .dashboard-grid {
                padding: 0.75rem;
            }
            
            .glass-card {
                padding: 1rem;
                border-radius: 15px;
            }
            
            .sidebar .glass-card {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }
            
            .profile-section {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .profile-avatar {
                width: 50px;
                height: 50px;
            }
            
            .sidebar h3 {
                font-size: 1.1rem;
            }
            
            .sidebar p {
                font-size: 0.8rem;
            }
            
            .points-display {
                min-width: auto;
                width: 100%;
            }
            
            .points-display .value {
                font-size: 1.8rem;
            }
            
            .item-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            .dashboard-item-card {
                padding: 0.75rem;
            }
            
            .dashboard-item-card img {
                height: 120px;
            }
            
            .dashboard-item-card strong {
                font-size: 1rem;
            }
            
            .item-actions {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .item-actions .btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
            }
            
            .swap-card {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            
            .request-item {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .request-item img {
                width: 50px;
                height: 50px;
            }
            
            .modal-content {
                margin: 20% auto;
                padding: 1.5rem;
                width: 95%;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .main-content h2 {
                font-size: 1.3rem;
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
        }

        /* Very Small Mobile (360px and below) */
        @media (max-width: 360px) {
            .item-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-item-card img {
                height: 140px;
            }
            
            .logo {
                font-size: 1.3rem;
            }
            
            .main-nav a {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="nav-container">
            <a href="index.php" class="logo">ReWear</a>
            <nav class="main-nav">
                <a href="add_item.php" class="btn"><i class="fa-solid fa-circle-plus"></i> List Item</a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-grid">
        <aside class="sidebar">
            <div class="glass-card">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=43cea2&color=fff&size=128&bold=true" alt="Avatar" class="profile-avatar">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="points-display">
                    <span class="label">Your Balance</span>
                    <span class="value"><?php echo htmlspecialchars($user['points']); ?></span>
                </div>
            </div>
        </aside>

        <section class="main-content">
        <?php if ($incoming_requests && $incoming_requests->num_rows > 0): ?>
        <div class="glass-card">
            <h2><i class="fa-solid fa-inbox"></i> Incoming Swap Requests</h2>
            <?php while($request = $incoming_requests->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-item">
                        <img src="../uploads/<?php echo htmlspecialchars($request['offered_image']); ?>" alt="">
                        <strong><?php echo htmlspecialchars($request['offered_title']); ?></strong>
                        <p style="font-size:0.8rem; opacity:0.8;">Offered by <?php echo htmlspecialchars($request['requester_name']); ?></p>
                    </div>

                    <div class="request-swap-icon">
                        <i class="fa-solid fa-right-left"></i>
                    </div>

                    <div class="request-item">
                        <img src="../uploads/<?php echo htmlspecialchars($request['requested_image']); ?>" alt="">
                        <strong><?php echo htmlspecialchars($request['requested_title']); ?></strong>
                        <p style="font-size:0.8rem; opacity:0.8;">Your Item</p>
                    </div>
                    
                    <div class="request-actions">
                        <form action="../php/manage_request.php" method="POST" style="width:100%;">
                            <input type="hidden" name="swap_id" value="<?php echo $request['swap_id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-accept">Accept</button>
                        </form>
                        <form action="../php/manage_request.php" method="POST" style="width:100%;">
                            <input type="hidden" name="swap_id" value="<?php echo $request['swap_id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    
            <div class="glass-card">
                <h2>Your Uploaded Items</h2>
                <div class="item-grid">
                    <?php if ($my_items && $my_items->num_rows > 0): ?>
                        <?php while($item = $my_items->fetch_assoc()): ?>
                            <div class="dashboard-item-card">
                                <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                <div class="item-meta"><?php echo htmlspecialchars($item['category']); ?> | <?php echo htmlspecialchars($item['size']); ?></div>
                                <span class="item-status status-<?php echo htmlspecialchars($item['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($item['status'])); ?>
                                </span>
                                
                                <div class="item-actions">
                                    <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-edit">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <button class="btn btn-delete" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title']); ?>')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>You haven't uploaded any items yet.</p>
                            <a href="add_item.php">List your first one to start swapping!</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card">
                <h2>Recent Swap Activity</h2>
                <div class="swap-list">
                    <?php if ($my_swaps && $my_swaps->num_rows > 0): ?>
                        <?php while($swap = $my_swaps->fetch_assoc()): ?>
                            <a href="swap_detail.php?id=<?php echo $swap['id']; ?>" class="swap-card-link">
                                <div class="swap-card">
                                    <div>
                                        <strong><?php echo htmlspecialchars($swap['title']); ?></strong><br>
                                        <small>On: <?php echo date("F j, Y", strtotime($swap['created_at'])); ?></small>
                                    </div>
                                    <span class="item-status status-<?php echo htmlspecialchars($swap['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($swap['status'])); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No swap activity yet.</p>
                            <a href="index.php">Go browse some items!</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete "<span id="itemTitle"></span>"?</p>
            <p style="font-size: 0.9rem; opacity: 0.8; margin-top: 0.5rem;">This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-confirm" onclick="deleteItem()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let itemToDelete = null;
        
        function confirmDelete(itemId, itemTitle) {
            itemToDelete = itemId;
            document.getElementById('itemTitle').textContent = itemTitle;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            itemToDelete = null;
        }
        
        function deleteItem() {
            if (itemToDelete) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../php/delete_item.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'item_id';
                input.value = itemToDelete;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>