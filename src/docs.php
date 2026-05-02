<?php
/**
 * Knowledge Base — responsive split panel
 */

$page_title = 'Docs';
$current_page = 'docs';
require_once __DIR__ . '/includes/layout.php';

$message = '';
$messageType = '';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
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
                    $base = $slug; $c = 1;
                    while (true) {
                        $chk = $pdo->prepare('SELECT id FROM docs WHERE slug = ?');
                        $chk->execute([$slug]);
                        if (!$chk->fetch()) break;
                        $slug = $base . '-' . $c++;
                    }
                    $stmt = $pdo->prepare("INSERT INTO docs (title, slug, status, created_by) VALUES (?, ?, 'published', ?)");
                    $stmt->execute([$title, $slug, $_SESSION['user_id']]);
                    header('Location: /docs?id=' . $pdo->lastInsertId() . '&edit=1');
                    exit;
                }
                break;

            case 'save_doc':
                $docId = (int)($_POST['doc_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = $_POST['content'] ?? '';
                $tagIds = $_POST['tags'] ?? [];
                $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
                if ($docId && $title) {
                    $stmt = $pdo->prepare("UPDATE docs SET title = ?, content = ?, project_id = ?, updated_by = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$title, $content, $projectId, $_SESSION['user_id'], $docId]);
                    $pdo->prepare('DELETE FROM doc_tag_map WHERE doc_id = ?')->execute([$docId]);
                    if ($tagIds) {
                        $ins = $pdo->prepare('INSERT INTO doc_tag_map (doc_id, tag_id) VALUES (?, ?)');
                        foreach ($tagIds as $tid) $ins->execute([$docId, (int)$tid]);
                    }
                    header('Location: /docs?id=' . $docId . '&saved=1');
                    exit;
                }
                break;

            case 'delete_doc':
                $docId = (int)($_POST['doc_id'] ?? 0);
                $pdo->prepare('DELETE FROM docs WHERE id = ?')->execute([$docId]);
                header('Location: /docs?deleted=1');
                exit;

            case 'add_tag':
                $name = trim($_POST['tag_name'] ?? '');
                $color = $_POST['tag_color'] ?? 'slate';
                if ($name) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                    $stmt = $pdo->prepare('INSERT OR IGNORE INTO doc_tags (name, slug, color) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $color]);
                }
                break;

            case 'delete_tag':
                $tagId = (int)($_POST['tag_id'] ?? 0);
                $pdo->prepare('DELETE FROM doc_tags WHERE id = ?')->execute([$tagId]);
                break;
        }
    }
}

if (isset($_GET['saved'])) { $message = 'Document saved.'; $messageType = 'success'; }
if (isset($_GET['deleted'])) { $message = 'Document deleted.'; $messageType = 'success'; }

$filterTag = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
$selectedDocId = (int)($_GET['id'] ?? 0);
$editMode = isset($_GET['edit']);
$selectedDoc = null;

if ($selectedDocId) {
    $stmt = $pdo->prepare('SELECT d.*, u.first_name as author_first, u.name as author_name, p.name as project_name, p.color as project_color FROM docs d LEFT JOIN users u ON d.created_by = u.id LEFT JOIN projects p ON d.project_id = p.id WHERE d.id = ?');
    $stmt->execute([$selectedDocId]);
    $selectedDoc = $stmt->fetch();
    if ($selectedDoc) {
        $stmt = $pdo->prepare('SELECT t.* FROM doc_tags t JOIN doc_tag_map m ON t.id = m.tag_id WHERE m.doc_id = ? ORDER BY t.name');
        $stmt->execute([$selectedDocId]);
        $selectedDoc['tags'] = $stmt->fetchAll();
    }
}

$allTags = $pdo->query('SELECT * FROM doc_tags ORDER BY name')->fetchAll();
$allProjectsRaw = $pdo->query("SELECT id, name, color, parent_id FROM projects WHERE status = 'active' ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, name")->fetchAll();
$projects = [];
foreach ($allProjectsRaw as $ap) {
    if ($ap['parent_id']) {
        foreach ($allProjectsRaw as $pp) { if ($pp['id'] == $ap['parent_id']) { $ap['display_name'] = $pp['name'] . ' › ' . $ap['name']; break; } }
        if (!isset($ap['display_name'])) $ap['display_name'] = $ap['name'];
    } else { $ap['display_name'] = $ap['name']; }
    $projects[] = $ap;
}

