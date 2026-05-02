<?php
/**
 * Upskilling API — shared list of learning links (videos, articles).
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

/**
 * Extract a YouTube video ID from a URL. Handles watch, youtu.be, shorts, embed forms.
 * Returns null if the URL isn't a recognised YouTube link.
 */
function extractYouTubeId(string $url): ?string {
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*?&)?v=|embed/|shorts/|v/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
        return $m[1];
    }
    return null;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {

        case 'create': {
            $url = trim($_POST['url'] ?? '');
            if ($url === '') throw new Exception('URL required');
            if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;

            $title = trim($_POST['title'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;

            $youtubeId = extractYouTubeId($url);

            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM upskilling_items")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO upskilling_items (url, title, notes, youtube_id, assigned_to, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$url, $title ?: null, $notes ?: null, $youtubeId, $assignedTo, $maxSort, $userId]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');

            $fields = [];
            $params = [];
            if (array_key_exists('url', $_POST)) {
                $url = trim($_POST['url']);
                if ($url === '') throw new Exception('URL cannot be empty');
                if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
                $fields[] = 'url=?';
                $params[] = $url;
                $fields[] = 'youtube_id=?';
                $params[] = extractYouTubeId($url);
            }
            if (array_key_exists('title', $_POST)) {
                $t = trim($_POST['title']);
                $fields[] = 'title=?';
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
            $pdo->prepare("UPDATE upskilling_items SET " . implode(',', $fields) . " WHERE id=?")->execute($params);
            echo json_encode(['success' => true]);
            break;
        }

        case 'set_status': {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id || !in_array($status, ['unwatched', 'watching', 'watched'], true)) {
                throw new Exception('Invalid input');
            }
            $pdo->prepare("UPDATE upskilling_items SET status=?, updated_at=datetime('now') WHERE id=?")->execute([$status, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM upskilling_items WHERE id=?")->execute([$id]);
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
