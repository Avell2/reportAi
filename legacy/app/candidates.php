<?php
// Conservative extraction: exact names, JSON Pointer and original value.

$candidates = [];
$stack = [['value' => $payload, 'path' => '']];

while ($stack) {
    $node = array_pop($stack);
    if (!is_array($node['value'])) {
        continue;
    }
    foreach ($node['value'] as $key => $value) {
        $path = $node['path'] . '/' . str_replace(['~', '/'], ['~0', '~1'], (string)$key);
        if (in_array((string)$key, $catalog[$method]['fields'], true)) {
            $candidates[] = [
                'field' => (string)$key,
                'path' => $path,
                'value' => $value,
            ];
        } elseif (is_array($value)) {
            $stack[] = ['value' => $value, 'path' => $path];
        }
    }
}
