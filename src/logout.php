<?php
/**
 * Logout
 */

require_once 'includes/auth.php';

logout();

header('Location: /admin/');
exit;
