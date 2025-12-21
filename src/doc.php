<?php
/**
 * Single Document View/Edit
 * WYSIWYG editor using Quill
 */

// Include auth first to handle POST before any output
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

requirePermission('docs.view');

$docId = (int)($_GET['id'] ?? 0);

if (!$docId) {
    header('Location: /docs');
    exit;
}

$message = '';
$messageType = '';

// Handle POST actions BEFORE including layout (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'save':
                if (!hasPermission('docs.edit')) break;

                $title = trim($_POST['title'] ?? '');
                $content = $_POST['content'] ?? '';
                $docType = $_POST['doc_type'] ?? 'reference';
                $status = $_POST['status'] ?? 'draft';

                if ($title) {
                    $stmt = $pdo->prepare('UPDATE docs SET title = ?, content = ?, doc_type = ?, status = ?, updated_by = ? WHERE id = ?');
                    $stmt->execute([$title, $content, $docType, $status, $_SESSION['user_id'], $docId]);
                    $message = 'Document saved.';
                    $messageType = 'success';

                    // Redirect to view mode after save
                    if (isset($_POST['save_and_view'])) {
                        header("Location: /doc?id=$docId");
                        exit;
                    }
                }
                break;

            case 'create_subpage':
                if (!hasPermission('docs.create')) break;

                $title = trim($_POST['subpage_title'] ?? '');
                $docType = $_POST['subpage_type'] ?? 'reference';

                // Get parent's category
                $stmt = $pdo->prepare('SELECT category_id FROM docs WHERE id = ?');
                $stmt->execute([$docId]);
                $parent = $stmt->fetch();
                $categoryId = $parent['category_id'];

                if ($title && $categoryId) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');

                    $stmt = $pdo->prepare('INSERT INTO docs (category_id, parent_id, title, slug, doc_type, status, created_by) VALUES (?, ?, ?, ?, ?, "draft", ?)');
                    $stmt->execute([$categoryId, $docId, $title, $slug, $docType, $_SESSION['user_id']]);
                    $newId = $pdo->lastInsertId();

                    header("Location: /doc?id=$newId&edit=1");
                    exit;
                }
                break;
        }
    }
}

// Now we can determine edit mode (after auth is loaded)
$editMode = isset($_GET['edit']) && hasPermission('docs.edit');

$csrfToken = generateCsrfToken();

// Get document
$stmt = $pdo->prepare('
    SELECT d.*,
           c.name as category_name,
           c.color as category_color,
           u.name as author_name,
           u2.name as updater_name
    FROM docs d
    LEFT JOIN doc_categories c ON d.category_id = c.id
    LEFT JOIN users u ON d.created_by = u.id
    LEFT JOIN users u2 ON d.updated_by = u2.id
    WHERE d.id = ?
');
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: /docs');
    exit;
}

// Get parent breadcrumb
$breadcrumbs = [];
$currentParentId = $doc['parent_id'];
while ($currentParentId) {
    $stmt = $pdo->prepare('SELECT id, title, parent_id FROM docs WHERE id = ?');
    $stmt->execute([$currentParentId]);
    $parentDoc = $stmt->fetch();
    if ($parentDoc) {
        array_unshift($breadcrumbs, $parentDoc);
        $currentParentId = $parentDoc['parent_id'];
    } else {
        break;
    }
}

// Get sub-pages
$stmt = $pdo->prepare('SELECT id, title, doc_type, status FROM docs WHERE parent_id = ? ORDER BY sort_order, title');
$stmt->execute([$docId]);
$subpages = $stmt->fetchAll();

// Get siblings for navigation
$stmt = $pdo->prepare('SELECT id, title FROM docs WHERE parent_id <=> ? AND category_id = ? AND id != ? ORDER BY sort_order, title');
$stmt->execute([$doc['parent_id'], $doc['category_id'], $docId]);
$siblings = $stmt->fetchAll();

$dashboard_title = $doc['title'];
$current_dashboard_page = 'docs';

// Now include the layout (which outputs HTML)
include 'includes/dashboard-layout.php';

// Type labels
$typeLabels = [
    'reference' => 'Reference',
    'process' => 'Process',
    'workflow' => 'Workflow',
    'guide' => 'Guide',
];
?>

<?php if ($message): ?>
<div class="mb-6 rounded-md p-4 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Breadcrumbs -->
<nav class="mb-4 text-sm">
    <ol class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
        <li><a href="/docs" class="hover:text-blue-600 dark:hover:text-blue-400">Docs</a></li>
        <li>/</li>
        <li><a href="/docs?category=<?= $doc['category_id'] ?>" class="hover:text-blue-600 dark:hover:text-blue-400"><?= htmlspecialchars($doc['category_name']) ?></a></li>
        <?php foreach ($breadcrumbs as $crumb): ?>
        <li>/</li>
        <li><a href="/doc?id=<?= $crumb['id'] ?>" class="hover:text-blue-600 dark:hover:text-blue-400"><?= htmlspecialchars($crumb['title']) ?></a></li>
        <?php endforeach; ?>
        <li>/</li>
        <li class="text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($doc['title']) ?></li>
    </ol>
</nav>

