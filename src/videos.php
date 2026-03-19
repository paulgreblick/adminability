<?php
/**
 * Affirmation Videos Dashboard - v2
 * Phase view with expandable details and AJAX status updates
 */

$dashboard_title = 'Videos';
$current_dashboard_page = 'videos';

include 'includes/dashboard-layout.php';

$message = '';
$messageType = '';

// Define the 5 phases and their steps
$phases = [
    'writing' => ['label' => 'Writing', 'color' => 'blue'],
    'audio' => ['label' => 'Audio', 'color' => 'purple'],
    'video' => ['label' => 'Video', 'color' => 'indigo'],
    'publish' => ['label' => 'Ready to Publish', 'color' => 'orange'],
    'final' => ['label' => 'Published', 'color' => 'green']
];

// Get workflow steps from database
$allSteps = $pdo->query('SELECT * FROM workflow_steps ORDER BY sort_order')->fetchAll();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_step':
                $videoId = (int)($_POST['video_id'] ?? 0);
                $stepId = (int)($_POST['step_id'] ?? 0);
                $status = $_POST['status'] ?? '';

                if ($stepId && in_array($status, ['not_started', 'in_progress', 'complete'])) {
                    $stmt = $pdo->prepare("UPDATE video_progress SET status = ?, updated_at = datetime('now') WHERE video_id = ? AND step_id = ?");
                    $stmt->execute([$status, $videoId, $stepId]);
                }
                break;

            case 'add_video':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $title = trim($_POST['video_title'] ?? '');
                if ($categoryId && $title) {
                    $stmt = $pdo->prepare('INSERT INTO videos (category_id, title) VALUES (?, ?)');
                    $stmt->execute([$categoryId, $title]);
                    $newVideoId = $pdo->lastInsertId();

                    // Initialize progress for all workflow steps
                    $insertStmt = $pdo->prepare('INSERT INTO video_progress (video_id, step_id, status) VALUES (?, ?, "not_started")');
                    foreach ($allSteps as $step) {
                        $insertStmt->execute([$newVideoId, $step['id']]);
                    }

                    $message = 'Video added.';
                    $messageType = 'success';
                }
                break;

            case 'delete_video':
                $videoId = (int)($_POST['video_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM videos WHERE id = ?');
                $stmt->execute([$videoId]);
                $message = 'Video deleted.';
                $messageType = 'success';
                break;

            case 'add_category':
                $name = trim($_POST['category_name'] ?? '');
                $description = trim($_POST['category_description'] ?? '');
                if ($name) {
                    $stmt = $pdo->prepare('INSERT INTO video_categories (name, description) VALUES (?, ?)');
                    $stmt->execute([$name, $description]);
                    $message = 'Category added.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

$csrfToken = generateCsrfToken();

// Get filter
$filterCategory = (int)($_GET['category'] ?? 0);

// Get categories
$categories = $pdo->query('SELECT * FROM video_categories ORDER BY sort_order, name')->fetchAll();

// Build category lookup
$categoryLookup = [];
foreach ($categories as $cat) {
    $categoryLookup[$cat['id']] = $cat['name'];
}

// Get videos with progress
$videoSql = 'SELECT v.*, c.name as category_name FROM videos v JOIN video_categories c ON v.category_id = c.id';
if ($filterCategory) {
    $videoSql .= ' WHERE v.category_id = ?';
}
$videoSql .= ' ORDER BY c.sort_order, c.name, v.title';

$stmt = $pdo->prepare($videoSql);
if ($filterCategory) {
    $stmt->execute([$filterCategory]);
} else {
    $stmt->execute();
}
$videos = $stmt->fetchAll();

// Get progress for all videos
$videoProgress = [];
foreach ($videos as $video) {
    $progStmt = $pdo->prepare('
        SELECT vp.step_id, vp.status, ws.phase, ws.name as step_name
        FROM video_progress vp
        JOIN workflow_steps ws ON vp.step_id = ws.id
        WHERE vp.video_id = ?
        ORDER BY ws.sort_order
    ');
    $progStmt->execute([$video['id']]);
    $videoProgress[$video['id']] = $progStmt->fetchAll();
}

// Helper: Calculate phase completion
function getPhaseStatus($progress, $phase) {
    $phaseSteps = array_filter($progress, fn($p) => $p['phase'] === $phase);
    if (empty($phaseSteps)) return 'empty';

    $total = count($phaseSteps);
    $complete = count(array_filter($phaseSteps, fn($p) => $p['status'] === 'complete'));
    $inProgress = count(array_filter($phaseSteps, fn($p) => $p['status'] === 'in_progress'));

    if ($complete === $total) return 'complete';
    if ($complete > 0 || $inProgress > 0) return 'in_progress';
    return 'not_started';
}

// Helper: Get phase icon
function getPhaseIcon($status) {
    switch ($status) {
        case 'complete': return '<span class="text-green-600 text-lg">&#x25CF;</span>';
        case 'in_progress': return '<span class="text-yellow-500 text-lg">&#x25D0;</span>';
        case 'empty': return '<span class="text-gray-200 text-lg">&#x25CB;</span>';
        default: return '<span class="text-gray-300 text-lg">&#x25CB;</span>';
    }
}

// Stats
$totalVideos = count($videos);
$completedVideos = 0;
foreach ($videos as $video) {
    $progress = $videoProgress[$video['id']] ?? [];
    $allComplete = !empty($progress) && count(array_filter($progress, fn($p) => $p['status'] === 'complete')) === count($progress);
    if ($allComplete) $completedVideos++;
}
?>

<?php if ($message): ?>
<div class="mb-6 rounded-md p-4 <?= $messageType === 'error' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<!-- Stats -->
<?php if ($totalVideos > 0): ?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-gray-900 dark:text-white"><?= $completedVideos ?></span> of <span class="font-semibold text-gray-900 dark:text-white"><?= $totalVideos ?></span> videos complete
    </div>
    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 max-w-xs">
        <div class="bg-green-600 h-2 rounded-full" style="width: <?= $totalVideos > 0 ? round(($completedVideos / $totalVideos) * 100) : 0 ?>%"></div>
    </div>
</div>
<?php endif; ?>

<!-- Filters & Actions -->
<div class="mb-6 space-y-4">
    <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:overflow-visible sm:flex-wrap">
        <span class="text-sm text-gray-600 dark:text-gray-400 flex-shrink-0">Filter:</span>
        <a href="/videos" class="px-3 py-1 rounded-full text-sm whitespace-nowrap flex-shrink-0 <?= !$filterCategory ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">All Topics</a>
        <?php foreach ($categories as $cat): ?>
        <a href="/videos?category=<?= $cat['id'] ?>" class="px-3 py-1 rounded-full text-sm whitespace-nowrap flex-shrink-0 <?= $filterCategory === $cat['id'] ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>"><?= htmlspecialchars($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="flex gap-2">
        <button onclick="document.getElementById('addVideoModal').classList.remove('hidden')" class="flex-1 sm:flex-none px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
            + Add Video
        </button>
        <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="flex-1 sm:flex-none px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700">
            + Category
        </button>
    </div>
</div>

<!-- Main Table -->
<?php if (empty($videos)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500 dark:text-gray-400">
    No videos yet. Add your first video to get started.
</div>
<?php else: ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden overflow-x-auto">
    <table class="min-w-full" style="min-width: 700px;">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-10"></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subject</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Writing</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-purple-600 dark:text-purple-400 uppercase">Audio</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase">Video</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-orange-600 dark:text-orange-400 uppercase">Ready</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-green-600 dark:text-green-400 uppercase">Published</th>
                <th class="px-4 py-3 w-16"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($videos as $video):
                $progress = $videoProgress[$video['id']] ?? [];
            ?>
            <tr class="video-row hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" data-video-id="<?= $video['id'] ?>">
                <td class="px-4 py-3 text-gray-400">
                    <svg class="w-4 h-4 expand-icon transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($video['category_name']) ?></td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($video['title']) ?></td>
                <td class="px-4 py-3 text-center"><?= getPhaseIcon(getPhaseStatus($progress, 'writing')) ?></td>
                <td class="px-4 py-3 text-center"><?= getPhaseIcon(getPhaseStatus($progress, 'audio')) ?></td>
                <td class="px-4 py-3 text-center"><?= getPhaseIcon(getPhaseStatus($progress, 'video')) ?></td>
                <td class="px-4 py-3 text-center"><?= getPhaseIcon(getPhaseStatus($progress, 'publish')) ?></td>
                <td class="px-4 py-3 text-center"><?= getPhaseIcon(getPhaseStatus($progress, 'final')) ?></td>
                <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this video?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete_video">
                        <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <!-- Expanded Detail Row -->
            <tr class="detail-row hidden bg-gray-50 dark:bg-gray-700/50" data-video-id="<?= $video['id'] ?>">
                <td colspan="9" class="px-4 py-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                        <?php foreach (['writing', 'audio', 'video', 'publish', 'final'] as $phaseKey):
                            $phaseSteps = array_filter($progress, fn($p) => $p['phase'] === $phaseKey);
                            $phaseInfo = $phases[$phaseKey];
                        ?>
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2"><?= $phaseInfo['label'] ?></div>
                            <div class="space-y-1">
                                <?php foreach ($phaseSteps as $step): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="status-btn hover:scale-110 transition-transform"
                                        data-video="<?= $video['id'] ?>"
                                        data-step="<?= $step['step_id'] ?>"
                                        data-status="<?= $step['status'] ?>"
                                        title="Click to change">
                                        <?php if ($step['status'] === 'complete'): ?>
                                        <span class="text-green-600">&#x25CF;</span>
                                        <?php elseif ($step['status'] === 'in_progress'): ?>
                                        <span class="text-yellow-500">&#x25D0;</span>
                                        <?php else: ?>
                                        <span class="text-gray-300 dark:text-gray-500">&#x25CB;</span>
                                        <?php endif; ?>
                                    </button>
                                    <span class="text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($step['step_name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($phaseSteps)): ?>
                                <div class="text-sm text-gray-400 italic">No steps defined</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Add Video Modal -->
<div id="addVideoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Add Video</h2>
            <button type="button" onclick="document.getElementById('addVideoModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="add_video">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject (Category)</label>
                <select name="category_id" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
                    <option value="">Select subject...</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Video Title</label>
                <input type="text" name="video_title" placeholder="e.g., Morning Energy Boost" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('addVideoModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Add Video</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Add Category</h2>
            <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="add_category">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
                <input type="text" name="category_name" placeholder="e.g., Confidence" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description (optional)</label>
                <input type="text" name="category_description" placeholder="Brief description" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2">
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 dark:text-white rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Add Category</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle expanded rows
document.querySelectorAll('.video-row').forEach(row => {
    row.addEventListener('click', function() {
        const videoId = this.dataset.videoId;
        const detailRow = document.querySelector(`.detail-row[data-video-id="${videoId}"]`);
        const icon = this.querySelector('.expand-icon');
        detailRow.classList.toggle('hidden');
        icon.classList.toggle('rotate-90');
    });
});

// AJAX status updates
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.stopPropagation();

        const videoId = this.dataset.video;
        const stepId = this.dataset.step;
        let currentStatus = this.dataset.status;

        const nextStatus = currentStatus === 'not_started' ? 'in_progress'
                         : currentStatus === 'in_progress' ? 'complete'
                         : 'not_started';

        const icons = {
            'not_started': '<span class="text-gray-300">\u25CB</span>',
            'in_progress': '<span class="text-yellow-500">\u25D0</span>',
            'complete': '<span class="text-green-600">\u25CF</span>'
        };
        this.innerHTML = icons[nextStatus];
        this.dataset.status = nextStatus;

        const formData = new FormData();
        formData.append('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');
        formData.append('action', 'update_step');
        formData.append('video_id', videoId);
        formData.append('step_id', stepId);
        formData.append('status', nextStatus);

        try {
            await fetch('/videos', { method: 'POST', body: formData });
            setTimeout(() => location.reload(), 300);
        } catch (err) {
            this.innerHTML = icons[currentStatus];
            this.dataset.status = currentStatus;
        }
    });
});
</script>

<?php include 'includes/dashboard-footer.php'; ?>
