"""The public How to Plant pages, one per crop, from the app's guides (no video).

    python database/how-to-plant/build_pages.py          write every page and check it
    python database/how-to-plant/build_pages.py --dry    only report

Reads database/how-to-plant/guides/*.json (made from the research by the
scratchpad builder) and writes database/site-pages/how-to-plant/<slug>.json in
the site page format (STYLE.md), then runs check.py on them. Each page opens
with the same disclaimer as the app (this is how traditional farmers usually
plant the crop, a starting point to update for a higher yield), then the
steps by phase (each phase a steps block, so a HowTo in the structured data,
the parts of a step nested under it), the details, mistakes, what to change
for a higher yield, the app, questions and related guides. The page is gated
for guests by App\\Support\\ContentGate like the other guides.

Keywords: each page's focus is the Filipino search ("paano magtanim ng
kamote"), except rice and yellow corn, whose Tagalog searches belong to the
crop guides crops/pagtatanim-ng-palay and crops/pagtatanim-ng-mais: those
pages take the English search ("how to plant rice in the Philippines") and
link the crop guide both ways. The research keywords go to keywords.json
here, read by database/site-pages/keyword_map.py. A guide too short to make
a page of its own (some temperate crops) stays in the app only.

Change this generator and the guides, never the page files.
"""
import glob
import json
import os
import re
import sys
from collections import Counter

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HERE))
SITE = os.path.join(ROOT, "database", "site-pages")
OUT = os.path.join(SITE, "how-to-plant")
sys.path.insert(0, SITE)
import check  # noqa: E402

MIN_WORDS = 650

SHELF = {"rice": "Rice", "corn": "Corn and Sorghum", "legumes": "Legumes", "roots": "Root Crops", "vegetables": "Vegetables",
         "onions": "Onion and Garlic", "industrial": "Sugarcane and Industrial Crops", "fruits": "Fruits and Plantation Crops"}
SHELF_ORDER = list(SHELF)
PHASES = [("prep", "Before planting", "Bago magtanim"), ("nursery", "The nursery", "Punlaan"), ("planting", "Planting day", "Pagtatanim"),
          ("care", "Care while it grows", "Pag aalaga"), ("harvest", "Harvest", "Anihan")]

# The keywords the crop guides own (keyword_map.py): never a focus here.
OWNED = {"pagtatanim ng palay": "crops/pagtatanim-ng-palay", "paano magtanim ng palay": "crops/pagtatanim-ng-palay",
         "pagtatanim ng mais": "crops/pagtatanim-ng-mais", "paano magtanim ng mais": "crops/pagtatanim-ng-mais",
         "pagtatanim ng gulay": "crops/pagtatanim-ng-gulay", "pagtatanim ng puno": "crops/pagtatanim-ng-puno",
         "banana farming philippines": "crops/banana-farming-philippines", "palay farming": "crops/pagtatanim-ng-palay"}
FOCUS = {"rice": "how to plant rice in the Philippines", "corn_yellow": "how to plant corn in the Philippines"}

# The crop guides each page links (and that link back).
CROP_GUIDES = {
    "rice": [("/crops/pagtatanim-ng-palay", "pagtatanim ng palay, the full season guide in Tagalog"), ("/crops/palay", "palay from seed to grain")],
    "corn_yellow": [("/crops/pagtatanim-ng-mais", "pagtatanim ng mais, the full season guide in Tagalog"), ("/crops/corn-seeds", "how to choose corn seeds")],
    "corn_sweet": [("/crops/pagtatanim-ng-mais", "pagtatanim ng mais in Tagalog")], "corn_glutinous": [("/crops/pagtatanim-ng-mais", "pagtatanim ng mais in Tagalog")],
    "banana": [("/crops/banana-farming-philippines", "banana farming in the Philippines")],
    "coconut": [("/crops/coconut-fertilizer", "fertilizer for coconut trees by age")],
}
SHELF_GUIDES = {"vegetables": [("/crops/pagtatanim-ng-gulay", "pagtatanim ng gulay"), ("/crops/vegetables-philippines", "vegetables in the Philippines")],
                "fruits": [("/crops/pagtatanim-ng-puno", "pagtatanim ng puno")]}
