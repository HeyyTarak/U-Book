<?php
require_once 'includes/config.php';

echo "<h2>Database Structure Check</h2>";

// Check bookings table structure
try {
    $stmt = $pdo->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Bookings Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>