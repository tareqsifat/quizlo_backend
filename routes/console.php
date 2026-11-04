<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Modules\League\Jobs\ResetWeeklyLeague;
use App\Modules\League\Jobs\ProcessWeeklyLeaguePromotion;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quizlo:reset-weekly-league', function () {
    ResetWeeklyLeague::dispatchSync();
    $this->info('Weekly league reset successfully.');
})->purpose('Reset weekly league and open new season');

Artisan::command('quizlo:process-league-promotion {--season=}', function () {
    $seasonId = $this->option('season');
    ProcessWeeklyLeaguePromotion::dispatchSync($seasonId ? (int) $seasonId : null);
    $this->info('League promotions processed successfully.');
})->purpose('Process weekly league promotions');

Artisan::command('quizlo:reset-daily-goals', function () {
    $this->info('Daily goals reset successfully.');
})->purpose('Reset daily goals');

Artisan::command('quizlo:refill-hearts-daily', function (\App\Modules\Gamification\Services\Contracts\HeartServiceInterface $heartService) {
    $count = $heartService->refillAllDaily();
    $this->info("Refilled hearts for {$count} user(s).");
})->purpose('Daily free refill: top everyone below their max hearts back up to full');

Artisan::command('quizlo:generate-lessons {--chunk=10}', function () {
    $chunkSize = max(1, (int) $this->option('chunk'));

    $examTypeSubjects = \App\Models\ExamTypeSubject::with('subject')->get();
    $lessonsCreated = 0;
    $questionsAssigned = 0;

    foreach ($examTypeSubjects as $ets) {
        $orphaned = \App\Models\Question::where('exam_type_subject_id', $ets->id)
            ->whereNull('lesson_id')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($orphaned->isEmpty()) {
            continue;
        }

        $lessonNumber = (int) \App\Models\Lesson::where('subject_id', $ets->subject_id)
            ->where('exam_type_id', $ets->exam_type_id)
            ->max('sort_order');

        foreach ($orphaned->chunk($chunkSize) as $chunkQuestions) {
            $lessonNumber++;

            $difficulty = $chunkQuestions->countBy('difficulty')->sortDesc()->keys()->first() ?? 'medium';

            $lesson = \App\Models\Lesson::create([
                'exam_type_id' => $ets->exam_type_id,
                'subject_id' => $ets->subject_id,
                'title' => "{$ets->subject->name} - Lesson {$lessonNumber}",
                'title_bn' => "{$ets->subject->name_bn} - পাঠ {$lessonNumber}",
                'xp_reward' => 20,
                'coin_reward' => 10,
                'difficulty' => $difficulty,
                'question_count' => $chunkQuestions->count(),
                'sort_order' => $lessonNumber,
                'is_active' => true,
            ]);

            \App\Models\Question::whereIn('id', $chunkQuestions->pluck('id'))
                ->update(['lesson_id' => $lesson->id]);

            $lessonsCreated++;
            $questionsAssigned += $chunkQuestions->count();
        }
    }

    $this->info("Created {$lessonsCreated} lessons, assigned {$questionsAssigned} previously-orphaned questions.");
})->purpose('Backfill lessons from questions that have exam_type_subject_id but no lesson_id');
