/**
 * Main JavaScript file
 */

// Dark mode - apply immediately to prevent flash
(function() {
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }
})();

// Dark mode toggle function
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
    updateDarkModeIcons();
}

// Update all dark mode icons (desktop and mobile)
function updateDarkModeIcons() {
    const isDark = document.documentElement.classList.contains('dark');

    // Desktop icons
    const sunIcon = document.getElementById('sun-icon');
    const moonIcon = document.getElementById('moon-icon');
    if (sunIcon && moonIcon) {
        sunIcon.classList.toggle('hidden', !isDark);
        moonIcon.classList.toggle('hidden', isDark);
    }

    // Mobile icons
    const sunIconMobile = document.getElementById('sun-icon-mobile');
    const moonIconMobile = document.getElementById('moon-icon-mobile');
    if (sunIconMobile && moonIconMobile) {
        sunIconMobile.classList.toggle('hidden', !isDark);
        moonIconMobile.classList.toggle('hidden', isDark);
    }
}

// Sidebar state
let sidebarOpen = false;

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const openIcon = document.getElementById('menu-open-icon');
    const closeIcon = document.getElementById('menu-close-icon');

    if (!sidebar || !backdrop) return;

    sidebarOpen = !sidebarOpen;

    if (sidebarOpen) {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Toggle icons
    if (openIcon && closeIcon) {
        openIcon.classList.toggle('hidden', sidebarOpen);
        closeIcon.classList.toggle('hidden', !sidebarOpen);
    }
}

// Close sidebar on mobile (used when clicking nav links)
function closeSidebarOnMobile() {
    if (window.innerWidth < 1024 && sidebarOpen) {
        toggleSidebar();
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Update dark mode icons on load
    updateDarkModeIcons();

    // Sidebar toggle button
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking backdrop
    const backdrop = document.getElementById('sidebar-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarOpen) {
            toggleSidebar();
        }
    });

    // Handle window resize - close sidebar if resizing to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024 && sidebarOpen) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const openIcon = document.getElementById('menu-open-icon');
            const closeIcon = document.getElementById('menu-close-icon');

            sidebarOpen = false;
            backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            if (openIcon) openIcon.classList.remove('hidden');
            if (closeIcon) closeIcon.classList.add('hidden');
        }
    });
});
