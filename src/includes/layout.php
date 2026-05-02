<?php
/**
 * Main layout shell for authenticated pages
 *
 * Usage:
 *   $page_title = 'Dashboard';
 *   $current_page = 'dashboard';
 *   require_once __DIR__ . '/includes/layout.php';
 *   layout_start();
 *   // ... page content ...
 *   layout_end();
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$page_title = $page_title ?? 'Adminability';
$current_page = $current_page ?? '';
$csrf = generateCsrfToken();

function layout_start() {
    global $page_title, $current_page, $currentUser, $csrf;
    ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title><?= htmlspecialchars($page_title) ?> · Adminability</title>

    <!-- Prevent flash of unstyled theme -->
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

    <!-- Mobile top bar -->
    <header class="lg:hidden sticky top-0 z-30 flex items-center justify-between h-14 px-4 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
        <button type="button" onclick="openSidebar()" class="p-2 -ml-2 rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($page_title) ?></span>
        <div class="w-9"></div>
    </header>

    <div class="flex min-h-screen lg:min-h-0">

        <!-- Sidebar backdrop -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden" onclick="closeSidebar()"></div>

        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-1 min-w-0 lg:h-screen lg:overflow-y-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">
    <?php
}

function layout_end() {
    ?>
            </div>
        </main>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="/assets/js/scripts.js"></script>
</body>
</html>
    <?php
}
