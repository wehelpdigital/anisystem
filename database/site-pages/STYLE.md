# Writing the anee.io public pages

These pages exist to rank in Google Philippines for farming searches and to
bring Filipino farmers to anee.io. They must read like a Filipino agricultural
technician who also writes well: practical, warm, exact, and never like a
machine wrote them.

## 1. Voice

- Write as a Filipino agriculturist talking to farmers: plain words, short
  sentences, real field detail (cavans, hectares, the DA, PhilRice, the
  wet and dry seasons, typhoons, the local names of pests and weeds).
- English pages may use common Filipino farm words naturally (palay, mais,
  abono, punla, ani, bukid), each explained once.
- Tagalog pages (lang "tl") are written in natural Filipino as a Filipino
  farm writer would, with the English terms farmers actually use (fertilizer,
  urea, insecticide, hectare). Not stiff textbook Tagalog, not word for word
  translation.
- Be exact. Numbers, names, active ingredients and dates must be true. When a
  value varies (prices, rates) say so and give the range and the source. Never
  invent a statistic, a study, a product claim or a quote.
- Pesticides: name the active ingredient and the group, say what it is used
  on, and always tell the reader to follow the product label and the FPA
  registration. Never give a spray rate you did not verify on a label or an
  official source. Never promise a cure.
- No fluff openings ("Rice is life..."), no hype, no filler conclusions.

## 2. Forbidden

Never use any of these words or phrases, in any case, anywhere (titles,
meta, body, FAQ, alt text):

by paying attention, in summary, smooth experience, daunting task, in this
article, in conclusion, break the bank, breaking the bank, today's digital
age, moreover, homework, furthermore, additionally, lastly, in addition,
therefore, ultimately, informed decision, dive in, dive into, delve, fascinating
world, performing your research, doing your research, explore the world of,
consequently, utilize, implement, in order to, pertaining to, regarding,
subsequently, thus, facilitate, prior to, in the event of, owing to, in light
of, on the contrary, in the midst of, despite, in accordance with, with regard
to, subsequent to, commence, endeavor, in lieu of, notwithstanding, in
conjunction with, landscape, realm, navigating, tailored, underpins, unveil,
transformative, encompass, dynamic, world, ecosystem, confluence, engaging,
quest, solutions, delving, significant, specific, numerous, unsatisfied,
craft, glean, glance, enhancing

Also never use these machine tells: unlock, seamless, robust, leverage,
elevate, harness, comprehensive, game changer, boasts, a testament, treasure
trove, nestled, vibrant, whether you're, look no further, embark, journey,
tapestry, intricate, pivotal, crucial, vital, paramount, meticulous,
bustling, foster, empower, streamline, cutting edge, state of the art, in
today's, it's worth noting, it is important to note, when it comes to, at the
end of the day, plays a key role, a key role, navigate.

Use instead: also, so, but, use, carry out, to, about, later, before, if,
because of, considering, during, following, begin, try, instead of, along
with, besides, finally, important, exact, many, answers, help, customized,
cover, reveal, supports.

## 3. Characters

- No dashes of any kind in prose: no em dash, no en dash, no hyphen. Rewrite
  instead: "well drained soil", "day 30 after transplanting", "14 14 14",
  "a two week interval", "zero tillage". (Addresses/URLs are fine.)
- No ampersand (&), semicolon (;), ellipsis, arrows, emoji, or curly quotes
  in text. Use plain words and straight quotes.
- Write "percent" instead of the % sign, "kilograms" or "kg", "per hectare".

## 4. Yoast rules every page must pass

- One focus keyphrase per page, never reused on another page.
- Focus keyphrase in: the page title (H1), the SEO title (ideally at the
  start), the meta description, the slug, the first paragraph (the excerpt),
  and at least one H2. Use it naturally 0.5 to 3 percent of words; use the
  secondary keywords and synonyms for the rest.
- SEO title: 60 characters or less (the site adds " | anee.io").
- Meta description: 120 to 156 characters, contains the keyphrase, says why
  to click.
- Length: crop, pest, disease, weed guide and blog pages 900 to 1600 words;
  weed, pest and disease profiles (the catalogues) 650 to 1700 words, tables
  included; feature pages 600 to 1000 words.
- Headings: H2 for sections, H3 inside them; never skip levels; a heading at
  least every 300 words.
- Paragraphs: 150 words at most, usually 2 to 4 sentences.
- Sentences: at most a quarter of them over 20 words.
- Transition words (also, but, so, because, for example, first, next, then,
  after that, finally, besides, instead, in fact, as a result, for instance,
  that is why, even so, still, while, since, before) in at least 30 percent of
  sentences (English pages).
