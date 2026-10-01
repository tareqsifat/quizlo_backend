# Quizlo Question-Bank Verification — Handover

## Task
User asked: "check the all Question's answer, with independent checks and additional google search, make sure that, correct answer is actually correct, not any wrong information" — a full fact-check of every question's marked-correct answer in the DB. User explicitly chose scope: **entire bank (4,530 questions, both BCS Preliminary and NTRCA)**, not just NTRCA.

## What's done
- Exported all 4,530 questions (BCS: 2,959 + NTRCA: 1,571) into 84 batch files of ≤60 questions, grouped by `exam_type_subject_id`.
- Dispatched 84 background `general-purpose` agents (one per batch), each independently fact-checking every question using own knowledge + WebSearch (WebSearch budget ran out for most agents partway through, so many relied on internal knowledge only for later items — they were instructed to skip rather than guess when unsure, and self-reported when this happened).
- **All 84 batches now have a `verify_corrections/<batch>.json` file** (flagged-only items, empty array if none found). One batch (`ntrca_general_science_batch6`) failed with a session-limit API error mid-run but had already written `[]` (0 flagged out of 32) before dying — **treat this one batch's result as low-confidence / re-verify it** rather than trusting the empty result.
- Aggregated all flagged items into one file: **`aggregate_corrections.json`** (143 flagged items total across all subjects), via `aggregate_corrections.php` (both now in `C:\laragon\www\quizlo_backend\`).

## Results summary (143 flagged out of 4,530 checked)
| Exam | Subject | Flagged/Total |
|---|---|---|
| bcs | Bangla Literature | 24/748 |
| bcs | Bangladesh Affairs | 20/350 |
| bcs | Computer and Information Technology | 2/37 |
| bcs | English Grammar | 37/744 |
| bcs | Ethics, Values and Good Governance | 1/28 |
| bcs | General Science | 2/216 |
| bcs | Geography, Environment and Disaster Management | 5/75 |
| bcs | International Affairs | 9/431 |
| bcs | Mathematics | 3/293 |
| bcs | Mental Ability | 1/37 |
| ntrca | Bangla | 12/373 |
| ntrca | English | 10/304 |
| ntrca | General Knowledge | 4/321 |
| ntrca | General Math | 0/181 |
| ntrca | General Science | 13/392 (incl. batch6 = low-confidence 0) |

## File locations
- Scratchpad base: `C:/Users/FAISAL/AppData/Local/Temp/claude/C--laragon-www/f88ac5bf-f050-4ab3-8a93-6f9eb04cb8fa/scratchpad`
  - `verify_batches/*.json` — 84 input batches + `manifest.json` (file→subject/exam/count mapping)
  - `verify_corrections/*.json` — 84 output files, flagged-only items
  - `aggregate_corrections.json` — all 143 flagged items merged, each tagged with `batch`, `subject`, `exam`
- `C:\laragon\www\quizlo_backend\export_all.php` — the export script (already run, no need to rerun unless bank changes)
- `C:\laragon\www\quizlo_backend\aggregate_corrections.php` — the aggregation script (already run; rerun anytime after re-verifying a batch)

## DB schema reminders (for applying fixes)
- `questions`: id, exam_type_subject_id, question_text, explanation, ... (no `right_answer` column)
- `question_options`: id, question_id, option_text, is_correct, sort_order — **correctness lives here**, one row per option
- `exam_type_subjects`: id, exam_type_id, title — subject display name is here, NOT on `subjects` table
- Fix mechanism: `DB::table('question_options')->where('id', $id)->update(['is_correct' => 0/1])` via `php artisan tinker --execute="..."` from `C:/laragon/www/quizlo_backend`. Remember to flip the *previously*-correct option to 0 when setting a new one to 1 (only one option per question should have is_correct=1).

## NOT yet done — pick up here
1. **Spot-check the 143 flagged items** in `aggregate_corrections.json` — read through them (they're already broken out by subject/reason), and for anything not obviously solid, do an independent check (own knowledge / WebSearch) before trusting an agent's flag. Many agents flagged with "moderate-high but not search-verified confidence" since WebSearch quota ran out for most of them — don't blindly apply every flag.
2. **Re-verify `ntrca_general_science_batch6`** (32 questions) — its correction file is `[]` but the agent that produced it was killed by an API session-limit error mid-run, so this result is unreliable. Re-dispatch a fresh check of `verify_batches/ntrca_general_science_batch6.json`.
3. **Apply confirmed corrections** to `question_options.is_correct` via tinker, one by one or in a small script, only for items you've verified are genuinely wrong.
4. **Report back to the user**: total checked (4,530), total flagged (143), total confirmed-and-fixed (TBD), broken down by subject, with an honest caveat that most agents had exhausted WebSearch budget partway through and fell back to internal-knowledge-only verification for later items in their batch (they skipped flagging anything they weren't highly confident about, rather than guessing).

## Known limitations to disclose
- WebSearch session budget ran out for the large majority of the 84 agents partway through their batch — they explicitly said so in their completion summaries and fell back to internal-knowledge verification, skipping (not guessing on) borderline items. This means some real errors may have been missed, especially recent/current-affairs facts (officeholders, treaties, statistics) that need a live search to confirm.
- This was a *flag-only-high-confidence* pass by design (per user's earlier standing instruction: no fabrication, only genuine corrections, skip if uncertain) — so 143 flagged is a conservative floor, not an exhaustive list of every possible error.

## Suggested prompt to paste in the next chat
> Continue the Quizlo question-bank verification task. Read `C:\laragon\www\quizlo_backend\handOver.md` for full context — it explains what's done (84/84 batches fact-checked, 143 flagged items aggregated into `aggregate_corrections.json` in the scratchpad) and what's left (spot-check the flags, re-verify the one low-confidence batch `ntrca_general_science_batch6`, apply confirmed fixes to `question_options.is_correct` via tinker, then report a final summary to me).
