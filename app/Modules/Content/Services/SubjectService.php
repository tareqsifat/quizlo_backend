<?php

namespace App\Modules\Content\Services;

use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserLessonCompletion;
use App\Models\UserSubjectMastery;
use App\Modules\Content\Services\Contracts\SubjectServiceInterface;
use App\Modules\Content\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SubjectService implements SubjectServiceInterface
{
    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository
    ) {}

    public function getSubjectsByExamType(int $examTypeId, ?User $user = null): Collection
    {
        $cacheKey = "subjects:exam:{$examTypeId}";
        $ttl = config('quizlo.cache.exam_type_subjects_ttl', 86400);

        // Cache only the raw attribute arrays (not serialized Eloquent objects)
        // to avoid "incomplete object" errors on deserialization.
        $rawRows = Cache::remember($cacheKey, $ttl, function () use ($examTypeId) {
            $subjects = $this->subjectRepository->getByExamType($examTypeId);

            $lessonCounts = Lesson::where('exam_type_id', $examTypeId)
                ->where('is_active', true)
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->selectRaw('subject_id, count(*) as cnt')
                ->groupBy('subject_id')
                ->pluck('cnt', 'subject_id');

            return $subjects->map(function ($subject) use ($lessonCounts) {
                // Preserve the pivot data and total-lesson count alongside the model attributes.
                $attrs = $subject->getAttributes();
                if ($subject->relationLoaded('examTypes') && $subject->examTypes->isNotEmpty()) {
                    $attrs['_pivot'] = $subject->examTypes->first()->pivot->toArray();
                }
                $attrs['_total_lessons'] = (int) ($lessonCounts[$subject->id] ?? 0);
                return $attrs;
            })->values()->all();
        });

        // Per-user data (mastery %, completed lessons) is never cached above — it's
        // user-specific and would leak/stale across users under the shared cache key.
        $masteryBySubject = new Collection();
        $completedBySubject = new Collection();
        if ($user !== null) {
            $subjectIds = collect($rawRows)->pluck('id');

            $masteryBySubject = UserSubjectMastery::where('user_id', $user->id)
                ->where('exam_type_id', $examTypeId)
                ->whereIn('subject_id', $subjectIds)
                ->get()
                ->keyBy('subject_id');

            $completedBySubject = UserLessonCompletion::join('lessons', 'lessons.id', '=', 'user_lesson_completions.lesson_id')
                ->where('user_lesson_completions.user_id', $user->id)
                ->where('lessons.exam_type_id', $examTypeId)
                ->whereIn('lessons.subject_id', $subjectIds)
                ->selectRaw('lessons.subject_id, count(*) as cnt')
                ->groupBy('lessons.subject_id')
                ->pluck('cnt', 'lessons.subject_id');
        }

        // Hydrate back into proper Eloquent models so SubjectResource works normally.
        return Collection::make($rawRows)->map(function ($attrs) use ($masteryBySubject, $completedBySubject) {
            $pivotData = $attrs['_pivot'] ?? [];
            $totalLessons = $attrs['_total_lessons'] ?? 0;
            unset($attrs['_pivot'], $attrs['_total_lessons']);

            // newFromBuilder hydrates raw attributes directly (like a fresh DB row),
            // bypassing $fillable — Subject::newModelInstance() silently dropped 'id'
            // because 'id' isn't mass-assignable, which broke every downstream identifier.
            $subject = (new Subject())->newFromBuilder($attrs);

            if (!empty($pivotData)) {
                // Re-attach a minimal pivot object on the examTypes relation — this is
                // the correct place for it: Subject::examTypes() is the belongsToMany
                // side, so pivot data lives on each related ExamType, not on $subject
                // directly. SubjectResource reads it via examTypes()->first()->pivot.
                $examType = new ExamType();
                $pivot = ExamTypeSubject::fromRawAttributes($examType, $pivotData, 'exam_type_subjects', true);
                $examTypeCollection = new Collection([$examType->setRelation('pivot', $pivot)]);
                $subject->setRelation('examTypes', $examTypeCollection);
            }

            $subject->total_lessons = (int) $totalLessons;
            $mastery = $masteryBySubject->get($subject->id);
            $subject->user_mastery_percentage = $mastery ? (float) $mastery->mastery_percentage : 0.0;
            $subject->completed_lessons = (int) ($completedBySubject[$subject->id] ?? 0);

            return $subject;
        });
    }
}
