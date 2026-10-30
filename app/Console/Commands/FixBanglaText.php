<?php

namespace App\Console\Commands;

use App\Support\BanglaText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBanglaText extends Command
{
    protected $signature = 'quizlo:fix-bangla-text {--dry-run : List the changes without saving them}';

    protected $description = 'Repair broken Bangla character sequences in questions and options';

    private const COLUMNS = [
        'questions'        => ['question_text', 'question_bn', 'explanation'],
        'question_options' => ['option_text', 'option_text_bn'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        foreach (self::COLUMNS as $table => $columns) {
            DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')
                ->chunkById(1000, function ($rows) use ($table, $columns, $dryRun, &$changed) {
                    foreach ($rows as $row) {
                        $updates = [];
                        foreach ($columns as $column) {
                            $fixed = BanglaText::fix($row->$column);
                            if ($fixed !== $row->$column) {
                                $updates[$column] = $fixed;
                                $this->line("{$table}#{$row->id}.{$column}: {$row->$column}  →  {$fixed}");
                            }
                        }
                        if ($updates) {
                            $changed++;
                            if (!$dryRun) {
                                DB::table($table)->where('id', $row->id)->update($updates);
                            }
                        }
                    }
                });
        }

        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$changed} rows.");

        return self::SUCCESS;
    }
}
