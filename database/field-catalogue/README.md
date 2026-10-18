# The field catalogue (2026-10-08)

The pests, diseases and weeds of every crop anee.io knows, and the weed
control plans by crop and age. Read through `App\Support\FieldCatalogue`;
shown by /pests, /diseases and /weeds (catalogues, the finders, the weed
control helper), by the fact sheet of an entry with no profile page yet, and
by the app's Field helpers (/app/field-helpers/{weeds,pests,diseases}).

| file              | table              | what one row is                                   |
|-------------------|--------------------|---------------------------------------------------|
| `problems.json`   | `as_crop_problems` | a pest or disease (`pests` / `diseases` lists)    |
| `weeds.json`      | `as_weeds`         | a weed, with the crops it troubles, `stop` (how to keep it out without a spray) and `named` (the active ingredients its Philippine labels name, by crop) |
| `weed-plans.json` | `as_weed_plans`    | one crop group's weed control windows; the shared `ingredients` map gives each active ingredient its HRAC groups; each plan has `prevent` and `critical` (each crop's weed free stretch) |

**Shipping a change takes a migration** that calls `FieldCatalogue::sync()`
(copy `2026_10_08_150100_field_catalogue_first_shipment.php`). The sync
writes new and changed rows, retires file rows no longer in the files, and
never touches a row whose `source` is not `file`. Never run it locally: the
local `.env` points at the production database. Test on a SQLite copy.

Until the tables exist (a fresh copy, a test database) the files answer
directly, so the site works either way.

## Rules for the words

- Crop keys are `App\Support\CropCatalog` keys; every rice is `rice` (the
  helper asks how it was planted). `parts`, `signs` and `kind` come from
  `App\Support\ProblemCatalogue`.
- `ranks` puts each crop's worst problems first in the finder
  (`{"sweetpotato": 0}`); problems with no rank for a crop follow in file order.
- Active ingredients only, never brands, never rates; IRAC, FRAC or HRAC
  groups beside them. A note in brackets after a herbicide (`Nicosulfuron
  (yellow corn only)`) is part of its name and shows on the helper.
- No dashes in visible text, plain words, and the banned list of
  `database/site-pages/check.py`. Never name eDamuhan anywhere near /weeds.
- An entry with a profile page (`database/site-pages/{pests,diseases,weeds}/{slug}.json`)
  shows that page; the rest get a short fact sheet, kept out of search
  engines (noindex) until a page is written for them.

## The weed plans and the registrations (2026-10-18)

Every ingredient in `weed-plans.json` is checked against the products
registered for sale in the Philippines (`database/products/pesticides.json`),
crop by crop, by the builder in the session scratchpad (`weedreg/match.py`,
`spec.py`, `build_plans.py`). Each window carries `on` (name => the plan's
crops a current registration names), `abroad` (no product registered here)
and `only` (the crops a name is meant for). Read a window through
`FieldCatalogue::forCrop($window, $crop, $group)`: what is registered for the
crop, or, where nothing is, the rest marked (the helper's JS twin is `pick()`
in `field-helpers/partials/weeds`). A name with no registration here stays
only where nothing registered covers that window. Paraquat (restricted to
institutional use) is not listed. Lists put what Philippine companies sell
first (`database/products/company-origin.json`).

