# anee.io public SEO pages (PH site)

Every page is one JSON file here: `database/site-pages/{section}/{slug}.json`.
`App\Support\SitePages::sync()` loads them into `as_site_pages`; the mother
app (AniSystem > Website pages) edits them after that with a drag and drop
block builder. A file is only re-applied to a page nobody has edited in the
mother app; an edited page keeps its edits, and the mother's "Shipped
version" button puts the file's copy back.

**Shipping changed or new files takes a migration.** The first shipment is
`2026_10_01_100100_site_pages_first_shipment`; a later release that changes
these files adds another migration that calls `SitePages::sync()` (copy that
one), or the change never reaches production. Locally,
`php artisan site-pages:sync` does the same.

Sections and their addresses:

| section  | hub        | page                 |
|----------|------------|----------------------|
| crops    | /crops     | /crops/{slug}        |
| pests    | /pests     | /pests/{slug}        |
| diseases | /diseases  | /diseases/{slug}     |
| weeds    | /weeds     | /weeds/{slug}        |
| blog     | /blog      | /blog/{slug}         |
| features | /features  | /features/{slug}     |

/problems is the front door to pests, diseases and weeds, and an old
/problems/{slug} address moves to the page's new home (2026-10-06).

See `STYLE.md` for the writing rules and the page format, and run
`python database/site-pages/check.py` before committing a page, and
`python database/site-pages/check.py --coverage` to see every keyword of
`keywords.json` (built by `keyword_map.py` from the keyword CSV) and the
pages it really appears on. `sortOrder` in a file places the page in its
section (pages answering the biggest searches first).

Pictures borrowed from Wikimedia Commons are saved under
`public/images/site/guides/` and credited (author, licence) in the page's
`heroImage.credit`.

## Latest in Agriculture (the blog, 2026-10-07)

The blog section is called Latest in Agriculture on the site (the address
stays /blog). Besides the shipped posts here, it carries farm news roundups
that anee.io writes itself every few days from the RSS feeds kept in the
mother app (AniSystem > Latest in Agriculture): App\Services\NewsRoundup,
called by /cron/news-roundup?key=... (the key is the site setting
news.cron_key). Roundups live only in the database (kind = roundup), never
as files here, so a sync never touches them.
