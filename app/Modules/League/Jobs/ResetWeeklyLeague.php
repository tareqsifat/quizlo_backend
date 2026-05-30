<?php

namespace App\Modules\League\Jobs;

use App\Models\ExamType;
use App\Models\LeagueSeason;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetWeeklyLeague implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $examTypes = ExamType::where('is_active', true)->get();

        foreach ($examTypes as $examType) {
            // Close current season
            LeagueSeason::where('exam_type_id', $examType->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Open next week's season
            $nextWeek = now()->addWeek();
            LeagueSeason::create([
                'exam_type_id' => $examType->id,
                'week_number' => $nextWeek->weekOfYear,
                'year' => $nextWeek->year,
                'starts_at' => $nextWeek->copy()->startOfWeek(),
                'ends_at' => $nextWeek->copy()->endOfWeek(),
                'is_active' => true,
                'processed' => false,
            ]);
        }
    }
}
