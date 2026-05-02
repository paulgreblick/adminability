<?php
/**
 * Single Document — full page view with Quill editor
 */

$docId = (int)($_GET['id'] ?? 0);
if (!$docId) {
    header('Location: /docs');
    exit;
}

$page_title = 'Document';
$current_page = 'docs';
require_once __DIR__ . '/includes/layout.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'save') {
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $tagIds = $_POST['tags'] ?? [];
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        if ($title) {
            $pdo->prepare("UPDATE docs SET title = ?, content = ?, project_id = ?, updated_by = ?, updated_at = datetime('now') WHERE id = ?")
                ->execute([$title, $content, $projectId, $_SESSION['user_id'], $docId]);
            $pdo->prepare('DELETE FROM doc_tag_map WHERE doc_id = ?')->execute([$docId]);
            if ($tagIds) {
                $ins = $pdo->prepare('INSERT INTO doc_tag_map (doc_id, tag_id) VALUES (?, ?)');
                foreach ($tagIds as $tid) $ins->execute([$docId, (int)$tid]);
            }
            if (isset($_POST['save_and_close'])) {
                header('Location: /docs?id=' . $docId . '&saved=1');
                exit;
            }
            $message = 'Document saved.';
            $messageType = 'success';
        }
    }
}

$editMode = isset($_GET['edit']);

$stmt = $pdo->prepare('SELECT d.*, u.first_name as author_first, u.name as author_name, p.name as project_name, p.color as project_color FROM docs d LEFT JOIN users u ON d.created_by = u.id LEFT JOIN projects p ON d.project_id = p.id WHERE d.id = ?');
$stmt->execute([$docId]);
$doc = $stmt->fetch();
if (!$doc) {
    header('Location: /docs');
    exit;
}

$stmt = $pdo->prepare('SELECT t.* FROM doc_tags t JOIN doc_tag_map m ON t.id = m.tag_id WHERE m.doc_id = ? ORDER BY t.name');
$stmt->execute([$docId]);
$docTags = $stmt->fetchAll();

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

$page_title = $doc['title'];
layout_start();
?>

<!-- Breadcrumb -->
<div class="mb-4">
    <a href="/docs?id=<?= $docId ?>" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Knowledge Base
    </a>
</div>

<?php if ($message): ?>
<div class="mb-4 rounded-lg px-4 py-2.5 text-sm bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($editMode): ?>
<!-- Edit Mode -->
<form method="POST" id="doc-form" class="card overflow-hidden">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="content" id="doc-content-hidden">

    <div class="px-5 md:px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 mb-3">
            <input type="text" name="title" value="<?= htmlspecialchars($doc['title']) ?>" required
                   class="!text-xl !font-semibold !border-transparent !bg-transparent !px-0 focus:!bg-white dark:focus:!bg-slate-800 focus:!border-slate-200 dark:focus:!border-slate-700 focus:!px-3"
                   placeholder="Document title">
            <a href="/doc?id=<?= $docId ?>" class="btn-secondary flex-shrink-0">Cancel</a>
            <button type="submit" class="btn-secondary flex-shrink-0">Save</button>
            <button type="submit" name="save_and_close" value="1" class="btn-primary flex-shrink-0">Save &amp; Close</button>
        </div>
        <div class="flex items-center gap-3 flex-wrap mb-2">
            <span class="text-xs text-slate-500 dark:text-slate-400">Project:</span>
            <select name="project_id" class="!w-auto !py-1 !text-xs">
                <option value="">No Project</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $doc['project_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['display_name'] ?? $p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!empty($allTags)): ?>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-slate-500 dark:text-slate-400">Tags:</span>
            <?php foreach ($allTags as $tag):
                $isTagged = false;
                foreach ($docTags as $t) if ($t['id'] == $tag['id']) $isTagged = true;
            ?>
            <label class="cursor-pointer">
                <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" <?= $isTagged ? 'checked' : '' ?> class="sr-only peer">
                <span class="pill-<?= htmlspecialchars($tag['color']) ?> peer-checked:ring-2 peer-checked:ring-indigo-500 peer-checked:ring-offset-1 dark:peer-checked:ring-offset-slate-900">
                    <?= htmlspecialchars($tag['name']) ?>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div id="editor" class="min-h-[500px]"><?= $doc['content'] ?? '' ?></div>
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
<!-- View Mode -->
<article class="card overflow-hidden">
    <header class="px-5 md:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($doc['title']) ?></h1>
                <div class="mt-2 flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                    <span><?= htmlspecialchars($doc['author_first'] ?: $doc['author_name'] ?? 'Unknown') ?></span>
                    <span>·</span>
                    <span><?= date('M j, Y', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?></span>
                    <?php if ($doc['project_name']): ?>
                        <span class="pill-<?= htmlspecialchars($doc['project_color'] ?? 'slate') ?>"><?= htmlspecialchars($doc['project_name']) ?></span>
                    <?php endif; ?>
                    <?php foreach ($docTags as $tag): ?>
                        <span class="pill-<?= htmlspecialchars($tag['color']) ?>"><?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="?id=<?= $docId ?>&edit=1" class="btn-primary flex-shrink-0">Edit</a>
        </div>
    </header>

    <div class="px-5 md:px-8 py-6">
        <?php if ($doc['content']): ?>
            <div class="prose-content"><?= $doc['content'] ?></div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-slate-500 dark:text-slate-400">No content yet.</p>
                <a href="?id=<?= $docId ?>&edit=1" class="btn-primary mt-3 inline-flex">Add content</a>
            </div>
        <?php endif; ?>
    </div>
</article>
<?php endif; ?>

<?php layout_end(); ?>
