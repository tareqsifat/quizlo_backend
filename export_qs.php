<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$outDir = 'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad';

$subjects = [5=>'bangla', 6=>'english', 4=>'general_knowledge', 7=>'general_math', 8=>'general_science'];

foreach ($subjects as $etsId => $slug) {
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
            'explanation' => $q->explanation,
        ];
    }
    file_put_contents("$outDir/verify_{$slug}.json", json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo "$slug: ".count($data)." questions exported\n";
}
