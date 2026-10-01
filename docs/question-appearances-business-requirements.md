# Question Exam-Appearance Tracking — Business Requirements

## Problem

Exam question banks (BCS, NTRCA) reuse the same question across multiple years and sometimes
across exam types. Quizlo's bank had no way to record this. Two consequences:

1. **Students had no signal** that a question they were seeing had appeared before — e.g. in
   BCS-58 as well as BCS-60.
2. **Admins had no way to avoid re-entering the same question** when importing a new batch
   (e.g. BCS-60), so the same question could exist as multiple separate rows in the database
   with no relationship between them.

## Requirements

1. Record, for every question, every exam-type + year it has appeared in ("appearance").
2. Show a question's appearances to students on the mobile quiz screen, below-right of the
   question text (e.g. a "BCS-60" tag).
3. Give admins a **button-triggered** "Search Similar Questions" tool when creating a question
   (not live-as-you-type — the bank is large enough, ~20K rows, that per-keystroke search would
   be noisy and wasteful).
4. If an admin recognizes a suggested result as the same question, they tag it with the new
   exam+year appearance instead of creating a new question row — no duplicate is created.
5. One-time historical cleanup: research the exam-type+year of recently-added questions via web
   search, and deduplicate against the existing bank so it ends up with zero duplicate question
   rows — only appearance records for repeats.

## Out of scope for this feature

- **Fixing the pre-existing dangling `question_type`/`audio_url` (Question) and `match_text`
  (QuestionOption) fields.** These were already declared in the Eloquent models' `$fillable`
  arrays with no matching migration column — a genuine pre-existing bug that blocked *all*
  question creation with options, discovered while building this feature (see the security/
  design docs). It was fixed as a prerequisite because it blocked this feature end-to-end, not
  as part of the feature's own scope.
- **Consolidating the Flutter app's two `Question` model definitions.** The quiz screen uses a
  private, screen-local `_Question`/`_parseApiQuestion`, bypassing the "official"
  `core/domain/entities.dart` `Question` class (which is dead code for the live quiz screen).
  This feature only touches the local class actually in use; unifying the two is a separate
  refactor.
- **Normalizing `source_batch` into a lookup table.** It stays free text for now (matching the
  existing bulk-import UX). If duplicate-looking labels ("BCS-60" vs "BCS 60th") become a real
  problem after the historical backfill, a lookup table is the natural next step.

## Success criteria

- Admin can search for similar questions before creating a new one, and tag a match instead of
  duplicating it.
- A question's exam appearances are visible on the mobile question screen.
- The historical batch of recently-added questions has been reviewed, confirmed duplicates
  merged (only after human approval), and appearance data backfilled.
