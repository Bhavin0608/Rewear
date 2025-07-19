<?php
// =================================================================
// ReWear: Enhanced All-in-One Direct-Login Admin Dashboard
// Location: /public/admin/index.php
// =================================================================

// --- 1. SESSION & LOGIN LOGIC ---
session_start();

// Include the database connection. This is the only external file needed.
require_once '../../php/admin_db.php';

$login_error = '';

// Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    if (isset($pdo)) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: index.php'); // Redirect to clear POST data
            exit;
        } else {
            $login_error = 'Invalid credentials or not an admin.';
        }
    } else {
        $login_error = 'Database connection failed.';
    }
}

// --- 2. ACTION HANDLER (for logged-in admins) ---
if (isset($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'block_user' || $action === 'unblock_user') {
            $userId = $_POST['user_id'] ?? 0;
            $newStatus = ($action === 'block_user') ? 'blocked' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            echo json_encode(['success' => true, 'message' => 'User status updated.']);
        } elseif ($action === 'approve_item' || $action === 'reject_item') {
            $itemId = $_POST['item_id'] ?? 0;
            $newStatus = ($action === 'approve_item') ? 'approved' : 'rejected';
            $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $itemId]);
            echo json_encode(['success' => true, 'message' => 'Item status updated.']);
        } elseif ($action === 'delete_item') {
            $itemId = $_POST['item_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
        } elseif ($action === 'update_item') {
            $itemId = $_POST['item_id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $condition = $_POST['condition'] ?? '';
            $size = $_POST['size'] ?? '';
            $status = $_POST['status'] ?? '';
            
            // Updated SQL query without 'brand'
            $stmt = $pdo->prepare("UPDATE items SET `title` = ?, `description` = ?, `category` = ?, `condition` = ?, `size` = ?, `status` = ? WHERE `id` = ?");
            
            // Updated execute array without the brand variable
            $stmt->execute([$title, $description, $category, $condition, $size, $status, $itemId]);
            
            echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
        } elseif ($action === 'get_item_details') {
            $itemId = $_POST['item_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT items.*, users.name as username FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                echo json_encode(['success' => true, 'item' => $item]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// --- 3. DATA FETCHING (for logged-in admins) ---
if (isset($_SESSION['admin_logged_in'])) {
    // Fetch dashboard statistics
    $stats = [
        'total_users' => $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin != 1')->fetchColumn(),
        'total_items' => $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn(),
        'pending_items' => $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn(),
        'active_swaps' => $pdo->query("SELECT COUNT(*) FROM swaps WHERE status = 'ongoing'")->fetchColumn(),
    ];
    
    // Fetch all non-admin users
    $users_stmt = $pdo->query('SELECT id, name, email, created_at, status FROM users WHERE is_admin != 1 ORDER BY created_at DESC');
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all items that need moderation
    $items_stmt = $pdo->query("SELECT items.*, users.name as username FROM items JOIN users ON items.user_id = users.id WHERE items.status = 'pending' OR items.status = 'reported' ORDER BY items.created_at ASC");
    $items_to_moderate = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all items for management
    $all_items_stmt = $pdo->query("SELECT items.*, users.name as username FROM items JOIN users ON items.user_id = users.id ORDER BY items.created_at DESC");
    $all_items = $all_items_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReWear Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .sidebar-link { display: block; }
        .sidebar-link-active { background-color: #43cea2; color: #fff; }
        .view-hidden { display: none; }
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { 
            background-color: #fefefe; 
            margin: 5% auto; 
            padding: 2rem; 
            border-radius: 8px; 
            width: 90%; 
            max-width: 600px; 
            max-height: 85vh; 
            overflow-y: auto; 
        }
        .close { 
            color: #aaa; 
            float: right; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        .close:hover { color: black; }
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.5s;
        }
    </style>
</head>
<body class="bg-gray-100">

<?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
    <div class="flex min-h-screen">
        <nav class="w-64 bg-gray-800 text-white p-4 flex flex-col">
            <div class="text-center pb-4 border-b border-gray-700">
                <h1 class="text-2xl font-bold">ReWear</h1>
                <span class="text-sm text-gray-400">Admin Panel</span>
            </div>
            <ul class="mt-6 space-y-2 flex-grow">
                <li><a href="#" data-view="dashboard" class="sidebar-link p-3 rounded-lg hover:bg-gray-700 sidebar-link-active">Dashboard</a></li>
                <li><a href="#" data-view="users" class="sidebar-link p-3 rounded-lg hover:bg-gray-700">Users</a></li>
                <li><a href="#" data-view="items" class="sidebar-link p-3 rounded-lg hover:bg-gray-700">Item Moderation</a></li>
                <li><a href="#" data-view="all-items" class="sidebar-link p-3 rounded-lg hover:bg-gray-700">All Items</a></li>
            </ul>
            <a href="index.php?action=logout" class="w-full mt-4 p-3 text-center bg-red-600 hover:bg-red-700 rounded-lg">Logout</a>
        </nav>

        <main class="flex-1 p-8">
            <div id="view-dashboard" class="view-container">
                <h1 class="text-3xl font-bold mb-6">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <h2 class="text-gray-500 text-sm font-medium">Total Users</h2>
                        <p class="text-3xl font-bold text-indigo-600"><?php echo htmlspecialchars($stats['total_users']); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <h2 class="text-gray-500 text-sm font-medium">Total Items</h2>
                        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($stats['total_items']); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <h2 class="text-gray-500 text-sm font-medium">Items Pending Approval</h2>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo htmlspecialchars($stats['pending_items']); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <h2 class="text-gray-500 text-sm font-medium">Active Swaps</h2>
                        <p class="text-3xl font-bold text-green-500"><?php echo htmlspecialchars($stats['active_swaps']); ?></p>
                    </div>
                </div>
            </div>

            <div id="view-users" class="view-container view-hidden">
                <h1 class="text-3xl font-bold mb-6">User Management</h1>
                <div class="bg-white rounded-lg shadow overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">User</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Status</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Joined Date</th>
                                <th class="p-4 text-center text-sm font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b hover:bg-gray-50" id="user-row-<?php echo $user['id']; ?>">
                                <td class="p-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button onclick="handleUserAction('block_user', <?php echo $user['id']; ?>)" class="text-sm text-red-500 hover:underline">Block</button>
                                    <?php else: ?>
                                        <button onclick="handleUserAction('unblock_user', <?php echo $user['id']; ?>)" class="text-sm text-green-500 hover:underline">Unblock</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="view-items" class="view-container view-hidden">
                <h1 class="text-3xl font-bold mb-6">Item Moderation</h1>
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 space-y-4">
                        <?php if (empty($items_to_moderate)): ?>
                            <p class="text-center text-gray-500 py-8">No items are currently pending approval.</p>
                        <?php else: ?>
                            <?php foreach ($items_to_moderate as $item): ?>
                            <div class="border rounded-lg p-4 flex items-center justify-between" id="item-mod-row-<?php echo $item['id']; ?>">
                                <div>
                                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        Listed by: <span class="font-medium"><?php echo htmlspecialchars($item['username']); ?></span> | 
                                        Category: <?php echo htmlspecialchars($item['category']); ?>
                                    </p>
                                    <div class="mt-2">
                                        <?php if ($item['status'] === 'reported'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                                Reported
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending Approval
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="handleItemAction('approve_item', <?php echo $item['id']; ?>)" class="px-4 py-2 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">Approve</button>
                                    <button onclick="handleItemAction('reject_item', <?php echo $item['id']; ?>)" class="px-4 py-2 text-sm bg-red-500 text-white rounded-md hover:bg-red-600">Reject</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="view-all-items" class="view-container view-hidden">
                <h1 class="text-3xl font-bold mb-6">All Items Management</h1>
                <div class="bg-white rounded-lg shadow overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Item</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Owner</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Category</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Status</th>
                                <th class="p-4 text-left text-sm font-semibold text-gray-600">Created</th>
                                <th class="p-4 text-center text-sm font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_items as $item): ?>
                            <tr class="border-b hover:bg-gray-50" id="all-item-row-<?php echo $item['id']; ?>">
                                <td class="p-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></div>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($item['username']); ?></div>
                                </td>
                                <td class="p-4 text-sm"><?php echo htmlspecialchars($item['category']); ?></td>
                                <td class="p-4">
                                    <?php if (empty($item['status'])): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800">
                                            No Status
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                            switch($item['status']) {
                                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                case 'reported': echo 'bg-orange-100 text-orange-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars(ucfirst($item['status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                <td class="p-4 text-center">
                                    <div class="flex space-x-1 justify-center">
                                        <button onclick="editItem(<?php echo $item['id']; ?>)" class="text-sm text-blue-500 hover:underline">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button onclick="deleteItem(<?php echo $item['id']; ?>)" class="text-sm text-red-500 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Item</h2>
            <form id="editItemForm">
                <input type="hidden" id="editItemId">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" id="editTitle" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="editCategory" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="clothing">Clothing</option>
                            <option value="shoes">Shoes</option>
                            <option value="accessories">Accessories</option>
                            <option value="bags">Bags</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                        <select id="editCondition" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
                        <input type="text" id="editSize" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="editStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="reported">Reported</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="editDescription" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md" required></textarea>
                </div>
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">Update Item</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="toast" class="toast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Navigation Logic ---
            const links = document.querySelectorAll('.sidebar-link');
            const views = document.querySelectorAll('.view-container');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const viewId = this.getAttribute('data-view');
                    links.forEach(l => l.classList.remove('sidebar-link-active'));
                    this.classList.add('sidebar-link-active');
                    views.forEach(view => {
                        view.classList.toggle('view-hidden', view.id !== 'view-' + viewId);
                    });
                });
            });

            // --- Modal close events ---
            window.addEventListener('click', function(e) {
                if (e.target === document.getElementById('editModal')) {
                    closeEditModal();
                }
            });

            // --- Edit form submission ---
            document.getElementById('editItemForm').addEventListener('submit', function(e) {
                e.preventDefault();
                updateItem();
            });
        });

        // --- Helper Functions ---
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.opacity = '0';
            }, 3000);
        }

        async function performAction(formData) {
            try {
                const response = await fetch('index.php', { method: 'POST', body: formData });
                return await response.json();
            } catch (error) {
                console.error('Fetch Error:', error);
                showToast('A network error occurred.');
                return { success: false, message: 'A network error occurred.' };
            }
        }

        function fadeOutAndRemove(element) {
            if (element) {
                element.style.transition = 'opacity 0.5s ease';
                element.style.opacity = '0';
                setTimeout(() => element.remove(), 500);
            }
        }

        // --- Modal Functions ---
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // --- Main Action Functions ---

        async function handleUserAction(action, userId) {
            if (!confirm(`Are you sure you want to ${action.replace('_', ' ')} this user?`)) return;
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', userId);

            const result = await performAction(formData);
            if (result.success) {
                showToast(result.message);
                // For a better UX, reload the page to see the status change correctly
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Error: ' + (result.message || 'Action failed.'));
            }
        }
        
        async function handleItemAction(action, itemId) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('item_id', itemId);

            const result = await performAction(formData);
            if (result.success) {
                showToast(result.message);
                fadeOutAndRemove(document.getElementById('item-mod-row-' + itemId));
            } else {
                showToast('Error: ' + (result.message || 'Action failed.'));
            }
        }

        async function deleteItem(itemId) {
            if (!confirm('Are you sure you want to permanently delete this item?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('item_id', itemId);

            const result = await performAction(formData);
            if (result.success) {
                showToast(result.message);
                fadeOutAndRemove(document.getElementById('all-item-row-' + itemId));
            } else {
                showToast('Error: ' + (result.message || 'Failed to delete item.'));
            }
        }

        async function editItem(itemId) {
            const formData = new FormData();
            formData.append('action', 'get_item_details');
            formData.append('item_id', itemId);
            
            const result = await performAction(formData);
            
            if (result.success && result.item) {
                const item = result.item;
                document.getElementById('editItemId').value = item.id;
                document.getElementById('editTitle').value = item.title;
                document.getElementById('editDescription').value = item.description;
                document.getElementById('editCategory').value = item.category;
                document.getElementById('editCondition').value = item.condition;
                document.getElementById('editSize').value = item.size;
                // The line below that caused the error has been removed.
                document.getElementById('editStatus').value = item.status;
                
                document.getElementById('editModal').style.display = 'block';
            } else {
                showToast('Error: ' + (result.message || 'Could not fetch item details.'));
            }
        }

        async function updateItem() {
            const formData = new FormData();
            formData.append('action', 'update_item');
            formData.append('item_id', document.getElementById('editItemId').value);
            formData.append('title', document.getElementById('editTitle').value);
            formData.append('description', document.getElementById('editDescription').value);
            formData.append('category', document.getElementById('editCategory').value);
            formData.append('condition', document.getElementById('editCondition').value); 
            formData.append('size', document.getElementById('editSize').value);
            // The 'brand' line has been removed
            formData.append('status', document.getElementById('editStatus').value);

            const result = await performAction(formData);
            if (result.success) {
                showToast(result.message);
                closeEditModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Error: ' + (result.message || 'Failed to update item.'));
            }
        }
    </script>
<?php else: ?>
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-lg">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">ReWear Admin Login</h2>
                <p class="text-sm text-gray-500">Private access only</p>
            </div>
            <?php if (!empty($login_error)): ?>
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="index.php" class="space-y-4">
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Admin Email" required>
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Password" required>
                </div>
                <button type="submit" class="w-full px-4 py-3 font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Sign In
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>