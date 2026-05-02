<?php
/**
 * Monitors API — uptime checks (bundle-style: one monitor → many URLs)
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
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    return $url;
}

/**
 * Run an HTTP check against $url.
 * Returns: ['status' => 'up'|'down', 'code' => int|null, 'time_ms' => int|null, 'error' => string|null]
 */
function check_url($url) {
    if (!function_exists('curl_init')) {
        return ['status' => 'down', 'code' => null, 'time_ms' => null, 'error' => 'cURL not available'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'Adminability-Uptime/1.0',
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $start = microtime(true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    // Retry with GET if HEAD is not accepted
    if (in_array($code, [405, 501], true)) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Adminability-Uptime/1.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => false,
        ]);
        $start = microtime(true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
    }

    $timeMs = (int) round((microtime(true) - $start) * 1000);

    if ($err) {
        return ['status' => 'down', 'code' => $code ?: null, 'time_ms' => $timeMs, 'error' => $err];
    }
    if ($code >= 200 && $code < 400) {
        return ['status' => 'up', 'code' => $code, 'time_ms' => $timeMs, 'error' => null];
    }
    return ['status' => 'down', 'code' => $code, 'time_ms' => $timeMs, 'error' => "HTTP $code"];
}

/**
 * Replace all URLs for a monitor with the given list (url + label pairs).
 * Preserves last-status for URLs that still exist (matched by url string).
 */
function replace_monitor_urls(PDO $pdo, int $monitorId, array $urls, array $labels): void {
    // Load existing so we can preserve status for URLs that are kept
    $existing = $pdo->prepare("SELECT url, last_status, last_status_code, last_response_time_ms, last_checked_at, last_error FROM monitor_urls WHERE monitor_id = ?");
    $existing->execute([$monitorId]);
    $statusByUrl = [];
    foreach ($existing->fetchAll() as $row) {
        $statusByUrl[$row['url']] = $row;
    }

    $pdo->prepare("DELETE FROM monitor_urls WHERE monitor_id = ?")->execute([$monitorId]);

    $ins = $pdo->prepare("INSERT INTO monitor_urls (monitor_id, label, url, last_status, last_status_code, last_response_time_ms, last_checked_at, last_error, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $i = 0;
    foreach ($urls as $idx => $rawUrl) {
        $url = normalize_url($rawUrl);
        if ($url === null) continue;
        $label = trim((string)($labels[$idx] ?? ''));
        if ($label === '') $label = null;

        $prev = $statusByUrl[$url] ?? null;
        $ins->execute([
            $monitorId,
            $label,
            $url,
            $prev['last_status']            ?? 'unknown',
            $prev['last_status_code']       ?? null,
            $prev['last_response_time_ms']  ?? null,
            $prev['last_checked_at']        ?? null,
            $prev['last_error']             ?? null,
            $i++,
        ]);
    }
}

try {
    switch ($action) {

        case 'create': {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Name required');

            $urls = $_POST['urls'] ?? [];
            $labels = $_POST['labels'] ?? [];
            if (!is_array($urls)) $urls = [];
            if (!is_array($labels)) $labels = [];

            $stmt = $pdo->prepare("INSERT INTO monitors (name, url, created_by) VALUES (?, '', ?)");
            $stmt->execute([$name, $userId]);
            $monitorId = (int)$pdo->lastInsertId();

            replace_monitor_urls($pdo, $monitorId, $urls, $labels);

            echo json_encode(['success' => true, 'data' => ['id' => $monitorId]]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['monitor_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if (!$id || $name === '') throw new Exception('Invalid input');

            $urls = $_POST['urls'] ?? [];
            $labels = $_POST['labels'] ?? [];
            if (!is_array($urls)) $urls = [];
            if (!is_array($labels)) $labels = [];

            $pdo->prepare("UPDATE monitors SET name=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name, $id]);

            replace_monitor_urls($pdo, $id, $urls, $labels);

            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['monitor_id'] ?? 0);
            if (!$id) throw new Exception('Missing monitor_id');
            $pdo->prepare('DELETE FROM monitors WHERE id = ?')->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        case 'check': {
            $monitorId = (int)($_POST['monitor_id'] ?? 0);
            if (!$monitorId) throw new Exception('Missing monitor_id');

            $stmt = $pdo->prepare('SELECT id, url FROM monitor_urls WHERE monitor_id = ? ORDER BY sort_order, id');
            $stmt->execute([$monitorId]);
            $rows = $stmt->fetchAll();
            if (!$rows) {
                echo json_encode(['success' => true, 'data' => ['results' => []]]);
                break;
            }

            $upd = $pdo->prepare("
                UPDATE monitor_urls
                SET last_status = ?, last_status_code = ?, last_response_time_ms = ?, last_error = ?,
                    last_checked_at = datetime('now')
                WHERE id = ?
            ");

            $results = [];
            foreach ($rows as $row) {
                $r = check_url($row['url']);
                $upd->execute([$r['status'], $r['code'], $r['time_ms'], $r['error'], $row['id']]);
                $results[] = [
                    'id'      => (int)$row['id'],
                    'status'  => $r['status'],
                    'code'    => $r['code'],
                    'time_ms' => $r['time_ms'],
                    'error'   => $r['error'],
                ];
            }

            $pdo->prepare("UPDATE monitors SET updated_at=datetime('now') WHERE id=?")->execute([$monitorId]);

            echo json_encode(['success' => true, 'data' => ['results' => $results]]);
            break;
        }

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
