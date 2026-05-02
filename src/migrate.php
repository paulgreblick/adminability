<?php
/**
 * Migration Runner — visit /migrate to apply any pending schema migrations.
 * Tracks applied versions in schema_migrations table.
 * Auto-detects already-applied migrations on first run (based on tables/columns present).
 */

$page_title = 'Migrations';
$current_page = '';  // Not in sidebar nav
require_once __DIR__ . '/includes/layout.php';

$migrationDir = __DIR__ . '/data';

function versionFromFile(string $path): string {
    if (preg_match('/migrate-v([\d.]+)\.sql$/i', basename($path), $m)) return $m[1];
    return basename($path);
}

// Ensure tracking table
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version TEXT PRIMARY KEY,
    applied_at TEXT DEFAULT (datetime('now'))
)");

// Discover migration files, sorted by version
$files = glob($migrationDir . '/migrate-*.sql') ?: [];
usort($files, fn($a, $b) => version_compare(versionFromFile($a), versionFromFile($b)));

// First-run bootstrap: auto-detect already-applied migrations so we don't re-run
// ALTER TABLE statements that would fail on existing columns.
if ((int)$pdo->query("SELECT COUNT(*) FROM schema_migrations")->fetchColumn() === 0) {
    $tables = array_flip($pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN));
    $colExists = function (string $table, string $col) use ($pdo): bool {
        foreach ($pdo->query("PRAGMA table_info(" . $table . ")")->fetchAll() as $c) {
            if (strcasecmp($c['name'], $col) === 0) return true;
        }
        return false;
    };

    $detected = [];
    if (isset($tables['projects']) && isset($tables['tasks']))         $detected[] = '3';
    if (isset($tables['tab_sets']) && isset($tables['notes']) && $colExists('notes', 'project_id')) $detected[] = '3.2';
    if (isset($tables['monitors']))                                    $detected[] = '3.3';
    if (isset($tables['checklist_items']) && isset($tables['task_dependencies'])) $detected[] = '3.4';
    if (isset($tables['projects']) && $colExists('projects', 'parent_id')) $detected[] = '3.5';
    if (isset($tables['monitor_urls']))                                $detected[] = '3.6';
    if (isset($tables['brainstorm_items']) && isset($tables['procedures'])) $detected[] = '3.7';
    if (isset($tables['brainstorm_items']) && $colExists('brainstorm_items', 'notes')) $detected[] = '3.8';
    if (isset($tables['brainstorm_items']) && $colExists('brainstorm_items', 'assigned_to')) $detected[] = '3.9';
    if (isset($tables['brainstorm_steps']))                            $detected[] = '3.10';

    $ins = $pdo->prepare("INSERT OR IGNORE INTO schema_migrations (version) VALUES (?)");
    foreach ($detected as $v) $ins->execute([$v]);
}

$applied = $pdo->query("SELECT version, applied_at FROM schema_migrations")->fetchAll(PDO::FETCH_KEY_PAIR);

// Apply pending on POST
$results = [];
$didRun = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $results[] = ['version' => '-', 'status' => 'error', 'message' => 'Invalid CSRF token — reload the page and try again.'];
    } else {
        $didRun = true;
        foreach ($files as $f) {
            $v = versionFromFile($f);
            if (isset($applied[$v])) continue;

            $sql = file_get_contents($f);
            if ($sql === false) {
                $results[] = ['version' => $v, 'status' => 'error', 'message' => 'Could not read file'];
                continue;
            }

            try {
                $pdo->beginTransaction();
                $pdo->exec($sql);
                $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)")->execute([$v]);
                $pdo->commit();
                $applied[$v] = date('Y-m-d H:i:s');
                $results[] = ['version' => $v, 'status' => 'ok', 'message' => 'Applied successfully'];
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $results[] = ['version' => $v, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }
        if (!$results) {
            $results[] = ['version' => '-', 'status' => 'ok', 'message' => 'No pending migrations — database is already up to date.'];
        }
    }
}

// Current (possibly updated) state
$pending = array_filter($files, fn($f) => !isset($applied[versionFromFile($f)]));

layout_start();
?>

<div class="max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Database Migrations</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Apply any pending schema changes to this environment's database.</p>
    </div>

    <?php if ($results): ?>
        <div class="card p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Results</h2>
            <ul class="space-y-2">
                <?php foreach ($results as $r): ?>
                    <li class="flex items-start gap-3 text-sm">
                        <?php if ($r['status'] === 'ok'): ?>
                            <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        <?php else: ?>
                            <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-slate-900 dark:text-white">v<?= htmlspecialchars($r['version']) ?></div>
                            <div class="text-slate-600 dark:text-slate-400"><?= htmlspecialchars($r['message']) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">All migrations</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= count($applied) ?> applied · <?= count($pending) ?> pending
            </span>
        </div>
        <ul>
            <?php foreach ($files as $f):
                $v = versionFromFile($f);
                $isApplied = isset($applied[$v]);
                $appliedAt = $applied[$v] ?? null;
            ?>
                <li class="flex items-center gap-3 px-5 py-3 border-b border-slate-200 dark:border-slate-800 last:border-b-0">
                    <?php if ($isApplied): ?>
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    <?php else: ?>
                        <span class="flex-shrink-0 w-5 h-5 rounded-full border-2 border-amber-400"></span>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 dark:text-white">v<?= htmlspecialchars($v) ?>
                            <span class="text-xs font-normal text-slate-500 dark:text-slate-400 ml-2"><?= htmlspecialchars(basename($f)) ?></span>
                        </div>
                        <?php if ($appliedAt): ?>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Applied <?= htmlspecialchars($appliedAt) ?></div>
                        <?php else: ?>
                            <div class="text-xs text-amber-600 dark:text-amber-400">Pending</div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($pending): ?>
        <form method="POST" class="flex items-center gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <button type="submit" class="btn-primary" onclick="return confirm('Apply <?= count($pending) ?> pending migration(s) to this database?')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                Apply <?= count($pending) ?> pending migration<?= count($pending) === 1 ? '' : 's' ?>
            </button>
            <a href="/dashboard" class="btn-ghost">Back to dashboard</a>
        </form>
    <?php else: ?>
        <div class="card p-5 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <div class="text-sm text-emerald-800 dark:text-emerald-200">
                Database is up to date — all <?= count($files) ?> migration<?= count($files) === 1 ? '' : 's' ?> have been applied.
            </div>
            <a href="/dashboard" class="btn-ghost ml-auto">Back to dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php layout_end(); ?>
