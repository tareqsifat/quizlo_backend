import unicodedata
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


