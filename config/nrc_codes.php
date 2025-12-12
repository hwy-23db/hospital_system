<?php

$path = base_path('config/nrc_codes_data.json');
$entries = [];

if (is_file($path)) {
    $decoded = json_decode(file_get_contents($path), true);
    if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
        $entries = $decoded['data'];
    }
}

$codes = [];
foreach ($entries as $entry) {
    if (!isset($entry['nrc_code'], $entry['name_en'])) {
        continue;
    }
    $codes[$entry['nrc_code']][] = $entry['name_en'];
}

return [
    'citizenships' => ['N', 'F', 'P', 'TH', 'S'],
    // Raw entries with id/name_en/name_mm/nrc_code for reference
    'entries' => $entries,
    // Grouped list of township codes by nrc_code for quick validation
    'codes' => $codes,
];
