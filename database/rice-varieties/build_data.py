"""Builds varieties.json, the rice variety catalogue behind /rice-varieties.

The facts come from PhilRice: the variety list inside its free Binhing Palay
app (the owner downloaded the app, 2026-10-13; only the bundled data file
assets/varieties.json and assets/regions.json are read, never its pictures,
words or code), and PhilRice's public seed database at
https://dbmp.philrice.gov.ph/seeds, crawled the same day, which fills what
the app leaves blank and links each variety to a public record. Only facts
are kept (names, years, numbers, ratings, places); every sentence on the
pages is anee.io's own, written by build_pages.py.

    python database/rice-varieties/build_data.py <app data folder> <crawl folder>

The app data folder holds varieties.json and regions.json from the app; the
crawl folder holds index_rows.json, dbmp.json and research.json (local names
and growing environments from the NSIC registry, each with its source).
"""
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
APP = sys.argv[1] if len(sys.argv) > 1 else HERE
CRAWL = sys.argv[2] if len(sys.argv) > 2 else HERE

TAB_ENV = {'Irrigated': 'irrigated', 'Rainfed_TPR': 'rainfed-tpr', 'Rainfed_DSR': 'rainfed-dsr', 'Upland': 'upland',
           'Saline_Prone': 'saline', 'Flood_Prone': 'flood', 'Drought_Prone': 'drought', 'Cool_Elevated': 'cool'}
# The app's own codes, read from its data: 1 holds the Tubigan and Mestiso
# lines, 2 the Sahod Ulan, 3 the Cordillera names, 4 the Salinas, 5 the Katihan.
APP_ENV = {1: 'irrigated', 2: 'rainfed', 3: 'cool', 4: 'saline', 5: 'upland'}
# PhilRice names its lines by where they grow; when the app gives no code.
NAME_ENV = [('tubigan', 'irrigated'), ('mestiso', 'irrigated'), ('mestizo', 'irrigated'), ('mestio', 'irrigated'), ('sahod ulan', 'rainfed'),
            ('salinas', 'saline'), ('katihan', 'upland'), ('submarino', 'flood'), ('japonica', 'irrigated'), ('mabango', 'irrigated'),
            ('malagkit', 'irrigated'), ('pangmainit', 'irrigated'), ('zinc', 'irrigated')]
BREEDER = {'PHILRICE': 'PhilRice', 'SYNGENTA': 'Syngenta', 'BAYER': 'Bayer', 'PHILSCAT': 'PhilSCAT', 'PhilsCAT': 'PhilSCAT', 'PhilScat': 'PhilSCAT',
           'Trad. Variety': 'Traditional variety', 'SL-Agritech': 'SL Agritech', 'SL Agritech Corp.': 'SL Agritech', 'MONSANTO': 'Monsanto',
           'BIOSEED': 'Bioseed', 'HYRICE': 'Hyrice', 'PHILRICE & UPLB': 'PhilRice and UPLB', 'PhilRice/UPLB': 'PhilRice and UPLB',
           'UPLB/PhilRice': 'UPLB and PhilRice', 'IRRI & PhilRice': 'IRRI and PhilRice', 'IRRI/PhilRice': 'IRRI and PhilRice',
           'Methahelix': 'Metahelix', 'ADVANTA': 'Advanta', 'SEEDWORKS': 'Seedworks', 'PIONEER': 'Pioneer', 'PhilsCAT/Long Ping': 'PhilSCAT and Long Ping'}
ACRONYMS = {'GSR', 'PJ7', 'IR65', 'BPI', 'UPL', 'MS', 'NSIC', 'PSB'}
LOCAL_FIX = {'PE�ARANDA': 'Peñaranda', 'Pe�aranda': 'Peñaranda', 'Mestio 88': 'Mestiso 88'}
APP_SOURCE = 'https://play.google.com/store/apps/details?id=ph.gov.philrice.binhi'


def num(v):
    m = re.match(r'\s*([0-9]+(?:\.[0-9]+)?)', str(v) if v is not None else '')
    return float(m.group(1)) if m else None


def graded(v):
    """'52.1 G1' -> [52.1, 'G1']."""
    n = num(v)
    if n is None:
        return None
    parts = str(v).split()
    return [n, parts[1] if len(parts) > 1 else None]


def display(raw):
    """'NSIC 2009 Rc222' -> 'NSIC Rc 222', 'PSB RC92' -> 'PSB Rc 92', 'NSIC Rc 762 SR' -> 'NSIC Rc 762SR'."""
    s = re.sub(r'\b(19|20)\d\d\s+', '', raw.strip())
    s = re.sub(r'\b(RC|Rc|rc)\s*(\d+)', lambda m: 'Rc ' + m.group(2), s)
    s = re.sub(r'\bRi\s*(\d+)', r'Ri \1', s)
    s = re.sub(r'\s+', ' ', s).strip()
    s = re.sub(r'(\d)\s+(H|SR)\b', r'\1\2', s)
    return s


