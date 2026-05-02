<?php
/**
 * Sidebar navigation — shared across all dashboard pages
 * Expects: $current_page (one of: dashboard, tasks, videos, notes, docs)
 * Expects: $currentUser (from layout.php)
 */

$nav = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/dashboard',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['id' => 'projects', 'label' => 'Projects', 'href' => '/projects',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>'],
    ['id' => 'tasks', 'label' => 'Tasks', 'href' => '/tasks',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
    ['id' => 'brainstorm', 'label' => 'Brainstorm', 'href' => '/brainstorm',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
    ['id' => 'notes', 'label' => 'Notes', 'href' => '/notes',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
    ['id' => 'docs', 'label' => 'Docs', 'href' => '/docs',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
    ['id' => 'procedures', 'label' => 'Procedures', 'href' => '/procedures',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6m-6 4h4"/>'],
    ['id' => 'upskilling', 'label' => 'Upskilling', 'href' => '/upskilling',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.26 10.147a60.438 60.438 0 000 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 000-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>'],
    ['id' => 'tabs', 'label' => 'Tab Opener', 'href' => '/tabs',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>'],
    ['id' => 'uptime', 'label' => 'Uptime', 'href' => '/uptime',
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'],
];

// Derive avatar initial
$initial = strtoupper(substr($currentUser['first_name'] ?? $currentUser['name'] ?? '?', 0, 1));
$avatarColor = ($initial === 'P') ? 'bg-indigo-500' : (($initial === 'A') ? 'bg-rose-500' : 'bg-slate-500');
?>
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-60 flex flex-col bg-white dark:bg-slate-900
              border-r border-slate-200 dark:border-slate-800
              transform -translate-x-full transition-transform duration-200 ease-in-out
              lg:relative lg:translate-x-0 lg:flex">

    <!-- Brand -->
    <div class="flex items-center justify-between h-14 px-5 border-b border-slate-200 dark:border-slate-800">
        <a href="/dashboard" class="flex items-center gap-2 group">
            <div class="w-7 h-7 rounded-md bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-sm font-semibold tracking-tight text-slate-900 dark:text-white">Adminability</span>
        </a>
        <button type="button" onclick="closeSidebar()" class="lg:hidden p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <?php foreach ($nav as $item): ?>
            <?php $active = $current_page === $item['id']; ?>
            <a href="<?= $item['href'] ?>"
               onclick="closeSidebarOnMobile()"
               class="nav-item <?= $active ? 'nav-item-active' : '' ?>">
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?= $item['icon'] ?>
                </svg>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- User section -->
    <div class="p-3 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 px-2 py-1.5">
            <div class="avatar <?= $avatarColor ?> flex-shrink-0"><?= htmlspecialchars($initial) ?></div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-slate-900 dark:text-white truncate">
                    <?= htmlspecialchars($currentUser['first_name'] ?? $currentUser['name']) ?>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    <?= htmlspecialchars($currentUser['email']) ?>
                </div>
            </div>
        </div>
        <div class="mt-2 space-y-1">
            <button type="button" onclick="toggleDarkMode()" class="w-full flex items-center justify-center gap-2 px-2 py-1.5 text-xs font-medium rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors" title="Toggle theme">
                <svg data-theme-icon="light" class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg data-theme-icon="dark" class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="dark:hidden">Dark mode</span>
                <span class="hidden dark:inline">Light mode</span>
            </button>
            <a href="/logout" class="w-full flex items-center justify-center gap-2 px-2 py-2 text-sm font-medium rounded-md border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 dark:hover:bg-rose-950/40 dark:hover:border-rose-900 dark:hover:text-rose-300 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Log out</span>
            </a>
        </div>
    </div>
</aside>
