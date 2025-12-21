<?php
/**
 * Login Page (Hidden URL)
 */

require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$lockoutRemaining = isLockedOut();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockoutRemaining) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } elseif (authenticate($email, $password)) {
        header('Location: /dashboard');
        exit;
    } else {
        $remaining = getRemainingAttempts();
        if ($remaining > 0) {
            $error = "Invalid email or password. $remaining attempts remaining.";
        } else {
            $lockoutRemaining = isLockedOut();
            $error = '';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <!-- Dark mode toggle -->
    <button onclick="toggleDarkMode()" class="fixed top-4 right-4 p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors" title="Toggle dark mode">
        <!-- Sun icon (shown in dark mode) -->
        <svg id="sun-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <!-- Moon icon (shown in light mode) -->
        <svg id="moon-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>

    <div class="w-full max-w-sm">
        <?php if ($lockoutRemaining): ?>
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
            <p class="text-sm text-red-700 dark:text-red-400">
                Too many failed attempts. Please try again in <?= ceil($lockoutRemaining / 60) ?> minute<?= ceil($lockoutRemaining / 60) > 1 ? 's' : '' ?>.
            </p>
        </div>
        <?php elseif ($error): ?>
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
            <p class="text-sm text-red-700 dark:text-red-400"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 pt-8 pb-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="email">
                    Email
                </label>
                <input
                    class="appearance-none border border-gray-300 dark:border-gray-600 rounded-md w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= $lockoutRemaining ? 'bg-gray-100 dark:bg-gray-600' : '' ?>"
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    <?= $lockoutRemaining ? 'disabled' : '' ?>
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="password">
                    Password
                </label>
                <input
                    class="appearance-none border border-gray-300 dark:border-gray-600 rounded-md w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= $lockoutRemaining ? 'bg-gray-100 dark:bg-gray-600' : '' ?>"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    <?= $lockoutRemaining ? 'disabled' : '' ?>
                >
            </div>

            <button
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                type="submit"
                <?= $lockoutRemaining ? 'disabled' : '' ?>
            >
                Sign In
            </button>
        </form>
    </div>
    <script src="/assets/js/scripts.js"></script>
</body>
</html>
