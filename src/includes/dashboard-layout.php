<?php
/**
 * Dashboard Layout - Sidebar navigation with header
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$userPermissions = getUserPermissions();

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
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-4 border-b border-gray-800">
                <span class="text-xl font-bold">Admin</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 overflow-y-auto">
                <!-- Main Sections -->
                <div class="mb-6">
                    <a href="/dashboard" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'dashboard' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                </div>

                <!-- Progress Section -->
                <div class="mb-6">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</div>
                    <ul class="space-y-1">
                        <?php if (in_array('videos.view', $userPermissions)): ?>
                        <li>
                            <a href="/videos" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'videos' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Affirmations
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Docs Section -->
                <div class="mb-6">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Docs</div>
                    <ul class="space-y-1">
                        <?php if (in_array('docs.view', $userPermissions)): ?>
                        <li>
                            <a href="/docs" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'docs' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                Knowledge Base
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array('notes.view', $userPermissions)): ?>
                        <li>
                            <a href="/notes" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'notes' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Notes
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Tracking Section -->
                <div class="mb-6">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tracking</div>
                    <ul class="space-y-1">
                        <li>
                            <span class="flex items-center px-4 py-2 text-gray-500 text-sm italic">Coming soon...</span>
                        </li>
                    </ul>
                </div>

                <!-- Admin Section -->
                <?php if (in_array('users.view', $userPermissions) || in_array('roles.view', $userPermissions)): ?>
                <div class="mb-6">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</div>
                    <ul class="space-y-1">
                        <?php if (in_array('users.view', $userPermissions)): ?>
                        <li>
                            <a href="/users" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'users' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Users
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (in_array('roles.view', $userPermissions)): ?>
                        <li>
                            <a href="/roles" class="flex items-center px-4 py-2 rounded-md <?= $current_dashboard_page === 'roles' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Roles
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </nav>

            <!-- User menu -->
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium"><?= htmlspecialchars($currentUser['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($currentUser['role_name'] ?? 'No Role') ?></p>
                    </div>
                    <button onclick="toggleDarkMode()" class="text-gray-400 hover:text-white mr-3" title="Toggle dark mode">
                        <!-- Sun icon (shown in dark mode) -->
                        <svg id="sun-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon icon (shown in light mode) -->
                        <svg id="moon-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <a href="/logout" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($dashboard_title ?? 'Dashboard') ?></h1>
                </div>
            </header>

            <!-- Page content -->
            <div class="p-6">
