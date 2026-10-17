"""Writes one public page per crop variety, database/site-pages/crop-varieties/
{slug}.json, from varieties.json (see build_data.py) and crops.json. The facts
are the NSIC registry's and the research notes'; every sentence around them is
anee.io's own: what the numbers mean, how the variety compares with others of
its crop, and which pests, diseases and guides matter for it. The heroes are
anee.io's own farm photos. No page names the NSIC registry, the Bureau of Plant
Industry or any other agency or institute as where its facts come from (the
owner, 2026-10-17): the facts are said as our own, and the sources block keeps
only the seller's and farm sources' own pages. Pages follow
database/site-pages/STYLE.md and pass check.py.

    python database/crop-varieties/build_pages.py
"""
import json
import os
import re
import statistics
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
SITE = os.path.join(HERE, '..', 'site-pages')
OUT = os.path.join(SITE, 'crop-varieties')

CROPS = json.load(open(os.path.join(HERE, 'crops.json'), encoding='utf-8'))
C = CROPS['crops']
HEROES = CROPS['heroes']

sys.path.insert(0, SITE)
from check import is_gov, is_gov_url  # noqa: E402  (the checker's own list of what may not show as a source)
RATING = {
    'HR': ('Highly resistant', 'held up very well'),
    'R': ('Resistant', 'held up well'),
    'MR': ('Moderately resistant', 'took some damage but held up'),
    'T': ('Tolerant', 'took damage but still yielded'),
    'I': ('Intermediate', 'took a middling share of damage'),
    'MS': ('Moderately susceptible', 'took clear damage'),
    'S': ('Susceptible', 'took heavy damage'),
    'HS': ('Highly susceptible', 'took very heavy damage'),
}
GOOD = ('HR', 'R', 'MR', 'T')
BAD = ('MS', 'S', 'HS')
BANNED = re.compile(r"\b(moreover|furthermore|additionally|lastly|therefore|ultimately|utilize|implement|regarding|thus|despite|landscape|realm|"
                    r"tailored|dynamic|world|ecosystem|solutions?|significant\w*|specific\w*|numerous|robust|seamless|leverage|elevate|harness|"
                    r"comprehensive|boasts|vibrant|journey|crucial|vital|pivotal|unlock|foster|empower|streamline|enhancing|craft|glance|engaging)\b", re.I)


def article(word):
    w = (word or 'x').split(' ')[0]
    if re.fullmatch(r'[A-Z][A-Z0-9]+', w):
        return 'an' if w[0] in 'AEFHILMNORSX' else 'a'   # an SRA clone, an F1
    return 'an' if w[:1].lower() in 'aeiou' else 'a'


def fmt(x, nd=1):
    if x is None:
        return ''
    if abs(x - round(x)) < 0.05:
        return '{:,}'.format(int(round(x)))
    return ('%.*f' % (nd, x)).rstrip('0').rstrip('.')


def safe(s):
    """A research note as page text: no hyphens, percent signs, ampersands or
    semicolons (STYLE.md), and nothing with a banned word in it."""
    s = (s or '').strip()
    if not s:
        return ''
    # Variety codes keep their parts (Phil 97-3933, IR 64-616); ranges read "to".
    s = re.sub(r'\b((?:Phil|PHIL|[A-Z]{2,}[a-z]?)\s?\d+)\s*[-–]\s*(?=\d)', r'\1 ', s)
    s = re.sub(r'(?<=\d)\s*[-–]\s*(?=\d)', ' to ', s)
    s = re.sub(r'(?<=\w)[-–](?=\w)', ' ', s)
    # A source named in brackets in a note: the sources list names it already.
    s = re.sub(r'\s*\([^()]*\b(?:wikipedia|registry|study|paper|report|news|journal|survey|pcaarrd|ciat|agris|guide|catalog|catalogue|brochure|website|region|company post|\d{4})\b[^()]*\)', '', s, flags=re.I)
    s = s.replace(' - ', ', ').replace(' – ', ', ').replace('—', ', ').replace('%', ' percent').replace('&', 'and').replace(';', ',')
    s = s.replace('‘', '').replace('’', "'").replace('“', '').replace('”', '').replace('"', '').replace('…', '.')
    s = s.replace('->', 'to').replace('~', 'about ').replace('°', ' degrees ')
    s = re.sub(r'(?i)\bcorn\s+world\b', 'Cornworld', s).replace('Cornworld (Cornworld)', 'Cornworld')
    s = re.sub(r'\s+', ' ', s).strip(' ,')
    if BANNED.search(s):
        return ''
    return s


# ---- no agency or institute as the source of a fact (the owner, 2026-10-17)
# The research notes cite them in brackets and as subjects ("(DA Caraga)",
# "BPI gives 90 to 110 days"). The fact stays, said as our own; a variety
# named after one (BPI Cp 4, PCA 15 3, UPLB Gold, Pagasa 7) keeps its name.
GOVN = (r"(?:the\s+)?(?:DA(?:\s+(?:BAR|RFO\s*\d+|Caraga|CAR|Region\s+\d+))?|BPI(?!\s*-?\s*(?:[A-Z][a-z]{0,2}\s*-?\s*)?\d)(?:\s+station)?"
        r"|PCA(?!\s*\d)|PhilRice|PCAARRD|BSWM|IRRI|FAO|NSIC(?:\s+registry)?(?!\s*(?:\d{4}\s*)?(?:[A-Z][a-z]{0,3}\s*-?\s*\d|\d))"
        r"|UPLB(?:\s+IPB)?|MMSU|USM|VSU|CLSU|(?:DOST\s+)?PCAARRD|DOST|the\s+registry|registry)")
