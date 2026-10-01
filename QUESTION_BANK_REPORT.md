# Quizlo Question Bank Report

_Regenerated 2026-09-26 from the local `quizlo` MySQL database, after the top-up import that brought the bank to 10,000 questions._

## 1. Summary

| Metric | Count |
|---|---:|
| Question rows in `questions` | **10,072** |
| Merged duplicates (`merged_into_question_id` set, all inactive) | 72 |
| **Live, usable questions** | **10,000** ✅ target reached |
| BCS (live) | **5,192** (51.9%) |
| NTRCA (live) | **4,808** (48.1%) |
| Subjects (exam-subject pairs) | 15 (BCS 10, NTRCA 5) |
| Questions with an explanation | 4,585 (45.5%) |
| Live questions with no past-exam source | 3,262 (32.6%) |

Every question is an MCQ. All 72 inactive questions are merged duplicates, so no question is inactive for any other reason.

### What changed since the last report

| | Before | Added | Now |
|---|---:|---:|---:|
| BCS | 4,891 | +301 | 5,192 |
| NTRCA | 3,267 | +1,541 | 4,808 |
| **Total live** | **8,158** | **+1,842** | **10,000** |

The 1,842 new questions (IDs 8408–10249) came from chakribangla.com's BCS model tests, NTRCA model tests and subject-wise practice pages. The model tests themselves added almost nothing new because nearly all of their questions were already in the bank. Each candidate passed three duplicate checks before it was imported, and a scan after the import found **0 duplicates within an exam**. Details are in section 7.

## 2. By exam

| Exam | Rows | Merged | Live | With explanation | No source (live) |
|---|---:|---:|---:|---:|---:|
| BCS Preliminary | 5,251 | 59 | **5,192** | 2,294 (43.7%) | 902 (17.4%) |
| NTRCA | 4,821 | 13 | **4,808** | 2,291 (47.5%) | 2,360 (49.1%) |
| **Total** | **10,072** | **72** | **10,000** | **4,585 (45.5%)** | **3,262 (32.6%)** |

NTRCA's "no source" count jumped because practice-page and model-test questions come from no real exam paper, so they are correctly left without a year/batch.

## 3. BCS by subject

"Exam weight" uses the standard 200-mark BCS Preliminary syllabus.

| # | Subject | Live Qs | Added | Share of BCS bank | Exam weight | With explanation | No source |
|---|---|---:|---:|---:|---:|---:|---:|
| 1 | Bangla Literature | 1,189 | +62 | 22.9% | 35 (17.5%) | 454 (37.8%) | 184 |
| 2 | English Grammar | 1,050 | 0 | 20.2% | 35 (17.5%) | 309 (29.4%) | 203 |
| 3 | Bangladesh Affairs | 711 | +46 | 13.7% | 30 (15%) | 372 (51.5%) | 55 |
| 4 | International Affairs | 671 | +25 | 12.9% | 20 (10%) | 258 (37.4%) | 143 |
| 5 | Mathematics | 481 | +29 | 9.3% | 15 (7.5%) | 195 (40.0%) | 121 |
| 6 | General Science | 383 | +17 | 7.4% | 15 (7.5%) | 172 (44.3%) | 85 |
| 7 | Computer & IT | 200 | +17 | 3.9% | 15 (7.5%) | 163 (81.5%) | 16 |
| 8 | Geography, Environment & Disaster Mgmt | 179 | +29 | 3.4% | 10 (5%) | 108 (59.0%) | 15 |
| 9 | Mental Ability | 177 | +34 | 3.4% | 15 (7.5%) | 140 (79.1%) | 53 |
| 10 | Ethics, Values & Good Governance | 151 | +42 | 2.9% | 10 (5%) | 123 (81.5%) | 27 |
| | **Total** | **5,192** | **+301** | 100% | 200 | **2,294** | **902** |

**Coverage gaps compared with exam weight:** Computer & IT, Mental Ability, Ethics and Geography are still at roughly half the share their exam weight suggests, although each grew by 10–39%. English Grammar got nothing new because every English question the source had was already in the DB.

## 4. NTRCA by subject

