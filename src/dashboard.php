<?php
/**
 * Dashboard v3.2 — command center
 * Greeting, my tasks, project overview, pinned notes, recent activity
 */

$page_title = 'Dashboard';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/layout.php';

$userId = $_SESSION['user_id'];
$firstName = $_SESSION['user_first_name'] ?? $_SESSION['user_name'] ?? 'there';

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

// My tasks
$stmt = $pdo->prepare("
    SELECT t.*, p.name as project_name, p.color as project_color
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    WHERE t.assigned_to = ? AND t.status != 'done'
    ORDER BY
        CASE
            WHEN t.due_date IS NULL THEN 1
            WHEN t.due_date <= date('now') THEN 0
            ELSE 2
        END,
        CASE t.priority
            WHEN 'urgent' THEN 0 WHEN 'high' THEN 1
            WHEN 'normal' THEN 2 WHEN 'low' THEN 3
        END,
        t.due_date
    LIMIT 8
");
$stmt->execute([$userId]);
$myTasks = $stmt->fetchAll();

// Stats
$taskStats = [
    'todo' => (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'done'")->fetchColumn(),
    'mine' => 0, 'done_today' => 0, 'projects' => 0,
];
$mineStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'done'");
$mineStmt->execute([$userId]);
$taskStats['mine'] = (int)$mineStmt->fetchColumn();

$doneToday = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'done' AND date(completed_at) = date('now')");
$doneToday->execute([$userId]);
$taskStats['done_today'] = (int)$doneToday->fetchColumn();

$taskStats['projects'] = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn();

// Active projects with task counts
$projects = $pdo->query("
    SELECT p.id, p.name, p.color, p.description,
           COUNT(t.id) as total_tasks,
           SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as done_tasks
    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY
        (CASE WHEN COUNT(t.id) > 0 AND SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) < COUNT(t.id) THEN 0 ELSE 1 END),
        p.name
    LIMIT 6
")->fetchAll();

// Pinned notes
$pinnedNotes = $pdo->query("SELECT id, title, content FROM notes WHERE is_pinned = 1 AND status != 'archived' ORDER BY updated_at DESC LIMIT 4")->fetchAll();

layout_start();
?>

<!-- Greeting -->
<div class="mb-8">
    <h1 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
        <?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?>
    </h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= date('l, F j') ?></p>
</div>

<!-- Stats row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
    <div class="card p-4">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">My Open Tasks</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"><?= $taskStats['mine'] ?></div>
    </div>
    <div class="card p-4">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Done Today</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"><?= $taskStats['done_today'] ?></div>
    </div>
    <div class="card p-4">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">All Open</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"><?= $taskStats['todo'] ?></div>
    </div>
    <div class="card p-4">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Projects</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"><?= $taskStats['projects'] ?></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left column (2/3) -->
    <div class="lg:col-span-2 space-y-6">

        <!-- My Tasks -->
        <section class="card">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">My Tasks</h2>
                <a href="/tasks" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">View all →</a>
            </div>
            <?php if (empty($myTasks)): ?>
                <div class="p-8 text-center">
                    <div class="w-10 h-10 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">All clear. Nothing on your plate.</p>
                    <a href="/tasks" class="mt-3 inline-block text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Create a task →</a>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($myTasks as $task):
                        $isOverdue = $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'done';
                        $isToday = $task['due_date'] === date('Y-m-d');
                    ?>
                    <li class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <button onclick="toggleTask(<?= $task['id'] ?>, this)"
                            class="flex-shrink-0 w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 hover:border-indigo-500 transition-colors flex items-center justify-center">
                            <?php if ($task['status'] === 'in_progress'): ?>
                                <span class="block w-2 h-2 rounded-full bg-amber-500"></span>
                            <?php endif; ?>
                        </button>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($task['title']) ?></span>
                                <?php if ($task['project_name']): ?>
                                    <span class="pill-<?= htmlspecialchars($task['project_color'] ?? 'slate') ?>"><?= htmlspecialchars($task['project_name']) ?></span>
                                <?php endif; ?>
                                <?php if ($task['priority'] === 'urgent'): ?>
                                    <span class="pill-rose">Urgent</span>
                                <?php elseif ($task['priority'] === 'high'): ?>
                                    <span class="pill-amber">High</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($task['due_date']): ?>
                            <span class="text-xs flex-shrink-0 <?= $isOverdue ? 'text-rose-600 dark:text-rose-400 font-medium' : ($isToday ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-500 dark:text-slate-400') ?>">
                                <?= $isOverdue ? 'Overdue' : ($isToday ? 'Today' : date('M j', strtotime($task['due_date']))) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Active Projects -->
        <?php if (!empty($projects)): ?>
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="section-label">Active Projects</h2>
                <a href="/projects" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">All →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($projects as $p):
                    $percent = $p['total_tasks'] > 0 ? round(($p['done_tasks'] / $p['total_tasks']) * 100) : 0;
                ?>
                <a href="/project?id=<?= $p['id'] ?>" class="card card-hover block p-4 group">
                    <div class="flex items-start gap-3">
                        <span class="w-2 h-2 mt-2 rounded-full bg-<?= htmlspecialchars($p['color']) ?>-500 flex-shrink-0"></span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if ($p['description']): ?>
                                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 line-clamp-1"><?= htmlspecialchars($p['description']) ?></div>
                            <?php endif; ?>
                            <?php if ((int)$p['total_tasks'] > 0): ?>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="flex-1 progress"><div class="progress-bar" style="width: <?= $percent ?>%"></div></div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 tabular-nums"><?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?></span>
                            </div>
                            <?php else: ?>
                            <div class="mt-2 text-xs text-slate-400 dark:text-slate-500">No tasks yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Empty state if nothing at all -->
        <?php if (empty($myTasks) && empty($projects)): ?>
        <div class="card p-8 text-center">
            <p class="text-slate-500 dark:text-slate-400">Your workspace is empty. Get started:</p>
            <div class="mt-4 flex items-center justify-center gap-3">
                <a href="/projects" class="btn-primary">New Project</a>
                <a href="/tasks" class="btn-secondary">New Task</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column (1/3) -->
    <div class="space-y-6">

        <!-- Pinned Notes -->
        <section class="card">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z"/></svg>
                    Pinned Notes
                </h2>
                <a href="/notes?filter=pinned" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">All →</a>
            </div>
            <?php if (empty($pinnedNotes)): ?>
                <div class="p-5 text-center text-sm text-slate-500 dark:text-slate-400">No pinned notes yet.</div>
            <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($pinnedNotes as $note): ?>
                    <li>
                        <a href="/notes" class="block px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <?php if ($note['title']): ?>
                                <div class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($note['title']) ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= htmlspecialchars(substr(strip_tags($note['content']), 0, 80)) ?></div>
                            <?php else: ?>
                                <div class="text-sm text-slate-700 dark:text-slate-300 line-clamp-2"><?= htmlspecialchars(substr(strip_tags($note['content']), 0, 120)) ?></div>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    </div>
</div>

<script>
async function toggleTask(id, btn) {
    btn.disabled = true;
    try {
        const res = await api('/api/tasks', { action: 'toggle_status', task_id: id });
        const li = btn.closest('li');
        if (res.data && res.data.status === 'done') {
            li.style.transition = 'opacity 200ms, transform 200ms';
            li.style.opacity = '0';
            li.style.transform = 'translateX(20px)';
            setTimeout(() => li.remove(), 200);
            toast('Task completed', 'success');
        } else if (res.data && res.data.status === 'in_progress') {
            btn.innerHTML = '<span class="block w-2 h-2 rounded-full bg-amber-500"></span>';
        } else {
            btn.innerHTML = '';
        }
    } catch (e) {
        btn.disabled = false;
    }
}
</script>

<?php layout_end(); ?>
