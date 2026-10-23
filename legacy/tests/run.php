<?php
// Synthetic fixtures EXCLUSIVELY for testing; never imported into the application.

$catalog = json_decode(file_get_contents(__DIR__ . '/../config/endpoints.json'), true);
$method = 'priceData';
$payload = ['data' => ['a/b~c' => ['last' => 0, 'Last' => 999, 'close' => null, 'unexpected' => 123]]];
require __DIR__ . '/../app/candidates.php';

if (count($candidates) !== 2 || $candidates[0]['value'] !== 0 || $candidates[0]['path'] !== '/data/a~1b~0c/last' || $candidates[1]['value'] !== null) {
    fwrite(STDERR, "FAIL: zero/null/aliases/JSON Pointer\n");
    exit(1);
}

$method = 'financial';
$payload = ['periods' => [['netIncome' => 10], ['netIncome' => 20, 'EBITDA' => 90, 'ebtida' => 5]]];
require __DIR__ . '/../app/candidates.php';

if (count($candidates) !== 3 || $candidates[0]['path'] === $candidates[2]['path']) {
    fwrite(STDERR, "FAIL: periods/field case\n");
    exit(1);
}

$report = ['symbol' => 'TEST', 'price_date' => '2026-10-20', 'cutoff' => '2026-10-21 23:59:59'];
$point = ['field_name' => 'last', 'value_json' => '0'];
$base = [
    'decision' => 'approved',
    'reference_date' => '2026-10-20',
    'public_at' => '2026-10-20T20:00',
    'entity' => 'TEST',
    'context' => 'Test fixture /data',
    'period' => 'instant',
    'unit' => 'currency per share',
    'currency' => 'USD',
    'scale' => '1',
    'reviewer' => 'Test',
    'evidence_checked' => 'on',
];

$cases = [
    ['name' => 'valid zero', 'changes' => [], 'valid' => true],
    ['name' => 'cutoff violation', 'changes' => ['public_at' => '2026-10-22T00:00'], 'valid' => false],
    ['name' => 'historical price mismatch', 'changes' => ['reference_date' => '2026-10-19'], 'valid' => false],
    ['name' => 'invalid calendar date', 'changes' => ['reference_date' => '2026-02-31'], 'valid' => false],
    ['name' => 'entity mismatch', 'changes' => ['entity' => 'OTHER'], 'valid' => false],
    ['name' => 'missing unit', 'changes' => ['unit' => ''], 'valid' => false],
    ['name' => 'negative scale', 'changes' => ['scale' => '-1'], 'valid' => false],
    ['name' => 'missing rejection reason', 'changes' => ['decision' => 'rejected'], 'valid' => false],
    ['name' => 'explicit rejection', 'changes' => ['decision' => 'rejected', 'reason' => 'Unconfirmed unit'], 'valid' => true],
];

foreach ($cases as $case) {
    $_POST = array_replace($base, $case['changes']);
    $error = '';
    require __DIR__ . '/../app/validate_review.php';
    if (($error === '') !== $case['valid']) {
        fwrite(STDERR, 'FAIL: ' . $case['name'] . "\n");
        exit(1);
    }
}

$point['value_json'] = 'null';
$_POST = $base;
$error = '';
require __DIR__ . '/../app/validate_review.php';
if ($error === '') {
    fwrite(STDERR, "FAIL: null approval\n");
    exit(1);
}

$point['value_json'] = '1';
$_POST = $base;
unset($_POST['evidence_checked']);
$error = '';
require __DIR__ . '/../app/validate_review.php';
if ($error === '') {
    fwrite(STDERR, "FAIL: unchecked evidence\n");
    exit(1);
}

echo "OK: 13 extraction and review checks. No external calls or database writes.\n";
