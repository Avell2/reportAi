<?php

$encoded = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
if (file_put_contents($jobPath . '.tmp', $encoded, LOCK_EX) === false || !rename($jobPath . '.tmp', $jobPath)) {
    $error = 'Unable to save report. Please check storage folder permissions.';
}
