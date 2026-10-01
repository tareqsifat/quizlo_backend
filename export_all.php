<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$base = 'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad';
$batchDir = "$base/verify_batches";
if (!is_dir($batchDir)) mkdir($batchDir, 0777, true);

function slugify($s) {
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim($s, '_');
}

$subjects = DB::table('exam_type_subjects')->select('id','exam_type_id','title')->get();
$manifest = [];
$batchSize = 60;

foreach ($subjects as $subj) {
    $etsId = $subj->id;
    $examLabel = $subj->exam_type_id == 1 ? 'bcs' : 'ntrca';
    $slug = $examLabel.'_'.slugify($subj->title);

    $questions = DB::table('questions')->where('exam_type_subject_id', $etsId)->orderBy('id')->get();
    $data = [];
    foreach ($questions as $q) {
        $opts = DB::table('question_options')->where('question_id', $q->id)->orderBy('sort_order')->get();
        $optArr = [];
        $correct = null;
        $letters = ['A','B','C','D'];
        foreach ($opts as $i => $o) {
            $letter = $letters[$i] ?? chr(65+$i);
            $optArr[$letter] = $o->option_text;
            if ($o->is_correct) $correct = $letter;
        }
        $data[] = [
            'id' => $q->id,
            'question' => $q->question_text,
            'options' => $optArr,
            'current_answer' => $correct,
        ];
    }

    $chunks = array_chunk($data, $batchSize);
    foreach ($chunks as $i => $chunk) {
        $fname = "{$slug}_batch".($i+1).".json";
        file_put_contents("$batchDir/$fname", json_encode($chunk, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $manifest[] = [
            'file' => $fname,
            'subject' => $subj->title,
            'exam' => $examLabel,
            'ets_id' => $etsId,
            'count' => count($chunk),
            'status' => 'pending',
        ];
    }
    echo $slug.': '.count($data)." questions in ".count($chunks)." batches\n";
}

file_put_contents("$batchDir/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
echo "\nTotal batches: ".count($manifest)."\n";
