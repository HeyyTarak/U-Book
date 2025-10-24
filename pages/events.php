<?php
$page_title = "All Events";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build query
$sql = "SELECT id, name, description, event_date, venue, total_tickets, available_tickets 
        FROM events 
        WHERE event_date >= NOW()";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY event_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    addFlashMessage('error', 'Error loading events: ' . $e->getMessage());
}
?>

<div class="container">
    <div class="page-header">
        <h1>All Events</h1>
        <p>Discover and book tickets for upcoming college events</p>
    </div>

    <!-- Search and Filter -->
    <div class="events-filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search events..." 
                       value="<?php echo sanitize($search); ?>" class="search-input">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="events.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Events Grid -->
    <div class="events-section">
        <?php if (empty($events)): ?>
            <div class="no-events">
                <h3>No events found</h3>
                <p>Try adjusting your search criteria or check back later for new events.</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-header">
                        <h3><?php echo sanitize($event['name']); ?></h3>
                        <span class="event-date"><?php echo formatDate($event['event_date']); ?></span>
                    </div>
                    <div class="event-body">
                        <p class="event-venue">📍 <?php echo sanitize($event['venue']); ?></p>
                        <p class="event-tickets">
                            🎫 
                            <span class="<?php echo $event['available_tickets'] < 10 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $event['available_tickets']; ?> available
                            </span>
                            / <?php echo $event['total_tickets']; ?> total
                        </p>
                        <p class="event-description">
                            <?php echo sanitize(substr($event['description'], 0, 150)); ?>
                            <?php if (strlen($event['description']) > 150): ?>...<?php endif; ?>
                        </p>
                    </div>
                    <div class="event-actions">
                        <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">
                            View Details & Book
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>