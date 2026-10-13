"""Writes one public page per rice variety, database/site-pages/rice-varieties/
{slug}.json, from varieties.json (PhilRice's catalogue of released varieties,
see build_data.py). The facts are PhilRice's: names, years, numbers, ratings,
seasons and regions. Every sentence around them is anee.io's own: what the
numbers mean for a farmer, how the variety compares with others of its kind,
and which pests, diseases and guides matter for it. No picture from PhilRice
is used; the heroes are anee.io's own farm photos. Pages follow
database/site-pages/STYLE.md and pass check.py.

    python database/rice-varieties/build_pages.py
"""
import json
import os
import re
import statistics

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..', 'site-pages', 'rice-varieties')

ENV = {
    'irrigated': ('irrigated lowland', 'Irrigated lowland', 'irrigated lowland fields, where you control the water'),
    'rainfed-tpr': ('rainfed lowland', 'Rainfed lowland, transplanted', 'rainfed lowland fields that are transplanted'),
    'rainfed-dsr': ('rainfed lowland', 'Rainfed lowland, direct seeded', 'rainfed fields that are seeded directly'),
    'upland': ('upland fields', 'Upland', 'upland fields with no standing water'),
    'saline': ('saline prone fields', 'Saline prone', 'fields where salt reaches the soil'),
    'flood': ('flood prone fields', 'Flood prone', 'fields that go under water in the wet season'),
    'drought': ('drought prone fields', 'Drought prone', 'fields that run dry in a long dry spell'),
    'cool': ('cool, elevated areas', 'Cool elevated', 'cool, elevated places such as the Cordillera'),
}
RATING = {
    'R': ('Resistant', 'held up well in the tests'),
    'MR': ('Moderately resistant', 'took some damage but held up'),
    'I': ('Intermediate', 'took a middling share of damage'),
    'MS': ('Moderately susceptible', 'took clear damage'),
    'S': ('Susceptible', 'took heavy damage'),
}
PROBLEM = {
    'blast': ('Rice blast', '/diseases/rice-blast'),
    'blb': ('Bacterial leaf blight', '/diseases/bacterial-leaf-blight'),
    'tungro': ('Tungro', '/diseases/rice-tungro'),
    'bph': ('Brown planthopper', '/pests/brown-planthopper'),
    'glh': ('Green leafhopper', '/pests/green-leafhopper'),
    'sb': ('Stem borer', '/pests/rice-stem-borer'),
    'shb': ('Sheath blight', '/diseases/sheath-blight'),
}
SEASON = {'DS': 'dry season', 'WS': 'wet season'}
GRADE = {'Pr': 'Premium', 'G1': 'Grade 1', 'G2': 'Grade 2', 'G3': 'Grade 3'}
AMYLOSE = {'VL': 'very low', 'L': 'low', 'l': 'low', 'I': 'intermediate', 'H': 'high'}
LENGTH = {'Sh': 'short', 'S': 'short', 'M': 'medium', 'L': 'long', 'EL': 'extra long'}
SHAPE = {'S': 'slender', 'I': 'intermediate', 'B': 'bold'}
HERO = {
    'hybrid': ('/images/site/photos/palay-heads.jpg', 'Ripe palay heads, the harvest a hybrid like {n} is bred to fill'),
    'irrigated': ('/images/site/palay.jpg', 'An irrigated palay field in the Philippines, where {n} is meant to grow'),
    'rainfed-tpr': ('/images/site/fields-aerial.jpg', 'Rainfed rice fields from the air, the kind of farm {n} is meant for'),
    'rainfed-dsr': ('/images/site/fields-aerial.jpg', 'Rainfed rice fields from the air, the kind of farm {n} is meant for'),
    'upland': ('/images/site/photos/inspect.jpg', 'A farmer checking his crop, as you would on the upland fields {n} is bred for'),
    'saline': ('/images/site/photos/transplant.jpg', 'Farmers transplanting rice, as on the saline prone fields {n} is bred for'),
    'flood': ('/images/site/photos/storm-paddies.jpg', 'A storm over rice paddies, the flood risk {n} is bred to face'),
    'drought': ('/images/site/photos/farmer-hijab.jpg', 'A farmer in her field, the kind of dry season farm {n} is bred for'),
    'cool': ('/images/site/hero-terraces.jpg', 'Rice terraces in the cool highlands, where {n} is meant to grow'),
    None: ('/images/site/photos/hero-planting.jpg', 'Farmers planting rice by hand, the start of a season of {n}'),
}
SRC_PALAYCHECK = {'label': 'PhilRice: PalayCheck System, 2022 revised edition', 'url': 'https://www.philrice.gov.ph/wp-content/uploads/2023/02/PalayCheck-System-2022-Revised-Edition.pdf'}
SRC_IRRI_STAGES = {'label': 'IRRI Rice Knowledge Bank: Growth stages of the rice plant', 'url': 'http://www.knowledgebank.irri.org/ericeproduction/0.2._Growth_stages_of_the_rice_plant.htm'}
SRC_FAO = {'label': 'FAO: Rice in human nutrition, amylose classes of milled rice', 'url': 'https://www.fao.org/4/T0567E/T0567E0b.htm'}
SRC_JULIANO = {'label': 'PhilRice: Rice in health and nutrition, by Dr Bienvenido O. Juliano (2013)', 'url': 'https://www.philrice.gov.ph/rice-in-health-and-nutrition/'}
SRC_PHILRICE = {'label': 'PhilRice: Seed information on released rice varieties', 'url': 'https://dbmp.philrice.gov.ph/seeds/'}
SRC_NSIC = {'label': 'BPI: NSIC registry of rice varieties', 'url': 'https://nsic.buplant.da.gov.ph/registry.php'}
SRC_HYBRID = {'label': 'Wikipedia: Hybrid rice, and why its seed is bought each season', 'url': 'https://en.wikipedia.org/wiki/Hybrid_rice'}


