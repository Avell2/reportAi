<?php

require __DIR__ . '/candidates.php';

$found = count($candidates);

foreach ($candidates as $candidate) {
    $pointId = bin2hex(random_bytes(16));
    $sql = 'INSERT INTO report_data_points (longId,reports,report_qm_requests,method,field_name,locator,value_json,context,absence_reason) VALUES (?,?,?,?,?,?,?,?,?)';
    $params = [
        $pointId,
        $reportId,
        $requestId,
        $method,
        $candidate['field'],
        $candidate['path'],
        json_encode($candidate['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        '',
        $candidate['value'] === null ? 'API returned null' : '',
    ];
    require __DIR__ . '/query.php';

    $sql = 'INSERT INTO report_data_evidence (longId,report_data_points,report_qm_requests,locator) VALUES (?,?,?,?)';
    $params = [bin2hex(random_bytes(16)), $pointId, $requestId, $candidate['path']];
    require __DIR__ . '/query.php';
}

if (!$found) {
    $sql = 'INSERT INTO report_validation_issues (longId,reports,code,message,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())';
    $params = [bin2hex(random_bytes(16)), $reportId, 'unmapped_response', 'No exact key located. Review provider contract and original response.'];
    require __DIR__ . '/query.php';
}
