"""Check the anee.io SEO pages against STYLE.md.

    python database/site-pages/check.py                 every page
    python database/site-pages/check.py blog/urea-fertilizer.json ...

Errors must be fixed; warnings are worth a look. Exit code 1 on any error.
"""
import glob
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))

BANNED = [
    "by paying attention", "in summary", "smooth experience", "daunting task", "in this article", "in conclusion",
    "break the bank", "breaking the bank", "today's digital age", "moreover", "homework", "furthermore",
    "additionally", "lastly", "in addition", "therefore", "ultimately", "informed decision", "dive in", "dive into",
    "delve", "fascinating world", "performing your research", "doing your research", "explore the world of",
    "consequently", "utilize", "utilise", "implement", "in order to", "pertaining to", "regarding", "subsequently", "thus",
    "facilitate", "prior to", "in the event of", "owing to", "in light of", "on the contrary", "in the midst of",
    "despite", "in accordance with", "with regard to", "subsequent to", "commence", "endeavor", "endeavour", "in lieu of",
    "notwithstanding", "in conjunction with", "landscape", "realm", "navigating", "tailored", "underpins", "unveil",
    "transformative", "encompass", "dynamic", "world", "ecosystem", "confluence", "engaging", "quest", "solutions",
    "solution", "delving", "significant", "significantly", "specific", "specifically", "numerous", "unsatisfied", "craft",
    "glean", "glance", "enhancing",
    # machine tells
    "unlock", "seamless", "robust", "leverage", "elevate", "harness", "comprehensive", "game changer", "boasts",
    "a testament", "treasure trove", "nestled", "vibrant", "whether you're", "look no further", "embark", "journey",
    "tapestry", "intricate", "pivotal", "crucial", "vital", "paramount", "meticulous", "bustling", "foster", "empower",
    "streamline", "cutting edge", "state of the art", "in today's", "it's worth noting", "it is important to note",
    "when it comes to", "at the end of the day", "plays a key role", "a key role", "navigate",
]
# Words whose banned stem also starts innocent words.
STEM_OK = {"craft": ["aircraft", "handicraft", "crafts"], "world": []}

TRANSITIONS = [
    "also", "but", "so", "because", "for example", "for instance", "first", "second", "third", "next", "then",
    "after that", "after", "finally", "besides", "instead", "in fact", "as a result", "that is why", "even so",
    "still", "while", "since", "before", "when", "if", "although", "though", "yet", "or", "once", "until",
    "unless", "as well", "similarly", "likewise", "in short", "above all", "otherwise", "meanwhile", "later",
    "at first", "to start", "in the end", "at the same time", "on the other hand", "even if", "even though",
    "in other words", "for this reason", "that means", "this means", "above", "besides that", "on top of that",
]

SECTIONS = {"crops", "pests", "diseases", "weeds", "land-preparation", "blog", "features"}
BLOCK_TYPES = {"heading", "text", "list", "steps", "table", "callout", "image", "quote", "faq", "cta", "links", "sources"}

SITE_URLS = {"/", "/features", "/pricing", "/about", "/tutorial", "/contact", "/signup", "/crops", "/problems", "/pests", "/diseases", "/weeds", "/land-preparation", "/blog"}


def url_map():
    text = open(os.path.join(HERE, "STYLE.md"), encoding="utf-8").read()
    part = text.split("## 8. URL map", 1)[1]
    return SITE_URLS | set(re.findall(r"(/(?:features|crops|pests|diseases|weeds|land-preparation|blog)/[a-z0-9\-]+)", part))


LINK = re.compile(r"\[([^\]]+)\]\(([^)]+)\)")


def plain(s):
    s = LINK.sub(lambda m: m.group(1), s or "")
    return s.replace("**", "")


