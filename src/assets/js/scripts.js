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
    updateDarkModeIcon();
}

// Update dark mode icon
function updateDarkModeIcon() {
    const sunIcon = document.getElementById('sun-icon');
    const moonIcon = document.getElementById('moon-icon');
    if (sunIcon && moonIcon) {
        const isDark = document.documentElement.classList.contains('dark');
        sunIcon.classList.toggle('hidden', !isDark);
        moonIcon.classList.toggle('hidden', isDark);
    }
}

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Update dark mode icon on load
    updateDarkModeIcon();
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            const isExpanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';

            // Toggle menu visibility
            mobileMenu.classList.toggle('hidden');

            // Update aria-expanded
            mobileMenuButton.setAttribute('aria-expanded', !isExpanded);

            // Toggle icons
            const icons = mobileMenuButton.querySelectorAll('svg');
            icons.forEach(icon => icon.classList.toggle('hidden'));
        });
    }
});
