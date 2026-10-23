<?php

$decision = (string)($_POST['decision'] ?? '');
$reference = (string)($_POST['reference_date'] ?? '');
$publicAt = str_replace('T', ' ', (string)($_POST['public_at'] ?? '')) . ':00';
$entity = trim((string)($_POST['entity'] ?? ''));
$context = trim((string)($_POST['context'] ?? ''));
$period = trim((string)($_POST['period'] ?? ''));
$unit = trim((string)($_POST['unit'] ?? ''));
$currency = strtoupper(trim((string)($_POST['currency'] ?? '')));
$scale = filter_var($_POST['scale'] ?? '', FILTER_VALIDATE_FLOAT);
$reason = trim((string)($_POST['reason'] ?? ''));
$reviewer = trim((string)($_POST['reviewer'] ?? ''));
$refDate = DateTimeImmutable::createFromFormat('!Y-m-d', $reference);
$pubDate = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $publicAt);

if (!in_array($decision, ['approved', 'rejected'], true) || $reviewer === '' || strlen($reviewer) > 150) {
    $error = 'Provide decision and reviewer (up to 150 characters).';
} elseif ($decision === 'rejected' && $reason === '') {
    $error = 'Provide rejection reason.';
} elseif ($decision === 'approved') {
    if (
        $point['value_json'] === 'null'
        || !$refDate
        || $refDate->format('Y-m-d') !== $reference
        || !$pubDate
        || $pubDate->format('Y-m-d H:i:s') !== $publicAt
        || $publicAt > $report['cutoff']
        || $reference > substr($report['cutoff'], 0, 10)
        || $entity !== $report['symbol']
        || $context === ''
        || $period === ''
        || $unit === ''
        || !$scale
        || $scale <= 0
        || $scale > 1000000000000
        || !preg_match('/^(?:[A-Z]{3}|N\/A)$/D', $currency)
    ) {
        $error = 'Approval requires non-null value, entity matching symbol, context, period, unit, positive scale, currency (or N/A), date, and public availability up to cutoff.';
    }

    // Recent quote cannot replace selected historical price.
    $priceFields = ['last', 'close', 'open', 'high', 'low', 'prevClose', 'prevclose', 'bid', 'ask', 'vwap'];
    if (in_array($point['field_name'], $priceFields, true) && $reference !== $report['price_date']) {
        $error = 'Price can only be approved for the date chosen in the report. Consult fullHistory for past dates.';
    }

    if (!isset($_POST['evidence_checked'])) {
        $error = 'Confirm metadata verification in the original response.';
    }
}

if (strlen($entity) > 100 || strlen($period) > 100 || strlen($unit) > 80) {
    $error = 'Entity/period limited to 100 characters; unit to 80.';
}
