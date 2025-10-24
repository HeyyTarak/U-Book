<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

logoutUser();
redirect('/pages/index.php', 'You have been logged out successfully');
?>