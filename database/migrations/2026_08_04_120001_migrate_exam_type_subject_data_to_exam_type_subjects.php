<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time data migration: exam_type_subject (legacy singular pivot)
     * -> exam_type_subjects (plural, now the single source of truth).
     *
     * For pairs that already exist in exam_type_subjects (matched on
     * exam_type_id + subject_id), only the columns exam_type_subjects
     * didn't have before (sort_order, total_marks, syllabus_note) are
     * backfilled — title/title_bn/is_active on that side are left alone
     * since exam_type_subjects is authoritative for those.
     *
     * For pairs that only exist in the legacy table, a new row is created,
     * defaulting title/title_bn to the subject's own name since the legacy
     * pivot never had a per-exam-type display title.
     *
     * This migration does not drop exam_type_subject — that table is
     * removed by hand once the data here is verified.
     */
    public function up(): void
    {
        if (!Schema::hasTable('exam_type_subject')) {
            // Legacy table already removed (e.g. fresh install) — nothing to migrate.
            return;
        }

        $subjectNames = DB::table('subjects')->pluck('name', 'id');
        $subjectNamesBn = DB::table('subjects')->pluck('name_bn', 'id');

        $legacyRows = DB::table('exam_type_subject')->get();

        foreach ($legacyRows as $legacy) {
            $existing = DB::table('exam_type_subjects')
                ->where('exam_type_id', $legacy->exam_type_id)
                ->where('subject_id', $legacy->subject_id)
                ->first();

            if ($existing) {
                DB::table('exam_type_subjects')
                    ->where('id', $existing->id)
                    ->update([
                        'sort_order'    => $legacy->sort_order,
                        'total_marks'   => $legacy->total_marks,
                        'syllabus_note' => $legacy->syllabus_note,
                    ]);

                continue;
            }

            $title = $subjectNames[$legacy->subject_id] ?? "Subject #{$legacy->subject_id}";

            // Guard the (exam_type_id, title) unique constraint in case another
            // subject under the same exam type already uses this exact title.
            $titleTaken = DB::table('exam_type_subjects')
                ->where('exam_type_id', $legacy->exam_type_id)
                ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($title))])
                ->exists();

            if ($titleTaken) {
                $title .= " ({$legacy->subject_id})";
            }

            DB::table('exam_type_subjects')->insert([
                'exam_type_id'  => $legacy->exam_type_id,
                'subject_id'    => $legacy->subject_id,
                'title'         => $title,
                'title_bn'      => $subjectNamesBn[$legacy->subject_id] ?? $title,
                'is_active'     => $legacy->is_active,
                'sort_order'    => $legacy->sort_order,
                'total_marks'   => $legacy->total_marks,
                'syllabus_note' => $legacy->syllabus_note,
                'created_at'    => $legacy->created_at,
                'updated_at'    => $legacy->created_at,
            ]);
        }
    }

    public function down(): void
    {
        // Data-only migration; nothing to reverse. The legacy table (if still
        // present) still holds the original values.
    }
};
