# Question Exam-Appearance Tracking — Design & Failure Modes

## Data model

`question_exam_appearances`: `question_id`, `exam_type_id`, `year` (nullable), `source_batch`
(nullable, free text), `created_by` (nullable, audit trail), unique on
`(question_id, exam_type_id, year)`.

`questions.merged_into_question_id` (nullable, self-referencing FK): set on a question that was
found to be a duplicate during the historical dedup pass and merged into a canonical question.
Used for soft-delete-with-explanation rather than hard delete (see "Merge semantics" below).

## Failure modes and mitigations

**Admin ties a wrong appearance to a question.**
Mitigated by `DELETE /admin/questions/{question}/appearances/{appearance}`, exposed in the admin
edit dialog as a removable chip. Nothing about appearance-tagging is otherwise reversible from
the UI, so this endpoint is the only recovery path — it must keep working.

**Two admins tag the same question with the same appearance concurrently.**
`POST /appearances` uses `firstOrCreate`, and the unique index on
`(question_id, exam_type_id, year)` is the actual backstop: a `QueryException` from the race is
caught and the existing row is returned instead of a 500. Both requests succeed with the same
end state.

**Near-duplicate-but-actually-different questions** (same stem, different correct answer or
option wording across years — this happens in real exam banks when a question is reused with a
twist).
The only safeguard is the admin actually reading the candidate before clicking "this is the same
question" — so `search-similar` returns full question text *and* all options (not just a
snippet), and the UI renders them for comparison. This is a human-judgment control, not an
automated one; get it wrong and a genuinely different question silently becomes an "appearance"
of the wrong canonical question. There is no automated detection of this failure mode.

**Meilisearch index falls out of sync with MySQL.**
Scout is configured `SCOUT_QUEUE=false` (synchronous) specifically to avoid this — a
`Question::create()`/`update()`/`delete()` indexes synchronously as part of the same request, so
there's no queue-worker dependency to fail silently. If a bulk operation is done outside Eloquent
(e.g. a raw DB update) the index will drift; the fix is `php artisan scout:import
"App\Models\Question"` to force a full re-sync. `shouldBeSearchable()` excludes soft-deleted
(`merged_into_question_id != null`) and inactive questions, so merged/deactivated questions stop
surfacing as "similar" matches going forward.

**Meilisearch service is down or unreachable.**
The `search-similar` endpoint depends on Meilisearch; base question creation does not. If the
search service is down, the admin should still be able to create a question directly — the
"Search Similar Questions" button failing should never block the Save button. (The Vue admin's
`openSearchSimilarDialog` catches and logs errors rather than throwing; verify this stays true if
the UI is refactored — a failed search must fail open, not block question creation.)

**Merge operation's FK re-pointing correctness (historical dedup pass).**
When a duplicate is confirmed and merged into a canonical question:
1. `QuestionExamAppearance` rows move from the duplicate to the canonical question (skipped if
   the canonical already has that exact `(exam_type_id, year)` — the unique constraint prevents
   a collision either way).
2. Any other FK referencing the duplicate's `questions.id` (at minimum `user_answers.question_id`
   and `model_test_questions.question_id`) must be re-pointed. **Do not assume this list is
   exhaustive** — grep the live schema for `question_id` foreign keys at merge-execution time,
   since new question-referencing tables may have been added since this doc was written.
3. The duplicate is **soft-deleted**, not hard-deleted: `is_active = false` and
   `merged_into_question_id` set to the canonical question's id. This keeps "why did question #X
   disappear" answerable later — a hard delete would silently break any external reference or
   audit trail to that id.
4. The duplicate is removed from the Meilisearch index (`shouldBeSearchable()` returning false
   for a merged question means the next reindex drops it automatically; call `->unsearchable()`
   explicitly at merge time rather than waiting for the next reindex).

**Nothing merges without a human.** The historical dedup pass's WebSearch agents only *propose*
duplicate pairs with evidence; no merge SQL runs until the user has explicitly reviewed and
approved each candidate (or an explicit batch). This is a hard requirement, not a convenience —
merging is destructive (soft-delete) and hard to fully undo (re-pointed FKs are not
automatically reversible).

## Known pre-existing issue found (and fixed) while building this

`Question::$fillable` already listed `question_type`/`audio_url`, and
`QuestionOption::$fillable` already listed `match_text`, with no migration ever creating those
columns. Any `storeQuestion`/`updateQuestion` call that touched options failed with a database
error — in production, not just in tests. This blocked the appearance feature's own "create a
new question with a tag" path, so a migration
(`2026_09_01_000002_add_missing_columns_for_question_type_audio_match.php`) was added to close
the gap. This was a genuine live bug, not scope creep for this feature — it's flagged here so
the fix's origin is traceable.
