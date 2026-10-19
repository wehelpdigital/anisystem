"""Builds varieties.json, the crop variety catalogue behind /crop-varieties
(every crop but rice, which has /rice-varieties).

Two kinds of source, facts only:
  1. The NSIC registry of crop varieties, the spreadsheets the Bureau of
     Plant Industry publishes at https://nsic.buplant.da.gov.ph/registry.php
     (corn, vegetables, legumes, root crops, sugarcane, plantation crops,
     fiber crops and fruit crops). Names, years, owners, trial numbers,
     ratings and the place and season each is recommended for.
  2. Research notes (2026-10-13): the varieties farmers actually buy, the
     names on the seed pack, and what each seed company or agency says about
     them, every note with the pages it came from.
Every sentence on the pages is anee.io's own, written by build_pages.py.

    python database/crop-varieties/build_data.py <nsic xlsx folder> <research folder>
"""
import glob
import json
import os
import re
import statistics
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import nsic  # noqa: E402

XL = sys.argv[1] if len(sys.argv) > 1 else HERE
RESEARCH = sys.argv[2] if len(sys.argv) > 2 else None

# NSIC sheet -> (crop key, kind it implies). Left out: wheat, chickpea,
# mulberry and tobacco (not grown for food or not on anee.io farms).
SHEETS = {
    'CORN': ('corn', None), 'SORGHUM': ('sorghum', None),
    'ABACA': ('abaca', None), 'COTTON': ('cotton', None),
    'MUNGBEAN': ('mungbean', None), 'PEANUT': ('peanut', None), 'PIGEONPEA': ('pigeonpea', None),
    'CACAO': ('cacao', None), 'COCONUT': ('coconut', None), 'COFFEE': ('coffee', None), 'RUBBER': ('rubber', None),
    'CASSAVA': ('cassava', None), 'LESSER YAM (Tugi)': ('tugi', None), 'SWEET POTATO': ('sweetpotato', None),
    'TARO (Gabi)': ('taro', None), 'WATER YAM (Ubi)': ('ubi', None), 'WHITE POTATO': ('potato', None),
    'SUGARCANE': ('sugarcane', None),
    'BUSH SITAO': ('stringbean', 'bush sitao'), 'POLE SITAO': ('stringbean', 'pole sitao'),
    'BUSH SNAP BEAN': ('snapbean', 'bush snap bean'), 'POLE SNAP BEAN': ('snapbean', 'pole snap bean'),
    'CABBAGE': ('cabbage', None), 'CAULIFLOWER': ('cauliflower', None), 'CHINESE CABBAGE': ('chinesecabbage', None),
    'COWPEA': ('cowpea', None), 'CUCUMBER': ('cucumber', None), 'EGGPLANT': ('eggplant', None), 'GARDEN PEA': ('pea', None),
    'GARLIC': ('garlic', None), 'LETTUCE': ('lettuce', None), 'LIMA BEAN': ('limabean', None), 'LONG CHILI PEPPER': ('chili', 'long chili'),
    'MUSKMELON': ('melon', None), 'OKRA': ('okra', None), 'PECHAY': ('pechay', None), 'SQUASH': ('squash', None),
    'SWAMP CABBAGE': ('kangkong', None), 'SWEET PEPPER': ('bellpepper', None), 'TOMATO': ('tomato', None), 'WATERMELON': ('watermelon', None),
    'ABIU': ('abiu', None), 'ATEMOYA': ('atemoya', None), 'AVOCADO': ('avocado', None), 'BANANA': ('banana', None),
    'BIGNAY': ('bignay', None), 'BREAD FRUIT': ('breadfruit', None), 'CAIMITO': ('starapple', None), 'CALAMANSI': ('calamansi', None),
    'CAMACHILE': ('camachile', None), 'CASHEW': ('cashew', None), 'CHESA (Tiesa)': ('chesa', None), 'CHICO (Sapodilla)': ('chico', None),
    'DURIAN': ('durian', None), 'GUAVA': ('guava', None), 'GOV. PLUM (Bitungol)': ('bitungol', None), 'HAGIS': ('hagis', None),
    'JACK FRUIT': ('jackfruit', None), 'JAMBOLAN (Duhat)': ('duhat', None), 'KATMON': ('katmon', None), 'LANZONES': ('lanzones', None),
    'LONGAN': ('longan', None), 'MABOLO': ('mabolo', None), 'MACADAMIA': ('macadamia', None), 'MARANG': ('marang', None),
    'MANGO': ('mango', None), 'MANGOSTEEN': ('mangosteen', None), 'NAVEL ORANGE': ('citrus', 'navel orange'),
    'PAPAYA': ('papaya', None), 'PILI': ('pili', None), 'PUMMELO': ('pomelo', None), 'RAMBUTAN': ('rambutan', None),
    'SANTOL': ('santol', None), 'TAMARIND': ('tamarind', None), 'SATSUMA MANDARIN': ('citrus', 'satsuma mandarin'),
    'SOURSOUP (Guyabano)': ('guyabano', None), 'STAR FRUIT (Balimbing)': ('balimbing', None), 'SUGARAPPLE': ('atis', None),
    'SWEET ORANGE': ('citrus', 'sweet orange'),
}
# The vegetable sheet has its own soybean (vegetable soybean, edamame).
SHEET_FILE_KIND = {('nsic-vegetable.xlsx', 'SOYBEAN'): ('soybean', 'vegetable soybean'),
                   ('nsic-fieldlegume.xlsx', 'SOYBEAN'): ('soybean', 'grain soybean')}

