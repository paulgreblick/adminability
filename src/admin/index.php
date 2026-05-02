<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$lockoutRemaining = isLockedOut();

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
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>Log in · Adminability</title>

    <script>
        (function() {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16.png">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <link rel="alternate icon" href="/assets/images/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="min-h-full antialiased">

    <div class="min-h-screen relative flex items-center justify-center px-4 py-12
                bg-gradient-to-br from-slate-50 via-white to-slate-100
                dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">

        <!-- Decorative gradient orb -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-500/10 dark:bg-purple-500/20 rounded-full blur-3xl"></div>
        </div>

        <!-- Theme toggle (top right) -->
        <button type="button" onclick="toggleDarkMode()"
                class="fixed top-4 right-4 p-2 rounded-lg bg-white/60 dark:bg-slate-800/60 backdrop-blur
                       border border-slate-200 dark:border-slate-800
                       text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800
                       transition-colors"
                title="Toggle theme">
            <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <!-- Login card -->
        <div class="relative w-full max-w-sm">

            <!-- Brand -->
            <div class="mb-8 flex flex-col items-center">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Adminability</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Log in to your workspace</p>
            </div>

            <!-- Error / lockout message -->
            <?php if ($lockoutRemaining): ?>
                <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 p-3.5">
                    <p class="text-sm text-rose-700 dark:text-rose-300">
                        Too many failed attempts. Try again in <?= ceil($lockoutRemaining / 60) ?> minute<?= ceil($lockoutRemaining / 60) > 1 ? 's' : '' ?>.
                    </p>
                </div>
            <?php elseif ($error): ?>
                <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 p-3.5">
                    <p class="text-sm text-rose-700 dark:text-rose-300"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="card p-6 shadow-sm space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           autofocus
                           <?= $lockoutRemaining ? 'disabled' : '' ?>
                           placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           <?= $lockoutRemaining ? 'disabled' : '' ?>
                           placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary w-full" <?= $lockoutRemaining ? 'disabled' : '' ?>>
                    Log in
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-500">
                Protected area · Authorized users only
            </p>
        </div>
    </div>

    <script src="/assets/js/scripts.js"></script>
</body>
</html>