def texts(page):
    """(kind, text) pieces in reading order, links already flattened."""
    out = [("excerpt", page.get("excerpt", ""))]
    for b in page.get("blocks", []):
        t = b.get("type")
        if t in ("text",):
            for para in re.split(r"\n\s*\n", b.get("text", "")):
                out.append(("para", para))
        elif t == "heading":
            out.append(("h%s" % b.get("level", 2), b.get("text", "")))
        elif t == "list":
            out += [("item", i) for i in b.get("items", [])]
        elif t == "steps":
            for i in b.get("items", []):
                out.append(("h4", i.get("title", "")))
                out.append(("para", i.get("text", "")))
        elif t == "table":
            out += [("cell", " ".join(str(c) for c in row)) for row in b.get("rows", [])]
            out.append(("cell", b.get("caption", "")))
        elif t == "callout":
            out += [("h4", b.get("title", "")), ("para", b.get("text", ""))]
        elif t == "faq":
            for i in b.get("items", []):
                out += [("h4", i.get("q", "")), ("para", i.get("a", ""))]
        elif t == "cta":
            out += [("h4", b.get("title", "")), ("para", b.get("text", "")), ("item", b.get("label", ""))]
        elif t == "quote":
            out += [("para", b.get("text", "")), ("item", b.get("cite", ""))]
        elif t == "image":
            out += [("alt", b.get("alt", "")), ("item", b.get("caption", ""))]
        elif t == "links":
            out.append(("h4", b.get("title", "")))
            out += [("item", i.get("label", "")) for i in b.get("items", [])]
    return out


def words(s):
    return re.findall(r"[A-Za-zÀ-ÿñÑ0-9']+", plain(s))


def norm(w):
    w = w.lower().strip("'")
    return w[:-1] if len(w) > 3 and w.endswith("s") and not w.endswith("ss") else w


def has_phrase(text, phrase):
    """All the keyphrase's words in this text, any order (Yoast's own match)."""
    have = {norm(w) for w in words(text)}
    return all(norm(w) in have for w in words(phrase))


def count_phrase(text, phrase):
    ws = [norm(w) for w in words(text)]
    ph = [norm(w) for w in words(phrase)]
    if not ph:
        return 0
    return sum(1 for i in range(len(ws) - len(ph) + 1) if ws[i:i + len(ph)] == ph)


def sentences(s):
    s = plain(s).strip()
    if not s:
        return []
    return [x.strip() for x in re.split(r"(?<=[.!?])\s+(?=[A-Z0-9\"'])", s) if x.strip()]


