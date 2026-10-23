<?php

require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$error = '';
$job = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expired. Please refresh the page.']);
    exit;
}

$symbol = strtoupper(trim((string)($_POST['symbol'] ?? '')));

if (!preg_match('/^[A-Z0-9][A-Z0-9.\-:]{0,31}$/D', $symbol)) {
    http_response_code(422);
    echo json_encode(['error' => 'Please provide a valid symbol.']);
    exit;
}

if (!$ready) {
    http_response_code(503);
    echo json_encode(['error' => 'Configure data source access and Claude API key/model on the server to generate reports.']);
    exit;
}

session_write_close();
set_time_limit(240);

if (!is_dir($storage) && !mkdir($storage, 0700, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to prepare storage directory.']);
    exit;
}

// Each valid submission receives a new server-generated identifier.
do {
    $jobId = bin2hex(random_bytes(16));
    $jobPath = $storage . '/' . $jobId . '.json';
} while (is_file($jobPath));

$job = [
    'id' => $jobId,
    'symbol' => $symbol,
    'created_at' => gmdate('c'),
    'status' => 'collecting',
    'attempts' => [],
    'sources' => [],
    'disclosures' => [
        'editor' => $config['editor'],
        'reviewer' => $config['reviewer'],
        'sponsorship' => $config['sponsorship'],
    ],
];

require __DIR__ . '/../app/data_collect.php';

$job['status'] = $error === '' ? 'generating' : 'failed';
require __DIR__ . '/../app/save_job.php';

if ($error === '') {
    require __DIR__ . '/../app/claude_generate.php';

}

if ($error !== '') {
    $job['status'] = 'failed';
    $job['last_error'] = $error;
}

require __DIR__ . '/../app/save_job.php';

if ($error !== '') {
    http_response_code(502);
    echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['url' => '/?report=' . $jobId]);
}
