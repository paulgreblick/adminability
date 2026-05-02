<?php
/**
 * Partial: single procedure row (used on procedures.php list)
 * Expects: $proc
 */
?>
<li class="group flex items-center gap-2 px-4 py-3 border-b border-slate-200 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" data-procedure-id="<?= $proc['id'] ?>">
    <span class="drag-handle cursor-move flex-shrink-0 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity" title="Drag to reorder">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
    </span>
    <a href="/procedure?id=<?= $proc['id'] ?>" class="flex-1 min-w-0">
        <div class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($proc['title']) ?></div>
        <?php if ($proc['description']): ?>
            <div class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($proc['description']) ?></div>
        <?php endif; ?>
    </a>
    <span class="text-xs text-slate-400 dark:text-slate-500 flex-shrink-0"><?= (int)$proc['step_count'] ?> step<?= (int)$proc['step_count'] === 1 ? '' : 's' ?></span>
    <?php if (!empty($proc['project_name'])): ?>
        <span class="pill-<?= htmlspecialchars($proc['project_color'] ?? 'slate') ?> !text-[10px]"><?= htmlspecialchars($proc['project_name']) ?></span>
    <?php endif; ?>
    <button onclick='editProcedure(<?= json_encode($proc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" title="Edit">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </button>
</li>
