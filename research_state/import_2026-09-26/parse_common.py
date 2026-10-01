import re, html

BN = str.maketrans('০১২৩৪৫৬৭৮৯', '0123456789')


def text(fragment):
    t = re.sub(r'<br\s*/?>', '\n', fragment)
    t = html.unescape(re.sub(r'</?[a-zA-Z][^>]*>', '', t))
    return re.sub(r'[ \t\r\f\v]+', ' ', t).strip()


def split_options(opt):
    labels = '[কখগঘঙ]' if re.search(r'[কখগঘঙ]\)', opt) else '[a-eA-E]'
    parts = re.split(r'(?:^|(?<=\s))\(?(' + labels + r')\)\s*', opt)
    if len(parts) < 3:
        return None
    return [{'label': parts[i], 'text': parts[i + 1].strip()} for i in range(1, len(parts) - 1, 2)]


def legacy_entries(path):
    """Entries in an existing source file that came from somewhere other than chakribangla."""
    import json, os
    if not os.path.exists(path):
        return []
    with open(path, encoding='utf-8') as fh:
        return [e for e in json.load(fh)['entries'] if 'chakribangla.com' not in e.get('source_url', '')]
