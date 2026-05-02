<?php
/**
 * Tab Opener — bundles of URLs that open all at once
 */

$page_title = 'Tab Opener';
$current_page = 'tabs';
require_once __DIR__ . '/includes/layout.php';

$userId = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';

// Filter can be: all, mine, unassigned, or user-<id>
$filterUserId = null;
if (preg_match('/^user-(\d+)$/', $filter, $m)) {
    $filterUserId = (int)$m[1];
}

$where = '';
$params = [];
if ($filter === 'mine') {
    $where = 'WHERE s.assigned_to = ? OR s.created_by = ?';
    $params = [$userId, $userId];
} elseif ($filter === 'unassigned') {
    $where = 'WHERE s.assigned_to IS NULL';
} elseif ($filterUserId) {
    $where = 'WHERE s.assigned_to = ?';
    $params = [$filterUserId];
}

$stmt = $pdo->prepare("
    SELECT s.*, u.first_name as assignee_first
    FROM tab_sets s
    LEFT JOIN users u ON s.assigned_to = u.id
    $where
    ORDER BY s.sort_order, s.name
");
$stmt->execute($params);
$sets = $stmt->fetchAll();

// Bulk-fetch URLs
$urlsBySet = [];
if ($sets) {
    $ids = array_column($sets, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $urlStmt = $pdo->prepare("SELECT * FROM tab_set_urls WHERE set_id IN ($placeholders) ORDER BY set_id, sort_order");
    $urlStmt->execute($ids);
    foreach ($urlStmt->fetchAll() as $u) {
        $urlsBySet[$u['set_id']][] = $u;
    }
}

$users = $pdo->query("SELECT id, first_name, name FROM users WHERE is_active = 1 ORDER BY first_name")->fetchAll();

$counts = [
    'all'        => (int)$pdo->query("SELECT COUNT(*) FROM tab_sets")->fetchColumn(),
    'unassigned' => (int)$pdo->query("SELECT COUNT(*) FROM tab_sets WHERE assigned_to IS NULL")->fetchColumn(),
];
$cMine = $pdo->prepare("SELECT COUNT(*) FROM tab_sets WHERE assigned_to = ? OR created_by = ?");
$cMine->execute([$userId, $userId]);
$counts['mine'] = (int)$cMine->fetchColumn();

// Per-user counts (assigned_to)
$userCounts = [];
$cUser = $pdo->prepare("SELECT COUNT(*) FROM tab_sets WHERE assigned_to = ?");
foreach ($users as $u) {
    $cUser->execute([$u['id']]);
    $userCounts[$u['id']] = (int)$cUser->fetchColumn();
}

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Tab Opener</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Open a bundle of URLs in new tabs with one click</p>
    </div>
    <button data-shortcut="new" onclick="openNewSet()" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New Set
    </button>
</div>

<!-- Filters -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="?filter=all" class="filter-pill <?= $filter === 'all' ? 'filter-pill-active' : '' ?>">All <span class="ml-1.5 text-xs opacity-60"><?= $counts['all'] ?></span></a>
    <a href="?filter=mine" class="filter-pill <?= $filter === 'mine' ? 'filter-pill-active' : '' ?>">Mine <span class="ml-1.5 text-xs opacity-60"><?= $counts['mine'] ?></span></a>
    <?php foreach ($users as $u):
        $uid = (int)$u['id'];
        if ($uid === (int)$userId) continue; // "Mine" already covers the current user
        $label = $u['first_name'] ?: $u['name'];
        $pillFilter = 'user-' . $uid;
    ?>
        <a href="?filter=<?= $pillFilter ?>" class="filter-pill <?= $filter === $pillFilter ? 'filter-pill-active' : '' ?>"><?= htmlspecialchars($label) ?>'s <span class="ml-1.5 text-xs opacity-60"><?= $userCounts[$uid] ?></span></a>
    <?php endforeach; ?>
    <a href="?filter=unassigned" class="filter-pill <?= $filter === 'unassigned' ? 'filter-pill-active' : '' ?>">Unassigned <span class="ml-1.5 text-xs opacity-60"><?= $counts['unassigned'] ?></span></a>
</div>

<!-- Browser warning banner -->
<div class="hidden card border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 mb-4 text-sm text-amber-800 dark:text-amber-200" id="popup-warning">
    <strong>Heads up:</strong> Your browser blocked some popups. Allow popups for this site to open multiple tabs at once.
</div>

<!-- Sets grid -->
<?php if (empty($sets)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No tab sets yet</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create a set of URLs you want to open together with one click.</p>
        <button onclick="openNewSet()" class="btn-primary mt-4">New Set</button>
    </div>
<?php else: ?>
<div id="sets-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($sets as $set):
        $urls = $urlsBySet[$set['id']] ?? [];
        $count = count($urls);
    ?>
    <div class="card p-5 flex flex-col cursor-move" data-set-id="<?= $set['id'] ?>">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-<?= htmlspecialchars($set['color']) ?>-500 flex-shrink-0"></span>
                <h3 class="font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($set['name']) ?></h3>
            </div>
            <button onclick='editSet(<?= json_encode($set, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($urls, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-1 -m-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
        </div>

        <?php if ($set['description']): ?>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><?= htmlspecialchars($set['description']) ?></p>
        <?php endif; ?>

        <!-- URL list -->
        <ul class="space-y-1 mb-4 flex-1">
            <?php foreach (array_slice($urls, 0, 6) as $u):
                $host = parse_url($u['url'], PHP_URL_HOST) ?: $u['url'];
            ?>
            <li class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <span class="truncate" title="<?= htmlspecialchars($u['url']) ?>"><?= htmlspecialchars($u['label'] ?: $host) ?></span>
            </li>
            <?php endforeach; ?>
            <?php if ($count > 6): ?>
                <li class="text-xs text-slate-400 dark:text-slate-500 pl-5">+ <?= $count - 6 ?> more</li>
            <?php endif; ?>
        </ul>

        <!-- Footer with Open All button + meta -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
            <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span><?= $count ?> URL<?= $count === 1 ? '' : 's' ?></span>
                <?php if ($set['assignee_first']): ?>
                    <span>·</span>
                    <span><?= htmlspecialchars($set['assignee_first']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($count > 0): ?>
                <button onclick='openAll(<?= json_encode(array_column($urls, 'url')) ?>)' class="btn-primary !px-3 !py-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Open All
                </button>
            <?php else: ?>
                <span class="text-xs text-slate-400 italic">No URLs</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Set Modal (shared for create/edit) -->
<div id="set-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel max-w-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="set-modal-title">New Tab Set</h3>
            <button onclick="closeModal('set-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="set-form" onsubmit="submitSet(event)" class="p-5 space-y-4">
            <input type="hidden" name="set_id" id="set-id" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" name="name" id="set-name" required placeholder="e.g., Morning Routine">
                </div>
                <div>
                    <label class="form-label">Color</label>
                    <select name="color" id="set-color">
                        <option value="indigo">Indigo</option>
                        <option value="emerald">Emerald</option>
                        <option value="amber">Amber</option>
                        <option value="rose">Rose</option>
                        <option value="blue">Blue</option>
                        <option value="purple">Purple</option>
                        <option value="orange">Orange</option>
                        <option value="slate">Slate</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" name="description" id="set-description" placeholder="What is this for?">
            </div>

            <div>
                <label class="form-label">Assigned to <span class="text-slate-400 font-normal">(optional)</span></label>
                <select name="assigned_to" id="set-assignee">
                    <option value="">Anyone</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] ?: $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <button type="button" id="set-delete-btn" onclick="deleteSet()" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hidden">Delete</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeModal('set-modal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" id="set-submit-btn">Create Set</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function urlRowHtml(url = '', label = '') {
    return `
        <div class="flex items-center gap-2 url-row">
            <span class="drag-handle cursor-move p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 flex-shrink-0" title="Drag to reorder">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
            </span>
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

function openNewSet() {
    document.getElementById('set-modal-title').textContent = 'New Tab Set';
    document.getElementById('set-form').reset();
    document.getElementById('set-id').value = '';
    document.getElementById('set-submit-btn').textContent = 'Create Set';
    document.getElementById('set-delete-btn').classList.add('hidden');
    document.getElementById('url-rows').innerHTML = '';
    addUrlRow();
    addUrlRow();
    addUrlRow();
    openModal('set-modal');
    wireUrlRowSortable();
}

function editSet(set, urls) {
    document.getElementById('set-modal-title').textContent = 'Edit Tab Set';
    document.getElementById('set-id').value = set.id;
    document.getElementById('set-name').value = set.name;
    document.getElementById('set-description').value = set.description || '';
    document.getElementById('set-color').value = set.color;
    document.getElementById('set-assignee').value = set.assigned_to || '';
    document.getElementById('set-submit-btn').textContent = 'Save Changes';
    document.getElementById('set-delete-btn').classList.remove('hidden');
    document.getElementById('url-rows').innerHTML = '';
    if (urls && urls.length > 0) {
        urls.forEach(u => addUrlRow(u.url, u.label || ''));
    } else {
        addUrlRow();
    }
    openModal('set-modal');
    wireUrlRowSortable();
}

async function submitSet(e) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const isEdit = !!fd.get('set_id');
    fd.append('action', isEdit ? 'update_set' : 'create_set');

    // Build payload (handle multi-value urls[] and labels[])
    const data = {};
    const arrayKeys = ['urls', 'labels'];
    for (const [key, val] of fd.entries()) {
        if (key.endsWith('[]')) {
            const k = key;
            if (!data[k]) data[k] = [];
            data[k].push(val);
        } else {
            data[key] = val;
        }
    }

    // Manually serialize because URLSearchParams handles arrays differently
    const body = new URLSearchParams();
    body.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    Object.entries(data).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach(item => body.append(k, item));
        else if (v !== null && v !== undefined) body.append(k, v);
    });

    try {
        const res = await fetch('/api/tabs', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        if (!res.ok || json.success === false) throw new Error(json.error || 'Save failed');
        toast(isEdit ? 'Set updated' : 'Set created', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (err) {
        toast(err.message, 'error');
    }
}

async function deleteSet() {
    const id = document.getElementById('set-id').value;
    if (!id || !confirm('Delete this tab set?')) return;
    try {
        await api('/api/tabs', { action: 'delete_set', set_id: id });
        toast('Set deleted', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

// --- Drag and drop ---
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('sets-grid');
    if (grid && window.Sortable) {
        Sortable.create(grid, {
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                const ids = Array.from(grid.querySelectorAll('[data-set-id]')).map(el => el.dataset.setId);
                try {
                    await api('/api/tabs', { action: 'reorder_sets', ids });
                    toast('Order saved', 'success');
                } catch (e) {}
            }
        });
    }
});

// Called when opening the modal — wires SortableJS on URL rows
function wireUrlRowSortable() {
    const rows = document.getElementById('url-rows');
    if (rows && window.Sortable) {
        Sortable.create(rows, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'opacity-40',
        });
    }
}

function openAll(urls) {
    let blocked = 0;
    urls.forEach((url, i) => {
        // Slight stagger to reduce popup blocker triggers
        setTimeout(() => {
            const w = window.open(url, '_blank');
            if (!w || w.closed) blocked++;
            if (i === urls.length - 1 && blocked > 0) {
                document.getElementById('popup-warning').classList.remove('hidden');
            }
        }, i * 50);
    });
}
</script>

<?php layout_end(); ?>
