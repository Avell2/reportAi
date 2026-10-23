<?php
// Shared procedural interface: $sql, $params -> $result.

$result = mysqli_execute_query($db, $sql, $params ?? []);
$params = [];

if ($result === false) {
    $databaseErrorCode = mysqli_errno($db);
    mysqli_rollback($db);
    error_log('ReportStock database failure; code=' . $databaseErrorCode);
    http_response_code(500);
    exit('Unable to save operation. Check schema and server log.');
}
