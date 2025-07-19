<?php
// edit_item.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Dynamic path detection
$db_path = '';
if (file_exists('../php/db.php')) {
    $db_path = '../php/db.php';
} elseif (file_exists('php/db.php')) {
    $db_path = 'php/db.php';
} elseif (file_exists('./php/db.php')) {
    $db_path = './php/db.php';
} else {
    die('Database connection file not found. Please check your directory structure.');
}

require_once $db_path;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$item_id) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?error=item_not_found');
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

// --- REVISED: Security check to prevent editing of swapped items ---
$is_locked = false;
$lock_message = '';
$editable_statuses = ['approved', 'pending', 'rejected'];
if (!in_array($item['status'], $editable_statuses)) {
    $is_locked = true;
    $lock_message = "This item cannot be edited because it is currently involved in a swap (status: " . htmlspecialchars($item['status']) . ").";
}

// Handle form submission ONLY if the item is not locked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $size = $_POST['size'];
    $condition = $_POST['condition'];

    if (empty($title) || empty($description) || empty($category) || empty($size) || empty($condition)) {
        $error = "All fields are required.";
    } else {
        $image_name = $item['image'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_image_name = uniqid('item_', true) . '.' . $file_extension;
                
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }
                
                $upload_path = $upload_dir . $new_image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    if (!empty($item['image']) && file_exists($upload_dir . $item['image'])) {
                        unlink($upload_dir . $item['image']);
                    }
                    $image_name = $new_image_name;
                } else {
                    $error = "Failed to upload image. Please check server permissions for the 'uploads' directory.";
                }
            } else {
                $error = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
             switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "File is too large.";
                    break;
                default:
                    $error = "An error occurred during file upload.";
            }
        }
        
        if (!isset($error)) {
            // The column `condition` is a reserved keyword, so it needs backticks (`).
            $sql = "UPDATE items SET title = ?, description = ?, category = ?, size = ?, `condition` = ?, image = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssii", $title, $description, $category, $size, $condition, $image_name, $item_id, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Item updated successfully!";
                header('Location: dashboard.php?success=item_updated');
                exit;
            } else {
                $error = "Failed to update item. Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - ReWear</title>
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
            --error-color: #dc3545;
            --error-glow: rgba(220, 53, 69, 0.4);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #fff;
            background-color: #0c1e35;
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(67, 206, 162, 0.3) 0%, transparent 40%),
                radial-gradient(circle at 85% 90%, rgba(24, 90, 157, 0.4) 0%, transparent 40%);
            background-attachment: fixed;
            background-size: cover;
            min-height: 100vh;
        }
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
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .logo { font-size: 1.8rem; font-weight: 700; color: #fff; text-decoration: none; }
        .main-nav { display: flex; align-items: center; gap: 2rem; }
        .main-nav a { font-weight: 600; color: #fff; text-decoration: none; display: flex; align-items: center; opacity: 0.9; transition: all 0.3s; }
        .main-nav a:hover { opacity: 1; color: var(--primary-color); }
        .main-nav a i { margin-right: 8px; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .glass-card {
            background: var(--white-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--white-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }
        h1 { font-size: 2rem; margin-bottom: 1.5rem; text-align: center; color: var(--primary-color); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #fff; }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--white-border);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-glow); }
        input::placeholder, textarea::placeholder { color: rgba(255, 255, 255, 0.6); }
        select option { background: #0c1e35; color: #fff; }
        input:disabled, select:disabled, textarea:disabled, button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
            background: rgba(255, 255, 255, 0.05);
        }
        .file-input-button:disabled { cursor: not-allowed; }
        textarea { height: 120px; resize: vertical; }
        .file-input-wrapper { position: relative; display: inline-block; width: 100%; }
        .file-input { position: absolute; left: -9999px; }
        .file-input-button { display: inline-block; padding: 0.75rem 1.5rem; background: var(--primary-color); color: #fff; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-align: center; width: 100%; }
        .file-input-button:hover { background: #369d78; }
        .current-image { margin-bottom: 1rem; text-align: center; }
        .current-image img { max-width: 200px; height: auto; border-radius: 10px; border: 2px solid var(--primary-color); }
        .btn-group { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { flex: 1; padding: 0.75rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover:not(:disabled) { background: #369d78; transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .error-message, .lock-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--error-color);
            color: var(--error-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="nav-container">
            <a href="index.php" class="logo">ReWear</a>
            <nav class="main-nav">
                <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="glass-card">
            <h1><i class="fa-solid fa-edit"></i> Edit Item</h1>
            
            <?php if ($is_locked): ?>
                <div class="lock-message">
                    <i class="fa-solid fa-lock"></i> <?php echo $lock_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Item Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required <?php if($is_locked) echo 'disabled'; ?>>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required <?php if($is_locked) echo 'disabled'; ?>><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required <?php if($is_locked) echo 'disabled'; ?>>
                        <option value="shirts" <?php echo ($item['category'] === 'shirts') ? 'selected' : ''; ?>>Shirts</option>
                        <option value="pants" <?php echo ($item['category'] === 'pants') ? 'selected' : ''; ?>>Pants</option>
                        <option value="dresses" <?php echo ($item['category'] === 'dresses') ? 'selected' : ''; ?>>Dresses</option>
                        <option value="shoes" <?php echo ($item['category'] === 'shoes') ? 'selected' : ''; ?>>Shoes</option>
                        <option value="accessories" <?php echo ($item['category'] === 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                        <option value="outerwear" <?php echo ($item['category'] === 'outerwear') ? 'selected' : ''; ?>>Outerwear</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="size">Size</label>
                    <select id="size" name="size" required <?php if($is_locked) echo 'disabled'; ?>>
                        <option value="XS" <?php echo ($item['size'] === 'XS') ? 'selected' : ''; ?>>XS</option>
                        <option value="S" <?php echo ($item['size'] === 'S') ? 'selected' : ''; ?>>S</option>
                        <option value="M" <?php echo ($item['size'] === 'M') ? 'selected' : ''; ?>>M</option>
                        <option value="L" <?php echo ($item['size'] === 'L') ? 'selected' : ''; ?>>L</option>
                        <option value="XL" <?php echo ($item['size'] === 'XL') ? 'selected' : ''; ?>>XL</option>
                        <option value="XXL" <?php echo ($item['size'] === 'XXL') ? 'selected' : ''; ?>>XXL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="condition">Condition</label>
                    <select id="condition" name="condition" required <?php if($is_locked) echo 'disabled'; ?>>
                        <option value="new" <?php echo ($item['condition'] === 'new') ? 'selected' : ''; ?>>New</option>
                        <option value="like_new" <?php echo ($item['condition'] === 'like_new') ? 'selected' : ''; ?>>Like New</option>
                        <option value="good" <?php echo ($item['condition'] === 'good') ? 'selected' : ''; ?>>Good</option>
                        <option value="fair" <?php echo ($item['condition'] === 'fair') ? 'selected' : ''; ?>>Fair</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Current Image</label>
                    <?php if (!empty($item['image'])): ?>
                        <div class="current-image">
                            <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Current item image" onerror="this.style.display='none'">
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; margin-bottom: 1rem;">No image currently uploaded</p>
                    <?php endif; ?>
                    
                    <label for="image">Upload New Image (Optional)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" class="file-input" <?php if($is_locked) echo 'disabled'; ?>>
                        <label for="image" class="file-input-button <?php if($is_locked) echo 'disabled'; ?>">
                            <i class="fa-solid fa-cloud-upload-alt"></i> Choose New Image
                        </label>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary" <?php if($is_locked) echo 'disabled'; ?>><i class="fa-solid fa-save"></i> Update Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const button = document.querySelector('.file-input-button');
            const fileName = e.target.files.length > 0 ? e.target.files[0].name : '';

            if (fileName) {
                button.innerHTML = `<i class="fa-solid fa-check"></i> ${fileName.length > 25 ? fileName.substring(0, 25) + '...' : fileName}`;
            } else {
                button.innerHTML = '<i class="fa-solid fa-cloud-upload-alt"></i> Choose New Image';
            }
        });
    </script>
</body>
</html>