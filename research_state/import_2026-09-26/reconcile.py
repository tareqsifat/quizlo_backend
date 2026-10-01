"""Decide the final fate of every planned question after the first import run. Read-only; writes reconcile.json."""
import json, re, collections
exec(open('plan_import.py', encoding='utf-8').read().split('# ── pool of known')[0]
     .replace("os.path.abspath(__file__)", "os.path.abspath('plan_import.py')"))

ET = {1: 1, 2: 1, 3: 1, 9: 1, 10: 1, 11: 1, 12: 1, 13: 1, 14: 1, 15: 1, 4: 2, 5: 2, 6: 2, 7: 2}


def loose_same(a, b):
    a, b = norm(a), norm(b)
    da, db_ = re.sub(r'\D', '', a), re.sub(r'\D', '', b)
    return a == b or bool(da and da == db_) or bool(a and b and (a in b or b in a)) or fuzz.ratio(a, b) >= 85


def same_answer(a, b):
    """Stricter than loose_same: long sentence answers must match exactly after normalising."""
    a, b = norm(a), norm(b)
    da, db_ = re.sub(r'\D', '', a), re.sub(r'\D', '', b)
    return a == b or bool(da and da == db_) or (max(len(a), len(b)) < 25 and fuzz.ratio(a, b) >= 90)


def loose_overlap(A, B):
    B, h = list(B), 0
    for x in A:
        for i, y in enumerate(B):
            if loose_same(x, y):
                h += 1
                del B[i]
                break
    return h


plan = json.load(open('import_plan.json', encoding='utf-8'))
resolved = {int(k): v for k, v in json.load(open('resolved_ids.json')).items()}
pool = {1: [], 2: []}          # records: {'id' or 'plan', text, norm, opts, correct}
texts = {1: [], 2: []}
for q in json.load(open('db_questions.json', encoding='utf-8')):
    rec = {'id': q['id'], 'text': q['question_text'], 'opts': [o['text'] for o in q['options']],
           'correct': next((o['text'] for o in q['options'] if o['is_correct']), '')}
    pool[q['exam_type_id']].append(rec)
    texts[q['exam_type_id']].append(norm(q['question_text']))

decisions, why = [], collections.Counter()
for i, q in enumerate(plan['new_questions']):
    et = ET[q['exam_type_subject_id']]
    correct = q['options'][q['correct_index']]
    target = None
    for _, score, j in process.extract(norm(q['question_text']), texts[et], scorer=fuzz.ratio, score_cutoff=85, limit=10):
        rec = pool[et][j]
        if meaning_differs(q['question_text'], rec['text']) and score < 100:
            continue
        if loose_overlap(q['options'], rec['opts']) >= 3:
            target, reason = rec, 'options'          # same question, options formatted differently
            break
        if score >= 92 and rec['correct'] and same_answer(correct, rec['correct']):
            target, reason = rec, 'same_answer'      # same question, different distractors
            break
    row = resolved.get(i)
    if target:
        why[reason] += 1
        decisions.append({'i': i, 'action': 'drop' if row else 'skip', 'row': row,
                          'target_id': target.get('id'), 'target_plan': target.get('plan'), 'reason': reason})
    else:
        decisions.append({'i': i, 'action': 'keep' if row else 'insert', 'row': row})
        rec = {'plan': i, 'id': row, 'text': q['question_text'], 'opts': q['options'], 'correct': correct}
        pool[et].append(rec)
        texts[et].append(norm(q['question_text']))

json.dump(decisions, open('reconcile.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=0)
print(collections.Counter(d['action'] for d in decisions), dict(why))