| # | Subject | Live Qs | Added | Share of NTRCA bank | With explanation | No source |
|---|---|---:|---:|---:|---:|---:|
| 1 | General Knowledge | 1,204 | +400 | 25.0% | 563 (46.8%) | 531 |
| 2 | Bangla | 1,170 | +450 | 24.3% | 416 (35.2%) | 628 |
| 3 | General Math | 1,015 | +323 | 21.1% | 757 (74.6%) | 419 |
| 4 | General Science | 760 | +368 | 15.8% | 198 (26.1%) | 750 |
| 5 | English | 659 | 0 | 13.7% | 357 (54.0%) | 32 |
| | **Total** | **4,808** | **+1,541** | 100% | **2,291** | **2,360** |

NTRCA General Science nearly doubled: questions from the science practice pages were routed here instead of into General Knowledge. English is now the smallest NTRCA subject, and the source had nothing new for it.

## 5. All subjects ranked (both exams)

| Rank | Exam | Subject | Live Qs |
|---:|---|---|---:|
| 1 | NTRCA | General Knowledge | 1,204 |
| 2 | BCS | Bangla Literature | 1,189 |
| 3 | NTRCA | Bangla | 1,170 |
| 4 | BCS | English Grammar | 1,050 |
| 5 | NTRCA | General Math | 1,015 |
| 6 | NTRCA | General Science | 760 |
| 7 | BCS | Bangladesh Affairs | 711 |
| 8 | BCS | International Affairs | 671 |
| 9 | NTRCA | English | 659 |
| 10 | BCS | Mathematics | 481 |
| 11 | BCS | General Science | 383 |
| 12 | BCS | Computer & IT | 200 |
| 13 | BCS | Geography, Environment & Disaster Mgmt | 179 |
| 14 | BCS | Mental Ability | 177 |
| 15 | BCS | Ethics, Values & Good Governance | 151 |

## 6. Past-exam sources (`question_exam_appearances`)

| Exam | Appearance rows | Distinct questions | Batches | Year range |
|---|---:|---:|---:|---|
| BCS | 4,709 (+239) | 4,290 | 40 (BCS-10 → BCS-50) | 1990 – 2026 (268 rows have no year) |
| NTRCA | 2,552 | 2,448 | 27 (NTRCA 10th – 18th; School / School-2 / College) | 2014 – 2024 |

- **New appearance rows:** the 239 new rows come only from BCS practice-page questions labelled with a real past paper (35th–47th BCS). Model-test and untagged practice questions got none.
- **Repeated questions:** 477 questions appeared in more than one exam (434 twice, 40 three times, 3 four times).
- **Missing BCS batch:** BCS-19 is still absent. BCS-39, 42, 48 and 49 are "special" batches.
- **Older BCS papers are partial:** papers up to BCS-34 have only about 56–96 appearance rows each, not a full 200.

### Appearances per year

| BCS year | Rows | | BCS year | Rows | | NTRCA year | Rows |
|---|---:|---|---|---:|---|---|---:|
| 1990 | 77 | | 2012 | 151 | | 2014 | 573 |
| 1991 | 81 | | 2013 | 73 | | 2015 | 283 |
| 1992 | 160 | | 2015 | 164 | | 2016 | 281 |
| 1993 | 170 | | 2016 | 404 | | 2017 | 280 |
| 1995 | 177 | | 2017 | 215 | | 2019 | 570 |
| 1997 | 69 | | 2018 | 60 | | 2022 | 287 |
| 1999 | 82 | | 2019 | 181 | | 2024 | 278 |
| 2001 | 73 | | 2021 | 525 | | | |
| 2003 | 146 | | 2022 | 209 | | | |
| 2005 | 93 | | 2023 | 219 | | | |
| 2006 | 85 | | 2024 | 219 | | | |
| 2008 | 85 | | 2025 | 356 | | | |
| 2009 | 57 | | 2026 | 196 | | | |
| 2011 | 114 | | _unknown_ | 268 | | | |

## 7. Duplicate safety (top-up import)