NA = re.compile(r'^(n\.?\s*a\.?|na|none|nd|n\.?d\.?|not available|\-+|\?|0)$', re.I)
# Owners written in capitals that are names, not acronyms, get plain case.
ACRONYM_OWNERS = {'USMARC', 'PCAARRD', 'PHILFIDA', 'BNSCRDPSC', 'NCRDPSC', 'CVIARC', 'DMMMSU', 'PHILMECH', 'CLSURE', 'NOMIARC', 'WESVIARC', 'CAVSU'}
FIX_WORDS = {'Yielg': 'Yield', 'Pupl': 'Pulp', 'Pant ': 'Plant ', 'Eariness': 'Earliness', 'Straigth': 'Straight',
             'Hyrbrid': 'Hybrid', 'neckled': 'necked', 'obaservation': 'observation', 'Pangansinan': 'Pangasinan'}
BANNED_BITS = re.compile(r'\b(robust|world|dynamic|significant\w*|specific\w*|crucial|vital|superior|excellent|best|unique|premium)\b', re.I)

# ---- Fixes of the registry list's own slips (2026-10-20, the owner: "yes, fix all"), each settled
# from the registration papers or the crop's sane range. Applied on every build (apply_fixes).
# A trait the list names wrongly, and the traits its cells cannot be trusted for.
TRAIT_LABEL = {('cacao', 'Pod index (cm)'): 'Pod index (pods for 1 kg of dry beans)'}
TRAIT_DROP = {
    # Fresh to dry ratios of 1.1 and 233.57 side by side: the column mixes weights, ratios and percents.
    ('cacao', 'Bean fresh wt to dry weight ratio.'), ('coffee', 'Bean fresh wt to dry wt ratio (percent)'),
}
# Root crops can pass 40 t/ha (PSB Cv 10 gave 44.8): the 40 t/ha ceiling that catches a kg/ha
# figure in a t/ha column is 80 for them.
ROOT_YIELD = {'cassava', 'ubi', 'tugi', 'taro', 'sweetpotato', 'potato'}
# One cell, by crop, variety and trait: the right figure, or None where it cannot be settled (the
# figure leaves the page; nothing is put in its place). '@height' and '@yield' are the record's own.
FIGURE_FIX = {
    # A decimal point lost, or centimeters written in a millimeter column and back.
    ('sugarcane', 'UPLB LUZONFED G6', 'Stalk diameter (cm)'): '2.32',
    ('banana', 'Lakatan', 'Fruit width (cm)'): '3.5',
    ('banana', 'Latundan', 'Fruit width (cm)'): '2.5',
    ('cashew', 'Dayap', 'Nut length (mm)'): '33', ('cashew', 'Dayap', 'Nut width (mm)'): '25',
    ('cashew', 'Farinas', 'Nut length (mm)'): '33', ('cashew', 'Farinas', 'Nut width (mm)'): '25',
    ('chico', 'Gonzales', 'Flesh sweetness (brix)'): '22', ('chico', 'Pineras', 'Flesh sweetness (brix)'): '22',
    ('coffee', 'CvSU Barako', 'Tree length of longest lateral (cm)'): '110',
    ('coffee', 'CvSU Kapeng Manipis', 'Tree length of longest lateral (cm)'): '140',
    ('durian', 'GD 69', 'Seed width (cm)'): '4', ('durian', 'GD 69', 'Skin thickness (mm)'): '12',
    ('durian', 'Lacson Uno', 'Skin thickness (mm)'): '16', ('durian', 'Oboza', 'Skin thickness (mm)'): '14',
    ('jackfruit', 'Jo 2', 'Seed length (cm)'): '3.5', ('jackfruit', 'Jo 2', 'Seed width (cm)'): '1.5',
    ('jackfruit', 'Bat.-Jf 0 4', 'Whole fruit length (cm)'): '59', ('jackfruit', 'Bat.-Jf 0 4', 'Whole fruit width (cm)'): '31.2',
    ('rambutan', 'JMD RRR', 'Seed length (cm)'): '2.47', ('rambutan', 'JMD RRR', 'Seed width (cm)'): '1.44',
    ('cacao', 'K1', 'Pod wall thickness (cm)'): '0.87',
    ('pili', 'Lavesarez 01', 'Whole Fruit Size length (mm)'): '76',
    ('mungbean', 'Pagasa 13', '@height'): 54.0,
    # Far outside the crop and no way to tell the right figure: left out.
    ('cacao', 'USM CH 1', 'Tree canopy diameter (cm)'): None, ('cacao', 'USM CH 2', 'Tree canopy diameter (cm)'): None,
    ('cashew', 'R2T3 QP Recto', 'Kernel weight (g)'): None,
    ('chico', 'Chikok', 'Seed length (cm)'): None,
    ('jackfruit', 'Baybay Sweet', 'Seed weight (g)'): None,
    ('mango', 'MMSU Gold', 'Peel thickness (mm)'): None,
    ('mungbean', 'Mabunga 4', 'Seed crude fat'): None,   # its crude protein again
    ('mungbean', 'VC 6510 151', 'Seed crude fat'): None,  # 0.04, a twentieth of the others
    ('mungbean', 'Kinang', 'Seed crude protein'): None,
    ('mungbean', 'Mabunga 3', 'Seed crude protein'): None, ('mungbean', 'Mabunga 3', 'Seed carbohydrates'): None,
    # The papers give 118 and 116 cm (the trial rows keep them for the analyses).
    ('cotton', 'CRD 50 95 4 1 3 B', 'Plant height (cm)'): None, ('cotton', 'Bioseed 6527', 'Plant height (cm)'): None,
    ('soybean', 'AGS 191 (BPI #4)', '@yield'): None,   # 8.55 and 14.76 t/ha of soybean
}
# Other names: SG 94 17 17 (NSIC 2000 Sp 23) is UPL Sp 14 in its nomination papers and the papers'
# index; the list's UPL Sp 13 for it is the name of SG02 07 5 (NSIC 2011 Sp 32). The list also gives
# UPL Sp 14 to SG 96 35 03 (NSIC 2001 Sp 24), whose papers are not online: one name cannot be two
# varieties', so it leaves SG 96 35 03 until its papers settle it.
ALIAS_FIX = {
    ('sweetpotato', 'SG 94 17 17'): {'drop': ['UPL Sp 13'], 'add': ['UPL Sp 14']},
    ('sweetpotato', 'SG 96 35 03'): {'drop': ['UPL Sp 14']},
    ('cassava', 'PSB Cv 10'): {'add': ['MCol 1684']},
}
# One variety held twice: VC 5 is PSB Cv 10 (its breeder's page: "PSB Cv-10 (VC-5)(MCol 1684)",
# released 1993, for starch). The research note named VC 5 joins the registered record.
SAME = {('cassava', 'VC 5'): 'PSB Cv 10'}


