"""The daily tile batch (2026-10-12): never render live.

Once a day (Cloud Scheduler calls POST /api/batch/run), for every field a
paying farm on anee.io keeps (anee.io lists them at ANEE_FIELDS_URL, and only
those: no ocean, no city, no field nobody pays for):

  1. find the field's newest clear Sentinel-2 look (the same strict rules as a
     report);
  2. if the bucket already holds tiles for that look, stop: no new picture,
     no work;
  3. otherwise draw the NDVI and true colour tiles ONCE, zooms 13 to 17 over
     the field's box, and save them as static PNG files in the Cloud Storage
     bucket (TILES_BUCKET), under tiles/<field>/<date>/<layer>/<z>/<x>/<y>.png,
     marked public and immutable, with tiles/<field>/latest.json saying which
     date is newest.

Cloudflare sits in front of the bucket (tiles.anee.io), so a farmer's map
reads cached PNG files: Earth Engine works once per field per new picture,
and Google is not paid egress for every view. anee.io points its maps at
FIELD_TILES_CDN when it is set (App\\Http\\Controllers\\SatelliteController::tiles).
"""
from __future__ import annotations

import json
import math
import os
import time
import urllib.request
from typing import Any

import ee

ZOOMS = range(13, 18)
MAX_TILES_PER_FIELD = 600     # a field of a few hundred hectares at zoom 17; bigger ones stop at a lower zoom
LAYERS = ('ndvi', 'rgb')


def _bbox(geom: ee.Geometry) -> tuple[float, float, float, float]:
    ring = geom.bounds(1).coordinates().getInfo()[0]
    lngs = [p[0] for p in ring]
    lats = [p[1] for p in ring]
    return min(lngs), min(lats), max(lngs), max(lats)


def _tile_xy(lng: float, lat: float, z: int) -> tuple[int, int]:
    n = 2 ** z
    x = int((lng + 180.0) / 360.0 * n)
    lat_r = math.radians(max(-85.0, min(85.0, lat)))
    y = int((1.0 - math.asinh(math.tan(lat_r)) / math.pi) / 2.0 * n)
    return x, y


def _tiles_over(box: tuple[float, float, float, float]) -> list[tuple[int, int, int]]:
    w, s, e, n = box
    out: list[tuple[int, int, int]] = []
    for z in ZOOMS:
        x0, y0 = _tile_xy(w, n, z)
        x1, y1 = _tile_xy(e, s, z)
        level = [(z, x, y) for x in range(x0, x1 + 1) for y in range(y0, y1 + 1)]
        if len(out) + len(level) > MAX_TILES_PER_FIELD:
            break
        out += level
    return out


def _fields() -> list[dict]:
    url = os.environ.get('ANEE_FIELDS_URL', '').strip()
    if not url:
        raise RuntimeError('ANEE_FIELDS_URL is not set')
    req = urllib.request.Request(url, headers={'X-Service-Token': os.environ.get('FIELD_HEALTH_TOKEN', ''), 'Accept': 'application/json'})
    with urllib.request.urlopen(req, timeout=60) as r:
        return (json.loads(r.read().decode('utf-8')).get('data') or {}).get('fields') or []


def run(newest_clear, mask_s2, ndvi_vis: dict, rgb_vis: dict, geometry) -> dict:
    """The whole batch. The helpers come from main.py so a report and the batch read a field the same way."""
    from google.cloud import storage  # imported here: the API serves without it

    bucket_name = os.environ.get('TILES_BUCKET', '').strip()
    if not bucket_name:
        raise RuntimeError('TILES_BUCKET is not set')
    bucket = storage.Client().bucket(bucket_name)
    started = time.time()
    done, skipped, failed, tiles = [], [], [], 0
    for f in _fields():
        key = str(f.get('key') or '')
        try:
            geom = geometry(f.get('polygon'))
            img = newest_clear(geom)
            if img is None:
                skipped.append({'field': key, 'why': 'no clear look'})
                continue
            image_id = img.get('system:index').getInfo()
            date = image_id[:8] if image_id[:8].isdigit() else time.strftime('%Y%m%d')
            manifest_blob = bucket.blob(f'tiles/{key}/latest.json')
            if manifest_blob.exists():
                old = json.loads(manifest_blob.download_as_text() or '{}')
                if old.get('imageId') == image_id:
                    skipped.append({'field': key, 'why': 'no new picture'})
                    continue
            urls = {
                'ndvi': mask_s2(img).normalizedDifference(['B8', 'B4']).clip(geom).getMapId(ndvi_vis)['tile_fetcher'].url_format,
                'rgb': img.clip(geom.buffer(150)).getMapId(rgb_vis)['tile_fetcher'].url_format,
            }
            box = _bbox(geom.buffer(150))
            grid = _tiles_over(box)
            for layer in LAYERS:
                for z, x, y in grid:
                    src = urls[layer].replace('{z}', str(z)).replace('{x}', str(x)).replace('{y}', str(y))
                    with urllib.request.urlopen(src, timeout=60) as r:
                        png = r.read()
                    blob = bucket.blob(f'tiles/{key}/{date}/{layer}/{z}/{x}/{y}.png')
                    blob.cache_control = 'public, max-age=31536000, immutable'
                    blob.upload_from_string(png, content_type='image/png')
                    tiles += 1
            manifest_blob.cache_control = 'public, max-age=3600'
            manifest_blob.upload_from_string(json.dumps({
                'field': key, 'imageId': image_id, 'date': date, 'layers': list(LAYERS),
                'zooms': sorted({z for z, _, _ in grid}), 'bbox': box, 'madeAt': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()),
            }), content_type='application/json')
            done.append({'field': key, 'date': date, 'tiles': len(grid) * len(LAYERS)})
        except Exception as e:  # noqa: BLE001  one field's failure must not stop the rest
            failed.append({'field': key, 'error': str(e)[:200]})
    return {'ok': True, 'fields': len(done) + len(skipped) + len(failed), 'drawn': done, 'skipped': skipped, 'failed': failed,
            'tiles': tiles, 'seconds': round(time.time() - started, 1)}
