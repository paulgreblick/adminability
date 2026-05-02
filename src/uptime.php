<?php
/**
 * Uptime — manual URL status checks, grouped into named bundles
 */

$page_title = 'Uptime';
$current_page = 'uptime';
require_once __DIR__ . '/includes/layout.php';

$monitors = $pdo->query("SELECT * FROM monitors ORDER BY sort_order, name")->fetchAll();

// Bulk-fetch URLs
$urlsByMonitor = [];
if ($monitors) {
    $ids = array_column($monitors, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $urlStmt = $pdo->prepare("SELECT * FROM monitor_urls WHERE monitor_id IN ($placeholders) ORDER BY monitor_id, sort_order, id");
    $urlStmt->execute($ids);
    foreach ($urlStmt->fetchAll() as $u) {
        $urlsByMonitor[$u['monitor_id']][] = $u;
    }
}

// Roll up stats across all URLs
$totalUrls = 0; $up = 0; $down = 0; $unknown = 0;
foreach ($urlsByMonitor as $list) {
    foreach ($list as $u) {
        $totalUrls++;
        if ($u['last_status'] === 'up') $up++;
        elseif ($u['last_status'] === 'down') $down++;
        else $unknown++;
    }
}

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Uptime</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Group URLs into monitors — check them all with one click</p>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($totalUrls > 0): ?>
            <button id="check-all-btn" onclick="checkAll()" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Check All
            </button>
        <?php endif; ?>
        <button data-shortcut="new" onclick="openNewMonitor()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Monitor
        </button>
    </div>
</div>

<!-- Stats -->
<?php if ($totalUrls > 0): ?>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">URLs</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"><?= $totalUrls ?></div>
    </div>
    <div class="card p-4 border-l-4 border-l-emerald-500">
        <div class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Up</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white" id="stat-up"><?= $up ?></div>
    </div>
    <div class="card p-4 border-l-4 border-l-rose-500">
        <div class="text-xs font-medium uppercase tracking-wider text-rose-600 dark:text-rose-400">Down</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white" id="stat-down"><?= $down ?></div>
    </div>
    <div class="card p-4 border-l-4 border-l-slate-400">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Unknown</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white" id="stat-unknown"><?= $unknown ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Monitor grid -->
<?php if (empty($monitors)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No monitors yet</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create a monitor to group URLs you want to check together.</p>
        <button onclick="openNewMonitor()" class="btn-primary mt-4">New Monitor</button>
    </div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($monitors as $m):
        $urls = $urlsByMonitor[$m['id']] ?? [];
        $urlCount = count($urls);
        $mUp = 0; $mDown = 0; $mUnknown = 0;
        foreach ($urls as $u) {
            if ($u['last_status'] === 'up') $mUp++;
            elseif ($u['last_status'] === 'down') $mDown++;
            else $mUnknown++;
        }
    ?>
    <div class="card p-5 monitor-card flex flex-col" data-monitor-id="<?= $m['id'] ?>">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2 min-w-0">
                <?php if ($mDown > 0): ?>
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0" title="<?= $mDown ?> down"></span>
                <?php elseif ($mUnknown > 0 || $urlCount === 0): ?>
                    <span class="w-2.5 h-2.5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex-shrink-0" title="Not fully checked"></span>
                <?php else: ?>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0" title="All up"></span>
                <?php endif; ?>
                <h3 class="font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($m['name']) ?></h3>
            </div>
            <button onclick='editMonitor(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($urls, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-1 -m-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
        </div>

        <!-- URL list -->
        <?php if ($urlCount === 0): ?>
            <p class="text-sm text-slate-400 italic mb-3">No URLs yet. Click the edit icon to add some.</p>
        <?php else: ?>
        <ul class="space-y-1.5 mb-4 flex-1">
            <?php foreach ($urls as $u):
                $s = $u['last_status'];
                $host = parse_url($u['url'], PHP_URL_HOST) ?: $u['url'];
                $label = $u['label'] ?: $host;
            ?>
            <li class="monitor-url-row flex items-center gap-2 text-xs" data-url-id="<?= $u['id'] ?>">
                <span class="url-status-dot flex-shrink-0" data-status="<?= $s ?>">
                    <?php if ($s === 'up'): ?>
                        <span class="block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <?php elseif ($s === 'down'): ?>
                        <span class="block w-2 h-2 rounded-full bg-rose-500"></span>
                    <?php else: ?>
                        <span class="block w-2 h-2 rounded-full border border-slate-300 dark:border-slate-600"></span>
                    <?php endif; ?>
                </span>
                <a href="<?= htmlspecialchars($u['url']) ?>" target="_blank" rel="noopener" class="flex-1 truncate text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400" title="<?= htmlspecialchars($u['url']) ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
                <span class="url-status-meta text-slate-500 dark:text-slate-400 flex-shrink-0">
                    <?php if ($s === 'up'): ?>
                        <?= (int)$u['last_response_time_ms'] ?>ms
                    <?php elseif ($s === 'down'): ?>
                        <span class="text-rose-600 dark:text-rose-400"><?= $u['last_status_code'] ? 'HTTP ' . (int)$u['last_status_code'] : 'Error' ?></span>
                    <?php endif; ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- Footer -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span>
                <?= $urlCount ?> URL<?= $urlCount === 1 ? '' : 's' ?>
                <?php if ($mDown > 0): ?>
                    · <span class="text-rose-600 dark:text-rose-400"><?= $mDown ?> down</span>
                <?php endif; ?>
            </span>
            <?php if ($urlCount > 0): ?>
                <button onclick="checkOne(<?= $m['id'] ?>)" class="btn-ghost !px-2 !py-1 !text-xs">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Check
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Monitor Modal -->
<div id="monitor-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel max-w-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="monitor-modal-title">New Monitor</h3>
            <button onclick="closeModal('monitor-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="monitor-form" onsubmit="submitMonitor(event)" class="p-5 space-y-4">
            <input type="hidden" name="monitor_id" id="monitor-id" value="">

            <div>
                <label class="form-label">Name</label>
                <input type="text" name="name" id="monitor-name" required placeholder="e.g., Main sites">
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="form-label !mb-0">URLs</label>
                    <button type="button" onclick="addUrlRow()" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add URL</button>
                </div>
                <div id="url-rows" class="space-y-2">
                    <!-- url rows injected by JS -->
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="monitor-delete-btn" onclick="deleteMonitor()" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hidden">Delete</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeModal('monitor-modal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" id="monitor-submit-btn">Create Monitor</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function urlRowHtml(url = '', label = '') {
    return `
        <div class="flex items-center gap-2 url-row">
            <input type="url" name="urls[]" value="${url.replace(/"/g, '&quot;')}" placeholder="https://example.com" required class="flex-1">
            <input type="text" name="labels[]" value="${label.replace(/"/g, '&quot;')}" placeholder="Label (optional)" class="flex-1">
            <button type="button" onclick="this.closest('.url-row').remove()" class="p-2 text-slate-400 hover:text-rose-500 flex-shrink-0" title="Remove">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    `;
}

function addUrlRow(url = '', label = '') {
    document.getElementById('url-rows').insertAdjacentHTML('beforeend', urlRowHtml(url, label));
}

function openNewMonitor() {
    document.getElementById('monitor-modal-title').textContent = 'New Monitor';
    document.getElementById('monitor-form').reset();
    document.getElementById('monitor-id').value = '';
    document.getElementById('monitor-submit-btn').textContent = 'Create Monitor';
    document.getElementById('monitor-delete-btn').classList.add('hidden');
    document.getElementById('url-rows').innerHTML = '';
    addUrlRow();
    addUrlRow();
    openModal('monitor-modal');
}

function editMonitor(m, urls) {
    document.getElementById('monitor-modal-title').textContent = 'Edit Monitor';
    document.getElementById('monitor-id').value = m.id;
    document.getElementById('monitor-name').value = m.name;
    document.getElementById('monitor-submit-btn').textContent = 'Save Changes';
    document.getElementById('monitor-delete-btn').classList.remove('hidden');
    document.getElementById('url-rows').innerHTML = '';
    if (urls && urls.length > 0) {
        urls.forEach(u => addUrlRow(u.url, u.label || ''));
    } else {
        addUrlRow();
    }
    openModal('monitor-modal');
}

async function submitMonitor(e) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const isEdit = !!fd.get('monitor_id');
    fd.append('action', isEdit ? 'update' : 'create');

    const body = new URLSearchParams();
    body.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    for (const [key, val] of fd.entries()) {
        body.append(key, val);
    }

    try {
        const res = await fetch('/api/monitors', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        if (!res.ok || json.success === false) throw new Error(json.error || 'Save failed');
        toast(isEdit ? 'Monitor updated' : 'Monitor created', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (err) {
        toast(err.message, 'error');
    }
}

async function deleteMonitor() {
    const id = document.getElementById('monitor-id').value;
    if (!id || !confirm('Delete this monitor and all its URLs?')) return;
    try {
        await api('/api/monitors', { action: 'delete', monitor_id: id });
        toast('Monitor deleted', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

function setRowChecking(row) {
    const dot = row.querySelector('.url-status-dot');
    const meta = row.querySelector('.url-status-meta');
    dot.innerHTML = '<svg class="w-2.5 h-2.5 animate-spin text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    meta.innerHTML = '<span class="text-slate-500">…</span>';
}

function applyRowResult(row, result) {
    const dot = row.querySelector('.url-status-dot');
    const meta = row.querySelector('.url-status-meta');
    if (result.status === 'up') {
        dot.innerHTML = '<span class="block w-2 h-2 rounded-full bg-emerald-500"></span>';
        meta.innerHTML = `${result.time_ms}ms`;
    } else if (result.status === 'down') {
        dot.innerHTML = '<span class="block w-2 h-2 rounded-full bg-rose-500"></span>';
        meta.innerHTML = `<span class="text-rose-600 dark:text-rose-400" title="${escapeAttr(result.error || '')}">${result.code ? 'HTTP ' + result.code : 'Error'}</span>`;
    } else {
        dot.innerHTML = '<span class="block w-2 h-2 rounded-full border border-slate-300 dark:border-slate-600"></span>';
        meta.innerHTML = '';
    }
}

function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
function escapeAttr(s) { return escapeHtml(s); }

async function checkOne(monitorId) {
    const card = document.querySelector(`.monitor-card[data-monitor-id="${monitorId}"]`);
    if (!card) return;
    const rows = card.querySelectorAll('.monitor-url-row');
    rows.forEach(setRowChecking);
    try {
        const res = await api('/api/monitors', { action: 'check', monitor_id: monitorId });
        if (res.data && res.data.results) {
            res.data.results.forEach(r => {
                const row = card.querySelector(`.monitor-url-row[data-url-id="${r.id}"]`);
                if (row) applyRowResult(row, r);
            });
        }
        updateStats();
    } catch (e) {}
}

async function checkAll() {
    const cards = document.querySelectorAll('.monitor-card');
    const btn = document.getElementById('check-all-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Checking…';

    // Run each monitor's checks in parallel
    await Promise.all(Array.from(cards).map(card => checkOne(card.dataset.monitorId)));
    updateStats();

    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Check All';
    toast('Checks complete', 'success');
}

function updateStats() {
    let up = 0, down = 0, unknown = 0;
    document.querySelectorAll('.monitor-url-row .url-status-dot span').forEach(dot => {
        const cls = dot.className;
        if (cls.includes('emerald')) up++;
        else if (cls.includes('rose')) down++;
        else unknown++;
    });
    const upEl = document.getElementById('stat-up');
    const downEl = document.getElementById('stat-down');
    const unkEl = document.getElementById('stat-unknown');
    if (upEl) upEl.textContent = up;
    if (downEl) downEl.textContent = down;
    if (unkEl) unkEl.textContent = unknown;
}
</script>

<?php layout_end(); ?>
