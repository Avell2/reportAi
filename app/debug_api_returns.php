<?php
// Data source returns only. Does not include configuration, headers, or Claude calls.
$apiDebug = ['symbol' => $job['symbol'], 'responses' => []];
foreach ($job['collection'] ?? [] as $sourceId => $entry) {
    $apiDebug['responses'][$sourceId] = [
        'method' => $entry['method'] ?? '',
        'http' => $entry['http'] ?? null,
        'received_at' => $entry['received_at'] ?? '',
        'usable' => $entry['usable'] ?? false,
        'data' => is_string($entry['raw'] ?? null) ? json_decode($entry['raw'], true) : null,
        'raw' => $entry['raw'] ?? null,
    ];
}
?>
<script type="application/json" id="api-returns"><?= json_encode($apiDebug, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
