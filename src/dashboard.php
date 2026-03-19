<?php
/**
 * Dashboard v2 - Command Center
 * Shows what's in progress, what's next, recent completions, pinned notes, and stats.
 */

$dashboard_title = 'Dashboard';
$current_dashboard_page = 'dashboard';

include 'includes/dashboard-layout.php';

$firstName = htmlspecialchars($_SESSION['user_first_name'] ?? $_SESSION['user_name'] ?? 'there');

// Get all workflow steps
$allSteps = $pdo->query('SELECT * FROM workflow_steps ORDER BY sort_order')->fetchAll();
$totalSteps = count($allSteps);

// Get all videos with progress
$videos = $pdo->query('
    SELECT v.*, c.name as category_name
    FROM videos v
    JOIN video_categories c ON v.category_id = c.id
    ORDER BY c.sort_order, v.title
')->fetchAll();

$totalVideos = count($videos);

// Calculate per-video progress
$inProgress = [];
$upNext = [];
$recentlyDone = [];
$completedCount = 0;

foreach ($videos as $video) {
    $stmt = $pdo->prepare('
        SELECT vp.status, ws.name as step_name, ws.phase
        FROM video_progress vp
        JOIN workflow_steps ws ON vp.step_id = ws.id
        WHERE vp.video_id = ?
        ORDER BY ws.sort_order
    ');
    $stmt->execute([$video['id']]);
    $progress = $stmt->fetchAll();

    $complete = count(array_filter($progress, fn($p) => $p['status'] === 'complete'));
    $total = count($progress);
    $percent = $total > 0 ? round(($complete / $total) * 100) : 0;

    $video['progress_percent'] = $percent;
    $video['completed_steps'] = $complete;
    $video['total_steps'] = $total;

    if ($percent === 100) {
        $completedCount++;
        $recentlyDone[] = $video;
    } elseif ($percent > 0) {
        // Find current step (first non-complete)
        $currentStep = null;
        foreach ($progress as $p) {
            if ($p['status'] !== 'complete') {
                $currentStep = $p;
                break;
            }
        }
        $video['current_step'] = $currentStep;
        $inProgress[] = $video;
    } else {
        $upNext[] = $video;
    }
}

// Limit lists
$recentlyDone = array_slice($recentlyDone, 0, 5);
$upNext = array_slice($upNext, 0, 4);

// Overall stats
$overallPercent = $totalVideos > 0 ? round(($completedCount / $totalVideos) * 100) : 0;

// Get pinned notes
$pinnedNotes = $pdo->query('SELECT * FROM notes WHERE is_pinned = 1 ORDER BY updated_at DESC LIMIT 5')->fetchAll();
?>

<!-- Greeting -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Hey, <?= $firstName ?></h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left column: In Progress + Up Next -->
    <div class="lg:col-span-2 space-y-6">

        <!-- In Progress -->
        <?php if (!empty($inProgress)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">In Progress (<?= count($inProgress) ?>)</h3>
            <div class="space-y-3">
                <?php foreach ($inProgress as $video): ?>
                <a href="/videos" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($video['title']) ?></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <?= htmlspecialchars($video['category_name']) ?>
                                <?php if ($video['current_step']): ?>
                                    &middot; <span class="text-yellow-600 dark:text-yellow-400"><?= htmlspecialchars($video['current_step']['step_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ml-4 flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300"><?= $video['progress_percent'] ?>%</span>
                            <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= $video['progress_percent'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Up Next -->
        <?php if (!empty($upNext)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Up Next (<?= count($upNext) ?>)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($upNext as $video): ?>
                <a href="/videos" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow border-l-4 border-gray-300 dark:border-gray-600">
                    <div class="font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($video['title']) ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($video['category_name']) ?></div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Ready to start</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Empty state -->
        <?php if (empty($inProgress) && empty($upNext)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">No videos yet.</p>
            <a href="/videos" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">Add your first video</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column: Stats + Recently Done + Pinned Notes -->
    <div class="space-y-6">

        <!-- Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Progress</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white"><?= $completedCount ?> <span class="text-lg font-normal text-gray-500 dark:text-gray-400">/ <?= $totalVideos ?></span></div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">videos complete</div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= $overallPercent ?>%"></div>
            </div>
            <div class="text-right text-sm text-gray-500 dark:text-gray-400 mt-1"><?= $overallPercent ?>%</div>
        </div>

        <!-- Recently Done -->
        <?php if (!empty($recentlyDone)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Recently Done</h3>
            <ul class="space-y-2">
                <?php foreach ($recentlyDone as $video): ?>
                <li class="flex items-center gap-2 text-sm">
                    <span class="text-green-600">&#10003;</span>
                    <span class="text-gray-700 dark:text-gray-300 truncate"><?= htmlspecialchars($video['title']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Pinned Notes -->
        <?php if (!empty($pinnedNotes)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pinned Notes</h3>
                <a href="/notes" class="text-xs text-blue-600 hover:text-blue-700">View all</a>
            </div>
            <ul class="space-y-2">
                <?php foreach ($pinnedNotes as $note): ?>
                <li class="text-sm text-gray-700 dark:text-gray-300 truncate">
                    <?= htmlspecialchars($note['title'] ?: substr($note['content'], 0, 60)) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
