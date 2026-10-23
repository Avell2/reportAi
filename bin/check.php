<?php

if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

echo 'PHP: ' . PHP_VERSION . PHP_EOL;
echo 'cURL: ' . (extension_loaded('curl') ? 'OK' : 'MISSING') . PHP_EOL;
echo 'Data source: ' . ($config['api_auth_confirmed'] ? 'configured' : 'pending') . PHP_EOL;
echo 'Claude key: ' . ($config['claude_api_key'] !== '' ? 'configured' : 'pending') . PHP_EOL;
echo 'Claude model: ' . ($config['claude_model'] !== '' ? 'configured' : 'pending') . PHP_EOL;
echo 'Generation: ' . ($ready ? 'ready for live testing' : 'awaiting configuration') . PHP_EOL;

exit($ready ? 0 : 1);
