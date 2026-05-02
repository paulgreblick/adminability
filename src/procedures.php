<?php
/**
 * Procedures — list view grouped by subject
 */

$page_title = 'Procedures';
$current_page = 'procedures';
require_once __DIR__ . '/includes/layout.php';

$subjectFilter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$projectFilter = isset($_GET['project']) ? (int)$_GET['project'] : 0;

$where = [];
$params = [];
if ($subjectFilter) { $where[] = 'p.subject_id = ?'; $params[] = $subjectFilter; }
if ($projectFilter) { $where[] = 'p.project_id = ?'; $params[] = $projectFilter; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT p.*,
           s.name as subject_name, s.color as subject_color, s.slug as subject_slug,
           pr.name as project_name, pr.color as project_color,
           (SELECT COUNT(*) FROM procedure_steps st WHERE st.procedure_id = p.id) as step_count
    FROM procedures p
    LEFT JOIN procedure_subjects s ON p.subject_id = s.id
    LEFT JOIN projects pr ON p.project_id = pr.id
    $whereSql
    ORDER BY COALESCE(s.sort_order, 999999), s.name, p.sort_order, p.title
");
$stmt->execute($params);
$procedures = $stmt->fetchAll();

$subjects = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM procedures p WHERE p.subject_id = s.id) as count FROM procedure_subjects s ORDER BY sort_order, name")->fetchAll();
$projects = $pdo->query("SELECT id, name, color FROM projects WHERE status = 'active' ORDER BY name")->fetchAll();

// Group by subject
$grouped = [];
$unassigned = [];
foreach ($procedures as $p) {
    if ($p['subject_id']) {
        $grouped[$p['subject_id']]['subject'] = [
            'id' => $p['subject_id'],
            'name' => $p['subject_name'],
            'color' => $p['subject_color'],
        ];
        $grouped[$p['subject_id']]['procedures'][] = $p;
    } else {
        $unassigned[] = $p;
    }
}

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Procedures</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Step-by-step procedures, organized by subject</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="openModal('subjects-modal')" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Manage subjects
        </button>
        <button data-shortcut="new" onclick="openNewProcedure()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Procedure
        </button>
    </div>
</div>

