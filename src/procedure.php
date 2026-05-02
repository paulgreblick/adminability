<?php
/**
 * Procedure detail — view, edit, and reorder steps
 */

$procId = (int)($_GET['id'] ?? 0);
if (!$procId) { header('Location: /procedures'); exit; }

$page_title = 'Procedure';
$current_page = 'procedures';
require_once __DIR__ . '/includes/layout.php';

$stmt = $pdo->prepare("
    SELECT p.*,
           s.name as subject_name, s.color as subject_color,
           pr.name as project_name, pr.color as project_color
    FROM procedures p
    LEFT JOIN procedure_subjects s ON p.subject_id = s.id
    LEFT JOIN projects pr ON p.project_id = pr.id
    WHERE p.id = ?
");
$stmt->execute([$procId]);
$proc = $stmt->fetch();
if (!$proc) { header('Location: /procedures'); exit; }

$page_title = $proc['title'];

$stepsStmt = $pdo->prepare("SELECT * FROM procedure_steps WHERE procedure_id = ? ORDER BY sort_order, id");
$stepsStmt->execute([$procId]);
$steps = $stepsStmt->fetchAll();

layout_start();
?>

<!-- Breadcrumb -->
<div class="mb-4 text-sm">
    <a href="/procedures" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400">Procedures</a>
    <span class="mx-2 text-slate-400">›</span>
    <span class="text-slate-900 dark:text-white"><?= htmlspecialchars($proc['title']) ?></span>
</div>

<!-- Header -->
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
            <?php if ($proc['subject_name']): ?>
                <span class="pill-<?= htmlspecialchars($proc['subject_color']) ?>"><?= htmlspecialchars($proc['subject_name']) ?></span>
            <?php endif; ?>
            <?php if ($proc['project_name']): ?>
                <span class="pill-<?= htmlspecialchars($proc['project_color'] ?? 'slate') ?>"><?= htmlspecialchars($proc['project_name']) ?></span>
            <?php endif; ?>
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($proc['title']) ?></h1>
        <?php if ($proc['description']): ?>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300"><?= nl2br(htmlspecialchars($proc['description'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Steps -->
<div class="card overflow-hidden mb-4">
    <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Steps</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($steps) ?> step<?= count($steps) === 1 ? '' : 's' ?></span>
    </div>
    <?php if (empty($steps)): ?>
        <div class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
            No steps yet. Add your first below.
        </div>
    <?php else: ?>
        <ol id="steps-list">
            <?php foreach ($steps as $i => $step): ?>
                <li class="step-row group flex items-start gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-800 last:border-b-0" data-step-id="<?= $step['id'] ?>">
                    <span class="drag-handle cursor-move flex-shrink-0 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity" title="Drag to reorder">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                    </span>
                    <span class="step-number flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-center"><?= $i + 1 ?></span>
                    <span class="step-text flex-1 text-sm text-slate-700 dark:text-slate-200 whitespace-pre-wrap" ondblclick="startEditStep(this, <?= $step['id'] ?>)" title="Double-click to edit"><?= htmlspecialchars($step['text']) ?></span>
                    <button onclick="deleteStep(<?= $step['id'] ?>, this)" class="p-1 text-slate-400 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<!-- Add step -->
<form onsubmit="addStep(event)" class="card p-3 flex items-start gap-2">
    <textarea id="new-step-text" rows="1" placeholder="Add a step…" required class="flex-1 !py-2 resize-none" onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); this.closest('form').requestSubmit();}"></textarea>
    <button type="submit" class="btn-primary">Add</button>
</form>

<script>
const PROC_ID = <?= $procId ?>;

async function addStep(e) {
    e.preventDefault();
    const input = document.getElementById('new-step-text');
    const text = input.value.trim();
    if (!text) return;
    try {
        await api('/api/procedures', { action: 'add_step', procedure_id: PROC_ID, text });
        location.reload();
    } catch (err) {}
}

async function deleteStep(id, btn) {
    if (!confirm('Delete this step?')) return;
    try {
        await api('/api/procedures', { action: 'delete_step', id });
        const row = btn.closest('li');
        row.style.opacity = '0';
        setTimeout(() => { row.remove(); renumber(); }, 150);
    } catch (err) {}
}

function startEditStep(span, id) {
    const orig = span.textContent;
    const ta = document.createElement('textarea');
    ta.value = orig;
    ta.className = 'flex-1 !py-1 !text-sm resize-none';
    ta.rows = Math.max(1, orig.split('\n').length);
    span.replaceWith(ta);
    ta.focus();
    ta.setSelectionRange(orig.length, orig.length);

    const finish = async (save) => {
        const newText = ta.value.trim();
        if (save && newText && newText !== orig) {
            try { await api('/api/procedures', { action: 'update_step', id, text: newText }); } catch (err) { ta.replaceWith(span); return; }
            span.textContent = newText;
        }
        ta.replaceWith(span);
    };

    ta.addEventListener('blur', () => finish(true));
    ta.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); finish(true); }
        else if (e.key === 'Escape') finish(false);
    });
}

function renumber() {
    document.querySelectorAll('#steps-list .step-number').forEach((el, i) => el.textContent = i + 1);
}

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('steps-list');
    if (list && window.Sortable) {
        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                const ids = Array.from(list.querySelectorAll('[data-step-id]')).map(el => el.dataset.stepId);
                renumber();
                try {
                    await api('/api/procedures', { action: 'reorder_steps', procedure_id: PROC_ID, ids });
                } catch (err) {}
            }
        });
    }
});
</script>

<?php layout_end(); ?>
