<?php
/**
 * Procedures API — subjects, procedures, and ordered steps
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

$allowedColors = ['slate','indigo','emerald','amber','rose','blue','purple','orange'];

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s ?: 'item-' . time();
}

try {
    switch ($action) {

        // --- Subjects ---

        case 'create_subject': {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $color = in_array($_POST['color'] ?? '', $allowedColors) ? $_POST['color'] : 'indigo';
            $baseSlug = slugify($name);
            $slug = $baseSlug; $n = 2;
            $check = $pdo->prepare("SELECT 1 FROM procedure_subjects WHERE slug=?");
            while (true) {
                $check->execute([$slug]);
                if (!$check->fetchColumn()) break;
                $slug = $baseSlug . '-' . $n++;
            }
            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM procedure_subjects")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO procedure_subjects (name, slug, color, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $color, $maxSort]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'slug' => $slug]]);
            break;
        }

        case 'update_subject': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');
            $color = in_array($_POST['color'] ?? '', $allowedColors) ? $_POST['color'] : 'indigo';
            $pdo->prepare("UPDATE procedure_subjects SET name=?, color=? WHERE id=?")->execute([$name, $color, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_subject': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM procedure_subjects WHERE id=?")->execute([$id]);
            // procedures.subject_id FK is ON DELETE SET NULL, so procedures survive
            echo json_encode(['success' => true]);
            break;
        }

        // --- Procedures ---

        case 'create_procedure': {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new Exception('Title required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM procedures")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO procedures (title, description, subject_id, project_id, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $subjectId, $projectId, $maxSort, $userId]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
        }

        case 'update_procedure': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new Exception('Title required');
            $description = trim($_POST['description'] ?? '') ?: null;
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $pdo->prepare("UPDATE procedures SET title=?, description=?, subject_id=?, project_id=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$title, $description, $subjectId, $projectId, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_procedure': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM procedures WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'reorder_procedures': {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) throw new Exception('ids must be an array');
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE procedures SET sort_order=?, updated_at=datetime('now') WHERE id=?");
            foreach ($ids as $i => $id) $upd->execute([(int)$i, (int)$id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
        }

        // --- Steps ---

        case 'add_step': {
            $procId = (int)($_POST['procedure_id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$procId || $text === '') throw new Exception('Procedure and text required');
            $maxSort = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM procedure_steps WHERE procedure_id=?");
            $maxSort->execute([$procId]);
            $sort = (int)$maxSort->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO procedure_steps (procedure_id, text, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$procId, $text, $sort]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'sort_order' => $sort]]);
            break;
        }

        case 'update_step': {
            $id = (int)($_POST['id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (!$id || $text === '') throw new Exception('Invalid input');
            $pdo->prepare("UPDATE procedure_steps SET text=? WHERE id=?")->execute([$text, $id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete_step': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Missing id');
            $pdo->prepare("DELETE FROM procedure_steps WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'reorder_steps': {
            $procId = (int)($_POST['procedure_id'] ?? 0);
            $ids = $_POST['ids'] ?? [];
            if (!$procId || !is_array($ids)) throw new Exception('Invalid input');
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE procedure_steps SET sort_order=? WHERE id=? AND procedure_id=?");
            foreach ($ids as $i => $stepId) $upd->execute([(int)$i, (int)$stepId, $procId]);
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
