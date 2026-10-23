<?php
// Procedural PHP: no classes, custom functions, or try/catch.

date_default_timezone_set('UTC');

$config = require __DIR__ . '/../config/config.example.php';
if (is_file(__DIR__ . '/../config/local.php')) {
    $config = array_replace($config, require __DIR__ . '/../config/local.php');
}

$catalog = json_decode(file_get_contents(__DIR__ . '/../config/endpoints.json'), true);

$sectionNames = [
    'Identification and Cover',
    'Company Overview',
    'Investment Highlights',
    'Investment Thesis',
    'Technology and Products',
    'Commercialization and Catalysts',
    'Industry and Competition',
    'Financial Snapshot',
    'Management and Board',
    'Ownership Structure',
    'Intellectual Property',
    'Financial Overview',
    'Financial Statements',
    'Stock Performance',
    'Risks',
    'Disclosures and Review',
];

$sectionMethods = [
    1 => ['overview', 'SymbolsByExchange'],
    2 => [],
    3 => [],
    4 => [],
    5 => [],
    6 => ['earnings'],
    7 => [],
    8 => ['fundamentals', 'dividends'],
    9 => ['executives'],
    10 => [],
    11 => [],
    12 => ['financial'],
    13 => ['financial'],
    14 => ['priceData', 'fullHistory', 'intraDay', 'charts', 'getIntervals'],
    15 => [],
    16 => [],
];

$db = false;
$dbReady = false;
$dbMessage = 'Configure MySQL to start data collection. No sample data is presented as real.';

if ($config['db_user'] !== '' && extension_loaded('mysqli')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name'], (int)$config['db_port']);
    if ($db) {
        mysqli_set_charset($db, 'utf8mb4');
        $check = mysqli_query($db, "SELECT version FROM beta_schema WHERE version = 1");
        $dbReady = $check && mysqli_num_rows($check) === 1;
        $dbMessage = $dbReady ? '' : 'Database connected, but the beta schema was not installed.';
    } else {
        $dbMessage = 'Unable to connect to MySQL with local configuration.';
    }
}
