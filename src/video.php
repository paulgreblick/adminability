<?php
/**
 * Video Detail / Edit Page
 * Dynamic workflow steps
 */

$dashboard_title = 'Video Details';
$current_dashboard_page = 'videos';

include 'includes/dashboard-layout.php';

// Auth handled by dashboard-layout.php

$videoId = (int)($_GET['id'] ?? 0);

if (!$videoId) {
    header('Location: /videos');
    exit;
}

// Get workflow steps from database
$stepsQuery = $pdo->query('SELECT * FROM workflow_steps ORDER BY sort_order');
$allSteps = $stepsQuery->fetchAll();

// Group steps by phase (5 phases matching the new workflow)
$phases = [
    'writing' => ['label' => 'Writing', 'color' => 'blue', 'steps' => []],
    'audio' => ['label' => 'Audio', 'color' => 'purple', 'steps' => []],
    'video' => ['label' => 'Video', 'color' => 'indigo', 'steps' => []],
    'publish' => ['label' => 'Ready to Publish', 'color' => 'orange', 'steps' => []],
    'final' => ['label' => 'Published', 'color' => 'green', 'steps' => []]
];
foreach ($allSteps as $step) {
    if (isset($phases[$step['phase']])) {
        $phases[$step['phase']]['steps'][] = $step;
    }
}

$message = '';
$messageType = '';

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $title = trim($_POST['title'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $folderLink = trim($_POST['folder_link'] ?? '');
        $youtubeUrl = trim($_POST['youtube_url'] ?? '');

        // Update basic info
        $stmt = $pdo->prepare('UPDATE videos SET title = ?, category_id = ?, notes = ?, folder_link = ?, youtube_url = ? WHERE id = ?');
        $stmt->execute([$title, $categoryId, $notes, $folderLink, $youtubeUrl, $videoId]);

        // Update step progress
        foreach ($allSteps as $step) {
            $status = $_POST["step_{$step['id']}"] ?? 'not_started';
            if (in_array($status, ['not_started', 'in_progress', 'complete'])) {
                $stmt = $pdo->prepare('UPDATE video_progress SET status = ? WHERE video_id = ? AND step_id = ?');
                $stmt->execute([$status, $videoId, $step['id']]);
            }
        }

        $message = 'Video updated successfully.';
        $messageType = 'success';
    }
}

$csrfToken = generateCsrfToken();

// Get video
$stmt = $pdo->prepare('SELECT v.*, c.name as category_name FROM videos v JOIN video_categories c ON v.category_id = c.id WHERE v.id = ?');
$stmt->execute([$videoId]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: /videos');
    exit;
}

// Get video progress
$stmt = $pdo->prepare('SELECT step_id, status FROM video_progress WHERE video_id = ?');
$stmt->execute([$videoId]);
$progress = [];
while ($row = $stmt->fetch()) {
    $progress[$row['step_id']] = $row['status'];
}

// Get categories for dropdown
$categories = $pdo->query('SELECT * FROM video_categories ORDER BY sort_order, name')->fetchAll();

// Calculate progress
$completedSteps = 0;
$totalSteps = count($allSteps);
foreach ($allSteps as $step) {
    if (($progress[$step['id']] ?? 'not_started') === 'complete') {
        $completedSteps++;
    }
}
$progressPercent = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

// Calculate phase progress
function getPhaseProgress($progress, $steps) {
    $completed = 0;
    $total = count($steps);
    foreach ($steps as $step) {
        if (($progress[$step['id']] ?? 'not_started') === 'complete') {
            $completed++;
        }
    }
    return ['completed' => $completed, 'total' => $total, 'percent' => $total > 0 ? ($completed / $total) * 100 : 0];
}