def apply_fixes(recs):
    """The fixes above on the finished records (also run on varieties.json as it stands): the
    variety held twice merged, the names, then the cells. Safe to run again."""
    by = {(r['crop'], r['name']): r for r in recs}
    for (crop, name), into in SAME.items():
        a, b = by.get((crop, name)), by.get((crop, into))
        if a and b:
            if a.get('research') and not b.get('research'):
                b['research'] = a['research']
            b['aliases'] = list(dict.fromkeys(b['aliases'] + [x for x in [a['name']] + a['aliases'] if key_name(x) != key_name(b['name'])]))[:6]
            recs = [r for r in recs if r is not a]
    by = {(r['crop'], r['name']): r for r in recs}
    for k, fx in ALIAS_FIX.items():
        r = by.get(k)
        if not r:
            continue
        old = list(r['aliases'])
        al = [a for a in r['aliases'] if key_name(a) not in [key_name(d) for d in fx.get('drop', [])]]
        for a in fx.get('add', []):
            if key_name(a) not in [key_name(x) for x in al]:
                # In the place of the name it replaces, else last.
                at = next((i for i, x in enumerate(old) if key_name(x) in [key_name(d) for d in fx.get('drop', [])]), len(al))
                al.insert(min(at, len(al)), a)
        r['aliases'] = al
    for (crop, name, label), val in FIGURE_FIX.items():
        r = by.get((crop, name))
        if not r:
            continue
        if label == '@height':
            if r.get('height'):
                r['height'] = dict(r['height'], value=val) if val is not None else None
        elif label == '@yield':
            if val is None:
                r['yield'] = None
        else:
            r['traits'] = [[l, val if l == label else v] for l, v in r['traits'] if not (l == label and val is None)]
    return recs


def fixwords(s):
    for a, b in FIX_WORDS.items():
        s = s.replace(a, b)
    return s


def num(s):
    """The numbers in a cell, as floats: '5.81/4.82' -> [5.81, 4.82]."""
    return [float(x.replace(',', '')) for x in re.findall(r'\d[\d,]*\.?\d*', s or '') if re.match(r'^\d', x)]


def tidy_number(x):
    if abs(x) >= 100:
        return '{:,.0f}'.format(x) if abs(x) >= 10000 else ('%.0f' % x if x == int(x) else ('%.1f' % x).rstrip('0').rstrip('.'))
    return ('%.2f' % x).rstrip('0').rstrip('.')


