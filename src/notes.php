<?php
/**
 * Notes Dashboard - Redesigned
 * Clean grid layout with collapsible cards and modal forms
 */

// Handle POST before any output
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$message = '';
$messageType = '';

// Get categories for dropdowns
$categories = $pdo->query('SELECT * FROM note_projects ORDER BY sort_order')->fetchAll();
$categoryLookup = [];
foreach ($categories as $c) {
    $categoryLookup[$c['id']] = $c;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                if (!hasPermission('notes.create')) break;

                $categoryId = (int)($_POST['category_id'] ?? 1);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'note';
                $priority = $_POST['priority'] ?? 'normal';

                if (empty($content)) {
                    $message = 'Note content is required.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO notes (project_id, title, content, type, priority, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$categoryId, $title ?: null, $content, $type, $priority, $_SESSION['user_id']]);
                    $message = 'Note created.';
                    $messageType = 'success';
                }
                break;

            case 'update':
                if (!hasPermission('notes.edit')) break;

                $noteId = (int)($_POST['note_id'] ?? 0);
                $categoryId = (int)($_POST['category_id'] ?? 1);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'note';
                $status = $_POST['status'] ?? 'idea';
                $priority = $_POST['priority'] ?? 'normal';

                if (empty($content)) {
                    $message = 'Note content is required.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('UPDATE notes SET project_id = ?, title = ?, content = ?, type = ?, status = ?, priority = ?, updated_by = ? WHERE id = ?');
                    $stmt->execute([$categoryId, $title ?: null, $content, $type, $status, $priority, $_SESSION['user_id'], $noteId]);
                    $message = 'Note updated.';
                    $messageType = 'success';
                }
                break;

            case 'toggle_pin':
                if (!hasPermission('notes.edit')) break;
                $noteId = (int)($_POST['note_id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE notes SET is_pinned = NOT is_pinned, updated_by = ? WHERE id = ?');
                $stmt->execute([$_SESSION['user_id'], $noteId]);
                break;

            case 'update_status':
                if (!hasPermission('notes.edit')) break;
                $noteId = (int)($_POST['note_id'] ?? 0);
                $status = $_POST['status'] ?? 'idea';
                $stmt = $pdo->prepare('UPDATE notes SET status = ?, updated_by = ? WHERE id = ?');
                $stmt->execute([$status, $_SESSION['user_id'], $noteId]);
                break;

            case 'delete':
                if (!hasPermission('notes.delete')) break;
                $noteId = (int)($_POST['note_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
                $stmt->execute([$noteId]);
                $message = 'Note deleted.';
                $messageType = 'success';
                break;

            case 'add_category':
                if (!hasPermission('notes.create')) break;
                $name = trim($_POST['category_name'] ?? '');
                $color = $_POST['category_color'] ?? 'gray';
                if ($name) {
                    $stmt = $pdo->prepare('INSERT INTO note_projects (name, color) VALUES (?, ?)');
                    $stmt->execute([$name, $color]);
                    $message = 'Category created.';
                    $messageType = 'success';
                    $categories = $pdo->query('SELECT * FROM note_projects ORDER BY sort_order')->fetchAll();
                }
                break;

            case 'delete_category':
                if (!hasPermission('notes.delete')) break;
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($categoryId > 1) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE project_id = ?');
                    $stmt->execute([$categoryId]);
                    $noteCount = $stmt->fetchColumn();

                    if ($noteCount > 0) {
                        $message = "Cannot delete: category has $noteCount note(s). Move or delete them first.";
                        $messageType = 'error';
                    } else {
                        $stmt = $pdo->prepare('DELETE FROM note_projects WHERE id = ?');
                        $stmt->execute([$categoryId]);
                        $message = 'Category deleted.';
                        $messageType = 'success';
                        $categories = $pdo->query('SELECT * FROM note_projects ORDER BY sort_order')->fetchAll();
                    }
                }
                break;
        }
    }
}

// Now include layout
$dashboard_title = 'Notes';
$current_dashboard_page = 'notes';
include 'includes/dashboard-layout.php';

requirePermission('notes.view');

$csrfToken = generateCsrfToken();

// Get filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$statusFilter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';

// Build query
$sql = '
    SELECT n.*,
           u.name as author_name,
           p.name as category_name,
           p.color as category_color
    FROM notes n
    LEFT JOIN users u ON n.created_by = u.id
    LEFT JOIN note_projects p ON n.project_id = p.id
    WHERE n.parent_id IS NULL
';

$params = [];
if ($categoryFilter) {
    $sql .= ' AND n.project_id = ?';
    $params[] = $categoryFilter;
}
if ($statusFilter !== 'all') {
    $sql .= ' AND n.status = ?';
    $params[] = $statusFilter;
}
if ($typeFilter !== 'all') {
    $sql .= ' AND n.type = ?';
    $params[] = $typeFilter;
}

$sql .= ' ORDER BY n.is_pinned DESC,
    CASE n.priority WHEN "high" THEN 1 WHEN "normal" THEN 2 WHEN "low" THEN 3 END,
    n.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Color classes with dark mode support
