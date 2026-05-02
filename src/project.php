<?php
/**
 * Project detail — tasks, notes, docs, and sub-projects
 */

$projectId = (int)($_GET['id'] ?? 0);
if (!$projectId) { header('Location: /projects'); exit; }

$page_title = 'Project';
$current_page = 'projects';
require_once __DIR__ . '/includes/layout.php';

$stmt = $pdo->prepare("SELECT p.*, u.first_name as creator_first, pp.name as parent_name FROM projects p LEFT JOIN users u ON p.created_by = u.id LEFT JOIN projects pp ON p.parent_id = pp.id WHERE p.id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) { header('Location: /projects'); exit; }

$page_title = $project['name'];
$isSubProject = !empty($project['parent_id']);

// Sub-projects
$subStmt = $pdo->prepare("
    SELECT sp.*,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = sp.id) as task_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = sp.id AND t.status = 'done') as task_done
    FROM projects sp
    WHERE sp.parent_id = ? AND sp.status = 'active'
    ORDER BY sp.name
");
$subStmt->execute([$projectId]);
$subProjects = $subStmt->fetchAll();

// Tasks for this project directly
$tasksStmt = $pdo->prepare("
    SELECT t.*, u.first_name as assignee_name
    FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ?
    ORDER BY CASE WHEN t.status = 'done' THEN 1 ELSE 0 END,
             CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 END,
             t.due_date
");
$tasksStmt->execute([$projectId]);
$tasks = $tasksStmt->fetchAll();

$notesStmt = $pdo->prepare("SELECT * FROM notes WHERE project_id = ? AND status != 'archived' ORDER BY is_pinned DESC, updated_at DESC");
$notesStmt->execute([$projectId]);
$notes = $notesStmt->fetchAll();

$docsStmt = $pdo->prepare("SELECT id, title, slug, updated_at FROM docs WHERE project_id = ? AND status != 'archived' ORDER BY updated_at DESC");
$docsStmt->execute([$projectId]);
$docs = $docsStmt->fetchAll();

// Aggregate: own tasks + sub-project tasks
$totalOwn = count($tasks);
$doneOwn = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$totalSub = 0; $doneSub = 0;
foreach ($subProjects as $sp) { $totalSub += (int)$sp['task_count']; $doneSub += (int)$sp['task_done']; }
$totalAll = $totalOwn + $totalSub;
$doneAll = $doneOwn + $doneSub;
$percentAll = $totalAll > 0 ? round(($doneAll / $totalAll) * 100) : 0;

layout_start();
?>

<!-- Breadcrumb -->
<div class="mb-4 flex items-center gap-1 text-sm">
    <a href="/projects" class="font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Projects</a>
    <?php if ($isSubProject): ?>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="/project?id=<?= $project['parent_id'] ?>" class="font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"><?= htmlspecialchars($project['parent_name']) ?></a>
    <?php endif; ?>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-900 dark:text-white"><?= htmlspecialchars($project['name']) ?></span>
</div>

<!-- Header card -->
<div class="card p-6 mb-6">
    <div class="flex items-start justify-between gap-4 mb-3">
        <div class="flex items-start gap-3 min-w-0 flex-1">
            <span class="w-3 h-3 mt-2 rounded-full bg-<?= htmlspecialchars($project['color']) ?>-500 flex-shrink-0"></span>
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($project['name']) ?></h1>
                <?php if ($project['description']): ?>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400"><?= htmlspecialchars($project['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button onclick="openEditProject()" class="btn-secondary">Edit</button>
            <?php if ($project['status'] === 'active'): ?>
                <button onclick="archiveProject()" class="btn-ghost">Archive</button>
            <?php else: ?>
                <button onclick="unarchiveProject()" class="btn-secondary">Unarchive</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($totalAll > 0): ?>
    <div>
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1.5">
            <span><?= $doneAll ?> of <?= $totalAll ?> tasks<?= $totalSub > 0 ? ' (incl. sub-projects)' : '' ?></span>
            <span class="font-medium text-slate-700 dark:text-slate-300"><?= $percentAll ?>%</span>
        </div>
        <div class="progress"><div class="progress-bar <?= $percentAll === 100 ? 'bg-emerald-500' : '' ?>" style="width: <?= $percentAll ?>%"></div></div>
    </div>
    <?php endif; ?>
</div>

<!-- Sub-projects section (if any or if this is a top-level project) -->
<?php if (!empty($subProjects) || !$isSubProject): ?>
<section class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="section-label">Sub-projects</h2>
        <button onclick="openSubProjectModal()" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add sub-project</button>
    </div>
    <?php if (empty($subProjects)): ?>
        <div class="card p-5 text-center text-sm text-slate-500 dark:text-slate-400">
            No sub-projects yet. Break this project into smaller pieces?
        </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($subProjects as $sp):
            $spPercent = $sp['task_count'] > 0 ? round(($sp['task_done'] / $sp['task_count']) * 100) : 0;
        ?>
        <a href="/project?id=<?= $sp['id'] ?>" class="card card-hover block p-4 group">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-<?= htmlspecialchars($sp['color']) ?>-500 flex-shrink-0"></span>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($sp['name']) ?></h3>
            </div>
            <?php if ($sp['description']): ?>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mb-2"><?= htmlspecialchars($sp['description']) ?></p>
            <?php endif; ?>
            <?php if ((int)$sp['task_count'] > 0): ?>
                <div class="flex items-center gap-2">
                    <div class="flex-1 progress"><div class="progress-bar" style="width: <?= $spPercent ?>%"></div></div>
                    <span class="text-xs text-slate-500 tabular-nums"><?= $sp['task_done'] ?>/<?= $sp['task_count'] ?></span>
                </div>
            <?php else: ?>
                <span class="text-xs text-slate-400">No tasks yet</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Tasks (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        <section class="card">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Tasks <span class="text-slate-400 ml-1"><?= count($tasks) ?></span></h2>
                <a href="/tasks?project=<?= $projectId ?>" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Manage →</a>
            </div>
            <?php if (empty($tasks)): ?>
                <div class="p-8 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No tasks in this project yet.</p>
                    <a href="/tasks?project=<?= $projectId ?>" class="mt-3 inline-block text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Add a task →</a>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($tasks as $task):
                        $isDone = $task['status'] === 'done';
                        $isOverdue = $task['due_date'] && $task['due_date'] < date('Y-m-d') && !$isDone;
                        $isToday = $task['due_date'] === date('Y-m-d');
                        $assigneeInitial = $task['assignee_name'] ? strtoupper(substr($task['assignee_name'], 0, 1)) : null;
                        $assigneeColor = $assigneeInitial === 'P' ? 'bg-indigo-500' : ($assigneeInitial === 'A' ? 'bg-rose-500' : 'bg-slate-500');
                    ?>
                    <li class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center
                                    <?= $isDone ? 'bg-emerald-500 border-emerald-500' : ($task['status'] === 'in_progress' ? 'border-amber-500' : 'border-slate-300 dark:border-slate-600') ?>">
                            <?php if ($isDone): ?>
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <span class="block w-2 h-2 rounded-full bg-amber-500"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium <?= $isDone ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' ?>"><?= htmlspecialchars($task['title']) ?></span>
                        </div>
                        <?php if ($task['due_date']): ?>
                            <span class="text-xs flex-shrink-0 <?= $isOverdue ? 'text-rose-600 dark:text-rose-400 font-medium' : ($isToday ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400') ?>">
                                <?= $isOverdue ? 'Overdue' : ($isToday ? 'Today' : date('M j', strtotime($task['due_date']))) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($assigneeInitial): ?>
                            <div class="avatar <?= $assigneeColor ?> w-6 h-6 text-[10px] flex-shrink-0"><?= $assigneeInitial ?></div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <!-- Notes & Docs (1/3) -->
    <div class="space-y-6">
        <section class="card">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Notes <span class="text-slate-400 ml-1"><?= count($notes) ?></span></h2>
                <a href="/notes" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">All notes →</a>
            </div>
            <?php if (empty($notes)): ?>
                <div class="p-5 text-center text-sm text-slate-500 dark:text-slate-400">No notes linked.</div>
            <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($notes as $note): ?>
                    <li class="px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <?php if ($note['title']): ?>
                            <div class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($note['title']) ?></div>
                        <?php endif; ?>
                        <div class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5"><?= htmlspecialchars(substr(strip_tags($note['content']), 0, 100)) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Docs <span class="text-slate-400 ml-1"><?= count($docs) ?></span></h2>
                <a href="/docs" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">All docs →</a>
            </div>
            <?php if (empty($docs)): ?>
                <div class="p-5 text-center text-sm text-slate-500 dark:text-slate-400">No docs linked.</div>
            <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($docs as $doc): ?>
                    <li>
                        <a href="/doc?id=<?= $doc['id'] ?>" class="block px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <div class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($doc['title']) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= date('M j, Y', strtotime($doc['updated_at'])) ?></div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Edit Project Modal -->
<div id="edit-project-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Edit Project</h3>
            <button onclick="closeModal('edit-project-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form onsubmit="updateProject(event)" class="p-5 space-y-4">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">
            <div>
                <label class="form-label">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Color</label>
                <select name="color">
                    <?php foreach (['indigo','emerald','amber','rose','blue','purple','orange','slate'] as $c): ?>
                        <option value="<?= $c ?>" <?= $project['color'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="deleteProject()" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">Delete</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeModal('edit-project-modal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Sub-project Modal -->
<div id="sub-project-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">New Sub-project</h3>
            <button onclick="closeModal('sub-project-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form onsubmit="createSubProject(event)" class="p-5 space-y-4">
            <div>
                <label class="form-label">Name</label>
                <input type="text" name="name" required placeholder="Sub-project name">
            </div>
            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="description" rows="2" placeholder="What's this sub-project about?"></textarea>
            </div>
            <div>
                <label class="form-label">Color</label>
                <select name="color">
                    <?php foreach (['indigo','emerald','amber','rose','blue','purple','orange','slate'] as $c): ?>
                        <option value="<?= $c ?>" <?= $project['color'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeModal('sub-project-modal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
const projectId = <?= $projectId ?>;

function openEditProject() { openModal('edit-project-modal'); }
function openSubProjectModal() { openModal('sub-project-modal'); }

async function updateProject(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = 'update_project';
    try { await api('/api/tasks', data); toast('Project updated', 'success'); setTimeout(() => location.reload(), 200); } catch (e) {}
}

async function createSubProject(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = 'create_project';
    data.parent_id = projectId;
    try {
        const res = await api('/api/tasks', data);
        toast('Sub-project created', 'success');
        if (res.data && res.data.id) location.href = '/project?id=' + res.data.id;
        else location.reload();
    } catch (e) {}
}

async function archiveProject() {
    if (!confirm('Archive this project?')) return;
    try { await api('/api/tasks', { action: 'archive_project', project_id: projectId }); toast('Archived', 'success'); setTimeout(() => location.href = '/projects', 300); } catch (e) {}
}
async function unarchiveProject() {
    try { await api('/api/tasks', { action: 'unarchive_project', project_id: projectId }); toast('Restored', 'success'); setTimeout(() => location.reload(), 300); } catch (e) {}
}
async function deleteProject() {
    if (!confirm('Delete this project? Tasks/notes/docs become unassigned. Sub-projects become top-level.')) return;
    try { await api('/api/tasks', { action: 'delete_project', project_id: projectId }); toast('Deleted', 'success'); setTimeout(() => location.href = '/projects', 300); } catch (e) {}
}
</script>

<?php layout_end(); ?>
