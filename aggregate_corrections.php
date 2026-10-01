<?php
$base = 'C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad';
$manifest = json_decode(file_get_contents("$base/verify_batches/manifest.json"), true);
$fileToMeta = [];
foreach ($manifest as $m) {
    $slug = preg_replace('/\.json$/', '', $m['file']);
    $fileToMeta[$slug] = $m;
}

$corrDir = "$base/verify_corrections";
$all = [];
$totalFlagged = 0;
$totalQuestions = 0;
$bySubject = [];

foreach (glob("$corrDir/*.json") as $f) {
    $slug = basename($f, '.json');
    $items = json_decode(file_get_contents($f), true);
    $meta = $fileToMeta[$slug] ?? ['subject' => $slug, 'exam' => '?', 'count' => 0];
    $totalQuestions += $meta['count'];
    if (!is_array($items)) $items = [];
    $totalFlagged += count($items);
    $key = $meta['exam'].'|'.$meta['subject'];
    if (!isset($bySubject[$key])) $bySubject[$key] = ['exam'=>$meta['exam'],'subject'=>$meta['subject'],'flagged'=>0,'total'=>0];
    $bySubject[$key]['flagged'] += count($items);
    $bySubject[$key]['total'] += $meta['count'];
    foreach ($items as $it) {
        $it['batch'] = $slug;
        $it['subject'] = $meta['subject'];
        $it['exam'] = $meta['exam'];
        $all[] = $it;
    }
}

file_put_contents("$base/aggregate_corrections.json", json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Total questions checked: $totalQuestions\n";
echo "Total flagged: $totalFlagged\n\n";
echo "By subject (exam | flagged/total):\n";
foreach ($bySubject as $d) {
    echo "  [{$d['exam']}] {$d['subject']}: {$d['flagged']}/{$d['total']}\n";
}
echo "\nAggregate written to $base/aggregate_corrections.json\n";