def clean(v, percent=False):
    """A cell as text a page may show: no hyphens, no percent signs, no
    long float tails ('0.7408' in a percent column -> '74.1')."""
    v = fixwords((v or '').strip())
    if not v or NA.match(v):
        return None
    v = re.sub(r'\s*(\+/-|±)\s*[\d.,]+', '', v)
    v = re.sub(r'\b\d+\.\d{3,}\b', lambda m: tidy_number(float(m.group(0)) * (100 if percent and float(m.group(0)) <= 1.5 else 1)), v)
    if percent and re.fullmatch(r'[01]?\.\d+|1|0', v) and float(v) <= 1:
        v = tidy_number(float(v) * 100) + ' percent'
    v = re.sub(r'(?<=\d)\s*-\s*(?=\d)', ' to ', v)
    v = re.sub(r'(?<=[A-Za-z])\s*-\s*(?=[A-Za-z0-9])|(?<=[0-9])-(?=[A-Za-z])', ' ', v)
    v = v.replace(' - ', ', ').replace('-', ' ').replace('–', ' to ').replace('~', 'about ')
    v = v.replace('&', 'and').replace('%', ' percent').replace(';', ',').replace('°', ' ')
    v = re.sub(r'\s+', ' ', v).strip(' ,.')
    return v or None


def pretty(label):
    """'Fruit Characteristics | Weight (g)' -> 'Fruit weight (g)'."""
    label = fixwords(label)
    parts = [p.strip() for p in label.split('|') if p.strip()]
    parts = [p for p in parts if p not in ('DS', 'WS')]
    groups = []
    for p in parts[:-1]:
        g = re.sub(r'\s*Characteristics?\b', '', p, flags=re.I).strip()
        g = {'Varietal': '', 'Other Varietal': '', 'Seed Size': 'Seed', 'Quality and Grading': 'Cup', 'Whole Fruit': 'Fruit',
             'Latex and Rubber Properties': 'Latex', 'Agronomic Yield and Chemical': '', 'Seed Quality (%)': 'Seed',
             'Morphological (Leaf)': 'Leaf', 'Pulp (fresh)': 'Pulp', 'Tree': 'Tree', 'Plant': 'Plant'}.get(g, g)
        if g:
            groups.append(g)
    last = parts[-1] if parts else ''
    last = re.sub(r'(?i)tss\s*(\([^)]*\))?', 'Sweetness (Brix)', last.replace('\ufffd', ''))
    last = re.sub(r'\s*\(\s*%\s*\)', ' (percent)', last)
    last = re.sub(r'\s+-\s+|(?<=\w)-(?=\w)', ' ', last).replace('/ ', ' ').replace(' /', ' ')
    # Plain case: 'Edible Portion (Meat)' -> 'edible portion (meat)', HCN kept.
    last = ' '.join(w if re.fullmatch(r'\(?[A-Z]{2,}\w*\)?', w) else w.lower() for w in last.split())
    last = last.replace('t.ha', 't/ha').replace('%', 'percent')
    g = groups[-1] if groups else ''
    g = re.sub(r'\s*/.*$', '', g)
    if g and not last.lower().startswith(g.lower().split()[0]):
        out = '%s %s' % (g, last[:1].lower() + last[1:])
    else:
        out = last
    out = re.sub(r'\s+', ' ', out).strip()
    out = re.sub(r'(?i)\(yrs\.? after planting\)', '(years after planting)', out).replace('(no. of months)', '(months)').replace('(mos.)', '(months)')
    out = re.sub(r'(?i)^(\w+) \1\b', r'\1', out)
    return out[:1].upper() + out[1:]


def season_of(label):
    m = re.search(r'\|\s*(DS|WS)\s*$', label)
    return m.group(1) if m else None


RATING_WORDS = [('highly resistant', 'HR'), ('moderately resistant', 'MR'), ('moderately susceptible', 'MS'),
                ('highly susceptible', 'HS'), ('resistant', 'R'), ('susceptible', 'S'), ('intermediate', 'I'), ('tolerant', 'T')]


def rating(v):
    v = (v or '').strip()
    if not v or NA.match(v):
        return None
    m = re.match(r'^\(?\s*(HR|MR|MS|HS|R|I|S|T)\b', v)
    if m:
        return m.group(1)
    low = v.lower()
    for w, c in RATING_WORDS:
        if w in low:
            return c
    return None


PEST_NAMES = {'CLS': 'Cercospora leaf spot', 'CBB': 'Cassava bacterial blight', 'BLB': 'Bacterial leaf blight', 'Borer': 'Corn borer',
              'Stalkrot': 'Stalk rot', 'Earworm': 'Corn earworm', 'Root-rot': 'Root rot', 'Rats': 'Rats', 'Stem Borers': 'Stem borers',
              'Virus': 'Virus diseases', 'Brown Age Leaf Spot': 'Brown leaf spot'}


def pest_name(sub):
    sub = sub.strip()
    if sub in PEST_NAMES:
        return PEST_NAMES[sub]
    return sub[:1].upper() + sub[1:].lower()