| Layer | What it does | Removed |
|---|---|---:|
| 1. Match against the DB | Normalized/fuzzy text plus option overlap, same-answer rule, and guards for different numbers and "নয়"/"not" | 3,431 already in the DB, 216 repeated in the harvest, 187 borderline |
| 2. Strict reworded-question check | Same correct answer with a similar question (word-overlap ≥ 70 or partial ≥ 80), or partly matching question (≥ 90) with ≥ 2 shared options | 484 |
| 3. Loose "anything resembling" check | Word-overlap ≥ 80, or one answer contained in the other with word-overlap ≥ 60 | 1,130 |
| Post-import scan of all 10,000 | Identical question text, or ≥ 90 similarity with ≥ 3 shared options or the same answer, within an exam | **0 found** |

- **Identical-text groups** grew from 65 to 74. All 9 new groups are **cross-exam pairs** with one BCS row and one NTRCA row, e.g. "বংশগতির জনক কে?" in both BCS and NTRCA General Science. The schema intends this, since each question belongs to one exam. The other 65 groups predate the import.
- **Rejected look-alikes** are logged in `research_state/topup_2026-09-26/suspects.json` and `loose_rejected.json` for manual review. Some are genuinely new questions that were skipped to be safe.

## 8. Data-quality issues

| Issue | Affected | Severity |
|---|---:|---|
| No topic assigned (`topic_id` NULL) | 10,070 / 10,072. Only 2 topics exist | 🔴 High: topic-based learning has no data yet |
| Difficulty not graded: all `medium` except 1 `easy` | 10,071 | 🟠 Medium: adaptive difficulty can't work |
| No explanation | 5,487 (54.5%). Worst: NTRCA General Science 26%, BCS English 29% | 🟠 Medium |
| New practice-page questions are written tersely (e.g. "বিজয় দিবস? → ১৬ ডিসে") | about 1,500 of the 1,842 new questions | 🟡 Low: correct, but less polished than past-paper questions |
| `question_bn` is identical to `question_text` | 10,070 | 🟡 Low: the column duplicates the text and holds no separate translation |
| Live questions with no past-exam source | 3,262. Mostly model-test and practice questions, which is expected | 🟡 Info |
| Identical text within an exam (from before the top-up) | 65 groups | 🟡 Review |
| MCQ with **no correct option** | 1 (Q #3441, BCS Math, 3 options) | 🔴 Fix: this question can't be answered |
| MCQ with only 3 options | 4 (Q #3441, #3584, #4535 BCS Math; #829 NTRCA Math) | 🟡 Review |
| BCS appearances without a year | 268 rows | 🟡 Low |
| Answer disagreements with the source site | 57 existing questions (see `research_state/topup_2026-09-26/answer_conflicts.json`; the DB was not changed) | 🟡 Review |

## 9. Import timeline (`questions.created_at`, stored in UTC)

| Date (UTC) | Questions added | Batch |
|---|---:|---|
| 2026-08-23 | 108 | initial seed |
| 2026-08-24 | 664 | bulk upload |
| 2026-08-25 | 3,758 | bulk upload |
| 2026-09-25 | 3,700 | past-paper harvest (IDs 4631–8407) |
| 2026-09-25 | 1,842 | top-up to 10k (IDs 8408–10249) |

## 10. Other tables

| Table | Rows |
|---|---:|
| topics | 2 |
| lessons | 2 |
| users | 7 |
| user_answers | 0 |
| model_tests | 0 |

## 11. Recommended next steps

1. Fix Q #3441, which has no correct answer, and check the three other questions that have only 3 options.
2. Tag questions with topics. This is the biggest gap. The practice-page source URLs (e.g. `.../bangla/somas.html`, `.../general-knowledge/network-and-internet.html`) already map the new questions to topics and could seed this.
3. Grade difficulty, either by hand or later from `user_answers` accuracy.
4. Write explanations, starting with NTRCA General Science (26%), BCS English (29%) and NTRCA Bangla (35%).
5. Find a new source for English questions: neither exam gained any in this top-up.
6. Add more BCS Computer & IT, Mental Ability, Ethics and Geography questions to match exam weight.
7. Review the 65 older identical-text groups and the 57 answer disagreements.
8. Push to production. Everything so far is on the **local DB only**. Backups: `storage/app/backup_quizlo_before_topup_2026-09-26.sql` (and the earlier `backup_quizlo_before_harvest_import_2026-09-26.sql`).
