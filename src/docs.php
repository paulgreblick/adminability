<?php
/**
 * Documentation / Knowledge Base
 * Tree-based navigation with collapsible categories and docs
 */

// Include auth first to handle POST before any output
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

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
            case 'create_doc':
                if (!hasPermission('docs.create')) break;

                $categoryId = (int)($_POST['category_id'] ?? 0);
                $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
                $title = trim($_POST['title'] ?? '');
                $docType = $_POST['doc_type'] ?? 'reference';

                if ($categoryId && $title) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');

                    // Ensure unique slug within category
                    $baseSlug = $slug;
                    $counter = 1;
                    while (true) {
                        $stmt = $pdo->prepare('SELECT id FROM docs WHERE category_id = ? AND slug = ?');
                        $stmt->execute([$categoryId, $slug]);
                        if (!$stmt->fetch()) break;
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    $stmt = $pdo->prepare('INSERT INTO docs (category_id, parent_id, title, slug, doc_type, status, created_by) VALUES (?, ?, ?, ?, ?, "draft", ?)');
                    $stmt->execute([$categoryId, $parentId, $title, $slug, $docType, $_SESSION['user_id']]);
                    $newId = $pdo->lastInsertId();

                    header("Location: /doc?id=$newId&edit=1");
                    exit;
                }
                break;

            case 'delete_doc':
                if (!hasPermission('docs.delete')) break;

                $docId = (int)($_POST['doc_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM docs WHERE id = ?');
                $stmt->execute([$docId]);
                $message = 'Document deleted.';
                $messageType = 'success';
                break;

            case 'add_category':
                if (!hasPermission('docs.create')) break;

                $name = trim($_POST['category_name'] ?? '');
                $color = $_POST['category_color'] ?? 'gray';
                if ($name) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                    $stmt = $pdo->prepare('INSERT INTO doc_categories (name, slug, color) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $color]);
                    $message = 'Category created.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

// Now include the layout (which outputs HTML)
$dashboard_title = 'Docs';
$current_dashboard_page = 'docs';
include 'includes/dashboard-layout.php';

requirePermission('docs.view');

// Get categories
$categories = $pdo->query('SELECT * FROM doc_categories ORDER BY sort_order, name')->fetchAll();

$csrfToken = generateCsrfToken();

// Fetch ALL docs for tree building
$allDocs = $pdo->query('
    SELECT id, title, category_id, parent_id, status
    FROM docs
    ORDER BY sort_order, title
')->fetchAll();

// Build tree structure: group by category, then build parent->child hierarchy
function buildDocTree($docs, $parentId = null) {
    $tree = [];
    foreach ($docs as $doc) {
        if ($doc['parent_id'] == $parentId) {
            $children = buildDocTree($docs, $doc['id']);
            $doc['children'] = $children;
            $tree[] = $doc;
        }
    }
    return $tree;
}

// Group docs by category
$docsByCategory = [];
foreach ($allDocs as $doc) {
    $catId = $doc['category_id'];
    if (!isset($docsByCategory[$catId])) {
        $docsByCategory[$catId] = [];
    }
    $docsByCategory[$catId][] = $doc;
}

// Build tree for each category
$categoryTrees = [];
foreach ($categories as $cat) {
    $catDocs = $docsByCategory[$cat['id']] ?? [];
    $categoryTrees[$cat['id']] = [
        'category' => $cat,
        'docs' => buildDocTree($catDocs, null)
    ];
}

// Recursive function to render doc tree items
function renderDocTree($docs, $depth = 0) {
    if (empty($docs)) return;
    // Start at pl-8 to indent under folder icon, add 4 for each level
    $paddingClass = 'pl-' . (8 + ($depth * 4));
    ?>
    <ul class="<?= $paddingClass ?>">
        <?php foreach ($docs as $doc):
            $hasChildren = !empty($doc['children']);
            $isDraft = $doc['status'] === 'draft';
        ?>
        <li>
            <div class="flex items-center group">
                <?php if ($hasChildren): ?>
                <button type="button" onclick="toggleDocTree(this)" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
                    <svg class="w-3 h-3 text-gray-400 transition-transform tree-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <?php else: ?>
                <span class="w-5"></span>
                <?php endif; ?>
                <a href="/doc?id=<?= $doc['id'] ?>"
                   class="flex-1 py-1 px-1 text-sm rounded hover:bg-gray-100 dark:hover:bg-gray-700 truncate <?= $isDraft ? 'text-gray-500 dark:text-gray-400' : 'text-gray-700 dark:text-gray-200' ?>">
                    <?= htmlspecialchars($doc['title']) ?>
                    <?php if ($isDraft): ?><span class="text-xs">(draft)</span><?php endif; ?>
                </a>
            </div>
            <?php if ($hasChildren): ?>
            <div class="doc-children hidden">
                <?php renderDocTree($doc['children'], $depth + 1); ?>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
?>

<?php if ($message): ?>
<div class="mb-6 rounded-md p-4 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<div class="flex gap-6">
    <!-- Tree Sidebar -->
    <div class="w-64 flex-shrink-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <!-- Header with New Doc button -->
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <?php if (hasPermission('docs.create')): ?>
                <button onclick="document.getElementById('newDocModal').classList.remove('hidden')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Document
                </button>
                <?php endif; ?>
            </div>

            <!-- Tree Navigation -->
            <div class="p-2 max-h-[calc(100vh-280px)] overflow-y-auto">
                <?php foreach ($categoryTrees as $catId => $catData):
                    $cat = $catData['category'];
                    $docs = $catData['docs'];
                    $hasDocsInCategory = !empty($docs);
                ?>
                <div class="mb-1">
                    <!-- Category Header -->
                    <button type="button" onclick="toggleCategory(this)"
                            class="w-full flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                        <svg class="w-4 h-4 text-gray-400 transition-transform category-chevron <?= $hasDocsInCategory ? '' : 'opacity-0' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <?= htmlspecialchars($cat['name']) ?>
                    </button>

                    <!-- Category Docs -->
                    <?php if ($hasDocsInCategory): ?>
                    <div class="category-docs hidden">
                        <?php renderDocTree($docs, 0); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if (empty($categories)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 px-2 py-4 text-center">No categories yet</p>
                <?php endif; ?>
            </div>

            <!-- Footer with Add Category -->
            <?php if (hasPermission('docs.create')): ?>
            <div class="p-2 border-t border-gray-200 dark:border-gray-700">
                <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')"
                        class="w-full text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 py-1">
                    + Add Category
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Knowledge Base</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Select a document from the tree to view it, or create a new one to get started.</p>
            <?php if (hasPermission('docs.create')): ?>
            <button onclick="document.getElementById('newDocModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Document
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Document Modal -->
<div id="newDocModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">New Document</h2>
            <button onclick="document.getElementById('newDocModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="create_doc">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2" placeholder="Document title">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select name="category_id" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="doc_type" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                        <option value="reference">Reference</option>
                        <option value="process">Process</option>
                        <option value="workflow">Workflow</option>
                        <option value="guide">Guide</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('newDocModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Add Category</h2>
            <button onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="add_category">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
                    <input type="text" name="category_name" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                    <select name="category_color" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-3 py-2">
                        <option value="gray">Gray</option>
                        <option value="blue">Blue</option>
                        <option value="green">Green</option>
                        <option value="purple">Purple</option>
                        <option value="yellow">Yellow</option>
                        <option value="red">Red</option>
                        <option value="orange">Orange</option>
                        <option value="pink">Pink</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Add</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle category folder
function toggleCategory(button) {
    const chevron = button.querySelector('.category-chevron');
    const docs = button.nextElementSibling;

    if (docs && docs.classList.contains('category-docs')) {
        docs.classList.toggle('hidden');
        chevron.classList.toggle('rotate-90');
    }
}

// Toggle doc with children
function toggleDocTree(button) {
    const chevron = button.querySelector('.tree-chevron');
    const parent = button.closest('li');
    const children = parent.querySelector('.doc-children');

    if (children) {
        children.classList.toggle('hidden');
        chevron.classList.toggle('rotate-90');
    }
}
</script>

<?php include 'includes/dashboard-footer.php'; ?>
