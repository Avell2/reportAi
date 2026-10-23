<?php
require __DIR__ . '/../app/bootstrap.php';

// Local development workspace: list the reports saved on disk, across sessions.
$reports = [];
foreach (glob($storage . '/*.json') ?: [] as $path) {
    $id = basename($path, '.json');
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) { continue; }
    $saved = json_decode(file_get_contents($path), true);
    if (!is_array($saved) || !is_string($saved['symbol'] ?? null)) { continue; }
    $reports[$id] = [
        'id' => $id,
        'symbol' => $saved['symbol'],
        'created_at' => $saved['created_at'] ?? '',
        'status' => $saved['status'] ?? 'failed',
        'available' => is_array($saved['document'] ?? null),
    ];
    foreach ($saved['attempts'] ?? [] as $attempt) {
        $response = json_decode($attempt['raw'] ?? '', true);
        foreach ($response['content'] ?? [] as $block) {
            if (($block['name'] ?? '') === 'render_report' && is_array($block['input']['sections'] ?? null)) {
                $reports[$id]['available'] = true;
            }
        }
    }
}
$dates = array_column($reports, 'created_at');
if ($reports) { array_multisort($dates, SORT_DESC, SORT_STRING, $reports); }

$jobId = (string)($_GET['report'] ?? '');
$job = null;
$document = null;
if (preg_match('/^[a-f0-9]{32}$/D', $jobId) && is_file($storage . '/' . $jobId . '.json')) {
    $job = json_decode(file_get_contents($storage . '/' . $jobId . '.json'), true);
    $document = $job['document'] ?? null;
    if (!is_array($document)) {
        foreach (array_reverse($job['attempts'] ?? []) as $attempt) {
            $response = json_decode($attempt['raw'] ?? '', true);
            foreach ($response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === 'render_report' && is_array($block['input'] ?? null)) {
                    $document = $block['input'];
                    break 2;
                }
            }
        }
    }
    require __DIR__ . '/../app/prepare_document.php';
}
$notFound = $jobId !== '' && !$document;
if ($notFound) { http_response_code(404); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $document ? htmlspecialchars('AI Analysis ' . $job['symbol'] . ' report') : 'Reports' ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<?php if (!$document): ?>
    <main class="simple-index">
        <h1>Reports</h1>
        <form id="generate-form" action="/generate.php" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <label for="symbol">Symbol</label>
            <div class="symbol-field">
                <input id="symbol" name="symbol" required maxlength="32" placeholder="AAPL" autocomplete="off" autocapitalize="characters" spellcheck="false" pattern="[A-Za-z0-9][A-Za-z0-9.\-:]{0,31}">
                <button type="submit">Generate report</button>
            </div>
        </form>
        <p id="progress" hidden role="status">Generating report…</p>
        <p id="error" class="error" hidden role="alert"></p>
        <?php if ($notFound): ?><p class="error">Report unavailable.</p><?php endif ?>
        <h2>Saved reports</h2>
        <?php if (!$reports): ?><p>No reports yet.</p><?php else: ?>
        <div class="table-wrap"><table class="saved-reports">
            <thead><tr><th>Report</th><th>Created (UTC)</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($reports as $item): ?>
                <tr>
                    <td><?php if ($item['available']): ?><a href="/?report=<?= htmlspecialchars($item['id']) ?>">AI Analysis <?= htmlspecialchars($item['symbol']) ?> report</a><?php else: ?><?= htmlspecialchars($item['symbol']) ?><?php endif ?></td>
                    <td><?= htmlspecialchars(str_replace(['T', '+00:00'], [' ', ''], $item['created_at'])) ?></td>
                    <td><?= htmlspecialchars($item['available'] ? 'Saved' : $item['status']) ?></td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
        <?php endif ?>
    </main>
<?php else: ?>
        <div class="report-toolbar">
            <a href="/">← New report</a>
            <span><?= htmlspecialchars($job['symbol']) ?> · Generated on <?= htmlspecialchars(gmdate('d/m/Y', strtotime($job['created_at']))) ?></span>
            <button id="print" type="button">Save to PDF / Print</button>
        </div>
        <main class="report-paper">
            <?php require __DIR__ . '/render_report.php'; ?>
            <?php require __DIR__ . '/../app/debug_api_returns.php'; ?>
        </main>
<?php endif ?>
</body>
</html>
