<?php
// Procedural application, without framework, custom classes or functions.

// Sessions and HTTP headers apply only to web requests.
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();

    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');

    $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}

date_default_timezone_set('UTC');

$config = require __DIR__ . '/../config/config.example.php';
if (is_file(__DIR__ . '/../config/local.php')) {
    $config = array_replace($config, require __DIR__ . '/../config/local.php');
}

$catalog = json_decode(file_get_contents(__DIR__ . '/../config/endpoints.json'), true);

$sectionNames = [
    2 => 'Company Overview',
    3 => 'Investment Highlights',
    4 => 'Investment Thesis',
    5 => 'Technology and Products',
    6 => 'Commercialization and Catalysts',
    7 => 'Industry and Competition',
    8 => 'Financial Snapshot',
    9 => 'Management and Board',
    10 => 'Ownership Structure',
    11 => 'Intellectual Property',
    12 => 'Financial Analysis',
    13 => 'Financial Statements',
    14 => 'Stock Performance',
    15 => 'Risks and Key Considerations',
];

$storage = __DIR__ . '/../var/reports';
$ready = extension_loaded('curl')
    && $config['api_auth_confirmed']
    && $config['claude_api_key'] !== ''
    && $config['claude_model'] !== '';
