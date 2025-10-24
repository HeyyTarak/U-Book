<?php
require_once '../includes/config.php';

// Clear the pending booking session
unset($_SESSION['pending_booking']);

echo "Session cleared";
?>