- Passive voice in under 10 percent of sentences.
- Never three sentences in a row starting with the same word.
- At least 3 internal links to other pages of this site in the body text (from
  the URL map below), at least 1 link to an anee.io feature page or /signup
  or /pricing, and at least 1 outbound link to an authoritative source
  (PhilRice, DA, BPI, FPA, IRRI, PSA, a university, a manufacturer's own
  label page) inside a "sources" block.
- Every image has alt text that describes the picture (use the keyphrase in
  the alt of the first image where it is natural).
- Keywords must read naturally and be grammatically correct in the sentence.
  "fertilizer urea" becomes "urea fertilizer"; "rice variety philippines"
  becomes "rice varieties in the Philippines". A keyword that cannot be used
  naturally is left out rather than forced.
- Every page promotes anee.io honestly: at least one "cta" block and one
  in-text mention that shows how anee.io helps with this exact topic (the
  cropping calendar that dates every task, Anee the AI technician who answers
  in Tagalog or English and reads a photo, growth stages and weather, the
  inventory that tracks fertilizer, the reports). Never claim a feature that
  is not in section 7.

## 5. Page file format

```json
{
  "section": "crops",
  "slug": "palay",
  "lang": "en",
  "category": "Palay",
  "title": "Palay: What It Is and How to Grow It Well",
  "metaTitle": "Palay: Meaning, Growth Stages and Farming Guide",
  "metaDescription": "Palay is rice still in its husk. Learn what palay means in English, how it grows from seed to harvest, and how to raise your yield per hectare.",
  "focusKeyword": "palay",
  "keywords": ["palay in english", "palay meaning", "rice seeds"],
  "excerpt": "First paragraph shown under the title. Contains the focus keyphrase.",
  "heroImage": { "src": "/images/site/palay.jpg", "alt": "Palay heads ripening in a field", "credit": "" },
  "blocks": [ ... ]
}
```

`excerpt` is the page's first paragraph (it is rendered right under the H1),
so do not repeat it as the first text block.

Blocks (the builder in the mother app edits exactly these):

| type | fields |
|---|---|
| heading | `level` 2 or 3, `text` |
| text | `text`: paragraphs separated by a blank line. Inline: `[label](/url)` links and `**bold**`. No HTML. |
| list | `ordered` true/false, `items`: array of strings (inline links allowed) |
| steps | `items`: array of `{ "title": "...", "text": "..." }` |
| table | `caption`, `rows`: array of arrays; the first row is the header |
| callout | `tone` "tip", "warn" or "info", `title`, `text` |
| image | `src`, `alt`, `caption` (credit goes in the caption) |
| quote | `text`, `cite` |
| faq | `items`: array of `{ "q": "...", "a": "..." }` (becomes FAQ structured data; 3 to 6 questions people really ask) |
| cta | `title`, `text`, `label`, `url` (e.g. "/signup", "/features/ai-agricultural-technician") |
| links | `title`, `items`: array of `{ "label": "...", "url": "/..." }` (related pages) |
| sources | `items`: array of `{ "label": "...", "url": "https://..." }` (outbound references) |

A good page shape: excerpt, 2 to 3 text paragraphs, H2 sections with text,
lists, tables or steps as the content needs, a callout, a mid page cta, an
faq block, a links block to 3 to 6 related pages, a sources block last.

Images: use a site image from the list in section 6, or propose one Wikimedia
Commons photo in `heroImage` as `{ "src": "commons:File:Exact_file_name.jpg",
"alt": "...", "credit": "Author, License" }` using only CC0, public domain,
CC BY or CC BY SA files. Check the file page to be sure the name and licence
are right. Do not use an image that does not show what the page is about.

## 6. Site images you may use

/images/site/palay.jpg (palay field), /images/site/hero-terraces.jpg (rice
terraces), /images/site/fields-aerial.jpg (aerial farm fields),
/images/site/corn-rows.jpg (corn rows), /images/site/harvest-hands.jpg
(workers harvesting), /images/site/photos/palay-heads.jpg (ripe palay heads),
/images/site/photos/transplant.jpg (transplanting seedlings),
/images/site/photos/hero-planting.jpg (planting by hand),
/images/site/photos/inspect.jpg (farmer inspecting a crop),
/images/site/photos/storm-paddies.jpg (storm over paddies),
/images/site/photos/sacks.jpg (sacks of harvest),
/images/site/photos/sacks-shed.jpg (sacks in a shed),
/images/site/photos/farmer-hijab.jpg (a farmer in the field),
/images/site/photos/palay-phone.jpg (farmer using a phone in the field),
/images/site/lp/tractor.webp (tractor in a field), /images/site/lp/weather.webp,
/images/site/app/board.png, /images/site/app/growth.png,
/images/site/app/weather.png, /images/site/app/notes.png,
/images/site/app/dashboard.png, /images/site/app/community.png (app screens),
/images/site/lp/anee-chat-hand.webp (chatting with Anee on a phone),
/images/site/lp/report-money.webp, /images/site/lp/report-top.webp (reports).

