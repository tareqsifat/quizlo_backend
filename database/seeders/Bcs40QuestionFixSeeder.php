<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\Question;
use App\Support\BanglaText;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-off data fix (2026-10-30), safe to run more than once:
 *   php artisan db:seed --class=Bcs40QuestionFixSeeder --force
 *
 * 1. Repairs broken Bangla character sequences everywhere (quizlo:fix-bangla-text) and six typos.
 * 2. Cleans 10 questions whose options swallowed the next questions during import; the 7 that
 *    cannot be made correct without the original paper are deactivated.
 * 3. Aligns two BCS-40 answer keys with the published 40th BCS solution.
 * 4. Removes the BCS-40 tag from 32 questions that are not in the 40th BCS paper.
 * 5. Tags 4 existing questions as BCS-40 and adds the 34 paper questions that were missing.
 *
 * Rows are matched by text, not id, so it works on databases whose ids differ.
 */
class Bcs40QuestionFixSeeder extends Seeder
{
    private const BATCH = 'BCS-40';
    private const YEAR = 2019;

    private const TEXT_COLUMNS = [
        'questions'        => ['question_text', 'question_bn', 'explanation'],
        'question_options' => ['option_text', 'option_text_bn'],
    ];

    /** Typos the generic repair rules cannot fix safely. */
    private const TYPOS = [
        "সাহিতে\u{200D}\u{09CD}যর" => 'সাহিত্যের',
        'প্রকিাশিত'                => 'প্রকাশিত',
        'চেীধুরী'                  => 'চৌধুরী',
        'সুী গঙ্গোপাধ্যা'           => 'সুনীল গঙ্গোপাধ্যা',
        'প্লািইস্টোসিন'             => 'প্লাইস্টোসিন',
        'এন্ডুারিং'                 => 'এন্ডুরিং',
    ];

    /**
     * Options that contain the following question(s) pasted after the real option text.
     * fragment => [clean option text (null = keep the first line), deactivate the question?]
     */
    private const PASTED_OPTIONS = [
        '৯৫. যে নারীর হাসি পবিত্র'      => ['নাথবতী', false],
        'সেলিম আল দীনের নাটকে অনুসৃত'   => ['অদ্ভুত আঁধার এক', false],
        'কমিটির একমাত্র নারী সদস্য'     => ['৯', false],
        '২৪। The antonym of'            => ['There are trees on either side of the road.', true],   // duplicate options
        'CONIC শব্দটির'                 => [null, true],   // correct answer (8π) is not an option
        'থলিতে ১টি নীল'                 => [null, true],   // options belong to other questions
        'একটি সিলিন্ডারের বৃত্ত'         => ['41', true],   // no option is correct (answer is 29)
        'Ubiquitous'                    => ['৪৫', true],   // answer key: question is ill-posed
        'শুদ্ধাচার কৌশল নীতিমালা'        => ['২৪', true],   // answer key: no option is correct
    ];

    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/data/bcs40_fixes.json'), true, flags: JSON_THROW_ON_ERROR);
        $bcs = ExamType::where('code', 'BCS')->firstOrFail();

        $this->command?->call('quizlo:fix-bangla-text');