GOV_PART = re.compile(GOVN + r"(?:\s+(?:derby|derbies|trials?|data|guide|station|records?|list|study|studies|project|survey|report))?", re.I)
GOV_SAYS = [
    # "DA-BPI's ampalaya guide", "BPI's Jan 2025 list" -> "the ampalaya guide", "the Jan 2025 list".
    (re.compile(r"\b(?:DA[- ]BPI|BPI(?!\s*-?\s*(?:[A-Z][a-z]{0,2}\s*-?\s*)?\d)|DA|PCA(?!\s*\d)|PhilRice)'s\s+"), 'the '),
    # "the Benguet State University organic cabbage trial", "two DMMMSU La Union trials" -> "the organic cabbage trial", "two La Union trials".
    (re.compile(r"\b(a|an|the|one|two|three|four)\s+(?:[A-Z][a-z]+\s+){1,3}State\s+University\s+"), r"\1 "),
    (re.compile(r"\b(a|an|the|one|two|three|four)\s+(?:UPLB|MMSU|USM|VSU|CLSU|DMMMSU|BSU|CvSU)\s+(?=[A-Za-z0-9])"), r"\1 "),
    # "by a DOST PCAARRD project", "in DOST PCAARRD gabi demo farms", "a 2017 DOST PCAARRD forum" -> the project, the farms, the forum.
    (re.compile(r"\b(?:DOST\s+)?PCAARRD\s+(?=[a-z0-9])"), ''),
    # "on DA Region 2's recommended rate" -> "on the recommended rate".
    (re.compile(r"\b(?:the\s+)?DA(?:\s+(?:Region|RFO)\s*\d+)?'s\s+"), 'the '),
    (re.compile(r"(^|[.;:]\s+)(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice)\s+recommends\s+it\b"), lambda m: m.group(1) + ('It is recommended' if m.group(1).strip() in ('', '.') else 'it is recommended')),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice)\s+describes\b"), 'also described as'),
    (re.compile(r"\bNSIC trials\b"), 'national trials'),
    (re.compile(r"\bthe NSIC registry gives the events as\b"), 'its registered events are'),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA|PhilRice)'s\s+guide\b"), 'the guide'),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice|NSIC)\s+gives\b"), 'trials give'),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice|NSIC)\s+lists it\b"), 'it is listed'),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice|NSIC)\s+lists\b"), 'the list has'),
    (re.compile(r"\b(?:the\s+)?(?:DA|BPI|PCA(?!\s*\d)|PhilRice)\s+recommends\b"), 'recommended'),
    (re.compile(r"\bthe (?:NSIC )?registry gives\b"), 'its record gives'),
]


def unsource_text(t):
    if not isinstance(t, str) or not t:
        return t

    def paren(m):
        parts = [x.strip() for x in re.split(r'\s*[,;]\s*', m.group(1))]
        left = [x for x in parts if x and not GOV_PART.fullmatch(x)]
        if len(left) == len(parts):
            return m.group(0)
        return (' (' + ', '.join(left) + ')') if left else ''
    t = re.sub(r'\s?\(([^()\n]{1,120})\)', paren, t)
    for rx, rep in GOV_SAYS:
        t = rx.sub(rep, t)
    return re.sub(r'(?<=\S) {2,}', ' ', t)


def unsource_page(o):
    if isinstance(o, dict):
        return {k: (v if k in ('url', 'src', 'slug', 'credit') else unsource_page(v)) for k, v in o.items()}
    if isinstance(o, list):
        return [unsource_page(x) for x in o]
    return unsource_text(o)


def sentence(s):
    s = safe(s)
    if not s:
        return ''
    s = s[:1].upper() + s[1:]
    return s if s.endswith(('.', '!', '?')) else s + '.'


# Words that may start a note put after "Why farmers know it:" in small letters. Any other first
# word is a name (Ramgo's, East West, PhilFIDA, Allied, Ilocos) and keeps its capital.
PLAIN_START = {'the', 'a', 'an', 'one', 'on', 'in', 'sold', 'listed', 'top', 'with', 'named', 'registered', 'best', 'highest',
               'among', 'called', 'introduced', 'low', 'ranked', 'shown', 'launched', 'still', 'second', 'tied', 'first', 'old',
               'topped', 'kept', 'large', 'big', 'tiny', 'sweet', 'showcased', 'check', 'about', 'produced', 'featured', 'over',
               'it', 'mother', 'father', 'grown', 'planted', 'bred', 'used', 'widely', 'popular', 'known', 'chosen', 'picked',
               'released', 'recommended', 'many', 'most', 'its', 'for', 'from', 'at', 'as', 'two', 'three', 'four', 'five',
               'very', 'uncultivated', 'subject', 'straight', 'several', 'seed', 'public', 'outyielded', 'long', 'erect', 'early',
               'dark', 'covered', 'considered', 'commercially', 'beat'}


def lower_start(s):
    """A sentence put after a colon: its first letter small only when its first word is an ordinary word."""
    w = re.sub(r'[^A-Za-z]', '', s.split()[0]) if s.split() else ''
    return s[:1].lower() + s[1:] if w.lower() in PLAIN_START else s


