"""Parse chakribangla.com model tests + preparation-page MCQs into exam_sources-style JSON (sources/*.json).
Only items with >=2 labelled options and a labelled answer are kept; Q&A items without options are skipped."""
import re, glob, json, os, html, collections

HERE = os.path.dirname(os.path.abspath(__file__))
PAGES = os.path.join(HERE, 'pages')
OUT = os.path.join(HERE, 'sources')
os.makedirs(OUT, exist_ok=True)
BN = str.maketrans('০১২৩৪৫৬৭৮৯', '0123456789')
SITE = 'https://www.chakribangla.com/'

BCS_SEC = {'bangla': 'bcs_bangla_literature', 'english': 'bcs_english_grammar', 'bangladesh': 'bcs_bangladesh_affairs',
           'international': 'bcs_international_affairs', 'geography': 'bcs_geography_environment',
           'science': 'bcs_general_science', 'ict': 'bcs_computer_it', 'math': 'bcs_mathematics',
           'mental-ability': 'bcs_mental_ability', 'ethics': 'bcs_ethics_governance'}
NTRCA_SEC = {'bangla': 'ntrca_bangla', 'english': 'ntrca_english', 'math': 'ntrca_general_math', 'gk': 'ntrca_general_knowledge'}
BCS_PREP = {'bangla-bhasha-o-shahityo': 'bcs_bangla_literature', 'bangladesh-affairs': 'bcs_bangladesh_affairs',
            'computer-and-it': 'bcs_computer_it', 'english-language-and-literature': 'bcs_english_grammar',
            'ethics-values': 'bcs_ethics_governance', 'general-science': 'bcs_general_science',
            'geography-environment-and-disaster-management': 'bcs_geography_environment',
            'international-affairs': 'bcs_international_affairs', 'mathematical-reasoning': 'bcs_mathematics',
            'mental-ability': 'bcs_mental_ability'}
NTRCA_PREP = {'bangla': 'ntrca_bangla', 'english': 'ntrca_english', 'general-knowledge': 'ntrca_general_knowledge',
              'mathematics': 'ntrca_general_math'}
NTRCA_SCIENCE_PAGES = {'light-sound-heat', 'biology-and-human-body', 'chemistry-in-daily-life',
                       'modern-science-and-discoveries', 'physics-motion-force-energy', 'vitamins-nutrition-and-health'}
BCS_YEAR = {10: 1990, 11: 1992, 12: 1991, 13: 1992, 14: 1993, 15: 1993, 16: 1995, 17: 1995, 18: 1997, 20: 1999,
            22: 2003, 23: 2001, 24: 2003, 25: 2005, 27: 2006, 28: 2008, 29: 2009, 30: 2011, 31: 2011, 32: 2012,
            33: 2012, 34: 2013, 35: 2015, 36: 2016, 37: 2016, 38: 2017, 40: 2019, 41: 2021, 43: 2021, 44: 2022,
            45: 2023, 46: 2024, 47: 2025, 50: 2026}


def text(fragment):
    t = re.sub(r'<br\s*/?>', '\n', fragment)
    t = html.unescape(re.sub(r'</?[a-zA-Z][^>]*>', '', t)).replace('\xa0', ' ')
    return re.sub(r'[ \t\r\f\v]+', ' ', t).strip()


def split_options(opt):
    parts = re.split(r'(?:^|(?<=\s))\(([কখগঘঙ])\)\s*', opt)
    if len(parts) < 5:
        return None
    return [{'label': parts[i], 'text': parts[i + 1].strip()} for i in range(1, len(parts) - 1, 2)]


def answer_label(ans):
    m = re.search(r'\(([কখগঘঙ])\)', ans or '')
    return m.group(1) if m else None


def clean_q(q):
    return re.sub(r'^[০-৯0-9]+\s*[.।)]\s*', '', q).strip()


results = collections.defaultdict(list)
skipped = collections.Counter()


def add(key, q, options, label, expl, url, batch=None, year=None):
    if not q or not options or len(options) < 2 or not label:
        skipped['incomplete'] += 1
        return
    if label not in [o['label'] for o in options]:
        skipped['answer_not_in_options'] += 1
        return
    if any(not o['text'] for o in options):
        skipped['empty_option'] += 1
        return
    results[key].append({'question_text': q, 'year': year, 'source_batch': batch, 'source_url': url,
                         'options': options, 'correct_answer': f'({label})', 'explanation': expl or None})


