<?php

$pointId = (string)($_POST['point'] ?? '');
$sql = 'SELECT * FROM report_data_points WHERE longId=? AND reports=?';
$params = [$pointId, $reportId];
require __DIR__ . '/query.php';
$point = mysqli_fetch_assoc($result);

if (!$point) {
    $error = 'Data point not found.';
    return;
}

require __DIR__ . '/validate_review.php';

if ($error !== '') {
    $sql = 'INSERT INTO report_validation_issues (longId,reports,report_data_points,code,message,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())';
    $params = [bin2hex(random_bytes(16)), $reportId, $pointId, 'review_blocked', $error];
    require __DIR__ . '/query.php';
    return;
}

mysqli_begin_transaction($db);

$sql = 'UPDATE report_data_points SET status=?,entity=?,context=?,period_label=?,reference_date=?,public_at=?,unit=?,scale_value=?,currency=?,absence_reason=?,reviewer=?,reviewed_at=UTC_TIMESTAMP() WHERE longId=? AND reports=?';
$params = [
    $decision,
    $entity,
    $context,
    $period,
    $decision === 'approved' ? $reference : null,
    $decision === 'approved' ? $publicAt : null,
    $unit,
    $scale ?: 1,
    $currency,
    $reason,
    $reviewer,
    $pointId,
    $reportId,
];
require __DIR__ . '/query.php';

$sql = 'INSERT INTO report_validation_issues (longId,reports,report_data_points,code,message,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())';
$params = [
    bin2hex(random_bytes(16)),
    $reportId,
    $pointId,
    'review_' . $decision,
    json_encode([
        'reviewer' => $reviewer,
        'reference_date' => $reference,
        'public_at' => $publicAt,
        'unit' => $unit,
        'scale' => $scale,
        'currency' => $currency,
        'context' => $context,
        'period' => $period,
        'reason' => $reason,
    ], JSON_UNESCAPED_UNICODE),
];
require __DIR__ . '/query.php';

$sql = 'DELETE FROM report_section_data_points WHERE report_data_points=?';
$params = [$pointId];
require __DIR__ . '/query.php';

if ($decision === 'approved') {
    foreach ($sectionMethods as $number => $methods) {
        if (!in_array($point['method'], $methods, true)) {
            continue;
        }
        $sql = 'SELECT longId FROM report_sections WHERE reports=? AND section_number=?';
        $params = [$reportId, $number];
        require __DIR__ . '/query.php';
        $section = mysqli_fetch_assoc($result);

        $sql = 'INSERT INTO report_section_data_points (longId,report_sections,report_data_points) VALUES (?,?,?)';
        $params = [bin2hex(random_bytes(16)), $section['longId'], $pointId];
        require __DIR__ . '/query.php';
    }
}

$sql = "UPDATE report_sections s SET status=IF(EXISTS(SELECT 1 FROM report_section_data_points p WHERE p.report_sections=s.longId),'partial','gap') WHERE s.reports=?";
$params = [$reportId];
require __DIR__ . '/query.php';

mysqli_commit($db);
$_SESSION['notice'] = 'Review recorded. The report remains a beta draft.';