def adaptation(text):
    """Island groups, seasons and elevation named in a recommendation."""
    t = (text or '').lower()
    out = {'domain': [], 'seasons': [], 'elevation': []}
    if re.search(r'nationwide|national|all regions|anywhere in the (country|philippines)|throughout the (country|philippines)', t):
        out['domain'] = ['National']
    else:
        for isl in ('Luzon', 'Visayas', 'Mindanao'):
            if isl.lower() in t:
                out['domain'].append(isl)
    if re.search(r'either wet or dry|both (wet and dry|seasons)|wet and dry|dry and wet|all year|year round|whole year', t):
        out['seasons'] = ['wet', 'dry']
    else:
        if re.search(r'wet season|rainy season|\bws\b', t):
            out['seasons'].append('wet')
        if re.search(r'dry season|\bds\b', t):
            out['seasons'].append('dry')
    if re.search(r'high(er)? elevation|highland|mid ?elevation|upland area|mountain', t):
        out['elevation'].append('highland' if 'mid' not in t else 'mid elevation')
    if re.search(r'low(er)? elevation|lowland', t):
        out['elevation'].append('lowland')
    return out


def slugify(s):
    s = s.lower().replace('ñ', 'n').replace('+', ' plus ')
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


def key_name(s):
    return re.sub(r'[^a-z0-9]', '', (s or '').lower().replace('dekalb', 'dk').replace('pioneer', 'p'))


def key_reg(s):
    return re.sub(r'[^a-z0-9]', '', (s or '').lower())


def nice_name(raw):
    """The display name and its other names: 'SAPT 84HYC / Magilas 84' is
    sold as Magilas 84; a hyphen (STYLE.md) becomes a space."""
    raw = fixwords(re.sub(r'\s+', ' ', raw or '').strip())
    raw = re.sub(r"[‘’“”']", '', raw).strip()
    parts = [p.strip() for p in re.split(r'\s+/\s*|\s*/\s+', raw) if p.strip()]
    clean_ = lambda p: re.sub(r'(?<=[A-Za-z0-9])\s*-\s*(?=[A-Za-z0-9])', ' ', p).replace('&', 'and').strip()
    parts = [clean_(p) for p in parts]
    parts = [re.sub(r'^PHIL\b', 'Phil', p) for p in parts]
    if len(parts) == 1:
        m = re.match(r'^(.*?)\s*\(([^)]*)\)\s*$', parts[0])
        if m and re.search(r'[A-Za-z]{4,}', m.group(2)) and re.search(r'\d', m.group(1)) and not re.search(r'(?i)acce|selection|hybrid|var\.', m.group(2)):
            return m.group(2).strip(), [m.group(1).strip()]
    if len(parts) > 1:
        # The commercial name is the one with a word in it.
        named = [p for p in parts if re.search(r'[A-Za-z]{4,}', p) and not re.search(r'\d{3,}', p)]
        main = named[0] if named else parts[0]
        return main, [p for p in parts if p != main]
    return parts[0] if parts else raw, []


def corn_kind(t, name):
    t = fixwords(t or '').lower()
    color = 'white' if t.startswith('white') else 'yellow' if t.startswith('yellow') else None
    glut = 'glutinous' in t or re.search(r'glut|lagkitan|waxy|malagkit', name or '', re.I)
    if 'genetically' in t or 'gm resistance' in t:
        kind = 'GM hybrid'
    elif 'hybrid' in t and 'non inbred' not in t.replace('-', ' '):
        kind = 'hybrid'
    else:
        kind = 'open pollinated'
    if 'special' in t:
        kind = 'special open pollinated'
    return color, ('glutinous ' if glut else '') + kind


