"""The Nutrients guides (2026-10-30, the owner): one page per plant nutrient, macro and micro, plus the overview.
What each one does, whether it is a building block, a helper or a regulator, where it shows first, when each crop needs it
most (from the app's own stage book, database/range-effect/criticality.json, through export.php into crit.json), what
keeps it from the crop, where it comes from, and too much of it. Writes database/site-pages/nutrients/*.json.

    php database/nutrients/export.php database/nutrients/crit.json && python database/nutrients/build.py
"""
import json
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..', 'site-pages', 'nutrients')
CRIT = json.load(open(os.path.join(HERE, 'crit.json'), encoding='utf-8'))

COUNTER = {'DAT': 'DAT', 'DAS': 'DAS', 'DAP': 'DAP'}
ORDER = ['rice', 'rice_dsr_wet', 'corn_yellow', 'corn_sweet', 'leafy', 'fruiting', 'cucurbit', 'legume', 'root', 'bulb', 'sugarcane', 'banana', 'cereal']


def clean(t):
    t = t.replace('&', 'and').replace('%', ' percent')
    # "a; b" reads as two sentences on these pages.
    t = re.sub(r';\s*([a-z])', lambda m: '. ' + m.group(1).upper(), t)
    t = t.replace(';', '.')
    return t.strip()


def days(table, st):
    c = COUNTER[CRIT[table]['counter']]
    a, b = st['from'], st['to']
    if b <= a:
        return '%s %d onward' % (c, a)
    return '%s %d to %d' % (c, a, b)


def stage_list(table, stages):
    return ', '.join('%s (%s)' % (clean(s['label']), days(table, s)) for s in stages)


def crit_rows(el):
    rows = [['Crop', 'Most critical', 'Also needed', 'Why it matters then']]
    for t in ORDER:
        book = CRIT[t]
        cr = book.get('crit') or {}
        st = book['stages']
        by = [(s, (cr.get(s['label'], {}).get('nutrients', {}) or {}).get(el)) for s in st]
        by = [(s, v) for s, v in by if v]
        if not by:
            continue
        top = [s for s, v in by if v[0] >= 3]
        mid = [s for s, v in by if v[0] == 2]
        why_src = next((v for s, v in by if v[0] >= 3), None) or next((v for s, v in by if v[0] == 2), None) or max((v for s, v in by), key=lambda v: v[0])
        rows.append([book['say'], stage_list(t, top) if top else 'No stage stands out', stage_list(t, mid) if mid else 'Small amounts the rest of the season', clean(why_src[1])])
    return rows


def late_notes(el):
    seen, out = set(), []
    for t in ORDER:
        n = (CRIT[t].get('late') or {}).get(el)
        if n and clean(n) not in seen:
            seen.add(clean(n))
            out.append('%s: %s' % (CRIT[t]['say'], clean(n)))
    return out


# The crops of the timing tables, a table a group.
GROUPS = [('Rice, corn and other grains', [CRIT[t]['say'] for t in ('rice', 'rice_dsr_wet', 'corn_yellow', 'corn_sweet', 'cereal')]),
          ('Vegetables and legumes', [CRIT[t]['say'] for t in ('leafy', 'fruiting', 'cucurbit', 'legume', 'bulb')]),
          ('Root crops, sugarcane and banana', [CRIT[t]['say'] for t in ('root', 'sugarcane', 'banana')])]


def page(n):
    fk = n['fk']
    blocks = []
    H = lambda t, l=2: blocks.append({'type': 'heading', 'level': l, 'text': t})
    T = lambda t: blocks.append({'type': 'text', 'text': t})
    H(n['h_role'])
    T('\n\n'.join(n['role']))
    H(n['h_kind'])
    T('\n\n'.join(n['kind']))
    blocks.append({'type': 'table', 'caption': '%s in short' % n['name'], 'rows': [
        ['Fact', n['name']], ['Group', n['group']], ['Kind of nutrient', n['kindWord']], ['Moves inside the plant', n['moves']], ['Where a shortage shows first', n['first']], ['Taken up as', n['form']],
    ]})
    H(n['h_mobile'])
    T('\n\n'.join(n['mobile']))
    if n.get('crit', True):
        H(n['h_crit'])
        T('\n\n'.join(n['crit_intro'][:-1]) + '\n\nDAT means days after transplanting, DAS days after sowing in the field, and DAP days after planting. The days are the usual ones for a typical variety, so a long or short variety moves them a little. Timing %s to these windows gets more harvest from the same bags. Our guide to [fertilizer timing by growth stage](/blog/fertilizer-timing-by-growth-stage) puts them together for a whole season.' % n['fk'] + '\n\n' + n['crit_intro'][-1])
        rows = crit_rows(n['key'])
        head, body = rows[0], rows[1:]
        for title, keys in GROUPS:
            mine = [r for r in body if r[0] in keys]
            if mine:
                H(title, 3)
                blocks.append({'type': 'table', 'caption': 'When %s is critical: %s' % (n['lower'], title.lower()), 'rows': [head] + mine})
        late = late_notes(n['key'])
        if late:
            H('When %s comes too late' % n['lower'], 3)
            blocks.append({'type': 'list', 'ordered': False, 'items': late})
        if n.get('crit_after'):
            T('\n\n'.join(n['crit_after']))
    else:
        H(n['h_crit'])
        T('\n\n'.join(n['timing']))
    H(n['h_block'])
    T('\n\n'.join(n['block']))
    H(n['h_source'])
    T('\n\n'.join(n['source']))
    if n.get('source_list'):
        blocks.append({'type': 'list', 'ordered': False, 'items': n['source_list']})
    blocks.append({'type': 'callout', 'tone': 'warn', 'title': n['much_t'], 'text': n['much']})
    blocks.append({'type': 'cta', 'title': n['cta_t'], 'text': n['cta'], 'label': 'Start free', 'url': '/signup'})
    H('%s questions' % n['name'])
    blocks.append({'type': 'faq', 'items': [{'q': q, 'a': a} for q, a in n['faq']]})
    blocks.append({'type': 'links', 'title': 'Related pages', 'items': [{'label': l, 'url': u} for l, u in n['links']]})
    return {
        'section': 'nutrients', 'slug': n['slug'], 'lang': 'en', 'category': n['category'], 'sortOrder': n['sort'],
        'title': n['title'], 'metaTitle': n['metaTitle'], 'metaDescription': n['meta'], 'focusKeyword': fk, 'keywords': n['keywords'],
        'excerpt': n['excerpt'], 'heroImage': n['hero'], 'blocks': blocks,
    }


def hero(src, alt, credit=''):
    return {'src': src, 'alt': alt, 'credit': credit}


CTA_PLAN = 'Put your fertilizers on your crop\'s calendar in anee.io and the [Coverage Planner](/features/residual-effect-calendar) shows, day by day, whether %s reaches the stages that need it, and where it runs out. Free to start.'

N = []

# ------------------------------------------------------------------ nitrogen
N.append(dict(
    key='N', name='Nitrogen', lower='nitrogen', slug='nitrogen-in-plants', fk='nitrogen in plants', sort=110, category='Primary nutrients',
    title='Nitrogen in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Nitrogen in Plants: Role, Signs and When to Apply',
    meta='Nitrogen in plants builds every leaf and protein. See what it does, when palay, mais and gulay need it most, and how to keep it from going to waste.',
    keywords=['role of nitrogen in plants', 'function of nitrogen in plants', 'when to apply nitrogen', 'nitrogen fertilizer'],
    excerpt='Nitrogen in plants is the nutrient of green growth. It is built into chlorophyll, the green color that turns sunlight into food, and into every protein and enzyme, so a crop short of it stays small and pale. Palay, mais and leafy vegetables take more of it than any other nutrient. Here is what it does, when each crop needs it most, and how to keep it from going to waste.',
    hero=hero('/images/site/deficiencies/general-n.webp', 'Chili plants with pale yellow green older leaves: nitrogen in plants running short', 'Maja Dumat, CC BY 2.0, via Flickr'),
    h_role='What nitrogen does in plants',
    role=['Every leaf a plant makes is built partly from nitrogen. It sits at the heart of the amino acids that make up proteins, and proteins run the whole plant: they build new cells, drive growth and form the enzymes that carry out the chemistry of life. Nitrogen is also part of chlorophyll, so the deep green of a well fed field is nitrogen you can see.',
          'That is why nitrogen sets the size of the plant and the size of the harvest. In palay it decides how many tillers form and how many of them carry a panicle. In mais it sets the length of the ear and the number of kernels. For pechay and cabbage it is most of what you sell, because the harvest is the leaves themselves.'],
    h_kind='A building block, used up as the crop grows',
    kind=['Nitrogen is a building block, not a helper. A helper nutrient such as zinc switches on an enzyme and works again and again in tiny amounts. Nitrogen instead becomes part of the plant body, in every protein and every leaf, so the crop uses it up as it grows and the harvest carries it out of the field.',
          'A crop of palay takes away roughly 15 to 20 kilograms of nitrogen for every ton of grain, counting the straw. So the soil alone rarely holds enough for a full harvest, and nitrogen has to come back every season, from fertilizer, manure, compost or a legume grown before.'],
    group='Primary macronutrient', kindWord='Building block', moves='Yes, freely', first='The old, lower leaves', form='Nitrate and ammonium',
    h_mobile='Where nitrogen shows first',
    mobile=['Nitrogen moves freely inside the plant. When the roots cannot find enough, the plant pulls it out of its oldest leaves and sends it to the young ones. So the lower leaves yellow first, often from the tip back, while the top stays green for a while.',
            'That is the sign to watch for: yellowing that climbs from the bottom up, on a plant that also stays short and thin. See [nitrogen deficiency](/nutrient-deficiencies/nitrogen-deficiency) for how it looks in each crop, and what else can look like it.'],
    h_crit='When nitrogen in plants is most critical',
    crit_intro=['Nitrogen is needed the whole season, but a few stages decide the yield. In palay the tillers are counted early and the grains per panicle are set at panicle initiation, so those are the windows to feed. In mais the plant takes most of its nitrogen in the weeks of fast growth before tasselling.',
                'Here is when it matters most for each crop, from the same stage book the anee.io calendar reads:'],
    h_block='What keeps nitrogen from the crop',
    block=['Nitrogen is easy to lose. Urea left on the surface of wet soil turns to ammonia gas within days, and in a flooded paddy part of it escapes as gas when the water stands too long. Heavy rain and typhoons also wash nitrate below the roots, most of all on sandy soil.',
           'So split the dose instead of giving it all at once: a little at planting, more when the crop starts its fastest growth, and the rest before the stage that sets the harvest. Work urea into the soil, or broadcast it into shallow water and keep the water in, so more of it stays for the crop. Soils low in organic matter hold little nitrogen of their own, so they need the split doses most.'],
    h_source='Where nitrogen comes from',
    source=['Most farms here buy nitrogen as urea, the cheapest nitrogen per kilogram. The other common fertilizers add a second nutrient with it, so pick by what the soil also lacks. See our guide to [urea fertilizer](/blog/urea-fertilizer) for rates and timing, and the [rice fertilizer schedule](/blog/rice-fertilizer-schedule) for palay.'],
    source_list=['Urea (46 0 0): the most nitrogen per bag, for topdressing.', 'Ammonium sulfate (21 0 0): nitrogen with sulfur, good on alkaline soil.', 'Ammonium phosphate (16 20 0) and complete fertilizer (14 14 14): nitrogen with phosphorus at planting.', 'Chicken manure, compost and a legume grown before: slower nitrogen that also feeds the soil.'],
    much_t='Too much nitrogen is a problem too',
    much='Too much nitrogen makes soft, dark green leaves that blast, sheath blight and planthoppers love, and tall weak stems that lodge in the first strong wind. In palay it also delays heading. Give what the crop needs at each stage, not more.',
    cta_t='See if your nitrogen lasts to the stages that need it',
    cta=CTA_PLAN % 'nitrogen',
    faq=[('Is nitrogen a macronutrient or a micronutrient?', 'Nitrogen is a primary macronutrient, along with phosphorus and potassium. Crops take it in tens of kilograms per hectare, far more than any micronutrient.'),
         ('When should I apply nitrogen to palay?', 'Split it. Give a small part as basal, the biggest share at active tillering, and the last dose at panicle initiation, about 35 to 45 days after transplanting for most varieties. Nitrogen after flowering is barely used.'),
         ('Why do the lower leaves turn yellow first?', 'Because nitrogen moves inside the plant. A hungry plant takes it from its old leaves to feed the new ones, so the oldest leaves yellow while the top stays green.'),
         ('Can I give nitrogen through the leaves?', 'Yes, a weak urea spray can green up vegetables quickly. But leaves take up only small amounts at a time, so most of the season\'s nitrogen still has to go to the soil.')],
    links=[('Nitrogen deficiency', '/nutrient-deficiencies/nitrogen-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Phosphorus in plants', '/nutrients/phosphorus-in-plants'),
           ('Potassium in plants', '/nutrients/potassium-in-plants'), ('Fertilizer for rice tillering', '/blog/fertilizer-for-rice-tillering'), ('Urea fertilizer', '/blog/urea-fertilizer')],
))

