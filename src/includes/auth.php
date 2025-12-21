<?php
/**
 * Authentication & Authorization Functions
 * With session hardening and rate limiting
 */

// Session configuration (must be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Only require secure cookies in production (not on .test or localhost)
$isLocal = (
    strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
);
ini_set('session.cookie_secure', $isLocal ? 0 : 1);

session_start();

require_once __DIR__ . '/db.php';

// Session timeout (30 minutes of inactivity)
define('SESSION_TIMEOUT', 1800);

// Rate limiting settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

/**
 * Check and enforce session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Get client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // Cloudflare
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Check if IP is currently locked out
 */
function isLockedOut() {
    global $pdo;

    $ip = getClientIp();

    // Clean up expired lockouts
    $pdo->exec("DELETE FROM ip_lockouts WHERE locked_until < NOW()");

    // Check for active lockout
    $stmt = $pdo->prepare('SELECT locked_until FROM ip_lockouts WHERE ip_address = ? AND locked_until > NOW()');
    $stmt->execute([$ip]);
    $lockout = $stmt->fetch();

    if ($lockout) {
        return strtotime($lockout['locked_until']) - time(); // Return seconds remaining
    }

    return false;
}

/**
 * Record a failed login attempt
 */
function recordFailedAttempt() {
    global $pdo;

    $ip = getClientIp();

    // Record the attempt
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address) VALUES (?)');
    $stmt->execute([$ip]);

    // Count recent attempts (last 15 minutes)
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([$ip]);
    $count = $stmt->fetch()['count'];

    // Lock out if too many attempts
    if ($count >= MAX_LOGIN_ATTEMPTS) {
        $stmt = $pdo->prepare('INSERT INTO ip_lockouts (ip_address, locked_until, attempt_count) VALUES (?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?) ON DUPLICATE KEY UPDATE locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND), attempt_count = attempt_count + 1');
        $stmt->execute([$ip, LOCKOUT_DURATION, $count, LOCKOUT_DURATION]);
    }

    return $count;
}

/**
 * Clear failed attempts for an IP (on successful login)
 */
function clearFailedAttempts() {
    global $pdo;

    $ip = getClientIp();

    $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
    $pdo->prepare('DELETE FROM ip_lockouts WHERE ip_address = ?')->execute([$ip]);
}

/**
 * Get remaining login attempts
 */
function getRemainingAttempts() {
    global $pdo;

    $ip = getClientIp();

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([$ip]);
    $count = $stmt->fetch()['count'];

    return max(0, MAX_LOGIN_ATTEMPTS - $count);
}

/**
 * Authenticate user with email and password
 */
function authenticate($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role_id, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
        recordFailedAttempt();
        return false;
    }

    // Clear failed attempts on success
    clearFailedAttempts();

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Update last login
    $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role_id'] = $user['role_id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = time();

    return true;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Check session timeout
    if (!checkSessionTimeout()) {
        return false;
    }

    return true;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;

    $stmt = $pdo->prepare('
        SELECT u.*, r.name as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get user's permissions
 */
function getUserPermissions($userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) {
            return [];
        }
        $userId = $_SESSION['user_id'];
    }

    global $pdo;

    $stmt = $pdo->prepare('
        SELECT p.name
        FROM permissions p
        JOIN role_permissions rp ON p.id = rp.permission_id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.id = ?
    ');
    $stmt->execute([$userId]);

    return array_column($stmt->fetchAll(), 'name');
}

/**
 * Check if user has a specific permission
 */
function hasPermission($permission) {
    $permissions = getUserPermissions();
    return in_array($permission, $permissions);
}

/**
 * Check if user has any of the specified permissions
 */
function hasAnyPermission($permissionsToCheck) {
    $permissions = getUserPermissions();
    return !empty(array_intersect($permissionsToCheck, $permissions));
}

/**
 * Require user to be logged in, redirect to login if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /TreePlane');
        exit;
    }
}

/**
 * Require user to have a specific permission
 */
function requirePermission($permission) {
    requireLogin();

    if (!hasPermission($permission)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. You do not have permission to access this resource.');
    }
}

/**
 * Log out the current user
 */
function logout() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Create a new user (admin only)
 */
function createUser($email, $password, $name, $roleId) {
    global $pdo;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, name, role_id) VALUES (?, ?, ?, ?)');

    try {
        $stmt->execute([$email, $passwordHash, $name, $roleId]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return false; // Duplicate email
        }
        throw $e;
    }
}

/**
 * Get all roles
 */
function getRoles() {
    global $pdo;

    $stmt = $pdo->query('SELECT * FROM roles ORDER BY id');
    return $stmt->fetchAll();
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
