<?php
require_once 'auth_check.php';
require_once '../includes/config.php';

$error = '';
$success = '';

// Fetch categories for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $venue = trim($_POST['venue']);
    $total_tickets = intval($_POST['total_tickets']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image_url = trim($_POST['image_url']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Basic validation
    if (empty($name) || empty($event_date) || empty($venue) || $total_tickets <= 0) {
        $error = 'Please fill all required fields correctly';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO events (name, description, event_date, venue, total_tickets, available_tickets, price, category_id, image_url, is_featured, created_by) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, 
                $description, 
                $event_date, 
                $venue, 
                $total_tickets, 
                $total_tickets, // available_tickets starts same as total
                $price,
                $category_id > 0 ? $category_id : null,
                $image_url,
                $is_featured,
                $_SESSION['user_id']
            ]);
            
            $success = 'Event added successfully!';
            // Clear form
            $_POST = array();
        } catch (PDOException $e) {
            $error = "Error adding event: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - U-Book Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .price-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .price-option {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .price-option:hover {
            border-color: #6366f1;
        }
        .price-option.selected {
            border-color: #6366f1;
            background: #f0f4ff;
        }
        .price-option input {
            display: none;
        }
        .price-display {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .price-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .featured-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Add New Event</h1>
            <a href="manage_events.php" class="btn btn-secondary">← Back to Events</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="event-form">
            <div class="form-group">
                <label for="name">Event Name *</label>
                <input type="text" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required 
                       placeholder="Enter event name">
            </div>

            <div class="form-group">
                <label for="description">Event Description</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Describe the event..."><?php echo $_POST['description'] ?? ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="event_date">Event Date & Time *</label>
                    <input type="datetime-local" id="event_date" name="event_date" 
                           value="<?php echo $_POST['event_date'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="venue">Venue *</label>
                    <input type="text" id="venue" name="venue" value="<?php echo $_POST['venue'] ?? ''; ?>" 
                           placeholder="Event location" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="total_tickets">Total Tickets *</label>
                    <input type="number" id="total_tickets" name="total_tickets" min="1" 
                           value="<?php echo $_POST['total_tickets'] ?? '100'; ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Price Selection -->
            <div class="form-group">
                <label>Ticket Price *</label>
                <div class="price-options">
                    <label class="price-option <?php echo ($_POST['price'] ?? 0) == 0 ? 'selected' : ''; ?>" 
                           onclick="selectPrice(0)">
                        <input type="radio" name="price" value="0" 
                               <?php echo ($_POST['price'] ?? 0) == 0 ? 'checked' : ''; ?>>
                        <div class="price-display">FREE</div>
                        <div class="price-label">No charge</div>
                    </label>
                    
                    <label class="price-option <?php echo ($_POST['price'] ?? 0) == 10 ? 'selected' : ''; ?>" 
                           onclick="selectPrice(10)">
                        <input type="radio" name="price" value="10" 
                               <?php echo ($_POST['price'] ?? 0) == 10 ? 'checked' : ''; ?>>
                        <div class="price-display">$10</div>
                        <div class="price-label">Budget friendly</div>
                    </label>
                    
                    <label class="price-option <?php echo ($_POST['price'] ?? 0) == 25 ? 'selected' : ''; ?>" 
                           onclick="selectPrice(25)">
                        <input type="radio" name="price" value="25" 
                               <?php echo ($_POST['price'] ?? 0) == 25 ? 'checked' : ''; ?>>
                        <div class="price-display">$25</div>
                        <div class="price-label">Standard</div>
                    </label>
                    
                    <label class="price-option <?php echo (isset($_POST['price']) && $_POST['price'] > 25) ? 'selected' : ''; ?>" 
                           onclick="document.getElementById('custom-price').style.display='block';">
                        <input type="radio" name="price" value="custom" 
                               <?php echo (isset($_POST['price']) && $_POST['price'] > 25) ? 'checked' : ''; ?>>
                        <div class="price-display">Custom</div>
                        <div class="price-label">Set your price</div>
                    </label>
                </div>
                
                <div id="custom-price" style="display: <?php echo (isset($_POST['price']) && $_POST['price'] > 25) ? 'block' : 'none'; ?>;">
                    <input type="number" name="price_custom" id="price_custom" 
                           value="<?php echo (isset($_POST['price']) && $_POST['price'] > 25) ? $_POST['price'] : ''; ?>" 
                           min="0" step="0.01" placeholder="Enter custom price"
                           style="margin-top: 0.5rem;">
                </div>
            </div>

            <div class="form-group">
                <label for="image_url">Event Image URL</label>
                <input type="url" id="image_url" name="image_url" 
                       value="<?php echo $_POST['image_url'] ?? ''; ?>" 
                       placeholder="https://example.com/image.jpg">
                <small style="color: #666;">Leave empty for default event image</small>
                
                <!-- Sample image URLs for quick selection -->
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #666;">
                    <strong>Sample Images:</strong>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem; flex-wrap: wrap;">
                        <button type="button" onclick="setImageUrl('https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400')" class="btn-small">Tech</button>
                        <button type="button" onclick="setImageUrl('https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400')" class="btn-small">Music</button>
                        <button type="button" onclick="setImageUrl('https://images.unsplash.com/photo-1546519638-68e109498ffc?w=400')" class="btn-small">Sports</button>
                        <button type="button" onclick="setImageUrl('https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400')" class="btn-small">Art</button>
                    </div>
                </div>
            </div>

            <div class="featured-checkbox">
                <input type="checkbox" id="is_featured" name="is_featured" value="1" 
                       <?php echo ($_POST['is_featured'] ?? '') ? 'checked' : ''; ?>>
                <label for="is_featured">Feature this event on homepage</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Event</button>
                <a href="manage_events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function selectPrice(price) {
        // Update radio buttons
        document.querySelectorAll('input[name="price"]').forEach(radio => {
            radio.checked = radio.value == price;
        });
        
        // Update UI
        document.querySelectorAll('.price-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.target.closest('.price-option').classList.add('selected');
        
        // Hide custom price input
        document.getElementById('custom-price').style.display = 'none';
    }
    
    // Handle custom price selection
    document.querySelectorAll('input[name="price"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('custom-price').style.display = 'block';
                document.getElementById('price_custom').focus();
            } else {
                document.getElementById('custom-price').style.display = 'none';
            }
            
            // Update UI
            document.querySelectorAll('.price-option').forEach(option => {
                option.classList.remove('selected');
            });
            this.closest('.price-option').classList.add('selected');
        });
    });
    
    // Set sample image URL
    function setImageUrl(url) {
        document.getElementById('image_url').value = url;
    }
    
    // Form submission - handle custom price
    document.querySelector('form').addEventListener('submit', function(e) {
        const customPriceRadio = document.querySelector('input[name="price"][value="custom"]');
        const customPriceInput = document.getElementById('price_custom');
        
        if (customPriceRadio.checked) {
            if (!customPriceInput.value || customPriceInput.value < 0) {
                e.preventDefault();
                alert('Please enter a valid custom price');
                customPriceInput.focus();
                return;
            }
            // Create hidden input with custom price
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'price';
            hiddenInput.value = customPriceInput.value;
            this.appendChild(hiddenInput);
        }
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>