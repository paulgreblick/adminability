<?php
/**
 * Notes v2 - Flat list with inline actions
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'note';
                $priority = $_POST['priority'] ?? 'normal';

                if (empty($content)) {
                    $message = 'Note content is required.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notes (title, content, type, priority, created_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title ?: null, $content, $type, $priority, $_SESSION['user_id']]);
                    $message = 'Note created.';
                    $messageType = 'success';
                }
                break;

            case 'update':
                $noteId = (int)($_POST['note_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'note';
                $status = $_POST['status'] ?? 'active';
                $priority = $_POST['priority'] ?? 'normal';

                if (empty($content)) {
                    $message = 'Note content is required.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, type = ?, status = ?, priority = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$title ?: null, $content, $type, $status, $priority, $noteId]);
                    $message = 'Note updated.';
                    $messageType = 'success';
                }
                break;

            case 'toggle_pin':
                $noteId = (int)($_POST['note_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE notes SET is_pinned = CASE WHEN is_pinned = 1 THEN 0 ELSE 1 END, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$noteId]);
                break;

            case 'toggle_done':
                $noteId = (int)($_POST['note_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE notes SET status = CASE WHEN status = 'done' THEN 'active' ELSE 'done' END, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$noteId]);
                break;

            case 'delete':
                $noteId = (int)($_POST['note_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
                $stmt->execute([$noteId]);
                $message = 'Note deleted.';
                $messageType = 'success';
                break;
        }
    }
}

$dashboard_title = 'Notes';
$current_dashboard_page = 'notes';
include 'includes/dashboard-layout.php';

$csrfToken = generateCsrfToken();

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$sql = 'SELECT n.*, u.name as author_name FROM notes n LEFT JOIN users u ON n.created_by = u.id';
$params = [];

switch ($filter) {
    case 'pinned':
        $sql .= ' WHERE n.is_pinned = 1';
        break;
    case 'ideas':
        $sql .= " WHERE n.type = 'idea' AND n.status != 'archived'";
        break;
    case 'tasks':
        $sql .= " WHERE n.type = 'task' AND n.status != 'archived'";
        break;
    case 'done':
        $sql .= " WHERE n.status = 'done'";
        break;
    case 'archived':
        $sql .= " WHERE n.status = 'archived'";
        break;
    default:
        $sql .= " WHERE n.status != 'archived'";
        break;
}

$sql .= ' ORDER BY n.is_pinned DESC, n.updated_at DESC';
$notes = $pdo->query($sql)->fetchAll();

// Type icons
$typeIcons = [
    'note' => '<span class="text-gray-400" title="Note">&#x1F4DD;</span>',
    'idea' => '<span class="text-yellow-500" title="Idea">&#x1F4A1;</span>',
    'task' => '<span class="text-blue-500" title="Task">&#x2705;</span>',
];
?>

<?php if ($message): ?>
<div class="mb-4 rounded-md p-3 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Header with filter and add button -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
    <!-- Filter pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <?php
        $filters = ['all' => 'All', 'pinned' => 'Pinned', 'ideas' => 'Ideas', 'tasks' => 'Tasks', 'done' => 'Done'];
        foreach ($filters as $key => $label):
        ?>
        <a href="/notes?filter=<?= $key ?>" class="px-3 py-1 rounded-full text-sm whitespace-nowrap <?= $filter === $key ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('newNoteModal').classList.remove('hidden')"
        class="sm:ml-auto px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
        + New Note
    </button>
</div>

<!-- Notes List -->
<?php if (empty($notes)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center text-gray-500 dark:text-gray-400">
    No notes yet.
</div>
<?php else: ?>
<div class="space-y-2">
    <?php foreach ($notes as $note):
        $isDone = $note['status'] === 'done';
        $isPinned = $note['is_pinned'];
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 <?= $isDone ? 'opacity-60' : '' ?> <?= $isPinned ? 'border-l-4 border-yellow-400' : '' ?>">
        <div class="flex items-start gap-3">
            <!-- Type icon + pin -->
            <div class="flex-shrink-0 pt-0.5">
                <?= $typeIcons[$note['type']] ?? $typeIcons['note'] ?>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <?php if ($note['title']): ?>
                <div class="font-medium text-gray-900 dark:text-white <?= $isDone ? 'line-through' : '' ?>"><?= htmlspecialchars($note['title']) ?></div>
                <?php endif; ?>
                <div class="text-sm text-gray-600 dark:text-gray-400 <?= $isDone ? 'line-through' : '' ?> whitespace-pre-line"><?= htmlspecialchars($note['content']) ?></div>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                    <span><?= htmlspecialchars($note['author_name'] ?? 'Unknown') ?></span>
                    <span><?= date('M j', strtotime($note['updated_at'] ?? $note['created_at'])) ?></span>
                    <?php if ($note['priority'] === 'high'): ?>
                    <span class="text-red-500 font-medium">High priority</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1 flex-shrink-0">
                <!-- Pin toggle -->
                <form method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="toggle_pin">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <button type="submit" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 <?= $isPinned ? 'text-yellow-500' : 'text-gray-400' ?>" title="<?= $isPinned ? 'Unpin' : 'Pin' ?>">
                        <svg class="w-4 h-4" fill="<?= $isPinned ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>
                </form>

                <!-- Done toggle (tasks only) -->
                <?php if ($note['type'] === 'task'): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="toggle_done">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <button type="submit" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 <?= $isDone ? 'text-green-500' : 'text-gray-400' ?>" title="<?= $isDone ? 'Reopen' : 'Mark done' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Edit -->
                <button type="button" onclick='openEditModal(<?= json_encode($note) ?>)' class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>

                <!-- Delete -->
                <form method="POST" class="inline" onsubmit="return confirm('Delete this note?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <button type="submit" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-red-500" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- New Note Modal -->
<div id="newNoteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">New Note</h2>
            <button onclick="document.getElementById('newNoteModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                            <option value="note">Note</option>
                            <option value="idea">Idea</option>
                            <option value="task">Task</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                        <select name="priority" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title (optional)</label>
                    <input type="text" name="title" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2" placeholder="Note title">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                    <textarea name="content" rows="4" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2" placeholder="Write your note..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('newNoteModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Note Modal -->
<div id="editNoteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Edit Note</h2>
            <button onclick="document.getElementById('editNoteModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <form method="POST" id="editNoteForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="note_id" id="edit_note_id">
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type" id="edit_type" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                            <option value="note">Note</option>
                            <option value="idea">Idea</option>
                            <option value="task">Task</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                        <select name="priority" id="edit_priority" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                        <option value="active">Active</option>
                        <option value="done">Done</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title (optional)</label>
                    <input type="text" name="title" id="edit_title" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                    <textarea name="content" id="edit_content" rows="4" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2"></textarea>
                </div>
            </div>
            <div class="mt-6 flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('editNoteModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(note) {
    document.getElementById('edit_note_id').value = note.id;
    document.getElementById('edit_title').value = note.title || '';
    document.getElementById('edit_content').value = note.content;
    document.getElementById('edit_type').value = note.type;
    document.getElementById('edit_priority').value = note.priority;
    document.getElementById('edit_status').value = note.status;
    document.getElementById('editNoteModal').classList.remove('hidden');
}
</script>

<?php include 'includes/dashboard-footer.php'; ?>
