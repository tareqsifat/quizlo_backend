import re, glob, json, os, collections
from parse_common import text, split_options, BN, legacy_entries

PAGES = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'ntrca')
OUT = r'C:/laragon/www/quizlo_backend/research_state/exam_sources'
BASE = 'https://www.chakribangla.com/ntrca-question-solution/'

TARGETS = {
    'math': ('ntrca_general_math', 'General Math'),
    'bangla': ('ntrca_bangla', 'Bangla'),
    'english': ('ntrca_english', 'English'),
    'gk': ('ntrca_general_knowledge', 'General Knowledge'),
}
LEVELS = {'school-level': 'School', 'school-level-2': 'School-2', 'college-level': 'College'}

results = collections.defaultdict(list)
pages_used = collections.defaultdict(set)
skipped = collections.Counter()

for path in sorted(glob.glob(os.path.join(PAGES, '*-level*.html'))):
    fname = os.path.basename(path)
    m = re.match(r'(\d+)-ntrca-(.+)\.html', fname)
    num, level = int(m.group(1)), LEVELS[m.group(2)]
    url = f'{BASE}{num}th-ntrca-question-solution/{fname}'
    s = open(path, encoding='utf-8').read()
    first = s.find('class="content-section"')
    ym = re.search(r'([১২][০-৯]{3})', re.sub(r'<[^>]+>', ' ', s[:first]))
    year = int(ym.group(1).translate(BN)) if ym else None

    sections = re.split(r'<div id="([^"]+)" class="content-section">', s)
    for i in range(1, len(sections), 2):
        sec_id, body = sections[i], sections[i + 1]
        blocks = re.findall(r'<div class="question-block">(.*?)</div>', body, re.S)
        if sec_id not in TARGETS:
            skipped[sec_id] += len(blocks)
            continue
        key = TARGETS[sec_id][0]
        seen = set()
        for b in blocks:
            q = opts = ans = expl = None
            for attrs, inner in re.findall(r'<p([^>]*)>(.*?)</p>', b, re.S):
                if 'correct-answer' in attrs:
                    ans = re.sub(r'^(?:উত্তর|Answer|Ans)\s*[:ঃ.]?\s*', '', text(inner), flags=re.I)
                elif 'explanation' in attrs:
                    expl = re.sub(r'^ব্যাখ্যা\s*[:ঃ]\s*', '', text(inner))
                elif q is None:
                    q = text(inner)
                elif opts is None:
                    opts = text(inner)
            if not q or 'চিত্রভিত্তিক' in q:
                skipped['image-based'] += 1
                continue
            q = re.sub(r'^[০-৯0-9]+[.।)]\s*', '', q)
            if q in seen:
                continue
            seen.add(q)
            if not ans:
                skipped['no-answer'] += 1
                continue
            results[key].append({
                'question_text': q,
                'year': year,
                'source_batch': f'NTRCA-{num}-{level}',
                'source_url': url,
                'options': split_options(opts) if opts else None,
                'options_raw': opts,
                'correct_answer': ans,
                'explanation': expl,
            })
            pages_used[key].add(url)

for sec_id, (key, subject) in TARGETS.items():
    legacy = legacy_entries(os.path.join(OUT, key + '.json'))
    entries = results[key] + legacy
    doc = {
        'subject': subject,
        'exam_type_code': 'NTRCA',
        'entries': entries,
        'sources_checked': sorted({e['source_url'] for e in legacy}) + [BASE + 'ntrca-question-solution.html'] + sorted(pages_used[key]),
        'note': ('Harvested 2026-09-26 from chakribangla.com NTRCA question-solution pages '
                 f'(10th-18th NTRCA, school/school-2/college level), section "{sec_id}".'),
    }
    with open(os.path.join(OUT, key + '.json'), 'w', encoding='utf-8') as fh:
        json.dump(doc, fh, ensure_ascii=False, indent=2)
    opt_counts = collections.Counter(len(e.get('options')) if e.get('options') else 0 for e in entries)
    no_year = sum(1 for e in entries if e['year'] is None)
    print(f'{key}: {len(entries)} entries from {len(pages_used[key])} papers '
          f'(option counts: {dict(opt_counts)}, no year: {no_year})')
print('skipped:', dict(skipped))