<?php if ($editMode): ?>
<!-- Edit Mode -->
<form method="POST" id="docForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="content" id="contentInput">

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Editor Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <input type="text" name="title" value="<?= htmlspecialchars($doc['title']) ?>" required
                class="text-xl font-semibold text-gray-900 dark:text-white dark:bg-gray-800 border-0 focus:ring-0 p-0 w-full max-w-xl" placeholder="Document title">

            <div class="flex items-center gap-3">
                <select name="doc_type" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    <?php foreach ($typeLabels as $type => $label): ?>
                    <option value="<?= $type ?>" <?= $doc['doc_type'] === $type ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    <option value="draft" <?= $doc['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $doc['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= $doc['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
        </div>

        <!-- Quill Editor -->
        <div id="editor" class="min-h-[400px]"><?= $doc['content'] ?></div>

        <!-- Save Bar -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700">
            <a href="/doc?id=<?= $docId ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Cancel</a>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-500 rounded-md text-sm font-medium">Save Draft</button>
                <button type="submit" name="save_and_view" value="1" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">Save & View</button>
            </div>
        </div>
    </div>
</form>

<!-- Quill CSS & JS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
var quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Start writing...',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            ['blockquote', 'code-block'],
            ['link'],
            ['clean']
        ]
    }
});

// Save content to hidden input before submit
document.getElementById('docForm').onsubmit = function() {
    document.getElementById('contentInput').value = quill.root.innerHTML;
};
</script>

<style>
#editor {
    font-size: 16px;
    line-height: 1.6;
}
.ql-container {
    border: none !important;
    font-family: inherit;
}
.ql-toolbar {
    border-left: none !important;
    border-right: none !important;
    border-top: none !important;
    background: #f9fafb;
}
.ql-editor {
    padding: 2rem;
    min-height: 400px;
}
.ql-editor h1 { font-size: 2em; font-weight: bold; margin-bottom: 0.5em; }
.ql-editor h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; }
.ql-editor h3 { font-size: 1.25em; font-weight: bold; margin-bottom: 0.5em; }
.ql-editor p { margin-bottom: 1em; }
.ql-editor ul, .ql-editor ol { margin-bottom: 1em; padding-left: 1.5em; }
.ql-editor blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; color: #6b7280; }
.ql-editor pre { background: #1f2937; color: #f9fafb; padding: 1em; border-radius: 0.5em; }
</style>

<?php else: ?>
<!-- View Mode -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-3">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <!-- Doc Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($doc['title']) ?></h1>
                        <div class="mt-2 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span><?= htmlspecialchars($doc['author_name'] ?? 'Unknown') ?></span>
                            <span>•</span>
                            <span><?= date('M j, Y', strtotime($doc['updated_at'])) ?></span>
                            <?php if ($doc['status'] === 'draft'): ?>
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">Draft</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (hasPermission('docs.edit')): ?>
                    <a href="/doc?id=<?= $docId ?>&edit=1" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                        Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Doc Content -->
            <div class="px-6 py-6 prose dark:prose-invert max-w-none">
                <?php if ($doc['content']): ?>
                <?= $doc['content'] ?>
                <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 italic">No content yet. Click Edit to add content.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sub-pages -->
        <?php if (!empty($subpages)): ?>
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-medium text-gray-900 dark:text-white">Sub-pages</h2>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($subpages as $sub): ?>
                <li>
                    <a href="/doc?id=<?= $sub['id'] ?>" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($sub['title']) ?></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><?= $typeLabels[$sub['doc_type']] ?? 'Doc' ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Add Sub-page -->
        <?php if (hasPermission('docs.create')): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="font-medium text-gray-900 dark:text-white mb-3">Add Sub-page</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="create_subpage">
                <input type="text" name="subpage_title" placeholder="Sub-page title" required class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded mb-2 px-3 py-2 border">
                <select name="subpage_type" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded mb-2 px-3 py-2 border">
                    <option value="reference">Reference</option>
                    <option value="process">Process</option>
                    <option value="workflow">Workflow</option>
                    <option value="guide">Guide</option>
                </select>
                <button type="submit" class="w-full bg-gray-800 dark:bg-gray-600 text-white text-sm py-2 rounded hover:bg-gray-900 dark:hover:bg-gray-500">Create</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Navigation -->
        <?php if (!empty($siblings)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-white">Related</h3>
            </div>
            <ul class="p-2">
                <?php foreach ($siblings as $sib): ?>
                <li>
                    <a href="/doc?id=<?= $sib['id'] ?>" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                        <?= htmlspecialchars($sib['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Meta -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="font-medium text-gray-900 dark:text-white mb-3">Details</h3>
            <dl class="text-sm space-y-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Category</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($doc['category_name']) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                    <dd class="text-gray-900 dark:text-white"><?= $typeLabels[$doc['doc_type']] ?? 'Unknown' ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="text-gray-900 dark:text-white"><?= date('M j, Y', strtotime($doc['created_at'])) ?></dd>
                </div>
                <?php if ($doc['updated_by']): ?>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Last edited by</dt>
                    <dd class="text-gray-900 dark:text-white"><?= htmlspecialchars($doc['updater_name'] ?? 'Unknown') ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
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
.prose strong { font-weight: bold; }
.prose em { font-style: italic; }
.dark .prose { color: #f3f4f6; }
.dark .prose h1, .dark .prose h2, .dark .prose h3 { color: #f3f4f6; }
.dark .prose code { background: #374151; color: #f3f4f6; }
.dark .prose blockquote { color: #9ca3af; }
</style>
<?php endif; ?>

<?php include 'includes/dashboard-footer.php'; ?>
