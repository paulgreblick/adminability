<?php
/**
 * Notes — flat list with type filters and AJAX actions
 */

$page_title = 'Notes';
$current_page = 'notes';
require_once __DIR__ . '/includes/layout.php';

$filter = $_GET['filter'] ?? 'all';
$projectFilter = isset($_GET['project']) ? (int)$_GET['project'] : 0;

$allProjectsRaw = $pdo->query("SELECT id, name, color, parent_id FROM projects WHERE status = 'active' ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, name")->fetchAll();
$projects = [];
foreach ($allProjectsRaw as $ap) {
    if ($ap['parent_id']) {
        foreach ($allProjectsRaw as $pp) { if ($pp['id'] == $ap['parent_id']) { $ap['display_name'] = $pp['name'] . ' › ' . $ap['name']; break; } }
        if (!isset($ap['display_name'])) $ap['display_name'] = $ap['name'];
    } else { $ap['display_name'] = $ap['name']; }
    $projects[] = $ap;
}

$sql = 'SELECT n.*, u.first_name as author_first, p.name as project_name, p.color as project_color
        FROM notes n
        LEFT JOIN users u ON n.created_by = u.id
        LEFT JOIN projects p ON n.project_id = p.id';
$conditions = [];
switch ($filter) {
    case 'pinned':   $conditions[] = 'n.is_pinned = 1'; $conditions[] = "n.status != 'archived'"; break;
    case 'ideas':    $conditions[] = "n.type = 'idea'"; $conditions[] = "n.status != 'archived'"; break;
    case 'tasks':    $conditions[] = "n.type = 'task'"; $conditions[] = "n.status != 'archived'"; break;
    case 'questions':$conditions[] = "n.type = 'question'"; $conditions[] = "n.status != 'archived'"; break;
    case 'done':     $conditions[] = "n.status = 'done'"; break;
    case 'archived': $conditions[] = "n.status = 'archived'"; break;
    default:         $conditions[] = "n.status != 'archived'"; break;
}
if ($projectFilter) $conditions[] = 'n.project_id = ' . $projectFilter;
$sql .= ' WHERE ' . implode(' AND ', $conditions);
$sql .= ' ORDER BY n.is_pinned DESC, n.updated_at DESC';
$notes = $pdo->query($sql)->fetchAll();

$typePills = [
    'note'     => ['label' => 'Note', 'class' => 'pill-slate'],
    'idea'     => ['label' => 'Idea', 'class' => 'pill-amber'],
    'task'     => ['label' => 'Task', 'class' => 'pill-blue'],
    'question' => ['label' => 'Question', 'class' => 'pill-purple'],
];

$filterOptions = [
    'all'      => 'All',
    'pinned'   => 'Pinned',
    'ideas'    => 'Ideas',
    'tasks'    => 'Tasks',
    'questions'=> 'Questions',
    'done'     => 'Done',
    'archived' => 'Archived',
];

layout_start();
?>

<!-- Header -->
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Notes</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Quick notes, ideas, and reminders</p>
    </div>
    <button data-shortcut="new" onclick="openNewNote()" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New Note
    </button>
</div>

<!-- Filters -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <?php foreach ($filterOptions as $key => $label): ?>
        <a href="?filter=<?= $key ?>" class="filter-pill <?= $filter === $key ? 'filter-pill-active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Notes list -->
<?php if (empty($notes)): ?>
    <div class="card p-12 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No notes here</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Capture your first note or idea.</p>
        <button onclick="openNewNote()" class="btn-primary mt-4">New Note</button>
    </div>
