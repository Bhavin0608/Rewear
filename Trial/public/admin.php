<?php
require_once '../php/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo '<div class="container"><div class="card">Access denied. Only admins can view this page.</div></div>';
    include 'includes/footer.php';
    exit;
}
// Fetch all items pending approval
$pending_items = $conn->query("SELECT items.*, users.name as uploader FROM items JOIN users ON items.user_id = users.id WHERE items.status = 'pending' ORDER BY items.created_at DESC");
// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<div class="container admin-panel">
    <h2>Admin Panel</h2>
    <h3>Moderate Item Listings</h3>
    <?php if ($pending_items && $pending_items->num_rows > 0): ?>
        <?php while($item = $pending_items->fetch_assoc()): ?>
            <div class="card">
                <strong><?php echo htmlspecialchars($item['title']); ?></strong> by <?php echo htmlspecialchars($item['uploader']); ?><br>
                <small>Category: <?php echo htmlspecialchars($item['category']); ?> | Status: <?php echo htmlspecialchars($item['status']); ?></small><br>
                <form action="../php/admin.php" method="POST" style="display:inline;">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <button class="btn" name="approve">Approve</button>
                    <button class="btn" name="reject" style="background:#c62828;">Reject</button>
                    <button class="btn" name="remove" style="background:#fbc02d; color:#222;">Remove</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">No items pending approval.</div>
    <?php endif; ?>
    <h3>Manage Users</h3>
    <?php if ($users && $users->num_rows > 0): ?>
        <?php while($user = $users->fetch_assoc()): ?>
            <div class="card">
                <strong><?php echo htmlspecialchars($user['name']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)<br>
                <small>Role: <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?> | Points: <?php echo $user['points']; ?> | Joined: <?php echo htmlspecialchars($user['created_at']); ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">No users found.</div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?> 