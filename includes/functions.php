<?php
/**
 * Helper Functions for U-Book
 * (Authentication functions are in auth.php)
 */

/**
 * Sanitize output
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Check if event is in the past
 */
function isPastEvent($event_date) {
    return strtotime($event_date) < time();
}

/**
 * Add flash message
 */
function addFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get and clear flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    if (empty($messages)) return;
    
    foreach ($messages as $message) {
        $alertClass = $message['type'] === 'error' ? 'alert-error' : 'alert-success';
        echo '<div class="alert ' . $alertClass . '">' . sanitize($message['message']) . '</div>';
    }
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        addFlashMessage($type, $message);
    }
    header('Location: ' . BASE_URL . $url);
    exit();
}
?>