def check(path, urls, focus_seen):
    errs, warns = [], []
    try:
        page = json.load(open(path, encoding="utf-8"))
    except Exception as e:  # noqa
        return ["not valid JSON: %s" % e], []

    rel = os.path.relpath(path, HERE).replace("\\", "/")
    sec, slug = page.get("section"), page.get("slug")
    if sec not in SECTIONS:
        errs.append("section must be one of %s" % sorted(SECTIONS))
    if rel != "%s/%s.json" % (sec, slug):
        errs.append("file must be at %s/%s.json" % (sec, slug))
    lang = page.get("lang", "en")
    for f in ("title", "metaTitle", "metaDescription", "focusKeyword", "excerpt", "category"):
        if not str(page.get(f, "")).strip():
            errs.append("missing %s" % f)
    for b in page.get("blocks", []):
        if b.get("type") not in BLOCK_TYPES:
            errs.append("unknown block type %r" % b.get("type"))

    fk = page.get("focusKeyword", "")
    key = " ".join(norm(w) for w in words(fk))
    if key in focus_seen:
        errs.append("focus keyphrase %r already used by %s" % (fk, focus_seen[key]))
    focus_seen[key] = rel

    # ---- everything a reader or a crawler sees, for the word rules
    every = [page.get("title", ""), page.get("metaTitle", ""), page.get("metaDescription", ""),
             page.get("excerpt", ""), (page.get("heroImage") or {}).get("alt", "")]
    raw_pieces = []
    for b in page.get("blocks", []):
        for k, v in b.items():
            if k in ("type", "level", "ordered", "tone", "src"):
                continue
            if k == "url":
                continue
            raw_pieces.append(json.dumps(v, ensure_ascii=False) if not isinstance(v, str) else v)
    every += raw_pieces
    blob = "\n".join(every)
    blob_no_urls = LINK.sub(lambda m: m.group(1), blob)
    blob_no_urls = re.sub(r'"url":\s*"[^"]*"', "", blob_no_urls)
    blob_no_urls = re.sub(r"https?://\S+", "", blob_no_urls)
    low = blob_no_urls.lower()
    for phrase in BANNED:
        for m in re.finditer(r"(?<![a-z])" + re.escape(phrase) + r"(?![a-z])", low):
            ctx = low[max(0, m.start() - 25):m.end() + 25].replace("\n", " ")
            errs.append("banned phrase %r: ...%s..." % (phrase, ctx))
            break
    for ch, name in (("—", "em dash"), ("–", "en dash"), ("&", "ampersand"), (";", "semicolon"),
                     ("…", "ellipsis"), ("‘", "curly quote"), ("’", "curly quote"),
                     ("“", "curly quote"), ("”", "curly quote"), ("%", "percent sign"), ("->", "arrow")):
        if ch in blob_no_urls:
            i = blob_no_urls.index(ch)
            errs.append("%s in text: ...%s..." % (name, blob_no_urls[max(0, i - 25):i + 25].replace("\n", " ")))
    for m in re.finditer(r"[A-Za-z0-9]-[A-Za-z0-9]|\s-\s", blob_no_urls):
        i = m.start()
        errs.append("hyphen in text: ...%s..." % blob_no_urls[max(0, i - 25):i + 25].replace("\n", " "))
        break

    # ---- SEO basics
    mt, md = page.get("metaTitle", ""), page.get("metaDescription", "")
    if len(mt) > 60:
        errs.append("SEO title is %d characters (60 at most)" % len(mt))
    if not (120 <= len(md) <= 156):
        errs.append("meta description is %d characters (120 to 156)" % len(md))
    for name, val in (("title", page.get("title", "")), ("SEO title", mt), ("meta description", md), ("excerpt", page.get("excerpt", ""))):
        if not has_phrase(val, fk):
            errs.append("focus keyphrase %r not in the %s" % (fk, name))
    if mt and fk and not has_phrase(" ".join(words(mt)[:max(3, len(words(fk)) + 2)]), fk):
        warns.append("keyphrase is not at the start of the SEO title")
    slug_words = set(norm(w) for w in slug.replace("-", " ").split())
    if not all(norm(w) in slug_words for w in words(fk)) and lang == "en":
        warns.append("keyphrase not in the slug")

    pieces = texts(page)
    heads = [t for k, t in pieces if k in ("h2", "h3")]
    if not any(has_phrase(h, fk) for h in heads):
        errs.append("keyphrase in no H2 or H3")
    h2 = [b for b in page.get("blocks", []) if b.get("type") == "heading"]
    levels = [int(b.get("level", 2)) for b in h2]
    if levels and levels[0] != 2:
        errs.append("first heading must be H2")
    for a, b in zip(levels, levels[1:]):
        if b > a + 1:
            errs.append("heading skips a level (H%d then H%d)" % (a, b))

    body = [t for k, t in pieces if k in ("excerpt", "para", "item", "cell", "h2", "h3", "h4")]
    all_words = sum(len(words(t)) for t in body)
    # A weed in the catalogue is a profile, not a long guide, though its
    # ingredient table by rice age adds about 250 words (2026-10-06).
    profile = (sec == "weeds" and page.get("category") in ("Grasses", "Sedges", "Broadleaves")) or (sec in ("pests", "diseases") and page.get("profile"))
    lo, hi = (600, 1100) if sec == "features" else (650, 1700) if profile else (900, 1800)
    if all_words < lo:
        errs.append("only %d words (at least %d)" % (all_words, lo))
    elif all_words > hi:
        warns.append("%d words (aim for %d at most)" % (all_words, hi))
    occ = sum(count_phrase(t, fk) for t in body)
    dens = 100.0 * occ * max(1, len(words(fk))) / max(1, all_words)
    if dens < 0.5 or dens > 3.2:
        warns.append("keyphrase density %.1f percent (%d uses; aim 0.5 to 3)" % (dens, occ))

    # words between headings
    run = 0
    for k, t in pieces:
        if k in ("h2", "h3"):
            run = 0
        elif k in ("excerpt", "para", "item", "cell"):
            run += len(words(t))
            if run > 330:
                warns.append("more than 300 words without a subheading near: %s" % plain(t)[:60])
                run = 0

    paras = [t for k, t in pieces if k in ("excerpt", "para")]
    for p in paras:
        if len(words(p)) > 150:
            errs.append("paragraph over 150 words: %s..." % plain(p)[:60])

    sents = [s for p in paras for s in sentences(p)]
    if sents and lang == "en":
        long = sum(1 for s in sents if len(words(s)) > 20)
        if long / len(sents) > 0.25:
            warns.append("%d of %d sentences over 20 words (25 percent at most)" % (long, len(sents)))
        trans = 0
        for s in sents:
            ls = " " + plain(s).lower() + " "
            if any(re.search(r"(?<![a-z])" + re.escape(t) + r"(?![a-z])", ls) for t in TRANSITIONS):
                trans += 1
        if trans / len(sents) < 0.30:
            warns.append("transition words in %d of %d sentences (30 percent at least)" % (trans, len(sents)))
        passive = sum(1 for s in sents if re.search(r"\b(is|are|was|were|be|been|being|gets|got)\s+(\w+ly\s+)?\w+(ed|en)\b", s.lower()))
        if passive / len(sents) > 0.10:
            warns.append("about %d of %d sentences look passive (10 percent at most)" % (passive, len(sents)))
        firsts = [words(s)[0].lower() for s in sents if words(s)]
        for i in range(len(firsts) - 2):
            if firsts[i] == firsts[i + 1] == firsts[i + 2]:
                warns.append("three sentences in a row start with %r" % firsts[i])
                break

    # ---- links
    internal, promo, outbound = set(), 0, 0
    link_texts = [b.get("text", "") for b in page.get("blocks", []) if b.get("type") == "text"]
    link_texts += [i for b in page.get("blocks", []) if b.get("type") == "list" for i in b.get("items", [])]
    link_texts += [i.get("text", "") for b in page.get("blocks", []) if b.get("type") == "steps" for i in b.get("items", [])]
    link_texts += [i.get("a", "") for b in page.get("blocks", []) if b.get("type") == "faq" for i in b.get("items", [])]
    link_texts += [b.get("text", "") for b in page.get("blocks", []) if b.get("type") == "callout"]
    all_urls = []
    for t in link_texts:
        for _label, url in LINK.findall(t):
            all_urls.append(url)
    for b in page.get("blocks", []):
        if b.get("type") == "cta":
            all_urls.append(b.get("url", ""))
        if b.get("type") == "links":
            all_urls += [i.get("url", "") for i in b.get("items", [])]
        if b.get("type") == "sources":
            outbound += len([i for i in b.get("items", []) if str(i.get("url", "")).startswith("http")])
    body_internal = set()
    for t in link_texts:
        for _label, url in LINK.findall(t):
            if url.startswith("/"):
                body_internal.add(url.split("#")[0])
    self_url = "/%s/%s" % (sec, slug)
    for u in all_urls:
        if u.startswith("/"):
            base = u.split("#")[0].split("?")[0]
            if base not in urls:
                errs.append("link to %s is not in the URL map" % u)
            if base == self_url:
                warns.append("page links to itself")
            internal.add(base)
            if base.startswith("/features") or base in ("/signup", "/pricing"):
                promo += 1
        elif u.startswith("http"):
            outbound += 1
    if len(body_internal - {self_url}) < 3:
        errs.append("only %d internal links in the body text (3 at least)" % len(body_internal - {self_url}))
    if not promo:
        errs.append("no link to an anee.io feature, /signup or /pricing")
    if not any(b.get("type") == "cta" for b in page.get("blocks", [])):
        errs.append("no cta block")
    if outbound < 1 and sec != "features":
        errs.append("no outbound source link")
    if not any(b.get("type") == "faq" for b in page.get("blocks", [])):
        warns.append("no faq block")
    hero = page.get("heroImage") or {}
    if not hero.get("src") or not hero.get("alt"):
        warns.append("no hero image with alt text")
    return errs, warns