function getPhaseColorClasses($color) {
    $colors = [
        'blue' => ['bg' => 'bg-blue-600', 'light' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300'],
        'purple' => ['bg' => 'bg-purple-600', 'light' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-300'],
        'indigo' => ['bg' => 'bg-indigo-600', 'light' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'border' => 'border-indigo-300'],
        'orange' => ['bg' => 'bg-orange-600', 'light' => 'bg-orange-100', 'text' => 'text-orange-800', 'border' => 'border-orange-300'],
        'green' => ['bg' => 'bg-green-600', 'light' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300'],
    ];
    return $colors[$color] ?? $colors['blue'];
}
?>

<?php if ($message): ?>
<div class="mb-6 rounded-md p-4 <?= $messageType === 'error' ? 'bg-red-50' : 'bg-green-50' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700' : 'text-green-700' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<div class="mb-6">
    <a href="/videos" class="text-blue-600 hover:underline text-sm">&larr; Back to Videos</a>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Video Information</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($video['title']) ?>" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $video['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Folder Link</label>
                        <input type="url" name="folder_link" value="<?= htmlspecialchars($video['folder_link'] ?? '') ?>" placeholder="https://drive.google.com/..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">YouTube URL</label>
                        <input type="url" name="youtube_url" value="<?= htmlspecialchars($video['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="4" placeholder="Ideas, reminders, or other notes about this video..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2"><?= htmlspecialchars($video['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Workflow Progress -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Workflow Progress</h2>

                <!-- Overall Progress bar -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span class="font-medium">Overall Progress</span>
                        <span><?= $completedSteps ?> of <?= $totalSteps ?> steps complete (<?= round($progressPercent) ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-600 h-3 rounded-full transition-all" style="width: <?= $progressPercent ?>%"></div>
                    </div>
                </div>

                <!-- Phases -->
                <?php foreach ($phases as $phaseKey => $phase):
                    if (empty($phase['steps'])) continue;
                    $phaseProgress = getPhaseProgress($progress, $phase['steps']);
                    $colors = getPhaseColorClasses($phase['color']);
                ?>
                <div class="mb-6 border rounded-lg overflow-hidden <?= $colors['border'] ?>">
                    <div class="px-4 py-3 <?= $colors['light'] ?> flex items-center justify-between">
                        <span class="font-medium <?= $colors['text'] ?>"><?= $phase['label'] ?> Phase</span>
                        <div class="flex items-center gap-3">
                            <span class="text-sm <?= $colors['text'] ?>"><?= $phaseProgress['completed'] ?>/<?= $phaseProgress['total'] ?></span>
                            <div class="w-24 bg-white rounded-full h-2">
                                <div class="<?= $colors['bg'] ?> h-2 rounded-full transition-all" style="width: <?= $phaseProgress['percent'] ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <?php foreach ($phase['steps'] as $step):
                            $stepStatus = $progress[$step['id']] ?? 'not_started';
                        ?>
                        <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                            <span class="text-gray-900"><?= htmlspecialchars($step['name']) ?></span>
                            <div class="flex gap-1">
                                <label class="flex items-center gap-1 px-2 py-1 rounded cursor-pointer hover:bg-gray-100 <?= $stepStatus === 'not_started' ? 'bg-gray-100' : '' ?>">
                                    <input type="radio" name="step_<?= $step['id'] ?>" value="not_started" <?= $stepStatus === 'not_started' ? 'checked' : '' ?> class="sr-only">
                                    <span class="w-4 h-4 rounded-full border-2 <?= $stepStatus === 'not_started' ? 'border-gray-400 bg-gray-200' : 'border-gray-300' ?>"></span>
                                    <span class="text-xs text-gray-500">Not Started</span>
                                </label>
                                <label class="flex items-center gap-1 px-2 py-1 rounded cursor-pointer hover:bg-yellow-50 <?= $stepStatus === 'in_progress' ? 'bg-yellow-50' : '' ?>">
                                    <input type="radio" name="step_<?= $step['id'] ?>" value="in_progress" <?= $stepStatus === 'in_progress' ? 'checked' : '' ?> class="sr-only">
                                    <span class="w-4 h-4 rounded-full border-2 <?= $stepStatus === 'in_progress' ? 'border-yellow-500 bg-yellow-400' : 'border-yellow-300' ?>"></span>
                                    <span class="text-xs text-yellow-700">In Progress</span>
                                </label>
                                <label class="flex items-center gap-1 px-2 py-1 rounded cursor-pointer hover:bg-green-50 <?= $stepStatus === 'complete' ? 'bg-green-50' : '' ?>">
                                    <input type="radio" name="step_<?= $step['id'] ?>" value="complete" <?= $stepStatus === 'complete' ? 'checked' : '' ?> class="sr-only">
                                    <span class="w-4 h-4 rounded-full border-2 <?= $stepStatus === 'complete' ? 'border-green-500 bg-green-500' : 'border-green-300' ?>"></span>
                                    <span class="text-xs text-green-700">Complete</span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                    Save Changes
                </button>
            </div>

            <!-- Quick Links -->
            <?php if ($video['folder_link'] || $video['youtube_url']): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-medium mb-3">Quick Links</h3>
                <div class="space-y-2">
                    <?php if ($video['folder_link']): ?>
                    <a href="<?= htmlspecialchars($video['folder_link']) ?>" target="_blank" class="text-blue-600 hover:underline text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Open Project Folder
                    </a>
                    <?php endif; ?>
                    <?php if ($video['youtube_url']): ?>
                    <a href="<?= htmlspecialchars($video['youtube_url']) ?>" target="_blank" class="text-red-600 hover:underline text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                        Watch on YouTube
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Badge -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-medium mb-3">Status</h3>
                <?php if ($progressPercent == 100): ?>
                <div class="flex items-center gap-2 text-green-700 bg-green-50 px-3 py-2 rounded-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Complete</span>
                </div>
                <?php else: ?>
                <?php
                    $currentPhase = 'writing';
                    foreach ($phases as $phaseKey => $phase) {
                        if (empty($phase['steps'])) continue;
                        $phaseProgress = getPhaseProgress($progress, $phase['steps']);
                        if ($phaseProgress['percent'] < 100) {
                            $currentPhase = $phaseKey;
                            break;
                        }
                    }
                    $colors = getPhaseColorClasses($phases[$currentPhase]['color']);
                ?>
                <div class="flex items-center gap-2 <?= $colors['text'] ?> <?= $colors['light'] ?> px-3 py-2 rounded-md">
                    <span class="font-medium"><?= $phases[$currentPhase]['label'] ?> Phase</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-medium mb-3">Details</h3>
                <dl class="text-sm space-y-2">
                    <div>
                        <dt class="text-gray-500">Category</dt>
                        <dd class="font-medium"><?= htmlspecialchars($video['category_name']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd><?= date('M j, Y', strtotime($video['created_at'])) ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd><?= date('M j, Y g:i A', strtotime($video['updated_at'])) ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Delete -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-medium mb-3 text-red-600">Danger Zone</h3>
                <form action="/videos" method="POST" onsubmit="return confirm('Are you sure you want to delete this video? This cannot be undone.')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_video">
                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                        Delete Video
                    </button>
                </form>
            </div>
        </div>
    </div>
</form>

<?php include 'includes/dashboard-footer.php'; ?>
