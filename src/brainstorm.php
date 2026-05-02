<?php
/**
 * Brainstorm — shared quick list for Paul + Anita
 */

$page_title = 'Brainstorm';
$current_page = 'brainstorm';
require_once __DIR__ . '/includes/layout.php';

// People for assignment / filtering
$people = $pdo->query("SELECT id, first_name, email FROM users WHERE is_active = 1 ORDER BY id")->fetchAll();

// Person filter: accepts user id, or 'none' (unassigned), or '' (all)
$personFilter = $_GET['person'] ?? '';
$filterLabel = 'All';
$where = '';
$params = [];
if ($personFilter === 'none') {
    $where = 'WHERE b.assigned_to IS NULL';
    $filterLabel = 'Unassigned';
} elseif ($personFilter !== '' && ctype_digit($personFilter)) {
    $where = 'WHERE b.assigned_to = ?';
    $params[] = (int)$personFilter;
    foreach ($people as $p) {
        if ((int)$p['id'] === (int)$personFilter) { $filterLabel = $p['first_name']; break; }
    }
}

$stmt = $pdo->prepare("
    SELECT b.*,
           u.first_name as creator_first,
           a.first_name as assignee_first
    FROM brainstorm_items b
    LEFT JOIN users u ON b.created_by = u.id
    LEFT JOIN users a ON b.assigned_to = a.id
    $where
    ORDER BY is_done, sort_order, id
");
$stmt->execute($params);
$items = $stmt->fetchAll();

$activeCount = count(array_filter($items, fn($i) => !$i['is_done']));
$doneCount = count($items) - $activeCount;

// Fetch all steps for visible items in one query, then group by brainstorm_id
$stepsByItem = [];
if (!empty($items)) {
    $itemIds = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stepStmt = $pdo->prepare("
        SELECT id, brainstorm_id, text, is_done, sort_order
        FROM brainstorm_steps
        WHERE brainstorm_id IN ($placeholders)
        ORDER BY sort_order, id
    ");
    $stepStmt->execute($itemIds);
    foreach ($stepStmt->fetchAll() as $s) {
        $stepsByItem[(int)$s['brainstorm_id']][] = $s;
    }
}

// Avatar color helper (matches sidebar)
$avatarColor = function ($firstName) {
    $initial = strtoupper(substr($firstName ?? '?', 0, 1));
    return $initial === 'P' ? 'bg-indigo-500' : ($initial === 'A' ? 'bg-rose-500' : 'bg-slate-500');
};

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-4 no-print">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Brainstorm</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">A quick shared list for ideas and small to-dos</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer select-none">
            <input type="checkbox" id="hide-completed-toggle" class="rounded border-slate-300 dark:border-slate-600 text-indigo-500 focus:ring-indigo-500">
            <span>Hide completed<?= $doneCount ? " ($doneCount)" : '' ?></span>
        </label>
        <button type="button" onclick="window.print()" class="btn-ghost" title="Print this list">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <?php if ($doneCount > 0): ?>
            <button onclick="clearDone()" class="btn-ghost text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400">Clear <?= $doneCount ?> done</button>
        <?php endif; ?>
    </div>
</div>

<!-- Person filter -->
<div class="flex items-center gap-1 flex-wrap mb-4 no-print">
    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-2">Show:</span>
    <?php
    $filters = [['', 'All']];
    foreach ($people as $p) $filters[] = [(string)$p['id'], $p['first_name']];
    $filters[] = ['none', 'Unassigned'];
    foreach ($filters as [$val, $label]):
        $active = ($personFilter === $val) || ($personFilter === '' && $val === '');
        $href = $val === '' ? '/brainstorm' : '/brainstorm?person=' . urlencode($val);
    ?>
        <a href="<?= $href ?>" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $active
            ? 'bg-indigo-500 text-white'
            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Print-only header (hidden on screen) -->
<div class="hidden print:block mb-4">
    <h1 class="text-xl font-semibold">Brainstorm — <?= htmlspecialchars($filterLabel) ?></h1>
    <p class="text-xs text-slate-600">Printed <?= date('M j, Y g:i a') ?></p>
</div>

<!-- Add new -->
<div class="card p-4 mb-4 no-print">
    <form onsubmit="addItem(event)" class="flex flex-wrap items-center gap-2">
        <input type="text" id="new-item-text" placeholder="Add something to the list…" autofocus required class="flex-1 min-w-[200px]">
        <select id="new-item-person" class="!w-auto">
            <option value="">Unassigned</option>
            <?php foreach ($people as $p): ?>
                <option value="<?= $p['id'] ?>"<?= (ctype_digit($personFilter) && (int)$personFilter === (int)$p['id']) ? ' selected' : '' ?>><?= htmlspecialchars($p['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Add</button>
    </form>
</div>

<!-- Items -->
<?php if (empty($items)): ?>
    <div class="card p-12 text-center no-print">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Nothing here yet</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Start by adding an idea or quick to-do above.</p>
    </div>
    <div class="hidden print:block text-center text-sm text-slate-600 py-8">No items.</div>
<?php else: ?>
    <div class="card overflow-hidden print-plain">
        <ul id="brainstorm-list">
            <?php foreach ($items as $item):
                $steps     = $stepsByItem[(int)$item['id']] ?? [];
                $stepCount = count($steps);
                $stepDone  = count(array_filter($steps, fn($s) => $s['is_done']));
            ?>
                <li class="brainstorm-item group border-b border-slate-200 dark:border-slate-800 last:border-b-0"
                    data-id="<?= $item['id'] ?>"
                    data-done="<?= (int)$item['is_done'] ?>">
                    <div class="flex items-start gap-2 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <span class="drag-handle cursor-move flex-shrink-0 p-1 mt-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity no-print" title="Drag to reorder">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                        </span>
                        <!-- Steps toggle: prominent left-side indicator, shown only when steps exist -->
                        <button type="button" onclick="toggleSteps(<?= $item['id'] ?>)"
                                class="steps-toggle flex-shrink-0 mt-0.5 inline-flex items-center gap-1 px-1.5 h-5 rounded text-[11px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-900 transition-colors no-print <?= $stepCount > 0 ? '' : 'hidden' ?>"
                                title="Show steps">
                            <svg class="steps-chevron w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            <span class="step-counter"><?= $stepDone ?>/<?= $stepCount ?></span>
                        </button>
                        <button onclick="toggleItem(<?= $item['id'] ?>, this)" class="flex-shrink-0 mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors <?= $item['is_done'] ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500' ?>">
                            <?php if ($item['is_done']): ?>
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?php endif; ?>
                        </button>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="item-text text-sm <?= $item['is_done'] ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200' ?>" ondblclick="startEdit(this, <?= $item['id'] ?>)" title="Double-click to edit">
                                    <?= htmlspecialchars($item['text']) ?>
                                </span>
                                <?php if (!empty($item['timing'])): ?>
                                    <span class="timing-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= htmlspecialchars($item['timing']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($item['assignee_first'])): ?>
                                    <span class="assignee-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium <?= $avatarColor($item['assignee_first']) ?> text-white">
                                        <?= htmlspecialchars($item['assignee_first']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="hidden print:inline text-[11px] text-slate-700 step-counter-print<?= $stepCount === 0 ? ' print:hidden' : '' ?>"><?= $stepDone ?>/<?= $stepCount ?> steps</span>
                                <!-- Inline Edit + Delete buttons (close to content, not pushed to far right) -->
                                <span class="row-actions inline-flex items-center gap-0.5 ml-auto pl-2 no-print">
                                    <button type="button" onclick="toggleDetails(<?= $item['id'] ?>)" class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" title="Edit details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" onclick="deleteItem(<?= $item['id'] ?>, this)" class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 opacity-0 group-hover:opacity-100 transition-opacity" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            </div>
                            <?php if (!empty($item['notes'])): ?>
                                <div class="notes-preview mt-1 text-xs text-slate-500 dark:text-slate-400 truncate print:whitespace-normal print:text-slate-700" title="<?= htmlspecialchars($item['notes']) ?>">
                                    <?= nl2br(htmlspecialchars($item['notes'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Steps panel: always rendered (hidden by default). JS reveals it when toggled. -->
                    <div class="steps-panel hidden px-4 pb-3 pt-2 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 print:block print:bg-transparent<?= $stepCount === 0 ? ' print:hidden' : '' ?>" data-parent-id="<?= $item['id'] ?>">
                        <ul class="steps-list ml-8 space-y-1">
                            <?php foreach ($steps as $step): ?>
                                <li class="step-item group/step flex items-center gap-2 py-0.5" data-step-id="<?= $step['id'] ?>" data-done="<?= (int)$step['is_done'] ?>">
                                    <span class="step-drag-handle cursor-move flex-shrink-0 p-0.5 text-slate-400 opacity-0 group-hover/step:opacity-100 transition-opacity no-print" title="Drag to reorder">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                                    </span>
                                    <button type="button" onclick="toggleStep(<?= $step['id'] ?>, this)" class="step-check flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors <?= $step['is_done'] ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500' ?>">
                                        <?php if ($step['is_done']): ?>
                                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <?php endif; ?>
                                    </button>
                                    <span class="step-text flex-1 text-sm <?= $step['is_done'] ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200' ?>" ondblclick="startStepEdit(this, <?= $step['id'] ?>)" title="Double-click to edit">
                                        <?= htmlspecialchars($step['text']) ?>
                                    </span>
                                    <button type="button" onclick="deleteStep(<?= $step['id'] ?>, this)" class="p-0.5 text-slate-400 hover:text-rose-500 flex-shrink-0 opacity-0 group-hover/step:opacity-100 transition-opacity no-print" title="Delete step">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <form onsubmit="addStep(event, <?= $item['id'] ?>)" class="ml-8 mt-2 flex items-center gap-2 no-print">
                            <input type="text" class="add-step-input flex-1 !py-1 !text-sm" placeholder="+ Add step">
                            <button type="submit" class="btn-ghost !py-1 !px-2 !text-xs">Add</button>
                        </form>
                    </div>

                    <!-- Inline editor (collapsed by default) -->
                    <div class="details-panel hidden px-4 pb-4 pt-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 no-print">
                        <div class="grid gap-3 max-w-2xl">
                            <label class="block">
                                <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Item</span>
                                <input type="text" class="detail-text w-full" value="<?= htmlspecialchars($item['text']) ?>">
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Person</span>
                                    <select class="detail-person w-full">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($people as $p): ?>
                                            <option value="<?= $p['id'] ?>"<?= ((int)$item['assigned_to'] === (int)$p['id']) ? ' selected' : '' ?>><?= htmlspecialchars($p['first_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Timing <span class="text-slate-400 font-normal">(e.g. ASAP, complete by Fri)</span></span>
                                    <input type="text" class="detail-timing w-full" value="<?= htmlspecialchars($item['timing'] ?? '') ?>" placeholder="Optional">
                                </label>
                            </div>
                            <label class="block">
                                <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</span>
                                <textarea class="detail-notes w-full" rows="3" placeholder="Optional — add more detail"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                            </label>
                            <div class="add-first-step-block pt-2 border-t border-slate-200 dark:border-slate-800<?= $stepCount === 0 ? '' : ' hidden' ?>">
                                <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Add a first step <span class="text-slate-400 font-normal">(optional — a step list will appear once you add one)</span></span>
                                <form onsubmit="addStep(event, <?= $item['id'] ?>)" class="flex items-center gap-2">
                                    <input type="text" class="add-step-input flex-1 !py-1 !text-sm" placeholder="e.g. Research vendors">
                                    <button type="submit" class="btn-ghost !py-1 !px-3 !text-sm">Add step</button>
                                </form>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="saveDetails(<?= $item['id'] ?>, this)" class="btn-primary !py-1.5 !px-3 text-sm">Save</button>
                                <button type="button" onclick="toggleDetails(<?= $item['id'] ?>)" class="btn-ghost !py-1.5 !px-3 text-sm">Cancel</button>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<script>
// ============================================================
// Cross-user polling — if Anita changes something while Paul
// has the page open, Paul's page reloads to show the update.
// ============================================================
(function() {
    const POLL_INTERVAL = 25000; // 25s
    const personFilter = new URLSearchParams(location.search).get('person') || '';
    let lastHash = null;
    let pollTimer = null;
    let inFlight = false;

    async function fetchHash() {
        try {
            // Use the original api() (not the wrapped one below) is fine —
            // state_hash is excluded from the wrapper's baseline-refresh branch.
            const res = await window.api('/api/brainstorm', { action: 'state_hash', person: personFilter });
            return res?.data?.hash ?? null;
        } catch (err) {
            return null;
        }
    }

    function isSafeToReload() {
        // Skip reload if user is mid-edit
        if (document.querySelector('.details-panel:not(.hidden)')) return false;
        // Skip if an inline input/textarea has focus
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) return false;
        // Skip if Sortable is currently dragging
        if (document.querySelector('.sortable-chosen')) return false;
        return true;
    }

    async function poll() {
        if (inFlight) return;
        inFlight = true;
        const hash = await fetchHash();
        inFlight = false;
        if (hash === null) return;
        if (lastHash === null) { lastHash = hash; return; }
        if (hash === lastHash) return;

        if (isSafeToReload()) {
            // Preserve which steps-panels are open across the reload
            const openSteps = Array.from(document.querySelectorAll('.steps-panel:not(.hidden)'))
                .map(p => p.dataset.parentId).filter(Boolean);
            try { sessionStorage.setItem('brainstorm_open_steps', JSON.stringify(openSteps)); } catch {}
            location.reload();
        }
        // If not safe, leave lastHash unchanged — we'll retry next tick
    }

    // Expose a way for local mutations to update the baseline silently
    window.__bsRefreshHash = async () => {
        const h = await fetchHash();
        if (h !== null) lastHash = h;
    };

    // Pause polling when tab is hidden; resume (and catch up) on show
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        } else {
            poll();
            if (!pollTimer) pollTimer = setInterval(poll, POLL_INTERVAL);
        }
    });

    // Kick off polling
    poll();
    pollTimer = setInterval(poll, POLL_INTERVAL);

    // Wrap api() so any successful local mutation (except state_hash itself)
    // silently bumps our baseline hash — prevents our own change from
    // triggering a "something changed, reload!" on the next poll.
    const rawApi = window.api;
    window.api = async function(url, data) {
        const res = await rawApi(url, data);
        if (res && res.success && data && data.action && data.action !== 'state_hash') {
            window.__bsRefreshHash();
        }
        return res;
    };

    // Restore open steps-panels after a poll-triggered reload
    document.addEventListener('DOMContentLoaded', () => {
        try {
            const saved = sessionStorage.getItem('brainstorm_open_steps');
            if (!saved) return;
            sessionStorage.removeItem('brainstorm_open_steps');
            JSON.parse(saved).forEach(id => {
                const li = document.querySelector(`li[data-id="${id}"]`);
                if (!li) return;
                const panel = li.querySelector('.steps-panel');
                if (!panel || !panel.classList.contains('hidden')) return;
                panel.classList.remove('hidden');
                const chev = li.querySelector('.steps-chevron');
                if (chev) chev.style.transform = 'rotate(90deg)';
            });
        } catch {}
    });
})();

async function addItem(e) {
    e.preventDefault();
    const input = document.getElementById('new-item-text');
    const personSel = document.getElementById('new-item-person');
    const text = input.value.trim();
    if (!text) return;
    try {
        await api('/api/brainstorm', { action: 'create', text, assigned_to: personSel ? personSel.value : '' });
        location.reload();
    } catch (err) {}
}

async function toggleItem(id, btn) {
    try {
        const res = await api('/api/brainstorm', { action: 'toggle', id });
        const li = btn.closest('li');
        const span = li.querySelector('.item-text');
        const done = res.data.is_done;
        li.dataset.done = done ? '1' : '0';
        btn.className = 'flex-shrink-0 mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors ' +
            (done ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500');
        btn.innerHTML = done ? '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : '';
        span.className = 'item-text text-sm ' + (done ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200');
        applyHideCompleted();
    } catch (err) {}
}

async function deleteItem(id, btn) {
    if (!confirm('Delete this item?')) return;
    try {
        await api('/api/brainstorm', { action: 'delete', id });
        const li = btn.closest('li');
        li.style.opacity = '0';
        setTimeout(() => li.remove(), 150);
    } catch (err) {}
}

async function clearDone() {
    if (!confirm('Clear all done items?')) return;
    try {
        await api('/api/brainstorm', { action: 'clear_done' });
        location.reload();
    } catch (err) {}
}

function startEdit(span, id) {
    const orig = span.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.value = orig;
    input.className = 'item-edit !py-1 !text-sm';
    span.replaceWith(input);
    input.focus();
    input.select();

    const finish = async (save) => {
        const newText = input.value.trim();
        if (save && newText && newText !== orig) {
            try { await api('/api/brainstorm', { action: 'update', id, text: newText }); } catch (err) { input.replaceWith(span); return; }
            span.textContent = newText;
        }
        input.replaceWith(span);
    };

    input.addEventListener('blur', () => finish(true));
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); finish(true); }
        else if (e.key === 'Escape') finish(false);
    });
}

function toggleDetails(id) {
    const li = document.querySelector(`li[data-id="${id}"]`);
    if (!li) return;
    const panel = li.querySelector('.details-panel');
    panel.classList.toggle('hidden');
    if (!panel.classList.contains('hidden')) {
        panel.querySelector('.detail-text')?.focus();
    }
}

async function saveDetails(id, btn) {
    const li = btn.closest('li');
    const text   = li.querySelector('.detail-text').value.trim();
    const timing = li.querySelector('.detail-timing').value.trim();
    const notes  = li.querySelector('.detail-notes').value;
    const person = li.querySelector('.detail-person').value;
    if (!text) { alert('Item text cannot be empty'); return; }
    btn.disabled = true;
    try {
        await api('/api/brainstorm', { action: 'update', id, text, timing, notes, assigned_to: person });
        location.reload();
    } catch (err) {
        btn.disabled = false;
    }
}

function applyHideCompleted() {
    const hide = localStorage.getItem('brainstorm_hide_done') === '1';
    document.querySelectorAll('#brainstorm-list > li[data-done="1"]').forEach(li => {
        li.style.display = hide ? 'none' : '';
    });
}

// --- Step (sub-item) functions ---
function toggleSteps(id) {
    const li = document.querySelector(`li[data-id="${id}"]`);
    if (!li) return;
    const panel = li.querySelector('.steps-panel');
    if (!panel) return;
    panel.classList.toggle('hidden');
    const chevron = li.querySelector('.steps-chevron');
    if (chevron) chevron.style.transform = panel.classList.contains('hidden') ? '' : 'rotate(90deg)';
    // If we just opened it, focus the add-step input so the user can type straight away
    if (!panel.classList.contains('hidden')) {
        panel.querySelector('.add-step-input')?.focus();
    }
}

async function toggleStep(id, btn) {
    try {
        const res = await api('/api/brainstorm', { action: 'step_toggle', id });
        const li = btn.closest('.step-item');
        const span = li.querySelector('.step-text');
        const done = res.data.is_done;
        li.dataset.done = done ? '1' : '0';
        btn.className = 'step-check flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors ' +
            (done ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500');
        btn.innerHTML = done ? '<svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : '';
        span.className = 'step-text flex-1 text-sm ' + (done ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200');
        updateStepPill(li.closest('.brainstorm-item'));
    } catch (err) {}
}

async function deleteStep(id, btn) {
    if (!confirm('Delete this step?')) return;
    try {
        await api('/api/brainstorm', { action: 'step_delete', id });
        const stepLi = btn.closest('.step-item');
        const parentLi = stepLi.closest('.brainstorm-item');
        stepLi.remove();
        updateStepPill(parentLi);
    } catch (err) {}
}

function buildStepLi(step) {
    const done = step.is_done ? 1 : 0;
    const li = document.createElement('li');
    li.className = 'step-item group/step flex items-center gap-2 py-0.5';
    li.dataset.stepId = step.id;
    li.dataset.done = done;
    li.innerHTML = `
        <span class="step-drag-handle cursor-move flex-shrink-0 p-0.5 text-slate-400 opacity-0 group-hover/step:opacity-100 transition-opacity no-print" title="Drag to reorder">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
        </span>
        <button type="button" onclick="toggleStep(${step.id}, this)" class="step-check flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors ${done ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500'}"></button>
        <span class="step-text flex-1 text-sm text-slate-700 dark:text-slate-200" ondblclick="startStepEdit(this, ${step.id})" title="Double-click to edit"></span>
        <button type="button" onclick="deleteStep(${step.id}, this)" class="p-0.5 text-slate-400 hover:text-rose-500 flex-shrink-0 opacity-0 group-hover/step:opacity-100 transition-opacity no-print" title="Delete step">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    // textContent to avoid XSS in DOM injection
    li.querySelector('.step-text').textContent = step.text;
    return li;
}

async function addStep(e, brainstormId) {
    e.preventDefault();
    const form = e.target;
    const input = form.querySelector('.add-step-input');
    const text = input.value.trim();
    if (!text) return;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
        const res = await api('/api/brainstorm', { action: 'step_create', brainstorm_id: brainstormId, text });
        const parentLi = document.querySelector(`li[data-id="${brainstormId}"]`);
        if (!parentLi) { location.reload(); return; }
        const stepsPanel = parentLi.querySelector('.steps-panel');
        const stepsList = stepsPanel.querySelector('.steps-list');
        stepsList.appendChild(buildStepLi({ id: res.data.id, text, is_done: 0 }));

        // Reveal chevron pill if hidden
        const pillBtn = parentLi.querySelector('.steps-toggle');
        if (pillBtn) pillBtn.classList.remove('hidden');

        // Hide the Edit-panel "Add first step" block if it was visible
        parentLi.querySelector('.add-first-step-block')?.classList.add('hidden');

        // Make sure the steps panel is visible so the user sees their addition
        if (stepsPanel.classList.contains('hidden')) toggleSteps(brainstormId);

        updateStepPill(parentLi);
        ensureStepSortable(stepsList);

        // Reset input; keep focus so user can add another step immediately
        input.value = '';
        input.focus();
    } catch (err) {
    } finally {
        if (btn) btn.disabled = false;
    }
}

function startStepEdit(span, id) {
    const orig = span.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.value = orig;
    input.className = 'flex-1 !py-0.5 !text-sm';
    span.replaceWith(input);
    input.focus();
    input.select();

    const finish = async (save) => {
        const newText = input.value.trim();
        if (save && newText && newText !== orig) {
            try { await api('/api/brainstorm', { action: 'step_update', id, text: newText }); } catch (err) { input.replaceWith(span); return; }
            span.textContent = newText;
        }
        input.replaceWith(span);
    };
    input.addEventListener('blur', () => finish(true));
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); finish(true); }
        else if (e.key === 'Escape') finish(false);
    });
}

function updateStepPill(parentLi) {
    const pillBtn = parentLi.querySelector('.steps-toggle');
    if (!pillBtn) return;
    const total = parentLi.querySelectorAll('.step-item').length;
    const done  = parentLi.querySelectorAll('.step-item[data-done="1"]').length;
    const counter = pillBtn.querySelector('.step-counter');
    if (counter) counter.textContent = `${done}/${total}`;

    // If we dropped back to 0 steps, hide chevron and show the Edit-panel "Add first step" block again
    if (total === 0) {
        pillBtn.classList.add('hidden');
        parentLi.querySelector('.add-first-step-block')?.classList.remove('hidden');
        // Also collapse the (now empty) steps panel
        const panel = parentLi.querySelector('.steps-panel');
        if (panel) panel.classList.add('hidden');
    }
}

function ensureStepSortable(stepsList) {
    if (!window.Sortable || !stepsList || stepsList.dataset.sortableReady === '1') return;
    Sortable.create(stepsList, {
        handle: '.step-drag-handle',
        animation: 150,
        ghostClass: 'opacity-40',
        onEnd: async () => {
            const ids = Array.from(stepsList.querySelectorAll('[data-step-id]')).map(el => el.dataset.stepId);
            try {
                await api('/api/brainstorm', { action: 'step_reorder', ids });
            } catch (err) {}
        }
    });
    stepsList.dataset.sortableReady = '1';
}

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('hide-completed-toggle');
    if (toggle) {
        toggle.checked = localStorage.getItem('brainstorm_hide_done') === '1';
        toggle.addEventListener('change', () => {
            localStorage.setItem('brainstorm_hide_done', toggle.checked ? '1' : '0');
            applyHideCompleted();
        });
        applyHideCompleted();
    }

    const list = document.getElementById('brainstorm-list');
    if (list && window.Sortable) {
        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                const ids = Array.from(list.children).map(el => el.dataset.id).filter(Boolean);
                try {
                    await api('/api/brainstorm', { action: 'reorder', ids });
                } catch (err) {}
            }
        });
    }

    // Wire Sortable on every steps list (even empty ones — so added steps become draggable immediately)
    document.querySelectorAll('.steps-list').forEach(ensureStepSortable);
});
</script>

<?php layout_end(); ?>
