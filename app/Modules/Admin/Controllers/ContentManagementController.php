<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Lesson;
use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\ModelTest;
use App\Models\ExamSession;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ContentManagementController extends Controller
{
    // ─── Questions CRUD ───────────────────────────────────────────

    public function listQuestions(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page'                 => ['nullable', 'integer', 'min:1'],
            'per_page'             => ['nullable', 'integer', 'min:1', 'max:100'],
            'exam_type_id'         => ['nullable', 'integer'],
            'exam_type_subject_id' => ['nullable', 'integer'],
            'topic_id'             => ['nullable', 'integer'],
            'question_type'        => ['nullable', 'string', 'max:30'],
            'difficulty'           => ['nullable', 'in:easy,medium,hard'],
            'search'               => ['nullable', 'string', 'max:200'],
            'year'                 => ['nullable', 'integer', 'between:1900,2100'],
            'has_year'             => ['nullable', 'boolean'],
            'verified_by_ai'       => ['nullable', 'boolean'],
        ]);

        $page = Question::with(['options', 'examTypeSubject.subject', 'examTypeSubject.examType', 'appearances.examType'])
            ->when($filters['exam_type_id'] ?? null, fn ($q, $v) => $q->whereHas('examTypeSubject', fn ($s) => $s->where('exam_type_id', $v)))
            ->when($filters['exam_type_subject_id'] ?? null, fn ($q, $v) => $q->where('exam_type_subject_id', $v))
            ->when($filters['topic_id'] ?? null, fn ($q, $v) => $q->where('topic_id', $v))
            ->when($filters['question_type'] ?? null, fn ($q, $v) => $q->where('question_type', $v))
            ->when($filters['difficulty'] ?? null, fn ($q, $v) => $q->where('difficulty', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $like = '%' . addcslashes($v, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('question_text', 'like', $like)->orWhere('question_bn', 'like', $like));
            })
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->whereHas('appearances', fn ($a) => $a->where('year', $v)))
            ->when(isset($filters['has_year']), fn ($q) => $request->boolean('has_year')
                ? $q->whereHas('appearances', fn ($a) => $a->whereNotNull('year'))
                : $q->whereDoesntHave('appearances', fn ($a) => $a->whereNotNull('year')))
            ->when(isset($filters['verified_by_ai']), fn ($q) => $q->where('verified_by_ai', $request->boolean('verified_by_ai')))
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        $questions = $page->getCollection()
            ->map(function ($q) {
                return array_merge($q->toArray(), [
                    'subject_name'         => $q->examTypeSubject?->subject?->name,
                    'exam_type_name'       => $q->examTypeSubject?->examType?->name,
                    'exam_subject_title'   => $q->examTypeSubject?->title,
                    'appearances'          => $q->appearances->map(fn ($a) => [
                        'id'             => $a->id,
                        'exam_type_id'   => $a->exam_type_id,
                        'exam_type_name' => $a->examType?->name,
                        'exam_type_code' => $a->examType?->code,
                        'year'           => $a->year,
                        'source_batch'   => $a->source_batch,
                    ])->values(),
                ]);
            });

        return response()->json([
            'success' => true,
            'data'    => $questions,
            'meta'    => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    /** Distinct exam years that have questions, newest first (for the admin year filter). */
    public function listQuestionYears(): JsonResponse
    {
        $years = DB::table('question_exam_appearances')
            ->whereNotNull('year')
            ->select('year', DB::raw('COUNT(DISTINCT question_id) as questions'))
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $years,
        ]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_subject_id'      => ['required', 'integer', 'exists:exam_type_subjects,id'],
            'topic_id'                  => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id'                 => ['nullable', 'integer', 'exists:lessons,id'],
            'question_type'              => ['sometimes', 'in:mcq,fill_gap,listen_answer,match_answer'],
            'question_text'             => ['required', 'string'],
            'question_bn'               => ['nullable', 'string'],
            'explanation'                => ['nullable', 'string'],
            'audio_url'                  => ['nullable', 'string'],
            'difficulty'                 => ['sometimes', 'in:easy,medium,hard'],
            'xp_value'                   => ['sometimes', 'integer'],
            'options'                    => ['sometimes', 'array'],
            'options.*.option_text'      => ['required_with:options', 'string'],
            'options.*.option_text_bn'   => ['nullable', 'string'],
            'options.*.match_text'       => ['nullable', 'string'],
            'options.*.is_correct'       => ['sometimes', 'boolean'],
            'appearance'                 => ['sometimes', 'array'],
            'appearance.exam_type_id'    => ['required_with:appearance', 'integer', 'exists:exam_types,id'],
            'appearance.year'            => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'appearance.source_batch'    => ['nullable', 'string', 'max:50'],
        ]);

        if (!empty($validated['topic_id'])) {
            $this->assertTopicMatchesExamSubject($validated['topic_id'], $validated['exam_type_subject_id']);
        }

        $question = DB::transaction(function () use ($request, $validated) {
            $question = Question::create(collect($validated)->except(['options', 'appearance'])->all());

            foreach ($request->input('options', []) as $sortOrder => $option) {
                $question->options()->create([
                    'option_text'    => $option['option_text'],
                    'option_text_bn' => $option['option_text_bn'] ?? null,
                    'match_text'     => $option['match_text'] ?? null,
                    'is_correct'     => (bool) ($option['is_correct'] ?? false),
                    'sort_order'     => $sortOrder,
                ]);
            }

            if (!empty($validated['appearance'])) {
                $question->appearances()->create([
                    'exam_type_id' => $validated['appearance']['exam_type_id'],
                    'year'         => $validated['appearance']['year'] ?? null,
                    'source_batch' => $validated['appearance']['source_batch'] ?? null,
                    'created_by'   => $request->user()?->id,
                ]);
            }

            return $question;
        });

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data'    => $question->load(['options', 'appearances.examType']),
        ]);
    }

    public function updateQuestion(Request $request, int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'exam_type_subject_id'      => ['sometimes', 'integer', 'exists:exam_type_subjects,id'],
            'topic_id'                  => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id'                 => ['nullable', 'integer', 'exists:lessons,id'],
            'question_type'              => ['sometimes', 'in:mcq,fill_gap,listen_answer,match_answer'],
            'question_text'             => ['sometimes', 'string'],
            'question_bn'               => ['nullable', 'string'],
            'explanation'                => ['nullable', 'string'],
            'audio_url'                  => ['nullable', 'string'],
            'difficulty'                 => ['sometimes', 'in:easy,medium,hard'],
            'xp_value'                   => ['sometimes', 'integer'],
            'checked_by_human'           => ['sometimes', 'boolean'],
            'rejection_reason'           => ['nullable', 'string'],
            'options'                    => ['sometimes', 'array'],
            'options.*.option_text'      => ['required_with:options', 'string'],
            'options.*.option_text_bn'   => ['nullable', 'string'],
            'options.*.match_text'       => ['nullable', 'string'],
            'options.*.is_correct'       => ['sometimes', 'boolean'],
            'appearance'                 => ['sometimes', 'array'],
            'appearance.exam_type_id'    => ['required_with:appearance', 'integer', 'exists:exam_types,id'],
            'appearance.year'            => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'appearance.source_batch'    => ['nullable', 'string', 'max:50'],
        ]);

        // A blank rejection reason means "not rejected".
        if (array_key_exists('rejection_reason', $validated)) {
            $validated['rejection_reason'] = trim((string) $validated['rejection_reason']) ?: null;
        }

        if (!empty($validated['topic_id'])) {
            $examTypeSubjectId = $validated['exam_type_subject_id'] ?? $question->exam_type_subject_id;
            $this->assertTopicMatchesExamSubject($validated['topic_id'], $examTypeSubjectId);
        }

        $question->update(collect($validated)->except(['options', 'appearance'])->all());

        if (!empty($validated['appearance'])) {
            $question->appearances()->firstOrCreate([
                'exam_type_id' => $validated['appearance']['exam_type_id'],
                'year'         => $validated['appearance']['year'] ?? null,
            ], [
                'source_batch' => $validated['appearance']['source_batch'] ?? null,
                'created_by'   => $request->user()?->id,
            ]);
        }

        if ($request->has('options')) {
            $question->options()->delete();
            foreach ($request->input('options', []) as $sortOrder => $option) {
                $question->options()->create([
                    'option_text'    => $option['option_text'],
                    'option_text_bn' => $option['option_text_bn'] ?? null,
                    'match_text'     => $option['match_text'] ?? null,
                    'is_correct'     => (bool) ($option['is_correct'] ?? false),
                    'sort_order'     => $sortOrder,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data'    => $question->load(['options', 'appearances.examType']),
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

    /**
     * A question's topic must belong to the same Exam Subject as the question itself.
     */
    private function assertTopicMatchesExamSubject(int $topicId, int $examTypeSubjectId): void
    {
        $topic = Topic::find($topicId);

        if ($topic && $topic->exam_type_subject_id !== $examTypeSubjectId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'topic_id' => ["The selected topic does not belong to this question's Exam Subject."],
            ]);
        }
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
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
            'rows.*.topic'       => ['nullable', 'string', 'max:150'],
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

        // Per-exam-subject cache of topic name (lowercased/trimmed) → topic_id,
        // populated lazily so repeated topic names within the same import batch
        // don't create duplicate Topic rows.
        $topicMapByExamSubject = [];

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
            $topicRaw    = $this->sanitiseField($row['topic']       ?? '', 150);
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

            // ── Layer 7: Resolve Topic (optional, auto-create if missing) ──
            $topicId = null;
            if ($topicRaw !== '') {
                if (!isset($topicMapByExamSubject[$examTypeSubjectId])) {
                    $topicMapByExamSubject[$examTypeSubjectId] = Topic::where('exam_type_subject_id', $examTypeSubjectId)
                        ->get()
                        ->mapWithKeys(fn ($t) => [mb_strtolower(trim($t->name)) => $t->id])
                        ->toArray();
                }

                $topicKey = mb_strtolower(trim($topicRaw));
                $topicId  = $topicMapByExamSubject[$examTypeSubjectId][$topicKey] ?? null;

                if ($topicId === null) {
                    $newTopic = Topic::create([
                        'exam_type_subject_id' => $examTypeSubjectId,
                        'name'                  => $topicRaw,
                        'name_bn'               => $topicRaw,
                        'is_active'             => true,
                        'sort_order'            => 0,
                    ]);
                    $topicId = $newTopic->id;
                    $topicMapByExamSubject[$examTypeSubjectId][$topicKey] = $topicId;
                }
            }

            // ── Persist ───────────────────────────────────────────
            $q = Question::create([
                'exam_type_subject_id' => $examTypeSubjectId,
                'topic_id'              => $topicId,
                'question_text'        => $question,
                'question_bn'          => $question,
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
                    'option_text'    => $text,
                    'option_text_bn' => $text,
                    'is_correct'     => ($letter === $rightAnswer),
                    'sort_order'     => $sortOrder++,
                ]);
            }

            if ($request->filled('source_year') || $request->filled('source_batch')) {
                $q->appearances()->create([
                    'exam_type_id' => $examTypeId,
                    'year'         => $request->input('source_year'),
                    'source_batch' => $request->input('source_batch'),
                    'created_by'   => $request->user()?->id,
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

    public function destroyLesson(int $id): JsonResponse
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Lesson not found.', 'data' => null], 404);
        }

        $usageCount = Question::where('lesson_id', $id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: lesson has {$usageCount} question(s) attached. "
                           . 'Remove those first, or deactivate the lesson instead.',
                'data'    => null,
            ], 422);
        }

        $lesson->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lesson deleted successfully.',
            'data'    => null,
        ]);
    }

    // ─── Model Tests CRUD ─────────────────────────────────────────

    public function listModelTests(Request $request): JsonResponse
    {
        $modelTests = ModelTest::with('examType')->get()
            ->map(function ($mt) {
                return array_merge($mt->toArray(), [
                    'exam_type_name' => $mt->examType?->name,
                ]);
            });

        return response()->json([
            'success' => true,
            'data'    => $modelTests,
        ]);
    }

    public function storeModelTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_id'    => ['required', 'integer', 'exists:exam_types,id'],
            'title'           => ['required', 'string', 'max:200'],
            'title_bn'        => ['required', 'string', 'max:200'],
            'total_questions' => ['sometimes', 'integer'],
            'duration_minutes'=> ['sometimes', 'integer'],
            'xp_reward'       => ['sometimes', 'integer'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $modelTest = ModelTest::create($validated)->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Model test created successfully.',
            'data'    => array_merge($modelTest->toArray(), [
                'exam_type_name' => $modelTest->examType?->name,
            ]),
        ]);
    }

    public function updateModelTest(Request $request, int $id): JsonResponse
    {
        $modelTest = ModelTest::find($id);
        if (!$modelTest) {
            return response()->json(['success' => false, 'message' => 'Model test not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'exam_type_id'    => ['sometimes', 'integer', 'exists:exam_types,id'],
            'title'           => ['sometimes', 'string', 'max:200'],
            'title_bn'        => ['sometimes', 'string', 'max:200'],
            'total_questions' => ['sometimes', 'integer'],
            'duration_minutes'=> ['sometimes', 'integer'],
            'xp_reward'       => ['sometimes', 'integer'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $modelTest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Model test updated successfully.',
            'data'    => array_merge($modelTest->toArray(), [
                'exam_type_name' => $modelTest->examType?->name,
            ]),
        ]);
    }

    public function destroyModelTest(int $id): JsonResponse
    {
        $modelTest = ModelTest::find($id);
        if (!$modelTest) {
            return response()->json(['success' => false, 'message' => 'Model test not found.', 'data' => null], 404);
        }

        $sessionCount = ExamSession::where('model_test_id', $id)->count();
        if ($sessionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$sessionCount} exam session(s) reference this model test. "
                           . 'Deactivate it instead.',
                'data'    => null,
            ], 422);
        }

        $modelTest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Model test deleted successfully.',
            'data'    => null,
        ]);
    }
}