## 7. What anee.io really does (only claim these)

anee.io is a mobile friendly web app for Filipino farmers to run a cropping
season (a "cropping schedule"). It has:

- Cropping schedules and the Activities board: the whole season from land
  preparation to harvest as a calendar; every task dated from each lot's own
  day zero (DAS, DAT or DAP counts: days after sowing, transplanting or
  planting); tasks, irrigation, hired services, payroll days and reminders;
  drag to reschedule; drafts and versions; undo.
- Lots: each lot's size, crop and variety, location, day count, a pin and a
  map; delay counter when a lot runs behind.
- Workers: roster with daily rates, assigning hands to activities, payroll
  days, attendance, worker logins with per module permissions (none, view,
  edit), a 6 AM morning email of the day's plan, a logs diary of changes.
- Materials and Inventory: items and stock moves (fertilizer, seeds,
  chemicals), costs, what each activity used, an audit trail.
- Growth stages for 85 Philippine crops (palay, mais, vegetables, fruit trees,
  annuals and perennials): pick a date and see each lot's stage, what to do
  and what to watch for.
- Weather: the week's forecast per lot location; When to Plant Analysis and
  What to Plant Analysis for your town; Variety Research and Comparison; Crop
  Protocol Analysis.
- Anee, the AI technician: answers in Tagalog or English, reads your
  schedules, stages and weather first, can look at a photo of a pest or a sick
  plant, and can write a full season report. Runs on credits.
- Protocol Builder: plan a whole crop protocol (tasks, materials, rates,
  rules) ahead of time, save versions, import it as a new cropping schedule,
  or have Anee review it.
- Notes, photos, videos and voice notes; a drawing pad; Maps to draw and
  measure fields over satellite imagery; a gallery; tags.
- Reports: labor, expenses, profit, yield and post harvest observations
  (buyers, prices); Compare Reports season against season.
- Community: news feed, discussion rooms, direct messages, co-farmers, a
  ranking ladder; a team Collab Room per season with chat and whiteboard.
- Offline mode for the field (paid plans), a Contact List, Global Notes.
- Plans: Libre (free, with ads), Libre plus Anee, Solo Farmer, Farm Owner;
  pay by GCash or bank transfer in the Philippines. Sign up at /signup.

## 8. URL map (link only to these)

Site: / (home), /features, /pricing, /about, /tutorial, /contact, /signup,
/crops, /problems, /pests, /diseases, /weeds, /land-preparation, /blog

Features:
/features/ai-agricultural-technician, /features/cropping-calendar,
/features/farm-workers-and-payroll, /features/farm-inventory-and-expenses,
/features/farm-reports, /features/growth-stages-and-weather,
/features/when-to-plant-analysis, /features/farm-maps,
/features/notes-photos-and-voice, /features/farmer-community,
/features/protocol-builder,
/features/what-to-plant-analysis, /features/variety-research,
/features/crop-protocol-analysis, /features/farm-drawing,
/features/protocol-review-by-anee, /features/farm-lots,
/features/team-logins, /features/collab-room, /features/morning-plan-email,
/features/automatic-stock-deduction, /features/farm-weather-forecast,
/features/realign-by-anee, /features/daily-farm-tasks,
/features/offline-farm-app, /features/farm-tip-of-the-day,
/features/analyze-so-far, /features/crop-problem-guides,
/features/harvest-records, /features/farm-contact-list,
/features/farm-documentation, /features/farm-photo-gallery,
/features/labor-report, /features/expenses-report, /features/profit-report,
/features/anee-season-report, /features/compare-reports,
/features/view-as-protocol

Crops:
/crops/palay, /crops/pagtatanim-ng-palay, /crops/rice-varieties-philippines,
/crops/corn-kernel, /crops/corn-seeds, /crops/pagtatanim-ng-mais,
/crops/mais-rice, /crops/vegetables-philippines, /crops/pagtatanim-ng-gulay,
/crops/coconut-fertilizer, /crops/banana-farming-philippines,
/crops/pagtatanim-ng-puno