if ($filterTag) {
    $stmt = $pdo->prepare('SELECT DISTINCT d.id, d.title, d.updated_at, d.created_at FROM docs d JOIN doc_tag_map m ON d.id = m.doc_id WHERE m.tag_id = ? ORDER BY d.updated_at DESC');
    $stmt->execute([$filterTag]);
    $allDocs = $stmt->fetchAll();
} else {
    $allDocs = $pdo->query('SELECT id, title, updated_at, created_at FROM docs ORDER BY updated_at DESC')->fetchAll();
}

$docTags = [];
foreach ($pdo->query('SELECT m.doc_id, t.id, t.name, t.color FROM doc_tag_map m JOIN doc_tags t ON t.id = m.tag_id') as $row) {
    $docTags[$row['doc_id']][] = $row;
}

layout_start();
?>

<?php if ($message): ?>
<div class="mb-4 rounded-lg px-4 py-2.5 text-sm <?= $messageType === 'error'
    ? 'bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300'
    : 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300' ?>">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Knowledge Base</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Reference material, processes, and docs</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="openModal('manage-tags-modal')" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Tags
        </button>
        <button data-shortcut="new" onclick="openModal('new-doc-modal')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Doc
        </button>
    </div>
</div>

<!-- Tag filters -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="/docs" class="filter-pill <?= !$filterTag ? 'filter-pill-active' : '' ?>">All</a>
    <?php foreach ($allTags as $tag): ?>
        <a href="?tag=<?= $tag['id'] ?>" class="filter-pill <?= $filterTag == $tag['id'] ? 'filter-pill-active' : '' ?>">
            <span class="w-1.5 h-1.5 rounded-full bg-<?= htmlspecialchars($tag['color']) ?>-500 mr-1.5"></span>
            <?= htmlspecialchars($tag['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Split panel -->
<div class="card overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-[320px_1fr] min-h-[calc(100vh-320px)]">

        <!-- Doc list -->
        <aside class="border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-800 flex flex-col">
            <?php if (empty($allDocs)): ?>
                <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    No documents yet
                </div>
            <?php else: ?>
            <ul class="divide-y divide-slate-200 dark:divide-slate-800 overflow-y-auto max-h-[500px] md:max-h-[calc(100vh-320px)]">
                <?php foreach ($allDocs as $doc):
                    $isSelected = $doc['id'] == $selectedDocId;
                    $tags = $docTags[$doc['id']] ?? [];
                ?>
                <li>
                    <a href="?id=<?= $doc['id'] ?><?= $filterTag ? '&tag='.$filterTag : '' ?>"
                       class="block px-4 py-3 transition-colors <?= $isSelected ? 'bg-indigo-50 dark:bg-indigo-950/30 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50' ?>">
                        <div class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($doc['title']) ?></div>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="text-xs text-slate-500 dark:text-slate-400"><?= date('M j', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?></span>
                            <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                                <span class="pill-<?= htmlspecialchars($tag['color']) ?> !text-[10px] !py-0.5 !px-1.5"><?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($tags) > 2): ?>
                                <span class="text-xs text-slate-400">+<?= count($tags) - 2 ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <div class="px-4 py-2 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 mt-auto">
                <?= count($allDocs) ?> document<?= count($allDocs) !== 1 ? 's' : '' ?>
            </div>
        </aside>

        <!-- Doc content -->
        <section class="min-w-0">
            <?php if (!$selectedDoc): ?>
                <div class="h-full flex items-center justify-center p-12 min-h-[400px]">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Select a document</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Or create a new one to get started.</p>
                        <button onclick="openModal('new-doc-modal')" class="btn-primary mt-4">New Doc</button>
                    </div>
                </div>
            <?php elseif ($editMode): ?>
                <!-- Edit mode -->
                <form method="POST" id="doc-form" class="flex flex-col min-h-[400px]">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="save_doc">
                    <input type="hidden" name="doc_id" value="<?= $selectedDoc['id'] ?>">
                    <input type="hidden" name="content" id="doc-content-hidden">

                    <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-3 mb-3">
                            <input type="text" name="title" value="<?= htmlspecialchars($selectedDoc['title']) ?>" required
                                   class="!text-lg !font-semibold !border-transparent !bg-transparent !px-0 focus:!bg-white dark:focus:!bg-slate-800 focus:!px-3 focus:!border-slate-200 dark:focus:!border-slate-700"
                                   placeholder="Document title">
                            <a href="/docs?id=<?= $selectedDoc['id'] ?>" class="btn-secondary flex-shrink-0">Cancel</a>
                            <button type="submit" class="btn-primary flex-shrink-0">Save</button>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Project:</span>
                            <select name="project_id" class="!w-auto !py-1 !text-xs">
                                <option value="">No Project</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selectedDoc['project_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Tags:</span>
                            <?php foreach ($allTags as $tag):
                                $isTagged = false;
                                foreach ($selectedDoc['tags'] as $t) if ($t['id'] == $tag['id']) $isTagged = true;
                            ?>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" <?= $isTagged ? 'checked' : '' ?> class="sr-only peer">
                                <span class="pill-<?= htmlspecialchars($tag['color']) ?> peer-checked:ring-2 peer-checked:ring-indigo-500 peer-checked:ring-offset-1 dark:peer-checked:ring-offset-slate-900">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="editor" class="flex-1 min-h-[400px]"><?= $selectedDoc['content'] ?? '' ?></div>
                </form>

                <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
                <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
                <script>
                    const quill = new Quill('#editor', {
                        theme: 'snow',
                        placeholder: 'Start writing...',
                        modules: { toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['blockquote', 'code-block'],
                            ['link'],
                            ['clean']
                        ]}
                    });
                    document.getElementById('doc-form').onsubmit = function() {
                        document.getElementById('doc-content-hidden').value = quill.root.innerHTML;
                    };
                </script>
            <?php else: ?>
                <!-- View mode -->
                <article class="flex flex-col min-h-[400px]">
                    <header class="px-5 md:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($selectedDoc['title']) ?></h1>
                                <div class="mt-2 flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                                    <span><?= htmlspecialchars($selectedDoc['author_first'] ?: $selectedDoc['author_name'] ?? 'Unknown') ?></span>
                                    <span>·</span>
                                    <span><?= date('M j, Y', strtotime($selectedDoc['updated_at'] ?? $selectedDoc['created_at'])) ?></span>
                                    <?php if ($selectedDoc['project_name']): ?>
                                        <span class="pill-<?= htmlspecialchars($selectedDoc['project_color'] ?? 'slate') ?>"><?= htmlspecialchars($selectedDoc['project_name']) ?></span>
                                    <?php endif; ?>
                                    <?php foreach ($selectedDoc['tags'] as $tag): ?>
                                        <span class="pill-<?= htmlspecialchars($tag['color']) ?>"><?= htmlspecialchars($tag['name']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="/doc?id=<?= $selectedDoc['id'] ?>" class="btn-ghost p-2" title="Open full page">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <a href="?id=<?= $selectedDoc['id'] ?>&edit=1" class="btn-primary">Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this document?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="delete_doc">
                                    <input type="hidden" name="doc_id" value="<?= $selectedDoc['id'] ?>">
                                    <button type="submit" class="btn-ghost text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <div class="px-5 md:px-8 py-6">
                        <?php if ($selectedDoc['content']): ?>
                            <div class="prose-content"><?= $selectedDoc['content'] ?></div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <p class="text-slate-500 dark:text-slate-400">No content yet.</p>
                                <a href="?id=<?= $selectedDoc['id'] ?>&edit=1" class="btn-primary mt-3 inline-flex">Add content</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- New Doc Modal -->
<div id="new-doc-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">New Document</h3>
            <button onclick="closeModal('new-doc-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="create_doc">
            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" required placeholder="Document title">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeModal('new-doc-modal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Tags Modal -->
<div id="manage-tags-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Tags</h3>
            <button onclick="closeModal('manage-tags-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5">
            <?php if (empty($allTags)): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-6">No tags yet. Create one below.</p>
            <?php else: ?>
                <ul class="space-y-1 mb-4">
                    <?php foreach ($allTags as $tag): ?>
                    <li class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <span class="pill-<?= htmlspecialchars($tag['color']) ?>"><?= htmlspecialchars($tag['name']) ?></span>
                        <form method="POST" onsubmit="return confirm('Delete this tag?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="action" value="delete_tag">
                            <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-500" title="Delete tag">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="add_tag">
                <h4 class="section-label mb-2">New Tag</h4>
                <div class="flex items-start gap-2">
                    <input type="text" name="tag_name" required placeholder="Tag name" class="flex-1">
                    <select name="tag_color" class="w-28 flex-shrink-0">
                        <option value="slate">Slate</option>
                        <option value="indigo">Indigo</option>
                        <option value="emerald">Emerald</option>
                        <option value="amber">Amber</option>
                        <option value="rose">Rose</option>
                        <option value="blue">Blue</option>
                        <option value="purple">Purple</option>
                    </select>
                    <button type="submit" class="btn-primary flex-shrink-0">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout_end(); ?>