REGION_NAMES = {'ILOCOS REGION': 'Ilocos', 'CAGAYAN VALLEY': 'Cagayan Valley', 'CENTRAL LUZON': 'Central Luzon', 'CALABARZON': 'Calabarzon',
                'MIMAROPA': 'Mimaropa', 'BICOL REGION': 'Bicol', 'WESTERN VISAYAS': 'Western Visayas', 'CENTRAL VISAYAS': 'Central Visayas',
                'EASTERN VISAYAS': 'Eastern Visayas', 'ZAMBOANGA PENINSULA': 'Zamboanga Peninsula', 'NORTHERN MINDANAO': 'Northern Mindanao',
                'DAVAO REGION': 'Davao', 'SOCCSKSARGEN': 'Soccsksargen', 'CARAGA': 'Caraga'}


def region_name(desc):
    """'REGION III (CENTRAL LUZON)' -> 'Central Luzon', 'NATIONAL CAPITAL REGION (NCR)' -> 'Metro Manila'."""
    m = re.match(r'\s*(.*?)\s*\((.*)\)\s*$', desc or '')
    if not m:
        return (desc or '').title()
    head, inner = m.group(1), m.group(2).upper()
    if inner in REGION_NAMES:
        return REGION_NAMES[inner]
    return {'NCR': 'Metro Manila', 'CAR': 'the Cordillera', 'ARMM': 'Bangsamoro'}.get(inner, head.title())


def fmt(x):
    return ('%.1f' % x).rstrip('0').rstrip('.') if isinstance(x, float) else str(x)


def code(r):
    m = re.match(r'\s*\(?\s*(MR|MS|R|I|S)', r or '')
    return m.group(1) if m else None


def article(word):
    return 'an' if word[:1].lower() in 'aeiou' else 'a'


def load():
    return json.load(open(os.path.join(HERE, 'varieties.json'), encoding='utf-8'))


def env_key(v):
    return v['environments'][0] if v['environments'] else None


def group_of(v):
    return (v['type'], env_key(v))


