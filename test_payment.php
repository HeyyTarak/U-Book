<?php
// Simple test to check if CSS loads
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .test-box { 
            background: #6366f1; 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="test-box">
        <h1>CSS Test Page</h1>
        <p>If this box has blue background and white text, CSS is working.</p>
        <p>If it's plain, CSS is broken.</p>
    </div>
    
    <div style="margin: 20px;">
        <h2>Check CSS File</h2>
        <p><a href="assets/css/style.css" target="_blank">Open CSS File</a></p>
        <p>Current path: <?php echo __DIR__; ?></p>
    </div>
</body>
</html>