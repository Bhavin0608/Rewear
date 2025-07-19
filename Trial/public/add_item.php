<?php
require_once '../php/db.php';
session_start();

// Guard clause: If user is not logged in, redirect them
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List a New Item - ReWear</title>
    
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
            --error-color: #ff8a80;
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
            padding: 2rem;
        }

        /* Internal Header */
        .page-header { position: absolute; top: 0; left: 0; width: 100%; padding: 2rem; z-index: 10; }
        .page-header .logo { font-size: 1.8rem; font-weight: 700; color: white; text-decoration: none; }

        /* Main Form Card */
        .container { max-width: 800px; margin: 4rem auto 2rem auto; }
        .glass-card {
            background: var(--white-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--white-border);
            border-radius: 20px;
            padding: 2.5rem;
        }
        .glass-card h2 { text-align: center; font-size: 2.2rem; margin-bottom: 2rem; }

        /* Form Grid Layout */
        .styled-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 0.5rem; }
        .form-group.full-width { grid-column: 1 / -1; } /* Make some fields span both columns */
        
        .styled-form label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .styled-form input, .styled-form textarea, .styled-form select {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--white-border);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        .styled-form input:focus, .styled-form textarea:focus, .styled-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .styled-form select option { background-color: #0c1e35; }
        
        /* Custom File Input */
        .file-input-wrapper {
            border: 2px dashed var(--white-border);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input-wrapper:hover { border-color: var(--primary-color); background: var(--white-glass); }
        .file-input-wrapper input[type="file"] { display: none; }
        .file-input-label i { font-size: 2rem; color: var(--primary-color); }
        #file-name { display: block; margin-top: 1rem; font-style: italic; }
        
        .submit-btn {
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
        }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px var(--primary-glow); }
        
        .form-errors {
            grid-column: 1 / -1;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background-color: rgba(255, 138, 128, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
            display: none;
        }
    </style>
</head>
<body>

    <header class="page-header">
        <a href="index.php" class="logo">ReWear</a>
    </header>

    <div class="container">
        <div class="glass-card">
            <h2><i class="fa-solid fa-circle-plus"></i> List a New Item</h2>

            <form action="../php/upload.php" method="POST" enctype="multipart/form-data" class="styled-form" id="add-item-form" novalidate>
                
                <div class="form-errors" id="form-errors">
                    <ul id="error-list" style="padding-left: 20px;"></ul>
                </div>
                
                <div class="form-group full-width">
                    <label for="image">Upload Image</label>
                    <div class="file-input-wrapper" onclick="document.getElementById('image').click();">
                        <input type="file" id="image" name="image" accept="image/jpeg, image/png" required>
                        <span class="file-input-label">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Click to browse or drag & drop</p>
                        </span>
                        <span id="file-name"></span>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Vintage Denim Jacket">
                </div>

                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required placeholder="Describe your item..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select...</option>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Kids">Kids</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="condition">Condition</label>
                    <select id="condition" name="condition" required>
                        <option value="">Select...</option>
                        <option value="New">New</option>
                        <option value="Like New">Like New</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="size">Size</label>
                    <input type="text" id="size" name="size" required placeholder="e.g. M, 10, 32x30">
                </div>

                <div class="form-group">
                    <label for="points">Point Value</label>
                    <input type="number" id="points" name="points" required placeholder="e.g. 50" min="1">
                </div>
                
                <div class="form-group full-width">
                    <label for="tags">Tags (optional, comma-separated)</label>
                    <input type="text" id="tags" name="tags" placeholder="e.g. vintage, denim, summer">
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="submit-btn">List My Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addItemForm = document.getElementById('add-item-form');
        const errorContainer = document.getElementById('form-errors');
        const errorList = document.getElementById('error-list');
        const fileInput = document.getElementById('image');
        const fileNameSpan = document.getElementById('file-name');

        addItemForm.addEventListener('submit', function(event) {
            errorList.innerHTML = '';
            errorContainer.style.display = 'none';
            const errors = [];
            
            // --- Validation Checks ---
            if (fileInput.files.length === 0) errors.push('An image must be uploaded.');
            if (document.getElementById('title').value.trim().length < 3) errors.push('Title must be at least 3 characters.');
            if (document.getElementById('description').value.trim().length < 10) errors.push('Description must be at least 10 characters.');
            if (document.getElementById('points').value < 1) errors.push('Points must be a positive number.');

            // If there are errors, display them and prevent submission
            if (errors.length > 0) {
                event.preventDefault();
                errorContainer.style.display = 'block';
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.innerText = error;
                    errorList.appendChild(li);
                });
                window.scrollTo(0, 0);
            }
        });

        fileInput.addEventListener('change', function() {
            fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : '';
        });
    </script>

</body>
</html>