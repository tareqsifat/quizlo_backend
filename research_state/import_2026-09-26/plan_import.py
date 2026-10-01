"""Match harvested questions against the DB (and each other) and write an import plan. Read-only."""
import json, os, re, unicodedata, collections
from rapidfuzz import fuzz, process

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = r'C:/laragon/www/quizlo_backend/research_state/exam_sources'

# harvest file -> (exam_type_id, exam_type_subject_id)
SUBJECTS = {
    'bcs_english_grammar': (1, 1), 'bcs_bangla_literature': (1, 2), 'bcs_bangladesh_affairs': (1, 3),
    'bcs_general_science': (1, 9), 'bcs_computer_it': (1, 10), 'bcs_mathematics': (1, 11),
    'bcs_mental_ability': (1, 12), 'bcs_geography_environment': (1, 13),
    'bcs_international_affairs': (1, 14), 'bcs_ethics_governance': (1, 15),
    'ntrca_general_knowledge': (2, 4), 'ntrca_bangla': (2, 5), 'ntrca_english': (2, 6),
    'ntrca_general_math': (2, 7),
}
TEXT_FUZZY = 92      # text similarity (0-100) at which 3 matching options are enough
TEXT_LOOSE = 75      # below TEXT_FUZZY, the whole option set must match
OPT_FUZZY = 85       # min similarity for two option texts to count as the same option
BN_DIGITS = str.maketrans('০১২৩৪৫৬৭৮৯', '0123456789')
ZW = dict.fromkeys(map(ord, '\u200b\u200c\u200d\ufeff'), None)


def norm(s):
    """Canonical form: NFKD (legacy 'ে+া' equals 'ো', '²' equals '2'), no zero-width chars,
    ASCII digits, lowercase, all punctuation, whitespace and '^' removed. Math symbols are kept."""
    s = (s or '').replace('≤', '<=').replace('≥', '>=').replace('^', '')
    s = unicodedata.normalize('NFKD', s).translate(ZW).translate(BN_DIGITS).lower()
    return ''.join(c for c in s if not c.isspace() and not unicodedata.category(c).startswith('P'))


NEGATION_WORDS = ['নয়', 'নহে', 'নেই', 'নাই', 'ব্যতীত', 'ছাড়া', 'অশুদ্ধ']
NEGATION = re.compile('|'.join(unicodedata.normalize('NFKD', w) for w in NEGATION_WORDS)
                      + r'|\b(?:not|except|incorrect|wrong)\b', re.I)


# questions that only make sense next to a figure or reading passage we don't have
NEEDS_CONTEXT = re.compile(r'(?:^|\s)(?:চিত্রে|চিত্রের)\s|passage', re.I)


def meaning_differs(a, b):
    """Near-identical wording that still asks something else: different numbers or a flipped negation."""
    a, b = (unicodedata.normalize('NFKD', t).translate(BN_DIGITS) for t in (a, b))
    return re.findall(r'\d+', a) != re.findall(r'\d+', b) or bool(NEGATION.search(a)) != bool(NEGATION.search(b))


def option_overlap(a, b):
    """How many options of a have a matching option in b (each b option used once)."""
    b = list(b)
    hits = 0
    for x in a:
        for i, y in enumerate(b):
            if x == y or (x and y and fuzz.ratio(x, y) >= OPT_FUZZY):
                hits += 1
                del b[i]
                break
    return hits


LABELS = {**{c: i for i, c in enumerate('কখগঘঙ')}, **{c: i for i, c in enumerate('abcde')}}


def correct_index(entry):
    opts, ans = entry.get('options') or [], entry.get('correct_answer') or ''
    m = re.match(r'^\s*\(?\s*([কখগঘঙa-eA-E])\s*\)', ans)
    if m:
        label = m.group(1).lower()
        for i, o in enumerate(opts):
            if o['label'].lower() == label:
                return i
    # no usable label: match the answer text against the options
    a = norm(re.sub(r'^\s*\(?\s*[কখগঘঙa-eA-E]\s*\)\s*', '', ans))
    scored = [(fuzz.ratio(a, norm(o['text'])), i) for i, o in enumerate(opts)]
    best = max(scored, default=(0, None))
    return best[1] if best[0] >= OPT_FUZZY else None


# ── pool of known questions per exam type: existing DB rows first ───────────────
pool = {1: [], 2: []}           # exam_type_id -> list of records
pool_text = {1: [], 2: []}      # parallel list of normalized texts for rapidfuzz
exact = {1: collections.defaultdict(list), 2: collections.defaultdict(list)}


def add_to_pool(et, rec):
    pool[et].append(rec)
    pool_text[et].append(rec['norm'])
    exact[et][rec['norm']].append(len(pool[et]) - 1)


with open(os.path.join(HERE, 'db_questions.json'), encoding='utf-8') as fh:
    for q in json.load(fh):
        add_to_pool(q['exam_type_id'], {
            'kind': 'db', 'id': q['id'], 'subject_id': q['exam_type_subject_id'],
            'text': q['question_text'], 'norm': norm(q['question_text']),
            'opts': [norm(o['text']) for o in q['options']],
            'correct': next((norm(o['text']) for o in q['options'] if o['is_correct']), None),
        })

db_by_id = {r['id']: r for et in pool for r in pool[et]}
with open(os.path.join(HERE, 'manual_same.json'), encoding='utf-8') as fh:
    MANUAL_SAME = json.load(fh)

