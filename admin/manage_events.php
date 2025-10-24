<?php
// Ensure this script is restricted to authorized users.
require_once 'auth_check.php';
// Database configuration and connection setup.
require_once '../includes/config.php';

// Fetch all events from database
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a production environment, you should log the error and show a generic message
    // instead of die() with the error message.
    error_log("Error fetching events: " . $e->getMessage()); // Log the error
    die("An error occurred while fetching events. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - U-Book Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    </head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <h1>Manage Events</h1>
        
        <div class="admin-actions">
            <a href="add_event.php" class="btn btn-primary">Add New Event</a>
        </div>

        <div class="events-table">
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Price</th>
                        <th>Tickets</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No events found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['name']); ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($event['event_date'])); ?></td>
                                <td><?= htmlspecialchars($event['venue']); ?></td>
                                <td>
                                    <?php if ($event['price'] == 0): ?>
                                        <span style="color: #10b981; font-weight: bold;">FREE</span>
                                    <?php else: ?>
                                        <span style="color: #6366f1; font-weight: bold;">$<?= number_format($event['price'], 2); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $event['total_tickets']; ?></td>
                                <td>
                                    <?php 
                                        $available = (int)$event['available_tickets'];
                                        $ticket_class = $available < 10 ? 'text-danger' : 'text-success';
                                    ?>
                                    <span class="<?= $ticket_class; ?>">
                                        <?= $available; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php $eventId = (int)$event['id']; ?>
                                    <a href="edit_event.php?id=<?= $eventId; ?>" class="btn btn-edit">Edit</a>
                                    <a href="delete_event.php?id=<?= $eventId; ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete the event: &quot;<?= htmlspecialchars($event['name']); ?>&quot;?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>