<?php else: ?>
<div class="space-y-2">
    <?php foreach ($notes as $note):
        $isDone = $note['status'] === 'done';
        $isArchived = $note['status'] === 'archived';
        $isPinned = (int)$note['is_pinned'] === 1;
        $type = $typePills[$note['type']] ?? $typePills['note'];
        $authorInitial = strtoupper(substr($note['author_first'] ?? '?', 0, 1));
    ?>
    <div class="group card hover:border-slate-300 dark:hover:border-slate-700 transition-colors <?= $isPinned ? 'ring-1 ring-amber-400/50' : '' ?> <?= $isArchived || $isDone ? 'opacity-60' : '' ?>" data-note-id="<?= $note['id'] ?>">
        <div class="flex items-start gap-3 p-4">
            <!-- Done toggle (tasks) or type icon -->
            <?php if ($note['type'] === 'task'): ?>
                <button onclick="toggleDone(<?= $note['id'] ?>, this)" class="flex-shrink-0 w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center mt-0.5
                         <?= $isDone ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300 dark:border-slate-600 hover:border-emerald-500' ?>" title="Toggle done">
                    <?php if ($isDone): ?>
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?php endif; ?>
                </button>
            <?php else: ?>
                <div class="w-5 h-5 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <?php if ($note['type'] === 'idea'): ?>
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <?php elseif ($note['type'] === 'question'): ?>
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php else: ?>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="flex-1 min-w-0 cursor-pointer" onclick='openEditNote(<?= json_encode($note, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                    <?php if ($note['title']): ?>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white <?= $isDone ? 'line-through' : '' ?>"><?= htmlspecialchars($note['title']) ?></span>
                    <?php endif; ?>
                    <span class="<?= $type['class'] ?>"><?= $type['label'] ?></span>
                    <?php if ($note['project_name']): ?>
                        <span class="pill-<?= htmlspecialchars($note['project_color'] ?? 'slate') ?>"><?= htmlspecialchars($note['project_name']) ?></span>
                    <?php endif; ?>
                    <?php if ($note['priority'] === 'high'): ?>
                        <span class="pill-rose">High</span>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-400 <?= $isDone ? 'line-through' : '' ?> whitespace-pre-line line-clamp-3"><?= htmlspecialchars($note['content']) ?></div>
                <div class="flex items-center gap-2 mt-2 text-xs text-slate-500 dark:text-slate-500">
                    <span><?= htmlspecialchars($note['author_first'] ?? '') ?></span>
                    <span>·</span>
                    <span><?= date('M j', strtotime($note['updated_at'] ?? $note['created_at'])) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-0.5 flex-shrink-0 opacity-60 group-hover:opacity-100 transition-opacity">
                <button onclick="togglePin(<?= $note['id'] ?>, this)" class="p-1.5 rounded text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 <?= $isPinned ? 'text-amber-500' : 'hover:text-amber-500' ?>" title="<?= $isPinned ? 'Unpin' : 'Pin' ?>">
                    <svg class="w-4 h-4" fill="<?= $isPinned ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                </button>
                <button onclick='openEditNote(<?= json_encode($note, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-1.5 rounded text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button onclick="deleteNote(<?= $note['id'] ?>)" class="p-1.5 rounded text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-500" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Note Modal (shared for create/edit) -->
<div id="note-modal" data-modal class="modal-backdrop hidden">
    <div class="modal-panel" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="note-modal-title">New Note</h3>
            <button onclick="closeModal('note-modal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="note-form" onsubmit="submitNote(event)" class="p-5 space-y-4">
            <input type="hidden" name="note_id" id="note-id" value="">

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="form-label">Type</label>
                    <select name="type" id="note-type">
                        <option value="note">Note</option>
                        <option value="idea">Idea</option>
                        <option value="task">Task</option>
                        <option value="question">Question</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" id="note-priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div id="note-status-wrap" class="hidden">
                    <label class="form-label">Status</label>
                    <select name="status" id="note-status">
                        <option value="active">Active</option>
                        <option value="done">Done</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label">Title <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" name="title" id="note-title-input" placeholder="A short title">
            </div>

            <div>
                <label class="form-label">Project <span class="text-slate-400 font-normal">(optional)</span></label>
                <select name="project_id" id="note-project">
                    <option value="">No Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Content</label>
                <textarea name="content" id="note-content" rows="5" required placeholder="Your note..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeModal('note-modal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" id="note-submit-btn">Create Note</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewNote() {
    document.getElementById('note-modal-title').textContent = 'New Note';
    document.getElementById('note-form').reset();
    document.getElementById('note-id').value = '';
    document.getElementById('note-status-wrap').classList.add('hidden');
    document.getElementById('note-submit-btn').textContent = 'Create Note';
    openModal('note-modal');
}

function openEditNote(note) {
    document.getElementById('note-modal-title').textContent = 'Edit Note';
    document.getElementById('note-id').value = note.id;
    document.getElementById('note-title-input').value = note.title || '';
    document.getElementById('note-content').value = note.content;
    document.getElementById('note-type').value = note.type;
    document.getElementById('note-priority').value = note.priority;
    document.getElementById('note-status').value = note.status;
    document.getElementById('note-project').value = note.project_id || '';
    document.getElementById('note-status-wrap').classList.remove('hidden');
    document.getElementById('note-submit-btn').textContent = 'Save Changes';
    openModal('note-modal');
}

async function submitNote(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const isEdit = !!data.note_id;
    data.action = isEdit ? 'update' : 'create';
    try {
        await api('/api/notes', data);
        toast(isEdit ? 'Note updated' : 'Note created', 'success');
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function togglePin(id, btn) {
    try {
        const res = await api('/api/notes', { action: 'toggle_pin', note_id: id });
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function toggleDone(id, btn) {
    try {
        await api('/api/notes', { action: 'toggle_done', note_id: id });
        setTimeout(() => location.reload(), 200);
    } catch (e) {}
}

async function deleteNote(id) {
    if (!confirm('Delete this note?')) return;
    try {
        await api('/api/notes', { action: 'delete', note_id: id });
        toast('Note deleted', 'success');
        document.querySelector(`[data-note-id="${id}"]`)?.remove();
    } catch (e) {}
}
</script>

<?php layout_end(); ?>
