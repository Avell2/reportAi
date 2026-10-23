<?php

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$_SESSION['csrf'] ??= bin2hex(random_bytes(24));

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/actions.php';

$reportId = (string)($_GET['report'] ?? '');
$reports = [];
$report = null;
$points = [];
$requests = [];
$issues = [];

if ($dbReady) {
    $sql = 'SELECT r.*,c.symbol FROM reports r JOIN companies c ON c.longId=r.companies ORDER BY r.id DESC LIMIT 100';
    $params = [];
    require __DIR__ . '/../app/query.php';
    $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if ($reportId !== '') {
        $sql = 'SELECT r.*,c.symbol FROM reports r JOIN companies c ON c.longId=r.companies WHERE r.longId=?';
        $params = [$reportId];
        require __DIR__ . '/../app/query.php';
        $report = mysqli_fetch_assoc($result);
        if (!$report) {
            http_response_code(404);
        }
    }

    if ($report) {
        $sql = 'SELECT * FROM report_data_points WHERE reports=? ORDER BY id';
        $params = [$reportId];
        require __DIR__ . '/../app/query.php';
        $points = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $sql = 'SELECT q.*,e.method FROM report_qm_requests q JOIN report_qm_endpoints e ON e.longId=q.report_qm_endpoints WHERE q.reports=? ORDER BY q.id DESC';
        $params = [$reportId];
        require __DIR__ . '/../app/query.php';
        $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $sql = 'SELECT * FROM report_validation_issues WHERE reports=? ORDER BY id DESC LIMIT 50';
        $params = [$reportId];
        require __DIR__ . '/../app/query.php';
        $issues = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

$approved = 0;
foreach ($points as $point) {
    if ($point['status'] === 'approved') {
        $approved++;
    }
}

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReportStock • Beta</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="/">stocks<span>.</span>news</a>
        <div class="workspace">RESEARCH WORKSPACE <b>BETA</b></div>
        <nav>
            <a href="#report">◈ &nbsp; Report</a>
            <a href="#collection">↓ &nbsp; Data Collection</a>
            <a href="#evidence">☷ &nbsp; Evidence</a>
            <a href="#audit">↗ &nbsp; History</a>
        </nav>
        <div class="sidebar-bottom">
            <span class="dot"></span> Stocks.News sources only<br>
            <small>Traceable research, section by section.</small>
        </div>
    </aside>

    <main>
        <header class="topbar">
            <span>Research / <strong>ReportStock</strong></span>
            <span class="draft">Draft • review required</span>
        </header>

        <?php if ($notice !== ''): ?>
            <div role="status" class="notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif ?>

        <?php if (!$dbReady): ?>
            <div class="notice"><?= htmlspecialchars($dbMessage) ?> <a href="#setup">View setup</a></div>
        <?php endif ?>

        <section class="intro" id="report">
            <div>
                <p class="eyebrow">EQUITY RESEARCH / BETA 01</p>
                <h1><?= $report ? htmlspecialchars($report['symbol']) . ' — research report' : 'From information to evidence.' ?></h1>
                <p class="muted"><?= $report ? 'Price on ' . htmlspecialchars($report['price_date']) . ' · Cutoff ' . htmlspecialchars($report['cutoff']) . ' UTC' : 'Build a corporate report with traceable data and explicit review.' ?></p>
            </div>
            <button class="secondary" id="print">Print / save PDF</button>
        </section>

        <div class="metrics">
            <div>
                <small>EDITORIAL SECTIONS</small>
                <strong>16</strong>
                <span>Research structure</span>
            </div>
            <div>
                <small>APPROVED FACTS</small>
                <strong><?= $approved ?></strong>
                <span>of <?= count($points) ?> collected fields</span>
            </div>
            <div>
                <small>REQUESTS</small>
                <strong><?= count($requests) ?></strong>
                <span>Preserved responses and attempts</span>
            </div>
            <div>
                <small>TEXT GENERATION</small>
                <strong class="small-value">In review</strong>
                <span>Claude not yet enabled</span>
            </div>
        </div>

        <section class="panel controls">
            <div>
                <p class="eyebrow">NEW DRAFT</p>
                <h2>Define research parameters</h2>
            </div>
            <form method="post" class="create-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="create">
                <label>Symbol
                    <input required name="symbol" placeholder="e.g.: AAPL" maxlength="32" pattern="[A-Za-z0-9][A-Za-z0-9.\-:]{0,31}">
                </label>
                <label>Price date
                    <input required type="date" name="price_date" value="<?= gmdate('Y-m-d') ?>">
                </label>
                <label>Research cutoff (UTC)
                    <input required type="datetime-local" name="cutoff" value="<?= gmdate('Y-m-d\TH:i') ?>">
                </label>
                <button <?= !$dbReady ? 'disabled' : '' ?>>Create report →</button>
            </form>
        </section>

        <?php if ($reports): ?>
            <div class="report-links">
                <span>Reports</span>
                <?php foreach ($reports as $item): ?>
                    <a class="chip" href="/?report=<?= urlencode($item['longId']) ?>"><?= htmlspecialchars($item['symbol'] . ' · ' . $item['price_date'] . ' · #' . $item['id']) ?></a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div class="section-heading">
            <div>
                <p class="eyebrow">THE REPORT</p>
                <h2>Editorial view</h2>
            </div>
            <span class="muted">Missing data is explicit</span>
        </div>

        <div class="editorial">
            <?php foreach ($sectionNames as $index => $title): ?>
                <?php
                $number = $index + 1;
                $sectionPoints = [];
                foreach ($points as $point) {
                    if ($point['status'] === 'approved' && in_array($point['method'], $sectionMethods[$number], true)) {
                        $sectionPoints[] = $point;
                    }
                }
                ?>
                <article class="section-card">
                    <div class="section-title">
                        <span class="number"><?= str_pad((string)$number, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="tag <?= $sectionPoints ? 'available' : '' ?>"><?= $sectionPoints ? 'Partial data' : 'Gap' ?></span>
                    </div>
                    <h3><?= htmlspecialchars($title) ?></h3>

                    <?php if ($sectionPoints): ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Field / source</th>
                                        <th>Value</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sectionPoints as $point): ?>
                                        <tr>
                                            <td>
                                                <a href="#point-<?= htmlspecialchars($point['longId']) ?>"><?= htmlspecialchars($point['field_name']) ?></a>
                                                <small><?= htmlspecialchars($point['method']) ?></small>
                                            </td>
                                            <td class="value">
                                                <?= htmlspecialchars($point['value_json']) ?>
                                                <small><?= htmlspecialchars($point['unit'] . ' · scale ' . $point['scale_value'] . ' · ' . $point['currency']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($point['reference_date']) ?>
                                                <small><?= htmlspecialchars($point['period_label']) ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($number === 16): ?>
                        <p>
                            Editor: <?= htmlspecialchars($config['editor'] ?: 'not provided') ?>.<br>
                            Reviewer: <?= htmlspecialchars($config['reviewer'] ?: 'not provided') ?>.<br>
                            Sponsorship: <?= htmlspecialchars($config['sponsorship'] ?: 'not provided') ?>.
                        </p>
                        <p class="muted">Beta version, without editorial approval and unreleased.</p>
                    <?php else: ?>
                        <p class="muted"><?= empty($sectionMethods[$number]) ? 'No approved content for this section. Textual coverage and review requirements pending.' : 'Awaiting collection and validation of entity, dates, unit, and currency.' ?></p>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>

        <section class="panel" id="collection">
            <p class="eyebrow">01 / COLLECTION</p>
            <h2>Documented methods</h2>
            <p class="muted">Catalog extracted from OUR API tab. Spreadsheet examples are not live data. Collect overview first; use other methods only when needed.</p>
            <div class="method-grid">
                <?php foreach ($catalog as $method => $definition): ?>
                    <div class="method">
                        <strong><?= htmlspecialchars($method) ?></strong>
                        <span><?= count($definition['fields']) ?> fields · row <?= $definition['row'] ?></span>
                        <?php if ($definition['scope'] === 'symbol'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="collect">
                                <input type="hidden" name="report" value="<?= htmlspecialchars($reportId) ?>">
                                <input type="hidden" name="method" value="<?= htmlspecialchars($method) ?>">
                                <button class="secondary" <?= !$report || !$config['api_auth_confirmed'] ? 'disabled' : '' ?>>Collect / resume</button>
                            </form>
                        <?php else: ?>
                            <small>Cataloged · outside individual collection</small>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        </section>

        <section class="panel" id="evidence">
            <p class="eyebrow">02 / VALIDATION</p>
            <h2>Evidence before assertion</h2>
            <p class="muted">Check original response before approving. Dates and units are not inferred. Composite values remain JSON without automated interpretation.</p>
            <?php if (!$points): ?>
                <div class="empty">No fields collected. The beta does not use dummy values.</div>
            <?php endif ?>

            <?php foreach ($points as $point): ?>
                <details id="point-<?= htmlspecialchars($point['longId']) ?>">
                    <summary><?= htmlspecialchars($point['method'] . ' / ' . $point['field_name']) ?> <span class="tag"><?= htmlspecialchars($point['status']) ?></span></summary>
                    <p><a href="#request-<?= htmlspecialchars($point['report_qm_requests']) ?>">Open original response</a> · JSON Pointer: <code><?= htmlspecialchars($point['locator']) ?></code></p>
                    <pre><?= htmlspecialchars($point['value_json']) ?></pre>
                    <form method="post" class="review-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="review">
                        <input type="hidden" name="report" value="<?= htmlspecialchars($reportId) ?>">
                        <input type="hidden" name="point" value="<?= htmlspecialchars($point['longId']) ?>">

                        <label>Entity (symbol)
                            <input name="entity" maxlength="100" value="<?= htmlspecialchars($point['entity']) ?>">
                        </label>
                        <label>Reference date
                            <input type="date" name="reference_date" value="<?= htmlspecialchars($point['reference_date'] ?? '') ?>">
                        </label>
                        <label>Publicly available on (UTC)
                            <input type="datetime-local" name="public_at" value="<?= htmlspecialchars(substr(str_replace(' ', 'T', $point['public_at'] ?? ''), 0, 16)) ?>">
                        </label>
                        <label>Period / nature
                            <input name="period" maxlength="100" placeholder="FY, quarter, instant…" value="<?= htmlspecialchars($point['period_label']) ?>">
                        </label>
                        <label>Unit
                            <input name="unit" maxlength="80" value="<?= htmlspecialchars($point['unit']) ?>">
                        </label>
                        <label>Scale
                            <input name="scale" type="number" step="any" min="0.000001" value="<?= htmlspecialchars($point['scale_value']) ?>">
                        </label>
                        <label>Currency / N/A
                            <input name="currency" maxlength="3" value="<?= htmlspecialchars($point['currency']) ?>">
                        </label>
                        <label>Reviewer
                            <input required name="reviewer" maxlength="150" value="<?= htmlspecialchars($config['reviewer']) ?>">
                        </label>
                        <label class="wide">Context and metadata locator
                            <textarea name="context"><?= htmlspecialchars($point['context']) ?></textarea>
                        </label>
                        <label class="wide">Rejection reason / note
                            <textarea name="reason"><?= htmlspecialchars($point['absence_reason']) ?></textarea>
                        </label>
                        <label class="wide checkbox">
                            <input type="checkbox" name="evidence_checked"> Verified entity, period, dates, and units in the original source.
                        </label>
                        <div class="wide">
                            <button name="decision" value="approved">Approve fact</button>
                            <button class="secondary" name="decision" value="rejected">Reject</button>
                        </div>
                    </form>
                </details>
            <?php endforeach ?>
        </section>

        <section class="panel" id="audit">
            <p class="eyebrow">03 / AUDIT</p>
            <h2>Responses and history</h2>
            <?php if (!$requests): ?>
                <p class="muted">No calls performed in this report.</p>
            <?php endif ?>

            <?php foreach ($requests as $request): ?>
                <details id="request-<?= htmlspecialchars($request['longId']) ?>">
                    <summary><?= htmlspecialchars($request['method'] . ' · HTTP ' . $request['http_status'] . ' · ' . $request['status'] . ' · ' . $request['started_at'] . ' UTC') ?></summary>
                    <p>SHA-256: <code><?= htmlspecialchars($request['response_hash']) ?></code></p>
                    <p>Pagination: unconfirmed. <?= htmlspecialchars($request['error_message']) ?></p>
                    <pre><?= htmlspecialchars($request['parameters_json']) ?></pre>
                    <pre><?= htmlspecialchars($request['response_body']) ?></pre>
                </details>
            <?php endforeach ?>

            <?php foreach ($issues as $issue): ?>
                <p class="audit-line"><?= htmlspecialchars($issue['created_at'] . ' · ' . $issue['code'] . ' · ' . $issue['message']) ?></p>
            <?php endforeach ?>
        </section>

        <?php if (!$dbReady): ?>
            <section class="panel" id="setup">
                <h2>Activate this beta</h2>
                <p>Create a dedicated empty database, apply <code>database/schema.sql</code>, and copy <code>config/config.example.php</code> to <code>config/local.php</code>. Configure the connection and confirmed API authentication. Full instructions are in the README.</p>
                <p class="muted">The existing schema has not been migrated. Use this application in a restricted local environment.</p>
            </section>
        <?php endif ?>

        <footer>ReportStock Beta · Stocks.News / QuoteMedia · Times in UTC · Unpublished draft</footer>
    </main>
</body>
</html>
