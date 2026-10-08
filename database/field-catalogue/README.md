# The field catalogue (2026-10-08)

The pests, diseases and weeds of every crop anee.io knows, and the weed
control plans by crop and age. Read through `App\Support\FieldCatalogue`;
shown by /pests, /diseases and /weeds (catalogues, the finders, the weed
control helper), by the fact sheet of an entry with no profile page yet, and
by the app's Field helpers (/app/field-helpers/{weeds,pests,diseases}).

| file              | table              | what one row is                                   |
|-------------------|--------------------|---------------------------------------------------|
| `problems.json`   | `as_crop_problems` | a pest or disease (`pests` / `diseases` lists)    |
| `weeds.json`      | `as_weeds`         | a weed, with the crops it troubles                |
| `weed-plans.json` | `as_weed_plans`    | one crop group's weed control windows; the shared `ingredients` map gives each active ingredient its HRAC groups |

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