GROUP_GUIDES = {
    'fruit': [['/land-preparation/fruit-tree-land-preparation', 'Land preparation for fruit trees'], ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno'],
              ['/blog/fertilizer-for-fruit-trees', 'Fertilizer for fruit trees']],
    'plantation': [['/land-preparation/fruit-tree-land-preparation', 'Land preparation for tree crops'], ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno']],
    'industrial': [['/land-preparation/land-preparation-philippines', 'Land preparation in the Philippines'], ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno']],
    'rootcrop': [['/land-preparation/root-crop-land-preparation', 'Land preparation for root crops']],
    'legume': [['/land-preparation/legume-land-preparation', 'Land preparation for legumes']],
    'beanveg': [['/land-preparation/legume-land-preparation', 'Land preparation for legumes'], ['/crops/pagtatanim-ng-gulay', 'Pagtatanim ng gulay']],
}
for _sec in ('fruitveg', 'cucurbit', 'leafy', 'bulb'):
    GROUP_GUIDES[_sec] = [['/crops/pagtatanim-ng-gulay', 'Pagtatanim ng gulay'], ['/blog/fertilizer-for-vegetables-philippines', 'Fertilizer for vegetables'],
                          ['/land-preparation/vegetable-land-preparation', 'Land preparation for vegetables']]
GROUP_GUIDES['cereal'] = [['/land-preparation/corn-land-preparation', 'Land preparation for corn']]


def guides_of(key):
    c = C.get(key, {})
    out, seen = [], set()
    for g in (c.get('guides') or []) + GROUP_GUIDES.get(c.get('group'), []):
        if g[0] not in seen:
            seen.add(g[0])
            out.append(g)
    return out[:4]


TREE_NOTE = ('A registered tree variety is a selection from one proven mother tree, and only grafted, budded or marcotted plants carry its traits. '
             'A seedling grown from its seed can turn out quite different. So buy from a nursery accredited by the Bureau of Plant Industry, '
             'and ask which mother tree the scions came from.')
GROUP_CHOOSE = {
    'fruit': 'Pick a variety by the fruit your buyers pay for: its size, sweetness and flesh, and how regularly the tree bears.',
    'plantation': 'Pick a variety by yield and by how it stands the pests and weather of your area, because a tree crop stays for decades.',
    'industrial': 'Pick a variety by the yield and quality the buyer pays for, and plant clean, certified planting material.',
}


def crop_slug(key):
    return {'stringbean': 'sitao', 'snapbean': 'snap-bean', 'chinesecabbage': 'chinese-cabbage', 'limabean': 'lima-bean',
            'wingedbean': 'winged-bean', 'bellpepper': 'bell-pepper', 'sweetpotato': 'sweet-potato', 'ubi': 'ube',
            'starapple': 'star-apple', 'pigeonpea': 'pigeon-pea'}.get(key, key)


def cname(key):
    return C.get(key, {}).get('name', key.title())


def cword(key):
    """The crop as a word in a sentence: 'corn', 'sweet potato', 'sitao'."""
    n = cname(key)
    return n if n[:1].isupper() and n.split()[0] in ('Baguio',) else n.lower()


# ---- the field catalogue: the pests and diseases that have pages
PROBLEMS = json.load(open(os.path.join(HERE, '..', 'field-catalogue', 'problems.json'), encoding='utf-8'))
PAGES = {}
for sec in ('pests', 'diseases'):
    for e in PROBLEMS[sec]:
        if os.path.exists(os.path.join(SITE, sec, e['slug'] + '.json')):
            crops = e['crops'] if isinstance(e['crops'], list) else []
            PAGES.setdefault(sec, []).append({'slug': e['slug'], 'name': e['name'], 'crops': crops, 'url': '/%s/%s' % (sec, e['slug'])})


KEEP_CAPS = {'Asian', 'Oriental', 'Southern', 'Northern', 'Fusarium', 'Cercospora', 'Panama', 'Philippine', 'Moko', 'Banana', 'Mango', 'Corn', 'Rice'}


def lc(name):
    """A pest's name inside a sentence: 'Corn rust' stays, 'Bacterial wilt' -> 'bacterial wilt'."""
    first = name.split(' ')[0]
    return name if first in KEEP_CAPS or first[:2].isupper() else name[:1].lower() + name[1:]


def problems_of(key):
    keys = set(C.get(key, {}).get('catalogue') or [])
    out = []
    for sec in ('pests', 'diseases'):
        for p in PAGES.get(sec, []):
            if keys & set(p['crops']):
                out.append(p)
    # The crop's own problems first (named for it), then the shared ones.
    return sorted(out, key=lambda p: (0 if keys & set(p['crops'][:2]) else 1, p['name']))


def page_for(label, key):
    """The guide page of a pest or disease a rating names, if there is one."""
    low = label.lower()
    cands = problems_of(key)
    alias = {'corn borer': 'asian corn borer', 'rust': 'rust', 'stalk rot': 'stalk rot', 'corn earworm': 'corn earworm',
             'cercospora leaf spot': 'cercospora leaf spot', 'bacterial wilt': 'bacterial wilt', 'anthracnose': 'anthracnose',
             'late blight': 'late blight', 'early blight': 'early blight', 'downy mildew': 'downy mildew', 'fusarium wilt': 'fusarium wilt',
             'leaf miner': 'leafminer', 'whitefly': 'whitefly', 'thrips': 'thrips', 'rats': 'rats'}.get(low, low)
    for p in cands:
        if alias in p['name'].lower():
            return p
    return None


def load():
    return json.load(open(os.path.join(HERE, 'varieties.json'), encoding='utf-8'))


def kind_of(v):
    # A research note's kind keeps its first clause, and never ends "variety".
    k = safe(v.get('kind') or '').split(',')[0].strip()
    k = re.sub(r'(?i)\s*\b(variety|cultivar)\b\s*$', '', k).strip()
    if len(k) > 34:
        k = ''
    if v['crop'] == 'corn':
        color = v.get('color')
        if 'sweet' in k or 'glutinous' in k:
            return k
        return ('%s %s' % (color, k)).strip() if color else k
    return k


def has_nums(v):
    return v.get('maturity') is not None or (v.get('yield') or {}).get('value') is not None


def mat_text(v, key, short=False):
    m = v.get('maturity')
    if not m:
        return None
    phrase = C.get(key, {}).get('harvest', 'days to harvest')
    rest = phrase.split(' ', 1)[1] if ' ' in phrase else ''
    if m.get('first') and 'first' not in rest:
        rest = rest.replace('to harvest', 'to the first harvest')
    unit = m['unit']
    if unit == 'days':
        m = dict(m, lo=round(m['lo']), hi=round(m['hi']), value=round(m['value']))
    if m['lo'] != m['hi'] and m['hi'] - m['lo'] >= 2:
        val = '%s to %s %s' % (fmt(m['lo']), fmt(m['hi']), unit)
    else:
        val = 'about %s %s' % (fmt(m['value']), unit)
    return val if short else '%s %s' % (val, rest)


def yield_text(v):
    y = v.get('yield')
    if not y:
        return None
    if y.get('value') is None:
        t = safe(y.get('text'))
        return ('a recorded yield of %s' % t[:1].lower() + t[1:]) if t else None
    unit = {'t/ha': 'tonnes per hectare', 'LKg/ha': 'LKg of sugar per hectare', 't copra/ha': 'tonnes of copra per hectare',
            'kg/ha': 'kilograms per hectare'}.get(y['unit'], y['unit'])
    if y.get('regional') and len(y['regional']) > 1:
        parts = ['%s in %s' % (fmt(b, 2), ('the Visayas' if a == 'Visayas' else a)) for a, b in y['regional']]
        return '%s %s in the trials (%s)' % (fmt(y['value'], 2), unit, ', '.join(parts))
    return '%s %s on average in the trials' % (fmt(y['value'], 2), unit)


def reg_text(v):
    reg, yr = v.get('reg'), v.get('year')
    if not reg:
        return None
    if re.search(r'seed board|^psb', reg, re.I):
        return 'The Philippine Seed Board approved it in %s.' % yr if yr else 'The Philippine Seed Board approved it.'
    return 'It was registered with the National Seed Industry Council (NSIC) in %s as %s.' % (yr, reg) if yr else 'It is registered with the NSIC as %s.' % reg


def where_text(v):
    d = v.get('domain') or []
    if not d:
        return None
    if d == ['National']:
        return 'anywhere in the country'
    names = ['the Visayas' if x == 'Visayas' else x for x in d]
    return ' and '.join(names) if len(names) < 3 else '%s, %s and %s' % tuple(names)


def season_text(v):
    s = v.get('seasons') or []
    if set(s) == {'wet', 'dry'}:
        return 'both the wet and the dry season'
    if s:
        return 'the %s season' % s[0]
    return None


TRAIT_SKIP = re.compile(r'(?i)^(type|species|age|tree age|.*age \(|.*laterals|.*nodes|.*angle|leaf (margin|shape|length|width|apex|base|arrangement|lobation)|foliage attitude|no\. of leaves|laminar|.*girth|.*virgin bark|.*withering|.*stimulation|latex (calcium|nitrogen|phosphorous|potassium|mechanical|mooney|total)|.*pedicel|fruit apex|stooling|.*cell (lumen|wall|diameter)|^1$)')


def show_traits(v):
    out = []
    for lab, val in v.get('traits') or []:
        if TRAIT_SKIP.match(lab) or not val:
            continue
        lab = safe(lab.replace('wt.', 'weight').replace(' (brix)', ' (Brix)').replace('(u)', '(microns)'))
        val = safe(val)
        if val in RATING:
            val = RATING[val][0]
        if lab and val and len(val) <= 60:
            out.append([lab[:1].upper() + lab[1:], val[:1].upper() + val[1:] if not re.match(r'^\d', val) else val])
    return out


def trait(v, *pats):
    for lab, val in v.get('traits') or []:
        if any(re.search(p, lab, re.I) for p in pats):
            return safe(val)
    return None


def stats_for(allv):
    """Average days and yield per crop and kind, and per crop."""
    st = {}
    for v in allv:
        for k in ((v['crop'], kind_of(v)), (v['crop'], None)):
            s = st.setdefault(k, {'mat': [], 'yld': [], 'unit': None, 'munit': None})
            if v.get('maturity'):
                if s['munit'] in (None, v['maturity']['unit']):
                    s['mat'].append(v['maturity']['value'])
                    s['munit'] = v['maturity']['unit']
            y = v.get('yield') or {}
            if y.get('value') is not None and s['unit'] in (None, y.get('unit')):
                s['yld'].append(y['value'])
                s['unit'] = y.get('unit')
    return st


def what_like(v, key, n):
    """The produce in words, from the registry's traits."""
    g = C.get(key, {}).get('group')
    bits = []
    if key == 'corn':
        el = trait(v, r'^ear length')
        sh = trait(v, r'shelling recovery')
        lod = trait(v, r'lodging')
        if el:
            bits.append('Its ears grew about %s cm long in the trials.' % re.sub(r'[^\d., to]', '', el).strip() if re.match(r'^[\d.]+$', el.strip()) else 'Its ear length in the trials: %s.' % el)
        if sh:
            bits.append('The shelling recovery was %s%s, the share of the ear weight that comes off as grain. A higher share means more grain from every sack of ears.' % (sh, '' if 'percent' in sh else ' percent'))
        if lod and lod in RATING:
            bits.append('Against lodging it rated %s, so it %s when wind and rain pushed the plants.' % (RATING[lod][0].lower(), 'stood up well' if lod in GOOD else 'fell more than most'))
    elif g == 'fruit' or key in ('coconut', 'cacao', 'coffee'):
        w = trait(v, r'^(whole )?fruit weight|^fruit size weight|^whole fruit size weight')
        shp = trait(v, r'^(whole )?fruit shape')
        sw = trait(v, r'sweetness')
        fc = trait(v, r'^flesh color|^pulp color|^flesh/ pulp color')
        ft = trait(v, r'^flesh texture')
        ep = trait(v, r'edible portion')
        sea = trait(v, r'fruiting season|harvest season')
        reg = trait(v, r'regularity of bearing')
        if w:
            bits.append('A fruit of %s weighs about %s grams%s.' % (n, w, (', and its shape is %s' % shp.lower()) if shp else ''))
        if fc or ft:
            bits.append('The flesh is %s.' % ' and '.join(x.lower() for x in (fc, ft) if x))
        if sw:
            bits.append('Its sweetness measured %s degrees Brix, the share of sugar in the juice. Most table fruit tastes sweet from about 12 Brix up.' % sw)
        if ep:
            bits.append('The edible part is %s%s of the fruit.' % (ep, '' if 'percent' in ep else ' percent'))
        if sea:
            bits.append('Its fruiting season is %s%s.' % (sea, (', bearing %s' % reg.lower()) if reg else ''))
        if key == 'coconut':
            np_ = trait(v, r'^nuts/palm')
            cp = trait(v, r'copra/nut')
            if np_:
                bits.append('Each palm gave about %s nuts a year in the records%s.' % (np_, (', with %s grams of copra per nut' % cp) if cp else ''))
        if key == 'cacao':
            bp = trait(v, r'beans per pod')
            if bp:
                bits.append('Each pod holds about %s beans.' % bp)
    elif g in ('rootcrop',) or key == 'potato':
        dm = trait(v, r'dry matter')
        stc = trait(v, r'starch')
        fl = trait(v, r'flesh color')
        sk = trait(v, r'skin color')
        hcn = trait(v, r'hcn')
        if fl or sk:
            bits.append('Its roots have %s.' % ' and '.join(x for x in (('%s flesh' % fl.lower()) if fl else '', ('%s skin' % sk.lower()) if sk else '') if x))
        if dm:
            bits.append('Dry matter is %s%s. A higher dry matter cooks mealy and gives processors more flour or starch from each tonne.' % (dm, '' if 'percent' in dm else ' percent'))
        if stc:
            bits.append('The starch content is %s%s.' % (stc, '' if 'percent' in stc else ' percent'))
        if hcn:
            bits.append('Its cyanide level, the HCN content, is %s. A low level is safer for the table, while a high level calls for peeling, soaking and full cooking.' % hcn.lower())
        if v.get('uses'):
            bits.append('It is recommended %s.' % safe(v['uses']).lower().rstrip('.'))
    elif g in ('legume', 'beanveg'):
        sw = trait(v, r'100 seed')
        pl = trait(v, r'pod length')
        sc = trait(v, r'seed coat color|seed color')
        pr = trait(v, r'crude protein')
        if pl:
            bits.append('Its pods grew about %s cm long.' % pl)
        if sw:
            bits.append('A hundred seeds weigh about %s grams, so the higher this number, the bigger the seed.' % sw)
        if sc:
            bits.append('The seed coat is %s.' % sc.lower())
        if pr:
            bits.append('The seed holds %s%s crude protein.' % (pr, '' if 'percent' in pr else ' percent'))
    elif key == 'sugarcane':
        sl = trait(v, r'stalk length')
        sd = trait(v, r'stalk diameter')
        fb = trait(v, r'fiber')
        tr = trait(v, r'trashiness')
        if sl or sd:
            bits.append('Its stalks grew %s.' % ' and '.join(x for x in (('about %s cm long' % sl) if sl else '', ('%s cm thick' % sd) if sd else '') if x))
        if fb:
            bits.append('The fiber content is %s%s.' % (fb, '' if 'percent' in fb else ' percent'))
        if tr:
            bits.append('Trash: %s, which decides how much cleaning the cane needs before milling.' % tr.lower())
    else:
        w = trait(v, r'fruit weight|head weight|bulb weight')
        fl = trait(v, r'fruit length|pod length')
        col = trait(v, r'fruit color|pod color|skin color|head color')
        shp = trait(v, r'fruit shape|head shape|bulb shape|shape of bulbs')
        if w:
            bits.append('A fruit weighs about %s grams.' % w)
        if fl:
            bits.append('It grew about %s cm long.' % fl)
        if col or shp:
            bits.append('It is %s.' % ' and '.join(x.lower() for x in (col, shp) if x))
    return [b for b in bits if b]


def build(v, allv, st):
    key = v['crop']
    c = C.get(key, {})
    n = v['name']
    slug = v['slug']
    cw = cword(key)
    crop_n = cname(key)
    local = c.get('local')
    fk = FK[slug]
    kind = kind_of(v)
    research = v.get('research') or {}
    company = safe(research.get('company') or '') or v.get('owner')
    # A public breeder or owner (a bureau, a state university, a research body) is not named to the reader
    # (the owner, 2026-10-18); a private seed company stays.
    if company and is_gov(company):
        company = None
    owner_shown = v.get('owner') if v.get('owner') and not is_gov(v['owner']) else None
    # Other names, minus the ones that only add the crop word ("Lakatan banana").
    flat = lambda x: re.sub(r'[\s-]', '', x.lower())
    taken = {flat(n), flat('%s %s' % (n, cw)), flat('%s %s' % (cw, n)), flat('%s %s' % (n, crop_slug(key)))}
    aliases = []
    for a in v.get('aliases') or []:
        a = safe(a)
        if a and flat(a) not in taken:
            taken.add(flat(a))
            aliases.append(a)
    aliases = aliases[:4]
    mat = mat_text(v, key)
    yld = yield_text(v)
    hub = '/crop-varieties/%s' % crop_slug(key) if key in HUBS else '/crop-varieties'
    where = where_text(v)
    seas = season_text(v)
    facts = research.get('facts') or {}

    # ---- the head of the page
    has = has_nums(v)
    tail = 'Yield, Maturity and Characteristics' if has else ('Fruit and Tree Characteristics' if c.get('group') == 'fruit' else 'Characteristics')
    title = '%s%s: %s' % (fk if fk != n else n, (' (%s)' % aliases[0]) if aliases and len(aliases[0]) <= 18 else '', tail)
    if fk == n and cw not in n.lower():
        title = '%s %s Variety: %s' % (n, crop_n, tail)
    meta_opts = ['%s: %s' % (fk, tail), '%s: Yield and Maturity' % fk if has else '%s: Characteristics' % fk, '%s Variety' % fk, fk]
    meta_title = next(t for t in meta_opts if len(t) <= 60)
    kind_txt = ('%s %s' % (kind, cw)).strip() if kind and cw not in kind else (kind or cw)
    desc_core = []
    if mat:
        desc_core.append(mat_text(v, key, short=True) + ' to harvest')
    y = v.get('yield') or {}
    if y.get('value') is not None and y.get('unit') == 't/ha':
        desc_core.append('%s t/ha in trials' % fmt(y['value'], 1))
    opts = [
        '%s is %s %s variety%s. %s. See its ratings and traits.' % (fk, article(kind_txt), kind_txt, (' for %s' % where) if where and where != 'anywhere in the country' else '', ', '.join(desc_core).capitalize()) if desc_core else '',
        '%s is %s %s variety. %s. See its pest ratings and traits.' % (fk, article(kind_txt), kind_txt, ', '.join(desc_core).capitalize()) if desc_core else '',
        '%s is %s %s variety%s. See its days to harvest, yield, pest ratings, traits and where it grows best.' % (fk, article(kind_txt), kind_txt, (' from %s' % company) if company and len(company) < 30 else ''),
        '%s is %s %s variety. See its days to harvest, yield, pest ratings, traits and where it grows best.' % (fk, article(kind_txt), kind_txt),
        '%s: the %s variety, with its traits, ratings and where it grows best in the Philippines.' % (fk, cw),
    ]
    meta_desc = next((o for o in opts if o and 120 <= len(o) <= 156), None)
    if not meta_desc:
        meta_desc = next((o for o in opts if o and len(o) <= 156), opts[-1])
        pads = [' Compare it with others.', ' Free guide.', ' In plain words.']
        for p in pads:
            if len(meta_desc) < 120 and len(meta_desc) + len(p) <= 156:
                meta_desc += p
        while len(meta_desc) < 120:
            meta_desc = meta_desc.rstrip('.') + ', in plain words.'
        meta_desc = meta_desc[:156]

    regt = reg_text(v)
    who = ('%s bred it. ' % owner_shown) if owner_shown and v.get('reg') else \
        ('It comes from %s. ' % company if company and not re.search(r'(?i)public|traditional|native|farmer|unknown|various|landrace', company) else '')
    lead = fk if kind else n
    fkword = crop_slug(key).replace('-', ' ')
    lead_kind = kind_txt if kind else ('registered %s' % fkword if v.get('reg') else fkword)
    excerpt = '%s is %s %s variety%s. %s%s' % (
        lead, article(lead_kind), lead_kind, (', also sold or known as %s' % ' or '.join(aliases[:2])) if aliases else '',
        (regt + ' ') if regt else 'It is not a registered variety, so the facts here come from its seller and farm sources. ', who)
    if mat:
        excerpt += 'It takes %s%s.' % (mat, (', with %s' % yld) if yld else '')
    elif yld:
        excerpt += 'Its record notes %s.' % yld.replace('a recorded yield of ', 'a yield of ')
    excerpt = re.sub(r'\s+', ' ', excerpt).strip()

    blocks = []
    H = lambda t, lvl=2: blocks.append({'type': 'heading', 'level': lvl, 'text': t})
    T = lambda t: blocks.append({'type': 'text', 'text': t})

    # ---- why it matters
    search = ''
    if aliases:
        search = 'If you came here looking for %s, this is the same %s. ' % (' or '.join(aliases[:2]), cw)
    intro = search + (('Its seller and farm sources add notes, listed at the end. ' if research else '')
                      if v.get('reg') else 'The facts below come from its seller and from farm sources, each one listed at the end. ')
    intro += 'Compare it with the others in our [%s variety finder](%s), or see [every crop variety](/crop-varieties) we list. ' % (cw, hub)
    gl = guides_of(key)
    guide = gl[0] if gl else [None, None]
    if guide[0]:
        intro += 'For the crop itself, read our guide: [%s](%s).' % (guide[1][:1].lower() + guide[1][1:] if not guide[1].startswith(('Pag', 'Land')) else guide[1], guide[0])
    else:
        intro += 'For the season around it, see [the growth stages and weather](/features/growth-stages-and-weather) that anee.io keeps for each crop.'
    T(intro)

    pts = []
    if mat:
        pts.append('**Days to harvest:** %s.' % mat)
    if yld:
        pts.append('**Yield:** %s.' % yld)
    if v.get('height'):
        h = v['height']
        pts.append('**Plant height:** about %s %s.' % (fmt(h['value']), 'meters' if h['unit'] == 'm' else 'cm'))
    if where or seas:
        pts.append('**Recommended for:** %s%s.' % (where or 'the areas in its trials', (', in %s' % seas) if seas else ''))
    if v.get('localName'):
        pts.append('**Local name:** %s.' % safe(v['localName']))
    if kind:
        pts.append('**Type:** %s.' % kind[:1].upper() + kind[1:] if False else '**Type:** %s.' % (kind[:1].upper() + kind[1:]))
    if pts:
        blocks.append({'type': 'list', 'ordered': False, 'items': pts})

    # ---- the facts table
    H('Key facts about %s' % fk)
    rows = [['Trait', 'What the record shows']]
    if v.get('reg'):
        rows.append(['Registration', safe(v['reg'])])
    if v.get('year'):
        rows.append(['Year approved', str(v['year'])])
    if owner_shown:
        rows.append(['Breeder or owner', safe(owner_shown)])
    elif company:
        rows.append(['Seller', company])
    if aliases:
        rows.append(['Also known as', ', '.join(aliases)])
    if kind:
        rows.append(['Type', kind[:1].upper() + kind[1:]])
    if v.get('maturity'):
        rows.append(['Days to harvest' if v['maturity']['unit'] == 'days' else 'Months to harvest', mat_text(v, key, short=True)])
    if y.get('value') is not None:
        rows.append(['Average yield', '%s %s' % (fmt(y['value'], 2), y['unit'])])
        for s_, val in (y.get('seasons') or {}).items():
            rows.append(['Yield, %s season' % ('dry' if s_ == 'DS' else 'wet'), '%s %s' % (fmt(val, 2), y['unit'])])
    elif y.get('text'):
        rows.append(['Yield noted', safe(y['text'])])
    if v.get('height'):
        rows.append(['Plant height', '%s %s' % (fmt(v['height']['value']), v['height']['unit'])])
    if where:
        rows.append(['Recommended area', where[:1].upper() + where[1:]])
    if seas:
        rows.append(['Season', seas[:1].upper() + seas[1:]])
    if v.get('elevation'):
        rows.append(['Elevation', ', '.join(v['elevation']).capitalize()])
    for lab, val in show_traits(v)[:12]:
        rows.append([lab, val])
    if facts.get('maturityDays') and not v.get('maturity'):
        rows.append(['Days to harvest, per the seller', safe(str(facts['maturityDays']))])
    if facts.get('yield') and y.get('value') is None:
        rows.append(['Yield, per the seller', safe(str(facts['yield']))])
    rows = [r for r in rows if r[1]]
    blocks.append({'type': 'table', 'caption': 'Key facts of %s' % n if v.get('reg') else 'From its seller and farm sources', 'rows': rows})

    # ---- days and yield, against the others of its crop
    s_kind = st.get((key, kind), {})
    s_all = st.get((key, None), {})
    tree_crop = c.get('group') in ('fruit', 'plantation') or key in ('rubber',)
    H('When does it bear, and how much?' if tree_crop and not v.get('maturity') else 'How long does it take, and how much does it yield?')
    para = []
    if v.get('maturity'):
        para.append('%s takes %s.' % (fk, mat))
        m = v['maturity']['value']
        pool = s_kind if len(s_kind.get('mat', [])) >= 5 else s_all
        if len(pool.get('mat', [])) >= 3 and pool.get('munit') == v['maturity']['unit']:
            avg = statistics.mean(pool['mat'])
            diff = m - avg
            who_ = '%d %s varieties with a day count' % (len(pool['mat']), kind_txt if pool is s_kind else cw)
            if abs(diff) < (3 if v['maturity']['unit'] == 'days' else 0.5):
                para.append('That is about the average of the %s, %s %s.' % (who_, fmt(avg), v['maturity']['unit']))
            else:
                para.append('That is %s %s %s than the average of the %s, %s %s.' % (fmt(abs(diff)), v['maturity']['unit'], 'faster' if diff < 0 else 'longer', who_, fmt(avg), v['maturity']['unit']))
        para.append('A shorter crop frees the field sooner and dodges more of the weather, while a longer one often fills more.' if v['maturity']['unit'] == 'days'
                    else 'Count the months from planting, and plan the harvest for a dry stretch when you can.')
    if y.get('value') is not None:
        para.append('\n\n')
        pool = s_kind if len(s_kind.get('yld', [])) >= 5 else s_all
        if len(pool.get('yld', [])) >= 3 and pool.get('unit') == y.get('unit'):
            avg = statistics.mean(pool['yld'])
            better = sorted(pool['yld'])
            rank = sum(1 for x in better if x <= y['value']) / len(better)
            para.append('Its average yield of %s %s %s the %s of its group, %s.' % (
                fmt(y['value'], 2), y['unit'], 'beats' if y['value'] > avg * 1.03 else 'trails' if y['value'] < avg * 0.97 else 'matches',
                'average' , fmt(avg, 2)) + (' It sits in the top quarter of its group.' if rank >= 0.75 else ' It sits in the lower quarter of its group.' if rank <= 0.25 else ''))
        if c.get('unitNote'):
            para.append(c['unitNote'])
        para.append('Trial yields come from well managed plots, so your own will depend on water, fertilizer, pests and timing.')
    if not para and (c.get('group') in ('fruit', 'plantation') or key in ('rubber',)):
        age = trait(v, r'age \(years|^age$|^tree age$')
        ht = trait(v, r'^tree height|^height')
        reg_ = trait(v, r'regularity of bearing')
        sea = trait(v, r'fruiting season|harvest season')
        bits = []
        if age:
            a_ = re.sub(r'(?i)\s*(years?|yrs\.?)$', '', age)
            bits.append(('over %s years old' % a_.lstrip('> ')) if a_.startswith('>') else ('about %s years old' % a_))
        if ht:
            bits.append('about %s meters tall' % re.sub(r'(?i)^approx\.?\s*|\s*m$', '', ht))
        line = 'A registered tree variety is described from its mother tree, not from trial plots.'
        if bits:
            line += ' The mother tree of %s was %s when it was registered.' % (n, ' and '.join(bits))
        if yld:
            line += ' Its record notes %s.' % yld.replace('a recorded yield of ', 'a yield of ')
        how = {'annual': 'every year', 'semi annual': 'twice a year', 'biennial': 'every other year', 'regular': 'regularly', 'irregular': 'irregularly',
               'perennial': 'all year', 'year round': 'all year', 'seasonal': 'once a season'}.get((reg_ or '').lower().strip())
        if how or sea:
            line += ' It bears %s%s.' % (how or 'fruit', (', with fruit in %s' % sea) if sea and how else (' in %s' % sea) if sea else '')
        first = trait(v, r'age at fruiting|age at first flowering')
        if first and re.match(r'^[\d.]+$', first):
            line += ' It first bore %s about %s months after planting.' % ('pods' if key == 'cacao' else 'fruit', fmt(float(first)))
        pt = next(((lab, safe(val)) for lab, val in v.get('traits') or [] if re.search(r'(?i)yield.*tree|kg/tree', lab)), None)
        if pt and not yld and re.match(r'^[\d.,]+$', pt[1]):
            u = 'grams' if '(g)' in pt[0] else 'kilograms a year' if 'year' in pt[0] else 'kilograms'
            line += ' Each tree gave about %s %s in the records.' % (fmt(float(pt[1].replace(',', ''))), u)
        line += ' A grafted or marcotted tree bears years sooner than one grown from seed, and its yield climbs as the tree grows.'
        para.append(line)
    if not para:
        para.append('No day count or trial yield is published for this variety. So ask the seller or your municipal agriculturist for figures from farms near you before you plan a season around it.')
    if facts.get('maturityDays') and v.get('maturity'):
        para.append('Its seller gives %s.' % safe(str(facts['maturityDays'])).rstrip('.'))
    T(re.sub(r' *\n\n *', '\n\n', ' '.join(para)).strip() + ('\n\nanee.io keeps the records of each block of trees, from fertilizer to harvest, with [the growth stages and weather](/features/growth-stages-and-weather) beside them.'
                                                              if tree_crop and not v.get('maturity') else
                                                              '\n\nOnce you know the day count, anee.io dates every task from each lot\'s own planting day, with [the growth stages and weather](/features/growth-stages-and-weather) beside it.'))

    # ---- pests and diseases
    H('Is it resistant to pests and diseases?')
    reacts = [r for r in v.get('reactions') or [] if r[1]]
    weak = []
    if reacts:
        rrows = [['Pest or disease', 'Rating', 'What it means']]
        for lab, val in reacts[:10]:
            meaning = RATING.get(val, (safe(val), 'see the note'))[1] if val in RATING else 'as noted in the record'
            rrows.append([safe(lab), RATING[val][0] if val in RATING else safe(val), meaning[:1].upper() + meaning[1:]])
            if val in BAD:
                weak.append(lab)
        blocks.append({'type': 'table', 'caption': 'Reactions in trials, rated under natural field conditions', 'rows': rrows})
        linked = []
        for lab, val in reacts:
            p = page_for(lab, key)
            if p and p['url'] not in [x[1] for x in linked]:
                linked.append((p['name'], p['url']))
        txt = []
        good = [lab for lab, val in reacts if val in GOOD]
        if good and not weak:
            txt.append('It showed no weak spot in the ratings on record. Still, a resistant variety can break down when a new strain or pest population comes, so keep scouting.')
        elif weak:
            txt.append('It was weak against %s, so scout for %s from the early weeks.' % (', '.join(safe(w).lower() for w in weak), 'it' if len(weak) == 1 else 'them'))
        if linked:
            txt.append('Read up on %s.' % ', '.join('[%s](%s)' % (lc(a), b) for a, b in linked[:4]))
        if txt:
            T(' '.join(txt))
    else:
        T('No pest or disease ratings are published for this variety. So treat it as untested, and scout every week.')
    common = [p for p in problems_of(key) if not any(p['name'].lower() in (r[0] or '').lower() for r in reacts)][:5]
    if common:
        T('The pests and diseases that hit %s fields most often: %s. Our [pest finder](/pests) and [disease finder](/diseases) help you name what you see.' % (
            cw, ', '.join('[%s](%s)' % (lc(p['name']), p['url']) for p in common)))

    # ---- what the produce is like
    like = what_like(v, key, n)
    if like:
        H('What the %s is like' % {'fruit': 'fruit', 'plantation': 'crop', 'rootcrop': 'root', 'legume': 'seed', 'beanveg': 'pod', 'cereal': 'grain'}.get(c.get('group'), 'harvest'))
        T(' '.join(like))

    # ---- what its seller says
    if research:
        H('What its seller and farm sources say')
        notes = []
        for k_ in ('maturityDays', 'harvest', 'yield', 'fruit', 'gm', 'season', 'areas', 'elevation', 'density', 'uses'):
            if facts.get(k_) and not (k_ == 'maturityDays' and v.get('maturity')):
                lab = {'maturityDays': 'Days to harvest', 'harvest': 'Harvest', 'yield': 'Yield', 'fruit': 'Fruit', 'gm': 'Traits in the seed',
                       'season': 'Season', 'areas': 'Where', 'elevation': 'Elevation', 'density': 'Stand', 'uses': 'Uses'}[k_]
                val = safe(str(facts[k_]))
                if val:
                    notes.append('**%s:** %s' % (lab, sentence(val)))
        for t_ in (facts.get('traits') or [])[:5]:
            t2 = sentence(str(t_))
            if t2:
                notes.append(t2)
        if notes:
            blocks.append({'type': 'list', 'ordered': False, 'items': notes})
        pop = sentence(research.get('popular') or '')
        line = []
        if pop:
            line.append('Why farmers know it: %s' % lower_start(pop))
        if research.get('claims'):
            line.append('These are the seller\'s own figures, from its own trials. So plant a small area first, and compare it with your usual %s on the same ground.' % cw)
        else:
            line.append('Check these against what farmers near you have seen, because the same variety can do differently from one town to the next.')
        T(' '.join(line))

    # ---- where and when
    H('Where and when to plant %s' % fk)
    ww = []
    if where:
        ww.append('It is recommended for %s%s.' % (where, (' in %s' % seas) if seas else ''))
    elif seas:
        ww.append('It is recommended for %s.' % seas)
    if v.get('elevation'):
        ww.append('It suits %s areas.' % ' and '.join(v['elevation']))
    if c.get('choose'):
        ww.append(c['choose'])
    else:
        ww.append(GROUP_CHOOSE.get(c.get('group'), 'Plant certified seed or planting material from an accredited source, so you get the variety you paid for.'))
    if c.get('group') in ('fruit', 'plantation') or key in ('rubber', 'abaca'):
        ww.append(TREE_NOTE)
    T(' '.join(ww) + (' Before planting, get the field ready with our guide: [%s](%s).' % (gl[1][1], gl[1][0]) if len(gl) > 1 else ''))

    # ---- before buying
    tree = c.get('group') in ('fruit', 'plantation') or key in ('rubber', 'abaca')
    H('Before you buy %s' % ('planting material' if tree else 'seed'), 3)
    blocks.append({'type': 'list', 'ordered': False, 'items': [
        ('Buy grafted or budded plants from an accredited nursery, with the variety name on the tag.' if tree
         else 'Buy sealed packs or tagged sacks from an accredited dealer, and check the packing date and germination rate.'),
        'Ask your municipal agriculturist or nearby farmers how it did in your area last season.',
        ('Plant a few trees first if the variety is new to your area, before you fill the whole farm.' if tree
         else 'Try it on a small area first, beside the variety you usually plant, before you switch the whole farm.'),
        'Write down the planting day, inputs and harvest, so you can compare varieties on your own ground. anee.io keeps these records for you.',
    ]})

    # ---- the way into the app
    blocks.append({'type': 'cta', 'title': 'Plan your next %s season' % cw,
                   'text': 'Set the planting day of each lot and anee.io dates every task to harvest, with the weather beside it. Free on the Libre plan.',
                   'label': 'Start free', 'url': '/signup'})

    # ---- similar varieties
    peers = [x for x in allv if x['crop'] == key and x['slug'] != slug]
    if v.get('maturity'):
        near = lambda x: (abs((x.get('maturity') or {'value': 999})['value'] - v['maturity']['value']) + (0 if kind_of(x) == kind else 20) - (x.get('year') or 0) * .01)
    else:
        near = lambda x: ((0 if kind_of(x) == kind else 20) + abs((x.get('year') or 2000) - (v.get('year') or 2000)) - (5 if x.get('research') else 0))
    sims = sorted(peers, key=near)[:4]
    if sims:
        H('%s varieties like this one' % crop_n)
        T('These are the closest matches among the %s varieties we list. So if seed of this one is hard to find, compare these first.' % cw)
        items = []
        for x in sims:
            bits = []
            if x.get('maturity'):
                bits.append(mat_text(x, key, short=True))
            yx = x.get('yield') or {}
            if yx.get('value') is not None:
                bits.append('%s %s' % (fmt(yx['value'], 2), yx['unit']))
            if x.get('year'):
                bits.append(str(x['year']))
            items.append('[%s](/crop-varieties/%s)%s' % (x['name'], x['slug'], (': ' + ', '.join(bits)) if bits else ''))
        blocks.append({'type': 'list', 'ordered': False, 'items': items})

    # ---- questions
    faq = []
    if aliases:
        faq.append({'q': 'Is %s the same as %s?' % (aliases[0], n),
                    'a': 'Yes. %s is another name for %s%s. Seed sellers use both, so ask for either one.' % (aliases[0], n, (', registered as %s' % v['reg']) if v.get('reg') and not re.search(r'seed board', v['reg'], re.I) else '')})
    if mat:
        faq.append({'q': 'How long does it take to harvest?', 'a': 'It takes %s. Then the weather and your care decide the exact day.' % mat})
    if yld:
        faq.append({'q': 'What is its yield?', 'a': 'The record shows %s. Your own yield depends on the soil, water, fertilizer and pests on your farm.' % yld})
    if kind and 'hybrid' in kind.lower():
        faq.append({'q': 'Can I save its seed?', 'a': 'No. It is a hybrid, so seed saved from it does not grow true and yields less. Buy new seed each season.'})
    elif kind and 'open pollinated' in kind.lower():
        faq.append({'q': 'Can I save its seed?', 'a': 'Yes. It is open pollinated, so seed from a clean, isolated field grows true. Still, certified seed gives the best start every few seasons.'})
    if where:
        faq.append({'q': 'Where does it grow best?', 'a': 'It is recommended for %s%s. Ask your municipal agriculturist how it has done in your town.' % (where, (' in %s' % seas) if seas else '')})
    if c.get('group') == 'fruit' or key in ('coconut', 'cacao', 'coffee', 'rubber'):
        faq.append({'q': 'Where can I buy planting material of %s?' % n,
                    'a': 'Buy grafted, budded or marcotted trees from a nursery accredited by the Bureau of Plant Industry, so you get the true variety. Ask your provincial agriculture office for the nearest one.'})
    else:
        faq.append({'q': 'Where can I buy seed of %s?' % n,
                    'a': 'Ask an accredited seed dealer in your province, the seller named on this page or your municipal agriculturist. Look for the certified seed tag or a sealed pack before you pay.'})
    blocks.append({'type': 'faq', 'items': faq[:6]})

    reads = [{'label': 'Every %s variety, side by side' % cw, 'url': hub}] if hub != '/crop-varieties' else []
    reads.append({'label': 'Crop varieties in the Philippines', 'url': '/crop-varieties'})
    reads += [{'label': g[1], 'url': g[0]} for g in gl[:3]]
    reads.append({'label': 'Rice varieties of the Philippines', 'url': '/rice-varieties'})
    blocks.append({'type': 'links', 'title': 'Read next', 'items': reads[:6]})

    sources = []
    for u in [x for x in (research.get('sources') or []) if not is_gov_url(x)][:3]:
        host = re.sub(r'^https?://(www\.)?', '', u).split('/')[0].replace('-', ' ')
        sources.append({'label': ('Notes on %s from %s' % (n, host)) if not is_gov(n) else 'Notes from %s' % host, 'url': u})
    if sources:
        blocks.append({'type': 'sources', 'items': sources})

    hero = HEROES.get(c.get('hero') or 'orchard')
    kw = [fk, n] + aliases + ['%s %s variety' % (n, cw), '%s variety philippines' % cw]
    if v.get('reg'):
        kw.append(v['reg'])
    page = {
        'section': 'crop-varieties', 'slug': slug, 'sortOrder': 10 + allv.index(v), 'lang': 'en',
        'category': crop_n, 'profile': True,
        'title': title, 'metaTitle': meta_title, 'metaDescription': meta_desc,
        'focusKeyword': fk, 'keywords': list(dict.fromkeys(k for k in kw if k)),
        'excerpt': excerpt,
        'heroImage': {'src': hero[0], 'alt': '%s, the kind of farm where %s is grown' % (hero[1], fk), 'credit': ''},
        'blocks': blocks,
    }
    return page


HUBS = set()
FK = {}


def fk_norm(s):
    return ' '.join(w[:-1] if len(w) > 3 and w.endswith('s') and not w.endswith('ss') else w for w in re.findall(r"[a-z0-9']+", s.lower()))


def focus_all(allv):
    seen = set()
    for v in sorted(allv, key=lambda v: (-(v.get('year') or 0), v['slug'])):
        word = crop_slug(v['crop']).replace('-', ' ')
        n = v['name']
        fk = n if fk_norm(word) in fk_norm(n) else '%s %s' % (n, word)
        if fk_norm(fk) in seen:
            extra = 'GM' if 'GM' in (v.get('kind') or '') else (v.get('kind') or '').split(' ')[0] or str(v.get('year') or '')
            fk = '%s %s %s' % (n, extra, word) if fk_norm(word) not in fk_norm(n) else '%s %s' % (n, extra)
        seen.add(fk_norm(fk))
        FK[v['slug']] = fk


def main():
    allv = load()
    from collections import Counter
    counts = Counter(v['crop'] for v in allv)
    HUBS.update(k for k, n in counts.items() if n >= 3)
    st = stats_for(allv)
    focus_all(allv)
    os.makedirs(OUT, exist_ok=True)
    # A page keeps the place it was first given (2026-10-15), so one new
    # variety does not renumber every page after it. A new page shares the
    # place of the page before it (the site sorts a tie by title).
    placed = {}
    for f in os.listdir(OUT):
        if f.endswith('.json'):
            try:
                placed[f[:-5]] = json.load(open(os.path.join(OUT, f), encoding='utf-8'))['sortOrder']
            except (ValueError, KeyError):
                pass
    keep = set()
    prev = None
    for v in allv:
        p = build(v, allv, st)
        if placed:
            p['sortOrder'] = placed.get(p['slug'], prev if prev is not None else p['sortOrder'])
        prev = p['sortOrder']
        keep.add(p['slug'] + '.json')
        p = unsource_page(p)
        json.dump(p, open(os.path.join(OUT, p['slug'] + '.json'), 'w', encoding='utf-8', newline='\n'), indent=2, ensure_ascii=False)
    for f in os.listdir(OUT):
        if f.endswith('.json') and f not in keep:
            os.remove(os.path.join(OUT, f))
    print(len(allv), 'pages,', len(HUBS), 'crop hubs')


if __name__ == '__main__':
    main()
