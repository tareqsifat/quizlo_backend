<?php

// Repair the first import run using reconcile.json (built by reconcile.py):
//   drop   -> row inserted by the import is a duplicate: delete it, its appearances go to the target
//   insert -> item wrongly skipped by the importer's text guard: insert it now
//   skip   -> item correctly skipped: its appearances go to the target
//   keep   -> nothing to change
// question_exam_appearances was empty before the import, so it is rebuilt from the plan.
// Run via: php artisan tinker --execute="require '<scratchpad>/harvest/apply_reconcile.php';"

use Illuminate\Support\Facades\DB;

const FIRST_IMPORTED_ID = 4631;   // max(questions.id) in the pre-import backup was 4630

$plan = json_decode(file_get_contents(__DIR__ . '/import_plan.json'), true, 512, JSON_THROW_ON_ERROR);
$decisions = json_decode(file_get_contents(__DIR__ . '/reconcile.json'), true, 512, JSON_THROW_ON_ERROR);
$now = now();
$stats = ['deleted' => 0, 'inserted' => 0, 'appearances' => 0];

DB::transaction(function () use ($plan, $decisions, $now, &$stats) {
    $finalId = [];

    // 1. delete duplicate rows (only ever rows created by the import)
    foreach ($decisions as $d) {
        if ($d['action'] !== 'drop') {
            continue;
        }
        if ($d['row'] < FIRST_IMPORTED_ID) {
            throw new RuntimeException("refusing to delete pre-existing question {$d['row']}");
        }
        DB::table('question_options')->where('question_id', $d['row'])->delete();
        DB::table('questions')->where('id', $d['row'])->delete();
        $stats['deleted']++;
    }

    // 2. insert wrongly skipped items; record ids for kept ones
    foreach ($decisions as $d) {
        $q = $plan['new_questions'][$d['i']];
        if ($d['action'] === 'keep') {
            $finalId[$d['i']] = $d['row'];
        } elseif ($d['action'] === 'insert') {
            $id = DB::table('questions')->insertGetId([
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
                    'question_id'    => $id,
                    'option_text'    => $text,
                    'option_text_bn' => $text,
                    'is_correct'     => $i === $q['correct_index'] ? 1 : 0,
                    'sort_order'     => $i,
                ]);
            }
            $finalId[$d['i']] = $id;
            $stats['inserted']++;
        }
    }
    // drop/skip point at a DB question or at another plan item (always a kept/inserted one)
    foreach ($decisions as $d) {
        if (in_array($d['action'], ['drop', 'skip'], true)) {
            $finalId[$d['i']] = $d['target_id'] ?? $finalId[$d['target_plan']];
        }
    }

    // 3. rebuild appearances
    DB::table('question_exam_appearances')->delete();
    $seen = [];
    $add = function (int $questionId, array $a) use (&$seen, &$stats, $now) {
        $key = implode('|', [$questionId, $a['exam_type_id'], $a['year'] ?? 'null:' . $a['source_batch']]);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
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
    foreach ($plan['new_questions'] as $i => $q) {
        foreach ($q['appearances'] as $a) {
            $add($finalId[$i], $a);
        }
    }
    foreach ($plan['existing_appearances'] as $a) {
        $add($a['question_id'], $a);
    }
});

echo json_encode($stats) . "\n";