# ── harvested entries, earliest exam first so it becomes the canonical copy ─────
entries = []
for key, (et, sid) in SUBJECTS.items():
    with open(os.path.join(SRC, key + '.json'), encoding='utf-8') as fh:
        for e in json.load(fh)['entries']:
            entries.append((key, et, sid, e))
entries.sort(key=lambda t: (t[3].get('year') is None, t[3].get('year') or 0))

new_questions, existing_appearances, review, conflicts = [], [], [], []
stats = collections.Counter()


def appearance(e, et):
    return {'exam_type_id': et, 'year': e.get('year'), 'source_batch': (e.get('source_batch') or '')[:50]}


for key, et, sid, e in entries:
    n = norm(e['question_text'])
    opts = [norm(o['text']) for o in (e.get('options') or [])]
    has_opts = len(opts) >= 2
    ci_h = correct_index(e) if has_opts else None

    # candidates: exact text first, then fuzzy text
    cands = {i: 100.0 for i in exact[et].get(n, [])}
    for _, score, i in process.extract(n, pool_text[et], scorer=fuzz.ratio, score_cutoff=TEXT_LOOSE, limit=8):
        cands.setdefault(i, score)

    match, borderline, match_score = None, None, None
    if e['question_text'] in MANUAL_SAME:          # hand-confirmed duplicates of a DB question
        match, match_score = db_by_id[MANUAL_SAME[e['question_text']]], 0.0
        cands = {}
    for i, score in sorted(cands.items(), key=lambda kv: -kv[1]):
        rec = pool[et][i]
        if not has_opts:                       # sattacademy text-only entries: text match is all we have
            if score >= 97:
                match, match_score = rec, score
                break
            continue
        ov = option_overlap(opts, rec['opts'])
        need = min(3, len(opts), len(rec['opts']))
        if score < TEXT_FUZZY:
            need = max(len(opts), len(rec['opts']))          # loose text: every option must match
        if ov >= need >= 2:
            if score < 100 and meaning_differs(e['question_text'], rec['text']):
                continue      # e.g. 10^-3 vs 10^-4, or "... নয় যার" vs "... যার" with the same options
            match, match_score = rec, score
            break
        # close lookalike that the strict rules could not confirm or rule out: don't guess, send to review
        if ov >= 2 and score >= 85 and borderline is None:
            borderline = (rec, score, ov)

    if match:
        if match['kind'] == 'db':
            existing_appearances.append({'question_id': match['id'], **appearance(e, et),
                                         '_score': round(match_score, 1), '_harvest_text': e['question_text'],
                                         '_harvest_options': [o['text'] for o in e.get('options') or []]})
            stats['matched_existing'] += 1
            if match['subject_id'] != sid:
                stats['matched_existing_other_subject'] += 1
            ci = correct_index(e) if has_opts else None
            if ci is not None and match['correct'] and option_overlap([opts[ci]], [match['correct']]) == 0:
                conflicts.append({'question_id': match['id'], 'db_text': match['text'],
                                  'db_correct': match['correct'], 'source_answer': e.get('correct_answer'),
                                  'source_url': e['source_url'], 'source_batch': e.get('source_batch')})
        else:
            match['appearances'].append(appearance(e, et))
            stats['repeat_of_new'] += 1
        continue

    if borderline:
        rec, score, ov = borderline
        review.append({'harvest_text': e['question_text'], 'harvest_options': [o['text'] for o in e['options']],
                       'candidate_kind': rec['kind'], 'candidate_id': rec.get('id'), 'candidate_text': rec['text'],
                       'candidate_options': rec['opts'], 'text_score': round(score, 1), 'option_overlap': ov,
                       'harvest_answer': e.get('correct_answer'), 'harvest_file': key,
                       'year': e.get('year'), 'source_batch': e.get('source_batch'), 'source_url': e['source_url']})
        stats['review_borderline'] += 1
        continue

    if not has_opts:
        stats['skipped_no_options'] += 1
        continue
    if NEEDS_CONTEXT.search(e['question_text']) or len(e['question_text']) > 350:
        stats['skipped_needs_figure_or_passage'] += 1
        continue
    ci = correct_index(e)
    if ci is None:
        stats['skipped_unresolved_answer'] += 1
        continue

    rec = {'kind': 'new', 'subject_id': sid, 'text': e['question_text'], 'norm': n, 'opts': opts,
           'correct': opts[ci], 'appearances': [appearance(e, et)]}
    add_to_pool(et, rec)
    new_questions.append({'exam_type_subject_id': sid, 'question_text': e['question_text'].strip(),
                          'options': [o['text'].strip() for o in e['options']], 'correct_index': ci,
                          'explanation': e.get('explanation'), 'source_url': e['source_url'],
                          'appearances': rec['appearances'], '_harvest_file': key})
    stats['new'] += 1

plan = {'new_questions': new_questions, 'existing_appearances': existing_appearances}
with open(os.path.join(HERE, 'import_plan.json'), 'w', encoding='utf-8') as fh:
    json.dump(plan, fh, ensure_ascii=False, indent=1)
for name, data in [('review_borderline.json', review), ('answer_conflicts.json', conflicts)]:
    with open(os.path.join(HERE, name), 'w', encoding='utf-8') as fh:
        json.dump(data, fh, ensure_ascii=False, indent=1)

print('harvest entries:', len(entries))
print(dict(stats))
per = collections.Counter(q['_harvest_file'] for q in new_questions)
print('new questions per subject:', dict(sorted(per.items())))
print('appearances: existing', len(existing_appearances), '| on new', sum(len(q['appearances']) for q in new_questions))
print('answer conflicts with DB:', len(conflicts))
