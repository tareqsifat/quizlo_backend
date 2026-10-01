<?php

// Apply import_plan.json (built by plan_import.py) to the database in one transaction.
// Run via: php artisan tinker --execute="require '<scratchpad>/harvest/import_plan.php';"
// Re-runnable: a question whose exact text already exists in the same subject is not inserted again,
// and an appearance (question, exam type, year) that already exists is not added again.

use Illuminate\Support\Facades\DB;

$plan = json_decode(file_get_contents(__DIR__ . '/import_plan_final.json'), true, 512, JSON_THROW_ON_ERROR);
$now = now();
$stats = ['questions' => 0, 'questions_already_present' => 0, 'options' => 0, 'appearances' => 0, 'appearances_already_present' => 0];

$addAppearance = function (int $questionId, array $a) use ($now, &$stats) {
    // the unique index ignores NULL years, so check explicitly
    $exists = DB::table('question_exam_appearances')
        ->where('question_id', $questionId)
        ->where('exam_type_id', $a['exam_type_id'])
        ->when($a['year'] === null,
            fn ($q) => $q->whereNull('year')->where('source_batch', $a['source_batch']),
            fn ($q) => $q->where('year', $a['year']))
        ->exists();
    if ($exists) {
        $stats['appearances_already_present']++;
        return;
    }
    DB::table('question_exam_appearances')->insert([
        'question_id'  => $questionId,
        'exam_type_id' => $a['exam_type_id'],
        'year'         => $a['year'],
        'source_batch' => $a['source_batch'] ?: null,
        'created_by'   => null,
        'created_at'   => $now,
        'updated_at'   => $now,
    ]);
    $stats['appearances']++;
};

DB::transaction(function () use ($plan, $now, &$stats, $addAppearance) {
    foreach ($plan['new_questions'] as $q) {
        $questionId = DB::table('questions')
            ->where('exam_type_subject_id', $q['exam_type_subject_id'])
            ->where('question_text', $q['question_text'])
            ->value('id');

        if ($questionId) {
            $stats['questions_already_present']++;
        } else {
            // same shape as the existing bulk-uploaded rows
            $questionId = DB::table('questions')->insertGetId([
                'exam_type_subject_id' => $q['exam_type_subject_id'],
                'question_type'        => 'mcq',
                'question_text'        => $q['question_text'],
                'question_bn'          => $q['question_text'],
                'explanation'          => $q['explanation'] ?: null,
                'difficulty'           => 'medium',
                'xp_value'             => 10,
                'is_active'            => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
            foreach ($q['options'] as $i => $text) {
                DB::table('question_options')->insert([
                    'question_id'    => $questionId,
                    'option_text'    => $text,
                    'option_text_bn' => $text,
                    'is_correct'     => $i === $q['correct_index'] ? 1 : 0,
                    'sort_order'     => $i,
                ]);
                $stats['options']++;
            }
            $stats['questions']++;
        }

        foreach ($q['appearances'] as $a) {
            $addAppearance($questionId, $a);
        }
    }

    foreach ($plan['existing_appearances'] as $a) {
        $addAppearance($a['question_id'], $a);
    }
});

echo json_encode($stats) . "\n";
