<?php
/**
 * Single Document View - Full Page
 * Distraction-free reading and editing
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

requireLogin();

$docId = (int)($_GET['id'] ?? 0);

if (!$docId) {
    header('Location: /docs');
    exit;
}

$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            $tagIds = $_POST['tags'] ?? [];

            if ($title) {
                $stmt = $pdo->prepare("UPDATE docs SET title = ?, content = ?, updated_by = ?, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$title, $content, $_SESSION['user_id'], $docId]);

                // Update tags
                $pdo->prepare('DELETE FROM doc_tag_map WHERE doc_id = ?')->execute([$docId]);
                if (!empty($tagIds)) {
                    $insertStmt = $pdo->prepare('INSERT INTO doc_tag_map (doc_id, tag_id) VALUES (?, ?)');
                    foreach ($tagIds as $tagId) {
                        $insertStmt->execute([$docId, (int)$tagId]);
                    }
                }

                if (isset($_POST['save_and_close'])) {
                    header("Location: /docs?id=$docId&saved=1");
                    exit;
                }

                $message = 'Document saved.';
                $messageType = 'success';
            }
        }
    }
}

$editMode = isset($_GET['edit']);
$csrfToken = generateCsrfToken();

// Get document
$stmt = $pdo->prepare('
    SELECT d.*, u.name as author_name
    FROM docs d
    LEFT JOIN users u ON d.created_by = u.id
    WHERE d.id = ?
');
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: /docs');
    exit;
}

// Get tags for this doc
$stmt = $pdo->prepare('
    SELECT t.* FROM doc_tags t
    JOIN doc_tag_map m ON t.id = m.tag_id
    WHERE m.doc_id = ?
    ORDER BY t.name
');
$stmt->execute([$docId]);
$docTags = $stmt->fetchAll();

// Get all tags (for edit mode)
$allTags = $pdo->query('SELECT * FROM doc_tags ORDER BY name')->fetchAll();

$tagColors = [
    'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
];

$dashboard_title = $doc['title'];
$current_dashboard_page = 'docs';

include 'includes/dashboard-layout.php';
?>

<?php if ($message): ?>
<div class="mb-4 rounded-md p-3 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav class="mb-4">
    <a href="/docs?id=<?= $docId ?>" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Knowledge Base
    </a>
</nav>

<?php if ($editMode): ?>
<!-- Edit Mode -->
<form method="POST" id="docForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="action" value="save">

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 mb-3">
                <input type="text" name="title" value="<?= htmlspecialchars($doc['title']) ?>" required
                    class="flex-1 text-xl font-semibold text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0"
                    placeholder="Document title">

                <a href="/doc?id=<?= $docId ?>" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Cancel</a>
                <button type="submit" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-white text-sm font-medium py-1.5 px-4 rounded">Save</button>
                <button type="submit" name="save_and_close" value="1" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-4 rounded">Save & Close</button>
            </div>
            <!-- Tag Selection -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-gray-500 dark:text-gray-400">Tags:</span>
                <?php foreach ($allTags as $tag):
                    $isTagged = false;
                    foreach ($docTags as $t) {
                        if ($t['id'] == $tag['id']) { $isTagged = true; break; }
                    }
                ?>
                <label class="cursor-pointer">
                    <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" <?= $isTagged ? 'checked' : '' ?> class="sr-only peer">
                    <span class="text-xs px-2 py-1 rounded-full border-2 peer-checked:border-blue-500 border-transparent <?= $tagColors[$tag['color']] ?? $tagColors['gray'] ?>">
                        <?= htmlspecialchars($tag['name']) ?>
                    </span>
                </label>
                <?php endforeach; ?>
                <?php if (empty($allTags)): ?>
                <span class="text-xs text-gray-400">No tags available</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quill Editor -->
        <input type="hidden" name="content" value="">
        <div id="editor"><?= $doc['content'] ?? '' ?></div>
    </div>
</form>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
var quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Start writing...',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});
document.getElementById('docForm').onsubmit = function() {
    document.querySelector('input[name="content"]').value = quill.root.innerHTML;
};
</script>
<style>
.ql-container { font-size: 16px; border: none !important; height: 500px; }
.ql-editor { line-height: 1.6; }
.ql-toolbar { border-left: none !important; border-right: none !important; border-top: none !important; background: #f9fafb; }
.dark .ql-toolbar { background: #374151; }
.dark .ql-stroke { stroke: #d1d5db !important; }
.dark .ql-fill { fill: #d1d5db !important; }
.dark .ql-picker-label { color: #d1d5db !important; }
.dark .ql-editor { color: #f3f4f6; }
</style>

<?php else: ?>
<!-- View Mode -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($doc['title']) ?></h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <?= htmlspecialchars($doc['author_name'] ?? 'Unknown') ?> · <?= date('M j, Y', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?>
                    </span>
                    <?php foreach ($docTags as $tag): ?>
                    <span class="text-xs px-2 py-1 rounded-full <?= $tagColors[$tag['color']] ?? $tagColors['gray'] ?>"><?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="/doc?id=<?= $docId ?>&edit=1" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded">Edit</a>
        </div>
    </div>

    <!-- Content -->
    <div class="px-6 py-6">
        <?php if ($doc['content']): ?>
        <div class="prose dark:prose-invert max-w-none">
            <?= $doc['content'] ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No content yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
.prose h1 { font-size: 2em; font-weight: bold; margin-bottom: 0.5em; }
.prose h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; margin-top: 1em; }
.prose h3 { font-size: 1.25em; font-weight: bold; margin-bottom: 0.5em; margin-top: 1em; }
.prose p { margin-bottom: 1em; line-height: 1.6; }
.prose ul, .prose ol { margin-bottom: 1em; padding-left: 1.5em; }
.prose ul { list-style-type: disc; }
.prose ol { list-style-type: decimal; }
.prose li { margin-bottom: 0.25em; }
.prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; color: #6b7280; margin: 1em 0; }
.prose pre { background: #1f2937; color: #f9fafb; padding: 1em; border-radius: 0.5em; overflow-x: auto; }
.prose code { background: #f3f4f6; padding: 0.125em 0.25em; border-radius: 0.25em; font-size: 0.875em; }
.prose a { color: #2563eb; text-decoration: underline; }
.prose table { width: 100%; border-collapse: collapse; margin: 1em 0; }
.prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.5em; text-align: left; }
.prose th { background: #f9fafb; font-weight: 600; }
.dark .prose { color: #f3f4f6; }
.dark .prose h1, .dark .prose h2, .dark .prose h3 { color: #f3f4f6; }
.dark .prose code { background: #374151; color: #f3f4f6; }
.dark .prose th { background: #374151; }
.dark .prose th, .dark .prose td { border-color: #4b5563; }
</style>

<?php include 'includes/dashboard-footer.php'; ?>
