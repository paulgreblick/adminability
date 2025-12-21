<?php
/**
 * Roles Management
 */

$dashboard_title = 'Roles';
$current_dashboard_page = 'roles';

include 'includes/dashboard-layout.php';

requirePermission('roles.view');

// Get all roles with permission counts
$stmt = $pdo->query('
    SELECT r.*, COUNT(rp.permission_id) as permission_count
    FROM roles r
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id
    ORDER BY r.id
');
$roles = $stmt->fetchAll();

// Get all permissions
$stmt = $pdo->query('SELECT * FROM permissions ORDER BY name');
$permissions = $stmt->fetchAll();

// Get role permissions mapping
$stmt = $pdo->query('SELECT role_id, permission_id FROM role_permissions');
$rolePermissions = [];
while ($row = $stmt->fetch()) {
    $rolePermissions[$row['role_id']][] = $row['permission_id'];
}
?>

<!-- Roles List -->
<div class="space-y-6">
    <?php foreach ($roles as $role): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($role['name']) ?></h3>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($role['description'] ?? '') ?></p>
            </div>
            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                <?= $role['permission_count'] ?> permissions
            </span>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-wrap gap-2">
                <?php
                $rolePerms = $rolePermissions[$role['id']] ?? [];
                foreach ($permissions as $perm):
                    $hasPerm = in_array($perm['id'], $rolePerms);
                ?>
                <span class="px-2 py-1 text-xs rounded <?= $hasPerm ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400' ?>">
                    <?= htmlspecialchars($perm['name']) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