# ------------------------------------------------------------------ phosphorus
N.append(dict(
    key='P', name='Phosphorus', lower='phosphorus', slug='phosphorus-in-plants', fk='phosphorus in plants', sort=120, category='Primary nutrients',
    title='Phosphorus in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Phosphorus in Plants: Role, Signs and When to Apply',
    meta='Phosphorus in plants carries energy, builds roots and sets the grain. See when palay, mais and gulay need it most, and why it must go in early.',
    keywords=['role of phosphorus in plants', 'function of phosphorus', 'phosphorus fertilizer', 'when to apply phosphorus'],
    excerpt='Phosphorus in plants is the energy nutrient. It carries the energy every cell runs on, builds new roots, and later moves into the seed and the grain. A crop needs it most when it is young, which is why phosphorus goes in at planting and not as a late topdress. Here is what it does, when each crop needs it, and why it often sits in the soil out of reach.',
    hero=hero('/images/site/deficiencies/general-p.webp', 'Older leaves turning purple red: phosphorus in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What phosphorus does in plants',
    role=['Phosphorus is part of ATP, the molecule that carries energy from where the leaves make it to where the plant spends it. Every time a cell divides, a root grows or a sugar moves, phosphorus is in the middle of it. It is also part of DNA and of the membranes around every cell.',
          'So phosphorus decides how fast a young crop gets going. A seedling with enough of it roots deep and fast, tillers early and flowers on time. Later, phosphorus moves into the seed and the grain, which is why a crop short of it fills poorly and matures late.'],
    h_kind='A building block and an energy carrier',
    kind=['Phosphorus is a building block. It becomes part of the plant body, in its genes, its cell walls and its seeds, and the harvest takes it away. A ton of palay grain carries off roughly 2 to 3 kilograms of phosphorus, which is much less than nitrogen, but it has to be replaced all the same.',
          'Unlike nitrogen, phosphorus does not wash away easily. The trouble is the reverse: it binds so tightly to the soil that the crop reaches only a small part of what is there. That is why where and when you place it matters as much as how much.'],
    group='Primary macronutrient', kindWord='Building block and energy carrier', moves='Yes', first='The old, lower leaves, often purple', form='Phosphate',
    h_mobile='Where phosphorus shows first',
    mobile=['Phosphorus moves inside the plant, so a shortage also starts on the older leaves. But it seldom yellows them the way nitrogen does. Instead the plant stays small and stiff, the leaves turn dark green, and in corn, tomato and many young plants the older leaves turn purple or reddish.',
            'In palay the sign is fewer tillers and late, uneven maturity rather than a color. See [phosphorus deficiency](/nutrient-deficiencies/phosphorus-deficiency) for the signs crop by crop.'],
    h_crit='When phosphorus in plants is most critical',
    crit_intro=['Phosphorus matters most at the start. Young roots need it the day they form, and a crop that runs short in its first weeks rarely catches up, even if phosphorus comes later. So it belongs in the soil at planting or transplanting, worked in near the roots.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps phosphorus from the crop',
    block=['Most soils hold far more phosphorus than any crop needs, but little of it is in a form the roots can take. On acid soil it locks up with iron and aluminum. On alkaline and limy soil it locks up with calcium. It is most available when the soil pH sits near 6 to 7.',
           'Cold, waterlogged or compacted soil also slows phosphorus uptake, because roots grow little there. Organic matter helps, and so does placing the fertilizer close to the roots instead of spreading it thinly over the surface. In a flooded paddy, phosphorus becomes easier to take up as the soil softens, which is one reason rice suffers less from it than upland crops.'],
    h_source='Where phosphorus comes from',
    source=['Phosphorus comes in the fertilizers you use at planting. Ammonium phosphate is the usual choice for palay and mais, with complete fertilizer when the soil also needs potassium. See our guides to [16 20 0 fertilizer](/blog/16-20-0-fertilizer) and [complete fertilizer](/blog/complete-fertilizer-14-14-14).'],
    source_list=['Ammonium phosphate (16 20 0): the common basal phosphorus for palay.', 'Complete fertilizer (14 14 14): phosphorus with nitrogen and potassium.', 'Diammonium phosphate (18 46 0): the most phosphorus per bag.', 'Chicken manure and compost: slow phosphorus that also builds the soil.'],
    much_t='Too much phosphorus locks up zinc',
    much='Phosphorus piled on season after season builds up in the soil and makes zinc and iron harder for the crop to take. If a soil test already shows plenty, put the money into the nutrient that is short instead.',
    cta_t='Check that phosphorus is in before the roots need it',
    cta=CTA_PLAN % 'phosphorus',
    faq=[('Should phosphorus go in at planting or later?', 'At planting. Young roots need it first, and phosphorus moves very little in the soil, so it has to be close to the roots from the start. A late phosphorus topdress does little for the yield.'),
         ('Why are my corn leaves purple?', 'Purple older leaves on young corn often mean a phosphorus shortage, most of all on cold, wet or acid soil. Some hybrids turn purple for a few days on their own, so check whether the plants are also small and slow.'),
         ('Is phosphorus a macronutrient?', 'Yes. Phosphorus is a primary macronutrient, with nitrogen and potassium, though crops take less of it than of the other two.'),
         ('Does liming help phosphorus?', 'On acid soil, yes. Raising the pH toward 6 frees phosphorus that was locked with iron and aluminum. On alkaline soil lime makes it worse.')],
    links=[('Phosphorus deficiency', '/nutrient-deficiencies/phosphorus-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'),
           ('Zinc in plants', '/nutrients/zinc-in-plants'), ('16 20 0 fertilizer', '/blog/16-20-0-fertilizer'), ('Balanced fertilization for rice', '/blog/balanced-fertilization-for-rice')],
))

# ------------------------------------------------------------------ potassium
N.append(dict(
    key='K', name='Potassium', lower='potassium', slug='potassium-in-plants', fk='potassium in plants', sort=130, category='Primary nutrients',
    title='Potassium in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Potassium in Plants: Role, Signs and When to Apply',
    meta='Potassium in plants steers water, sugar and strong stems. See when palay, mais and gulay need it most, and why it is a regulator, not a building block.',
    keywords=['role of potassium in plants', 'function of potassium', 'potash fertilizer', 'when to apply potassium'],
    excerpt='Potassium in plants is the regulator. It is not built into the plant body at all: it stays dissolved in the sap, where it opens and closes the leaf pores, moves sugar into the grain and keeps the stems firm. A crop short of it lodges, fills poorly and falls to disease. Here is what it does, when each crop needs it most and where it comes from.',
    hero=hero('/images/site/deficiencies/general-k.webp', 'Leaves with brown, scorched edges: potassium in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What potassium does in plants',
    role=['Potassium opens and closes the stomata, the tiny pores on the leaves that let in air and let out water. So it decides how well a crop uses its water and how it stands a dry spell. It also switches on dozens of enzymes, and it pushes the sugar the leaves make down into the grain, the fruit and the root.',
          'You see potassium most in the strength of a crop. It thickens cell walls and stems, so a well fed palay field stands up to wind and rain. It also helps the plant hold out against blast, sheath blight and other diseases, and it gives fruit their size, sweetness and color.'],
    h_kind='A regulator, not a building block',
    kind=['Potassium is a regulator. Unlike nitrogen and phosphorus it never becomes part of a protein, a gene or a wall. It stays free in the sap as a charged particle, steering water and switching enzymes on, and it can wash back out of old leaves in the rain.',
          'Even so, crops take a lot of it, often as much as nitrogen. Most of it stays in the straw and the stalks, so returning the straw to the field gives much of it back. Burning the straw or taking it away for feed takes that potassium out of the farm.'],
    group='Primary macronutrient', kindWord='Regulator', moves='Yes, freely', first='The old leaves, at the edges and tips', form='Potassium ion',
    h_mobile='Where potassium shows first',
    mobile=['Potassium moves freely, so the shortage starts on the older leaves. Their tips and edges turn yellow, then brown and dry, as if scorched, while the middle of the leaf stays green. In palay you often see it first as rusty brown spots on the lower leaves.',
            'A crop short of potassium also lodges easily and catches more disease. See [potassium deficiency](/nutrient-deficiencies/potassium-deficiency) for the signs in each crop.'],
    h_crit='When potassium in plants is most critical',
    crit_intro=['Potassium matters most when the crop builds its stem and then fills its grain or fruit. In palay that is panicle initiation through heading, when the stem has to carry the weight of the panicle and the sugar has to move into the grain. In fruit crops it is the weeks of fruit fill.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps potassium from the crop',
    block=['Sandy soil holds little potassium and loses it in heavy rain. Soils rich in clay can hold it tightly between their layers, and a soil heavy in calcium and magnesium, such as many limy or sodic soils, crowds potassium out at the roots. Too much ammonium or magnesium fertilizer also competes with it.',
           'Removing the straw takes potassium off the farm every season. So the fields that get the full harvest of straw carried away need the most potassium back, and the fields where straw is returned need less.'],
    h_source='Where potassium comes from',
    source=['Most potassium here is sold as muriate of potash, the cheapest potassium per kilogram. Sulfate of potash costs more but suits crops that dislike chloride, like tobacco and some vegetables. See our guide to [potash fertilizer](/blog/potash-fertilizer).'],
    source_list=['Muriate of potash (0 0 60): the usual potassium for palay and mais.', 'Sulfate of potash (0 0 50): potassium with sulfur, for chloride sensitive crops.', 'Complete fertilizer (14 14 14): potassium along with nitrogen and phosphorus.', 'Rice straw and wood ash: potassium returned to the field.'],
    much_t='Too much potassium crowds out magnesium and calcium',
    much='Heavy potassium can push magnesium and calcium out at the roots and bring on their shortage, most of all on sandy soil. Balance it with what the soil test says.',
    cta_t='Make sure potassium is there when the panicle forms',
    cta=CTA_PLAN % 'potassium',
    faq=[('Is potassium the same as potash?', 'Potash is the fertilizer, potassium is the nutrient in it. Muriate of potash (0 0 60) is 60 percent potassium oxide.'),
         ('When should I apply potassium to palay?', 'Part of it as basal, and a second dose at panicle initiation, about 35 to 45 days after transplanting. Potassium after grain filling starts adds little.'),
         ('Does potassium stop lodging?', 'It helps a lot. Potassium thickens the stem walls, so the crop stands better in wind and rain. Too much nitrogen still makes a crop lodge, even with enough potassium.'),
         ('Why is potassium called a regulator?', 'Because it is not built into the plant. It stays dissolved in the sap and controls water, enzymes and the movement of sugar.')],
    links=[('Potassium deficiency', '/nutrient-deficiencies/potassium-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'),
           ('Magnesium in plants', '/nutrients/magnesium-in-plants'), ('Potash fertilizer', '/blog/potash-fertilizer'), ('Fertilizer for rice grain filling', '/blog/fertilizer-for-rice-grain-filling')],
))

# ------------------------------------------------------------------ calcium
N.append(dict(
    key='Ca', name='Calcium', lower='calcium', slug='calcium-in-plants', fk='calcium in plants', sort=210, category='Secondary nutrients',
    title='Calcium in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Calcium in Plants: Role, Signs and When It Matters',
    meta='Calcium in plants builds the walls of every cell. See why it never moves to new growth, when fruit and vegetables need it most, and how to supply it.',
    keywords=['role of calcium in plants', 'calcium fertilizer', 'blossom end rot', 'calcium for tomatoes'],
    excerpt='Calcium in plants is the cement of the cell walls. It holds new cells together and keeps fruit firm, but it cannot move from old leaves to new ones, so the young parts suffer first. Blossom end rot on tomato and tip burn on cabbage are calcium problems. Here is what it does, when each crop needs it most and how to supply it.',
    hero=hero('/images/site/deficiencies/general-ca.webp', 'Tomatoes with dark sunken rot at the blossom end: calcium in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What calcium does in plants',
    role=['Calcium binds the walls of neighboring cells together, much like the cement between hollow blocks. It also keeps the outer membrane of every cell whole, and it carries signals inside the cell when the plant meets heat, drought or a pest.',
          'So calcium matters most wherever new tissue forms fast: root tips, young leaves, the growing point and every fruit. A tomato, a pepper or a melon short of calcium as it swells gets a rotten, sunken spot at its blossom end, and a cabbage head gets brown, burnt inner leaves.'],
    h_kind='A building block that stays where it lands',
    kind=['Calcium is a building block. Once it is laid into a cell wall it stays there for good. The plant cannot pull it back out to feed a new leaf, the way it does with nitrogen or potassium.',
          'Calcium also travels only with the water the plant draws up. So anything that slows the flow of water, like dry soil, very humid air or a fruit that loses little water, keeps calcium from reaching the parts that need it, even in a soil full of it.'],
    group='Secondary macronutrient', kindWord='Building block', moves='Hardly at all', first='The young leaves, growing tips and fruit', form='Calcium ion',
    h_mobile='Where calcium shows first',
    mobile=['Because calcium does not move inside the plant, a shortage starts on the newest growth. The young leaves come out hooked, wrinkled or burnt at the tips, the growing point can die, and fruit rot at the blossom end. The old leaves stay normal.',
            'Most calcium problems in the field are about water and timing, not an empty soil. See [calcium deficiency](/nutrient-deficiencies/calcium-deficiency) for how it shows in each crop.'],
    h_crit='When calcium in plants is most critical',
    crit_intro=['Calcium matters most while a crop forms its fruit or its head, and in peanut while the pods fill underground. Rice and corn need little of it and most soils give enough.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    crit_after=['Keep the soil evenly moist through fruit set, because a dry spell followed by heavy watering brings blossom end rot even when the soil holds plenty of calcium.'],
    h_block='What keeps calcium from the crop',
    block=['Strongly acid soil, sandy soil and heavily leached upland soil can run low on calcium. More often, though, the soil has enough and the plant cannot move it: dry soil, uneven watering, roots damaged by too much fertilizer, or heavy ammonium and potassium that crowd calcium out at the roots.',
           'On sodic soil the trouble is the reverse: too much sodium pushes calcium off the soil and the ground turns hard and sticky. Gypsum puts the calcium back and lets the sodium wash away.'],
    h_source='Where calcium comes from',
    source=['Lime gives calcium and raises the pH of acid soil at the same time. Gypsum gives calcium without changing the pH, so it suits sodic and neutral soil. For fruit crops, calcium nitrate on the soil or as a spray reaches the fruit fastest. See [fertilizer for tomatoes](/blog/fertilizer-for-tomatoes) for a fruit crop schedule.'],
    source_list=['Agricultural lime and dolomite: calcium for acid soil, dolomite with magnesium too.', 'Gypsum (calcium sulfate): calcium and sulfur without raising the pH.', 'Calcium nitrate: quick calcium with nitrogen, on the soil or the leaves.'],
    much_t='Too much lime locks up other nutrients',
    much='Liming past a pH of about 7 makes iron, manganese, zinc, boron and phosphorus harder to take up. Lime to the pH the crop needs, guided by a soil test.',
    cta_t='Time your calcium to the fruit set',
    cta=CTA_PLAN % 'calcium',
    faq=[('What causes blossom end rot?', 'A shortage of calcium in the fruit while it swells. It usually comes from uneven watering or dry soil rather than a soil without calcium.'),
         ('Is calcium a macronutrient or a micronutrient?', 'Calcium is a secondary macronutrient, with magnesium and sulfur. Crops need it in kilograms, but much less than nitrogen or potassium.'),
         ('Does palay need calcium fertilizer?', 'Rarely. Most rice soils give enough calcium. Gypsum helps on sodic paddies, where it is the sodium that needs fixing.'),
         ('Can calcium be sprayed on the leaves?', 'Yes, calcium nitrate or calcium chloride sprays help fruit and leafy crops, but calcium sprayed on the leaves does not move to the fruit, so spray the fruit itself.')],
    links=[('Calcium deficiency', '/nutrient-deficiencies/calcium-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Magnesium in plants', '/nutrients/magnesium-in-plants'),
           ('Boron in plants', '/nutrients/boron-in-plants'), ('Fertilizer for tomatoes', '/blog/fertilizer-for-tomatoes'), ('Fertilizer for vegetables', '/blog/fertilizer-for-vegetables-philippines')],
))

# ------------------------------------------------------------------ magnesium
N.append(dict(
    key='Mg', name='Magnesium', lower='magnesium', slug='magnesium-in-plants', fk='magnesium in plants', sort=220, category='Secondary nutrients',
    title='Magnesium in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Magnesium in Plants: Role, Signs and When It Matters',
    meta='Magnesium in plants sits at the center of chlorophyll. See what it does, when palay, mais and fruit crops need it most, and what crowds it out.',
    keywords=['role of magnesium in plants', 'magnesium fertilizer', 'magnesium sulfate for plants', 'dolomite'],
    excerpt='Magnesium in plants sits at the very center of chlorophyll, the green that turns sunlight into food, and it switches on the enzymes that move energy around the plant. A crop short of it turns yellow between the veins of its older leaves. Here is what it does, when each crop needs it most, and why too much potassium can cause its shortage.',
    hero=hero('/images/site/deficiencies/general-mg.webp', 'Tomato leaf yellow between green veins: magnesium in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What magnesium does in plants',
    role=['Every molecule of chlorophyll has one atom of magnesium at its center. Without it the leaf cannot catch light, so a plant short of magnesium makes less food however much nitrogen it gets.',
          'Magnesium also switches on the enzymes that handle ATP, the energy carrier, and it helps load sugar into the phloem so it can travel to the grain, the fruit and the roots. That is why a shortage shows late in the season as poor filling, after the crop has looked fine for weeks.'],
    h_kind='Both a building block and a helper',
    kind=['Magnesium works two ways. As part of chlorophyll it is a building block, held in the leaf. As the partner of ATP and many enzymes it is a helper, doing its job without being used up.',
          'Crops take less magnesium than calcium, often 10 to 30 kilograms per hectare, but on sandy and acid soils that can be more than the soil gives.'],
    group='Secondary macronutrient', kindWord='Building block and helper', moves='Yes', first='The old leaves, between the veins', form='Magnesium ion',
    h_mobile='Where magnesium shows first',
    mobile=['Magnesium moves inside the plant, so the shortage starts on the older leaves. The tissue between the veins turns yellow while the veins stay green, giving a striped or marbled look, and later the yellow parts can turn reddish or die.',
            'It is easy to confuse with potassium, which scorches the leaf edges instead, or with manganese, which starts on the young leaves. See [magnesium deficiency](/nutrient-deficiencies/magnesium-deficiency) to tell them apart.'],
    h_crit='When magnesium in plants is most critical',
    crit_intro=['Magnesium matters most when the crop is filling: grain in palay and mais, fruit in tomato and melon, bunches in banana. Before that the leaves need it to keep making food at full speed.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps magnesium from the crop',
    block=['Sandy and strongly acid soils hold little magnesium and lose it in heavy rain. On other soils the trouble is competition: heavy potassium or ammonium fertilizer, or a lot of calcium from lime, crowds magnesium out at the roots.',
           'So a field that gets plenty of muriate of potash season after season, without magnesium, can start to show the yellow stripes. Dolomite instead of plain lime fixes both the acidity and the magnesium.'],
    h_source='Where magnesium comes from',
    source=['Dolomite gives magnesium along with calcium and is the cheapest choice for acid soil. Magnesium sulfate, sold as Epsom salt or kieserite, dissolves quickly and works on the soil or as a leaf spray. Many complete fertilizers and foliar feeds also carry some. See [micronutrients for rice](/blog/micronutrients-for-rice) for the trace nutrients that often go with it.'],
    source_list=['Dolomite: magnesium and calcium, raises the pH of acid soil.', 'Magnesium sulfate (Epsom salt, kieserite): fast magnesium with sulfur, on the soil or the leaves.', 'Compost and manure: small amounts released slowly.'],
    much_t='Too much magnesium hurts the soil',
    much='Very high magnesium can make clay soil tight and slow to drain, and it crowds out potassium and calcium. Add it when a soil test or the leaves say it is short.',
    cta_t='Keep magnesium up through the filling weeks',
    cta=CTA_PLAN % 'magnesium',
    faq=[('Is Epsom salt good for plants?', 'Epsom salt is magnesium sulfate, so it helps where magnesium or sulfur is short. On a soil with enough magnesium it does nothing for the crop.'),
         ('What does magnesium deficiency look like?', 'The older leaves turn yellow between green veins, often in stripes on palay and mais. The young leaves stay green at first.'),
         ('Can too much potassium cause magnesium deficiency?', 'Yes. Heavy potassium crowds magnesium out at the roots, most of all on sandy soil.'),
         ('Is magnesium a micronutrient?', 'No. Magnesium is a secondary macronutrient, along with calcium and sulfur.')],
    links=[('Magnesium deficiency', '/nutrient-deficiencies/magnesium-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Potassium in plants', '/nutrients/potassium-in-plants'),
           ('Calcium in plants', '/nutrients/calcium-in-plants'), ('Micronutrients for rice', '/blog/micronutrients-for-rice'), ('Foliar fertilizer', '/blog/foliar-fertilizer')],
))

# ------------------------------------------------------------------ sulfur
N.append(dict(
    key='S', name='Sulfur', lower='sulfur', slug='sulfur-in-plants', fk='sulfur in plants', sort=230, category='Secondary nutrients',
    title='Sulfur in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Sulfur in Plants: Role, Signs and When It Matters',
    meta='Sulfur in plants builds proteins and oils and helps the crop use nitrogen. See when palay, mais, onion and legumes need it most, and where it comes from.',
    keywords=['role of sulfur in plants', 'sulfur fertilizer', 'ammonium sulfate', 'sulfur for onion'],
    excerpt='Sulfur in plants is a building block of proteins, and the partner nitrogen cannot work without. It also gives onion and garlic their bite and oil crops their oil. A crop short of it turns pale from the top down, which is easy to mistake for a lack of nitrogen. Here is what it does, when each crop needs it and where it comes from.',
    hero=hero('/images/site/deficiencies/general-s.webp', 'Young plant pale yellow green all over, new leaves included: sulfur in plants running short', 'R.J. Reynolds Tobacco Company'),
    h_role='What sulfur does in plants',
    role=['Two of the amino acids that make up proteins carry sulfur. So without it the plant cannot turn the nitrogen it takes up into protein, and a field fed plenty of urea still stays pale. Sulfur is also needed to make chlorophyll and several vitamins.',
          'In onion, garlic and cabbage, sulfur makes the compounds that give their smell and taste. In legumes it feeds the root nodules that fix nitrogen from the air, and in oil crops like peanut and soybean it raises the oil in the seed.'],
    h_kind='A building block that works with nitrogen',
    kind=['Sulfur is a building block. It is built into proteins and stays in the plant body, so the harvest takes it away. Crops need roughly one part of sulfur for every ten to fifteen parts of nitrogen.',
          'That partnership is the thing to remember. When you raise the nitrogen, a soil low in sulfur runs short faster, and the extra nitrogen goes to waste.'],
    group='Secondary macronutrient', kindWord='Building block', moves='Slowly', first='The young leaves, often the whole plant pale', form='Sulfate',
    h_mobile='Where sulfur shows first',
    mobile=['Sulfur moves only slowly inside the plant, so the shortage tends to start on the young leaves or on the whole plant at once. That is the way to tell it from nitrogen: nitrogen yellows the old leaves first, sulfur the new ones.',
            'See [sulfur deficiency](/nutrient-deficiencies/sulfur-deficiency) for how it looks crop by crop.'],
    h_crit='When sulfur in plants is most critical',
    crit_intro=['Sulfur is needed whenever the crop builds protein fast: tillering and panicle initiation in palay, early growth in mais, bulbing in onion, and pod filling in legumes.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps sulfur from the crop',
    block=['Sulfur comes mostly from organic matter in the soil, so sandy soils and soils low in organic matter run short first. Sulfate also washes down with heavy rain like nitrate does.',
           'Farms that use only urea and muriate of potash give the crop no sulfur at all. Burning straw sends the sulfur in it up in smoke. In long flooded paddies, sulfur can also turn into a form the roots cannot take until the field drains.'],
    h_source='Where sulfur comes from',
    source=['The easiest sulfur is ammonium sulfate, which gives it with nitrogen. Gypsum and potassium sulfate also carry it. See our guide to [ammonium sulfate](/blog/ammonium-sulfate-21-0-0).'],
    source_list=['Ammonium sulfate (21 0 0): sulfur with nitrogen, slightly acidifying.', 'Gypsum: sulfur with calcium.', 'Sulfate of potash (0 0 50): sulfur with potassium.', 'Elemental sulfur: slow sulfur that also lowers the pH of alkaline soil.'],
    much_t='Too much sulfur can sour the soil',
    much='Large amounts of elemental sulfur or ammonium sulfate make the soil more acid over time. That helps an alkaline field but hurts an acid one, so watch the pH.',
    cta_t='Pair your sulfur with your nitrogen',
    cta=CTA_PLAN % 'sulfur',
    faq=[('Is sulfur the same as sulfate?', 'Sulfate is the form of sulfur that plant roots take up. Elemental sulfur has to be turned into sulfate by soil microbes first, which takes weeks.'),
         ('Why is my crop pale even after urea?', 'It may be short of sulfur, which nitrogen needs to make protein. Look at the young leaves: if they are pale too, sulfur is the likely cause.'),
         ('Is sulfur a macronutrient?', 'Yes. Sulfur is a secondary macronutrient, with calcium and magnesium.'),
         ('Which crops need the most sulfur?', 'Onion, garlic, cabbage and other crucifers, legumes and oil crops need the most. Palay and mais need it too, mostly in their fast growth.')],
    links=[('Sulfur deficiency', '/nutrient-deficiencies/sulfur-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'),
           ('Calcium in plants', '/nutrients/calcium-in-plants'), ('Ammonium sulfate', '/blog/ammonium-sulfate-21-0-0'), ('Fertilizer for wet season rice', '/blog/fertilizer-for-wet-season-rice')],
))

# ------------------------------------------------------------------ zinc
N.append(dict(
    key='Zn', name='Zinc', lower='zinc', slug='zinc-in-plants', fk='zinc in plants', sort=310, category='Micronutrients',
    title='Zinc in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Zinc in Plants: Role, Signs and When It Matters',
    meta='Zinc in plants is a helper that switches on growth. See why flooded palay runs short of it early, when each crop needs it most, and how to supply it.',
    keywords=['role of zinc in plants', 'zinc sulfate for rice', 'zinc fertilizer', 'khaira disease'],
    excerpt='Zinc in plants is a helper: a tiny amount switches on the enzymes and hormones that make leaves grow and stems lengthen. Flooded palay runs short of it more often than of any other trace nutrient, usually two to four weeks after transplanting. Here is what it does, when each crop needs it most, and how a little zinc at the right time saves a season.',
    hero=hero('/images/site/deficiencies/general-zn.webp', 'Leaves with yellow blotches between green veins: zinc in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What zinc does in plants',
    role=['Zinc is part of or switches on more than a hundred enzymes. One of its jobs is helping the plant make auxin, the hormone that stretches young stems and leaves. So a plant short of zinc stays stunted, with short stems and small, crowded leaves.',
          'Zinc also helps build proteins and chlorophyll, keeps cell membranes whole, and is needed for pollen to form. In palay it decides how fast a young crop recovers after transplanting and how many tillers it makes.'],
    h_kind='A helper, needed in grams',
    kind=['Zinc is a helper, not a building block. It works like the key to an engine: it switches enzymes on and goes on working without being used up. That is why a crop needs only a few hundred grams of zinc per hectare, while it needs nitrogen by the bag.',
          'But a helper that is missing stops the whole engine. A field short of zinc cannot use its nitrogen, phosphorus or potassium well, so every peso of fertilizer earns less.'],
    group='Micronutrient', kindWord='Helper', moves='Poorly', first='The young and middle leaves', form='Zinc ion',
    h_mobile='Where zinc shows first',
    mobile=['Zinc moves poorly inside the plant, so the shortage shows on the young and middle leaves. In palay the midrib of the young leaves turns pale, then dusty brown spots and streaks spread on the older leaves, and the plants stay short and uneven. In mais a broad white or yellow band runs down each side of the midrib.',
            'See [zinc deficiency](/nutrient-deficiencies/zinc-deficiency) for the signs in each crop.'],
    h_crit='When zinc in plants is most critical',
    crit_intro=['Zinc matters most at the very start. In flooded palay the shortage shows two to four weeks after transplanting, when the young roots work hardest and the flooded soil holds zinc tightest. In mais and vegetables it is the first weeks of growth.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps zinc from the crop',
    block=['Most zinc problems come from the soil holding it, not from a soil without it. Alkaline, limy and sodic soils lock zinc up, and so does a paddy kept flooded for a long time. Heavy phosphorus makes it worse.',
           'Soils low in organic matter, sandy soils and fields leveled by cutting away the topsoil also run short. Cold weather and young roots slow uptake further, which is why the shortage is often worst early and fades as the crop grows.'],
    h_source='Where zinc comes from',
    source=['Zinc sulfate is the usual choice, on the soil before or at planting, or dissolved and sprayed when the signs show. Some complete and foliar fertilizers also carry zinc. See [micronutrients for rice](/blog/micronutrients-for-rice) and [nutrient deficiency in rice](/blog/nutrient-deficiency-in-rice).'],
    source_list=['Zinc sulfate (heptahydrate or monohydrate): on the soil or as a foliar spray.', 'Zinc chelate: a liquid that works well on alkaline soil.', 'Zinc oxide: slower, as a coating or in blends.', 'Dipping rice seedling roots in zinc mixed with water before transplanting.'],
    much_t='A little zinc goes a long way',
    much='Zinc is needed in grams, not kilograms, and too much harms the crop and the soil. Follow the label rate, and repeat only when the signs come back.',
    cta_t='Get zinc in before the young crop needs it',
    cta=CTA_PLAN % 'zinc',
    faq=[('When should I apply zinc to palay?', 'Before or at transplanting on soils known to be short, or as a spray as soon as the signs show, usually two to four weeks after transplanting. Zinc after flowering only raises the zinc in the grain.'),
         ('Why does flooded rice lack zinc?', 'Long flooding changes the soil so that zinc binds tightly and the roots cannot take it, most of all on alkaline or limy soil.'),
         ('Is zinc a micronutrient?', 'Yes. Crops need it in grams per hectare, but without it they cannot use the other nutrients well.'),
         ('Can I mix zinc sulfate with other fertilizers?', 'Zinc sulfate mixes with urea and potash, but keep it apart from phosphorus fertilizers in the same hole or band, because they bind each other.')],
    links=[('Zinc deficiency', '/nutrient-deficiencies/zinc-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Phosphorus in plants', '/nutrients/phosphorus-in-plants'),
           ('Iron in plants', '/nutrients/iron-in-plants'), ('Micronutrients for rice', '/blog/micronutrients-for-rice'), ('Nutrient deficiency in rice', '/blog/nutrient-deficiency-in-rice')],
))

# ------------------------------------------------------------------ boron
N.append(dict(
    key='B', name='Boron', lower='boron', slug='boron-in-plants', fk='boron in plants', sort=320, category='Micronutrients',
    title='Boron in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Boron in Plants: Role, Signs and When It Matters',
    meta='Boron in plants builds cell walls and makes pollen work. See why fruit and seed crops need it at flowering, and why too much is close to enough.',
    keywords=['role of boron in plants', 'boron fertilizer', 'boron for flowering', 'borax for plants'],
    excerpt='Boron in plants helps build cell walls and makes pollen grow, so flowers turn into fruit and seed. It does not move to new growth, so a shortage hits the growing tips and the flowers first. Fruit crops, root crops and coconut need it most. Here is what it does, when each crop needs it, and why the gap between enough and too much is small.',
    hero=hero('/images/site/deficiencies/general-b.webp', 'Coconut frond with leaflets stuck together and crumpled: boron in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What boron does in plants',
    role=['Boron ties together the pieces of the cell wall, so new cells form strong and whole. It also helps sugar move through the plant and keeps the growing tips dividing.',
          'Its biggest job comes at flowering. Pollen needs boron to grow its tube down to the ovule, so a crop short of boron flowers but sets little fruit or seed. That is why boron shows up as hollow stems in broccoli, cracked roots in carrot, poorly filled ears in mais and button nuts in coconut.'],
    h_kind='A building block in tiny amounts',
    kind=['Boron is a building block of the cell wall, though it is needed only in grams per hectare. Once laid down it stays where it is, much like calcium, so the plant cannot move it to new leaves or flowers.',
          'That makes boron a nutrient of steady supply. The crop needs a little all the time, and above all at flowering, rather than a big dose once.'],
    group='Micronutrient', kindWord='Building block, in small amounts', moves='Hardly at all', first='The growing tips, young leaves and flowers', form='Boric acid',
    h_mobile='Where boron shows first',
    mobile=['Because boron hardly moves, the shortage starts at the growing points. Tips die back, young leaves come out thick, brittle or crumpled, flowers drop and fruit crack or grow hollow.',
            'See [boron deficiency](/nutrient-deficiencies/boron-deficiency) for the signs in each crop.'],
    h_crit='When boron in plants is most critical',
    crit_intro=['Boron matters most from flowering to fruit set, and in root crops and cabbage while the root or head forms. Palay needs very little.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps boron from the crop',
    block=['Boron washes out of sandy soil and soil low in organic matter in heavy rain. Liming acid soil past neutral also makes it harder to take up, and a dry spell stops its flow into the plant.',
           'So the risk is highest on light, limed or upland soils in a wet season followed by a dry spell at flowering.'],
    h_source='Where boron comes from',
    source=['Borax and solubor are the usual sources, on the soil or as a weak spray before flowering. Many foliar fertilizers carry a little boron. See [foliar fertilizer](/blog/foliar-fertilizer) and [fertilizer for vegetables](/blog/fertilizer-for-vegetables-philippines).'],
    source_list=['Borax: on the soil, mixed evenly with other fertilizer.', 'Solubor (sodium borate): dissolves fast, for leaf sprays.', 'Compost: small amounts that last.'],
    much_t='The line between enough and too much is thin',
    much='Boron burns leaf edges and kills young plants at only a few times the right rate. Spread it evenly, never pile it, and follow the label to the gram.',
    cta_t='Have boron in place before the flowers open',
    cta=CTA_PLAN % 'boron',
    faq=[('When should I apply boron?', 'Before flowering, so it is there when the pollen grows. On fruit crops a light spray at the start of flowering is common.'),
         ('What does boron deficiency look like?', 'Dead or crumpled growing tips, brittle young leaves, flowers that drop, and fruit or roots that crack or grow hollow.'),
         ('Is borax safe for plants?', 'In very small amounts it supplies boron. A little too much harms the crop, so measure carefully and spread it evenly.'),
         ('Does palay need boron?', 'Very little. Most rice soils give enough, and too much boron is the more common trouble in rice.')],
    links=[('Boron deficiency', '/nutrient-deficiencies/boron-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Calcium in plants', '/nutrients/calcium-in-plants'),
           ('Zinc in plants', '/nutrients/zinc-in-plants'), ('Foliar fertilizer', '/blog/foliar-fertilizer'), ('Fertilizer for fruit trees', '/blog/fertilizer-for-fruit-trees')],
))

# ------------------------------------------------------------------ iron
N.append(dict(
    key='Fe', name='Iron', lower='iron', slug='iron-in-plants', fk='iron in plants', sort=330, category='Micronutrients',
    title='Iron in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Iron in Plants: Role, Signs and When It Matters',
    meta='Iron in plants helps make chlorophyll and carry energy. See why upland crops on limy soil run short, why flooded rice can get too much, and how to fix both.',
    keywords=['role of iron in plants', 'iron chlorosis', 'iron toxicity in rice', 'iron fertilizer'],
    excerpt='Iron in plants is a helper the leaf cannot make chlorophyll without. A crop short of it turns pale yellow on its newest leaves while the veins stay green. Upland crops on limy or alkaline soil run short most often, while flooded rice is more likely to get too much. Here is what it does, when it matters for each crop and how to handle both troubles.',
    hero=hero('/images/site/deficiencies/general-fe.webp', 'Twig with young leaves yellow between dark green veins: iron in plants running short', 'Scot Nelson, CC0 1.0, via Flickr'),
    h_role='What iron does in plants',
    role=['Iron is not part of chlorophyll itself, but the plant cannot make chlorophyll without it. It also sits in the proteins that pass energy along inside the leaf and the root, so photosynthesis and breathing both depend on it.',
          'A plant short of iron makes pale new leaves that cannot feed it well, so growth slows. In legumes iron is also needed by the nodules that fix nitrogen from the air.'],
    h_kind='A helper that carries energy',
    kind=['Iron is a helper. It does its work inside enzymes and energy carriers, passing electrons along without being used up, so crops need only a few hundred grams per hectare.',
          'Most soils hold plenty of iron. The trouble is almost always its form: in alkaline, limy and well aired soil it turns into rust like forms the roots cannot take.'],
    group='Micronutrient', kindWord='Helper', moves='Hardly at all', first='The youngest leaves, yellow between green veins', form='Ferrous iron or iron chelate',
    h_mobile='Where iron shows first',
    mobile=['Iron hardly moves inside the plant, so the shortage shows on the newest leaves. They turn pale yellow between the veins while the veins stay green, and in a bad case the whole young leaf turns almost white. The old leaves stay green.',
            'See [iron deficiency](/nutrient-deficiencies/iron-deficiency) for the signs crop by crop.'],
    h_crit='When iron in plants is most critical',
    crit_intro=['Iron matters most for young upland crops on alkaline or limy soil, and in direct seeded rice on dry, aerobic soil. Flooded rice seldom lacks it.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    crit_after=['In flooded palay the opposite trouble is common: on acid, poorly drained soil the water frees so much iron that the leaves turn orange or bronze. Draining the field for a few days and giving potassium helps.'],
    h_block='What keeps iron from the crop',
    block=['A soil pH above about 7.5, free lime, heavy liming, too much phosphorus or bicarbonate in irrigation water all lock iron up. Waterlogged upland soil and cold roots slow uptake further.',
           'So the field to watch is an upland one on limy ground, irrigated with hard water, growing a crop like mungbean, peanut or sorghum.'],
    h_source='Where iron comes from',
    source=['Iron put on alkaline soil as plain iron sulfate turns unavailable within days, so iron chelate or a leaf spray of iron sulfate works better there. Lowering the pH with sulfur or ammonium sulfate helps over time. See [foliar fertilizer](/blog/foliar-fertilizer) for spray timing.'],
    source_list=['Iron chelate: stays available on alkaline soil, on the soil or the leaves.', 'Iron sulfate: a cheap leaf spray, repeated every one to two weeks.', 'Compost and manure: keep iron in forms the roots can take.'],
    much_t='Too much iron poisons flooded rice',
    much='On acid, poorly drained paddies the water can free so much iron that it poisons the rice. Drain the field from time to time, keep potassium up and avoid piling on fresh organic matter right before flooding.',
    cta_t='See when iron matters on your soil',
    cta=CTA_PLAN % 'iron',
    faq=[('What does iron deficiency look like?', 'The newest leaves turn pale yellow between green veins, and in a bad case almost white, while the older leaves stay green.'),
         ('Why do plants lack iron on alkaline soil?', 'At a high pH iron turns into forms the roots cannot take, even though the soil holds plenty.'),
         ('What is iron toxicity in rice?', 'On acid, waterlogged paddies the soil frees too much iron. The leaves get small brown spots that spread into an orange or bronze color.'),
         ('Is iron a micronutrient?', 'Yes. Crops need it in small amounts, but nothing else can do its job.')],
    links=[('Iron deficiency', '/nutrient-deficiencies/iron-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Manganese in plants', '/nutrients/manganese-in-plants'),
           ('Zinc in plants', '/nutrients/zinc-in-plants'), ('Foliar fertilizer', '/blog/foliar-fertilizer'), ('Fertilizer for sandy soil', '/blog/fertilizer-for-sandy-soil')],
))

# ------------------------------------------------------------------ manganese
N.append(dict(
    key='Mn', name='Manganese', lower='manganese', slug='manganese-in-plants', fk='manganese in plants', sort=340, category='Micronutrients',
    title='Manganese in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Manganese in Plants: Role, Signs and When It Matters',
    meta='Manganese in plants splits water in sunlight and switches on enzymes. See when each crop needs it, why limed soils run short, and how to supply it.',
    keywords=['role of manganese in plants', 'manganese fertilizer', 'manganese sulfate', 'manganese deficiency in plants'],
    excerpt='Manganese in plants is the helper that splits water inside the leaf, the first step of turning sunlight into food. It also switches on enzymes that build defenses against disease. A crop short of it gets speckled, pale young leaves. Here is what it does, when each crop needs it most and why heavily limed soils often run short.',
    hero=hero('/images/site/deficiencies/general-mn.webp', 'Leaf with pale, speckled areas between the veins: manganese in plants running short', 'R.J. Reynolds Tobacco Company'),
    h_role='What manganese does in plants',
    role=['In the leaf, manganese sits in the part of the chloroplast that splits water in sunlight and releases oxygen. Without it photosynthesis stalls at the very first step.',
          'Manganese also switches on enzymes that make lignin, the tough stuff in cell walls, and other compounds that help the plant fight fungi and other diseases. So a crop short of it grows weak and catches more disease.'],
    h_kind='A helper, needed in small amounts',
    kind=['Manganese is a helper. It works inside enzymes and the water splitting step without being used up, so crops need only a few hundred grams per hectare.',
          'Like iron, it is usually in the soil but in the wrong form. Oxygen and a high pH turn it into forms the roots cannot take, while flooding and acid soil free it, sometimes too much.'],
    group='Micronutrient', kindWord='Helper', moves='Poorly', first='The young leaves, speckled between the veins', form='Manganese ion',
    h_mobile='Where manganese shows first',
    mobile=['Manganese moves poorly, so the shortage starts on the young and middle leaves. They turn pale between the veins with small dead specks, and the plant may look grayish. It looks a lot like iron shortage, but the speckles and the paler veins give manganese away.',
            'See [manganese deficiency](/nutrient-deficiencies/manganese-deficiency) for the signs crop by crop.'],
    h_crit='When manganese in plants is most critical',
    crit_intro=['Manganese matters most for upland crops on limy or heavily limed soil, early in the season. Flooded palay seldom lacks it because flooding frees manganese.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps manganese from the crop',
    block=['A pH above about 6.5, heavy liming, dry and well aired soil, and soil rich in organic matter all bind manganese. On the other hand, strongly acid and waterlogged soil can free so much that it harms the crop.',
           'So the risk of shortage is highest on a limed, sandy or limy upland field in a dry spell.'],
    h_source='Where manganese comes from',
    source=['Manganese sulfate on the soil or as a leaf spray is the usual fix. A spray works faster on limy soil, where soil applied manganese locks up quickly. Many trace element blends also carry it. See [micronutrients for rice](/blog/micronutrients-for-rice).'],
    source_list=['Manganese sulfate: on the soil or as a leaf spray.', 'Manganese chelate: for alkaline soil.', 'Trace element blends and foliar fertilizers: small amounts with zinc, iron and copper.'],
    much_t='Too much manganese is toxic on acid soil',
    much='On very acid or waterlogged soil, manganese can rise to toxic levels and show as brown spots on older leaves. Liming acid soil to the right pH fixes it.',
    cta_t='See when manganese matters on your soil',
    cta=CTA_PLAN % 'manganese',
    faq=[('How do I tell manganese from iron deficiency?', 'Both pale the young leaves between the veins. Manganese adds small dead specks and the veins look paler, while iron leaves the veins sharp green.'),
         ('Does lime cause manganese deficiency?', 'Too much lime can. Raising the pH past about 6.5 makes manganese harder for the roots to take.'),
         ('Is manganese a micronutrient?', 'Yes. Crops need it in small amounts as a helper for photosynthesis and disease defense.'),
         ('Can manganese be sprayed?', 'Yes, a manganese sulfate spray is the quickest fix, most of all on limy soil.')],
    links=[('Manganese deficiency', '/nutrient-deficiencies/manganese-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Iron in plants', '/nutrients/iron-in-plants'),
           ('Copper in plants', '/nutrients/copper-in-plants'), ('Micronutrients for rice', '/blog/micronutrients-for-rice'), ('Foliar fertilizer', '/blog/foliar-fertilizer')],
))

# ------------------------------------------------------------------ copper
N.append(dict(
    key='Cu', name='Copper', lower='copper', slug='copper-in-plants', fk='copper in plants', sort=350, category='Micronutrients',
    title='Copper in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Copper in Plants: Role, Signs and When It Matters',
    meta='Copper in plants helps make pollen and strong walls. See which crops and soils run short, when it matters, and why copper sprays already supply it.',
    keywords=['role of copper in plants', 'copper fertilizer', 'copper sulfate for plants', 'copper deficiency in plants'],
    excerpt='Copper in plants is a helper needed in very small amounts, for photosynthesis, for strong cell walls and above all for pollen. A crop short of it has twisted, wilting young leaves and sets poor grain. Few mineral soils lack it, but peat and very sandy soils can. Here is what it does, when it matters and how to supply it without overdoing it.',
    hero=hero('/images/site/deficiencies/general-cu.webp', 'Young shoot with drooping, cupped, twisted new leaves: copper in plants running short', 'Florida Division of Plant Industry'),
    h_role='What copper does in plants',
    role=['Copper works in enzymes that pass energy along in photosynthesis and breathing, and in others that build lignin for strong stems and cell walls.',
          'It matters most at flowering, because pollen needs copper to form and stay fertile. So a cereal short of copper may flower but leave many empty grains, and its heads may stay pale or bent.'],
    h_kind='A helper, needed in grams',
    kind=['Copper is a helper. It works inside enzymes and is needed in only tens of grams per hectare. Most of the time the soil has enough, and fungicide sprays based on copper add more.',
          'The trouble spots are soils that hold copper too tightly, like peat and soils very rich in organic matter, and very sandy soils that have little to begin with.'],
    group='Micronutrient', kindWord='Helper', moves='Poorly', first='The young leaves and growing tips', form='Copper ion',
    h_mobile='Where copper shows first',
    mobile=['Copper moves poorly inside the plant, so the shortage starts on the young growth. New leaves come out pale, twisted or rolled, the tips die back, and stems stay weak and floppy.',
            'See [copper deficiency](/nutrient-deficiencies/copper-deficiency) for the signs crop by crop.'],
    h_crit='When copper in plants is most critical',
    crit_intro=['Copper matters most from booting to flowering in cereals, when the pollen forms. It is rarely short on the mineral soils most farms here have.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps copper from the crop',
    block=['Peat and soils very rich in organic matter bind copper tightly. Very sandy soil may simply have little. A high pH and heavy nitrogen or phosphorus also make it harder to take up.',
           'On most mineral soils, though, copper shortage is rare, and the copper fungicides many farms spray add enough.'],
    h_source='Where copper comes from',
    source=['Copper sulfate on the soil or as a weak spray fixes a shortage. Copper fungicides like copper hydroxide and copper oxychloride also supply it while they protect against disease. See [micronutrients for rice](/blog/micronutrients-for-rice) for the other trace nutrients.'],
    source_list=['Copper sulfate: on the soil or as a weak leaf spray.', 'Copper fungicides: copper hydroxide and copper oxychloride, which feed and protect.', 'Trace element blends: small amounts with zinc and manganese.'],
    much_t='Copper builds up in the soil',
    much='Copper does not wash away, so years of copper sprays can build it up to harmful levels in the soil, most of all in orchards and vegetable plots. Spray copper when disease calls for it, not by habit.',
    cta_t='Know when copper matters in your crop',
    cta=CTA_PLAN % 'copper',
    faq=[('Do copper fungicides also feed the plant?', 'Yes. Copper hydroxide and copper oxychloride sprays supply copper as they protect against disease.'),
         ('Which soils lack copper?', 'Peat, soils very rich in organic matter and very sandy soils. Most mineral farm soils have enough.'),
         ('Is copper a micronutrient?', 'Yes. Crops need only tens of grams per hectare.'),
         ('Can copper harm the crop?', 'Too much can. It builds up in soil that gets copper sprays for many years and stunts the roots.')],
    links=[('Copper deficiency', '/nutrient-deficiencies/copper-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Zinc in plants', '/nutrients/zinc-in-plants'),
           ('Manganese in plants', '/nutrients/manganese-in-plants'), ('Micronutrients for rice', '/blog/micronutrients-for-rice'), ('Fertilizer for fruit trees', '/blog/fertilizer-for-fruit-trees')],
))

# ------------------------------------------------------------------ molybdenum
N.append(dict(
    key='Mo', name='Molybdenum', lower='molybdenum', slug='molybdenum-in-plants', fk='molybdenum in plants', sort=360, category='Micronutrients',
    title='Molybdenum in Plants: What It Does and When Your Crop Needs It',
    metaTitle='Molybdenum in Plants: Role, Signs and When It Matters',
    meta='Molybdenum in plants turns nitrate into protein and feeds legume nodules. See which crops need it, why acid soils run short, and how little it takes.',
    keywords=['role of molybdenum in plants', 'molybdenum fertilizer', 'molybdenum for legumes', 'sodium molybdate'],
    excerpt='Molybdenum in plants is the smallest need of all, often less than a hundred grams per hectare, yet without it a crop cannot use the nitrate it takes up, and a legume cannot fix nitrogen. Acid soils run short most often. Here is what it does, which crops need it most, and how little it takes to fix.',
    hero=hero('/images/site/deficiencies/general-mo.webp', 'Young corn plant on acid soil with pale, dying older leaves: molybdenum in plants running short', 'Alandmanson, CC BY SA 4.0, via Wikimedia Commons'),
    h_role='What molybdenum does in plants',
    role=['Molybdenum is part of the enzyme that turns nitrate, the form most nitrogen reaches the roots in, into a form the plant can build protein with. A plant short of it piles up nitrate and still looks hungry for nitrogen.',
          'In legumes like mungbean, peanut, sitaw and soybean, molybdenum is part of the enzyme in the root nodules that fixes nitrogen from the air. So a legume short of molybdenum stays pale and yields little, however well its nodules form.'],
    h_kind='A helper, the smallest need of all',
    kind=['Molybdenum is a helper. It sits inside two or three enzymes and works without being used up, so a crop needs less of it than of any other nutrient.',
          'That tiny need is why a seed treatment or a single light spray is usually enough for a whole season.'],
    group='Micronutrient', kindWord='Helper', moves='Fairly well', first='The old and middle leaves, pale like nitrogen', form='Molybdate',
    h_mobile='Where molybdenum shows first',
    mobile=['Molybdenum moves fairly well inside the plant, and because it works through nitrogen, its shortage looks like nitrogen shortage: pale older leaves, slow growth and in legumes, poor nodules. Cabbage and cauliflower get narrow, ragged leaves called whiptail.',
            'See [molybdenum deficiency](/nutrient-deficiencies/molybdenum-deficiency) for the signs crop by crop.'],
    h_crit='When molybdenum in plants is most critical',
    crit_intro=['Molybdenum matters most for legumes while their nodules form and work, and for cabbage, cauliflower and other crucifers in their early growth. Palay needs almost none.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps molybdenum from the crop',
    block=['Unlike most trace nutrients, molybdenum becomes harder to take up as the soil turns more acid. So strongly acid soils run short most often, and liming them often fixes the shortage by itself.',
           'Heavy sulfate fertilizer can also compete with it at the roots.'],
    h_source='Where molybdenum comes from',
    source=['Sodium or ammonium molybdate, used as a seed coating for legumes or a weak spray, supplies enough for the season. Liming acid soil frees what is already there. See [fertilizer for vegetables](/blog/fertilizer-for-vegetables-philippines) for the crops that need it most.'],
    source_list=['Sodium molybdate or ammonium molybdate: as a seed coating or a weak spray.', 'Lime on acid soil: frees the molybdenum the soil holds.'],
    much_t='A few grams is plenty',
    much='Molybdenum is needed in grams per hectare. Much more builds up in forage and can harm cattle that eat it, so stay at the label rate.',
    cta_t='Plan molybdenum for your legumes and crucifers',
    cta=CTA_PLAN % 'molybdenum',
    faq=[('Which crops need molybdenum?', 'Legumes, which need it to fix nitrogen, and cabbage, cauliflower and other crucifers. Palay and mais need almost none.'),
         ('Why does acid soil lack molybdenum?', 'In acid soil molybdenum binds tightly and the roots cannot take it. Liming frees it.'),
         ('What is whiptail?', 'A molybdenum shortage in cauliflower and cabbage that makes the leaves narrow and ragged.'),
         ('How much molybdenum does a crop need?', 'Very little, often less than a hundred grams per hectare, so a seed coating or one light spray is usually enough.')],
    links=[('Molybdenum deficiency', '/nutrient-deficiencies/molybdenum-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'),
           ('Sulfur in plants', '/nutrients/sulfur-in-plants'), ('Fertilizer for vegetables', '/blog/fertilizer-for-vegetables-philippines'), ('Organic fertilizer examples', '/blog/organic-fertilizer-examples')],
))

# ------------------------------------------------------------------ chlorine
N.append(dict(
    key='Cl', name='Chlorine', lower='chlorine', slug='chlorine-in-plants', fk='chlorine in plants', sort=370, category='Micronutrients', crit=False,
    title='Chlorine in Plants: What It Does and Which Crops Need It',
    metaTitle='Chlorine in Plants: Role, Coconut and Salt Sensitive Crops',
    meta='Chlorine in plants opens and closes the leaf pores and helps split water. See why coconut needs it, which crops dislike it, and where it comes from.',
    keywords=['role of chlorine in plants', 'chloride for coconut', 'salt for coconut', 'chloride sensitive crops'],
    excerpt='Chlorine in plants works as chloride, a helper that keeps water balanced in the cells and the leaf pores working. Most crops get all they need from rain, irrigation water and muriate of potash, and some crops dislike too much of it. Coconut is the exception: it needs more chloride than most crops, which is why coconut farmers here spread salt. Here is what it does and which crops need it.',
    hero=hero('/images/site/photos/inspect.jpg', 'A farmer inspecting a crop for signs of chlorine in plants running short or too high'),
    h_role='What chlorine does in plants',
    role=['Plants take chlorine as chloride, a charged particle dissolved in the sap. Together with potassium it pulls water into cells and keeps them firm, and it opens and closes the stomata, the leaf pores that let in air and let out water.',
          'So chlorine in plants works hand in hand with potassium. Chloride also takes part in the step of photosynthesis that splits water in sunlight, and it helps some crops resist leaf and root diseases.'],
    h_kind='A helper and a regulator',
    kind=['Chlorine is a helper and a regulator. It is never built into the plant body. It stays dissolved, balancing charges and steering water, much like potassium does.',
          'Crops need only a small amount, but they often take far more than they need because chloride is everywhere: in rain near the sea, in irrigation water and in muriate of potash.'],
    group='Micronutrient', kindWord='Helper and regulator', moves='Yes, freely', first='The young leaves wilting, then bronzing', form='Chloride',
    h_mobile='Where chlorine shows first',
    mobile=['A true shortage is rare. When it happens the leaves wilt in the afternoon, the young ones turn pale and bronzed, and the roots stay short and thick.',
            'Too much chloride is more common, on salty soil or with salty water. Leaf tips and edges burn brown, much like a [potassium shortage](/nutrient-deficiencies/potassium-deficiency) looks.'],
    h_crit='Which crops need chlorine most',
    timing=['Coconut needs more chloride than most crops. It helps the palm open its leaf pores and hold water, and coconuts given chloride carry more nuts with heavier meat. That is why many coconut farms here spread common salt or muriate of potash around the palm, and why palms near the sea often bear well.',
            'Oil palm and kiwi also respond to chloride. For palay and mais, the chloride in muriate of potash and in water is more than enough.',
            'Some crops dislike chloride. Tobacco burns poorly and potato and sweet potato store worse with too much, and citrus, avocado and many vegetables and beans burn at the leaf edges when chloride piles up. For these, sulfate of potash is the safer potassium. See [potassium in plants](/nutrients/potassium-in-plants) for the potash choices.'],
    h_block='What keeps chlorine from the crop',
    block=['Very little keeps chloride from a crop. It moves freely in water and washes out of soil easily, so a shortage shows only far from the sea, on sandy soil, after heavy rain, with no muriate of potash and no salt in the water.',
           'The real worry is the opposite: salty soil and salty water in coastal and dry areas push chloride too high, and the crop burns.'],
    h_source='Where chlorine comes from',
    source=['Muriate of potash, the common potassium fertilizer, is almost half chloride, so it supplies all most crops need. Rain near the coast and irrigation water add more. For coconut, common salt spread around the palm is the cheap source. See [potash fertilizer](/blog/potash-fertilizer) and [fertilizer for fruit trees](/blog/fertilizer-for-fruit-trees).'],
    source_list=['Muriate of potash (0 0 60): potassium and chloride together.', 'Common salt: for coconut only, spread in a ring around the palm.', 'Irrigation water and rain near the sea.'],
    much_t='Too much chloride burns leaves',
    much='On salty ground or with salty water, chloride builds up and burns leaf tips and edges. Never use salt on crops other than coconut, and use sulfate of potash for chloride sensitive crops.',
    cta_t='Plan your potassium and chloride by crop',
    cta=CTA_PLAN % 'potassium, the nutrient that brings chloride with it,',
    faq=[('Why do farmers put salt on coconut?', 'Coconut needs more chloride than most crops. Salt supplies it cheaply, and palms given chloride carry more and heavier nuts.'),
         ('Is chlorine a nutrient?', 'Yes. Plants take it as chloride and need a small amount as a helper and regulator.'),
         ('Which crops dislike chloride?', 'Tobacco, potato, sweet potato, citrus, avocado and many beans and vegetables. Use sulfate of potash for them.'),
         ('Does muriate of potash give chloride?', 'Yes. It is almost half chloride, enough for most crops.')],
    links=[('Plant nutrients', '/nutrients/plant-nutrients'), ('Potassium in plants', '/nutrients/potassium-in-plants'), ('Potassium deficiency', '/nutrient-deficiencies/potassium-deficiency'),
           ('Potash fertilizer', '/blog/potash-fertilizer'), ('Fertilizer for fruit trees', '/blog/fertilizer-for-fruit-trees')],
))

# ------------------------------------------------------------------ nickel
N.append(dict(
    key='Ni', name='Nickel', lower='nickel', slug='nickel-in-plants', fk='nickel in plants', sort=380, category='Micronutrients', crit=False,
    title='Nickel in Plants: What It Does and Why It Is Rarely Short',
    metaTitle='Nickel in Plants: Role, Urea and Legumes',
    meta='Nickel in plants helps them use urea and feeds legume nodules. See what it does, why it is almost never short on farm soils, and when it matters.',
    keywords=['role of nickel in plants', 'nickel and urea', 'nickel for legumes', 'nickel micronutrient'],
    excerpt='Nickel in plants is the newest nutrient on the list and the smallest need after molybdenum. It is part of urease, the enzyme that breaks down urea inside the plant, and it helps legumes fix nitrogen. Farm soils almost always hold enough, so a true shortage is rare. Here is what it does and when it matters.',
    hero=hero('/images/site/photos/palay-heads.jpg', 'Ripe palay heads in a field where nickel in plants is seldom short'),
    h_role='What nickel does in plants',
    role=['Nickel is the center of urease, the enzyme that breaks urea into ammonia the plant can build with. Urea forms inside every plant as it handles nitrogen, and a plant short of nickel lets it pile up until it burns the leaf tips.',
          'In legumes like mungbean, peanut and soybean, nickel also helps the nodules that fix nitrogen and the seed that sprouts from them.'],
    h_kind='A helper, needed in traces',
    kind=['Nickel is a helper. It works inside urease and one or two other enzymes, and a crop needs only a trace of it, about a gram per hectare or less.',
          'Because the need is so small, nearly every farm soil holds enough, and the seed carries enough to start the crop.'],
    group='Micronutrient', kindWord='Helper', moves='Fairly well', first='The leaf tips, dead from urea build up', form='Nickel ion',
    h_mobile='Where nickel shows first',
    mobile=['A nickel shortage shows as dead, burnt leaf tips where urea built up, and in some trees as small, rounded, crumpled young leaves. These signs are rare on farm soils.',
            'If leaf tips burn after a urea spray, the spray rate is a far more likely cause than nickel. See [nitrogen deficiency](/nutrient-deficiencies/nitrogen-deficiency) and our guide to [urea fertilizer](/blog/urea-fertilizer).'],
    h_crit='When nickel in plants matters',
    timing=['Nickel matters most for legumes and for crops fed mostly with urea sprays, because both depend on urease. Seed from a field low in nickel can also sprout poorly, since the young seedling lives on the nickel its seed carried until its roots take over.',
            'So the time to think about nickel is before planting a legume on a very sandy or heavily limed field, or when a crop fed with urea through the leaves keeps burning at the tips at a normal rate. Even then, check the spray rate and the weather first, because a hot midday spray burns leaves far more often than a lack of nickel does.',
            'For palay, mais and most vegetables on ordinary farm soil, there is no stage when nickel needs attention.'],
    h_block='What keeps nickel from the crop',
    block=['A very high soil pH, heavy liming and large amounts of zinc, copper or iron can make nickel harder to take up. Very sandy, heavily leached soil may hold little.',
           'Even then a shortage is rare, because the crop needs so little.'],
    h_source='Where nickel comes from',
    source=['Farm soil, manure and the seed itself usually give enough. Where a shortage is proven, a weak nickel sulfate spray fixes it, but almost no farm here needs one.',
            'Spend the money on the nutrients that run short more often instead: [nitrogen](/nutrients/nitrogen-in-plants), [zinc](/nutrients/zinc-in-plants) and [potassium](/nutrients/potassium-in-plants).'],
    much_t='Too much nickel is toxic',
    much='Soils on certain rocks and land near mines can hold enough nickel to stunt crops. Never add nickel unless a soil or leaf test shows a shortage.',
    cta_t='Know which nutrients your field really needs',
    cta='Put your fertilizers on your crop\'s calendar in anee.io and the [Coverage Planner](/features/residual-effect-calendar) shows, day by day, which nutrients reach the stages that need them, and where they run out. Free to start.',
    faq=[('Is nickel a plant nutrient?', 'Yes. It is the newest essential micronutrient, needed in traces for the enzyme that breaks down urea.'),
         ('Do I need nickel fertilizer?', 'Almost never. Farm soils, manure and seed give enough for nearly every crop.'),
         ('Why does nickel matter for urea?', 'Nickel is part of urease, the enzyme that turns urea into a form the plant can use.'),
         ('Can nickel harm crops?', 'Yes, on soils near some mines or certain rocks nickel can rise high enough to stunt crops.')],
    links=[('Plant nutrients', '/nutrients/plant-nutrients'), ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'), ('Molybdenum in plants', '/nutrients/molybdenum-in-plants'),
           ('Urea fertilizer', '/blog/urea-fertilizer'), ('What is fertilizer', '/blog/what-is-fertilizer')],
))

# ------------------------------------------------------------------ silicon
N.append(dict(
    key='Si', name='Silicon', lower='silicon', slug='silicon-in-plants', fk='silicon in plants', sort=410, category='Beneficial nutrients',
    title='Silicon in Plants: What It Does and When Palay Needs It',
    metaTitle='Silicon in Plants: Role in Rice, Signs and Sources',
    meta='Silicon in plants stiffens rice leaves and stems against lodging, blast and planthoppers. See when palay needs it and why burning straw takes it away.',
    keywords=['role of silicon in plants', 'silicon for rice', 'silicon fertilizer', 'potassium silicate'],
    excerpt='Silicon in plants is not on the list of essential nutrients, yet palay takes more of it than of nitrogen. It stiffens the leaves and stems, so the crop stands up to wind, sheds blast and resists planthoppers and stem borers. Rice straw holds most of it, which is why burning or hauling away straw drains a field. Here is what it does and when palay needs it.',
    hero=hero('/images/site/photos/palay-heads.jpg', 'Ripe palay heads on stiff stems: silicon in plants keeps rice standing'),
    h_role='What silicon does in plants',
    role=['Rice lays silicon down as a thin glassy layer under the skin of its leaves, stems and husk. That layer stiffens the plant, holds the leaves upright toward the sun, slows water loss and makes the tissue harder for fungi and insects to get through.',
          'So a palay crop with enough silicon lodges less, gets less blast and brown spot, and suffers less from planthoppers and stem borers. It also uses its nitrogen better, because upright leaves shade each other less.'],
    h_kind='A building block of the plant body',
    kind=['Silicon is a building block. Once laid down under the skin it stays there for good, and the straw carries most of it. A good palay crop can hold several hundred kilograms of silicon per hectare, more than its nitrogen.',
          'It counts as a beneficial nutrient rather than an essential one, because rice can finish its life without it. In the field, though, a rice crop short of silicon is weaker in every way that matters to the yield.'],
    group='Beneficial nutrient', kindWord='Building block', moves='Hardly at all', first='Droopy, soft leaves and more disease', form='Silicic acid',
    h_mobile='Where silicon shows first',
    mobile=['Silicon does not move once it is laid down, so new leaves rely on a steady supply. A rice crop short of it has soft, droopy leaves that bend over, more brown spot and blast, and stems that lodge easily.',
            'See [silicon deficiency](/nutrient-deficiencies/silicon-deficiency) for the signs and fixes.'],
    h_crit='When silicon in plants is most critical',
    crit_intro=['Palay needs silicon most from panicle initiation to heading, when it builds the stems that will carry the panicle and the leaves that will fill it, and it starts storing silicon from tillering. Sugarcane, mais and other grasses also take a lot of it.',
                'Here is when each crop needs it most, from the anee.io stage book:'],
    h_block='What keeps silicon from the crop',
    block=['Old, heavily weathered soils, sandy soils and peat hold little silicon the roots can take. Fields cropped twice a year for many years, with the straw burned or carried away, run down fastest.',
           'Returning the straw puts most of the silicon back. That is one of the best reasons to work straw into the field instead of burning it.'],
    h_source='Where silicon comes from',
    source=['Rice straw and rice hull ash are the cheapest silicon, and they cost nothing if they come from your own field. Calcium silicate slags and potassium silicate sprays are also sold. See [balanced fertilization for rice](/blog/balanced-fertilization-for-rice).'],
    source_list=['Rice straw worked back into the field: most of the silicon the crop took.', 'Rice hull ash and carbonized rice hull: silicon and some potassium.', 'Calcium silicate: on the soil, also raises the pH a little.', 'Potassium silicate: a spray, with potassium too.'],
    much_t='Silicon does not replace good nitrogen and potassium',
    much='Silicon makes a crop stand better and resist pests, but it cannot fix a field short of nitrogen or potassium. Use it alongside a balanced program, not instead of one.',
    cta_t='See if silicon reaches your palay when it builds its stems',
    cta=CTA_PLAN % 'silicon',
    faq=[('Is silicon a nutrient for rice?', 'Silicon is a beneficial nutrient. Rice can live without it, but it takes more silicon than nitrogen and grows much stronger with it.'),
         ('Does silicon stop lodging?', 'It helps a lot. Silicon stiffens the stems and leaves, so the crop stands better in wind and rain.'),
         ('Why should I not burn rice straw?', 'Burning sends the straw\'s sulfur and nitrogen up in smoke and leaves its silicon and potassium in ash that blows or washes away. Working the straw in returns them to the soil.'),
         ('When should I apply silicon to palay?', 'Before or early in tillering on the soil, or as sprays from active tillering to heading.')],
    links=[('Silicon deficiency', '/nutrient-deficiencies/silicon-deficiency'), ('Plant nutrients', '/nutrients/plant-nutrients'), ('Potassium in plants', '/nutrients/potassium-in-plants'),
           ('Nitrogen in plants', '/nutrients/nitrogen-in-plants'), ('Balanced fertilization for rice', '/blog/balanced-fertilization-for-rice'), ('Organic vs inorganic fertilizer', '/blog/organic-vs-inorganic-fertilizer')],
))


def overview():
    blocks = []
    H = lambda t, l=2: blocks.append({'type': 'heading', 'level': l, 'text': t})
    T = lambda t: blocks.append({'type': 'text', 'text': t})
    H('What plant nutrients are')
    T('Plants build themselves from air, water and a short list of minerals. Carbon, hydrogen and oxygen come from the air and the water, and they make up most of the plant\'s weight. The rest, the mineral plant nutrients, come from the soil, from fertilizer, from manure and from the water in the field.\n\nA nutrient counts as essential when the plant cannot finish its life without it and nothing else can do its job. Fourteen minerals pass that test. Silicon, sodium and cobalt also help some crops without being strictly essential, and silicon matters so much to palay that we give it a page of its own.')
    H('Macronutrients and micronutrients', 2)
    T('The difference is the amount, not the importance. Macronutrients are taken in kilograms per hectare, micronutrients in grams. A field short of a micronutrient loses yield just as surely as one short of nitrogen.')
    blocks.append({'type': 'table', 'caption': 'The plant nutrients and their groups', 'rows': [
        ['Group', 'Nutrients', 'How much a crop takes'],
        ['Primary macronutrients', '[Nitrogen](/nutrients/nitrogen-in-plants), [phosphorus](/nutrients/phosphorus-in-plants), [potassium](/nutrients/potassium-in-plants)', 'Tens to hundreds of kilograms per hectare'],
        ['Secondary macronutrients', '[Calcium](/nutrients/calcium-in-plants), [magnesium](/nutrients/magnesium-in-plants), [sulfur](/nutrients/sulfur-in-plants)', 'About 10 to 50 kilograms per hectare'],
        ['Micronutrients', '[Zinc](/nutrients/zinc-in-plants), [boron](/nutrients/boron-in-plants), [iron](/nutrients/iron-in-plants), [manganese](/nutrients/manganese-in-plants), [copper](/nutrients/copper-in-plants), [molybdenum](/nutrients/molybdenum-in-plants), [chlorine](/nutrients/chlorine-in-plants), [nickel](/nutrients/nickel-in-plants)', 'Grams to a few hundred grams per hectare'],
        ['Beneficial', '[Silicon](/nutrients/silicon-in-plants), sodium, cobalt', 'Silicon: hundreds of kilograms in palay'],
    ]})
    H('Building blocks, helpers and regulators')
    T('Plant nutrients do three kinds of work, and knowing which kind tells you how a crop uses it up.\n\n**Building blocks** become part of the plant body: nitrogen and sulfur in every protein, phosphorus in genes and membranes, calcium and boron in cell walls, magnesium in chlorophyll, silicon in the skin of rice. The crop uses them up as it grows, and the harvest carries them out of the field, so they have to come back every season.\n\n**Helpers** switch on enzymes and do their job again and again without being used up: zinc, iron, manganese, copper, molybdenum and nickel. A few grams do a big job, but a missing helper stops the work of the big nutrients too.\n\n**Regulators** stay dissolved in the sap and steer water, sugar and the leaf pores: potassium and chloride. They are never built into the plant, yet crops take a lot of potassium.')
    H('Old leaves or new: where a shortage shows')
    T('Some nutrients move inside the plant and some stay where they land. That is the first clue to which one is short.\n\nNitrogen, phosphorus, potassium and magnesium move freely. A hungry plant pulls them from its old leaves to feed the new ones, so the old leaves change first. Calcium, boron, iron, manganese, copper and zinc hardly move, so the young leaves, the tips and the fruit suffer first. Sulfur sits in between and often pales the whole plant. Our [nutrient deficiency guides](/nutrient-deficiencies) show these signs crop by crop.')
    H('When crops need plant nutrients most')
    T('A crop does not need every nutrient the same amount every day. Each one has its windows, set by what the crop is building at that stage.\n\nIn palay, phosphorus and zinc matter most in the first weeks, nitrogen at tillering and panicle initiation, potassium and silicon from panicle initiation to heading. In mais, nitrogen and potassium matter most in the fast growth before tasselling. Fruit crops need calcium and boron at flowering and fruit set, and onion needs sulfur as it bulbs. Each nutrient page shows these windows for thirteen kinds of crop, with the usual days.')
    blocks.append({'type': 'table', 'caption': 'The most critical windows in palay, transplanted', 'rows': [
        ['Nutrient', 'Most critical stage', 'Usual days after transplanting'],
        ['Phosphorus', 'Recovery and early tillering', 'Days 0 to 20'],
        ['Zinc', 'Recovery and early tillering', 'Days 0 to 20'],
        ['Nitrogen', 'Tillering and panicle initiation', 'Days 7 to 49'],
        ['Potassium', 'Panicle initiation to heading', 'Days 35 to 59'],
        ['Silicon', 'Panicle initiation to heading', 'Days 35 to 59'],
    ]})
    H('The weakest link sets the harvest')
    T('A crop grows only as well as its scarcest nutrient lets it. Picture a barrel made of staves of different heights: it holds water only up to the shortest one. Adding more nitrogen to a field short of zinc or potassium raises nothing but the cost.\n\nThat is why a soil test, or the answers you give about your field, comes before the fertilizer plan. The [NPK Plus calculator](/features/npk-plus-calculator) in anee.io reads your soil and your crop and shows every nutrient in your plan against what the crop needs.')
    H('How plant nutrients get lost or locked up')
    T('A nutrient in the soil is not always a nutrient the crop can use. Some wash away with heavy rain, like nitrate, sulfate and boron. Some escape as gas, like the nitrogen in urea left on wet soil. Many lock up in the wrong pH: phosphorus, iron, manganese and zinc on alkaline soil, phosphorus and molybdenum on very acid soil.\n\nSo the same bag of fertilizer gives more on a field kept near pH 6 to 7, rich in organic matter, and fed in split doses at the stages that need it. Our guide to [balanced fertilization for rice](/blog/balanced-fertilization-for-rice) puts these together for palay.')
    blocks.append({'type': 'callout', 'tone': 'tip', 'title': 'Return the straw', 'text': 'Rice straw holds most of the potassium and silicon a crop took, and much of its sulfur. Working it back into the field returns them for free. Burning it sends part up in smoke and leaves the rest to wash away.'})
    blocks.append({'type': 'cta', 'title': 'See every nutrient on your crop\'s calendar', 'text': 'Put your fertilizers on your plan in anee.io and the [Coverage Planner](/features/residual-effect-calendar) shows, day by day, which plant nutrients reach the stages that need them and where they run out. Free to start.', 'label': 'Start free', 'url': '/signup'})
    H('Plant nutrient questions')
    blocks.append({'type': 'faq', 'items': [
        {'q': 'How many nutrients do plants need?', 'a': 'Seventeen are essential: carbon, hydrogen and oxygen from air and water, and fourteen minerals from the soil. Silicon and a few others help some crops without being essential.'},
        {'q': 'What is the difference between macronutrients and micronutrients?', 'a': 'Only the amount. Macronutrients are taken in kilograms per hectare and micronutrients in grams, but both are needed for a full harvest.'},
        {'q': 'Which nutrient does palay need most?', 'a': 'Nitrogen is the one most often short, then phosphorus, potassium and zinc. Palay also takes a lot of silicon from the soil.'},
        {'q': 'Why do old leaves yellow first in some crops and new leaves in others?', 'a': 'Mobile nutrients like nitrogen and potassium move from old leaves to new ones, so old leaves show the shortage. Immobile ones like calcium and iron cannot move, so new growth shows it.'},
    ]})
    blocks.append({'type': 'links', 'title': 'Related pages', 'items': [
        {'label': 'Nutrient deficiencies', 'url': '/nutrient-deficiencies'}, {'label': 'Nitrogen in plants', 'url': '/nutrients/nitrogen-in-plants'},
        {'label': 'Zinc in plants', 'url': '/nutrients/zinc-in-plants'}, {'label': 'What is fertilizer', 'url': '/blog/what-is-fertilizer'},
        {'label': 'Fertilizer timing by growth stage', 'url': '/blog/fertilizer-timing-by-growth-stage'}]})
    return {
        'section': 'nutrients', 'slug': 'plant-nutrients', 'lang': 'en', 'category': 'Start here', 'sortOrder': 10,
        'title': 'Plant Nutrients: What Each One Does and When Crops Need It',
        'metaTitle': 'Plant Nutrients: Macro, Micro and When Crops Need Each',
        'metaDescription': 'Plant nutrients, macro and micro: which ones build the plant, which ones help it work, where each shortage shows and when palay, mais and gulay need them.',
        'focusKeyword': 'plant nutrients', 'keywords': ['essential plant nutrients', 'macronutrients and micronutrients', 'primary secondary micronutrients', 'mobile and immobile nutrients'],
        'excerpt': 'Plant nutrients are the minerals a crop takes from the soil to build its body and run its chemistry. Some are building blocks the harvest carries away, some are helpers that work in tiny amounts, and some are regulators that steer water and sugar. Here is how they group, where each shortage shows first and when crops need each one most.',
        'heroImage': hero('/images/site/photos/hero-planting.jpg', 'Planting by hand in a field that will need plant nutrients all season'),
        'blocks': blocks,
    }


os.makedirs(OUT, exist_ok=True)
pages = [overview()] + [page(n) for n in N]
for p in pages:
    json.dump(p, open(os.path.join(OUT, p['slug'] + '.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
print('wrote', len(pages))
