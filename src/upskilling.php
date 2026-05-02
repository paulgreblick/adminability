<?php
/**
 * Upskilling — shared list of learning links (videos, articles).
 */

$page_title = 'Upskilling';
$current_page = 'upskilling';
require_once __DIR__ . '/includes/layout.php';

$people = $pdo->query("SELECT id, first_name, email FROM users WHERE is_active = 1 ORDER BY id")->fetchAll();

$personFilter = $_GET['person'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($personFilter === 'none') {
    $where[] = 'u.assigned_to IS NULL';
} elseif ($personFilter !== '' && ctype_digit($personFilter)) {
    $where[] = 'u.assigned_to = ?';
    $params[] = (int)$personFilter;
}
if (in_array($statusFilter, ['unwatched', 'watching', 'watched'], true)) {
    $where[] = 'u.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT u.*,
           c.first_name as creator_first,
           a.first_name as assignee_first
    FROM upskilling_items u
    LEFT JOIN users c ON u.created_by = c.id
    LEFT JOIN users a ON u.assigned_to = a.id
    $whereSql
    ORDER BY
        CASE u.status WHEN 'watching' THEN 0 WHEN 'unwatched' THEN 1 WHEN 'watched' THEN 2 END,
        u.created_at DESC,
        u.id DESC
");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Counts for the status filter pills (respect person filter, ignore status)
$personOnlyWhere = '';
$personOnlyParams = [];
if ($personFilter === 'none') {
    $personOnlyWhere = 'WHERE assigned_to IS NULL';
} elseif ($personFilter !== '' && ctype_digit($personFilter)) {
    $personOnlyWhere = 'WHERE assigned_to = ?';
    $personOnlyParams[] = (int)$personFilter;
}
$countStmt = $pdo->prepare("SELECT status, COUNT(*) FROM upskilling_items $personOnlyWhere GROUP BY status");
$countStmt->execute($personOnlyParams);
$statusCounts = ['unwatched' => 0, 'watching' => 0, 'watched' => 0];
foreach ($countStmt->fetchAll(PDO::FETCH_NUM) as [$s, $c]) {
    if (isset($statusCounts[$s])) $statusCounts[$s] = (int)$c;
}
$totalCount = array_sum($statusCounts);

$avatarColor = function ($firstName) {
    $initial = strtoupper(substr($firstName ?? '?', 0, 1));
    return $initial === 'P' ? 'bg-indigo-500' : ($initial === 'A' ? 'bg-rose-500' : 'bg-slate-500');
};

$statusPill = function ($status) {
    return match ($status) {
        'watching' => ['Watching', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        'watched'  => ['Watched',  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'],
        default    => ['Unwatched', 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
    };
};

$domainOf = function ($url) {
    $host = parse_url($url, PHP_URL_HOST) ?: '';
    return preg_replace('/^www\./i', '', $host);
};

// Build filter URL preserving the other filter
$filterUrl = function ($personVal, $statusVal) use ($personFilter, $statusFilter) {
    $p = $personVal === '__keep__' ? $personFilter : $personVal;
    $s = $statusVal === '__keep__' ? $statusFilter : $statusVal;
    $q = [];
    if ($p !== '') $q['person'] = $p;
    if ($s !== '') $q['status'] = $s;
    return '/upskilling' . ($q ? '?' . http_build_query($q) : '');
};

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Upskilling</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Videos, articles, and other learning links to come back to</p>
    </div>
</div>

<!-- Filters: Person -->
<div class="flex items-center gap-1 flex-wrap mb-2">
    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-2">Who:</span>
    <?php
    $personFilters = [['', 'All']];
    foreach ($people as $p) $personFilters[] = [(string)$p['id'], $p['first_name']];
    $personFilters[] = ['none', 'Unassigned'];
    foreach ($personFilters as [$val, $label]):
        $active = ($personFilter === $val);
    ?>
        <a href="<?= htmlspecialchars($filterUrl($val, '__keep__')) ?>" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $active
            ? 'bg-indigo-500 text-white'
            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Filters: Status -->
<div class="flex items-center gap-1 flex-wrap mb-4">
    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-2">Status:</span>
    <?php
    $statusFilters = [
        ['',          'All',       $totalCount],
        ['unwatched', 'Unwatched', $statusCounts['unwatched']],
        ['watching',  'Watching',  $statusCounts['watching']],
        ['watched',   'Watched',   $statusCounts['watched']],
    ];
    foreach ($statusFilters as [$val, $label, $count]):
        $active = ($statusFilter === $val);
    ?>
        <a href="<?= htmlspecialchars($filterUrl('__keep__', $val)) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $active
            ? 'bg-indigo-500 text-white'
            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <?= htmlspecialchars($label) ?>
            <span class="text-xs <?= $active ? 'text-indigo-100' : 'text-slate-400 dark:text-slate-500' ?>"><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Add new -->
<div class="card p-4 mb-4">
    <form onsubmit="addItem(event)" class="grid gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" id="new-item-url" placeholder="Paste a YouTube URL or any link…" required class="flex-1 min-w-[240px]">
            <select id="new-item-person" class="!w-auto">
                <option value="">Unassigned</option>
                <?php foreach ($people as $p): ?>
                    <option value="<?= $p['id'] ?>"<?= (ctype_digit($personFilter) && (int)$personFilter === (int)$p['id']) ? ' selected' : '' ?>><?= htmlspecialchars($p['first_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Add</button>
        </div>
        <input type="text" id="new-item-title" placeholder="Title (optional — auto-fetched for YouTube)" class="w-full">
    </form>
</div>

<!-- Items -->
<?php if (empty($items)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 000 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 000-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Nothing here yet</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Drop a YouTube link (or any URL) above to save it for later.</p>
    </div>
<?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($items as $item):
            [$statusLabel, $statusClass] = $statusPill($item['status']);
            $thumb = $item['youtube_id'] ? "https://i.ytimg.com/vi/{$item['youtube_id']}/hqdefault.jpg" : null;
            $displayTitle = trim($item['title'] ?? '') !== '' ? $item['title'] : $item['url'];
        ?>
            <div class="card overflow-hidden flex flex-col upskilling-card" data-id="<?= $item['id'] ?>">
                <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener" class="block relative bg-slate-100 dark:bg-slate-800 aspect-video overflow-hidden group/thumb">
                    <?php if ($thumb): ?>
                        <img src="<?= htmlspecialchars($thumb) ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover/thumb:bg-black/30 transition-colors">
                            <svg class="w-12 h-12 text-white opacity-0 group-hover/thumb:opacity-100 transition-opacity drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                        </div>
                    <?php endif; ?>
                    <span class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium <?= $statusClass ?> shadow-sm">
                        <?= $statusLabel ?>
                    </span>
                </a>
                <div class="p-3 flex-1 flex flex-col gap-2">
                    <div class="flex items-start gap-2">
                        <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener" class="flex-1 min-w-0 group/title">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover/title:text-indigo-600 dark:group-hover/title:text-indigo-400 transition-colors">
                                <?= htmlspecialchars($displayTitle) ?>
                            </h3>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 truncate">
                                <?= htmlspecialchars($domainOf($item['url'])) ?>
                            </div>
                        </a>
                    </div>
                    <?php if (!empty($item['notes'])): ?>
                        <div class="text-xs text-slate-600 dark:text-slate-300 line-clamp-3 whitespace-pre-line"><?= htmlspecialchars($item['notes']) ?></div>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <?php if (!empty($item['assignee_first'])): ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium <?= $avatarColor($item['assignee_first']) ?> text-white">
                                    <?= htmlspecialchars($item['assignee_first']) ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    Unassigned
                                </span>
                            <?php endif; ?>
                            <span class="text-[11px] text-slate-400 dark:text-slate-500">added by <?= htmlspecialchars($item['creator_first'] ?? '?') ?></span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <button type="button" onclick="cycleStatus(<?= $item['id'] ?>, '<?= $item['status'] ?>')" class="p-1.5 rounded text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" title="Cycle status">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            <button type="button" onclick="toggleEdit(<?= $item['id'] ?>)" class="p-1.5 rounded text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button type="button" onclick="deleteItem(<?= $item['id'] ?>)" class="p-1.5 rounded text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inline edit panel (hidden by default) -->
                <div class="edit-panel hidden p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                    <div class="grid gap-2">
                        <label class="block">
                            <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">URL</span>
                            <input type="text" class="edit-url w-full !text-sm" value="<?= htmlspecialchars($item['url']) ?>">
                        </label>
                        <label class="block">
                            <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Title</span>
                            <input type="text" class="edit-title w-full !text-sm" value="<?= htmlspecialchars($item['title'] ?? '') ?>">
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Person</span>
                                <select class="edit-person w-full !text-sm">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($people as $p): ?>
                                        <option value="<?= $p['id'] ?>"<?= ((int)$item['assigned_to'] === (int)$p['id']) ? ' selected' : '' ?>><?= htmlspecialchars($p['first_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Status</span>
                                <select class="edit-status w-full !text-sm">
                                    <?php foreach (['unwatched' => 'Unwatched', 'watching' => 'Watching', 'watched' => 'Watched'] as $sv => $sl): ?>
                                        <option value="<?= $sv ?>"<?= $item['status'] === $sv ? ' selected' : '' ?>><?= $sl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <label class="block">
                            <span class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</span>
                            <textarea class="edit-notes w-full !text-sm" rows="2" placeholder="Optional"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="saveEdit(<?= $item['id'] ?>)" class="btn-primary !py-1 !px-3 text-sm">Save</button>
                            <button type="button" onclick="toggleEdit(<?= $item['id'] ?>)" class="btn-ghost !py-1 !px-3 text-sm">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
const STATUS_CYCLE = { unwatched: 'watching', watching: 'watched', watched: 'unwatched' };

async function addItem(e) {
    e.preventDefault();
    const url = document.getElementById('new-item-url').value.trim();
    const title = document.getElementById('new-item-title').value.trim();
    const personSel = document.getElementById('new-item-person');
    if (!url) return;
    try {
        await api('/api/upskilling', {
            action: 'create',
            url,
            title,
            assigned_to: personSel ? personSel.value : '',
        });
        location.reload();
    } catch (err) {}
}

async function cycleStatus(id, current) {
    const next = STATUS_CYCLE[current] || 'unwatched';
    try {
        await api('/api/upskilling', { action: 'set_status', id, status: next });
        location.reload();
    } catch (err) {}
}

function toggleEdit(id) {
    const card = document.querySelector(`.upskilling-card[data-id="${id}"]`);
    if (!card) return;
    card.querySelector('.edit-panel').classList.toggle('hidden');
}

async function saveEdit(id) {
    const card = document.querySelector(`.upskilling-card[data-id="${id}"]`);
    if (!card) return;
    const url    = card.querySelector('.edit-url').value.trim();
    const title  = card.querySelector('.edit-title').value.trim();
    const notes  = card.querySelector('.edit-notes').value.trim();
    const person = card.querySelector('.edit-person').value;
    const status = card.querySelector('.edit-status').value;
    if (!url) { toast('URL cannot be empty', 'error'); return; }
    try {
        await api('/api/upskilling', { action: 'update', id, url, title, notes, assigned_to: person });
        await api('/api/upskilling', { action: 'set_status', id, status });
        location.reload();
    } catch (err) {}
}

async function deleteItem(id) {
    if (!confirm('Delete this item?')) return;
    try {
        await api('/api/upskilling', { action: 'delete', id });
        location.reload();
    } catch (err) {}
}
</script>
