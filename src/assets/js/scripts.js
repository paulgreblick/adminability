/**
 * Adminability v3 - Client-side utilities
 *
 * Sections:
 *   1. Dark mode
 *   2. Sidebar (mobile)
 *   3. Modal helpers
 *   4. AJAX utility
 *   5. Toast notifications
 *   6. Keyboard shortcuts
 */

/* ============================================================
   1. Dark mode
   ============================================================ */
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

/* ============================================================
   2. Sidebar (mobile)
   ============================================================ */
function openSidebar() {
    document.getElementById('sidebar')?.classList.remove('-translate-x-full');
    document.getElementById('sidebar-backdrop')?.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('sidebar')?.classList.add('-translate-x-full');
    document.getElementById('sidebar-backdrop')?.classList.add('hidden');
    document.body.style.overflow = '';
}

function closeSidebarOnMobile() {
    if (window.innerWidth < 1024) closeSidebar();
}

// Close sidebar when screen resizes above lg breakpoint
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        document.getElementById('sidebar')?.classList.remove('-translate-x-full');
        document.getElementById('sidebar-backdrop')?.classList.add('hidden');
        document.body.style.overflow = '';
    }
});

/* ============================================================
   3. Modal helpers
   ============================================================ */
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Focus the first input field
    setTimeout(() => {
        const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) firstInput.focus();
    }, 50);
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function closeAllModals() {
    document.querySelectorAll('[data-modal]').forEach(m => {
        m.classList.add('hidden');
        m.classList.remove('flex');
    });
    document.body.style.overflow = '';
}

// Close modals on Escape, backdrop click
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAllModals();
        closeSidebar();
    }
});

document.addEventListener('click', (e) => {
    if (e.target.matches('[data-modal]')) {
        e.target.classList.add('hidden');
        e.target.classList.remove('flex');
        document.body.style.overflow = '';
    }
});

/* ============================================================
   4. AJAX utility
   ============================================================ */
async function api(url, data = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const payload = { ...data, csrf_token: token };

    const body = new URLSearchParams();
    Object.entries(payload).forEach(([k, v]) => {
        if (v === null || v === undefined) return;
        if (Array.isArray(v)) {
            const key = k.endsWith('[]') ? k : k + '[]';
            v.forEach(item => body.append(key, item));
        } else {
            body.append(k, v);
        }
    });

    try {
        const res = await fetch(url, {
            method: 'POST',
            body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (!res.ok || json.success === false) {
            throw new Error(json.error || 'Request failed');
        }
        return json;
    } catch (err) {
        console.error('API error:', err);
        toast(err.message || 'Something went wrong', 'error');
        throw err;
    }
}

/* ============================================================
   5. Toast notifications
   ============================================================ */
function toast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;

    const icon = type === 'success'
        ? '<svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
        : type === 'error'
        ? '<svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        : '<svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

    el.innerHTML = `${icon}<span class="flex-1">${message}</span>`;
    container.appendChild(el);

    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(8px)';
        el.style.transition = 'opacity 150ms, transform 150ms';
        setTimeout(() => el.remove(), 200);
    }, duration);
}

/* ============================================================
   6. Keyboard shortcuts
   ============================================================ */
document.addEventListener('keydown', (e) => {
    // N — new item on pages that support it (when not in an input)
    if (e.key === 'n' && !e.metaKey && !e.ctrlKey && !isInInput(e.target)) {
        const newBtn = document.querySelector('[data-shortcut="new"]');
        if (newBtn) { e.preventDefault(); newBtn.click(); }
    }
});

function isInInput(el) {
    return el.matches('input, textarea, select, [contenteditable="true"]');
}

/* ============================================================
   Expose to window for inline onclick handlers
   ============================================================ */
window.toggleDarkMode = toggleDarkMode;
window.openSidebar = openSidebar;
window.closeSidebar = closeSidebar;
window.closeSidebarOnMobile = closeSidebarOnMobile;
window.openModal = openModal;
window.closeModal = closeModal;
window.api = api;
window.toast = toast;