CUCURBITS = {"ampalaya", "squash", "cucumber", "watermelon", "melon", "upo", "patola", "sayote"}
LAND = {"rice": "rice-land-preparation", "corn": "corn-land-preparation", "legumes": "legume-land-preparation", "roots": "root-crop-land-preparation",
        "vegetables": "vegetable-land-preparation", "onions": "onion-garlic-land-preparation", "fruits": "fruit-tree-land-preparation"}
FERT = {"rice": "/blog/fertilizer-for-rice", "corn": "/blog/increase-corn-yield-per-hectare", "vegetables": "/blog/fertilizer-for-vegetables-philippines",
        "fruits": "/blog/fertilizer-for-fruit-trees", "roots": "/blog/fertilizer-for-plants", "legumes": "/blog/fertilizer-for-plants",
        "onions": "/blog/fertilizer-for-vegetables-philippines", "industrial": "/blog/fertilizer-for-plants"}
HEROES = {
    "rice": ("/images/site/photos/transplant.jpg", "Farmers transplanting palay seedlings by hand in a flooded field"),
}
SMALL = {"ng", "sa", "na", "at", "in", "the", "of", "to", "and", "a", "o"}


def load_json(p, default=None):
    try:
        return json.load(open(p, encoding="utf-8"))
    except Exception:  # noqa
        return default


def cap_title(s):
    out = []
    for i, w in enumerate(s.split()):
        out.append(w if (i and w.lower() in SMALL) else (w[0].upper() + w[1:] if w else w))
    return " ".join(out)


def first_sentences(text, n=2):
    parts = re.split(r"(?<=[.!?])\s+", (text or "").strip())
    return " ".join(parts[:n]).strip()


def day_word(way):
    name = ((way.get("key") or "") + " " + (way.get("label") or "")).replace("_", " ").lower()
    if "transplant" in name:
        return "transplanting"
    if re.search(r"\b(direct|broadcast|sow|sown|seeded|seeding|drill|dibbl)", name):
        return "sowing"
    note = (way.get("note") or "").lower()
    if re.search(r"day 0 is (?:the )?transplanting", note):
        return "transplanting"
    if re.search(r"day 0 is (?:the )?sowing", note):
        return "sowing"
    return "planting"