def normalize(r):
    sheet = r['sheet'].strip()
    crop, kind = SHEET_FILE_KIND.get((r['file'], sheet)) or SHEETS.get(sheet, (None, None))
    if not crop:
        return None
    cols = r['cols']
    name, aliases = nice_name(r['name'])
    if not name or len(name) > 60:
        return None
    rec = {'crop': crop, 'name': name, 'aliases': aliases, 'reg': clean(r['reg']) if re.search(r'NSIC|PSB|Seed Board', r['reg'] or '') else None,
           'year': int(r['year']) if re.match(r'^\d{4}$', r['year'] or '') else None,
           'owner': clean(re.sub(r'\b(Inc|Corp|Corporation|Phils?|Philippines?|Co|OPC)\b\.?,?', '', r['owner'] or '')).strip(' ,.') if r['owner'] else None,
           'kind': kind, 'color': None, 'localName': None, 'uses': None,
           'maturity': None, 'yield': None, 'height': None, 'traits': [], 'reactions': [],
           'domain': [], 'seasons': [], 'elevation': [], 'sources': ['https://nsic.buplant.da.gov.ph/registry.php']}
    if rec['owner']:
        rec['owner'] = re.sub(r'\(\s+', '(', re.sub(r'(?i)corn\s*world', 'Cornworld', rec['owner'])).strip()
        # SYNGENTA -> Syngenta; acronyms (PCA, UPLB, USMARC) stay.
        rec['owner'] = re.sub(r'\b[A-Z]{6,}\b', lambda m: m.group(0) if m.group(0) in ACRONYM_OWNERS else {'PHILRICE': 'PhilRice', 'PHILSCAT': 'PhilSCAT'}.get(m.group(0), m.group(0).title()), rec['owner'])
        rec['owner'] = re.sub(r'\s+(and|,)$', '', rec['owner']).strip(' ,')
    if rec['owner'] and re.fullmatch(r'(the )?philippine seed board|psb', rec['owner'], re.I):
        rec['owner'] = None
    if crop == 'corn':
        rec['color'], rec['kind'] = corn_kind(cols.get('Type of Variety'), r['name'])

    mats, yields, heights = {}, {}, {}
    adapt_text = []
    for label, val in cols.items():
        L = label.lower()
        s = season_of(label)
        base = re.sub(r'\s*\|\s*(DS|WS)\s*$', '', label)
        bl = base.lower()
        if re.search(r'address|location of tree|agency|developer|^no\.|crop species|^type of variety$|^type$', bl):
            if bl in ('type', 'crop species') and val:
                rec['traits'].append([('Type' if bl == 'type' else 'Species'), clean(val)])
            continue
        if re.search(r'agro.?climatic|adaptation|recommendation domain|adaptability|reasons for recommending|remarks', bl):
            if 'adaptability' in bl:
                if re.search(r'^\s*(yes|x|✓|adapted|\+)', val, re.I) or rating(val) in ('R', 'MR', 'T'):
                    rec['elevation'].append('highland' if 'high' in bl else 'lowland')
                continue
            adapt_text.append(val)
            continue
        if bl == 'local name':
            ln, _ = nice_name(val)
            if ln and key_name(ln) != key_name(name):
                if re.search(r'\d', ln):
                    rec['aliases'].append(ln)
                else:
                    rec['localName'] = ln
            continue
        if re.search(r'utilization|recommended use', bl):
            rec['uses'] = clean(val)
            continue
        if re.search(r'reaction to|damage due to|^diseases \|', bl) or re.match(r'^powdery mildew$', bl):
            sub = base.split('|')[-1].strip()
            c = rating(val)
            text = clean(val)
            if c or text:
                rec['reactions'].append([pest_name(sub), c or text])
            continue
        # Cacao's "Yield (kg/tree/year)" holds kilograms of dry beans a hectare, not a tree
        # (2026-10-20): the registration papers print each clone's dry beans a tree, and this
        # column is that times the trees a hectare (1,111, or 888 for BR 25). Read as tonnes of
        # dry beans a hectare a year.
        if crop == 'cacao' and re.search(r'yield \(kg/tree/year\)', bl):
            n = [x for x in num(val) if x > 0]
            if n:
                rec['yield'] = {'value': round(n[0] / 1000, 2), 'unit': 't dry beans/ha', 'regional': None, 'seasons': None}
            continue
        if re.search(r'maturity|days to mature|days to harvest|days to first harvest|1st harvest', bl) and not re.search(r'last harvest|flowering', bl):
            mats.setdefault(base, {})[s or 'all'] = val
            continue
        if re.search(r'\byield\b|copra/ha|computed yield', bl) and not re.search(r'leaf yield|cocoon|per tree|copra/nut|copra/palm|/\s*tree|\((g|kg)\)|kg/tree|tapping', bl):
            yields.setdefault(base, {})[s or 'all'] = val
            continue
        if re.search(r'plant height|^height', bl) and 'last harvest' not in bl:
            heights.setdefault(base, {})[s or 'all'] = val
            continue
        if re.search(r'^no\. \| 1|plant stand|grade index|nicotine', bl):
            continue
        pct = '%' in label or 'percent' in label.lower() or bool(re.search(r'recovery|content|portion|drying method|dry matter', bl))
        # Mungbean seed holds about 1 percent fat: its 0.51 is 0.51 percent, never a share to multiply (it read "51 percent").
        if crop == 'mungbean' and 'crude fat' in bl:
            pct = False
        v = clean(val, percent=pct)
        if v and not BANNED_BITS.search(v) and len(v) <= 80:
            rec['traits'].append([pretty(base) + (', %s' % {'DS': 'dry season', 'WS': 'wet season'}[s] if s else ''), v])

    # Maturity: the first matching column, the dry and wet season averaged.
    for base, by in mats.items():
        vals = [x for k in ('DS', 'WS', 'all') for x in num(by.get(k, ''))[:2]]
        vals = [x for x in vals if x > 0]
        if not vals:
            continue
        months = 'month' in base.lower()
        rec['maturity'] = {'lo': min(vals), 'hi': max(vals), 'value': round(statistics.mean(vals), 1),
                           'unit': 'months' if months else 'days',
                           'first': bool(re.search(r'first|1st', base, re.I)),
                           'text': clean(' / '.join('%s %s' % ({'DS': 'dry season', 'WS': 'wet season'}.get(k, ''), by[k]) for k in ('DS', 'WS', 'all') if by.get(k))).strip()}
        break
    for base, by in yields.items():
        raw = ' '.join(by.get(k, '') for k in ('DS', 'WS', 'all'))
        vals = [x for x in num(raw) if x > 0]
        if not vals:
            continue
        bl = base.lower()
        unit = 'LKg/ha' if 'lkg' in bl else 't copra/ha' if 'copra' in bl else 'kg/ha' if 'kg/ha' in bl else 't/ha' if re.search(r't/ha|t\.ha|mt/ha|t/ ha|\(t', bl) else None
        if unit is None:
            rec['yield'] = {'value': None, 'unit': None, 'text': clean(raw)}
        else:
            # Region by region ('Luzon 10.32, Visayas 7.10'): their average.
            regional = re.findall(r'(Luzon|Visayas|Mindanao)\s*[:=\-]?\s*(\d+\.?\d*)', raw)
            if regional:
                vals = [float(x) for _, x in regional]
            vals = [x for x in vals if (x < (80 if crop in ROOT_YIELD else 40) if unit in ('t/ha', 't copra/ha') else True)]
            if not vals:
                continue
            rec['yield'] = {'value': round(statistics.mean(vals), 2), 'unit': unit or 't/ha',
                            'regional': [[a, float(b)] for a, b in regional] if regional else None,
                            'seasons': {k: num(by[k])[0] for k in ('DS', 'WS') if by.get(k) and num(by[k])} or None}
        break
    for base, by in heights.items():
        vals = [x for k in ('DS', 'WS', 'all') for x in num(by.get(k, ''))[:2] if x > 0]
        if vals:
            rec['height'] = {'value': round(statistics.mean(vals), 1), 'unit': 'm' if '(m)' in base else 'cm'}
            break
    a = adaptation(' '.join(adapt_text))
    rec['domain'] = a['domain']
    rec['seasons'] = a['seasons']
    rec['elevation'] = sorted(set(rec['elevation'] + a['elevation']))
    # Corn's own domain column, when the text named none.
    if crop == 'corn' and not rec['domain']:
        rec['domain'] = adaptation(cols.get('Recommendation Domain', ''))['domain']
    rec['traits'] = [[TRAIT_LABEL.get((crop, t[0]), t[0]), t[1]] for t in rec['traits'] if t[1] and (crop, t[0]) not in TRAIT_DROP][:30]
    # The registry's own type column (mango: Carabao; coffee: Robusta) names its kind.
    if not rec['kind']:
        for lab, val in rec['traits']:
            if lab in ('Type', 'Species') and val and len(val) <= 24 and not re.search(r'\d', val):
                rec['kind'] = val.lower() if val.lower() not in ('carabao', 'pico', 'indian') else val
                break
    return rec


