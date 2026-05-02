<?php
/**
 * Projects — top-level list view with sub-project support
 */

$page_title = 'Projects';
$current_page = 'projects';
require_once __DIR__ . '/includes/layout.php';

$filter = $_GET['filter'] ?? 'active';

$statusWhere = $filter === 'archived' ? "AND p.status = 'archived'"
             : ($filter === 'all' ? '' : "AND p.status = 'active'");

// Only show top-level projects (parent_id IS NULL)
$projects = $pdo->query("
    SELECT p.*,
           u.first_name as creator_first,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as task_done,
           (SELECT COUNT(*) FROM notes n WHERE n.project_id = p.id) as note_count,
           (SELECT COUNT(*) FROM docs d WHERE d.project_id = p.id) as doc_count,
           (SELECT COUNT(*) FROM projects sp WHERE sp.parent_id = p.id AND sp.status = 'active') as sub_count
    FROM projects p
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.parent_id IS NULL $statusWhere
    ORDER BY
        CASE WHEN p.status = 'active' THEN 0 ELSE 1 END,
        p.name
")->fetchAll();

$counts = [
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active' AND parent_id IS NULL")->fetchColumn(),
    'archived' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'archived' AND parent_id IS NULL")->fetchColumn(),
];

// For aggregate stats: count tasks in sub-projects too
$subTaskCounts = [];
foreach ($projects as $p) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as done
        FROM tasks t
        JOIN projects sp ON t.project_id = sp.id
        WHERE sp.parent_id = ?
    ");
    $stmt->execute([$p['id']]);
    $subTaskCounts[$p['id']] = $stmt->fetch();
}

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Projects</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cross-cutting work — group tasks, notes, and docs</p>
    </div>
    <button data-shortcut="new" onclick="openProjectModal()" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New Project
    </button>
</div>

<!-- Filters -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="?filter=active" class="filter-pill <?= $filter === 'active' ? 'filter-pill-active' : '' ?>">Active <span class="ml-1.5 text-xs opacity-60"><?= $counts['active'] ?></span></a>
    <a href="?filter=archived" class="filter-pill <?= $filter === 'archived' ? 'filter-pill-active' : '' ?>">Archived <span class="ml-1.5 text-xs opacity-60"><?= $counts['archived'] ?></span></a>
    <a href="?filter=all" class="filter-pill <?= $filter === 'all' ? 'filter-pill-active' : '' ?>">All</a>
</div>

<!-- Project grid -->
<?php if (empty($projects)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No projects here</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create your first project to organize work.</p>
        <button onclick="openProjectModal()" class="btn-primary mt-4">New Project</button>
    </div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($projects as $p):
        $subTasks = $subTaskCounts[$p['id']] ?? ['total' => 0, 'done' => 0];
        $allTasks = (int)$p['task_count'] + (int)$subTasks['total'];
        $allDone = (int)$p['task_done'] + (int)$subTasks['done'];
        $percent = $allTasks > 0 ? round(($allDone / $allTasks) * 100) : 0;
        $isArchived = $p['status'] === 'archived';
    ?>
    <a href="/project?id=<?= $p['id'] ?>" class="card card-hover block p-5 group <?= $isArchived ? 'opacity-70' : '' ?>">
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-<?= htmlspecialchars($p['color']) ?>-500 flex-shrink-0"></span>
                <h3 class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($p['name']) ?></h3>
            </div>
            <?php if ($isArchived): ?>
                <span class="pill-slate text-[10px]">Archived</span>
            <?php endif; ?>
        </div>

        <?php if ($p['description']): ?>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 line-clamp-2"><?= htmlspecialchars($p['description']) ?></p>
        <?php else: ?>
            <p class="text-sm text-slate-400 dark:text-slate-500 italic mb-4">No description</p>
        <?php endif; ?>

        <?php if ($allTasks > 0): ?>
        <div class="mb-3">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <span><?= $allDone ?>/<?= $allTasks ?> tasks</span>
                <span class="font-medium text-slate-700 dark:text-slate-300"><?= $percent ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar <?= $percent === 100 ? 'bg-emerald-500' : '' ?>" style="width: <?= $percent ?>%"></div></div>
        </div>
        <?php endif; ?>

        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <?= $p['task_count'] ?>
            </span>
            <?php if ((int)$p['sub_count'] > 0): ?>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                <?= $p['sub_count'] ?> sub
            </span>
            <?php endif; ?>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <?= $p['note_count'] ?>
            </span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <?= $p['doc_count'] ?>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- New Project Modal -->
<div id="project-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">New Project</h3>
            <button onclick="closeModal('project-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="project-form" onsubmit="submitProject(event)" class="p-5 space-y-4">
            <div>
                <label class="form-label">Name</label>
                <input type="text" name="name" id="proj-name" required placeholder="What's this project about?">
            </div>
            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="description" id="proj-description" rows="3" placeholder="A bit more context..."></textarea>
            </div>
            <div>
                <label class="form-label">Color</label>
                <select name="color" id="proj-color">
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
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeModal('project-modal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProjectModal() {
    document.getElementById('project-form').reset();
    openModal('project-modal');
}

async function submitProject(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = 'create_project';
    try {
        const res = await api('/api/tasks', data);
        toast('Project created', 'success');
        if (res.data && res.data.id) location.href = '/project?id=' + res.data.id;
        else location.reload();
    } catch (e) {}
}
</script>

<?php layout_end(); ?>
