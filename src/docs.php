<?php
/**
 * Knowledge Base - Flat List with Tags
 * Left: Doc list with tag filter | Right: Document content
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

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

        switch ($action) {
            case 'create_doc':

                $title = trim($_POST['title'] ?? '');

                if ($title) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');

                    // Ensure unique slug
                    $baseSlug = $slug;
                    $counter = 1;
                    while (true) {
                        $stmt = $pdo->prepare('SELECT id FROM docs WHERE slug = ?');
                        $stmt->execute([$slug]);
                        if (!$stmt->fetch()) break;
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    $stmt = $pdo->prepare('INSERT INTO docs (title, slug, status, created_by) VALUES (?, ?, "published", ?)');
                    $stmt->execute([$title, $slug, $_SESSION['user_id']]);
                    $newId = $pdo->lastInsertId();

                    header("Location: /docs?id=$newId&edit=1");
                    exit;
                }
                break;

            case 'save_doc':

                $docId = (int)($_POST['doc_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = $_POST['content'] ?? '';
                $tagIds = $_POST['tags'] ?? [];

                if ($docId && $title) {
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

                    header("Location: /docs?id=$docId&saved=1");
                    exit;
                }
                break;

            case 'delete_doc':

                $docId = (int)($_POST['doc_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM docs WHERE id = ?');
                $stmt->execute([$docId]);

                header("Location: /docs?deleted=1");
                exit;

            case 'add_tag':

                $name = trim($_POST['tag_name'] ?? '');
                $color = $_POST['tag_color'] ?? 'gray';
                if ($name) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                    $stmt = $pdo->prepare('INSERT IGNORE INTO doc_tags (name, slug, color) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $color]);
                    $message = 'Tag created.';
                    $messageType = 'success';
                }
                break;

            case 'delete_tag':

                $tagId = (int)($_POST['tag_id'] ?? 0);
                $pdo->prepare('DELETE FROM doc_tags WHERE id = ?')->execute([$tagId]);
                $message = 'Tag deleted.';
                $messageType = 'success';
                break;
        }
    }
}

// Check for success messages from redirects
if (isset($_GET['saved'])) {
    $message = 'Document saved.';
    $messageType = 'success';
}
if (isset($_GET['deleted'])) {
    $message = 'Document deleted.';
    $messageType = 'success';
}

$dashboard_title = 'Knowledge Base';
$current_dashboard_page = 'docs';
include 'includes/dashboard-layout.php';

// Get filter tag
$filterTag = isset($_GET['tag']) ? (int)$_GET['tag'] : null;

// Get selected doc
$selectedDocId = (int)($_GET['id'] ?? 0);
$editMode = isset($_GET['edit']);
$selectedDoc = null;

if ($selectedDocId) {
    $stmt = $pdo->prepare('
        SELECT d.*, u.name as author_name
        FROM docs d
        LEFT JOIN users u ON d.created_by = u.id
        WHERE d.id = ?
    ');
    $stmt->execute([$selectedDocId]);
    $selectedDoc = $stmt->fetch();

    // Get tags for this doc
    if ($selectedDoc) {
        $stmt = $pdo->prepare('
            SELECT t.* FROM doc_tags t
            JOIN doc_tag_map m ON t.id = m.tag_id
            WHERE m.doc_id = ?
            ORDER BY t.name
        ');
        $stmt->execute([$selectedDocId]);
        $selectedDoc['tags'] = $stmt->fetchAll();
    }
}

// Get all tags
$allTags = $pdo->query('SELECT * FROM doc_tags ORDER BY name')->fetchAll();

// Get all docs (with optional tag filter)
if ($filterTag) {
    $stmt = $pdo->prepare('
        SELECT DISTINCT d.id, d.title, d.updated_at, d.created_at
        FROM docs d
        JOIN doc_tag_map m ON d.id = m.doc_id
        WHERE m.tag_id = ?
        ORDER BY d.updated_at DESC
    ');
    $stmt->execute([$filterTag]);
    $allDocs = $stmt->fetchAll();
} else {
    $allDocs = $pdo->query('SELECT id, title, updated_at, created_at FROM docs ORDER BY updated_at DESC')->fetchAll();
}

// Get tags for each doc (for display in list)
$docTags = [];
$tagQuery = $pdo->query('
    SELECT m.doc_id, t.id, t.name, t.color
    FROM doc_tag_map m
    JOIN doc_tags t ON t.id = m.tag_id
');
foreach ($tagQuery as $row) {
    if (!isset($docTags[$row['doc_id']])) {
        $docTags[$row['doc_id']] = [];
    }
    $docTags[$row['doc_id']][] = $row;
}

$csrfToken = generateCsrfToken();

$tagColors = [
    'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
];
?>

<?php if ($message): ?>
<div class="mb-4 rounded-md p-3 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Two-Panel Layout -->
<div class="flex gap-0 -mx-4 sm:-mx-6 lg:-mx-8 -mb-6 h-[calc(100vh-140px)]">

    <!-- Left Panel: Doc List -->
    <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">

        <!-- New Doc Button -->
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <button onclick="document.getElementById('newDocModal').classList.remove('hidden')"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded-md flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Document
            </button>
        </div>

        <!-- Tag Filter -->
        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="/docs" class="text-xs px-2 py-1 rounded-full <?= !$filterTag ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' ?>">
                    All
                </a>
                <?php foreach ($allTags as $tag): ?>
                <a href="/docs?tag=<?= $tag['id'] ?>"
                   class="text-xs px-2 py-1 rounded-full <?= $filterTag == $tag['id'] ? 'bg-blue-600 text-white' : ($tagColors[$tag['color']] ?? $tagColors['gray']) . ' hover:opacity-80' ?>">
                    <?= htmlspecialchars($tag['name']) ?>
                </a>
                <?php endforeach; ?>
                <button onclick="document.getElementById('manageTagsModal').classList.remove('hidden')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Doc List -->
        <div class="flex-1 overflow-y-auto">
            <?php if (empty($allDocs)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No documents yet</p>
            <?php else: ?>
            <?php foreach ($allDocs as $doc):
                $isSelected = $doc['id'] == $selectedDocId;
                $tags = $docTags[$doc['id']] ?? [];
            ?>
            <a href="/docs?id=<?= $doc['id'] ?><?= $filterTag ? '&tag='.$filterTag : '' ?>"
               class="block px-3 py-3 border-b border-gray-100 dark:border-gray-700 <?= $isSelected ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' ?>">
                <div class="font-medium text-sm text-gray-900 dark:text-white truncate"><?= htmlspecialchars($doc['title']) ?></div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-400"><?= date('M j', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?></span>
                    <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                    <span class="text-xs px-1.5 py-0.5 rounded <?= $tagColors[$tag['color']] ?? $tagColors['gray'] ?>"><?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($tags) > 2): ?>
                    <span class="text-xs text-gray-400">+<?= count($tags) - 2 ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Doc Count -->
        <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            <?= count($allDocs) ?> document<?= count($allDocs) !== 1 ? 's' : '' ?>
        </div>
    </div>

    <!-- Right Panel: Content -->
    <div class="flex-1 bg-gray-50 dark:bg-gray-900 overflow-y-auto">
        <?php if ($selectedDoc): ?>
            <?php if ($editMode): ?>
            <!-- Edit Mode -->
            <form method="POST" id="docForm" class="h-full flex flex-col">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_doc">
                <input type="hidden" name="doc_id" value="<?= $selectedDoc['id'] ?>">

                <!-- Edit Header -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                    <div class="flex items-center gap-4 mb-3">
                        <input type="text" name="title" value="<?= htmlspecialchars($selectedDoc['title']) ?>" required
                            class="flex-1 text-lg font-semibold text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0"
                            placeholder="Document title">

                        <a href="/docs?id=<?= $selectedDoc['id'] ?>" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Cancel</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-4 rounded">Save</button>
                    </div>
                    <!-- Tag Selection -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Tags:</span>
                        <?php foreach ($allTags as $tag):
                            $isTagged = false;
                            foreach ($selectedDoc['tags'] as $t) {
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
                    </div>
                </div>

                <!-- Quill Editor -->
                <input type="hidden" name="content" value="">
                <div class="flex-1 bg-white dark:bg-gray-800">
                    <div id="editor"><?= $selectedDoc['content'] ?? '' ?></div>
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
            .ql-container { font-size: 16px; border: none !important; }
            .ql-editor { min-height: 400px; line-height: 1.6; }
            .ql-toolbar { border-left: none !important; border-right: none !important; border-top: none !important; background: #f9fafb; }
            .dark .ql-toolbar { background: #374151; }
            .dark .ql-stroke { stroke: #d1d5db !important; }
            .dark .ql-fill { fill: #d1d5db !important; }
            .dark .ql-picker-label { color: #d1d5db !important; }
            .dark .ql-editor { color: #f3f4f6; }
            </style>

            <?php else: ?>
            <!-- View Mode -->
            <div class="h-full flex flex-col">
                <!-- View Header -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($selectedDoc['title']) ?></h1>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= htmlspecialchars($selectedDoc['author_name'] ?? 'Unknown') ?> · <?= date('M j, Y', strtotime($selectedDoc['updated_at'] ?? $selectedDoc['created_at'])) ?>
                                </span>
                                <?php foreach ($selectedDoc['tags'] as $tag): ?>
                                <span class="text-xs px-2 py-1 rounded-full <?= $tagColors[$tag['color']] ?? $tagColors['gray'] ?>"><?= htmlspecialchars($tag['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/doc?id=<?= $selectedDoc['id'] ?>" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" title="Open full page">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                            </a>
                            <a href="/docs?id=<?= $selectedDoc['id'] ?>&edit=1" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-4 rounded">Edit</a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this document?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_doc">
                                <input type="hidden" name="doc_id" value="<?= $selectedDoc['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto">
                    <div class="bg-white dark:bg-gray-800 min-h-full">
                        <?php if ($selectedDoc['content']): ?>
                        <div class="prose dark:prose-invert max-w-none p-6">
                            <?= $selectedDoc['content'] ?>
                        </div>
                        <?php else: ?>
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            <p>No content yet.</p>
                            <a href="/docs?id=<?= $selectedDoc['id'] ?>&edit=1" class="text-blue-600 hover:text-blue-700">Add content</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
        <!-- Empty State -->
        <div class="h-full flex items-center justify-center">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Knowledge Base</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Select a document or create a new one.</p>
                <button onclick="document.getElementById('newDocModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Document
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Document Modal -->
<div id="newDocModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">New Document</h2>
            <button onclick="document.getElementById('newDocModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="create_doc">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                <input type="text" name="title" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2" placeholder="Document title" autofocus>
            </div>
            <div class="mt-6 flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('newDocModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Tags Modal -->
<div id="manageTagsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Manage Tags</h2>
            <button onclick="document.getElementById('manageTagsModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>

        <!-- Existing Tags -->
        <div class="mb-4 space-y-2">
            <?php foreach ($allTags as $tag): ?>
            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded">
                <span class="text-sm px-2 py-1 rounded-full <?= $tagColors[$tag['color']] ?? $tagColors['gray'] ?>"><?= htmlspecialchars($tag['name']) ?></span>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this tag?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_tag">
                    <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php if (empty($allTags)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">No tags yet</p>
            <?php endif; ?>
        </div>

        <!-- Add New Tag -->
        <form method="POST" class="border-t border-gray-200 dark:border-gray-600 pt-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="add_tag">
            <div class="flex gap-2">
                <input type="text" name="tag_name" required class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm" placeholder="New tag name">
                <select name="tag_color" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-2 py-2 text-sm">
                    <option value="gray">Gray</option>
                    <option value="blue">Blue</option>
                    <option value="green">Green</option>
                    <option value="purple">Purple</option>
                    <option value="orange">Orange</option>
                    <option value="red">Red</option>
                    <option value="yellow">Yellow</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm">Add</button>
            </div>
        </form>
    </div>
</div>

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
