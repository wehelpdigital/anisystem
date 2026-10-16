"""Builds varieties.json, the rice variety catalogue behind /rice-varieties.

The facts come from PhilRice: its data file of released varieties
(varieties.json and regions.json, supplied by the owner 2026-10-13; facts
only, never pictures, words or code), and PhilRice's public seed database at
https://dbmp.philrice.gov.ph/seeds, crawled the same day, which fills what
the app leaves blank and links each variety to a public record. Only facts
are kept (names, years, numbers, ratings, places); every sentence on the
pages is anee.io's own, written by build_pages.py.

    python database/rice-varieties/build_data.py <app data folder> <crawl folder> [<trade names folder>]

The trade names folder (2026-10-13) holds tradenames*.json: the names
farmers and sellers use for a variety (Jackpot 102 for NSIC Rc 666H, SL-8H
for NSIC Rc 132H), each tied to its NSIC number by a source.

The app data folder holds varieties.json and regions.json from the app; the
crawl folder holds index_rows.json, dbmp.json and research.json (local names
and growing environments from the NSIC registry, each with its source).
"""
import glob
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
APP = sys.argv[1] if len(sys.argv) > 1 else HERE
CRAWL = sys.argv[2] if len(sys.argv) > 2 else HERE
TRADE = sys.argv[3] if len(sys.argv) > 3 else None
# A brand word alone names no one variety.
GENERIC_TRADE = {'jackpot', 'hatao', 'mestizo', 'hyvar', 'frontline', 'kimbee', 'biorice'}
# Trade names whose sources disagree on the NSIC number (2026-10-13): kept on
# the better supported number only, or left off when the registry names the
# number for another variety.
DOUBTFUL_TRADE = {('NSIC Rc 548H', 'arizebiganteplus'), ('NSIC Rc 548H', 'biganteplus'),
                  ('NSIC Rc 410H', 'arizehabilisplus'), ('NSIC Rc 410H', 'habilisplus'),
                  ('NSIC Rc 176H', 'phb73'), ('NSIC Rc 692H', 'pompyang2323'),
                  ('NSIC Rc 408H', 'sl68'), ('NSIC Rc 408H', 'sl68h')}

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


REGISTRY_URL = 'https://nsic.buplant.da.gov.ph/downloadables/nsic-registered_rice.xlsx'
# Brand names the owner corrected (2026-10-14): Rc 758H is sold as SL-22H (the
# registry's SL-222H is its line name), and Rc 696H as LP 908 (its line LP906).
TRADE_FIX = {
    'NSIC Rc 758H': (['SL-22H'], []),
    'NSIC Rc 696H': (['LP 908', 'Longping 908'], ['https://ilocos.da.gov.ph/?p=46408']),
    # Syngenta's NK line, sold under its registry name.
    'NSIC Rc 208H': (['NK 6401', 'NK6401'], [REGISTRY_URL]),
    # The owner asked (2026-10-15). LAV 777 is Rc 656H: Leads Agriventures
    # prints the number on the bag and its data sheet repeats the registry's
    # trial figures for Rc 656H (112 cm, 20.1 amylose, 75 milling), and DA
    # Ilocos lists 'NSIC Rc 656H (LAV 777)'. The registry names the line
    # LP 524 and Long Ping as its breeder, so Leads sells a Long Ping hybrid.
    'NSIC Rc 656H': (['LAV 777', 'LP 524'], ['https://www.leadsfarmex.com/downloads/lav-777.pdf', 'https://ilocos.da.gov.ph/?p=46408',
                                             'https://www.leadsfarmex.com/products']),
    # Nasiglat is Rc 750H (Mestiso 144, line LP 777): Pro Agri Seed, Long
    # Ping's distributor, sells it under that number. Not LAV 777 (Rc 656H).
    # Not Batari either (checked 2026-10-17, the owner asked): the NSIC 2023
    # catalogue gives LP 777 as its commercial name and Long Ping as breeder,
    # and no source ties Batari or Aljay to this number.
    'NSIC Rc 750H': (['Nasiglat', 'LP 777', 'Longping Nasiglat'], ['https://proagriseed.com/voucher/', 'https://proagriseed.com/about-us-2/',
                                                                 'https://nsic.buplant.da.gov.ph/downloadables/catalogue2023.pdf']),
    # Batari Indra 01 is Rc 406H (2026-10-17): IRRI's Green Super Rice news
    # and MMSU name it so, with the registry's own trial figures for Rc 406H
    # (112 days, 103 cm, 5.9 and 10.9 t/ha, 73.3 milling). The registry gives
    # Bayer as the breeder (line INH10002); Aljay (Aljay Agro-Industrial Solutions)
    # of Santiago, Isabela, sells it under its Batari brand (its seller file
    # is sellers_aljay.json in the trade names folder).
    'NSIC Rc 406H': (['Batari Indra 01', 'Batari'], ['https://gsr.irri.org/presidents-farm-feature-newly-released-and-high-yielding-rice-varieties/',
                                                     'https://www.mmsu.edu.ph/news/presidents-farm-highlights-agri-technologies',
                                                     'https://www.youtube.com/watch?v=6PtyYBclgDc']),
}


