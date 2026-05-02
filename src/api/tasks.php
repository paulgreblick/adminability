<?php
/**
 * Tasks API — JSON endpoints for task CRUD and status toggles
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {

        case 'toggle_status': {
            $id = (int)($_POST['task_id'] ?? 0);
            if (!$id) throw new Exception('Missing task_id');

            $stmt = $pdo->prepare('SELECT status FROM tasks WHERE id = ?');
            $stmt->execute([$id]);
            $task = $stmt->fetch();
            if (!$task) throw new Exception('Task not found');

            // Cycle: todo -> in_progress -> done -> todo
            $next = ['todo' => 'in_progress', 'in_progress' => 'done', 'done' => 'todo'][$task['status']];
            $completedAt = $next === 'done' ? date('Y-m-d H:i:s') : null;

            $upd = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ?, updated_at = datetime('now') WHERE id = ?");
            $upd->execute([$next, $completedAt, $id]);

            echo json_encode(['success' => true, 'data' => ['status' => $next]]);
            break;
        }

        case 'set_status': {
            $id = (int)($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id || !in_array($status, ['todo', 'in_progress', 'done'])) {
                throw new Exception('Invalid input');
            }
            $completedAt = $status === 'done' ? date('Y-m-d H:i:s') : null;
            $upd = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ?, updated_at = datetime('now') WHERE id = ?");
            $upd->execute([$status, $completedAt, $id]);
            echo json_encode(['success' => true, 'data' => ['status' => $status]]);
            break;
        }

        case 'create': {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new Exception('Title required');

            $description = trim($_POST['description'] ?? '') ?: null;
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $priority = $_POST['priority'] ?? 'normal';
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) $priority = 'normal';

            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, project_id, priority, due_date, created_by, assigned_to)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $projectId, $priority, $dueDate, $userId, $assignedTo]);

            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['task_id'] ?? 0);
            if (!$id) throw new Exception('Missing task_id');

            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new Exception('Title required');

            $description = trim($_POST['description'] ?? '') ?: null;
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $priority = $_POST['priority'] ?? 'normal';
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) $priority = 'normal';

            $stmt = $pdo->prepare("
                UPDATE tasks SET
                    title = ?, description = ?, project_id = ?,
                    priority = ?, due_date = ?, assigned_to = ?,
                    updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $projectId, $priority, $dueDate, $assignedTo, $id]);

            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['task_id'] ?? 0);
            if (!$id) throw new Exception('Missing task_id');

            $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;
        }

        case 'create_project': {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = $_POST['color'] ?? 'indigo';
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $allowed = ['slate', 'indigo', 'emerald', 'amber', 'rose', 'blue', 'purple', 'orange'];
            if (!in_array($color, $allowed)) $color = 'indigo';

            $stmt = $pdo->prepare("INSERT INTO projects (name, description, color, parent_id, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $color, $parentId, $userId]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
        }

        case 'update_project': {
            $id = (int)($_POST['project_id'] ?? 0);
            if (!$id) throw new Exception('Missing project_id');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = $_POST['color'] ?? 'indigo';
            $allowed = ['slate', 'indigo', 'emerald', 'amber', 'rose', 'blue', 'purple'];
            if (!in_array($color, $allowed)) $color = 'indigo';

            $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, color = ?, updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$name, $description, $color, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_project': {
            $id = (int)($_POST['project_id'] ?? 0);
            if (!$id) throw new Exception('Missing project_id');
            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        // --- Checklist items ---

        case 'add_checklist_item': {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$taskId || $text === '') throw new Exception('Task ID and text required');
            $maxSort = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM checklist_items WHERE task_id = ?");
            $maxSort->execute([$taskId]);
            $sort = (int)$maxSort->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO checklist_items (task_id, text, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, $text, $sort]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'sort_order' => $sort]]);
            break;
        }

        case 'toggle_checklist_item': {
            $id = (int)($_POST['item_id'] ?? 0);
            if (!$id) throw new Exception('Missing item_id');
            $pdo->prepare("UPDATE checklist_items SET is_done = CASE WHEN is_done = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$id]);
            $cur = $pdo->prepare('SELECT is_done FROM checklist_items WHERE id = ?');
            $cur->execute([$id]);
            echo json_encode(['success' => true, 'data' => ['is_done' => (int)$cur->fetchColumn()]]);
            break;
        }

        case 'update_checklist_item': {
            $id = (int)($_POST['item_id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$id || $text === '') throw new Exception('Invalid input');
            $pdo->prepare("UPDATE checklist_items SET text = ? WHERE id = ?")->execute([$text, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_checklist_item': {
            $id = (int)($_POST['item_id'] ?? 0);
            if (!$id) throw new Exception('Missing item_id');
            $pdo->prepare('DELETE FROM checklist_items WHERE id = ?')->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        // --- Dependencies ---

        case 'add_dependency': {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $dependsOnId = (int)($_POST['depends_on_id'] ?? 0);
            if (!$taskId || !$dependsOnId) throw new Exception('Both task IDs required');
            if ($taskId === $dependsOnId) throw new Exception('A task cannot depend on itself');
            $pdo->prepare("INSERT OR IGNORE INTO task_dependencies (task_id, depends_on_id) VALUES (?, ?)")
                ->execute([$taskId, $dependsOnId]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'remove_dependency': {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $dependsOnId = (int)($_POST['depends_on_id'] ?? 0);
            if (!$taskId || !$dependsOnId) throw new Exception('Both task IDs required');
            $pdo->prepare("DELETE FROM task_dependencies WHERE task_id = ? AND depends_on_id = ?")
                ->execute([$taskId, $dependsOnId]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'search_tasks': {
            $q = trim($_POST['q'] ?? '');
            $excludeId = (int)($_POST['exclude_id'] ?? 0);
            if ($q === '') { echo json_encode(['success' => true, 'data' => []]); break; }
            $stmt = $pdo->prepare("
                SELECT t.id, t.title, p.name as project_name, p.color as project_color
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.title LIKE ? AND t.id != ?
                ORDER BY t.title
                LIMIT 10
            ");
            $stmt->execute(['%' . $q . '%', $excludeId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        }

        case 'archive_project': {
            $id = (int)($_POST['project_id'] ?? 0);
            if (!$id) throw new Exception('Missing project_id');
            $pdo->prepare("UPDATE projects SET status = 'archived', updated_at = datetime('now') WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'unarchive_project': {
            $id = (int)($_POST['project_id'] ?? 0);
            if (!$id) throw new Exception('Missing project_id');
            $pdo->prepare("UPDATE projects SET status = 'active', updated_at = datetime('now') WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
