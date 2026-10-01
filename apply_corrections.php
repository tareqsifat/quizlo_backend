<?php

// Run via: php artisan tinker --execute="require 'apply_corrections.php';"
$listPath = 'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad/final_apply_list.json';
$list = json_decode(file_get_contents($listPath), true);

$letterToIndex = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
$applied = [];
$skipped = [];

foreach ($list as $item) {
    $qid = $item['id'];
    $letter = $item['letter'];
    $idx = $letterToIndex[$letter] ?? null;

    $options = DB::table('question_options')
        ->where('question_id', $qid)
        ->orderBy('sort_order')
        ->get();

    if ($options->count() !== 4 || $idx === null) {
        $skipped[] = ['id' => $qid, 'reason' => 'unexpected option count or bad letter', 'count' => $options->count()];
        continue;
    }

    $targetOption = $options[$idx];
    $currentCorrect = $options->firstWhere('is_correct', 1);

    if ($currentCorrect && $currentCorrect->id === $targetOption->id) {
        $skipped[] = ['id' => $qid, 'reason' => 'already correct, no change needed'];
        continue;
    }

    DB::table('question_options')->where('question_id', $qid)->update(['is_correct' => 0]);
    DB::table('question_options')->where('id', $targetOption->id)->update(['is_correct' => 1]);

    $applied[] = [
        'question_id' => $qid,
        'old_correct_option_id' => $currentCorrect ? $currentCorrect->id : null,
        'new_correct_option_id' => $targetOption->id,
        'new_letter' => $letter,
    ];
}

file_put_contents(
    'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad/apply_result.json',
    json_encode(['applied' => $applied, 'skipped' => $skipped], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "Applied: " . count($applied) . "\n";
echo "Skipped: " . count($skipped) . "\n";
