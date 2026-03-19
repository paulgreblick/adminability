<?php
/**
 * Dashboard Layout - v2 Simplified sidebar
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = getCurrentUser();

// Current page for active state
$current_dashboard_page = $current_dashboard_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($dashboard_title ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Mobile Header -->
        <header class="lg:hidden bg-gray-900 text-white flex items-center justify-between px-4 py-3 sticky top-0 z-40">
            <button id="sidebar-toggle" type="button" class="p-2 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-600" aria-label="Toggle menu">
                <svg id="menu-open-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg id="menu-close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <span class="text-lg font-bold">Admin</span>
            <div class="flex items-center gap-2">
                <button onclick="toggleDarkMode()" class="p-2 text-gray-400 hover:text-white" title="Toggle dark mode">
                    <svg id="sun-icon-mobile" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg id="moon-icon-mobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <a href="/logout" class="p-2 text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </header>

        <!-- Sidebar Backdrop (mobile only) -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" aria-hidden="true"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col transform -translate-x-full transition-transform duration-200 ease-in-out lg:relative lg:translate-x-0 lg:flex">
            <!-- Logo (hidden on mobile, shown on desktop) -->
            <div class="hidden lg:block p-4 border-b border-gray-800">
                <span class="text-xl font-bold">Admin</span>
            </div>

            <!-- Mobile: Add top padding to account for header -->
            <div class="h-14 lg:hidden"></div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 overflow-y-auto">
                <ul class="space-y-1">
                    <li>
                        <a href="/dashboard" onclick="closeSidebarOnMobile()" class="flex items-center px-4 py-2.5 rounded-md <?= $current_dashboard_page === 'dashboard' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="/videos" onclick="closeSidebarOnMobile()" class="flex items-center px-4 py-2.5 rounded-md <?= $current_dashboard_page === 'videos' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Videos</span>
                        </a>
                    </li>
                    <li>
                        <a href="/notes" onclick="closeSidebarOnMobile()" class="flex items-center px-4 py-2.5 rounded-md <?= $current_dashboard_page === 'notes' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Notes</span>
                        </a>
                    </li>
                    <li>
                        <a href="/docs" onclick="closeSidebarOnMobile()" class="flex items-center px-4 py-2.5 rounded-md <?= $current_dashboard_page === 'docs' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Docs</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User menu (desktop only) -->
            <div class="hidden lg:block p-4 border-t border-gray-800">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?= htmlspecialchars($currentUser['name']) ?></p>
                    </div>
                    <button onclick="toggleDarkMode()" class="text-gray-400 hover:text-white mr-3 flex-shrink-0" title="Toggle dark mode">
                        <svg id="sun-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg id="moon-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <a href="/logout" class="text-gray-400 hover:text-white flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 min-w-0 overflow-auto">
            <!-- Header (desktop only) -->
            <header class="hidden lg:block bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-4 sm:px-6 py-4">
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($dashboard_title ?? 'Dashboard') ?></h1>
                </div>
            </header>

            <!-- Mobile page title -->
            <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm px-4 py-3">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($dashboard_title ?? 'Dashboard') ?></h1>
            </div>

            <!-- Page content -->
            <div class="p-4 sm:p-6">
