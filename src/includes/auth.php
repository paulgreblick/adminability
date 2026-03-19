<?php
/**
 * Authentication - v2 Simplified
 * Session-based auth with rate limiting. No RBAC — all users are admins.
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

    // Count recent attempts (last 15 minutes)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempted_at > datetime('now', '-15 minutes')");
    $stmt->execute([$ip]);
    $count = $stmt->fetch()['count'];

    if ($count >= MAX_LOGIN_ATTEMPTS) {
        // Find when the oldest relevant attempt expires
        $stmt = $pdo->prepare("SELECT attempted_at FROM login_attempts WHERE ip_address = ? ORDER BY attempted_at ASC LIMIT 1");
        $stmt->execute([$ip]);
        $oldest = $stmt->fetch();
        if ($oldest) {
            $unlockTime = strtotime($oldest['attempted_at']) + LOCKOUT_DURATION;
            $remaining = $unlockTime - time();
            return $remaining > 0 ? $remaining : false;
        }
    }

    return false;
}

/**
 * Record a failed login attempt
 */
function recordFailedAttempt() {
    global $pdo;

    $ip = getClientIp();

    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);

    // Clean up old attempts (older than 1 hour)
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-1 hour')");

    // Count recent attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempted_at > datetime('now', '-15 minutes')");
    $stmt->execute([$ip]);
    return $stmt->fetch()['count'];
}

/**
 * Clear failed attempts for an IP (on successful login)
 */
function clearFailedAttempts() {
    global $pdo;
    $ip = getClientIp();
    $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}

/**
 * Get remaining login attempts
 */
function getRemainingAttempts() {
    global $pdo;
    $ip = getClientIp();

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempted_at > datetime('now', '-15 minutes')");
    $stmt->execute([$ip]);
    $count = $stmt->fetch()['count'];

    return max(0, MAX_LOGIN_ATTEMPTS - $count);
}

/**
 * Authenticate user with email and password
 */
function authenticate($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, first_name, is_active FROM users WHERE email = ?');
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
    $stmt = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_first_name'] = $user['first_name'];
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
    return checkSessionTimeout();
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Require user to be logged in, redirect to login if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/');
        exit;
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