Land preparation (by crop, and for problem soils):
/land-preparation/land-preparation-philippines,
/land-preparation/rice-land-preparation,
/land-preparation/direct-seeded-rice-land-preparation,
/land-preparation/corn-land-preparation,
/land-preparation/vegetable-land-preparation,
/land-preparation/onion-garlic-land-preparation,
/land-preparation/cucurbit-land-preparation,
/land-preparation/root-crop-land-preparation,
/land-preparation/legume-land-preparation,
/land-preparation/sugarcane-land-preparation,
/land-preparation/banana-land-preparation,
/land-preparation/fruit-tree-land-preparation,
/land-preparation/sodic-alkaline-soil-preparation,
/land-preparation/acidic-soil-preparation,
/land-preparation/saline-soil-preparation

Pests (guides):
/pests/rice-insects, /pests/hanip-mites-and-aphids,
/pests/crop-pests-philippines, /pests/integrated-pest-management

Pests (the catalogue, one profile per pest):
/pests/asian-corn-borer, /pests/banana-aphid, /pests/banana-weevil,
/pests/bean-fly, /pests/brown-planthopper, /pests/cabbage-webworm,
/pests/cacao-pod-borer, /pests/cocolisap, /pests/coconut-leaf-beetle,
/pests/coconut-rhinoceros-beetle, /pests/coffee-berry-borer,
/pests/corn-earworm, /pests/corn-planthopper, /pests/cutworm,
/pests/diamondback-moth, /pests/eggplant-fruit-and-shoot-borer,
/pests/eggplant-leafhopper, /pests/fall-armyworm,
/pests/golden-apple-snail, /pests/green-leafhopper, /pests/leafminer,
/pests/legume-pod-borer, /pests/mango-cecid-fly, /pests/mango-leafhopper,
/pests/mango-pulp-weevil, /pests/mealybug, /pests/melon-fly,
/pests/mole-cricket, /pests/onion-armyworm, /pests/oriental-fruit-fly,
/pests/red-palm-weevil, /pests/rice-armyworm, /pests/rice-birds,
/pests/rice-black-bug, /pests/rice-bug, /pests/rice-caseworm,
/pests/rice-field-rats, /pests/rice-hispa,
/pests/rice-leaffolder, /pests/rice-stem-borer, /pests/rice-whorl-maggot,
/pests/squash-beetle, /pests/thrips, /pests/white-grub,
/pests/whitebacked-planthopper, /pests/whitefly

Diseases (guides):
/diseases/plant-diseases-philippines, /diseases/rice-diseases

Diseases (the catalogue, one profile per disease):
/diseases/anthracnose, /diseases/bacterial-leaf-blight,
/diseases/bacterial-leaf-streak, /diseases/bacterial-wilt,
/diseases/bakanae, /diseases/banana-bunchy-top,
/diseases/banded-leaf-and-sheath-blight, /diseases/black-rot,
/diseases/black-sigatoka, /diseases/cacao-black-pod,
/diseases/cadang-cadang, /diseases/cercospora-leaf-spot,
/diseases/citrus-greening, /diseases/clubroot, /diseases/coconut-bud-rot,
/diseases/coffee-leaf-rust, /diseases/corn-downy-mildew,
/diseases/corn-ear-rot, /diseases/corn-rust, /diseases/corn-stalk-rot,
/diseases/damping-off, /diseases/downy-mildew, /diseases/early-blight,
/diseases/fusarium-wilt, /diseases/late-blight, /diseases/moko-disease,
/diseases/narrow-brown-leaf-spot, /diseases/northern-corn-leaf-blight,
/diseases/papaya-ringspot, /diseases/powdery-mildew,
/diseases/purple-blotch, /diseases/rice-blast, /diseases/rice-brown-spot,
/diseases/rice-false-smut, /diseases/rice-grassy-stunt,
/diseases/rice-ragged-stunt, /diseases/rice-sheath-rot,
/diseases/rice-stem-rot, /diseases/rice-tungro,
/diseases/root-knot-nematode, /diseases/sheath-blight,
/diseases/soft-rot, /diseases/tomato-leaf-curl

Weeds (guides):
/weeds/weed-management-in-rice, /weeds/herbicides-for-rice-weeds,
/weeds/types-of-weeds, /weeds/common-weeds-philippines

