<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Lesson;
use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentManagementController extends Controller
{
    // ─── Questions CRUD ───────────────────────────────────────────

    public function listQuestions(Request $request): JsonResponse
    {
        $questions = Question::with(['options', 'examTypeSubject.subject', 'examTypeSubject.examType'])->get()
            ->map(function ($q) {
                return array_merge($q->toArray(), [
                    'subject_name'         => $q->examTypeSubject?->subject?->name,
                    'exam_type_name'       => $q->examTypeSubject?->examType?->name,
                    'exam_subject_title'   => $q->examTypeSubject?->title,
                ]);
            });

        return response()->json([
            'success' => true,
            'data'    => $questions,
        ]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_subject_id' => ['required', 'integer', 'exists:exam_type_subjects,id'],
            'topic_id'             => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id'            => ['nullable', 'integer', 'exists:lessons,id'],
            'question_text'        => ['required', 'string'],
            'question_bn'          => ['nullable', 'string'],
            'explanation'          => ['nullable', 'string'],
            'difficulty'           => ['sometimes', 'in:easy,medium,hard'],
            'xp_value'             => ['sometimes', 'integer'],
        ]);

        $question = Question::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data'    => $question,
        ]);
    }

    public function updateQuestion(Request $request, int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'exam_type_subject_id' => ['sometimes', 'integer', 'exists:exam_type_subjects,id'],
            'topic_id'             => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id'            => ['nullable', 'integer', 'exists:lessons,id'],
            'question_text'        => ['sometimes', 'string'],
            'question_bn'          => ['nullable', 'string'],
            'explanation'          => ['nullable', 'string'],
            'difficulty'           => ['sometimes', 'in:easy,medium,hard'],
            'xp_value'             => ['sometimes', 'integer'],
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data'    => $question,
        ]);
    }

    public function destroyQuestion(int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.', 'data' => null], 404);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
            'data'    => null,
        ]);
    }

    // ─── Security constants for bulk import ───────────────────────

    private const IMPORT_MAX_ROWS       = 500;
    private const IMPORT_MAX_FIELD_LEN  = 2000;
    private const IMPORT_MAX_OPTION_LEN = 500;

    /**
     * Sanitise a single import field:
     *  - Strip null bytes and ASCII control characters (except tab/LF/CR)
     *  - Strip HTML/XML tags
     *  - Normalise whitespace
     *  - Trim to max length
     */
    private function sanitiseField(string $value, int $maxLen = 2000): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        $value = strip_tags($value);
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = trim($value);

        if (mb_strlen($value) > $maxLen) {
            $value = mb_substr($value, 0, $maxLen);
        }

        return $value;
    }

    /**
     * Scan a row's fields for suspicious patterns (SQL/XSS/injection).
     */
    private function detectSuspiciousContent(array $fields): array
    {
        $violations = [];

        $dangerousPatterns = [
            '/\bSELECT\b.*\bFROM\b/i'         => 'SQL SELECT pattern',
            '/\bINSERT\b.*\bINTO\b/i'          => 'SQL INSERT pattern',
            '/\bDROP\b.*\bTABLE\b/i'           => 'SQL DROP pattern',
            '/\bUNION\b.*\bSELECT\b/i'         => 'SQL UNION pattern',
            '/<script[\s>]/i'                   => 'Script tag',
            '/javascript\s*:/i'                 => 'JavaScript protocol',
            '/on\w+\s*=/i'                      => 'HTML event handler',
            '/\$\{.*\}/s'                       => 'Template injection',
            '/\{\{.*\}\}/s'                     => 'Template injection',
            '/`[^`]*`/s'                        => 'Shell backtick',
            '/\|\s*\w+/'                        => 'Shell pipe',
            '/;\s*(rm|del|format|shutdown)\b/i' => 'Shell command',
        ];

        foreach ($fields as $fieldName => $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }
            foreach ($dangerousPatterns as $pattern => $label) {
                if (preg_match($pattern, $value)) {
                    $violations[] = "Field [{$fieldName}] contains suspicious content ({$label})";
                    break;
                }
            }
        }

        return $violations;
    }

    /**
     * Bulk Question Import
     *
     * exam_type_id is now REQUIRED. The "subject" field in each row is matched
     * case-insensitively against ExamTypeSubject.title scoped to that exam type.
     * Rows with no matching Exam Subject are collected in `failed[]` and NOT persisted.
     */
    public function import(Request $request): JsonResponse
    {
        // ── Layer 1: Batch-level validation ──────────────────────
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'source_batch' => ['nullable', 'string', 'max:50'],
            'source_year'  => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'rows'         => ['required', 'array', 'min:1', 'max:' . self::IMPORT_MAX_ROWS],
            'rows.*.question'    => ['nullable', 'string', 'max:' . self::IMPORT_MAX_FIELD_LEN],
            'rows.*.optionA'     => ['nullable', 'string', 'max:' . self::IMPORT_MAX_OPTION_LEN],
            'rows.*.optionB'     => ['nullable', 'string', 'max:' . self::IMPORT_MAX_OPTION_LEN],
            'rows.*.optionC'     => ['nullable', 'string', 'max:' . self::IMPORT_MAX_OPTION_LEN],
            'rows.*.optionD'     => ['nullable', 'string', 'max:' . self::IMPORT_MAX_OPTION_LEN],
            'rows.*.rightAnswer' => ['nullable', 'string', 'in:A,B,C,D'],
            'rows.*.subject'     => ['nullable', 'string', 'max:200'],
            'rows.*.explanation' => ['nullable', 'string', 'max:' . self::IMPORT_MAX_FIELD_LEN],
        ]);

        $examTypeId = (int) $request->input('exam_type_id');
        $examType   = ExamType::find($examTypeId);

        // ── Layer 2: Build Exam Subject lookup map ────────────────
        // Key: LOWER(TRIM(title)) → exam_type_subject_id
        $examSubjectMap = ExamTypeSubject::where('exam_type_id', $examTypeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn ($ets) => [mb_strtolower(trim($ets->title)) => $ets->id])
            ->toArray();

        $imported = 0;
        $skipped  = 0;
        $failed   = [];
        $notices  = [];

        foreach ($request->input('rows') as $index => $row) {
            $rowNum = $index + 1;

            // ── Layer 3: Sanitise every field ─────────────────────
            $question    = $this->sanitiseField($row['question']    ?? '', self::IMPORT_MAX_FIELD_LEN);
            $optionA     = $this->sanitiseField($row['optionA']     ?? '', self::IMPORT_MAX_OPTION_LEN);
            $optionB     = $this->sanitiseField($row['optionB']     ?? '', self::IMPORT_MAX_OPTION_LEN);
            $optionC     = $this->sanitiseField($row['optionC']     ?? '', self::IMPORT_MAX_OPTION_LEN);
            $optionD     = $this->sanitiseField($row['optionD']     ?? '', self::IMPORT_MAX_OPTION_LEN);
            $rightAnswer = strtoupper(trim($row['rightAnswer'] ?? ''));
            $subjectRaw  = $this->sanitiseField($row['subject']     ?? '', 200);
            $explanation = $this->sanitiseField($row['explanation'] ?? '', self::IMPORT_MAX_FIELD_LEN);

            // ── Layer 4: Suspicious-content scan ──────────────────
            $violations = $this->detectSuspiciousContent([
                'question'    => $question,
                'optionA'     => $optionA,
                'optionB'     => $optionB,
                'optionC'     => $optionC,
                'optionD'     => $optionD,
                'explanation' => $explanation,
            ]);
            if (!empty($violations)) {
                $skipped++;
                $notices[] = [
                    'row'    => $rowNum,
                    'reason' => 'Row blocked by security scan: ' . implode('; ', $violations),
                ];
                continue;
            }

            // ── Layer 5: Mandatory field presence ─────────────────
            if ($question === '') {
                $skipped++;
                $notices[] = ['row' => $rowNum, 'reason' => 'Missing question text (column A)'];
                continue;
            }
            if ($optionA === '' || $optionB === '') {
                $skipped++;
                $notices[] = ['row' => $rowNum, 'reason' => 'Missing required options (columns B / C)'];
                continue;
            }
            if (!in_array($rightAnswer, ['A', 'B', 'C', 'D'], true)) {
                $skipped++;
                $notices[] = ['row' => $rowNum, 'reason' => 'Right answer (column F) must be exactly A, B, C or D'];
                continue;
            }

            // ── Layer 6: Resolve Exam Subject (REQUIRED) ──────────
            $subjectKey          = mb_strtolower(trim($subjectRaw));
            $examTypeSubjectId   = $examSubjectMap[$subjectKey] ?? null;

            if ($subjectKey === '') {
                $skipped++;
                $notices[] = [
                    'row'    => $rowNum,
                    'reason' => 'Subject column (G) is empty. Every row must specify a Subject title.',
                ];
                continue;
            }

            if ($examTypeSubjectId === null) {
                $failed[] = [
                    'row'         => $rowNum,
                    'subject_raw' => $subjectRaw,
                    'reason'      => "No Exam Subject found for \"{$subjectRaw}\" under \"{$examType->name}\". "
                                   . 'Check spelling and ensure it is listed in Exam Subjects.',
                ];
                continue;
            }

            // ── Persist ───────────────────────────────────────────
            $q = Question::create([
                'exam_type_subject_id' => $examTypeSubjectId,
                'question_text'        => $question,
                'explanation'          => $explanation ?: null,
                'difficulty'           => 'medium',
                'xp_value'             => 10,
                'is_active'            => true,
            ]);

            $optionTexts = [
                'A' => $optionA,
                'B' => $optionB,
                'C' => $optionC ?: null,
                'D' => $optionD ?: null,
            ];

            $sortOrder = 0;
            foreach ($optionTexts as $letter => $text) {
                if ($text === null) {
                    continue;
                }
                $q->options()->create([
                    'option_text' => $text,
                    'is_correct'  => ($letter === $rightAnswer),
                    'sort_order'  => $sortOrder++,
                ]);
            }

            $imported++;
        }

        $totalFailed = count($failed);
        return response()->json([
            'success' => true,
            'message' => "Import complete: {$imported} imported, {$skipped} skipped, {$totalFailed} failed (unmatched Exam Subject).",
            'data'    => [
                'imported' => $imported,
                'skipped'  => $skipped,
                'failed'   => $failed,
                'notices'  => $notices,
            ],
        ]);
    }

    // ─── Lessons CRUD ─────────────────────────────────────────────

    public function listLessons(Request $request): JsonResponse
    {
        $lessons = Lesson::all();
        return response()->json([
            'success' => true,
            'data'    => $lessons,
        ]);
    }

    public function storeLesson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_id'  => ['required', 'integer', 'exists:exam_types,id'],
            'subject_id'    => ['required', 'integer', 'exists:subjects,id'],
            'topic_id'      => ['nullable', 'integer', 'exists:topics,id'],
            'title'         => ['required', 'string', 'max:200'],
            'title_bn'      => ['required', 'string', 'max:200'],
            'description'   => ['nullable', 'string'],
            'xp_reward'     => ['sometimes', 'integer'],
            'coin_reward'   => ['sometimes', 'integer'],
            'difficulty'    => ['sometimes', 'in:easy,medium,hard'],
            'question_count'=> ['sometimes', 'integer'],
            'sort_order'    => ['sometimes', 'integer'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $lesson = Lesson::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully.',
            'data'    => $lesson,
        ]);
    }

    public function updateLesson(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Lesson not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'exam_type_id'  => ['sometimes', 'integer', 'exists:exam_types,id'],
            'subject_id'    => ['sometimes', 'integer', 'exists:subjects,id'],
            'topic_id'      => ['nullable', 'integer', 'exists:topics,id'],
            'title'         => ['sometimes', 'string', 'max:200'],
            'title_bn'      => ['sometimes', 'string', 'max:200'],
            'description'   => ['nullable', 'string'],
            'xp_reward'     => ['sometimes', 'integer'],
            'coin_reward'   => ['sometimes', 'integer'],
            'difficulty'    => ['sometimes', 'in:easy,medium,hard'],
            'question_count'=> ['sometimes', 'integer'],
            'sort_order'    => ['sometimes', 'integer'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $lesson->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully.',
            'data'    => $lesson,
        ]);
    }
}
