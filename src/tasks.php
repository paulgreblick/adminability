<?php
/**
 * Tasks — project-focused to-do list with checklists + dependencies
 */

$page_title = 'Tasks';
$current_page = 'tasks';
require_once __DIR__ . '/includes/layout.php';

$userId = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'active';
$projectFilter = isset($_GET['project']) ? (int)$_GET['project'] : 0;

$where = [];
$params = [];

switch ($filter) {
    case 'mine':
        $where[] = 't.assigned_to = ?'; $params[] = $userId;
        $where[] = "t.status != 'done'";
        break;
    case 'done':   $where[] = "t.status = 'done'"; break;
    case 'all':    break;
    case 'blocked':
        $where[] = "t.status != 'done'";
        $where[] = "EXISTS (SELECT 1 FROM task_dependencies td JOIN tasks bt ON bt.id = td.depends_on_id WHERE td.task_id = t.id AND bt.status != 'done')";
        break;
    default:       $where[] = "t.status != 'done'"; break;
}

if (!empty($_GET['assignee'])) {
    if ($_GET['assignee'] === 'unassigned') $where[] = 't.assigned_to IS NULL';
    else { $where[] = 't.assigned_to = ?'; $params[] = (int)$_GET['assignee']; }
}
if ($projectFilter) { $where[] = 't.project_id = ?'; $params[] = $projectFilter; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT t.*, p.name as project_name, p.color as project_color, u.first_name as assignee_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
    $whereSql
    ORDER BY
        CASE WHEN t.status = 'done' THEN 1 ELSE 0 END,
        COALESCE(p.id, 999999),
        CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 END,
        CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END,
        t.due_date, t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Bulk-fetch checklist items + dependencies for all visible tasks
$checklistByTask = [];
$depsByTask = [];       // tasks this task depends on
$blockersByTask = [];   // whether task is blocked
$taskIds = array_column($tasks, 'id');

if ($taskIds) {
    $ph = implode(',', array_fill(0, count($taskIds), '?'));

    // Checklist items
    $clStmt = $pdo->prepare("SELECT * FROM checklist_items WHERE task_id IN ($ph) ORDER BY sort_order, id");
    $clStmt->execute($taskIds);
    foreach ($clStmt->fetchAll() as $ci) $checklistByTask[$ci['task_id']][] = $ci;

    // Dependencies (what each task depends on)
    $depStmt = $pdo->prepare("
        SELECT td.task_id, td.depends_on_id, bt.title as dep_title, bt.status as dep_status,
               bp.name as dep_project, bp.color as dep_color
        FROM task_dependencies td
        JOIN tasks bt ON bt.id = td.depends_on_id
        LEFT JOIN projects bp ON bt.project_id = bp.id
        WHERE td.task_id IN ($ph)
    ");
    $depStmt->execute($taskIds);
    foreach ($depStmt->fetchAll() as $dep) {
        $depsByTask[$dep['task_id']][] = $dep;
        if ($dep['dep_status'] !== 'done') $blockersByTask[$dep['task_id']] = true;
    }
}

// Group by project
$grouped = [];
foreach ($tasks as $t) {
    $key = $t['project_id'] ?: 0;
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'project_id' => $t['project_id'],
            'project_name' => $t['project_name'] ?: 'No Project',
            'project_color' => $t['project_color'] ?: 'slate',
            'tasks' => [],
        ];
    }
    $grouped[$key]['tasks'][] = $t;
}

// Hierarchical project list (parents first, then children indented)
$allProjects = $pdo->query("SELECT id, name, color, parent_id FROM projects WHERE status = 'active' ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, name")->fetchAll();
$projects = []; // flat list with display_name for dropdowns
foreach ($allProjects as $ap) {
    if ($ap['parent_id']) {
        // Find parent name
        foreach ($allProjects as $pp) { if ($pp['id'] == $ap['parent_id']) { $ap['display_name'] = $pp['name'] . ' › ' . $ap['name']; break; } }
        if (!isset($ap['display_name'])) $ap['display_name'] = $ap['name'];
    } else {
        $ap['display_name'] = $ap['name'];
    }
    $projects[] = $ap;
}
$users = $pdo->query("SELECT id, first_name, name FROM users WHERE is_active = 1 ORDER BY first_name")->fetchAll();