Weeds (the catalogue, one profile per weed of rice):
/weeds/alyce-clover, /weeds/ammannia-baccifera,
/weeds/asian-spiderflower, /weeds/balloon-vine, /weeds/barnyard-grass,
/weeds/basilicum-polystachyon, /weeds/benghal-dayflower,
/weeds/bermuda-grass, /weeds/carabao-grass, /weeds/chamber-bitter,
/weeds/chinese-sprangletop, /weeds/climbing-dayflower,
/weeds/corchorus-aestuans, /weeds/creeping-water-primrose,
/weeds/crowfoot-grass, /weeds/cutleaf-groundcherry,
/weeds/cyperus-compactus, /weeds/cyperus-compressus,
/weeds/cyperus-digitatus, /weeds/cyperus-distans, /weeds/cyperus-haspan,
/weeds/cyperus-imbricatus, /weeds/doveweed,
/weeds/echinochloa-glabrescens, /weeds/eclipta-zippeliana,
/weeds/false-daisy, /weeds/forked-fimbry, /weeds/fringed-spiderflower,
/weeds/giant-bulrush, /weeds/giant-salvinia,
/weeds/giant-sensitive-plant, /weeds/globe-fringerush, /weeds/goosegrass,
/weeds/gooseweed, /weeds/hedyotis-biflora, /weeds/hedyotis-corymbosa,
/weeds/hedyotis-diffusa, /weeds/horse-purslane,
/weeds/hydrolea-zeylanica, /weeds/indian-heliotrope,
/weeds/indian-jointvetch, /weeds/ischaemum-rugosum, /weeds/jungle-rice,
/weeds/kangkong-weed, /weeds/knotgrass, /weeds/lindernia-antipoda,
/weeds/lindernia-procumbens, /weeds/littlebell,
/weeds/ludwigia-decurrens, /weeds/ludwigia-hyssopifolia,
/weeds/ludwigia-octovalvis, /weeds/ludwigia-perennis, /weeds/makahiya,
/weeds/malachra-capitata, /weeds/malachra-fasciata,
/weeds/melochia-concatenata, /weeds/merremia-emarginata,
/weeds/monochoria-vaginalis, /weeds/paspalum-scrobiculatum,
/weeds/phyllanthus-debilis, /weeds/purple-nutsedge, /weeds/purslane,
/weeds/rice-flatsedge, /weeds/saluyot-weed, /weeds/scirpus-juncoides,
/weeds/sessile-joyweed, /weeds/slender-amaranth,
/weeds/smallflower-umbrella-sedge, /weeds/southern-crabgrass,
/weeds/southern-cutgrass, /weeds/sphaeranthus-africanus,
/weeds/spiny-amaranth, /weeds/texasweed, /weeds/torpedo-grass,
/weeds/valley-redstem, /weeds/water-clover, /weeds/water-hyacinth,
/weeds/water-lettuce, /weeds/weedy-rice, /weeds/wild-bushbean,
/weeds/yellow-velvetleaf

Blog:
/blog/urea-fertilizer, /blog/complete-fertilizer-14-14-14,
/blog/16-20-0-fertilizer, /blog/ammonium-sulfate-21-0-0,
/blog/potash-fertilizer, /blog/foliar-fertilizer,
/blog/organic-fertilizer-examples, /blog/inorganic-fertilizer-examples,
/blog/fertilizer-calculation, /blog/fertilizer-application-methods,
/blog/fertilizer-for-rice, /blog/fertilizer-for-plants,
/blog/fertilizer-brands-and-dealers, /blog/fertilizer-and-pesticide-authority,
/blog/rice-insecticides-alika-virtako-chess,
/blog/karate-cymbush-selecron-insecticides, /blog/fungicides,
/blog/ant-bait-and-termiticides, /blog/alamat-ng-palay, /blog/alamat-ng-mais,
/blog/farm-words-in-tagalog, /blog/palay-price-philippines,
/blog/palayan-nueva-ecija, /blog/ani-meaning,
/blog/agriculture-in-the-philippine-economy, /blog/agricultural-engineer-career

## 9. Keywords per page

`keywords_by_page.json` lists, for every page, the keywords from the owner's
keyword research that must appear on it. The one marked `*` is a keyword this
page is the main home for; the rest are keywords whose main home is another
page but which also belong here. Every listed keyword must appear on the page
at least once, naturally and grammatically: all of its words inside one
sentence, a heading, a list item or an FAQ answer (word order may change so
the sentence reads right, e.g. "fertilizer urea" as "urea fertilizer"). Put
the page's own keywords (and the focus keyphrase) in the title, headings and
first paragraph; put the others where they fit, usually with a link to their
main page. `python database/site-pages/check.py --coverage` shows which
keywords are still missing across the site.

Put the page's `keywords` field to the list of keywords you used.