def slugify(name):
    return re.sub(r'[^a-z0-9]+', '-', name.lower()).strip('-')


def local_title(s):
    s = (s or '').strip()
    if not s:
        return None
    s = LOCAL_FIX.get(s, s)
    words = []
    for w in s.split():
        words.append(w.upper() if w.upper() in ACRONYMS else (w if not w.isupper() and not w.islower() else w.capitalize()))
    out = ' '.join(words)
    return LOCAL_FIX.get(out, out)


def rating(v):
    v = (v or '').strip()
    return v if v and v not in ('-',) else None


def main():
    app = json.load(open(os.path.join(APP, 'varieties.json'), encoding='utf-8'))
    regions = {r['regCode']: r['regDesc'] for r in json.load(open(os.path.join(APP, 'regions.json'), encoding='utf-8'))}
    rows = json.load(open(os.path.join(CRAWL, 'index_rows.json'), encoding='utf-8'))
    detail = json.load(open(os.path.join(CRAWL, 'dbmp.json'), encoding='utf-8'))
    research = {}
    rp = os.path.join(CRAWL, 'research.json')
    if os.path.exists(rp):
        for r in json.load(open(rp, encoding='utf-8')):
            research[display(r.get('name', ''))] = r

    # The public database, by name: its environment tabs, its ratings table, its own pages.
    db = {}
    for r in rows:
        name = display(r['cells'][0])
        c, vid = r['cells'], str(r['id'])
        rec = db.setdefault(name, {'envs': set(), 'ids': [], 'rows': []})
        rec['envs'].update(TAB_ENV[t] for t in r['eco'] if t in TAB_ENV)
        if 'Glutinous' in r['eco']:
            rec['glutinous'] = True
        rec['ids'].append(vid)
        rec['rows'].append((c, detail.get(vid, {})))

    out, seen = [], set()
    for a in app:
        name = display(a['name'])
        if '-' in name or not a.get('year_approved'):
            continue   # a breeding line code as its name, or a record without its year
        d = db.get(name, {'envs': set(), 'ids': [], 'rows': []})
        rs = research.get(name, {})
        slug = slugify(name)
        if slug in seen:
            continue
        seen.add(slug)

        # Trial results: transplanted (the app's type 1), direct seeded (type 2), or one set.
        trials = {e.get('plant_type'): e for e in a.get('varietydata') or []}
        main_t = trials.get(1) or trials.get(None) or trials.get(3) or trials.get(4)
        direct_t = trials.get(2) if (trials.get(1) or trials.get(None)) else None
        if not main_t and trials.get(2):
            main_t = trials.get(2)
        mat = num(main_t.get('maturity_days')) if main_t else None
        avg = num(main_t.get('average_yield')) if main_t else None
        top = num(main_t.get('maximum_yield')) if main_t else None
        height = num(main_t.get('plant_height')) if main_t else None
        if mat is None and d['rows']:
            c = max(d['rows'], key=lambda x: num(x[0][3]) or 0)[0]
            mat, avg, top, height = num(c[3]), num(c[1]), num(c[2]) or None, num(c[4])
        direct = None
        if direct_t and num(direct_t.get('maturity_days')):
            direct = {'maturity': num(direct_t['maturity_days']), 'yieldAvg': num(direct_t.get('average_yield')), 'yieldMax': num(direct_t.get('maximum_yield')) or None}

        # Where it grows.
        envs = []
        code = a.get('ecosystem')
        dom = ' '.join(str(a.get(k) or '') for k in ('recommendation_domain', 'recommended_location', 'remarks')).lower()
        if code in APP_ENV:
            envs.append(APP_ENV[code])
        local = local_title(a.get('local_name')) or local_title(rs.get('localName'))
        if not envs and local:
            envs += [e for k, e in NAME_ENV if local.lower().startswith(k)][:1]
        if 'rainfed' in envs:
            tabs = [e for e in d['envs'] if e.startswith('rainfed')]
            envs[envs.index('rainfed')] = tabs[0] if tabs else ('rainfed-dsr' if ('dry seeded' in dom or 'dsr' in dom or 'dds' in dom) else 'rainfed-tpr')
        for extra, key in (('drought', 'drought'), ('submerg', 'flood'), ('flood', 'flood'), ('saline', 'saline')):
            if key not in envs and (extra in dom or key in d['envs']):
                envs.append(key)
        if not envs:
            envs = sorted(d['envs'])
        envs = list(dict.fromkeys(envs))

        cat = a.get('category')
        compact = name.replace(' ', '')
        hybrid = bool(re.search(r'\d+H$', compact)) or cat == 2
        special = 'glutinous' if (cat == 3 or d.get('glutinous') or (local or '').lower().startswith('malagkit')) else ('special' if (cat == 4 or compact.endswith('SR')) else None)

        # Ratings: the app's, then the public table's for the blanks.
        reactions = {
            'blast': rating(a.get('blast')), 'blb': rating(a.get('bacterial_leaf_blight')), 'tungro': rating(a.get('tungro')),
            'bph': rating(a.get('brown_plant_hopper')), 'glh': rating(a.get('green_leaf_hopper')),
            'sb': rating(a.get('stemborer')) or rating(a.get('yellow_stemborer')), 'shb': rating(a.get('sheath_blight')),
        }
        if d['rows']:
            c = d['rows'][0][0]
            for k, i in (('blast', 5), ('blb', 6), ('tungro', 7), ('bph', 8), ('glh', 9), ('sb', 10)):
                if not reactions.get(k):
                    reactions[k] = rating(c[i])
        reactions = {k: v for k, v in reactions.items() if v}

        det = next((x[1] for x in d['rows'] if x[1].get('Amylose')), {})
        grain = {
            'amylose': graded(a.get('amylose')) or graded(det.get('Amylose')), 'protein': num(a.get('protein')) or num(det.get('Protein')),
            'milling': graded(a.get('milling_recovery')) or graded(det.get('Milling')), 'brown': graded(a.get('brown_rice')) or graded(det.get('Brown rice')),
            'head': graded(a.get('head_rice')) or graded(det.get('Head rice')), 'chalky': graded(a.get('chalky_grains')) or graded(det.get('Chalky grain')),
            'immature': graded(a.get('immature_grains')) or graded(det.get('im_grains')), 'length': graded(a.get('grain_size')) or graded(det.get('Grain length')),
            'shape': graded(a.get('grain_shape')) or graded(det.get('Grain shape')),
        }
        grain = {k: v for k, v in grain.items() if v}

        season = [s for s in (a.get('recommended_season') or '').replace(' ', '').split(',') if s in ('DS', 'WS')]
        est = set()
        for p in re.split(r'[,\s]+', a.get('recommended_crop_establishment') or ''):
            p = p.upper()
            if p in ('TR', 'TPR'):
                est.add('transplanted')
            elif p in ('DWS', 'DWSR'):
                est.add('wet direct seeded')
            elif p == 'DDS':
                est.add('dry direct seeded')
        regs = []
        for s in a.get('suitable_by_location') or []:
            r = regions.get(s.get('region'))
            if r and r not in regs:
                regs.append(r)
        breeder = (a.get('breeding_institution') or '').strip() or (d['rows'][0][1].get('Breeding Inst.') if d['rows'] else '')
        sources = ['https://dbmp.philrice.gov.ph/seeds/content/seed_characteristics/varietychar.php?Vchars=' + i for i in d['ids'][:2]]
        rec = {
            'slug': slug, 'name': name, 'localName': local,
            'year': int(a['year_approved']),
            'breeder': re.sub(r'\s+', ' ', (BREEDER.get(breeder, breeder) or '').replace('&', 'and').replace(',', '').replace(' Inc.', '').replace(' Inc', '').replace(' Corp.', '')).strip() or None,
            'type': 'hybrid' if hybrid else 'inbred',
            'special': special,
            'traditional': breeder.startswith('Trad'),
            'environments': envs,
            'maturity': mat, 'yieldAvg': avg, 'yieldMax': top or None, 'height': height,
            'reactions': reactions, 'grain': grain,
            'seasons': season, 'establishment': sorted(est),
            'domain': (a.get('recommendation_domain') or '').strip() or None,
            'location': (a.get('recommended_location') or '').strip() or None,
            'regions': regs,
            'remarks': (a.get('remarks') or '').strip() or None,
            'preferred': bool(a.get('national_preferred')),
            'new': bool(a.get('new_variety')),
            'sources': sources,
        }
        if direct:
            rec['direct'] = direct
        out.append(rec)
    out.sort(key=lambda v: (-(v['year'] or 0), v['name']))
    json.dump(out, open(os.path.join(HERE, 'varieties.json'), 'w', encoding='utf-8'), indent=1, ensure_ascii=False)
    print(len(out), 'varieties;', sum(1 for v in out if v['maturity']), 'with trial results;', sum(1 for v in out if v['environments']),
          'with an environment;', sum(1 for v in out if v['localName']), 'with a local name;', sum(1 for v in out if v['sources']), 'with a public record')


if __name__ == '__main__':
    main()
