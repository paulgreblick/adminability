<?php
/**
 * Dashboard - Main page after login
 */

$dashboard_title = 'Dashboard';
$current_dashboard_page = 'dashboard';

include 'includes/dashboard-layout.php';

// Get video progress stats
$videoStats = null;
if (in_array('videos.view', $userPermissions)) {
    $totalVideos = $pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn();
    $totalSteps = $pdo->query('SELECT COUNT(*) FROM workflow_steps')->fetchColumn();
    $completedSteps = $pdo->query('SELECT COUNT(*) FROM video_progress WHERE status = "complete"')->fetchColumn();
    $totalPossible = $totalVideos * $totalSteps;
    $overallPercent = $totalPossible > 0 ? round(($completedSteps / $totalPossible) * 100) : 0;

    // Count fully completed videos
    $completedVideos = $pdo->query('
        SELECT COUNT(DISTINCT v.id)
        FROM videos v
        WHERE NOT EXISTS (
            SELECT 1 FROM video_progress vp
            WHERE vp.video_id = v.id AND vp.status != "complete"
        )
    ')->fetchColumn();

    $videoStats = [
        'total' => $totalVideos,
        'completed' => $completedVideos,
        'percent' => $overallPercent
    ];
}

// Get notes count
$notesCount = 0;
if (in_array('notes.view', $userPermissions)) {
    $notesCount = $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
}
?>

<!-- Progress Section -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Progress</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (in_array('videos.view', $userPermissions) && $videoStats): ?>
        <a href="/videos" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow border-l-4 border-purple-500">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Affirmations</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">YouTube Video Project</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/50 rounded-full p-2">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600 dark:text-gray-400"><?= $videoStats['completed'] ?> of <?= $videoStats['total'] ?> videos complete</span>
                    <span class="font-medium text-purple-600 dark:text-purple-400"><?= $videoStats['percent'] ?>%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: <?= $videoStats['percent'] ?>%"></div>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <!-- Add Project Card -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 flex items-center justify-center text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-500 dark:hover:text-gray-300 transition-colors cursor-pointer">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-sm">Add Project</span>
            </div>
        </div>
    </div>
</div>

<!-- Docs Section -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Docs</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (in_array('notes.view', $userPermissions)): ?>
        <a href="/notes" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Notes</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Shared ideas & notes</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/50 rounded-full p-2">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $notesCount ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">total notes</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Add Doc Card -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 flex items-center justify-center text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-500 dark:hover:text-gray-300 transition-colors cursor-pointer">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-sm">Add Document</span>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Section -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tracking</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Placeholder Card -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 flex items-center justify-center text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-500 dark:hover:text-gray-300 transition-colors cursor-pointer">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-sm">Add Tracker</span>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
