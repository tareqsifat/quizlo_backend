<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionExamAppearance;
use App\Modules\Admin\Requests\SearchSimilarQuestionsRequest;
use App\Modules\Admin\Requests\StoreQuestionAppearanceRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class QuestionAppearanceController extends Controller
{
    /**
     * Button-triggered similarity search (Meilisearch via Scout).
     * Returns full question context so an admin can visually confirm
     * a duplicate rather than relying on a fabricated confidence score.
     */
    public function searchSimilar(SearchSimilarQuestionsRequest $request): JsonResponse
    {
        $matches = Question::search($request->validated('q'))
            ->take(10)
            ->get()
            ->load(['options', 'appearances.examType', 'examTypeSubject.subject', 'examTypeSubject.examType']);

        $data = $matches->map(fn (Question $q) => [
            'id'                  => $q->id,
            'question_text'       => $q->question_text,
            'question_bn'         => $q->question_bn,
            'exam_subject_title'  => $q->examTypeSubject?->title,
            'options'             => $q->options->map(fn ($o) => [
                'option_text' => $o->option_text,
                'is_correct'  => (bool) $o->is_correct,
            ])->values(),
            'appearances'         => $this->formatAppearances($q),
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function index(Question $question): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->formatAppearances($question->load('appearances.examType')),
        ]);
    }

    /**
     * Tag a question (new or existing) with an exam-type + year appearance.
     * Uses firstOrCreate + a catch for the unique-constraint race, so two
     * admins tagging the same appearance concurrently both succeed instead
     * of one getting a 500.
     */
    public function store(StoreQuestionAppearanceRequest $request, Question $question): JsonResponse
    {
        $attributes = [
            'exam_type_id' => $request->validated('exam_type_id'),
            'year'         => $request->validated('year'),
        ];

        try {
            $appearance = $question->appearances()->firstOrCreate($attributes, [
                'source_batch' => $request->validated('source_batch'),
                'created_by'   => $request->user()?->id,
            ]);
        } catch (QueryException $e) {
            // Unique-constraint race: another request created the same row first.
            $appearance = $question->appearances()->where($attributes)->first();
            if (!$appearance) {
                throw $e;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam appearance recorded.',
            'data'    => $appearance->load('examType'),
        ]);
    }

    public function destroy(Question $question, QuestionExamAppearance $appearance): JsonResponse
    {
        if ($appearance->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Appearance does not belong to this question.',
                'data'    => null,
            ], 404);
        }

        $appearance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam appearance removed.',
            'data'    => null,
        ]);
    }

    private function formatAppearances(Question $question): array
    {
        return $question->appearances->map(fn (QuestionExamAppearance $a) => [
            'id'            => $a->id,
            'exam_type_id'  => $a->exam_type_id,
            'exam_type_name'=> $a->examType?->name,
            'exam_type_code'=> $a->examType?->code,
            'year'          => $a->year,
            'source_batch'  => $a->source_batch,
        ])->values()->all();
    }
}
