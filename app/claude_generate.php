<?php

// Output schema and Claude request are kept together.
$referenceSchema = [
    'type' => 'array',
    'items' => ['type' => 'string'],
];

$paragraphSchema = [
    'type' => 'object',
    'properties' => [
        'text' => ['type' => 'string'],
        'sources' => $referenceSchema,
    ],
    'required' => ['text', 'sources'],
    'additionalProperties' => false,
];

$rowSchema = [
    'type' => 'object',
    'properties' => [
        'label' => ['type' => 'string'],
        'value' => ['type' => 'string'],
        'period' => ['type' => 'string'],
        'sources' => $referenceSchema,
    ],
    'required' => ['label', 'value', 'period', 'sources'],
    'additionalProperties' => false,
];

$reportSchema = [
    'type' => 'object',
    'properties' => [
        'title' => ['type' => 'string'],
        'company' => ['type' => 'string'],
        'company_sources' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ],
        'snapshot' => [
            'type' => 'array',
            'items' => $rowSchema,
        ],
        'sections' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'enum' => array_keys($sectionNames),
                    ],
                    'paragraphs' => [
                        'type' => 'array',
                        'items' => $paragraphSchema,
                    ],
                    'bullets' => [
                        'type' => 'array',
                        'items' => $paragraphSchema,
                    ],
                    'rows' => [
                        'type' => 'array',
                        'items' => $rowSchema,
                    ],
                    'gap' => ['type' => 'string'],
                ],
                'required' => ['id', 'paragraphs', 'bullets', 'rows', 'gap'],
                'additionalProperties' => false,
            ],
        ],
    ],
    'required' => ['title', 'company', 'company_sources', 'snapshot', 'sections'],
    'additionalProperties' => false,
];

$prompt = json_decode(file_get_contents(__DIR__.'/prompt.json'), true);
if (!is_array($prompt)) {
    $error = 'Invalid prompt JSON configuration.';
    return;
}
$prompt['title']['value'] = 'AI Analysis ' . $job['symbol'] . ' report';
$systemPrompt = json_encode($prompt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$requestBody = [
    'model' => $config['claude_model'],
    'max_tokens' => (int)$config['claude_max_tokens'],
    'system' => $systemPrompt,
    'messages' => [
        [
            'role' => 'user',
            'content' => json_encode(
                [
                    'symbol' => $job['symbol'],
                    'collected_at' => $job['created_at'],
                    'sections' => $sectionNames,
                    'sources' => $job['sources'],
                ],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ),
        ],
    ],
    'tools' => [
        [
            'name' => 'render_report',
            'description' => 'Deliver the structured final report with references to the provided sources.',
            'input_schema' => $reportSchema,
        ],
    ],
    'tool_choice' => [
        'type' => 'tool',
        'name' => 'render_report',
    ],
];

$headers = [
    'Content-Type: application/json',
    'x-api-key: '.$config['claude_api_key'],
    'anthropic-version: '.$config['claude_version'],
];

if ($config['claude_workspace'] !== '') {
    $headers[] = 'anthropic-workspace-id: '.$config['claude_workspace'];
}

$handle = curl_init('https://api.anthropic.com/v1/messages');

curl_setopt_array($handle, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_INVALID_UTF8_SUBSTITUTE),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 150,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
]);

$raw = curl_exec($handle);
$http = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
$transport = curl_errno($handle);
curl_close($handle);

$response = is_string($raw) ? json_decode($raw, true) : null;

$job['attempts'][] = [
    'model' => $config['claude_model'],
    'http' => $http,
    'transport_code' => $transport,
    'time' => gmdate('c'),
    'raw' => $raw,
    'usage' => $response['usage'] ?? null,
    'prompt_sha256' => hash('sha256', $systemPrompt),
];

$document = null;

if ($transport !== 0 || $http < 200 || $http >= 300 || !is_array($response)) {
    $error = 'Unable to complete generation with Claude (HTTP '.$http.'). Check API key, model, and usage limit; you may try again.';
} elseif (($response['stop_reason'] ?? '') !== 'tool_use') {
    $error = 'Claude did not complete the structured document. Please try again or adjust output token limit in configuration.';
} else {
    foreach ($response['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === 'render_report') {
            $document = $block['input'] ?? null;
            break;
        }
    }
    if (is_array($document)) {
        $document['title'] = 'AI Analysis ' . $job['symbol'] . ' report';
    }
    require __DIR__.'/prepare_document.php';
    if (!$document) { $error = 'Claude did not return displayable report content.'; }
}

if ($error === '') {
    $job['document'] = $document;
    $job['status'] = 'complete';
    $job['finished_at'] = gmdate('c');
}
