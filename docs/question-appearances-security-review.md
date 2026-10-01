# Question Exam-Appearance Tracking — Security Review

## Authentication & authorization

All four new routes (`GET /admin/questions/search-similar`,
`GET /admin/questions/{question}/appearances`, `POST .../appearances`,
`DELETE .../appearances/{appearance}`) are registered inside the existing
`Route::middleware(['auth:api', 'admin'])` group in `app/Modules/Admin/Routes/api.php` — same
gate as every other admin endpoint in this codebase. No new middleware was invented.

Verified by test, not just by reading the route file:
`QuestionAppearanceTest::test_search_similar_requires_admin_auth` asserts both an unauthenticated
request (401) and a request from a regular (`user` scope) token (403) are rejected.

## Rate limiting

`search-similar` carries `throttle:30,1` (30 requests/minute per the standard Laravel rate
limiter keying). Rationale: unlike typical CRUD, this endpoint is button-triggered but still
invokes an external search service (Meilisearch) — expensive relative to a normal query, and
worth bounding even though it isn't hit on every keystroke.

## Mass assignment

Both the new `store` appearance endpoint and the `storeQuestion`/`updateQuestion` extensions use
either dedicated Form Requests (`StoreQuestionAppearanceRequest`,
`SearchSimilarQuestionsRequest`) or an inline `$request->validate([...])` allowlist, matching the
existing controller's convention. Nowhere does the new code call `$request->all()` or pass
unvalidated input into `Model::create()`/`update()`. The `appearance` sub-object accepted by
`storeQuestion`/`updateQuestion` is validated field-by-field (`appearance.exam_type_id`,
`appearance.year`, `appearance.source_batch`) before being read.

## Injection risk in search

`Question::search($q)` goes through Laravel Scout's driver abstraction (Meilisearch client /
`database` driver in this repo, see below) — no raw string concatenation into a search DSL or SQL
query. The `q` parameter itself is bounded (`min:3`, `max:2000`) and passed through the search
driver's own parameterized query construction; it is never interpolated into raw SQL.

## `MEILISEARCH_KEY` handling

- Never logged: no log statement in `QuestionAppearanceController` or `Question::toSearchableArray()`
  touches the key.
- Never returned in an API response: the key lives only in `config/scout.php` (via `env()`) and
  the Scout/Meilisearch client construction — it is not part of any response payload.
- `.env.example` carries only a placeholder (empty value); the real key belongs in Railway's
  environment variables for the deployed backend service, never committed.
- Only the backend talks to Meilisearch directly (no client-side/browser calls to the search
  engine), so there's no exposure surface for a scoped vs. master key distinction to matter here
  — noted as a defense-in-depth consideration if that architecture ever changes.

## Current deployment note (disclosed, not hidden)

Local development and this session's testing use `SCOUT_DRIVER=database` (Scout's built-in
DB-backed search driver), **not** a live Meilisearch instance — no Meilisearch service is running
in this environment. This is a legitimate Scout driver (not a stub), so the code path is real,
but it does not exercise the Meilisearch HTTP client or its credential handling. Before
production traffic relies on this feature, `SCOUT_DRIVER=meilisearch` should be set with a real
`MEILISEARCH_HOST`/`MEILISEARCH_KEY` pointing at a provisioned Meilisearch service (see the
business requirements doc's infra note), and the search-similar endpoint should be smoke-tested
against that real service before relying on it in production.

## Audit trail

`created_by` on `question_exam_appearances` records which admin tagged an appearance
(`$request->user()?->id`, nullable in case of system/import-driven creation). No equivalent
`deleted_by` exists for the `DELETE` endpoint — if audit-trail-on-delete becomes a requirement,
that's a follow-up, not covered here.
