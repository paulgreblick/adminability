<?php
/**
 * Brainstorm API — shared quick-list CRUD + reorder
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

        case 'create': {
            $text = trim($_POST['text'] ?? '');
            if ($text === '') throw new Exception('Text required');
            $timing = trim($_POST['timing'] ?? '');
            $notes  = trim($_POST['notes'] ?? '');
            $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM brainstorm_items")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO brainstorm_items (text, timing, notes, assigned_to, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$text, $timing ?: null, $notes ?: null, $assignedTo, $maxSort, $userId]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'sort_order' => $maxSort]]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');

            // Build a partial update: only columns actually present in the POST.
            $fields = [];
            $params = [];
            if (array_key_exists('text', $_POST)) {
                $text = trim($_POST['text']);
                if ($text === '') throw new Exception('Text cannot be empty');
                $fields[] = 'text=?';
                $params[] = $text;
            }
            if (array_key_exists('timing', $_POST)) {
                $t = trim($_POST['timing']);
                $fields[] = 'timing=?';
                $params[] = $t === '' ? null : $t;
            }
            if (array_key_exists('notes', $_POST)) {
                $n = trim($_POST['notes']);
                $fields[] = 'notes=?';
                $params[] = $n === '' ? null : $n;
            }
            if (array_key_exists('assigned_to', $_POST)) {
                $a = $_POST['assigned_to'];
                $fields[] = 'assigned_to=?';
                $params[] = ($a === '' || $a === null) ? null : (int)$a;
            }
            if (!$fields) throw new Exception('Nothing to update');

            $fields[] = "updated_at=datetime('now')";
            $params[] = $id;
            $pdo->prepare("UPDATE brainstorm_items SET " . implode(',', $fields) . " WHERE id=?")->execute($params);
            echo json_encode(['success' => true]);
            break;
        }

        case 'toggle': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("UPDATE brainstorm_items SET is_done = CASE WHEN is_done = 1 THEN 0 ELSE 1 END, updated_at=datetime('now') WHERE id=?")->execute([$id]);
            $cur = $pdo->prepare("SELECT is_done FROM brainstorm_items WHERE id=?");
            $cur->execute([$id]);
            echo json_encode(['success' => true, 'data' => ['is_done' => (int)$cur->fetchColumn()]]);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM brainstorm_items WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'clear_done': {
            $pdo->exec("DELETE FROM brainstorm_items WHERE is_done = 1");
            echo json_encode(['success' => true]);
            break;
        }

        case 'reorder': {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) throw new Exception('ids must be an array');
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE brainstorm_items SET sort_order=?, updated_at=datetime('now') WHERE id=?");
            foreach ($ids as $i => $id) {
                $upd->execute([(int)$i, (int)$id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
        }

        case 'step_create': {
            $brainstormId = (int)($_POST['brainstorm_id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$brainstormId || $text === '') throw new Exception('brainstorm_id and text required');
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM brainstorm_steps WHERE brainstorm_id=?");
            $stmt->execute([$brainstormId]);
            $maxSort = (int)$stmt->fetchColumn();
            $pdo->prepare("INSERT INTO brainstorm_steps (brainstorm_id, text, sort_order) VALUES (?, ?, ?)")
                ->execute([$brainstormId, $text, $maxSort]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'sort_order' => $maxSort]]);
            break;
        }

        case 'step_toggle': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("UPDATE brainstorm_steps SET is_done = CASE WHEN is_done=1 THEN 0 ELSE 1 END WHERE id=?")->execute([$id]);
            $cur = $pdo->prepare("SELECT is_done FROM brainstorm_steps WHERE id=?");
            $cur->execute([$id]);
            echo json_encode(['success' => true, 'data' => ['is_done' => (int)$cur->fetchColumn()]]);
            break;
        }

        case 'step_update': {
            $id = (int)($_POST['id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$id || $text === '') throw new Exception('Invalid input');
            $pdo->prepare("UPDATE brainstorm_steps SET text=? WHERE id=?")->execute([$text, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'step_delete': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM brainstorm_steps WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'state_hash': {
            // Lightweight signature of current state for polling. Respects person filter.
            $person = $_POST['person'] ?? '';
            $whereBs = '';
            $bsParams = [];
            if ($person === 'none') {
                $whereBs = 'WHERE assigned_to IS NULL';
            } elseif ($person !== '' && ctype_digit($person)) {
                $whereBs = 'WHERE assigned_to = ?';
                $bsParams[] = (int)$person;
            }

            $iStmt = $pdo->prepare("
                SELECT id, text, timing, notes, is_done, sort_order, assigned_to, updated_at
                FROM brainstorm_items
                $whereBs
                ORDER BY is_done, sort_order, id
            ");
            $iStmt->execute($bsParams);
            $iRows = $iStmt->fetchAll(PDO::FETCH_ASSOC);

            $sRows = [];
            if (!empty($iRows)) {
                $ids = array_column($iRows, 'id');
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $sStmt = $pdo->prepare("
                    SELECT id, brainstorm_id, text, is_done, sort_order
                    FROM brainstorm_steps
                    WHERE brainstorm_id IN ($ph)
                    ORDER BY brainstorm_id, sort_order, id
                ");
                $sStmt->execute($ids);
                $sRows = $sStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $hash = md5(json_encode([$iRows, $sRows]));
            echo json_encode(['success' => true, 'data' => ['hash' => $hash]]);
            break;
        }

        case 'step_reorder': {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) throw new Exception('ids must be an array');
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE brainstorm_steps SET sort_order=? WHERE id=?");
            foreach ($ids as $i => $id) {
                $upd->execute([(int)$i, (int)$id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
        }

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