        DB::transaction(function () use ($data, $bcs) {
            $this->fixTypos();
            $this->cleanPastedOptions();
            $this->fixAnswerKeys();
            $this->untag($data['untag']);
            $this->tagExisting($data['tag_existing'], $bcs);
            $this->addMissing($data['new_questions'], $bcs);
        });
    }

    private function fixTypos(): void
    {
        $count = 0;
        foreach (self::TEXT_COLUMNS as $table => $columns) {
            foreach (self::TYPOS as $broken => $fixed) {
                $rows = DB::table($table)->where(function ($q) use ($columns, $broken) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', '%' . $broken . '%');
                    }
                })->get(array_merge(['id'], $columns));

                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        if ($row->$column !== null && str_contains($row->$column, $broken)) {
                            $updates[$column] = str_replace($broken, $fixed, $row->$column);
                        }
                    }
                    if ($updates) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                        $count++;
                    }
                }
            }
        }
        $this->say("Typos fixed: {$count} rows");
    }

    private function cleanPastedOptions(): void
    {
        $cleaned = $deactivated = 0;

        foreach (self::PASTED_OPTIONS as $fragment => [$clean, $deactivate]) {
            foreach ($this->pastedOptions($fragment) as $option) {
                $text = $clean ?? trim(strtok($option->option_text, "\n"));
                DB::table('question_options')->where('id', $option->id)->update(['option_text' => $text, 'option_text_bn' => $text]);
                $cleaned++;
                if ($deactivate) {
                    $deactivated += DB::table('questions')->where('id', $option->question_id)->update(['is_active' => false]);
                }
            }
        }

        // "বাংলাদেশের নবীনতম নদী কোনটি?" — all four options were shifted; restore them, keep it hidden until the answer is reviewed.
        foreach ($this->pastedOptions('মুজিব: একটি জাতির রূপকার') as $option) {
            foreach (['পদ্মা', 'যমুনা', 'জিঞ্জিরাম', 'মেঘনা'] as $sortOrder => $text) {
                DB::table('question_options')->where('question_id', $option->question_id)->where('sort_order', $sortOrder)
                    ->update(['option_text' => $text, 'option_text_bn' => $text]);
            }
            $deactivated += DB::table('questions')->where('id', $option->question_id)->update(['is_active' => false]);
            $cleaned++;
        }

        $this->say("Pasted-in options cleaned: {$cleaned}, questions deactivated: {$deactivated}");
    }

    /** Options containing $fragment that still carry pasted text (a line break or an "উত্তর:" note). */
    private function pastedOptions(string $fragment)
    {
        return DB::table('question_options')
            ->where('option_text', 'like', '%' . $fragment . '%')
            ->where(fn ($q) => $q->where('option_text', 'like', "%\n%")->orWhere('option_text', 'like', '%উত্তর:%'))
            ->get(['id', 'question_id', 'option_text'])
            ->filter(fn ($o) => str_contains($o->option_text, $fragment));
    }

    private function fixAnswerKeys(): void
    {
        $count = 0;

        foreach ($this->bcs40Questions('মৌর্য শাসনের প্রতিষ্ঠাতা') as $question) {
            $count += $this->setCorrect($question, fn ($o) => trim($o->option_text) === 'অশোক মৌর্য');
            $question->update(['explanation' => '৪০তম বিসিএস-এর প্রকাশিত সমাধান অনুযায়ী উত্তর অশোক মৌর্য। তবে মৌর্য সাম্রাজ্যের প্রতিষ্ঠাতা চন্দ্রগুপ্ত মৌর্য, তাই কিছু সূত্রে এই প্রশ্নের উত্তর চন্দ্রগুপ্ত মৌর্য দেওয়া হয়।']);
        }

        foreach ($this->bcs40Questions('থেকে হংকং') as $question) {
            DB::table('question_options')->where('question_id', $question->id)->where('sort_order', 3)
                ->update(['option_text' => 'অন্য কোন কারণ আছে', 'option_text_bn' => 'অন্য কোন কারণ আছে']);
            $count += $this->setCorrect($question, fn ($o) => (int) $o->sort_order === 3);
            $question->update(['explanation' => 'পৃথিবী পশ্চিম থেকে পূর্ব দিকে ঘোরে। ঢাকা থেকে হংকং হয়ে নিউইয়র্ক যাওয়ার সময় প্লেন পূর্ব দিকে, অর্থাৎ পৃথিবীর ঘূর্ণনের দিকেই যায়, তাই সূর্যের আপেক্ষিক অবস্থান দ্রুত বদলায় এবং দিন ছোট মনে হয়। প্রথম তিনটি কারণের কোনোটিই সঠিক নয়, তাই উত্তর: অন্য কোন কারণ আছে।']);
        }

        $this->say("Answer keys corrected: {$count} questions");
    }

    private function bcs40Questions(string $fragment)
    {
        return Question::where('question_text', 'like', '%' . $fragment . '%')
            ->whereHas('appearances', fn ($a) => $a->where('source_batch', self::BATCH))
            ->get()
            ->filter(fn ($q) => str_contains($q->question_text, $fragment));
    }

    /** Marks exactly the matching option as correct; returns 1 if anything changed. */
    private function setCorrect(Question $question, callable $isCorrect): int
    {
        $changed = 0;
        foreach (DB::table('question_options')->where('question_id', $question->id)->get() as $option) {
            $correct = (bool) $isCorrect($option);
            if ((bool) $option->is_correct !== $correct) {
                DB::table('question_options')->where('id', $option->id)->update(['is_correct' => $correct]);
                $changed = 1;
            }
        }

        return $changed;
    }

    private function untag(array $texts): void
    {
        $removed = DB::table('question_exam_appearances')
            ->where('source_batch', self::BATCH)
            ->whereIn('question_id', DB::table('questions')->whereIn('question_text', $texts)->select('id'))
            ->delete();

        $this->say("BCS-40 tags removed from questions not in the paper: {$removed}");
    }

    private function tagExisting(array $texts, ExamType $bcs): void
    {
        $tagged = 0;
        foreach ($texts as $text) {
            $question = Question::where('question_text', $text)->whereNull('merged_into_question_id')->orderBy('id')->first();
            if (!$question) {
                $this->say("  not found, skipped tagging: {$text}", 'warn');
                continue;
            }
            if (!$question->appearances()->where('source_batch', self::BATCH)->exists()) {
                $question->appearances()->create(['exam_type_id' => $bcs->id, 'year' => self::YEAR, 'source_batch' => self::BATCH]);
                $tagged++;
            }
        }
        $this->say("Existing questions tagged BCS-40: {$tagged}");
    }

    private function addMissing(array $questions, ExamType $bcs): void
    {
        $subjects = ExamTypeSubject::where('exam_type_id', $bcs->id)->with('subject')->get()
            ->mapWithKeys(fn ($ets) => [$ets->subject->name => $ets->id]);

        $added = 0;
        foreach ($questions as $item) {
            $text = BanglaText::fix($item['question']);

            $exists = Question::where('question_text', $text)
                ->whereHas('appearances', fn ($a) => $a->where('source_batch', self::BATCH))
                ->exists();
            if ($exists) {
                continue;
            }

            $question = Question::create([
                'exam_type_subject_id' => $subjects[$item['subject']] ?? throw new \RuntimeException("BCS subject not found: {$item['subject']}"),
                'question_type'        => 'mcq',
                'question_text'        => $text,
                'question_bn'          => $text,
                'explanation'          => $item['explanation'],
                'difficulty'           => 'medium',
                'xp_value'             => 10,
            ]);

            foreach ($item['options'] as $sortOrder => $option) {
                $option = BanglaText::fix($option);
                $question->options()->create([
                    'option_text'    => $option,
                    'option_text_bn' => $option,
                    'is_correct'     => $sortOrder === $item['answer'],
                    'sort_order'     => $sortOrder,
                ]);
            }

            $question->appearances()->create(['exam_type_id' => $bcs->id, 'year' => self::YEAR, 'source_batch' => self::BATCH]);
            $added++;
        }
        $this->say("Missing 40th BCS questions added: {$added}");
    }

    private function say(string $message, string $style = 'info'): void
    {
        $this->command?->{$style}($message);
    }
}