def build(v, allv, stats):
    n = v['name']
    slug = v['slug']
    num = re.sub(r'^(NSIC|PSB|BPI|UPL)\s+(Rc|Ri)\s+', '', n)
    e = env_key(v)
    env_short = ENV[e][0] if e else 'Philippine rice farms'
    kind = 'hybrid' if v['type'] == 'hybrid' else 'inbred'
    kind_txt = ('glutinous ' if v['special'] == 'glutinous' else 'special ' if v['special'] == 'special' else '') + kind
    has = v.get('maturity') is not None and v.get('yieldAvg') is not None
    mat = int(v['maturity']) if has else None
    avg, top = (v['yieldAvg'], v['yieldMax']) if has else (None, None)
    cav = int(round(avg * 20)) if has else None
    seasons = [SEASON[x] for x in v.get('seasons') or [] if x in SEASON]
    est = v.get('establishment') or []
    regions = [region_name(r) for r in v.get('regions') or []]
    by = v['breeder'] or 'its breeder'
    yr = v['year']
    d = v.get('direct')
    s2 = v.get('second')
    local = v.get('localName')
    if local and '-' in local:
        local = None   # a hyphen in the name (Ma-ayon): STYLE.md allows none in text
    # The names it is sold and searched under (Jackpot 102 for NSIC Rc 666H);
    # a name written with a hyphen (SL-8H) is written with a space instead.
    trades = [re.sub(r'(?<=[A-Za-z0-9])-(?=[A-Za-z0-9])', ' ', t) for t in v.get('tradeNames') or []]
    trades = [t for t in trades if not re.search(r'[;&%]', t)]
    trade = trades[0] if trades else None

    # ---- the head of the page
    title = '%s%s Rice Variety: %s' % (n, (' (%s)' % local) if local else '', 'Yield, Maturity and Characteristics' if has else 'Characteristics and Grain Quality')
    if trade:
        title = '%s (%s) %s Rice: %s' % (trade, n, 'Hybrid' if kind == 'hybrid' else 'Inbred', 'Yield, Maturity and Characteristics' if has else 'Characteristics and Grain Quality')
    meta_title = next((t for t in ([
        '%s (%s): Yield, Maturity and Characteristics' % (trade, n) if trade else '',
        '%s (%s) Rice: Yield and Maturity' % (trade, n) if trade else '',
    ] if has else [
        '%s (%s): Characteristics and Grain Quality' % (trade, n) if trade else '',
        '%s (%s) Rice Variety' % (trade, n) if trade else '',
    ]) + ([
        '%s Rice Variety: Yield, Maturity and Characteristics' % n,
        '%s (%s): Yield, Maturity, Characteristics' % (n, local) if local else '',
        '%s: Yield, Maturity and Characteristics' % n,
        '%s Rice Variety: Yield and Maturity' % n,
    ] if has else [
        '%s Rice Variety: Characteristics and Grain Quality' % n,
        '%s (%s): Characteristics and Grain' % (n, local) if local else '',
        '%s Rice Variety: Characteristics' % n,
    ]) if t and len(t) <= 60), '%s Rice Variety' % n)
    desc_opts = [] if not has else [
        '%s is %s %s rice for %s: about %d days and %s tonnes per hectare on average. See its pest ratings and grain quality.' % (n, article(kind_txt), kind_txt, env_short, mat, fmt(avg)),
        '%s is %s %s rice: about %d days and %s tonnes per hectare on average. See its pest ratings and grain quality.' % (n, article(kind_txt), kind_txt, mat, fmt(avg)),
        '%s: about %d days to harvest and %s tonnes per hectare on average, with its pest ratings and grain quality from PhilRice.' % (n, mat, fmt(avg)),
    ]
    if not has:
        desc_opts = [
            '%s%s is %s %s rice for %s, approved in %s. See its pest and disease ratings, grain quality and where it grows best.' % (n, (' (%s)' % local) if local else '', article(kind_txt), kind_txt, env_short, yr),
            '%s is %s %s rice for %s, approved in %s. See its pest and disease ratings, grain quality and where it grows best.' % (n, article(kind_txt), kind_txt, env_short, yr),
            '%s is %s %s rice approved in %s. See its pest and disease ratings, its grain quality and where it grows best.' % (n, article(kind_txt), kind_txt, yr),
        ]
    # The name on the sack comes first when there is one (Jackpot 102).
    if trade:
        desc_opts = ([
            '%s is %s, %s %s rice for %s: about %d days and %s tonnes per hectare on average. See its ratings.' % (trade, n, article(kind_txt), kind_txt, env_short, mat, fmt(avg)),
            '%s is %s, %s %s rice: about %d days and %s tonnes per hectare on average. See its pest ratings and grain.' % (trade, n, article(kind_txt), kind_txt, mat, fmt(avg)),
        ] if has else [
            '%s is %s, %s %s rice for %s approved in %s. See its pest ratings, grain quality and where it grows best.' % (trade, n, article(kind_txt), kind_txt, env_short, yr),
            '%s is %s, %s %s rice approved in %s. See its pest and disease ratings, grain quality and where it grows best.' % (trade, n, article(kind_txt), kind_txt, yr),
        ]) + desc_opts
    meta_desc = next((x for x in desc_opts if 120 <= len(x) <= 156), None)
    if not meta_desc:
        meta_desc = desc_opts[-1]
        while len(meta_desc) < 120:
            meta_desc = meta_desc[:-1] + ', in plain words.'
    approved = ('The NSIC approved it in %s.' % yr if n.startswith('NSIC') else 'The Philippine Seed Board approved it in %s.' % yr if n.startswith('PSB') else 'PhilRice lists it from %s.' % yr)
    bred = ('It is a traditional variety. ' if v.get('traditional') or by == 'Traditional variety' else '%s bred it. ' % by)
    known = []
    if trade:
        known.append('sold as %s' % (' or '.join(trades[:2])))
    if local:
        known.append('known locally as %s' % local)
    excerpt = '%s is %s %s rice variety for %s%s. %s %s' % (
        n, article(kind_txt), kind_txt, env_short, (', ' + ' and '.join(known)) if known else '', approved, bred)
    if has:
        excerpt += 'It matures in about %d days and averages %s tonnes per hectare%s.' % (mat, fmt(avg), (', with a top yield of %s tonnes in trials' % fmt(top)) if top else '')
    else:
        excerpt += 'PhilRice has not listed its days to harvest and trial yield yet, but its pest ratings and grain quality are below.'

    blocks = []
    H = lambda t, lvl=2: blocks.append({'type': 'heading', 'level': lvl, 'text': t})
    T = lambda t: blocks.append({'type': 'text', 'text': t})

    # ---- why it matters, then the key points
    g = stats.get(group_of(v), {'avg': 0, 'count': 0})
    if trade and num != n and re.match(r'^\d', num):
        search = 'If you came here looking for %s, or rice variety %s, this is the one. ' % (' or '.join(trades[:2]), num)
    elif num != n and re.match(r'^\d', num):
        search = 'If you came here looking for rice variety %s, or Rc %s, this is the one. ' % (num, num)
    else:
        search = ''
    T(search + 'The facts below come from sources that include PhilRice and its public seed database. '
      'So you can compare it with the other varieties in our [rice variety finder](/rice-varieties) before you buy seed. '
      'Also read our guide to [rice varieties in the Philippines](/crops/rice-varieties-philippines) for how inbreds, hybrids and special rice differ.')
    pts = []
    if has:
        pts.append('**Maturity:** about %d days after sowing%s.' % (mat, (' when transplanted, %d when direct seeded' % int(d['maturity'])) if d else ''))
        pts.append('**Yield:** %s tonnes per hectare on average, or about %d cavans of 50 kilograms%s.' % (fmt(avg), cav, ('. The best trial reached %s tonnes' % fmt(top)) if top else ''))
    if seasons:
        pts.append('**Season:** %s.' % (' and '.join(seasons)).capitalize())
    res = [k for k, r in v['reactions'].items() if code(r) in ('R', 'MR')]
    weak = [k for k, r in v['reactions'].items() if code(r) in ('S', 'MS')]
    if res:
        pts.append('**Holds up against:** ' + ', '.join(PROBLEM[k][0].lower() for k in res) + '.')
    if weak:
        pts.append('**Watch out for:** ' + ', '.join(PROBLEM[k][0].lower() for k in weak) + '.')
    am = v['grain'].get('amylose')
    if am and AMYLOSE.get(am[1]):
        pts.append('**Cooked rice:** %s amylose, so %s.' % (AMYLOSE[am[1]], 'soft and tender' if am[1] in ('VL', 'L', 'l') else 'soft but not sticky' if am[1] == 'I' else 'firm and fluffy'))
    blocks.append({'type': 'list', 'ordered': False, 'items': pts[:5]})

    # ---- at a glance
    H('Key facts about %s' % n)
    rows = [['Trait', 'What PhilRice lists']]
    rows.append(['Year approved', str(yr)])
    rows.append(['Bred by', by])
    if trades:
        rows.append(['Sold as', ', '.join(trades)])
    if local:
        rows.append(['Local name', local])
    rows.append(['Type', kind_txt.capitalize()])
    rows.append(['Growing environment', ', '.join(ENV[x][1] for x in v['environments']) if v['environments'] else 'Not stated'])
    if v.get('remarks') and not re.search(r'[;&%-]', v['remarks']):
        rows.append(['Also suited to', v['remarks'][0].upper() + v['remarks'][1:]])
    if seasons:
        rows.append(['Recommended season', ' and '.join(seasons).capitalize()])
    if est:
        rows.append(['How to plant it', ', '.join(est).capitalize()])
    if regions:
        rows.append(['Tested and suited in', ', '.join(regions)])
    elif v.get('location') and not re.search(r'[;&%-]', v['location']):
        rows.append(['Recommended for', 'The whole country' if v['location'] == 'National' else v['location']])
    if has:
        rows.append(['Maturity%s' % (', transplanted' if d else ''), '%d days after sowing' % mat])
        if d:
            rows.append(['Maturity, direct seeded', '%d days after sowing' % int(d['maturity'])])
        rows.append(['Average yield%s' % (', transplanted' if d else ''), '%s t/ha' % fmt(avg)])
        if d:
            rows.append(['Average yield, direct seeded', '%s t/ha' % fmt(d['yieldAvg'])])
        if top:
            rows.append(['Top yield in trials', '%s t/ha' % fmt(top)])
    if v.get('height'):
        rows.append(['Plant height', '%d cm' % int(v['height'])])
    blocks.append({'type': 'table', 'caption': 'From PhilRice\'s catalogue of released varieties (t/ha means tonnes per hectare)', 'rows': rows})
    if s2:
        T('PhilRice lists a second set of results for this variety too: %d days and %s tonnes per hectare. '
          'So expect a range, because the season and the way you plant change both.' % (int(s2['maturity']), fmt(s2['yieldAvg'])))

    # ---- yield and maturity, when PhilRice lists them
    if has:
        H('How much does %s yield?' % n)
        cmp_word = 'above' if avg > g['avg'] + 0.15 else 'below' if avg < g['avg'] - 0.15 else 'close to'
        peers = '%s %s in PhilRice\'s list' % (g['count'], ('%s %ss' % (ENV[e][0] if e else 'Philippine', kind)) if g['count'] != 1 else kind)
        T(('On average it gives %s tonnes per hectare. That is about %d cavans of 50 kilograms of palay. '
           'It sits %s the average of the %s, which is %s tonnes. ') % (fmt(avg), cav, cmp_word, peers, fmt(round(g['avg'], 1)))
          + (('But the top yield, %s tonnes, came from a trial with good water, seed and care. ' % fmt(top)) if top else '')
          + 'So plan your costs on the average, then work toward the top with the habits in our guide to '
            '[raising rice yield per hectare](/blog/increase-rice-yield-per-hectare).')
        H('How many days does it take to mature?')
        grp = 'very early' if mat < 100 else 'short duration' if mat <= 120 else 'medium duration' if mat <= 140 else 'long duration'
        T(('It takes about %d days from sowing to harvest%s. ') % (mat, (', and about %d days if you seed it directly' % int(d['maturity'])) if d else '')
          + ('IRRI groups varieties of 100 to 120 days as short duration and 120 to 140 days as medium, so this one counts as %s. ' % grp)
          + 'Then harvest when 85 to 90 percent of the grains turn golden yellow, as PalayCheck advises. '
            'Because the day count drives every task, anee.io counts it for you from each lot\'s own planting day, with the '
            '[growth stages and weather](/features/growth-stages-and-weather) beside it.')
    else:
        H('How many days does it take, and how much does it yield?')
        T('PhilRice has not published this variety\'s days to harvest and trial yields in its catalogue yet. '
          'So ask the seed seller or your municipal agriculturist for the figures from the trials in your region before you plan the season. '
          'Once you know the day count, anee.io dates every task from each lot\'s own planting day, with the '
          '[growth stages and weather](/features/growth-stages-and-weather) beside it. '
          'Meanwhile, our guide to [raising rice yield per hectare](/blog/increase-rice-yield-per-hectare) covers the habits that matter with any variety.')

    # ---- pests and diseases
    H('Is it resistant to pests and diseases?')
    if v['reactions']:
        rrows = [['Pest or disease', 'PhilRice rating', 'What it means']]
        for k, r in v['reactions'].items():
            c = code(r)
            label = RATING.get(c, ('Not rated', ''))
            rrows.append([PROBLEM[k][0], r if r.strip() != c else label[0], label[1].capitalize() if label[1] else 'No clear rating'])
        blocks.append({'type': 'table', 'caption': 'Reactions in PhilRice\'s tests: R resistant, MR moderately resistant, I intermediate, MS moderately susceptible, S susceptible. Some carry a second rating in brackets, as PhilRice lists them.', 'rows': rrows})
        if weak:
            links = ', '.join('[%s](%s)' % (PROBLEM[k][0].lower(), PROBLEM[k][1]) for k in weak)
            T('So scout early for %s. When you see the first signs, check the guide before you spray, because the right timing saves more than an extra dose. '
              'If you are not sure what you see, our [pest and disease finders](/features/pest-and-disease-finders) help you name it from the damage.' % links)
        else:
            T('It showed no weak spot in the ratings PhilRice lists. Still, a resistant variety can break down when a new strain of a disease or a new pest population comes. '
              'So keep scouting, and use our [pest and disease finders](/features/pest-and-disease-finders) when something looks wrong.')
    else:
        T('PhilRice\'s table gives no pest and disease ratings for this variety. So treat it as untested and scout every week, '
          'above all for [rice blast](/diseases/rice-blast), [tungro](/diseases/rice-tungro) and the [brown planthopper](/pests/brown-planthopper). '
          'Our [pest and disease finders](/features/pest-and-disease-finders) help you name what you see.')

    # ---- grain
    gr = v['grain']
    if gr:
        H('Grain and eating quality')
        grows = [['Measure', 'Value', 'Class']]
        if am:
            grows.append(['Amylose', '%s percent' % fmt(am[0]), (AMYLOSE.get(am[1], '') or 'Not given').capitalize()])
        if gr.get('milling'):
            grows.append(['Milling recovery', '%s percent' % fmt(gr['milling'][0]), GRADE.get(gr['milling'][1], 'Not graded')])
        if gr.get('head'):
            grows.append(['Head rice', '%s percent' % fmt(gr['head'][0]), GRADE.get(gr['head'][1], 'Not graded')])
        if gr.get('chalky'):
            grows.append(['Chalky grains', '%s percent' % fmt(gr['chalky'][0]), GRADE.get(gr['chalky'][1], 'Not graded')])
        if gr.get('length'):
            grows.append(['Grain length', '%s mm' % fmt(gr['length'][0]), (LENGTH.get(gr['length'][1], '') or 'Not given').capitalize()])
        if gr.get('shape'):
            grows.append(['Grain shape, length to width', fmt(gr['shape'][0]), (SHAPE.get(gr['shape'][1], '') or 'Not given').capitalize()])
        if gr.get('protein'):
            grows.append(['Protein', '%s percent' % fmt(gr['protein']), ''])
        blocks.append({'type': 'table', 'caption': 'Grain quality in PhilRice\'s seed database', 'rows': grows})
        words = []
        if am and AMYLOSE.get(am[1]):
            soft = {'VL': 'very soft and sticky', 'L': 'soft', 'l': 'soft', 'I': 'soft but not sticky', 'H': 'firm and dry, and it hardens as it cools'}[am[1]]
            words.append('Amylose decides how rice cooks. With %s amylose, expect rice that is %s. ' % (AMYLOSE[am[1]], soft)
                         + 'In fact, PhilRice notes that Filipino taste moved from high to intermediate and low amylose rice, because people want it softer.')
        if gr.get('head'):
            words.append('Head rice is the share of whole grains after milling. So a higher number means fewer broken grains and a better price at the mill.')
        if words:
            T(' '.join(words))

    # ---- where and when
    H('Where should you plant it?')
    if e:
        where = {
            'irrigated': 'It suits irrigated lowland, where you control the water. Level the field well so the water stands evenly, and plant at the same time as your neighbors, one of the PalayCheck key checks, so pests cannot move from field to field. Our guide to [rice land preparation](/land-preparation/rice-land-preparation) shows each step.',
            'rainfed-tpr': 'It suits rainfed lowland that depends on the rains. So wait until the rains settle before you sow the seedbed, then transplant into the puddled field. Our guide to [rice land preparation](/land-preparation/rice-land-preparation) shows each step.',
            'rainfed-dsr': 'It suits rainfed fields that are seeded directly. So prepare the field when the first rains come, and seed it once the soil holds moisture. Our guide to [land preparation for direct seeded rice](/land-preparation/direct-seeded-rice-land-preparation) shows how.',
            'upland': 'It suits upland fields, with no standing water. So sow at the start of the rains, and keep weeds down in the first weeks, because upland rice cannot drown them out. Our [rice variety finder](/rice-varieties) lists the other upland choices.',
            'saline': 'It suits fields where salt reaches the soil, such as coastal paddies. So flush the field with fresh water before planting when you can. Our guide to [saline soil preparation](/land-preparation/saline-soil-preparation) shows what helps.',
            'flood': 'It suits fields that flood in the wet season. Even so, drain the field as soon as the water allows, because a long flood still costs yield. Our guide to [typhoon preparation](/blog/typhoon-preparation-for-farmers) helps you plan for storms.',
            'drought': 'It suits fields that run short of water in a dry spell. Also time your planting with the rains, and check our tips on [El Nino and La Nina farming](/blog/el-nino-la-nina-farming-tips) before a dry year.',
            'cool': 'It suits cool, elevated places such as the rice terraces of the Cordillera, where the nights stay cold. So use it where lowland varieties grow too slowly or set too few grains.',
        }[e]
        more = []
        if seasons:
            more.append('PhilRice recommends it for the %s.' % ' and the '.join(seasons))
        if est:
            more.append('You can plant it %s.' % (' or '.join(est) if len(est) < 3 else ', '.join(est[:-1]) + ' or ' + est[-1]))
        if regions:
            more.append('It was tested and found suitable in %s.' % (', '.join(regions[:-1]) + ' and ' + regions[-1] if len(regions) > 1 else regions[0]))
        T(where + ' ' + ' '.join(more) + (' ' if more else '') + 'For the right month in your province, see [the best time to plant rice](/blog/best-time-to-plant-rice-philippines).')
    else:
        T('PhilRice\'s table does not say which growing environment this variety is for. So ask your municipal agriculturist whether it does well in your town, '
          'and read [how to pick the best rice variety for your area](/blog/best-rice-variety-for-my-area). For the right month, see [the best time to plant rice](/blog/best-time-to-plant-rice-philippines).')

    # ---- the way into the app
    blocks.append({'type': 'cta', 'title': 'Plan your next season of %s' % n,
                   'text': 'Set the planting day of each lot and anee.io dates every task to harvest, with the weather beside it. Free on the Libre plan.',
                   'label': 'Start free', 'url': '/signup'})

    # ---- similar varieties
    withdata = [x for x in allv if x['slug'] != slug and x.get('maturity') is not None and x.get('yieldAvg') is not None]
    if has:
        near = lambda x: abs(x['maturity'] - v['maturity']) + abs(x['yieldAvg'] - avg) * 6 - (x['year'] or 0) * .01
    else:
        near = lambda x: abs((x['year'] or 0) - yr) - (x['year'] or 0) * .01
    sims = sorted([x for x in withdata if group_of(x) == group_of(v)], key=near)[:4]
    if len(sims) < 3:
        sims = sorted([x for x in withdata if x['type'] == v['type']], key=near)[:4]
    if sims:
        H('Varieties like this one')
        T('These come from the same kind of farm%s. So if seed of this one is hard to find, compare these first.' % (' and grow about as long' if has else ', and PhilRice lists their days and yields'))
        blocks.append({'type': 'list', 'ordered': False, 'items': [
            '[%s](/rice-varieties/%s): %d days, %s tonnes per hectare on average, %s' % (x['name'], x['slug'], int(x['maturity']), fmt(x['yieldAvg']), x['year'])
            for x in sims]})

    # ---- questions
    faq = []
    if trade:
        faq.append({'q': 'Is %s the same as %s?' % (trade, n),
                    'a': 'Yes. %s is the name it is sold under, and %s is its registration with the National Seed Industry Council%s. The numbers on this page are PhilRice\'s for %s.' % (
                        trade, n, (', where its local name is %s' % local) if local else '', n)})
    if local:
        faq.append({'q': 'What is the local name of %s?' % n,
                    'a': 'It is known as %s. Seed sellers and agriculturists use both names, so ask for either one.' % local})
    if has:
        faq.append({'q': 'How many days does %s take to harvest?' % n,
                    'a': 'About %d days after sowing%s. Then harvest when 85 to 90 percent of the grains are golden yellow.' % (mat, (' when transplanted, and about %d days when direct seeded' % int(d['maturity'])) if d else '')})
        faq.append({'q': 'What is the average yield of %s?' % n,
                    'a': 'PhilRice lists %s tonnes per hectare on average, about %d cavans of 50 kilograms.%s Your own yield depends on water, seed, fertilizer and timing.' % (
                        fmt(avg), cav, (' The best trial reached %s tonnes.' % fmt(top)) if top else '')})
    else:
        faq.append({'q': 'When was %s approved?' % n,
                    'a': '%s %sPhilRice has not listed its days to harvest and trial yield yet, so ask for the regional trial figures before you plant.' % (approved, bred)})
    if seasons:
        faq.append({'q': 'In which season should I plant it?',
                    'a': 'PhilRice recommends it for the %s. Then pick the month with our planting calendar guide and the forecast for your town.' % ' and the '.join(seasons)})
    if kind == 'hybrid':
        faq.append({'q': 'Is this variety a hybrid?', 'a': 'Yes. So buy new seed each season, because seed saved from a hybrid crop does not grow true and yields less.'})
    else:
        faq.append({'q': 'Is this variety a hybrid?', 'a': 'No, it is an inbred. So the seed you buy grows true, but certified seed still gives the best start, as PalayCheck advises.'})
    if weak:
        faq.append({'q': 'What should I watch for when I plant it?',
                    'a': 'In PhilRice\'s tests it was weak against %s. So scout from the early weeks and act at the first signs.' % ', '.join(PROBLEM[k][0].lower() for k in weak)})
    if am and AMYLOSE.get(am[1]):
        faq.append({'q': 'Is it soft when cooked?',
                    'a': 'It has %s amylose, %s percent. %s' % (AMYLOSE[am[1]], fmt(am[0]), 'So it cooks soft.' if am[1] in ('VL', 'L', 'l') else 'So it cooks soft but not sticky.' if am[1] == 'I' else 'So it cooks firm and fluffy, and hardens as it cools.')})
    faq.append({'q': 'Where can I buy certified seed of this variety?',
                'a': 'Ask accredited seed growers in your province, your municipal agriculturist or the nearest PhilRice station. Look for the certified seed tag on the sack before you pay.'})
    blocks.append({'type': 'faq', 'items': faq[:6]})

    blocks.append({'type': 'links', 'title': 'Read next', 'items': [
        {'label': 'Every rice variety, side by side', 'url': '/rice-varieties'},
        {'label': 'Rice varieties in the Philippines', 'url': '/crops/rice-varieties-philippines'},
        {'label': 'The best rice variety for your area', 'url': '/blog/best-rice-variety-for-my-area'},
        {'label': 'Pagtatanim ng palay', 'url': '/crops/pagtatanim-ng-palay'},
        {'label': 'Palay: growth stages and harvest', 'url': '/crops/palay'},
    ]})
    sources = [SRC_PHILRICE]
    sources += [{'label': 'Where %s is named as %s' % (trade, n), 'url': u} for u in (v.get('tradeSources') or [])[:2]] if trade else []
    sources += [{'label': 'PhilRice seed database: %s variety characteristics%s' % (n, ', second record' if i else ''), 'url': u} for i, u in enumerate(v['sources'][:2])]
    sources += [SRC_NSIC, SRC_PALAYCHECK, SRC_IRRI_STAGES]
    if am:
        sources += [SRC_FAO, SRC_JULIANO]
    if kind == 'hybrid':
        sources.append(SRC_HYBRID)
    blocks.append({'type': 'sources', 'items': sources})

    hero = HERO['hybrid'] if kind == 'hybrid' and e == 'irrigated' else HERO.get(e, HERO[None])
    kw = [n, 'rice variety %s' % num if num != n else n, 'rc %s' % num if num != n else n, '%s rice variety characteristics' % num if num != n else n] + ([local] if local else []) + trades
    page = {
        'section': 'rice-varieties', 'slug': slug, 'sortOrder': 10 + allv.index(v), 'lang': 'en',
        'category': ('Hybrid rice' if kind == 'hybrid' else 'Glutinous rice' if v['special'] == 'glutinous' else (ENV[e][1] if e else 'Inbred rice')),
        'profile': True,
        'title': title, 'metaTitle': meta_title, 'metaDescription': meta_desc,
        'focusKeyword': n, 'keywords': list(dict.fromkeys(kw)),
        'excerpt': excerpt,
        'heroImage': {'src': hero[0], 'alt': hero[1].format(n=n), 'credit': ''},
        'blocks': blocks,
    }
    return page


def main():
    allv = load()
    stats = {}
    for v in allv:
        k = group_of(v)
        if v.get('yieldAvg') is not None:
            stats.setdefault(k, []).append(v['yieldAvg'])
    stats = {k: {'avg': statistics.mean(x), 'count': len(x)} for k, x in stats.items()}
    os.makedirs(OUT, exist_ok=True)
    keep = set()
    for v in allv:
        p = build(v, allv, stats)
        keep.add(p['slug'] + '.json')
        json.dump(p, open(os.path.join(OUT, p['slug'] + '.json'), 'w', encoding='utf-8', newline='\n'), indent=2, ensure_ascii=False)
    for f in os.listdir(OUT):
        if f.endswith('.json') and f not in keep:
            os.remove(os.path.join(OUT, f))
    print(len(allv), 'pages')


if __name__ == '__main__':
    main()
