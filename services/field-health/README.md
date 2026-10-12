# anee.io field health service

FastAPI + Google Earth Engine. Serves Satellite Analysis (Sentinel-2 NDVI,
Sentinel-1 VV/VH, heatmap tile URLs, 90 day history) and Satellite Weather
(storm tracks from GDACS). anee.io calls it from its jobs; farmers never call
it directly.

## Endpoints (all /api calls need `X-Service-Token: $FIELD_HEALTH_TOKEN`)

| Method | Path | What |
|---|---|---|
| GET | `/health` | liveness, and whether Earth Engine signed in |
| GET | `/api/field-health?polygon=<GeoJSON>&days=10` | the field's latest look |
| POST | `/api/field-health` `{polygon, days}` | the same, for big polygons |
| POST | `/api/tiles` `{polygon, s2Id, s1Id}` | fresh tile URLs for a saved report |
| GET | `/api/storms?lat=&lng=` | active tropical cyclones, track, cone, distance |
| POST | `/api/batch/run` | the daily tile batch (Cloud Scheduler only) |

Times come back in UTC and Philippine time (UTC+8). When typhoon cloud hides
the field from Sentinel-2 for the whole window, `mode` is `radar-only` and the
crop is read from Sentinel-1 alone.

## One time setup (Google Cloud)

1. A Google Cloud project with the **Earth Engine API** enabled, and the
   project **registered for Earth Engine**. anee.io is commercial, so it needs
   a commercial Earth Engine plan (noncommercial registration is not allowed
   for a paid app).
2. A **service account** in that project with the role *Earth Engine Resource
   Viewer* (and *Service Usage Consumer*). Create a JSON key for it.
3. Deploy to **Cloud Run** (from this folder):

   ```
   gcloud run deploy anee-field-health --source . --region asia-southeast1 \
     --allow-unauthenticated \
     --set-env-vars EE_PROJECT=<project-id>,FIELD_HEALTH_TOKEN=<long-random-secret> \
     --set-secrets EE_SERVICE_ACCOUNT_JSON=<secret-name>:latest
   ```

   (Put the key JSON in Secret Manager as `<secret-name>`; never commit it.)
4. On anee.io (Laravel Cloud environment): `FIELD_HEALTH_URL=<the Cloud Run URL>`
   and `FIELD_HEALTH_TOKEN=<the same secret>`. `/deploy-check` then shows
   `fieldHealth.configured: true`.

## Local run

```
pip install -r requirements.txt
set EE_SERVICE_ACCOUNT_JSON=C:\path\key.json
set FIELD_HEALTH_TOKEN=dev
uvicorn main:app --reload --port 8088
```

## The daily tile batch: never render live (2026-10-12)

A map that asks Earth Engine for tiles every time a farmer opens it pays
Earth Engine for every view. So the maps read **static PNG tiles**, drawn
once a day and only for fields that paying farms keep:

1. **Cloud Scheduler** calls `POST /api/batch/run` once a day (03:00 Manila).
2. The service asks anee.io which fields to draw (`ANEE_FIELDS_URL`, answered
   only to `X-Service-Token`): fields from Satellite Analyses of farms on a
   paid plan, read in the last 120 days, each field once. No ocean, no city,
   no field nobody pays for.
3. For each field it finds the newest clear Sentinel-2 look. If the bucket
   already has that look, it stops there (no new picture, no work). If not,
   it draws the NDVI and true colour tiles, zoom 13 to 17 over the field's
   box (at most 600 per field), and saves them to the bucket as
   `tiles/<field>/<date>/<layer>/<z>/<x>/<y>.png` (immutable), with
   `tiles/<field>/latest.json` naming the newest date.
4. **Cloudflare** sits in front of the bucket as `tiles.anee.io`, so every view
   after the first is served from Cloudflare's cache: no Earth Engine work and
   no Google egress for it.
5. anee.io reads saved reports' maps from there when `FIELD_TILES_CDN` is set
   (`SatelliteController::tiles`); a field the batch has not drawn yet falls
   back to the old path.

### Setting it up

```
# the bucket, named for the hostname Cloudflare will serve
gcloud storage buckets create gs://tiles.anee.io --location=asia-southeast1 --uniform-bucket-level-access
gcloud storage buckets add-iam-policy-binding gs://tiles.anee.io --member=allUsers --role=roles/storage.objectViewer
# the service account the Cloud Run service runs as may write to it
gcloud storage buckets add-iam-policy-binding gs://tiles.anee.io --member=serviceAccount:<run-sa> --role=roles/storage.objectAdmin

# the service, with the batch's settings
gcloud run services update anee-field-health --region asia-southeast1 --timeout 3600 \
  --update-env-vars TILES_BUCKET=tiles.anee.io,ANEE_FIELDS_URL=https://anee.io/svc/field-batch

# once a day
gcloud scheduler jobs create http anee-tile-batch --location asia-southeast1 \
  --schedule "0 3 * * *" --time-zone "Asia/Manila" \
  --uri https://<the Cloud Run URL>/api/batch/run --http-method POST \
  --headers X-Service-Token=<FIELD_HEALTH_TOKEN> --attempt-deadline 1800s
```

Cloudflare: add a proxied CNAME `tiles` to `c.storage.googleapis.com`, and a
Cache Rule for `tiles.anee.io/*`: *Eligible for cache*, Edge TTL *respect
origin* (the PNGs say a year, `latest.json` an hour). Then on anee.io set
`FIELD_TILES_CDN=https://tiles.anee.io`.

Today anee.io reads Sentinel from Microsoft's Planetary Computer, whose tiles
are drawn and served by Microsoft at no cost to us, so none of this runs until
Earth Engine is switched on (`FIELD_HEALTH_URL`). The batch is ready for that day.