$colorClasses = [
    'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    'pink' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300',
];

// Type config
$typeConfig = [
    'note' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Note'],
    'idea' => ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'label' => 'Idea'],
    'task' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => 'Task'],
    'question' => ['icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Question'],
];

// Status config
$statusConfig = [
    'idea' => ['class' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300', 'label' => 'Idea'],
    'in_progress' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'In Progress'],
    'done' => ['class' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300', 'label' => 'Done'],
];

// Helper function
function buildFilterUrl($changes) {
    $params = $_GET;
    foreach ($changes as $key => $value) {
        if ($value === 'all' || $value === 0) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return '/notes' . ($params ? '?' . http_build_query($params) : '');
}
?>

<?php if ($message): ?>
<div class="mb-4 rounded-lg p-4 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Header Bar -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
    <div class="flex flex-wrap items-center gap-4">
        <!-- Add Note Button -->
        <?php if (hasPermission('notes.create')): ?>
        <button onclick="openNoteModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Note
        </button>
        <?php endif; ?>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <!-- Category Filter -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">Category:</span>
            <select onchange="window.location.href=this.value" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-1.5">
                <option value="<?= buildFilterUrl(['category' => 0]) ?>" <?= !$categoryFilter ? 'selected' : '' ?>>All</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= buildFilterUrl(['category' => $cat['id']]) ?>" <?= $categoryFilter === $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="document.getElementById('categoryModal').classList.remove('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Manage categories">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
        </div>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <!-- Status Pills -->
        <div class="flex items-center gap-1">
            <a href="<?= buildFilterUrl(['status' => 'all']) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $statusFilter === 'all' ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' ?>">All</a>
            <a href="<?= buildFilterUrl(['status' => 'idea']) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $statusFilter === 'idea' ? 'bg-yellow-500 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/50 dark:text-yellow-300' ?>">Ideas</a>
            <a href="<?= buildFilterUrl(['status' => 'in_progress']) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $statusFilter === 'in_progress' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-300' ?>">In Progress</a>
            <a href="<?= buildFilterUrl(['status' => 'done']) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $statusFilter === 'done' ? 'bg-green-500 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-300' ?>">Done</a>
        </div>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <!-- Type Pills -->
        <div class="flex items-center gap-1">
            <a href="<?= buildFilterUrl(['type' => 'all']) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $typeFilter === 'all' ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' ?>">All Types</a>
            <?php foreach ($typeConfig as $type => $config): ?>
            <a href="<?= buildFilterUrl(['type' => $type]) ?>" class="px-3 py-1 rounded-full text-xs font-medium <?= $typeFilter === $type ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' ?>"><?= $config['label'] ?>s</a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Notes Grid -->
<?php if (empty($notes)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No notes yet</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-4">Create your first note to get started.</p>
    <?php if (hasPermission('notes.create')): ?>
    <button onclick="openNoteModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
        Create Note
    </button>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($notes as $note):
        $catColor = $colorClasses[$note['category_color']] ?? $colorClasses['gray'];
        $statusInfo = $statusConfig[$note['status']] ?? $statusConfig['idea'];
        $typeInfo = $typeConfig[$note['type']] ?? $typeConfig['note'];
        $preview = strlen($note['content']) > 120 ? substr($note['content'], 0, 120) . '...' : $note['content'];
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow <?= $note['is_pinned'] ? 'ring-2 ring-yellow-400' : '' ?>" data-note-id="<?= $note['id'] ?>">
        <!-- Card Header -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <!-- Title or Type -->
                    <div class="flex items-center gap-2 mb-1">
                        <?php if ($note['is_pinned']): ?>
                        <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm2 10v-3a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H8a1 1 0 01-1-1z"/>
                        </svg>
                        <?php endif; ?>
                        <h3 class="font-medium text-gray-900 dark:text-white truncate">
                            <?= $note['title'] ? htmlspecialchars($note['title']) : $typeInfo['label'] ?>
                        </h3>
                    </div>
                    <!-- Meta -->
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-2 py-0.5 rounded-full <?= $catColor ?>"><?= htmlspecialchars($note['category_name'] ?? 'General') ?></span>
                        <span class="px-2 py-0.5 rounded-full <?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                        <?php if ($note['priority'] === 'high'): ?>
                        <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">High</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Type Icon -->
                <div class="text-gray-400 dark:text-gray-500" title="<?= $typeInfo['label'] ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $typeInfo['icon'] ?>" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Content (Collapsible) -->
        <div class="p-4">
            <!-- Preview -->
            <div class="note-preview">
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($preview) ?></p>
            </div>
            <!-- Full Content (Hidden by default) -->
            <?php if (strlen($note['content']) > 120): ?>
            <div class="note-full hidden">
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($note['content']) ?></p>
            </div>
            <button onclick="toggleNoteContent(this)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-2">Show more</button>
            <?php endif; ?>
        </div>

        <!-- Card Footer -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-b-lg border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <!-- Author & Date -->
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium"><?= htmlspecialchars($note['author_name'] ?? 'Unknown') ?></span>
                    <span class="mx-1">&bull;</span>
                    <span><?= date('M j, Y', strtotime($note['created_at'])) ?></span>
                </div>
                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <?php if (hasPermission('notes.edit')): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="toggle_pin">
                        <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                        <button type="submit" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 <?= $note['is_pinned'] ? 'text-yellow-500' : 'text-gray-400 dark:text-gray-500' ?>" title="<?= $note['is_pinned'] ? 'Unpin' : 'Pin' ?>">
                            <svg class="w-4 h-4" fill="<?= $note['is_pinned'] ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm2 10v-3a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H8a1 1 0 01-1-1z"/>
                            </svg>
                        </button>
                    </form>
                    <button onclick="openEditModal(<?= $note['id'] ?>, <?= htmlspecialchars(json_encode($note), ENT_QUOTES) ?>)" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 dark:text-gray-500" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('notes.delete')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this note?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                        <button type="submit" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add/Edit Note Modal -->
<div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 id="noteModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">New Note</h2>
                <button onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="noteForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" id="noteAction" value="create">
                <input type="hidden" name="note_id" id="noteId" value="">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title (optional)</label>
                        <input type="text" name="title" id="noteTitle" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2" placeholder="Give your note a title...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content *</label>
                        <textarea name="content" id="noteContent" rows="12" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 resize-y min-h-[200px]" placeholder="What's on your mind?"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select name="category_id" id="noteCategory" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select name="type" id="noteType" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                                <option value="note">Note</option>
                                <option value="idea">Idea</option>
                                <option value="task">Task</option>
                                <option value="question">Question</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                            <select name="priority" id="notePriority" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div id="statusField" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status" id="noteStatus" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                                <option value="idea">Idea</option>
                                <option value="in_progress">In Progress</option>
                                <option value="done">Done</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" onclick="closeNoteModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Management Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Manage Categories</h2>
                <button onclick="document.getElementById('categoryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Existing Categories -->
            <div class="mb-6 space-y-2">
                <?php
                $sortedCategories = $categories;
                usort($sortedCategories, fn($a, $b) => strcasecmp($a['name'], $b['name']));
                foreach ($sortedCategories as $cat):
                    $catColor = $colorClasses[$cat['color']] ?? $colorClasses['gray'];
                ?>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <span class="px-3 py-1 rounded-full text-sm <?= $catColor ?>"><?= htmlspecialchars($cat['name']) ?></span>
                    <?php if ($cat['id'] > 1 && hasPermission('notes.delete')): ?>
                    <form method="POST" onsubmit="return confirm('Delete this category?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Add New Category -->
            <form method="POST" class="border-t border-gray-200 dark:border-gray-600 pt-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_category">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Add New Category</h3>
                <div class="flex gap-2">
                    <input type="text" name="category_name" required placeholder="Category name" class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm">
                    <select name="category_color" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm">
                        <option value="gray">Gray</option>
                        <option value="red">Red</option>
                        <option value="orange">Orange</option>
                        <option value="yellow">Yellow</option>
                        <option value="green">Green</option>
                        <option value="blue">Blue</option>
                        <option value="purple">Purple</option>
                        <option value="pink">Pink</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNoteModal() {
    document.getElementById('noteModalTitle').textContent = 'New Note';
    document.getElementById('noteAction').value = 'create';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitle').value = '';
    document.getElementById('noteContent').value = '';
    document.getElementById('noteCategory').value = '1';
    document.getElementById('noteType').value = 'note';
    document.getElementById('notePriority').value = 'normal';
    document.getElementById('statusField').classList.add('hidden');
    document.getElementById('noteModal').classList.remove('hidden');
}

function openEditModal(id, note) {
    document.getElementById('noteModalTitle').textContent = 'Edit Note';
    document.getElementById('noteAction').value = 'update';
    document.getElementById('noteId').value = id;
    document.getElementById('noteTitle').value = note.title || '';
    document.getElementById('noteContent').value = note.content || '';
    document.getElementById('noteCategory').value = note.project_id || '1';
    document.getElementById('noteType').value = note.type || 'note';
    document.getElementById('notePriority').value = note.priority || 'normal';
    document.getElementById('noteStatus').value = note.status || 'idea';
    document.getElementById('statusField').classList.remove('hidden');
    document.getElementById('noteModal').classList.remove('hidden');
}

function closeNoteModal() {
    document.getElementById('noteModal').classList.add('hidden');
}

function toggleNoteContent(btn) {
    const card = btn.closest('[data-note-id]');
    const preview = card.querySelector('.note-preview');
    const full = card.querySelector('.note-full');

    if (full.classList.contains('hidden')) {
        preview.classList.add('hidden');
        full.classList.remove('hidden');
        btn.textContent = 'Show less';
    } else {
        preview.classList.remove('hidden');
        full.classList.add('hidden');
        btn.textContent = 'Show more';
    }
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('noteModal').classList.add('hidden');
        document.getElementById('categoryModal').classList.add('hidden');
    }
});

// Close modals on backdrop click
document.getElementById('noteModal').addEventListener('click', function(e) {
    if (e.target === this) closeNoteModal();
});
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>

<?php include 'includes/dashboard-footer.php'; ?>