def page_sentences(page):
    """Every sentence a reader sees on the page, headings and meta included."""
    out = [page.get("title", ""), page.get("metaTitle", ""), page.get("metaDescription", "")]
    for _k, t in texts(page):
        out += sentences(t) or [plain(t)]
    return [s for s in out if s.strip()]


def coverage(files):
    """Every keyword of the CSV: on which pages it really appears."""
    kw = json.load(open(os.path.join(HERE, "keywords.json"), encoding="utf-8"))
    pages = {}
    for f in files:
        try:
            p = json.load(open(f, encoding="utf-8"))
        except Exception:  # noqa
            continue
        pages["%s/%s" % (p.get("section"), p.get("slug"))] = page_sentences(p)
    bad = 0
    lines = []
    for k, v in kw.items():
        found = [name for name, sents in pages.items() if any(has_phrase(s, k) for s in sents)]
        planned = [x for x in v["pages"] if x in pages]
        missing_here = [x for x in planned if x not in found]
        tag = "ok  "
        if not found:
            tag = "NONE" if planned else "todo"
            bad += 1 if planned else 0
        elif len(found) < 2:
            tag = "one "
        if missing_here or tag != "ok  ":
            lines.append("%s %5d  %-40s on %d page(s)%s" % (tag, v["volume"], k, len(found),
                         ("; missing from " + ", ".join(missing_here)) if missing_here else ""))
    print(chr(10).join(lines) or "every keyword is on two or more pages")
    print(chr(10) + "%d keywords, %d missing from pages that exist" % (len(kw), bad))
    return bad


