<?php
// Prepare available content for display, without approving its factual accuracy.
$inputDocument = $document ?? null;
$document = null;
if (!is_array($inputDocument)) { return; }
$prepared = [
    'title' => 'AI Analysis ' . $job['symbol'] . ' report',
    'company' => is_string($inputDocument['company'] ?? null) && trim($inputDocument['company']) !== '' ? $inputDocument['company'] : $job['symbol'],
    'company_sources' => [], 'snapshot' => [], 'sections' => [],
];
$sectionInputs = [];
foreach (is_array($inputDocument['sections'] ?? null) ? $inputDocument['sections'] : [] as $section) {
    if (!is_array($section)) { continue; }
    $number = filter_var($section['id'] ?? null, FILTER_VALIDATE_INT);
    if ($number !== false && isset($sectionNames[$number])) { $sectionInputs[$number] = $section; }
}
$hasContent = false;
foreach ([0 => 'Snapshot'] + $sectionNames as $number => $heading) {
    $input = $number === 0 ? ['rows' => $inputDocument['snapshot'] ?? []] : ($sectionInputs[$number] ?? []);
    $clean = ['id' => $number, 'paragraphs' => [], 'bullets' => [], 'rows' => [], 'gap' => is_string($input['gap'] ?? null) ? $input['gap'] : ''];
    foreach (['paragraphs', 'bullets', 'rows'] as $group) {
        foreach (is_array($input[$group] ?? null) ? $input[$group] : [] as $item) {
            if (!is_array($item)) { continue; }
            $entry = [];
            foreach ($group === 'rows' ? ['label', 'value', 'period'] : ['text'] as $key) {
                $value = $item[$key] ?? '';
                $entry[$key] = is_string($value) || is_int($value) || is_float($value) ? (string)$value : '';
            }
            if ($group === 'rows' ? trim($entry['label']) === '' || trim($entry['value']) === '' : trim($entry['text']) === '') { continue; }
            $entry['sources'] = [];
            foreach (is_array($item['sources'] ?? null) ? $item['sources'] : [] as $ref) {
                if (is_string($ref) && isset($job['sources'][$ref])) { $entry['sources'][] = $ref; }
            }
            $clean[$group][] = $entry;
            $hasContent = true;
        }
    }
    if (trim($clean['gap']) !== '') { $hasContent = true; }
    if ($number === 0) { $prepared['snapshot'] = $clean['rows']; }
    else { $prepared['sections'][] = $clean; }
}
if ($hasContent) { $document = $prepared; }
