<?php
/**
 * User Management
 */

$dashboard_title = 'Users';
$current_dashboard_page = 'users';

include 'includes/dashboard-layout.php';

requirePermission('users.view');

$message = '';
$messageType = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                if (!hasPermission('users.create')) {
                    $message = 'You do not have permission to create users.';
                    $messageType = 'error';
                    break;
                }

                $email = trim($_POST['email'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $password = $_POST['password'] ?? '';
                $roleId = (int)($_POST['role_id'] ?? 0);

                if (empty($email) || empty($name) || empty($password)) {
                    $message = 'All fields are required.';
                    $messageType = 'error';
                } elseif (strlen($password) < 8) {
                    $message = 'Password must be at least 8 characters.';
                    $messageType = 'error';
                } else {
                    $userId = createUser($email, $password, $name, $roleId ?: null);
                    if ($userId) {
                        $message = 'User created successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Email already exists.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'delete':
                if (!hasPermission('users.delete')) {
                    $message = 'You do not have permission to delete users.';
                    $messageType = 'error';
                    break;
                }

                $userId = (int)($_POST['user_id'] ?? 0);

                // Prevent self-deletion
                if ($userId === (int)$_SESSION['user_id']) {
                    $message = 'You cannot delete your own account.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $message = 'User deleted successfully.';
                    $messageType = 'success';
                }
                break;

            case 'toggle_status':
                if (!hasPermission('users.edit')) {
                    $message = 'You do not have permission to edit users.';
                    $messageType = 'error';
                    break;
                }

                $userId = (int)($_POST['user_id'] ?? 0);

                // Prevent self-deactivation
                if ($userId === (int)$_SESSION['user_id']) {
                    $message = 'You cannot deactivate your own account.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
                    $stmt->execute([$userId]);
                    $message = 'User status updated.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

$csrfToken = generateCsrfToken();
$roles = getRoles();

// Get all users
$stmt = $pdo->query('
    SELECT u.*, r.name as role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
');
$users = $stmt->fetchAll();
?>

<?php if ($message): ?>
<div class="mb-6 rounded-md p-4 <?= $messageType === 'error' ? 'bg-red-50' : 'bg-green-50' ?>">
    <p class="text-sm <?= $messageType === 'error' ? 'text-red-700' : 'text-green-700' ?>"><?= htmlspecialchars($message) ?></p>
</div>
<?php endif; ?>

<?php if (hasPermission('users.create')): ?>
<!-- Create User Form -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Create New User</h2>
    </div>
    <form method="POST" class="p-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="create">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="name">Name</label>
                <input type="text" name="name" id="name" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
                <input type="password" name="password" id="password" required minlength="8"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="role_id">Role</label>
                <select name="role_id" id="role_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2">
                    <option value="">No Role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                Create User
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Users List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                <?php if (hasPermission('users.edit') || hasPermission('users.delete')): ?>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $user): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        <?= htmlspecialchars($user['role_name'] ?? 'No Role') ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($user['is_active']): ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                    <?php else: ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                </td>
                <?php if (hasPermission('users.edit') || hasPermission('users.delete')): ?>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <?php if (hasPermission('users.edit') && $user['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="text-blue-600 hover:text-blue-900">
                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (hasPermission('users.delete') && $user['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
