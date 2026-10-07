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
