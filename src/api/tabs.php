<?php
/**
 * Tab Opener API — JSON endpoints for tab sets and URLs
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

function normalize_url($url) {
    $url = trim($url);
    if ($url === '') return null;
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

try {
    switch ($action) {

        case 'create_set': {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = $_POST['color'] ?? 'indigo';
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $allowed = ['slate','indigo','emerald','amber','rose','blue','purple','orange'];
            if (!in_array($color, $allowed)) $color = 'indigo';

            $urls = $_POST['urls'] ?? [];
            $labels = $_POST['labels'] ?? [];

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tab_sets (name, description, color, assigned_to, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $color, $assignedTo, $userId]);
            $setId = $pdo->lastInsertId();

            $urlIns = $pdo->prepare("INSERT INTO tab_set_urls (set_id, url, label, sort_order) VALUES (?, ?, ?, ?)");
            $sort = 0;
            foreach ($urls as $i => $u) {
                $clean = normalize_url($u);
                if ($clean !== null) {
                    $label = trim($labels[$i] ?? '') ?: null;
                    $urlIns->execute([$setId, $clean, $label, $sort++]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'data' => ['id' => $setId]]);
            break;
        }

        case 'update_set': {
            $id = (int)($_POST['set_id'] ?? 0);
            if (!$id) throw new Exception('Missing set_id');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = $_POST['color'] ?? 'indigo';
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $allowed = ['slate','indigo','emerald','amber','rose','blue','purple','orange'];
            if (!in_array($color, $allowed)) $color = 'indigo';

            $urls = $_POST['urls'] ?? [];
            $labels = $_POST['labels'] ?? [];

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE tab_sets SET name=?, description=?, color=?, assigned_to=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name, $description, $color, $assignedTo, $id]);

            // Replace all URLs (simpler than diffing)
            $pdo->prepare("DELETE FROM tab_set_urls WHERE set_id=?")->execute([$id]);
            $urlIns = $pdo->prepare("INSERT INTO tab_set_urls (set_id, url, label, sort_order) VALUES (?, ?, ?, ?)");
            $sort = 0;
            foreach ($urls as $i => $u) {
                $clean = normalize_url($u);
                if ($clean !== null) {
                    $label = trim($labels[$i] ?? '') ?: null;
                    $urlIns->execute([$id, $clean, $label, $sort++]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_set': {
            $id = (int)($_POST['set_id'] ?? 0);
            if (!$id) throw new Exception('Missing set_id');
            $pdo->prepare("DELETE FROM tab_sets WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'reorder_sets': {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) throw new Exception('ids must be an array');
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE tab_sets SET sort_order=?, updated_at=datetime('now') WHERE id=?");
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