def when(day, way, months):
    word = day_word(way)
    if day == 0:
        return word[0].upper() + word[1:] + " day"
    n = abs(day)
    if months and n >= 60:
        m = int(round(n / 30.0))
        span = ("%d years" % (m // 12)) if m >= 24 and m % 12 == 0 else ("%d months" % m)
        if day < 0:
            return span + " before " + word
        return ("Year %d" % (m // 12)) if m >= 24 and m % 12 == 0 else ("Month %d" % m)
    if day < 0:
        return "%d %s before %s" % (n, "day" if n == 1 else "days", word)
    return "Day %d" % day


def span_words(fr, to, way, months):
    """A watering stage's stretch: "Day 7 to 40", "21 to 1 days before transplanting". The twin of HowToPlant::spanWords."""
    if fr == to:
        return when(fr, way, months)
    a, b = when(fr, way, months), when(to, way, months)
    for p in ("Day ", "Month ", "Year "):
        if a.startswith(p) and b.startswith(p):
            return a + " to " + b[len(p):]
    my, ny = re.match(r"^(Month|Year) (\d+)$", a), re.match(r"^(Month|Year) (\d+)$", b)
    if my and ny:
        m = lambda x: int(x.group(2)) * (12 if x.group(1) == "Year" else 1)
        return "Month %d to %d" % (m(my), m(ny))
    rx =r"^(\d+) (days?|months|years) before (\w+)$"
    ma, mb = re.match(rx, a), re.match(rx, b)
    if ma and mb and ma.group(3) == mb.group(3) and ma.group(2).rstrip("s") == mb.group(2).rstrip("s"):
        return "%s to %s %ss before %s" % (ma.group(1), mb.group(1), ma.group(2).rstrip("s"), ma.group(3))
    return a + " to " + b[0].lower() + b[1:]


def watering_of(g, way):
    """The watering a way follows: its own, else the guide's (HowToPlant::watering)."""
    ok = lambda w: isinstance(w, dict) and (w.get("schedule") or (w.get("traditional") or "").strip())
    if ok(way.get("watering")):
        return way["watering"]
    return g["watering"] if ok(g.get("watering")) else None


def water_blocks(water, way, months, say, H, blocks, lead=None):
    """One watering: how it is done, the stages that must not dry out, the schedule as a table, the seasons, the signs."""
    first = " ".join(x for x in [lead, (water.get("traditional") or "").strip(), (water.get("method") or "").strip()] if x)
    if first:
        blocks.append({"type": "text", "text": first})
    if water.get("critical"):
        blocks.append({"type": "callout", "tone": "warn", "title": "Never let %s go dry at these stages" % say, "text": " ".join(c if c.endswith(".") else c + "." for c in water["critical"])})
    sched = [s for s in (water.get("schedule") or []) if isinstance(s, dict)]
    if sched:
        rows = [["Stage", "When", "How often", "How much"]]
        for s in sched:
            rows.append([s["stage"], span_words(int(s["from"]), int(s["to"]), way, months), s.get("often") or "As needed", s.get("amount") or "Enough to wet the root zone"])
        blocks.append({"type": "table", "caption": "Watering schedule for %s, stage by stage" % say, "rows": rows})
    seasons = [("rainfed", "Without irrigation"), ("dryseason", "In the dry season"), ("wet", "In the rainy season")]
    paras = ["**%s.** %s" % (label, water[k].strip()) for k, label in seasons if (water.get(k) or "").strip()]
    if paras:
        blocks.append({"type": "text", "text": "\n\n".join(paras)})
    signs = water.get("signs") or {}
    items = [("**Too little water:** " + signs["dry"]) if (signs.get("dry") or "").strip() else None,
             ("**Too much water:** " + signs["wet"]) if (signs.get("wet") or "").strip() else None]
    items = [x for x in items if x]
    if items:
        blocks.append({"type": "list", "ordered": False, "items": items})
    if water.get("better"):
        blocks.append({"type": "text", "text": "To water better for a higher yield: " + " ".join(b if b.endswith(".") else b + "." for b in water["better"])})


def harvest_words(way, months):
    d = int(way.get("days") or 0)
    if d <= 0:
        return None
    word = day_word(way)
    if months and d >= 120:
        m = int(round(d / 30.0))
        span = ("%d years" % (m // 12)) if m >= 24 and m % 12 == 0 else ("%d months" % m)
        return "about %s after %s" % (span, word)
    return "about %d days after %s" % (d, word)


def way_short(way):
    return re.sub(r"\s*\(.*$", "", way.get("label") or "").strip() or way.get("label") or ""


def hero_map():
    """crop => (src, alt, credit): the crop variety finders' pictures, credited as the pages that carry them."""
    book = load_json(os.path.join(ROOT, "database", "crop-varieties", "crops.json"), {}) or {}
    heroes = book.get("heroes", {})
    credits = {}
    for f in glob.glob(os.path.join(SITE, "*", "*.json")):
        d = load_json(f, {}) or {}
        h = d.get("heroImage") or {}
        if h.get("src") and h["src"] not in credits:
            credits[h["src"]] = h.get("credit") or ""
    out = {}
    for _, c in (book.get("crops") or {}).items():
        if not isinstance(c, dict):
            continue
        h = heroes.get(c.get("hero"))
        for ck in c.get("catalogue", []):
            if h:
                out[ck] = (h[0], h[1], credits.get(h[0], ""))
    for k, (src, alt) in HEROES.items():
        out[k] = (src, alt, credits.get(src, ""))
    return out, heroes, credits


def variety_hubs():
    """crop key => /crop-varieties/<slug> for the crops with a finder (three varieties or more), as check.py counts them."""
    book = load_json(os.path.join(ROOT, "database", "crop-varieties", "crops.json"), {}) or {}
    vs = load_json(os.path.join(ROOT, "database", "crop-varieties", "varieties.json"), []) or []
    n = Counter(v.get("crop") for v in vs)
    slugs = {"stringbean": "sitao", "snapbean": "snap-bean", "chinesecabbage": "chinese-cabbage", "limabean": "lima-bean", "wingedbean": "winged-bean",
             "bellpepper": "bell-pepper", "sweetpotato": "sweet-potato", "ubi": "ube", "starapple": "star-apple", "pigeonpea": "pigeon-pea"}
    out = {}
    for key, c in (book.get("crops") or {}).items():
        if isinstance(c, dict) and n.get(key, 0) >= 3:
            for ck in c.get("catalogue", []):
                out[ck] = "/crop-varieties/" + slugs.get(key, key)
    return out


def deficiency_page(g):
    for s in [g["slug"], "corn" if g["crop"].startswith("corn") else None, g["crop"].replace("_", "-")]:
        if s and os.path.exists(os.path.join(SITE, "nutrient-deficiencies", s + "-nutrient-deficiency.json")):
            return "/nutrient-deficiencies/%s-nutrient-deficiency" % s
    return None


def land_page(g):
    k, shelf = g["crop"], g.get("shelf")
    if k in CUCURBITS:
        return "/land-preparation/cucurbit-land-preparation", "cucurbits"
    if k in ("banana", "abaca"):
        return "/land-preparation/banana-land-preparation", "banana and abaca"
    if k == "sugarcane":
        return "/land-preparation/sugarcane-land-preparation", "sugarcane"
    if g.get("kind") == "perennial":
        return "/land-preparation/fruit-tree-land-preparation", "fruit and plantation trees"
    if shelf in LAND:
        word = {"rice": "rice", "corn": "corn", "legumes": "legumes", "roots": "root crops", "vegetables": "vegetables", "onions": "onion and garlic"}[shelf]
        return "/land-preparation/" + LAND[shelf], word
    return "/land-preparation/land-preparation-philippines", "every crop"


def focus_of(g, used):
    if g["crop"] in FOCUS:
        return FOCUS[g["crop"]]
    for kw in g.get("keywords") or []:
        kw = kw.strip().lower()
        if kw.startswith("paano magtanim ng ") and kw not in OWNED and kw not in used:
            return kw
    name = g["name"].lower()
    name = re.sub(r"\s*\(.*\)", "", name)
    return "how to plant " + name


def para_links(items):
    return ", ".join("[%s](%s)" % (l, u) for u, l in items)


def page_of(g, order, heroes, hubs, used):
    crop, name, local = g["crop"], g["name"], (g.get("local") or g["name"]).strip()
    months = g.get("kind") == "perennial"
    shelf = g.get("shelf") or "fruits"
    lname = re.sub(r"\s*\(.*\)", "", name).strip()
    low = lname.lower()
    # In a sentence: "kamote", "mais na malagkit", "sibuyas Tagalog" (only the first letter falls).
    say = (local[0].lower() + local[1:]) if local.lower() != low else low
    plant_word = say if say != low else low
    focus = focus_of(g, used)
    used.add(focus)
    tagalog = focus.startswith("paano")
    ftitle = cap_title(focus).replace(" Philippines", " Philippines")
    ways = g["ways"]

    # Titles.
    if tagalog:
        title = "%s: How to Plant %s the Traditional Way" % (ftitle, cap_title(low) if low != say.lower() else cap_title(low))
    else:
        title = "%s: %s the Traditional Way, Step by Step" % (ftitle, cap_title(local))
    meta_title = next(t for t in ["%s: The Traditional Way, Step by Step" % ftitle, "%s: The Traditional Way" % ftitle, "%s: Step by Step" % ftitle, ftitle] if len(t) <= 60)
    fcap = focus[0].upper() + focus[1:]
    descs = [
        "%s? See how traditional farmers plant %s step by step, with the days, the spacing and what to change for a higher yield." % (fcap, plant_word),
        "%s? See how traditional farmers plant %s step by step, with the days and what to change for a higher yield." % (fcap, plant_word),
        "%s? Every step of the traditional way, with its day, and what to change for a higher yield." % fcap,
        "%s the traditional way: every step with its day, the spacing, water and fertilizer, and what to change for a higher yield." % fcap,
    ]
    meta = next((d for d in descs if 120 <= len(d) <= 156), None)
    if not meta:
        d = descs[1] if len(descs[0]) > 156 else descs[0]
        meta = d if len(d) <= 156 else d[:150].rsplit(" ", 1)[0] + "."
        if len(meta) < 120:
            meta = meta[:-1] + " on a Philippine farm."
    if tagalog:
        excerpt = ("%s? Here is how traditional Filipino farmers usually plant %s%s, step by step from getting the field ready to the harvest, "
                   "with the day of each step and what to change for a higher yield." % (fcap, say, " (%s)" % low if say.lower() != low else ""))
    else:
        excerpt = ("%s the traditional way: here is how most Filipino farmers plant %s, step by step from getting the field ready to the harvest, "
                   "with the day of each step and what to change for a higher yield." % (fcap, say))

    land_url, land_word = land_page(g)
    guides = CROP_GUIDES.get(crop, []) + SHELF_GUIDES.get(shelf, [])
    links = [(land_url, "land preparation guide for " + land_word)]
    if hubs.get(crop):
        links.append((hubs[crop], "%s varieties sold here" % say))
    dp = deficiency_page(g)
    if dp:
        links.append((dp, "%s nutrient deficiency guide" % say))

    blocks = []
    H = lambda lvl, t: blocks.append({"type": "heading", "level": lvl, "text": t})
    # The disclaimer, first on every page, open to every reader.
    blocks.append({"type": "callout", "tone": "warn", "title": "This is the traditional way",
                   "text": "This guide shows how traditional farmers usually plant %s. Use it as a starting point. For a higher yield, update it: "
                           "plant certified seed or clean planting material, test your soil, and follow the right spacing and fertilizer." % say})
    intro2 = "Before you plant, get the field ready with our %s." % para_links(links[:1])
    if len(links) > 1:
        intro2 += " Then choose your seed or planting material with the %s." % para_links(links[1:2])
    if len(links) > 2:
        intro2 += " If the leaves turn pale later on, the %s helps you find what is missing." % para_links(links[2:3])
    if guides:
        intro2 += " For more on the crop, read %s." % para_links(guides[:2])
    blocks.append({"type": "text", "text": (g.get("summary") or "").strip() + "\n\n" + intro2})

    H(2, "When to plant %s" % say)
    blocks.append({"type": "text", "text": g.get("season") or ""})
    H(2, "What you plant")
    blocks.append({"type": "text", "text": g.get("material") or ""})

    for wi, w in enumerate(ways):
        hw = harvest_words(w, months)
        if wi == 0:
            H(2, "%s, step by step" % fcap if len(ways) == 1 else "%s: %s" % (fcap, way_short(w).lower()))
        else:
            H(2, "%s, step by step" % way_short(w))
        lead = []
        if len(ways) > 1:
            lead.append("This is the %s way (%s)." % (way_short(w).lower(), w.get("label") or ""))
        if w.get("note"):
            lead.append(w["note"])
        if hw:
            lead.append("The crop is ready %s." % hw)
        if lead:
            blocks.append({"type": "text", "text": " ".join(lead)})
        for pk, plabel, ptl in PHASES:
            steps = [s for s in w["steps"] if s.get("phase") == pk]
            if not steps:
                continue
            H(3, "%s (%s)" % (plabel, ptl) if len(ways) == 1 else "%s: %s (%s)" % (way_short(w), plabel.lower() if pk != "planting" else "planting day", ptl))
            items = []
            for s in steps:
                text = s["how"] + ((" Tip: " + s["tip"]) if s.get("tip") else "")
                it = {"title": "%s: %s" % (when(int(s["day"]), w, months), s["title"]), "text": text}
                if s.get("parts"):
                    it["parts"] = [dict({"title": p["title"], "text": p["how"] + ((" Tip: " + p["tip"]) if p.get("tip") else "")},
                                        **({"time": p["time"]} if p.get("time") else {})) for p in s["parts"]]
                items.append(it)
            blocks.append({"type": "steps", "items": items})

    # Watering (the owner, 2026-10-19): one H2 after the steps; a way with its own schedule gets an H3.
    waters = [(w, watering_of(g, w)) for w in ways]
    waters = [(w, x) for w, x in waters if x]
    if waters:
        H(2, "Watering %s, stage by stage" % say)
        shared = all(x is waters[0][1] for _, x in waters) and len(waters) == len(ways)
        if shared:
            water_blocks(waters[0][1], waters[0][0], months, say, H, blocks)
        else:
            for w, x in waters:
                H(3, "%s: how to water" % way_short(w) if len(ways) > 1 else "How and when to water")
                water_blocks(x, w, months, say, H, blocks)

    H(2, "Spacing, water and fertilizer for %s" % say)
    for key, label in (("spacing", "Spacing"), ("water", "Water"), ("fertilizer", "Fertilizer")):
        if (g.get(key) or "").strip():
            H(3, label)
            txt = g[key]
            if key == "fertilizer":
                txt += " To read what a bag really gives, see our guide to [%s](%s)." % ("fertilizer for this crop" if shelf != "rice" else "fertilizer for rice", FERT.get(shelf, "/blog/fertilizer-for-plants"))
            blocks.append({"type": "text", "text": txt})
    H(2, "Care and harvest")
    for key, label in (("care", "Care while it grows"), ("harvest", "When and how to harvest")):
        if (g.get(key) or "").strip():
            H(3, label)
            blocks.append({"type": "text", "text": g[key]})

    if g.get("mistakes"):
        H(2, "Common mistakes when planting %s" % say)
        blocks.append({"type": "list", "ordered": False, "items": g["mistakes"]})
    if g.get("better"):
        H(2, "How to get a higher yield of %s" % say)
        blocks.append({"type": "text", "text": "The traditional way gets a crop, but small changes add up. These are the first things to change, one season at a time."})
        blocks.append({"type": "list", "ordered": False, "items": g["better"]})
        blocks.append({"type": "text", "text": ("anee.io helps with each of these. The [NPK Plus calculator](/features/npk-plus-calculator) checks that your fertilizer plan "
                                                "feeds the yield you want, the [pest and disease finders](/features/pest-and-disease-finders) tell you what is on the leaves "
                                                "before you spray, and [Anee, the smart farm technician](/features/ai-agricultural-technician) answers your questions in Tagalog or English.")})
    blocks.append({"type": "callout", "tone": "tip", "title": "Use this guide in anee.io",
                   "text": ("Members open this guide in the [How to Plant](/app/how-to-plant?crop=%s) tool and send every step to the "
                            "[Protocol Builder](/features/protocol-builder) in one tap, each on its own day. There you can change the seed, the spacing "
                            "and the fertilizer, then turn it into a cropping calendar for your lots." % crop)})
    blocks.append({"type": "cta", "title": "Plan your %s season in anee.io" % say,
                   "text": "Start from the traditional way, make it better, and let anee.io date every task from your own planting day. Free to start.",
                   "label": "Start free", "url": "/signup"})

    faq = [{"q": ("%s?" % fcap) if tagalog else ("%s the traditional way?" % fcap),
            "a": "%s Follow the steps above in order, %s, then change one thing at a time for a higher yield." % (
                first_sentences(g.get("summary") or "", 1), "from getting the field ready to the harvest")}]
    if g.get("season"):
        faq.append({"q": "When do farmers plant %s in the Philippines?" % say, "a": first_sentences(g["season"], 2)})
    if g.get("spacing"):
        faq.append({"q": "How far apart do you plant %s?" % say, "a": first_sentences(g["spacing"], 2)})
    hws = [(way_short(w), harvest_words(w, months)) for w in ways if harvest_words(w, months)]
    if hws:
        a = " ".join("%s, it is ready %s." % (n, h) if len(hws) > 1 else "It is ready %s." % h for n, h in hws)
        if g.get("harvest"):
            a += " " + first_sentences(g["harvest"], 1)
        faq.append({"q": "How long before %s can be harvested?" % say, "a": a})
    if waters:
        x = waters[0][1]
        rows = [s for s in (x.get("schedule") or []) if isinstance(s, dict) and s.get("often")]
        said = []
        for s in rows[:3]:
            sp = span_words(int(s["from"]), int(s["to"]), waters[0][0], months)
            said.append("%s, %s: %s." % (s["stage"], sp[0].lower() + sp[1:], s["often"].rstrip(".")))
        a = " ".join(said)
        if not a:
            a = first_sentences(x.get("traditional") or "", 2)
        if (x.get("dryseason") or "").strip():
            a += " " + first_sentences(x["dryseason"], 1)
        if a.strip():
            faq.append({"q": "How often do you water %s?" % say, "a": a.strip()})
    if g.get("material"):
        faq.append({"q": "What do you plant to grow %s?" % say, "a": first_sentences(g["material"], 2)})
    if g.get("better"):
        faq.append({"q": "How can I get a higher yield of %s?" % say, "a": " ".join(g["better"][:2])})
    if faq:
        H(2, "Questions about planting %s" % say)
        # Six with the watering question (2026-10-19), five as before without it.
        blocks.append({"type": "faq", "items": faq[:6] if waters else faq[:5]})

    rel = [{"label": l[0].upper() + l[1:], "url": u} for u, l in links + guides]
    rel.append({"label": "Every crop, the traditional way", "url": "/how-to-plant"})
    rel.append({"label": "Pests of every crop", "url": "/pests"})
    seen, items = set(), []
    for r in rel:
        if r["url"] not in seen:
            seen.add(r["url"])
            items.append(r)
    blocks.append({"type": "links", "title": "Read next", "items": items[:6]})

    hero = heroes.get(crop)
    page = {
        "section": "how-to-plant", "slug": g["slug"], "lang": "en", "category": SHELF.get(shelf, "Fruits and Plantation Crops"),
        "sortOrder": order, "title": title, "metaTitle": meta_title, "metaDescription": meta, "focusKeyword": focus,
        "keywords": [], "excerpt": excerpt,
        "heroImage": {"src": hero[0], "alt": hero[1], "credit": hero[2]} if hero else {"src": "/images/site/photos/hero-planting.jpg", "alt": "A farm worker planting in a field", "credit": ""},
        "blocks": blocks,
    }
    # The keywords this page really carries: its focus, and the research words that read on it.
    sents = check.page_sentences(page)
    kws = [focus] + [k for k in (g.get("keywords") or []) if k != focus and any(check.has_phrase(s, k) for s in sents)]
    eng = "how to plant %s" % low
    if eng not in kws and any(check.has_phrase(s, eng) for s in sents):
        kws.append(eng)
    page["keywords"] = kws[:8]
    return page


def main():
    dry = "--dry" in sys.argv
    guides = []
    for f in glob.glob(os.path.join(HERE, "guides", "*.json")):
        g = load_json(f)
        if g and g.get("ways"):
            guides.append(g)
    cat_order = {}
    try:
        for i, k in enumerate(re.findall(r"^\s*'([a-z0-9_]+)'\s*=>\s*\[\s*'label'", open(os.path.join(ROOT, "app", "Support", "CropCatalog.php"), encoding="utf-8").read(), re.M)):
            cat_order.setdefault(k, i)
    except Exception:  # noqa
        pass
    guides.sort(key=lambda g: (SHELF_ORDER.index(g.get("shelf", "fruits")) if g.get("shelf") in SHELF_ORDER else 9, cat_order.get(g["crop"], 999)))
    heroes, _, _ = hero_map()
    hubs = variety_hubs()
    used = set()
    os.makedirs(OUT, exist_ok=True)
    made, skipped, keywords = [], {}, {}
    for i, g in enumerate(guides):
        page = page_of(g, 10 + i * 10, heroes, hubs, used)
        n = sum(len(check.words(t)) for k, t in check.texts(page) if k in ("excerpt", "para", "item", "cell", "h2", "h3", "h4"))
        if n < MIN_WORDS:
            skipped[g["crop"]] = n
            used.discard(page["focusKeyword"])
            continue
        made.append(page["slug"])
        # A search is mapped only where it really reads (check.py --coverage holds pages to it).
        for k in page["keywords"]:
            k = re.sub(r"\s+", " ", k.strip().lower())
            if k and k not in OWNED:
                keywords.setdefault(k, ["how-to-plant/" + page["slug"]])
        for k in (g.get("keywords") or []):
            k = re.sub(r"\s+", " ", k.strip().lower())
            if k in OWNED:
                keywords.setdefault(k, [OWNED[k], "how-to-plant/" + page["slug"]] if k in page["keywords"] else [OWNED[k]])
        keywords.setdefault(page["focusKeyword"].lower(), ["how-to-plant/" + page["slug"]])
        if not dry:
            with open(os.path.join(OUT, page["slug"] + ".json"), "w", encoding="utf-8", newline="\n") as fh:
                json.dump(page, fh, ensure_ascii=False, indent=1)
                fh.write("\n")
    if not dry:
        for f in glob.glob(os.path.join(OUT, "*.json")):
            if os.path.basename(f)[:-5] not in made:
                os.remove(f)
        with open(os.path.join(HERE, "keywords.json"), "w", encoding="utf-8", newline="\n") as fh:
            json.dump(dict(sorted(keywords.items())), fh, ensure_ascii=False, indent=1)
            fh.write("\n")
    print("%d pages, %d guides too short for a page: %s" % (len(made), len(skipped), skipped))
    if not dry and made:
        import subprocess
        subprocess.run([sys.executable, os.path.join(SITE, "check.py")] + ["how-to-plant/%s.json" % s for s in made])


if __name__ == "__main__":
    main()
