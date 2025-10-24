<?php
$page_title = "Home";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Fetch featured events with categories
try {
    $stmt = $pdo->query("
        SELECT e.*, c.name as category_name, c.color as category_color, c.icon as category_icon
        FROM events e 
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE e.event_date >= NOW() 
        ORDER BY e.event_date ASC 
        LIMIT 6
    ");
    $featured_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured_events = [];
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1>The Future of College Events is Here.</h1>
        <p>Discover, book, and experience the best campus events, all in one seamless platform.</p>
        <a href="events.php" class="btn btn-primary">Explore Events</a>
    </div>
</section>

<div class="container">
    <!-- Quick Stats -->
    <div class="quick-stats">
        <?php
        try {
            $total_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW()")->fetchColumn();
            $available_tickets = $pdo->query("SELECT SUM(available_tickets) FROM events")->fetchColumn() ?: 0;
            $upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        } catch (Exception $e) {
            $total_events = $available_tickets = $upcoming_events = $total_categories = 0;
        }
        ?>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_events; ?></div>
            <div class="stat-label">Total Events</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $available_tickets; ?></div>
            <div class="stat-label">Available Tickets</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $upcoming_events; ?></div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_categories; ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>

    <!-- Featured Events -->
    <section class="featured-events">
        <div class="page-header">
            <h1>Upcoming Events</h1>
            <p>Don't miss out on these amazing campus experiences</p>
        </div>
        
        <?php if (empty($featured_events)): ?>
            <div class="no-events">
                <h3>No upcoming events</h3>
                <p>Check back later for new events or contact organizers to add events.</p>
                <?php if (isAdmin()): ?>
                    <a href="../admin/add_event.php" class="btn btn-primary">Add Your First Event</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($featured_events as $event): 
                    $status = $event['available_tickets'] == 0 ? 'soldout' : 'available';
                    $status_text = $event['available_tickets'] == 0 ? 'Sold Out' : 'Available';
                    $image_url = $event['image_url'] ?: 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=400';
                ?>
                <div class="event-card">
                    <?php if ($event['image_url']): ?>
                    <div class="event-image" style="background-image: url('<?php echo $image_url; ?>')"></div>
                    <?php endif; ?>
                    
                    <div class="event-header">
                        <?php if ($event['category_name']): ?>
                        <div class="event-category" style="border-left-color: <?php echo $event['category_color'] ?: '#6366F1'; ?>">
                            <span><?php echo $event['category_icon']; ?></span>
                            <?php echo sanitize($event['category_name']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <span class="status-badge status-<?php echo $status; ?>">
                            <?php echo $status_text; ?>
                        </span>
                        
                        <h3><?php echo sanitize($event['name']); ?></h3>
                        <span class="event-date"><?php echo formatDate($event['event_date']); ?></span>
                    </div>
                    
                    <div class="event-body">
                        <div class="event-meta">
                            <div class="event-venue">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                                </svg>
                                <?php echo sanitize($event['venue']); ?>
                            </div>
                            <div class="event-tickets">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 12c0-1.1.9-2 2-2V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2zm-4.42 4.8L12 14.5l-3.58 2.3 1.08-4.12-3.29-2.69 4.24-.25L12 5.8l1.54 3.95 4.24.25-3.29 2.69 1.09 4.11z"/>
                                </svg>
                                <?php echo $event['available_tickets']; ?> available
                            </div>
                            <?php if ($event['price'] > 0): ?>
                            <div class="event-price">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-5h2v5zm4 0h-2V7h2v9z"/>
                                </svg>
                                $<?php echo number_format($event['price'], 2); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <p class="event-description">
                            <?php echo sanitize(substr($event['description'], 0, 120)); ?>
                            <?php if (strlen($event['description']) > 120): ?>...<?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="event-actions">
                        <?php if ($event['available_tickets'] > 0 && !isPastEvent($event['event_date'])): ?>
                            <?php if (isLoggedIn()): ?>
                                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">
                                    Book Now
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-secondary">
                                    Login to Book
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <?php echo isPastEvent($event['event_date']) ? 'Event Ended' : 'Sold Out'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="events.php" class="btn btn-primary">View All Events</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>