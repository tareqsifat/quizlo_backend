"""Pick exactly NEED questions from clean_plan.json, only from candidates that also pass a loose third check
(no DB/selected question in the same exam with token_set >= 80 on the stem, or answer-containment + token_set >= 60).
Quotas balance subjects; within a subject, prefer items with explanations and round-robin across source pages."""
import json, os, collections, itertools
from rapidfuzz import fuzz, process
from suspect_scan_lib import spaced, tight, ET

HERE = os.path.dirname(os.path.abspath(__file__))
NEED = int(os.environ['NEED'])
QUOTA = {'ntrca_general_science': 527, 'ntrca_general_math': 460, 'ntrca_bangla': 450,
         'ntrca_general_knowledge': 400, 'ntrca_english': 3}          # every BCS candidate is taken
plan = json.load(open(os.path.join(HERE, 'clean_plan.json'), encoding='utf-8'))
db = json.load(open(os.path.join(HERE, 'db_questions.json'), encoding='utf-8'))

pool = {1: [], 2: []}
for q in db:
    corr = next((o['text'] for o in q['options'] if o['is_correct']), '')
    pool[q['exam_type_id']].append((spaced(q['question_text']), tight(corr), f"db#{q['id']}", q['question_text']))


def loose_hit(q):
    et = ET[q['exam_type_subject_id']]
    stem, ans = spaced(q['question_text']), tight(q['options'][q['correct_index']])
    stems = [p[0] for p in pool[et]]
    for _, score, i in process.extract(stem, stems, scorer=fuzz.token_set_ratio, score_cutoff=60, limit=25):
        p_ans = pool[et][i][1]
        if score >= 80 or (ans and p_ans and (ans in p_ans or p_ans in ans)):
            return pool[et][i], score
    return None


by_subject = collections.defaultdict(list)
for q in plan['new_questions']:
    by_subject[q['_harvest_file']].append(q)


def ordered(qs):
    """explanations first, then round-robin across source pages for topic spread"""
    pages = collections.defaultdict(list)
    for q in sorted(qs, key=lambda q: not q.get('explanation')):
        pages[q['source_url']].append(q)
    return [q for grp in itertools.zip_longest(*pages.values()) for q in grp if q]


selected, rejected = [], []
for subj, qs in sorted(by_subject.items()):
    quota = QUOTA.get(subj, len(qs))
    taken = 0
    for q in ordered(qs):
        if taken >= quota:
            break
        hit = loose_hit(q)
        if hit:
            rejected.append({'candidate': q['question_text'], 'match': hit[0][2], 'match_text': hit[0][3], 'score': hit[1]})
            continue
        selected.append(q)
        pool[ET[q['exam_type_subject_id']]].append((spaced(q['question_text']), tight(q['options'][q['correct_index']]), 'new', q['question_text']))
        taken += 1

assert len(selected) >= NEED, f'only {len(selected)} survived, need {NEED}'
selected = selected[:NEED] if len(selected) > NEED else selected
# appearances only for real past papers (tagged BCS batches); model tests / practice pages are not exam appearances
for q in selected:
    q['appearances'] = [a for a in q['appearances'] if a.get('source_batch')]
json.dump({'new_questions': selected, 'existing_appearances': []},
          open(os.path.join(HERE, 'import_plan_final.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump(rejected, open(os.path.join(HERE, 'loose_rejected.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('selected', len(selected), '| loose-rejected', len(rejected))
print(dict(sorted(collections.Counter(q['_harvest_file'] for q in selected).items())))
print('with explanation', sum(1 for q in selected if q.get('explanation')),
      '| with past-paper appearance', sum(1 for q in selected if q['appearances']))
