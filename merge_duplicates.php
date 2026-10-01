<?php

// Merge confirmed exact-duplicate question groups into one canonical row each.
// DOES NOT RUN AUTOMATICALLY - only via explicit invocation after user approval.
// Run via: php artisan tinker --execute="require 'merge_duplicates.php';"

$groups = json_decode(file_get_contents(
    'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad/true_duplicate_groups.json'
), true);

$mergedCount = 0;
$deletedIds = [];

DB::transaction(function () use ($groups, &$mergedCount, &$deletedIds) {
    foreach ($groups as $g) {
        $ids = $g['ids'];
        sort($ids); // keep the lowest id as canonical (oldest / first-imported)
        $canonicalId = array_shift($ids); // remaining $ids are duplicates to merge away

        foreach ($ids as $dupId) {
            // 1. Move appearances to canonical, skipping ones the canonical already has
            $dupAppearances = DB::table('question_exam_appearances')->where('question_id', $dupId)->get();
            foreach ($dupAppearances as $a) {
                $exists = DB::table('question_exam_appearances')
                    ->where('question_id', $canonicalId)
                    ->where('exam_type_id', $a->exam_type_id)
                    ->where('year', $a->year)
                    ->exists();
                if (!$exists) {
                    DB::table('question_exam_appearances')
                        ->where('id', $a->id)
                        ->update(['question_id' => $canonicalId]);
                } else {
                    DB::table('question_exam_appearances')->where('id', $a->id)->delete();
                }
            }

            // 2. Re-point known FKs referencing the duplicate question.
            // user_answers has no unique constraint on question_id, so a blind update is safe.
            DB::table('user_answers')->where('question_id', $dupId)->update(['question_id' => $canonicalId]);

            // model_test_questions has a composite primary key (model_test_id, question_id):
            // a blind update would violate it if the canonical question is already in the
            // same model test as the duplicate. Re-point only where safe; drop the rest.
            $dupModelTestRows = DB::table('model_test_questions')->where('question_id', $dupId)->get();
            foreach ($dupModelTestRows as $row) {
                $alreadyInTest = DB::table('model_test_questions')
                    ->where('model_test_id', $row->model_test_id)
                    ->where('question_id', $canonicalId)
                    ->exists();
                if (!$alreadyInTest) {
                    DB::table('model_test_questions')
                        ->where('model_test_id', $row->model_test_id)
                        ->where('question_id', $dupId)
                        ->update(['question_id' => $canonicalId]);
                } else {
                    DB::table('model_test_questions')
                        ->where('model_test_id', $row->model_test_id)
                        ->where('question_id', $dupId)
                        ->delete();
                }
            }

            // 3. Soft-delete the duplicate (never hard-delete - keeps audit trail)
            DB::table('questions')->where('id', $dupId)->update([
                'is_active' => false,
                'merged_into_question_id' => $canonicalId,
            ]);

            $deletedIds[] = $dupId;
        }
        $mergedCount++;
    }
});

// Remove merged-away questions from the search index
\App\Models\Question::whereIn('id', $deletedIds)->get()->each->unsearchable();

echo "Merged {$mergedCount} duplicate groups.\n";
echo "Soft-deleted (merged_into_question_id set) ids: " . implode(',', $deletedIds) . "\n";
echo "Remaining active questions: " . \App\Models\Question::where('is_active', true)->count() . "\n";