def richness(r):
    return (r['maturity'] is not None) * 3 + (r['yield'] is not None) * 3 + len(r['traits']) + len(r['reactions'])


def load_research():
    out = []
    if not RESEARCH:
        return out
    for f in sorted(glob.glob(os.path.join(RESEARCH, '*.json'))):
        if os.path.basename(f).startswith(('part', 'final')):
            continue   # a researcher's unchecked notes, or the pieces merged into one file
        try:
            rows = json.load(open(f, encoding='utf-8'))
        except Exception as e:  # noqa
            print('skip', f, e)
            continue
        for x in rows if isinstance(rows, list) else []:
            srcs = [u for u in (x.get('sources') or []) if isinstance(u, str) and u.startswith('http') and 'pinoyrice.com' not in u]
            if x.get('crop') and x.get('name') and srcs:
                x['sources'] = srcs[:4]
                out.append(x)
    return out


CROP_OF_RESEARCH = {'corn_yellow': 'corn', 'corn_white': 'corn', 'corn_glutinous': 'corn', 'corn_sweet': 'corn'}


def main():
    recs = [n for n in (normalize(r) for r in nsic.records(XL)) if n]

    # A season's day count far from the rest of its crop is a cell typed in
    # the wrong column (PAC 999 Super: 108 days dry, "197.1" wet, its height).
    meds = {}
    for r in recs:
        if r['maturity']:
            meds.setdefault((r['crop'], r['maturity']['unit']), []).append(r['maturity']['value'])
    meds = {k: statistics.median(v) for k, v in meds.items() if len(v) >= 5}
    for r in recs:
        m = r['maturity']
        med = meds.get((r['crop'], m['unit'])) if m else None
        if not med or m['hi'] <= m['lo'] * 1.35:
            continue
        keep = min((m['lo'], m['hi']), key=lambda x: abs(x - med))
        r['maturity'] = dict(m, lo=keep, hi=keep, value=keep, text=None)

    # One record per name per crop: a variety registered again (a wider
    # domain, a new owner) keeps the record with the most facts.
    by = {}
    for r in recs:
        k = (r['crop'], key_name(r['name']))
        if k in by:
            old = by[k]
            keep, other = (r, old) if richness(r) > richness(old) or (richness(r) == richness(old) and (r['year'] or 0) > (old['year'] or 0)) else (old, r)
            keep.setdefault('otherRegs', [])
            if other['reg'] and other['reg'] != keep['reg']:
                keep['otherRegs'].append(other['reg'])
            keep['aliases'] = list(dict.fromkeys(keep['aliases'] + other['aliases']))
            by[k] = keep
        else:
            by[k] = r
    recs = list(by.values())

    # The research: matched to a registered variety by its NSIC number or a
    # name, else a variety of its own (most private vegetable hybrids are
    # never registered).
    # By number only: "Philippine Seed Board" names no one variety.
    regidx = {key_reg(r['reg']): r for r in recs if r['reg'] and any(ch.isdigit() for ch in r['reg'])}
    nameidx = {}
    for r in recs:
        for nm in [r['name']] + r['aliases']:
            nameidx.setdefault((r['crop'], key_name(nm)), r)
    added = 0
    for x in load_research():
        crop = CROP_OF_RESEARCH.get(x['crop'], x['crop'])
        hit = regidx.get(key_reg(x.get('nsic'))) if x.get('nsic') and any(ch.isdigit() for ch in x['nsic']) else None
        if not hit and (crop, nice_name(x['name'])[0]) in SAME:
            hit = nameidx.get((crop, key_name(SAME[(crop, nice_name(x['name'])[0])])))
        if not hit:
            for nm in [x['name']] + (x.get('aliases') or []):
                hit = nameidx.get((crop, key_name(nm)))
                if hit:
                    break
        if hit and hit['crop'] != crop:
            hit = None
        note = {k: x.get(k) for k in ('company', 'kind', 'popular', 'facts', 'claims', 'sources') if x.get(k)}
        names = [nice_name(a)[0] for a in [x['name']] + (x.get('aliases') or [])]
        if hit:
            hit['research'] = note
            hit['aliases'] = list(dict.fromkeys(hit['aliases'] + [a for a in names if key_name(a) != key_name(hit['name'])]))[:6]
            if crop == 'corn' and x['crop'] == 'corn_sweet':
                hit['kind'] = 'sweet corn ' + ('hybrid' if 'hybrid' in (hit['kind'] or '') else 'open pollinated')
        else:
            main, more = nice_name(x['name'])
            r = {'crop': crop, 'name': main, 'aliases': list(dict.fromkeys(more + [a for a in names[1:] if key_name(a) != key_name(main)]))[:6],
                 'reg': None, 'year': None, 'owner': x.get('company'), 'kind': x.get('kind'), 'color': None, 'localName': None,
                 'uses': (x.get('facts') or {}).get('uses'), 'maturity': None, 'yield': None, 'height': None, 'traits': [], 'reactions': [],
                 'domain': [], 'seasons': [], 'elevation': [], 'sources': [], 'research': note}
            if crop == 'corn':
                r['color'] = 'white' if re.search(r'white', x.get('kind') or '', re.I) else 'yellow' if re.search(r'yellow', x.get('kind') or '', re.I) else None
                if x['crop'] == 'corn_sweet':
                    r['kind'] = 'sweet corn ' + ('hybrid' if re.search(r'hybrid', x.get('kind') or '', re.I) else 'open pollinated')
                elif x['crop'] == 'corn_glutinous':
                    r['kind'] = 'glutinous ' + ('hybrid' if re.search(r'hybrid', x.get('kind') or '', re.I) else 'open pollinated')
            recs.append(r)
            nameidx[(crop, key_name(main))] = r
            added += 1

    recs = apply_fixes(recs)
    # A page needs something to say: trial numbers, traits, or research.
    keep = [r for r in recs if r.get('research') or r['maturity'] or r['yield'] or len(r['traits']) >= 4]
    seen = set()
    for r in sorted(keep, key=lambda r: (-(r.get('research') is not None), -(r['year'] or 0))):
        base = '%s-%s' % (crop_slug(r['crop']), slugify(r['name']))
        slug, i = base, 2
        while slug in seen:
            slug, i = '%s-%d' % (base, i), i + 1
        seen.add(slug)
        r['slug'] = slug
    keep.sort(key=lambda r: (r['crop'], -(r['year'] or 0), r['name']))
    # No agency or institute as the source of a fact (the owner, 2026-10-17): the notes say it as our own.
    from build_pages import unsource_page  # noqa: E402
    keep = unsource_page(keep)
    json.dump(keep, open(os.path.join(HERE, 'varieties.json'), 'w', encoding='utf-8', newline='\n'), ensure_ascii=False, indent=1)
    from collections import Counter
    c = Counter(r['crop'] for r in keep)
    print(len(recs), 'records,', added, 'from research only,', len(keep), 'with a page')
    print(', '.join('%s %d' % kv for kv in c.most_common()))


CROP_SLUGS = {'stringbean': 'sitao', 'snapbean': 'snap-bean', 'chinesecabbage': 'chinese-cabbage', 'limabean': 'lima-bean',
              'wingedbean': 'winged-bean', 'bellpepper': 'bell-pepper', 'sweetpotato': 'sweet-potato', 'ubi': 'ube',
              'starapple': 'star-apple', 'pigeonpea': 'pigeon-pea'}


def crop_slug(key):
    return CROP_SLUGS.get(key, key.replace('_', '-'))


if __name__ == '__main__':
    main()