# A tie between ratings (2026-10-15) is settled by this fixed order, MS and
# MR by whichever is written first. Before, a tie fell to Python's string
# hashing, so each rebuild could flip a rating; this order gives back the
# book exactly as it was first built.
TIE_ORDER = {'HS': 0, 'S': 1, 'MS': 2, 'MR': 2, 'I': 3, 'R': 4, 'HR': 5}


def codes(v):
    """The rating a registry cell gives most often: 'PhilRice CES - I, Midsayap - I, UPLB - S' -> 'I'."""
    found = re.findall(r'\b(HR|MR|MS|HS|R|I|S)\b', v or '')
    if not found:
        return None
    top = max(found.count(x) for x in found)
    tied = [x for x in dict.fromkeys(found) if found.count(x) == top]
    return min(tied, key=lambda x: (TIE_ORDER[x], tied.index(x)))


def first_num(v):
    m = re.search(r'\d+(?:\.\d+)?', v or '')
    return float(m.group(0)) if m else None


INSTITUTES = [('International Rice Research Institute', 'IRRI'), ('Philippine Rice Research Institute', 'PhilRice'),
              ('University of the Philippines Los Baños', 'UPLB'), ('University of the Philippines-Los Baños', 'UPLB'),
              ('University of the Philippines - Los Baños', 'UPLB'), ('Central Luzon State University', 'CLSU'),
              ('Philippine Sino Agricultural Technology', 'PhilSCAT'), ('Longping High-Tech Philippine R&D Center', 'Longping High Tech'),
              ('Long Ping High Tech', 'Longping High Tech')]


def short_breeder(v):
    """'Philippine Rice Research Institute and University of the Philippines - Los Banos' -> 'PhilRice and UPLB'."""
    v = re.sub(r'\s+', ' ', (v or '').replace('�', 'ñ')).strip()
    for a, b in INSTITUTES:
        v = re.sub(re.escape(a), b, v, flags=re.I)
    v = re.sub(r'(?i),?\s*\b(Inc|Corp|Corporation|Phils)\b\.?', '', v)
    v = re.sub(r'\s+-\s+|,\s*', ' and ', v).replace('&', 'and')
    v = re.sub(r'(?<=\w)-(?=\w)', ' ', v)
    return re.sub(r'\s+', ' ', v).strip(' ,.') or None