$counts = [];
$counts['active'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'done'")->fetchColumn();
$mineStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'done'");
$mineStmt->execute([$userId]);
$counts['mine'] = (int)$mineStmt->fetchColumn();
$counts['done'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'done'")->fetchColumn();
$counts['blocked'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks t WHERE t.status != 'done' AND EXISTS (SELECT 1 FROM task_dependencies td JOIN tasks bt ON bt.id = td.depends_on_id WHERE td.task_id = t.id AND bt.status != 'done')")->fetchColumn();

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Tasks</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your shared to-do list</p>
    </div>
    <button data-shortcut="new" onclick="openModal('new-task-modal')" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New Task
    </button>
</div>

<!-- Filters -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <?php
    $filterOptions = [
        ['id' => 'active', 'label' => 'Active', 'count' => $counts['active']],
        ['id' => 'mine', 'label' => 'My Tasks', 'count' => $counts['mine']],
        ['id' => 'blocked', 'label' => 'Blocked', 'count' => $counts['blocked']],
        ['id' => 'done', 'label' => 'Done', 'count' => $counts['done']],
        ['id' => 'all', 'label' => 'All', 'count' => null],
    ];
    foreach ($filterOptions as $opt):
        $active = $filter === $opt['id'];
        $href = '?filter=' . $opt['id'] . ($projectFilter ? '&project=' . $projectFilter : '');
    ?>
        <a href="<?= $href ?>" class="filter-pill <?= $active ? 'filter-pill-active' : '' ?>">
            <?= $opt['label'] ?>
            <?php if ($opt['count'] !== null): ?>
                <span class="ml-1.5 text-xs opacity-60"><?= $opt['count'] ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>

    <?php if (!empty($projects)): ?>
        <span class="mx-2 h-5 w-px bg-slate-200 dark:bg-slate-800"></span>
        <a href="?filter=<?= $filter ?>" class="filter-pill <?= !$projectFilter ? 'filter-pill-active' : '' ?>">All Projects</a>
        <?php foreach ($projects as $p): ?>
            <a href="?filter=<?= $filter ?>&project=<?= $p['id'] ?>" class="filter-pill <?= $projectFilter == $p['id'] ? 'filter-pill-active' : '' ?>">
                <span class="w-1.5 h-1.5 rounded-full bg-<?= htmlspecialchars($p['color']) ?>-500 mr-1.5"></span>
                <?= htmlspecialchars($p['name']) ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Task groups -->
<?php if (empty($tasks)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No tasks here</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $filter === 'done' ? 'Nothing completed yet.' : ($filter === 'blocked' ? 'No blocked tasks.' : 'Create your first task to get started.') ?></p>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($grouped as $group): ?>
            <section>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <span class="w-2 h-2 rounded-full bg-<?= htmlspecialchars($group['project_color']) ?>-500"></span>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($group['project_name']) ?></h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($group['tasks']) ?></span>
                </div>
                <div class="card overflow-hidden">
                    <?php foreach ($group['tasks'] as $task):
                        $isOverdue = $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'done';
                        $isToday = $task['due_date'] === date('Y-m-d');
                        $isDone = $task['status'] === 'done';
                        $isBlocked = !empty($blockersByTask[$task['id']]);
                        $assigneeInitial = $task['assignee_name'] ? strtoupper(substr($task['assignee_name'], 0, 1)) : null;
                        $assigneeColor = $assigneeInitial === 'P' ? 'bg-indigo-500' : ($assigneeInitial === 'A' ? 'bg-rose-500' : 'bg-slate-500');
                        $checklist = $checklistByTask[$task['id']] ?? [];
                        $clTotal = count($checklist);
                        $clDone = count(array_filter($checklist, fn($ci) => $ci['is_done']));
                        $deps = $depsByTask[$task['id']] ?? [];
                    ?>
                    <div class="task-item border-b border-slate-200 dark:border-slate-800 last:border-b-0" data-task-id="<?= $task['id'] ?>">
                        <!-- Summary row -->
                        <div class="group flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer" onclick="toggleTaskExpand(<?= $task['id'] ?>)">
                            <button onclick="event.stopPropagation(); toggleTaskStatus(<?= $task['id'] ?>, this)"
                                    class="flex-shrink-0 w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center
                                           <?= $isDone ? 'bg-emerald-500 border-emerald-500' : ($task['status'] === 'in_progress' ? 'border-amber-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500') ?>"
                                    title="Click to change status">
                                <?php if ($isDone): ?>
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                    <span class="block w-2 h-2 rounded-full bg-amber-500"></span>
                                <?php endif; ?>
                            </button>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium <?= $isDone ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' ?>"><?= htmlspecialchars($task['title']) ?></span>
                                    <?php if ($isBlocked): ?>
                                        <span class="pill-rose">Blocked</span>
                                    <?php endif; ?>
                                    <?php if ($task['priority'] === 'urgent'): ?><span class="pill-rose">Urgent</span>
                                    <?php elseif ($task['priority'] === 'high'): ?><span class="pill-amber">High</span>
                                    <?php elseif ($task['priority'] === 'low'): ?><span class="pill-slate">Low</span><?php endif; ?>
                                </div>
                                <?php if ($clTotal > 0 || $task['description']): ?>
                                <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <?php if ($clTotal > 0): ?>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                            <?= $clDone ?>/<?= $clTotal ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($task['description']): ?>
                                        <span class="truncate"><?= htmlspecialchars(substr($task['description'], 0, 60)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($task['due_date']): ?>
                                <span class="text-xs flex-shrink-0 <?= $isOverdue ? 'text-rose-600 dark:text-rose-400 font-medium' : ($isToday ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-500 dark:text-slate-400') ?>">
                                    <?= $isOverdue ? 'Overdue' : ($isToday ? 'Today' : date('M j', strtotime($task['due_date']))) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($assigneeInitial): ?>
                                <div class="avatar <?= $assigneeColor ?> w-6 h-6 text-[10px] flex-shrink-0"><?= $assigneeInitial ?></div>
                            <?php endif; ?>

                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0 expand-icon transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>

                        <!-- Expanded detail panel -->
                        <div class="task-detail hidden bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-800 px-4 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- Checklist -->
                                <div>
                                    <h4 class="section-label mb-2">Checklist</h4>
                                    <ul class="checklist space-y-1" data-task-id="<?= $task['id'] ?>">
                                        <?php foreach ($checklist as $ci): ?>
                                        <li class="flex items-center gap-2 group/cl" data-item-id="<?= $ci['id'] ?>">
                                            <button onclick="toggleChecklistItem(<?= $ci['id'] ?>, this)" class="flex-shrink-0 w-4 h-4 rounded border flex items-center justify-center transition-colors <?= $ci['is_done'] ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500' ?>">
                                                <?php if ($ci['is_done']): ?><svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><?php endif; ?>
                                            </button>
                                            <span class="text-sm flex-1 <?= $ci['is_done'] ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300' ?>"><?= htmlspecialchars($ci['text']) ?></span>
                                            <button onclick="deleteChecklistItem(<?= $ci['id'] ?>, this)" class="p-0.5 text-slate-400 hover:text-rose-500 opacity-0 group-hover/cl:opacity-100 transition-opacity"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <form onsubmit="addChecklistItem(event, <?= $task['id'] ?>)" class="flex items-center gap-2 mt-2">
                                        <input type="text" name="text" placeholder="Add item..." class="!text-sm !py-1 flex-1" required>
                                        <button type="submit" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline flex-shrink-0">Add</button>
                                    </form>
                                </div>

                                <!-- Dependencies -->
                                <div>
                                    <h4 class="section-label mb-2">Blocked by</h4>
                                    <?php if ($deps): ?>
                                    <ul class="space-y-1 mb-2">
                                        <?php foreach ($deps as $dep): ?>
                                        <li class="flex items-center gap-2 group/dep">
                                            <div class="flex-shrink-0 w-4 h-4 rounded-full border-2 flex items-center justify-center <?= $dep['dep_status'] === 'done' ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300 dark:border-slate-600' ?>">
                                                <?php if ($dep['dep_status'] === 'done'): ?><svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><?php endif; ?>
                                            </div>
                                            <span class="text-sm flex-1 <?= $dep['dep_status'] === 'done' ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300' ?>"><?= htmlspecialchars($dep['dep_title']) ?></span>
                                            <?php if ($dep['dep_project']): ?>
                                                <span class="pill-<?= htmlspecialchars($dep['dep_color'] ?? 'slate') ?> !text-[10px]"><?= htmlspecialchars($dep['dep_project']) ?></span>
                                            <?php endif; ?>
                                            <button onclick="removeDependency(<?= $task['id'] ?>, <?= $dep['depends_on_id'] ?>, this)" class="p-0.5 text-slate-400 hover:text-rose-500 opacity-0 group-hover/dep:opacity-100 transition-opacity"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                    <div class="relative dep-search">
                                        <input type="text" placeholder="Search tasks to add dependency..."
                                               oninput="searchDeps(this, <?= $task['id'] ?>)"
                                               class="!text-sm !py-1 w-full">
                                        <div class="dep-results hidden absolute z-10 mt-1 w-full card shadow-lg max-h-40 overflow-y-auto"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action bar -->
                            <div class="flex items-center gap-2 mt-4 pt-3 border-t border-slate-200 dark:border-slate-800">
                                <button onclick='editTask(<?= json_encode($task, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn-secondary !py-1.5 !text-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit details
                                </button>
                                <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-ghost !py-1.5 !text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">Delete</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- New/Edit Task Modal -->
<div id="new-task-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="task-modal-title">New Task</h3>
            <button onclick="closeModal('new-task-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="task-form" onsubmit="submitTask(event)" class="p-5 space-y-4">
            <input type="hidden" name="task_id" id="task-id" value="">
            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" id="task-title" required placeholder="What needs to be done?">
            </div>
            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="description" id="task-description" rows="3" placeholder="Extra details..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Project</label>
                    <select name="project_id" id="task-project">
                        <option value="">No Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Assigned to</label>
                    <select name="assigned_to" id="task-assignee">
                        <option value="">Unassigned</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $userId ? 'selected' : '' ?>><?= htmlspecialchars($u['first_name'] ?: $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" id="task-priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Due date</label>
                    <input type="date" name="due_date" id="task-due-date">
                </div>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="task-delete-btn" onclick="deleteTask()" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hidden">Delete</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeModal('new-task-modal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" id="task-submit-btn">Create Task</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// --- Expand/collapse ---
function toggleTaskExpand(id) {
    const item = document.querySelector(`.task-item[data-task-id="${id}"]`);
    const detail = item.querySelector('.task-detail');
    const icon = item.querySelector('.expand-icon');
    detail.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}

// --- Edit modal ---
function editTask(task) {
    document.getElementById('task-modal-title').textContent = 'Edit Task';
    document.getElementById('task-id').value = task.id;
    document.getElementById('task-title').value = task.title || '';
    document.getElementById('task-description').value = task.description || '';
    document.getElementById('task-project').value = task.project_id || '';
    document.getElementById('task-assignee').value = task.assigned_to || '';
    document.getElementById('task-priority').value = task.priority || 'normal';
    document.getElementById('task-due-date').value = task.due_date || '';
    document.getElementById('task-submit-btn').textContent = 'Save Changes';
    document.getElementById('task-delete-btn').classList.remove('hidden');
    openModal('new-task-modal');
}

document.querySelector('[onclick="openModal(\'new-task-modal\')"]')?.addEventListener('click', () => {
    document.getElementById('task-modal-title').textContent = 'New Task';
    document.getElementById('task-form').reset();
    document.getElementById('task-id').value = '';
    document.getElementById('task-submit-btn').textContent = 'Create Task';
    document.getElementById('task-delete-btn').classList.add('hidden');
});

async function submitTask(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = data.task_id ? 'update' : 'create';
    try {
        await api('/api/tasks', data);
        toast(data.action === 'update' ? 'Task updated' : 'Task created', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function deleteTask(idOverride) {
    const id = idOverride || document.getElementById('task-id').value;
    if (!id || !confirm('Delete this task?')) return;
    try {
        await api('/api/tasks', { action: 'delete', task_id: id });
        toast('Task deleted', 'success');
        const el = document.querySelector(`.task-item[data-task-id="${id}"]`);
        if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }
        else setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function toggleTaskStatus(id, btn) {
    btn.disabled = true;
    try {
        await api('/api/tasks', { action: 'toggle_status', task_id: id });
        setTimeout(() => location.reload(), 300);
    } catch (e) { btn.disabled = false; }
}

// --- Checklist ---
async function addChecklistItem(e, taskId) {
    e.preventDefault();
    const input = e.target.querySelector('input[name="text"]');
    const text = input.value.trim();
    if (!text) return;
    try {
        const res = await api('/api/tasks', { action: 'add_checklist_item', task_id: taskId, text });
        const ul = e.target.closest('.task-detail').querySelector('.checklist');
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 group/cl';
        li.dataset.itemId = res.data.id;
        li.innerHTML = `
            <button onclick="toggleChecklistItem(${res.data.id}, this)" class="flex-shrink-0 w-4 h-4 rounded border border-slate-300 dark:border-slate-600 hover:border-indigo-500 flex items-center justify-center transition-colors"></button>
            <span class="text-sm flex-1 text-slate-700 dark:text-slate-300">${escapeHtml(text)}</span>
            <button onclick="deleteChecklistItem(${res.data.id}, this)" class="p-0.5 text-slate-400 hover:text-rose-500 opacity-0 group-hover/cl:opacity-100 transition-opacity"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        `;
        ul.appendChild(li);
        input.value = '';
    } catch (e) {}
}

async function toggleChecklistItem(id, btn) {
    try {
        const res = await api('/api/tasks', { action: 'toggle_checklist_item', item_id: id });
        const li = btn.closest('li');
        const span = li.querySelector('span');
        const done = res.data.is_done;
        btn.className = 'flex-shrink-0 w-4 h-4 rounded border flex items-center justify-center transition-colors ' +
            (done ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500');
        btn.innerHTML = done ? '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : '';
        span.className = 'text-sm flex-1 ' + (done ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300');
    } catch (e) {}
}

async function deleteChecklistItem(id, btn) {
    try {
        await api('/api/tasks', { action: 'delete_checklist_item', item_id: id });
        btn.closest('li').remove();
    } catch (e) {}
}

// --- Dependencies ---
let depSearchTimeout;
async function searchDeps(input, taskId) {
    clearTimeout(depSearchTimeout);
    const q = input.value.trim();
    const container = input.closest('.dep-search').querySelector('.dep-results');
    if (q.length < 2) { container.classList.add('hidden'); return; }

    depSearchTimeout = setTimeout(async () => {
        try {
            const res = await api('/api/tasks', { action: 'search_tasks', q, exclude_id: taskId });
            if (!res.data.length) {
                container.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">No matching tasks</div>';
            } else {
                container.innerHTML = res.data.map(t =>
                    `<button type="button" onclick="addDependency(${taskId}, ${t.id}, '${escapeAttr(t.title)}', this)"
                        class="block w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        ${escapeHtml(t.title)}
                        ${t.project_name ? `<span class="pill-${t.project_color || 'slate'} !text-[10px] ml-1">${escapeHtml(t.project_name)}</span>` : ''}
                    </button>`
                ).join('');
            }
            container.classList.remove('hidden');
        } catch (e) {}
    }, 300);
}

async function addDependency(taskId, dependsOnId, title, btn) {
    try {
        await api('/api/tasks', { action: 'add_dependency', task_id: taskId, depends_on_id: dependsOnId });
        toast('Dependency added', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function removeDependency(taskId, dependsOnId, btn) {
    try {
        await api('/api/tasks', { action: 'remove_dependency', task_id: taskId, depends_on_id: dependsOnId });
        btn.closest('li').remove();
        toast('Dependency removed', 'success');
    } catch (e) {}
}

function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
function escapeAttr(s) { return escapeHtml(s); }

// Close dep search when clicking outside
document.addEventListener('click', e => {
    if (!e.target.closest('.dep-search')) {
        document.querySelectorAll('.dep-results').forEach(el => el.classList.add('hidden'));
    }
});
</script>

<?php layout_end(); ?>
