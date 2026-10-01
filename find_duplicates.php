<?php

// Find true duplicate questions: same normalized text AND same normalized option set.
// Run via: php artisan tinker --execute="require 'find_duplicates.php';"

$groups = DB::table('questions')
    ->select(DB::raw('LOWER(TRIM(question_text)) as norm_text'), DB::raw('GROUP_CONCAT(id) as ids'))
    ->groupBy(DB::raw('LOWER(TRIM(question_text))'))
    ->havingRaw('COUNT(*) > 1')
    ->get();

$trueDuplicateGroups = [];
$falsePositiveGroups = [];

foreach ($groups as $g) {
    $ids = array_map('intval', explode(',', $g->ids));
    $optionSets = [];

    foreach ($ids as $id) {
        $opts = DB::table('question_options')
            ->where('question_id', $id)
            ->orderBy('sort_order')
            ->pluck('option_text')
            ->map(fn ($t) => mb_strtolower(trim($t)))
            ->sort()
            ->values()
            ->all();
        $optionSets[$id] = implode('|', $opts);
    }

    $uniqueOptionSets = array_unique($optionSets);

    if (count($uniqueOptionSets) === 1) {
        // All rows in this text-group share the same option set too -> true duplicate
        $trueDuplicateGroups[] = ['ids' => $ids, 'text' => $g->norm_text];
    } else {
        // Same stem, different options -> generic reusable stem, not a real duplicate
        $falsePositiveGroups[] = ['ids' => $ids, 'text' => $g->norm_text, 'option_sets' => $optionSets];
    }
}

echo "True duplicate groups (same text AND same options): " . count($trueDuplicateGroups) . "\n";
$trueDupeRows = array_sum(array_map(fn ($g) => count($g['ids']), $trueDuplicateGroups));
echo "Total rows involved in true duplicates: {$trueDupeRows}\n\n";

echo "False-positive groups (same text, different options - generic stems): " . count($falsePositiveGroups) . "\n\n";

echo "=== TRUE DUPLICATES ===\n";
foreach ($trueDuplicateGroups as $g) {
    echo implode(',', $g['ids']) . " => " . mb_substr($g['text'], 0, 80) . "\n";
}

file_put_contents(
    'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad/true_duplicate_groups.json',
    json_encode($trueDuplicateGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
file_put_contents(
    'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad/false_positive_groups.json',
    json_encode($falsePositiveGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