def registry_rows(path):
    """The NSIC rice registry (a tab separated copy of nsic-registered_rice.xlsx):
    each variety's trial days, yields, height and ratings, and the name it is
    sold under when the registry writes it in brackets."""
    out = []
    if not os.path.exists(path):
        return out
    for r in (l.rstrip('\n').split('\t') for l in open(path, encoding='utf-8')):
        if len(r) < 36 or not re.search(r'\b(NSIC|PSB)\b', r[2]) or not re.match(r'^\d{4}$', r[1].strip()):
            continue
        name = display(r[2])
        vname = re.sub(r'\s+', ' ', r[3]).strip()
        m = re.match(r'^(.*?)\s*\((.*)\)\s*$', vname)
        local, trade = (m.group(1), m.group(2).strip()) if m else (vname, None)
        if trade and (re.match(r'(?i)nsic|rc\b', trade) or len(trade) > 24):
            trade = None
        if trade:
            trade = re.sub(r'\s*-\s*', '-', trade)   # DADI - 1H -> DADI-1H
        kg = lambda cells: [x for c in cells for x in (float(n) for n in re.findall(r'\d{4,5}(?:\.\d+)?', c)) if 1500 <= x <= 16000]
        tpr = kg([r[7], r[8]])
        dws = kg([r[9], r[10]])
        mt = [first_num(r[i]) for i in (11, 12) if first_num(r[i])]
        md = [first_num(r[i]) for i in (13, 14) if first_num(r[i])]
        ht = [first_num(r[i]) for i in (16, 17) if first_num(r[i])]
        am = first_num(r[39])
        rec = {
            'name': name, 'year': int(r[1]), 'local': local_title(local), 'trade': trade, 'line': r[4].strip(),
            'type': 'hybrid' if 'hybrid' in r[5].lower() or re.search(r'\d+H$', name.replace(' ', '')) else 'inbred',
            'breeder': short_breeder(r[6]),
            'maturity': round(sum(mt) / len(mt)) if mt else None,
            'yieldAvg': round(sum(tpr) / len(tpr) / 1000, 2) if tpr else (round(sum(dws) / len(dws) / 1000, 2) if dws else None),
            'yieldMax': round(max(tpr) / 1000, 2) if len(tpr) > 1 else None,
            'height': round(sum(ht) / len(ht)) if ht else None,
            'direct': {'maturity': round(sum(md) / len(md)), 'yieldAvg': round(sum(dws) / len(dws) / 1000, 2) if dws else None, 'yieldMax': None} if md and tpr else None,
            'reactions': {k: c for k, c in (('glh', codes(r[25])), ('bph', codes(r[26])), ('sb', codes(r[27]) or codes(r[30])),
                                            ('blast', codes(r[32])), ('blb', codes(r[33])), ('shb', codes(r[34])), ('tungro', codes(r[35]))) if c},
            'amylose': round(am * 100, 1) if am and am < 1 else am,
            'irrigated': 'irrigated' in (r[40] or '').lower(),
            'rainfed': 'rainfed' in (r[40] or '').lower(),
            'upland': 'upland' in (r[40] or '').lower(),
            'saline': 'saline' in (r[40] or '').lower(),
        }
        out.append(rec)
    return out


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

    # Trade names, by NSIC name; a name only when its source ties it to the number.
    trade = {}
    flat = lambda s: re.sub(r'[\s\-]', '', s.lower())
    if TRADE and os.path.isdir(TRADE):
        for f in sorted(glob.glob(os.path.join(TRADE, 'tradenames*.json'))):
            for t in json.load(open(f, encoding='utf-8')):
                if not t.get('nsic') or not t.get('tradeNames') or not t.get('sources'):
                    continue
                key = display(t['nsic'])
                e = trade.setdefault(key, {'names': [], 'sources': []})
                for nm in t['tradeNames']:
                    nm = re.sub(r'\s+', ' ', str(nm)).strip()
                    if (not nm or flat(nm) in [flat(x) for x in e['names']] or nm.lower() == key.lower() or len(nm) < 4
                            or re.match(r'(?i)^(nsic\s*)?rc\s*\d', nm) or nm.lower() in GENERIC_TRADE
                            or (key, flat(nm)) in DOUBTFUL_TRADE):
                        continue
                    e['names'].append(nm)
                e['sources'] += [u for u in t['sources'] if u not in e['sources'] and not any(b in u for b in ('pinoyrice.com',))]

    # The seller's own figures (2026-10-16, the owner: Syngenta's rice and
    # Bayer's Arize profiles, "used for AI analysis"): sellers*.json in the
    # trade names folder, each {nsic, tradeNames, company, kind, facts,
    # claims, sources}. Tied to a record by its NSIC number only; the names
    # join the trade names, the facts ride on the record as `seller`.
    sellers = {}
    if TRADE and os.path.isdir(TRADE):
        for f in sorted(glob.glob(os.path.join(TRADE, 'sellers*.json'))):
            for t in json.load(open(f, encoding='utf-8')):
                if not t.get('nsic') or not t.get('facts') or not t.get('sources'):
                    continue
                key = display(t['nsic'])
                srcs = [u for u in t['sources'] if u.startswith('http') and 'pinoyrice.com' not in u][:3]
                sellers[key] = {'company': t.get('company'), 'kind': t.get('kind'), 'names': [re.sub(r'\s+', ' ', str(n)).strip() for n in t.get('tradeNames') or []],
                                'facts': t['facts'], 'claims': bool(t.get('claims', True)), 'sources': srcs}
                e = trade.setdefault(key, {'names': [], 'sources': []})
                for nm in sellers[key]['names']:
                    if (nm and flat(nm) not in [flat(x) for x in e['names']] and nm.lower() != key.lower() and len(nm) >= 4
                            and not re.match(r'(?i)^(nsic\s*)?rc\s*\d', nm) and nm.lower() not in GENERIC_TRADE and (key, flat(nm)) not in DOUBTFUL_TRADE):
                        e['names'].insert(0, nm)
                e['sources'] = list(dict.fromkeys(e['sources'] + srcs[:1]))

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
        if name in trade:
            local_l = (local or '').lower()
            # Mestizo 30 is Mestiso 30, the local name, spelled the other way.
            sz = lambda x: re.sub(r'[\s\-]', '', x.lower().replace('z', 's'))
            rec['tradeNames'] = [t for t in trade[name]['names'] if sz(t) != sz(local_l) and not re.search(r'\d-\d', t)][:4]
            rec['tradeSources'] = trade[name]['sources'][:3]
            if not rec['tradeNames']:
                del rec['tradeNames'], rec['tradeSources']
        out.append(rec)
    # The NSIC registry (2026-10-14): the trial days, yields and heights the
    # app leaves blank for its newest varieties, and the varieties registered
    # after the app's list (2026), with the names they are sold under.
    byname = {r['name']: r for r in out}
    added = filled = 0
    for g in registry_rows(os.path.join(CRAWL, 'registry_rice.tsv')):
        r = byname.get(g['name'])
        if r is not None:
            if r['maturity'] is None and g['maturity']:
                r['maturity'], r['yieldAvg'], r['yieldMax'] = g['maturity'], g['yieldAvg'], g['yieldMax']
                if r.get('height') is None:
                    r['height'] = g['height']
                if g['direct'] and not r.get('direct'):
                    r['direct'] = g['direct']
                if REGISTRY_URL not in r['sources']:
                    r['sources'].append(REGISTRY_URL)
                filled += 1
            for k, c in g['reactions'].items():
                r['reactions'].setdefault(k, c)
            if g['trade'] and not r.get('tradeNames'):
                r['tradeNames'], r['tradeSources'] = [g['trade']], [REGISTRY_URL]
            continue
        if g['year'] < 2001 or '-' in g['name'] or g['name'] in byname:
            continue   # the old NSIC numbers repeat the PSB varieties under their PSB names
        compact = g['name'].replace(' ', '')
        envs = [e for e, on in (('irrigated', g['irrigated'] or g['type'] == 'hybrid'), ('rainfed-tpr', g['rainfed']), ('upland', g['upland']), ('saline', g['saline'])) if on]
        local = g['local'] if g['local'] and g['local'] != g['name'] else None
        if not envs and local:
            envs = [e for k, e in NAME_ENV if local.lower().startswith(k)][:1]
        am = g['amylose']
        rec = {
            'slug': slugify(g['name']), 'name': g['name'], 'localName': local, 'year': g['year'],
            'breeder': BREEDER.get(g['breeder'], g['breeder']), 'type': g['type'],
            'special': 'special' if compact.endswith('SR') else ('glutinous' if (local or '').lower().startswith('malagkit') else None),
            'traditional': False, 'environments': envs,
            'maturity': g['maturity'], 'yieldAvg': g['yieldAvg'], 'yieldMax': g['yieldMax'], 'height': g['height'],
            'reactions': g['reactions'],
            'grain': {'amylose': [am, 'VL' if am < 10 else 'L' if am < 20 else 'I' if am <= 25 else 'H']} if am else {},
            'seasons': [], 'establishment': ['transplanted', 'wet direct seeded'] if g['direct'] else [],
            'domain': None, 'location': None, 'regions': [], 'remarks': None,
            'preferred': False, 'new': True, 'sources': [REGISTRY_URL],
        }
        if g['direct']:
            rec['direct'] = g['direct']
        if g['trade']:
            rec['tradeNames'], rec['tradeSources'] = [g['trade']], [REGISTRY_URL]
        if g['name'] in trade:
            rec['tradeNames'] = list(dict.fromkeys((rec.get('tradeNames') or []) + trade[g['name']]['names']))[:4]
            rec['tradeSources'] = list(dict.fromkeys((rec.get('tradeSources') or []) + trade[g['name']]['sources']))[:3]
        out.append(rec)
        byname[g['name']] = rec
        added += 1
    for name, (names, srcs) in TRADE_FIX.items():
        if name in byname:
            byname[name]['tradeNames'] = names
            byname[name]['tradeSources'] = srcs
    for name, s in sellers.items():
        if name in byname:
            byname[name]['seller'] = {k: s[k] for k in ('company', 'kind', 'facts', 'claims', 'sources') if s.get(k) is not None}
    print(filled, 'filled from the NSIC registry,', added, 'added from it,', sum(1 for n in sellers if n in byname), 'with the seller\'s own figures',
          '(not on record: %s)' % ', '.join(n for n in sellers if n not in byname) if any(n not in byname for n in sellers) else '')
    out.sort(key=lambda v: (-(v['year'] or 0), v['name']))
    json.dump(out, open(os.path.join(HERE, 'varieties.json'), 'w', encoding='utf-8', newline='\n'), indent=1, ensure_ascii=False)
    print(sum(1 for v in out if v.get('tradeNames')), 'with a trade name;', len(out), 'varieties;', sum(1 for v in out if v['maturity']), 'with trial results;', sum(1 for v in out if v['environments']),
          'with an environment;', sum(1 for v in out if v['localName']), 'with a local name;', sum(1 for v in out if v['sources']), 'with a public record')


if __name__ == '__main__':
    main()
