<?php

$body = ['fields' => implode(',', $catalog[$method]['fields'])];
// The spreadsheet also exemplifies charts with fields only; no interval is assumed.
$bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
$requestHash = hash('sha256', $reportId . '|' . $method . '|' . $bodyJson);

$sql = 'SELECT GET_LOCK(?,0) AS acquired';
$params = [$requestHash];
require __DIR__ . '/query.php';
$lock = mysqli_fetch_assoc($result);

if ((int)$lock['acquired'] !== 1) {
    $error = 'Collection is already in progress. Please wait and refresh the page.';
    return;
}

// Lock acquired: previous running attempts were interrupted.
$sql = "UPDATE report_qm_requests SET status='interrupted',error_message='Previous process interrupted; retry allowed',finished_at=UTC_TIMESTAMP() WHERE reports=? AND request_hash=? AND status='running'";
$params = [$reportId, $requestHash];
require __DIR__ . '/query.php';

$sql = "SELECT longId FROM report_qm_requests WHERE reports=? AND request_hash=? AND status='normalized' LIMIT 1";
$params = [$reportId, $requestHash];
require __DIR__ . '/query.php';
$previous = mysqli_fetch_assoc($result);

if ($previous) {
    $_SESSION['notice'] = 'Response already collected and normalized. Reused without new call.';
} else {
    // Resumes normalization of saved response before attempting a new call.
    $sql = "SELECT * FROM report_qm_requests WHERE reports=? AND request_hash=? AND status='received' ORDER BY id DESC LIMIT 1";
    $params = [$reportId, $requestHash];
    require __DIR__ . '/query.php';
    $saved = mysqli_fetch_assoc($result);

    if ($saved) {
        $requestId = $saved['longId'];
        $raw = $saved['response_body'];
    } else {
        $sql = 'INSERT INTO report_qm_endpoints (longId,method,fields_json,source_row) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE method=VALUES(method)';
        $params = [bin2hex(random_bytes(16)), $method, json_encode($catalog[$method]['fields']), $catalog[$method]['row']];
        require __DIR__ . '/query.php';

        $sql = 'SELECT longId FROM report_qm_endpoints WHERE method=?';
        $params = [$method];
        require __DIR__ . '/query.php';
        $endpoint = mysqli_fetch_assoc($result);

        $requestId = bin2hex(random_bytes(16));
        $sql = "INSERT INTO report_qm_requests (longId,reports,report_qm_endpoints,request_hash,parameters_json,response_body,response_hash,status,error_message,started_at) VALUES (?,?,?,?,?,'','','running','',UTC_TIMESTAMP())";
        $params = [$requestId, $reportId, $endpoint['longId'], $requestHash, $bodyJson];
        require __DIR__ . '/query.php';

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        foreach ($config['api_headers'] as $header) {
            if (is_string($header) && strpos($header, ':') !== false && !str_contains($header, "\r") && !str_contains($header, "\n")) {
                $headers[] = $header;
            }
        }

        $curl = curl_init('https://api.stocks.news/quotes/realtime/' . rawurlencode($report['symbol']) . '/' . $method);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $bodyJson,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $response = curl_exec($curl);
        $http = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlCode = curl_errno($curl);
        curl_close($curl);

        $raw = $response === false ? '' : $response;
        $payload = json_decode($raw, true, 512);
        $ok = $curlCode === 0 && $http >= 200 && $http < 300 && json_last_error() === JSON_ERROR_NONE && is_array($payload);

        $sql = 'UPDATE report_qm_requests SET response_body=?,response_hash=?,http_status=?,status=?,error_message=?,finished_at=UTC_TIMESTAMP() WHERE longId=?';
        $params = [$raw, hash('sha256', $raw), $http, $ok ? 'received' : 'failed', $ok ? '' : 'HTTP/JSON/transport failure; cURL=' . $curlCode, $requestId];
        require __DIR__ . '/query.php';

        if (!$ok) {
            $error = 'Collection failed (HTTP ' . $http . '). Attempt was preserved; you may try again.';
        }
    }

    if ($error === '') {
        $payload = json_decode($raw, true);
        mysqli_begin_transaction($db);
        require __DIR__ . '/normalize.php';

        $sql = "UPDATE report_qm_requests SET status='normalized' WHERE longId=?";
        $params = [$requestId];
        require __DIR__ . '/query.php';

        mysqli_commit($db);
        $_SESSION['notice'] = 'Response preserved; ' . $found . ' fields awaiting review. Pagination and coverage not yet confirmed.';
    }
}

$sql = 'SELECT RELEASE_LOCK(?)';
$params = [$requestHash];
require __DIR__ . '/query.php';