def main():
    if "--coverage" in sys.argv:
        files = sorted(glob.glob(os.path.join(HERE, "*", "*.json")))
        sys.exit(1 if coverage(files) else 0)
    urls = url_map()
    files = sys.argv[1:] or sorted(glob.glob(os.path.join(HERE, "*", "*.json")))
    # "weeds/x.json" means the page next to this script, from any folder.
    files = [f if os.path.isabs(f) or os.path.exists(f) else os.path.join(HERE, f) for f in files]
    focus_seen = {}
    # Every page's keyphrase counts against the ones being checked.
    for f in sorted(glob.glob(os.path.join(HERE, "*", "*.json"))):
        if os.path.abspath(f) in [os.path.abspath(x) for x in files]:
            continue
        try:
            p = json.load(open(f, encoding="utf-8"))
            focus_seen[" ".join(norm(w) for w in words(p.get("focusKeyword", "")))] = os.path.relpath(f, HERE)
        except Exception:  # noqa
            pass
    bad = 0
    for f in files:
        errs, warns = check(f, urls, focus_seen)
        name = os.path.relpath(f, HERE).replace("\\", "/")
        if errs:
            bad += 1
        print("%s  %s" % ("FAIL" if errs else ("warn" if warns else "ok  "), name))
        for e in errs:
            print("   error: " + e)
        for w in warns:
            print("   warn:  " + w)
    print("\n%d page(s), %d with errors" % (len(files), bad))
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
