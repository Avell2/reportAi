<?php
// Replaceable adapter. Methods and fields come exclusively from the spreadsheet.

$multi = curl_multi_init();
$handles = [];
$job['sources'] = [];
$job['collection'] = [];

foreach ($config['data_methods'] as $method) {
    if (!isset($catalog[$method]) || $catalog[$method]['scope'] !== 'symbol') {
        $error = 'Invalid method in data source configuration.';
        break;
    }

    $url = rtrim($config['data_base_url'], '/') . '/' . rawurlencode($job['symbol']) . '/' . $method;

    if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
        $error = 'Data source must use HTTPS.';
        break;
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    foreach ($config['api_headers'] as $header) {
        if (!is_string($header) || str_contains($header, "\n") || str_contains($header, "\r")) {
            $error = 'Invalid data source header.';
            break 2;
        }
        $headers[] = $header;
    }

    $requestBody = [
        'fields' => implode(',', $catalog[$method]['fields']),
    ];

    $handle = curl_init($url);

    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 35,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);

    curl_multi_add_handle($multi, $handle);

    $handles[$method] = [
        'handle' => $handle,
        'parameters' => $requestBody,
    ];
}

if ($error === '') {
    do {
        $multiStatus = curl_multi_exec($multi, $running);
        if ($running && $multiStatus === CURLM_OK) {
            curl_multi_select($multi, 0.5);
        }
    } while ($running && $multiStatus === CURLM_OK);
}

foreach ($handles as $method => $entry) {
    $handle = $entry['handle'];
    $raw = curl_multi_getcontent($handle);
    $http = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $transport = curl_errno($handle);
    $payload = is_string($raw) ? json_decode($raw, true) : null;
    $usable = $transport === 0
        && $http >= 200
        && $http < 300
        && is_array($payload)
        && $payload !== []
        && !isset($payload['error'])
        && !isset($payload['errors'])
        && strlen($raw) <= 100000;

    $sourceId = 'S' . (count($job['collection']) + 1);

    $job['collection'][$sourceId] = [
        'method' => $method,
        'parameters' => $entry['parameters'],
        'http' => $http,
        'transport_code' => $transport,
        'received_at' => gmdate('c'),
        'usable' => $usable,
        'raw' => $raw,
        'reason' => $usable ? '' : (is_string($raw) && strlen($raw) > 100000 ? 'Response exceeds beta context limit' : 'Empty response, error, or unconfirmed format'),
    ];

    if ($usable) {
        $job['sources'][$sourceId] = [
            'method' => $method,
            'received_at' => gmdate('c'),
            'data' => $payload,
        ];
    }
    
    curl_multi_remove_handle($multi, $handle);
    curl_close($handle);
}
//print_r($job);die;
curl_multi_close($multi);

if ($error === '' && !$job['sources']) {
    $error = 'The source did not return usable data for this symbol. Check symbol and access configuration.';
}
