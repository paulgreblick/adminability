<?php
/**
 * Notes API — JSON endpoints
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

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {

        case 'toggle_pin': {
            $id = (int)($_POST['note_id'] ?? 0);
            if (!$id) throw new Exception('Missing note_id');
            $stmt = $pdo->prepare("UPDATE notes SET is_pinned = CASE WHEN is_pinned = 1 THEN 0 ELSE 1 END, updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$id]);
            $cur = $pdo->prepare('SELECT is_pinned FROM notes WHERE id = ?');
            $cur->execute([$id]);
            echo json_encode(['success' => true, 'data' => ['is_pinned' => (int)$cur->fetch()['is_pinned']]]);
            break;
        }

        case 'toggle_done': {
            $id = (int)($_POST['note_id'] ?? 0);
            if (!$id) throw new Exception('Missing note_id');
            $stmt = $pdo->prepare('SELECT status FROM notes WHERE id = ?');
            $stmt->execute([$id]);
            $n = $stmt->fetch();
            if (!$n) throw new Exception('Note not found');
            $newStatus = $n['status'] === 'done' ? 'active' : 'done';
            $upd = $pdo->prepare("UPDATE notes SET status = ?, updated_at = datetime('now') WHERE id = ?");
            $upd->execute([$newStatus, $id]);
            echo json_encode(['success' => true, 'data' => ['status' => $newStatus]]);
            break;
        }

        case 'create': {
            $content = trim($_POST['content'] ?? '');
            if ($content === '') throw new Exception('Content required');
            $title = trim($_POST['title'] ?? '') ?: null;
            $type = $_POST['type'] ?? 'note';
            $priority = $_POST['priority'] ?? 'normal';
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            if (!in_array($type, ['note', 'idea', 'task', 'question'])) $type = 'note';
            if (!in_array($priority, ['low', 'normal', 'high'])) $priority = 'normal';

            $stmt = $pdo->prepare("INSERT INTO notes (title, content, type, priority, project_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $type, $priority, $projectId, $userId]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['note_id'] ?? 0);
            if (!$id) throw new Exception('Missing note_id');
            $content = trim($_POST['content'] ?? '');
            if ($content === '') throw new Exception('Content required');
            $title = trim($_POST['title'] ?? '') ?: null;
            $type = $_POST['type'] ?? 'note';
            $priority = $_POST['priority'] ?? 'normal';
            $status = $_POST['status'] ?? 'active';
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            if (!in_array($type, ['note', 'idea', 'task', 'question'])) $type = 'note';
            if (!in_array($priority, ['low', 'normal', 'high'])) $priority = 'normal';
            if (!in_array($status, ['active', 'done', 'archived'])) $status = 'active';

            $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, type = ?, priority = ?, status = ?, project_id = ?, updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$title, $content, $type, $priority, $status, $projectId, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['note_id'] ?? 0);
            if (!$id) throw new Exception('Missing note_id');
            $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
