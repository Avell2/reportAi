<?php
// Copy to local.php. No credentials are sent to the browser.

return [
    'data_base_url' => 'https://api.stocks.news/quotes/realtime',
    'api_auth_confirmed' => false,
    'api_headers' => [], // Provider contract headers; do not assume format.
    'data_methods' => [
        'overview',
        'fundamentals',
        'financial',
        'earnings',
        'executives',
        'dividends',
        'fullHistory',
    ],
    'claude_api_key' => getenv('ANTHROPIC_API_KEY') ?: '',
    'claude_model' => getenv('ANTHROPIC_MODEL') ?: '', // Model enabled on your account.
    'claude_version' => '2023-06-01',
    'claude_workspace' => '', // If required by your account key.
    'claude_max_tokens' => 14000,
    'editor' => '',
    'reviewer' => '',
    'sponsorship' => '',
];