# ── model tests: <div class="question-block" data-correct="ঘ"> with q-stem / opt-text / explanation ──
for path in glob.glob(os.path.join(PAGES, '*model-test__*.html')):
    fname = os.path.basename(path)
    url = SITE + fname.replace('__', '/')
    s = open(path, encoding='utf-8').read()
    secmap = BCS_SEC if fname.startswith('bcs') else NTRCA_SEC
    sections = re.split(r'<div id="([^"]+)" class="content-section">', s)
    for i in range(1, len(sections), 2):
        sec_id, body = sections[i], sections[i + 1]
        blocks = re.findall(r'<div class="question-block" data-correct="([^"]*)">(.*?)<p class="explanation">(.*?)</p>', body, re.S)
        if sec_id not in secmap:
            skipped['unmapped_section:' + sec_id] += len(blocks)
            continue
        for label, b, expl in blocks:
            stem = re.search(r'<p class="q-stem">(.*?)</p>', b, re.S)
            q = clean_q(text(re.sub(r'<span class="q-num">.*?</span>', '', stem.group(1)))) if stem else None
            opts = [{'label': l, 'text': text(t)} for l, t in
                    re.findall(r'data-opt="([^"]+)">.*?<span class="opt-text">(.*?)</span>', b, re.S)]
            add(secmap[sec_id], q, opts, label.strip(), re.sub(r'^ব্যাখ্যা\s*[:ঃ]\s*', '', text(expl)), url)

# ── BCS preparation pages: <div class="solved-q"> with bcs-tag, opts, correct-answer ──
for path in glob.glob(os.path.join(PAGES, 'bcs-preliminary-preparation__*__*.html')):
    fname = os.path.basename(path)
    key = BCS_PREP[fname.split('__')[1]]
    url = SITE + fname.replace('__', '/')
    s = open(path, encoding='utf-8').read()
    for b in re.findall(r'<div class="solved-q">(.*?)</div>', s, re.S):
        tag = re.search(r'<span class="bcs-tag">(.*?)</span>', b, re.S)
        batch = year = None
        if tag:
            m = re.search(r'([০-৯0-9]+)\s*তম', text(tag.group(1)))
            if m:
                n = int(m.group(1).translate(BN))
                special = 'বিশেষ' in tag.group(1) or n in (39, 42, 48, 49)   # special BCS exams; tags often omit 'বিশেষ'
                batch = f'BCS-{n}' + ('-special' if special else '')
                year = None if special else BCS_YEAR.get(n)
        body = re.sub(r'<span class="bcs-tag">.*?</span>', '', b, flags=re.S)
        ps = re.findall(r'<p([^>]*)>(.*?)</p>', body, re.S)
        q = opts = ans = expl = None
        for attrs, inner in ps:
            if 'correct-answer' in attrs: ans = text(inner)
            elif 'explanation' in attrs: expl = re.sub(r'^ব্যাখ্যা\s*[:ঃ]\s*', '', text(inner))
            elif 'opts' in attrs: opts = split_options(text(inner))
            elif q is None: q = clean_q(text(inner))
        add(key, q, opts, answer_label(ans), expl, url, batch, year)

# ── NTRCA preparation pages: <div class="qa-block"> (only the ones that carry options) ──
for path in glob.glob(os.path.join(PAGES, 'ntrca-job-preparation__*__*.html')):
    fname = os.path.basename(path)
    key = NTRCA_PREP[fname.split('__')[1]]
    if fname.split('__')[-1][:-5] in NTRCA_SCIENCE_PAGES:
        key = 'ntrca_general_science'
    url = SITE + fname.replace('__', '/')
    s = open(path, encoding='utf-8').read()
    for b in re.findall(r'<div class="qa-block">(.*?)</div>', s, re.S):
        ps = re.findall(r'<p([^>]*)>(.*?)</p>', b, re.S)
        q = opts = ans = expl = None
        for attrs, inner in ps:
            if 'qa-ans' in attrs: ans = text(inner)
            elif 'qa-exp' in attrs: expl = re.sub(r'^ব্যাখ্যা\s*[:ঃ]\s*', '', text(inner))
            elif q is None: q = clean_q(text(inner))
            elif opts is None: opts = split_options(text(inner))
        if opts is None:
            skipped['ntrca_prep_no_options'] += 1
            continue
        add(key, q, opts, answer_label(ans), expl, url)

for key, entries in sorted(results.items()):
    with open(os.path.join(OUT, key + '.json'), 'w', encoding='utf-8') as fh:
        json.dump({'entries': entries}, fh, ensure_ascii=False, indent=1)
    kinds = collections.Counter('model' if 'model-test' in e['source_url'] else 'prep' for e in entries)
    print(f'{key}: {len(entries)} {dict(kinds)}')
print('total', sum(map(len, results.values())), '| skipped', dict(skipped))
