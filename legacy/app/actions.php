<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('Session expired. Please refresh the page.');
}

if (!$dbReady) {
    http_response_code(503);
    exit('Configure database before performing operations.');
}

$action = (string)($_POST['action'] ?? '');
$error = '';
$reportId = (string)($_POST['report'] ?? '');

if ($action === 'create') {
    $symbol = strtoupper(trim((string)($_POST['symbol'] ?? '')));
    $priceDate = (string)($_POST['price_date'] ?? '');
    $cutoffInput = (string)($_POST['cutoff'] ?? '');
    $cutoff = str_replace('T', ' ', $cutoffInput) . ':00';
    $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $priceDate);
    $validCutoff = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $cutoff);

    if (
        !preg_match('/^[A-Z0-9][A-Z0-9.\-:]{0,31}$/D', $symbol)
        || !$validDate
        || $validDate->format('Y-m-d') !== $priceDate
        || !$validCutoff
        || $validCutoff->format('Y-m-d H:i:s') !== $cutoff
        || $priceDate > substr($cutoff, 0, 10)
        || $cutoff > gmdate('Y-m-d H:i:s')
    ) {
        $error = 'Provide a valid symbol, price date up to the cutoff, and non-future cutoff (UTC).';
    } else {
        mysqli_begin_transaction($db);

        $sql = 'INSERT INTO companies (longId,symbol) VALUES (?,?) ON DUPLICATE KEY UPDATE symbol=VALUES(symbol)';
        $params = [bin2hex(random_bytes(16)), $symbol];
        require __DIR__ . '/query.php';

        $sql = 'SELECT longId FROM companies WHERE symbol=?';
        $params = [$symbol];
        require __DIR__ . '/query.php';
        $company = mysqli_fetch_assoc($result);

        $reportId = bin2hex(random_bytes(16));
        $sql = 'INSERT INTO reports (longId,companies,price_date,cutoff,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())';
        $params = [$reportId, $company['longId'], $priceDate, $cutoff];
        require __DIR__ . '/query.php';

        foreach ($sectionNames as $number => $title) {
            $sql = 'INSERT INTO report_sections (longId,reports,section_number) VALUES (?,?,?)';
            $params = [bin2hex(random_bytes(16)), $reportId, $number + 1];
            require __DIR__ . '/query.php';
        }

        mysqli_commit($db);
        $_SESSION['notice'] = 'Draft created. Choose methods to collect data.';
    }
} elseif (in_array($action, ['collect', 'review'], true)) {
    $sql = 'SELECT r.*,c.symbol FROM reports r JOIN companies c ON c.longId=r.companies WHERE r.longId=?';
    $params = [$reportId];
    require __DIR__ . '/query.php';
    $report = mysqli_fetch_assoc($result);

    if (!$report) {
        http_response_code(404);
        exit('Report not found.');
    }

    if ($action === 'collect') {
        $method = (string)($_POST['method'] ?? '');
        if (!isset($catalog[$method]) || $catalog[$method]['scope'] !== 'symbol') {
            $error = 'Method outside the scope of individual beta collection.';
        } elseif (!$config['api_auth_confirmed'] || !extension_loaded('curl')) {
            $error = 'Confirm authentication in config/local.php and enable cURL. No calls were made.';
        } else {
            require __DIR__ . '/collect.php';
        }
    } else {
        require __DIR__ . '/review.php';
    }
} else {
    $error = 'Invalid operation.';
}

if ($error !== '') {
    $_SESSION['notice'] = $error;
}

header('Location: /?report=' . rawurlencode($reportId), true, 303);
exit;