<!-- Filters -->
<?php if ($subjects || $projects): ?>
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="/procedures" class="filter-pill <?= !$subjectFilter && !$projectFilter ? 'filter-pill-active' : '' ?>">All <span class="ml-1.5 text-xs opacity-60"><?= count($procedures) ?></span></a>
    <?php foreach ($subjects as $s): if (!$s['count']) continue; ?>
        <a href="?subject=<?= $s['id'] ?>" class="filter-pill <?= $subjectFilter == $s['id'] ? 'filter-pill-active' : '' ?>">
            <span class="w-1.5 h-1.5 rounded-full bg-<?= htmlspecialchars($s['color']) ?>-500 mr-1.5"></span>
            <?= htmlspecialchars($s['name']) ?>
            <span class="ml-1.5 text-xs opacity-60"><?= $s['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Procedures grouped -->
<?php if (empty($procedures)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6m-6 4h4"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No procedures yet</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create your first procedure to document a process.</p>
        <button onclick="openNewProcedure()" class="btn-primary mt-4">New Procedure</button>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($grouped as $g): ?>
            <section>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <span class="w-2 h-2 rounded-full bg-<?= htmlspecialchars($g['subject']['color']) ?>-500"></span>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($g['subject']['name']) ?></h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($g['procedures']) ?></span>
                </div>
                <div class="card overflow-hidden">
                    <ul class="procedures-list" data-subject-id="<?= $g['subject']['id'] ?>">
                        <?php foreach ($g['procedures'] as $proc): ?>
                            <?php include __DIR__ . '/includes/_procedure_row.php'; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($unassigned): ?>
            <section>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">No subject</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($unassigned) ?></span>
                </div>
                <div class="card overflow-hidden">
                    <ul class="procedures-list" data-subject-id="0">
                        <?php foreach ($unassigned as $proc): ?>
                            <?php include __DIR__ . '/includes/_procedure_row.php'; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- New/Edit Procedure Modal -->
<div id="procedure-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="procedure-modal-title">New Procedure</h3>
            <button onclick="closeModal('procedure-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="procedure-form" onsubmit="submitProcedure(event)" class="p-5 space-y-4">
            <input type="hidden" name="id" id="procedure-id" value="">
            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" id="procedure-title" required placeholder="e.g., Publish a YouTube video">
            </div>
            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="description" id="procedure-description" rows="2" placeholder="Short intro or context..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Subject</label>
                    <select name="subject_id" id="procedure-subject">
                        <option value="">None</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Project <span class="text-slate-400 font-normal">(optional)</span></label>
                    <select name="project_id" id="procedure-project">
                        <option value="">None</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="procedure-delete-btn" onclick="deleteProcedure()" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hidden">Delete</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeModal('procedure-modal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" id="procedure-submit-btn">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Subjects management modal -->
<div id="subjects-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Manage Subjects</h3>
            <button onclick="closeModal('subjects-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <form onsubmit="createSubject(event)" class="flex items-center gap-2">
                <input type="text" id="new-subject-name" placeholder="New subject name" required class="flex-1">
                <select id="new-subject-color" class="w-28">
                    <option value="indigo">Indigo</option>
                    <option value="emerald">Emerald</option>
                    <option value="amber">Amber</option>
                    <option value="rose">Rose</option>
                    <option value="blue">Blue</option>
                    <option value="purple">Purple</option>
                    <option value="orange">Orange</option>
                    <option value="slate">Slate</option>
                </select>
                <button type="submit" class="btn-primary">Add</button>
            </form>
            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php foreach ($subjects as $s): ?>
                    <li class="flex items-center gap-3 py-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-<?= htmlspecialchars($s['color']) ?>-500 flex-shrink-0"></span>
                        <span class="flex-1 text-sm text-slate-700 dark:text-slate-200"><?= htmlspecialchars($s['name']) ?></span>
                        <span class="text-xs text-slate-400"><?= $s['count'] ?> procedure<?= $s['count'] === 1 ? '' : 's' ?></span>
                        <button onclick="deleteSubject(<?= $s['id'] ?>)" class="p-1 text-slate-400 hover:text-rose-500" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
function openNewProcedure() {
    document.getElementById('procedure-modal-title').textContent = 'New Procedure';
    document.getElementById('procedure-form').reset();
    document.getElementById('procedure-id').value = '';
    document.getElementById('procedure-submit-btn').textContent = 'Create';
    document.getElementById('procedure-delete-btn').classList.add('hidden');
    openModal('procedure-modal');
}

function editProcedure(p) {
    document.getElementById('procedure-modal-title').textContent = 'Edit Procedure';
    document.getElementById('procedure-id').value = p.id;
    document.getElementById('procedure-title').value = p.title || '';
    document.getElementById('procedure-description').value = p.description || '';
    document.getElementById('procedure-subject').value = p.subject_id || '';
    document.getElementById('procedure-project').value = p.project_id || '';
    document.getElementById('procedure-submit-btn').textContent = 'Save Changes';
    document.getElementById('procedure-delete-btn').classList.remove('hidden');
    openModal('procedure-modal');
}

async function submitProcedure(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = data.id ? 'update_procedure' : 'create_procedure';
    try {
        await api('/api/procedures', data);
        toast(data.action === 'update_procedure' ? 'Updated' : 'Created', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (err) {}
}

async function deleteProcedure() {
    const id = document.getElementById('procedure-id').value;
    if (!id || !confirm('Delete this procedure and all its steps?')) return;
    try {
        await api('/api/procedures', { action: 'delete_procedure', id });
        toast('Deleted', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (err) {}
}

async function createSubject(e) {
    e.preventDefault();
    const name = document.getElementById('new-subject-name').value.trim();
    const color = document.getElementById('new-subject-color').value;
    if (!name) return;
    try {
        await api('/api/procedures', { action: 'create_subject', name, color });
        location.reload();
    } catch (err) {}
}

async function deleteSubject(id) {
    if (!confirm('Delete this subject? Procedures using it will become "No subject".')) return;
    try {
        await api('/api/procedures', { action: 'delete_subject', id });
        location.reload();
    } catch (err) {}
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Sortable) return;
    document.querySelectorAll('.procedures-list').forEach(list => {
        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                const ids = Array.from(list.querySelectorAll('[data-procedure-id]')).map(el => el.dataset.procedureId);
                try {
                    await api('/api/procedures', { action: 'reorder_procedures', ids });
                } catch (err) {}
            }
        });
    });
});
</script>

<?php layout_end(); ?>
