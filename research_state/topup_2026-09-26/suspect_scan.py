"""Second, deliberately over-cautious duplicate pass over import_plan.json new_questions.
A candidate is dropped (-> suspects.json) when any DB question or earlier-kept candidate of the same exam type
  (a) has the same correct answer and a similar stem (token_set >= 70 or partial >= 80 on spaced text), or
  (b) has a partially matching stem (partial >= 90) and shares >= 2 options.
Writes clean_plan.json with the survivors."""
import json, os, re, unicodedata, collections
from rapidfuzz import fuzz, process

HERE = os.path.dirname(os.path.abspath(__file__))
BN = str.maketrans('০১২৩৪৫৬৭৮৯', '0123456789')
ZW = dict.fromkeys(map(ord, '​‌‍﻿'), None)
ET = {1: 1, 2: 1, 3: 1, 9: 1, 10: 1, 11: 1, 12: 1, 13: 1, 14: 1, 15: 1, 4: 2, 5: 2, 6: 2, 7: 2, 8: 2}
STOP = {'কী', 'কি', 'কোনটি', 'কোন', 'কে', 'কত', 'কবে', 'কোথায়', 'কাকে', 'বলে', 'হয়', 'হয়', 'এর', 'the', 'of',
        'is', 'a', 'an', 'which', 'what', 'who', 'নিচের', 'কয়টি', 'কয়টি', 'কাকে'}


def spaced(s):
    s = unicodedata.normalize('NFKD', s or '').translate(ZW).translate(BN).lower()
    s = ''.join(c if not unicodedata.category(c).startswith('P') else ' ' for c in s)
    return ' '.join(w for w in s.split() if w not in STOP)


def tight(s):
    return spaced(s).replace(' ', '')


plan = json.load(open(os.path.join(HERE, 'import_plan.json'), encoding='utf-8'))
db = json.load(open(os.path.join(HERE, 'db_questions.json'), encoding='utf-8'))

pool = {1: [], 2: []}
for q in db:
    corr = next((o['text'] for o in q['options'] if o['is_correct']), '')
    pool[q['exam_type_id']].append({'src': f"db#{q['id']}", 'text': q['question_text'], 'stem': spaced(q['question_text']),
                                    'ans': tight(corr), 'opts': [tight(o['text']) for o in q['options']]})
by_ans = {et: collections.defaultdict(list) for et in pool}
for et in pool:
    for i, r in enumerate(pool[et]):
        by_ans[et][r['ans']].append(i)


def overlap(a, b):
    return sum(1 for x in a if x and x in b)


kept, suspects = [], []
for q in plan['new_questions']:
    et = ET[q['exam_type_subject_id']]
    stem, ans = spaced(q['question_text']), tight(q['options'][q['correct_index']])
    opts = [tight(o) for o in q['options']]
    hit = None
    for i in by_ans[et].get(ans, []) if ans else []:
        r = pool[et][i]
        ts, pr = fuzz.token_set_ratio(stem, r['stem']), fuzz.partial_ratio(stem, r['stem'])
        if ts >= 70 or pr >= 80:
            hit = (r, 'same answer', max(ts, pr))
            break
    if not hit:
        stems = [r['stem'] for r in pool[et]]
        for _, score, i in process.extract(stem, stems, scorer=fuzz.partial_ratio, score_cutoff=90, limit=10):
            r = pool[et][i]
            if overlap(opts, r['opts']) >= 2 and len(stem) >= 6:
                hit = (r, 'similar stem + shared options', score)
                break
    if hit:
        r, why, score = hit
        suspects.append({'candidate': q['question_text'], 'candidate_answer': q['options'][q['correct_index']],
                         'matches': r['src'], 'match_text': r['text'], 'reason': why, 'score': round(score, 1),
                         'subject_file': q['_harvest_file']})
        continue
    kept.append(q)
    rec = {'src': 'new', 'text': q['question_text'], 'stem': stem, 'ans': ans, 'opts': opts}
    pool[et].append(rec)
    by_ans[et][ans].append(len(pool[et]) - 1)

json.dump(suspects, open(os.path.join(HERE, 'suspects.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump({'new_questions': kept, 'existing_appearances': plan['existing_appearances']},
          open(os.path.join(HERE, 'clean_plan.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('candidates', len(plan['new_questions']), '| dropped as suspect', len(suspects), '| kept', len(kept))
print('kept per subject', dict(sorted(collections.Counter(q['_harvest_file'] for q in kept).items())))
print('suspect reasons', dict(collections.Counter(s['reason'] for s in suspects)))
