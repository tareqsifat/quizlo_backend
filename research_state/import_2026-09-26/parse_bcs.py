import re, glob, json, html, os, collections
from parse_common import text, split_options, BN, legacy_entries

PAGES = os.path.join(os.path.dirname(__file__), 'pages')
OUT = r'C:/laragon/www/quizlo_backend/research_state/exam_sources'
BASE = 'https://www.chakribangla.com/bcs-question-solution/'

# only single-subject sections; combined ones (math-mental, science-computer, ...) are skipped
TARGETS = {
    'bangla': ('bcs_bangla_literature', 'Bangla Literature'),
    'international': ('bcs_international_affairs', 'International Affairs'),
    'ict': ('bcs_computer_it', 'Computer and Information Technology'),
    'bangladesh': ('bcs_bangladesh_affairs', 'Bangladesh Affairs'),
    'english': ('bcs_english_grammar', 'English Grammar'),
    'ethics': ('bcs_ethics_governance', 'Ethics, Values and Good Governance'),
    'science': ('bcs_general_science', 'General Science'),
    'geography': ('bcs_geography_environment', 'Geography, Environment and Disaster Management'),
    'math': ('bcs_mathematics', 'Mathematics'),
    'mental-ability': ('bcs_mental_ability', 'Mental Ability'),
}
results = collections.defaultdict(list)
skipped = collections.Counter()
pages_used = collections.defaultdict(set)

for path in sorted(glob.glob(os.path.join(PAGES, '[0-9]*-bcs*.html'))):
    fname = os.path.basename(path)
    s = open(path, encoding='utf-8').read()
    num = int(fname.split('-')[0])
    batch = f'BCS-{num}' + ('-special' if 'special' in fname else '')
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
            ps = re.findall(r'<p([^>]*)>(.*?)</p>', b, re.S)
            q = opts = ans = expl = None
            for attrs, inner in ps:
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
            if not ans:
                skipped['no-answer'] += 1
                continue
            seen.add(q)
            results[key].append({
                'question_text': q,
                'year': year,
                'source_batch': batch,
                'source_url': BASE + fname,
                'options': split_options(opts) if opts else None,
                'options_raw': opts,
                'correct_answer': ans,
                'explanation': expl,
            })
            pages_used[key].add(BASE + fname)

for sec_id, (key, subject) in TARGETS.items():
    legacy = legacy_entries(os.path.join(OUT, key + '.json'))
    entries = results[key] + legacy
    doc = {
        'subject': subject,
        'exam_type_code': 'BCS Preliminary',
        'entries': entries,
        'sources_checked': sorted({e['source_url'] for e in legacy}) + [BASE + 'bcs-question-solution.html'] + sorted(pages_used[key]),
        'note': ('Harvested 2026-09-26 from chakribangla.com BCS question-solution pages (10th-50th BCS), '
                 f'single-subject section "{sec_id}" only. Combined sections on older papers '
                 '(math-mental, science-computer, bangladesh-international, etc.) were not included.'),
    }
    with open(os.path.join(OUT, key + '.json'), 'w', encoding='utf-8') as fh:
        json.dump(doc, fh, ensure_ascii=False, indent=2)
    no_opts = sum(1 for e in entries if not e.get('options'))
    no_ans = sum(1 for e in entries if not e.get('correct_answer'))
    no_year = sum(1 for e in entries if e['year'] is None)
    print(f'{key}: {len(entries)} entries from {len(pages_used[key])} papers '
          f'(no options: {no_opts}, no answer: {no_ans}, no year: {no_year})')
print('skipped combined sections:', dict(skipped))
