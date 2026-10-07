"""anee.io field health service (Satellite Analysis and Satellite Weather).

A small FastAPI app that the Laravel app (anee.io) calls from its jobs:

    GET  /health                       liveness, and whether Earth Engine is up
    GET  /api/field-health?polygon=... the field's latest Sentinel-2 NDVI and
    POST /api/field-health             Sentinel-1 radar, with tile URLs
    POST /api/tiles                    fresh tile URLs for images read before
    GET  /api/storms                   active tropical cyclones near a point

Every /api call needs the header X-Service-Token = FIELD_HEALTH_TOKEN.

Earth Engine signs in with a Google Cloud service account, so the service runs
unattended (no browser sign in):

    EE_SERVICE_ACCOUNT_JSON   the key file's JSON text, or a path to the file
    EE_PROJECT                the Cloud project registered for Earth Engine
                              (defaults to the key's own project_id)
    FIELD_HEALTH_TOKEN        the shared secret anee.io sends

Times are said twice: UTC as Earth Engine records them, and Philippine time
(UTC+8), which is what a farmer reads.
"""
from __future__ import annotations

import datetime as dt
import json
import math
import os
import threading
import time
import urllib.request
from typing import Any

from fastapi import Body, FastAPI, Header, HTTPException, Query
from fastapi.responses import JSONResponse

import ee

PH = dt.timezone(dt.timedelta(hours=8))
S2 = 'COPERNICUS/S2_SR_HARMONIZED'
S1 = 'COPERNICUS/S1_GRD'
MAX_CLOUD = 20            # scene cloud cover, percent (the brief's strict limit)
MIN_CLEAR = 0.6           # share of the FIELD's own pixels that must be clear
NDVI_VIS = {'min': 0.0, 'max': 0.9, 'palette': ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee08b',
                                                 '#d9ef8b', '#a6d96a', '#66bd63', '#1a9850', '#006837']}
RGB_VIS = {'bands': ['B4', 'B3', 'B2'], 'min': 0, 'max': 3000, 'gamma': 1.2}
SAR_VIS = {'bands': ['VV', 'VH', 'VVminusVH'], 'min': [-22, -28, 2], 'max': [0, -8, 14]}

app = FastAPI(title='anee.io field health', version='1.0')
_ee_ready = {'ok': False, 'error': None}
_ee_lock = threading.Lock()


# --------------------------------------------------------------------- auth

def _init_ee() -> None:
    """ee.Initialize with the service account, once per process."""
    with _ee_lock:
        if _ee_ready['ok']:
            return
        raw = os.environ.get('EE_SERVICE_ACCOUNT_JSON', '').strip()
        if not raw:
            raise RuntimeError('EE_SERVICE_ACCOUNT_JSON is not set')
        info = json.loads(raw) if raw.startswith('{') else json.load(open(raw, encoding='utf-8'))
        creds = ee.ServiceAccountCredentials(info['client_email'], key_data=json.dumps(info))
        ee.Initialize(creds, project=os.environ.get('EE_PROJECT') or info.get('project_id'))
        _ee_ready.update(ok=True, error=None)


def _guard(token: str | None) -> None:
    want = os.environ.get('FIELD_HEALTH_TOKEN', '')
    if not want or token != want:
        raise HTTPException(status_code=401, detail='bad token')


# ------------------------------------------------------------------ helpers

def _geometry(polygon: Any) -> ee.Geometry:
    """A GeoJSON Polygon, MultiPolygon, Feature or FeatureCollection."""
    g = json.loads(polygon) if isinstance(polygon, str) else polygon
    if not isinstance(g, dict):
        raise HTTPException(status_code=422, detail='polygon must be GeoJSON')
    if g.get('type') == 'FeatureCollection':
        g = (g.get('features') or [{}])[0]
    if g.get('type') == 'Feature':
        g = g.get('geometry') or {}
    if g.get('type') not in ('Polygon', 'MultiPolygon'):
        raise HTTPException(status_code=422, detail='polygon must be a Polygon')
    return ee.Geometry(g, None, False)


def _times(ms: float | int | None) -> dict:
    if not ms:
        return {'utc': None, 'ph': None, 'phText': None, 'ageDays': None}
    t = dt.datetime.fromtimestamp(ms / 1000, tz=dt.timezone.utc)
    p = t.astimezone(PH)
    return {
        'utc': t.strftime('%Y-%m-%dT%H:%M:%SZ'),
        'ph': p.isoformat(timespec='seconds'),
        'phText': p.strftime('%B %-d, %Y, %-I:%M %p') if os.name != 'nt' else p.strftime('%B %d, %Y, %I:%M %p'),
        'ageDays': round((dt.datetime.now(dt.timezone.utc) - t).total_seconds() / 86400, 1),
    }


def _r(v: Any, n: int = 3) -> float | None:
    try:
        return None if v is None else round(float(v), n)
    except (TypeError, ValueError):
        return None


def _tile(image: ee.Image, vis: dict) -> str:
    return image.getMapId(vis)['tile_fetcher'].url_format


def _mask_s2(img: ee.Image) -> ee.Image:
    """Strict cloud mask: the scene classification (shadow, cloud medium and
    high probability, cirrus, snow) and the QA60 cloud and cirrus bits."""
    scl = img.select('SCL')
    clear = scl.neq(3).And(scl.neq(8)).And(scl.neq(9)).And(scl.neq(10)).And(scl.neq(11)).And(scl.neq(1))
    qa = img.select('QA60')
    clear = clear.And(qa.bitwiseAnd(1 << 10).eq(0)).And(qa.bitwiseAnd(1 << 11).eq(0))
    return img.updateMask(clear)


def _with_clear(geom: ee.Geometry):
    """Each image tagged with the share of the field's own pixels left clear."""
    def tag(img):
        # Every pixel of the field, whether or not this image covers it: a
        # scene that clips the field's edge counts as not clear there.
        total = ee.Image.constant(1).rename('B4').reduceRegion(ee.Reducer.count(), geom, 20, maxPixels=1e9).get('B4')
        kept = _mask_s2(img).select('B4').reduceRegion(ee.Reducer.count(), geom, 20, maxPixels=1e9).get('B4')
        share = ee.Number(kept).divide(ee.Number(total).max(1))
        return img.set('fieldClear', share)
    return tag


def _zones(image: ee.Image, band: str, geom: ee.Geometry) -> list:
    """The field cut into a 3 by 3 grid over its bounds: each cell's mean, so
    the report can say where the weak part is (north, south east...)."""
    b = geom.bounds().coordinates().get(0).getInfo()
    xs = [p[0] for p in b]
    ys = [p[1] for p in b]
    x0, x1, y0, y1 = min(xs), max(xs), min(ys), max(ys)
    feats = []
    for r in range(3):
        for c in range(3):
            cell = ee.Geometry.Rectangle([x0 + (x1 - x0) * c / 3, y1 - (y1 - y0) * (r + 1) / 3,
                                          x0 + (x1 - x0) * (c + 1) / 3, y1 - (y1 - y0) * r / 3]).intersection(geom, 1)
            feats.append(ee.Feature(cell, {'row': r, 'col': c}))
    fc = image.select(band).reduceRegions(ee.FeatureCollection(feats), ee.Reducer.mean(), 10).getInfo()
    names = [['north west', 'north', 'north east'], ['west', 'center', 'east'], ['south west', 'south', 'south east']]
    out = []
    for f in fc.get('features', []):
        p = f.get('properties', {})
        if p.get('mean') is not None:
            out.append({'row': p['row'], 'col': p['col'], 'where': names[p['row']][p['col']], 'mean': _r(p['mean'])})
    return out


# ------------------------------------------------------------ the analysis

def _sentinel2(geom: ee.Geometry, days: int) -> dict:
    end = ee.Date(int(time.time() * 1000))
    col = (ee.ImageCollection(S2).filterBounds(geom).filterDate(end.advance(-days, 'day'), end)
           .filter(ee.Filter.lt('CLOUDY_PIXEL_PERCENTAGE', MAX_CLOUD))
           .map(_with_clear(geom)).filter(ee.Filter.gte('fieldClear', MIN_CLEAR))
           .sort('system:time_start', False))
    if col.size().getInfo() == 0:
        # Typhoon cloud: no clear look inside the window. Say when the last
        # clear one was (up to 45 days back), but use the radar for now.
        last = (ee.ImageCollection(S2).filterBounds(geom).filterDate(end.advance(-45, 'day'), end)
                .filter(ee.Filter.lt('CLOUDY_PIXEL_PERCENTAGE', MAX_CLOUD)).map(_with_clear(geom))
                .filter(ee.Filter.gte('fieldClear', MIN_CLEAR)).sort('system:time_start', False))
        seen = last.first().get('system:time_start').getInfo() if last.size().getInfo() else None
        return {'available': False, 'blockedByCloud': True, 'lastClear': _times(seen), 'windowDays': days}

    img = ee.Image(col.first())
    ndvi = _mask_s2(img).normalizedDifference(['B8', 'B4']).rename('NDVI')
    stats = ndvi.reduceRegion(
        ee.Reducer.mean().combine(ee.Reducer.stdDev(), '', True).combine(ee.Reducer.minMax(), '', True)
        .combine(ee.Reducer.percentile([10, 50, 90]), '', True),
        geom, 10, maxPixels=1e9, bestEffort=True).getInfo()
    p50 = stats.get('NDVI_p50') or 0
    low = ndvi.lt(max(0.0, p50 - 0.1)).reduceRegion(ee.Reducer.mean(), geom, 10, maxPixels=1e9, bestEffort=True).get('NDVI').getInfo()
    info = img.toDictionary(['system:time_start', 'CLOUDY_PIXEL_PERCENTAGE', 'fieldClear', 'MGRS_TILE', 'SPACECRAFT_NAME']).getInfo()
    clipped = ndvi.clip(geom)
    return {
        'available': True,
        'imageId': img.get('system:index').getInfo(),
        'satellite': info.get('SPACECRAFT_NAME'),
        'time': _times(info.get('system:time_start')),
        'sceneCloud': _r(info.get('CLOUDY_PIXEL_PERCENTAGE'), 1),
        'fieldClear': _r(info.get('fieldClear'), 2),
        'ndvi': {
            'mean': _r(stats.get('NDVI_mean')), 'stdDev': _r(stats.get('NDVI_stdDev')),
            'min': _r(stats.get('NDVI_min')), 'max': _r(stats.get('NDVI_max')),
            'p10': _r(stats.get('NDVI_p10')), 'p50': _r(stats.get('NDVI_p50')), 'p90': _r(stats.get('NDVI_p90')),
            'lowShare': _r(low, 3),
        },
        'zones': _zones(ndvi, 'NDVI', geom),
        'tiles': {'ndvi': _tile(clipped, NDVI_VIS), 'rgb': _tile(img.clip(geom.buffer(150)), RGB_VIS)},
        'windowDays': days,
    }


def _sentinel1(geom: ee.Geometry, days: int) -> dict:
    end = ee.Date(int(time.time() * 1000))

    def pick(window):
        return (ee.ImageCollection(S1).filterBounds(geom).filterDate(end.advance(-window, 'day'), end)
                .filter(ee.Filter.eq('instrumentMode', 'IW'))
                .filter(ee.Filter.listContains('transmitterReceiverPolarisation', 'VV'))
                .filter(ee.Filter.listContains('transmitterReceiverPolarisation', 'VH'))
                .sort('system:time_start', False))

    col, window = pick(days), days
    if col.size().getInfo() == 0:
        # Sentinel-1 passes a place every 6 to 12 days: look a little further.
        col, window = pick(max(days, 24)), max(days, 24)
        if col.size().getInfo() == 0:
            return {'available': False, 'windowDays': window}
    img = ee.Image(col.first())
    sar = img.select(['VV', 'VH'])
    sar = sar.addBands(sar.select('VV').subtract(sar.select('VH')).rename('VVminusVH'))
    stats = sar.reduceRegion(ee.Reducer.mean().combine(ee.Reducer.stdDev(), '', True), geom, 10,
                             maxPixels=1e9, bestEffort=True).getInfo()
    info = img.toDictionary(['system:time_start', 'orbitProperties_pass', 'platform_number', 'relativeOrbitNumber_start']).getInfo()
    vv, vh = stats.get('VV_mean'), stats.get('VH_mean')
    # Linear cross ratio VH/VV: rises with leafy biomass in most crops.
    ratio = (10 ** (vh / 10)) / (10 ** (vv / 10)) if vv is not None and vh is not None else None
    return {
        'available': True,
        'imageId': img.get('system:index').getInfo(),
        'satellite': 'Sentinel-1' + (info.get('platform_number') or ''),
        'pass': info.get('orbitProperties_pass'),
        'time': _times(info.get('system:time_start')),
        'vvDb': _r(vv, 2), 'vhDb': _r(vh, 2), 'vvStdDb': _r(stats.get('VV_stdDev'), 2), 'vhStdDb': _r(stats.get('VH_stdDev'), 2),
        'vvMinusVhDb': _r(stats.get('VVminusVH_mean'), 2), 'vhVvRatio': _r(ratio, 3),
        'zones': _zones(sar, 'VH', geom),
        'tiles': {'sar': _tile(sar.clip(geom.buffer(150)), SAR_VIS)},
        'windowDays': window,
    }


def _series(geom: ee.Geometry, days: int = 90) -> dict:
    """The field's last three months: NDVI from every clear Sentinel-2 look,
    VV and VH from every Sentinel-1 pass."""
    end = ee.Date(int(time.time() * 1000))
    start = end.advance(-days, 'day')

    def s2_mean(img):
        v = _mask_s2(img).normalizedDifference(['B8', 'B4']).reduceRegion(ee.Reducer.mean(), geom, 10, maxPixels=1e9, bestEffort=True).get('nd')
        return ee.Feature(None, {'t': img.get('system:time_start'), 'ndvi': v})

    def s1_mean(img):
        v = img.select(['VV', 'VH']).reduceRegion(ee.Reducer.mean(), geom, 10, maxPixels=1e9, bestEffort=True)
        return ee.Feature(None, {'t': img.get('system:time_start'), 'vv': v.get('VV'), 'vh': v.get('VH')})

    s2 = (ee.ImageCollection(S2).filterBounds(geom).filterDate(start, end).filter(ee.Filter.lt('CLOUDY_PIXEL_PERCENTAGE', 60))
          .map(_with_clear(geom)).filter(ee.Filter.gte('fieldClear', MIN_CLEAR)).map(s2_mean)).getInfo()
    s1 = (ee.ImageCollection(S1).filterBounds(geom).filterDate(start, end).filter(ee.Filter.eq('instrumentMode', 'IW'))
          .filter(ee.Filter.listContains('transmitterReceiverPolarisation', 'VH')).map(s1_mean)).getInfo()

    def rows(fc, keys):
        out = {}
        for f in fc.get('features', []):
            p = f.get('properties', {})
            if any(p.get(k) is None for k in keys):
                continue
            day = _times(p['t'])['ph'][:10]
            out[day] = {'date': day, **{k: _r(p[k], 3) for k in keys}}
        return sorted(out.values(), key=lambda r: r['date'])

    return {'ndvi': rows(s2, ['ndvi']), 'radar': rows(s1, ['vv', 'vh']), 'days': days}


# -------------------------------------------------------------------- routes

@app.get('/health')
def health():
    try:
        _init_ee()
    except Exception as e:  # noqa: BLE001
        return {'ok': True, 'earthEngine': False, 'error': str(e)[:200]}
    return {'ok': True, 'earthEngine': True}


def _field_health(polygon: Any, days: int) -> dict:
    _init_ee()
    days = max(5, min(int(days or 10), 30))
    geom = _geometry(polygon)
    area_ha = geom.area(1).divide(10000).getInfo()
    if area_ha > 2000:
        raise HTTPException(status_code=422, detail='field too large (over 2,000 ha)')
    centroid = geom.centroid(1).coordinates().getInfo()
    s2 = _sentinel2(geom, days)
    s1 = _sentinel1(geom, days)
    return {
        'ok': True,
        'generatedAt': _times(int(time.time() * 1000)),
        'areaHa': _r(area_ha, 2),
        'centroid': {'lng': _r(centroid[0], 6), 'lat': _r(centroid[1], 6)},
        'sentinel2': s2,
        'sentinel1': s1,
        # Typhoon cloud over the field: read the crop from radar alone.
        'mode': 'optical+radar' if s2.get('available') and s1.get('available') else ('radar-only' if s1.get('available') else ('optical-only' if s2.get('available') else 'none')),
        'series': _series(geom, 90),
    }


@app.get('/api/field-health')
def field_health_get(polygon: str = Query(..., description='GeoJSON Polygon, URL encoded'), days: int = 10,
                     x_service_token: str | None = Header(None)):
    _guard(x_service_token)
    return _field_health(polygon, days)


@app.post('/api/field-health')
def field_health_post(body: dict = Body(...), x_service_token: str | None = Header(None)):
    _guard(x_service_token)
    return _field_health(body.get('polygon'), body.get('days', 10))


@app.post('/api/tiles')
def tiles(body: dict = Body(...), x_service_token: str | None = Header(None)):
    """Tile URLs expire; a saved report asks again for the same images."""
    _guard(x_service_token)
    _init_ee()
    geom = _geometry(body.get('polygon'))
    out = {}
    if body.get('s2Id'):
        img = ee.Image(S2 + '/' + body['s2Id'])
        out['ndvi'] = _tile(_mask_s2(img).normalizedDifference(['B8', 'B4']).clip(geom), NDVI_VIS)
        out['rgb'] = _tile(img.clip(geom.buffer(150)), RGB_VIS)
    if body.get('s1Id'):
        img = ee.Image(S1 + '/' + body['s1Id']).select(['VV', 'VH'])
        img = img.addBands(img.select('VV').subtract(img.select('VH')).rename('VVminusVH'))
        out['sar'] = _tile(img.clip(geom.buffer(150)), SAR_VIS)
    return {'ok': True, 'tiles': out}


# ------------------------------------------------------------------ storms

GDACS = 'https://www.gdacs.org/gdacsapi/api'


def _get_json(url: str) -> dict:
    req = urllib.request.Request(url, headers={'User-Agent': 'anee.io field health (https://anee.io/contact)'})
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.loads(r.read().decode('utf-8'))


def _km(a_lat, a_lng, b_lat, b_lng) -> float:
    """Haversine, the great circle distance in kilometres."""
    r = 6371.0
    p1, p2 = math.radians(a_lat), math.radians(b_lat)
    dp, dl = math.radians(b_lat - a_lat), math.radians(b_lng - a_lng)
    h = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * r * math.asin(math.sqrt(h))


def storm_tracks(lat: float | None = None, lng: float | None = None, days: int = 10) -> dict:
    """Active tropical cyclones from GDACS: the track (past and forecast
    points with their times), the cone of uncertainty, the current eye."""
    to = dt.datetime.now(dt.timezone.utc)
    frm = to - dt.timedelta(days=days)
    listing = _get_json(GDACS + '/events/geteventlist/SEARCH?eventlist=TC&fromdate=%s&todate=%s'
                        % (frm.strftime('%Y-%m-%d'), to.strftime('%Y-%m-%d')))
    storms = []
    for ev in listing.get('features', []):
        p = ev.get('properties', {})
        current = str(p.get('iscurrent')).lower() == 'true'
        try:
            geo = _get_json(p['url']['geometry'])
        except Exception:  # noqa: BLE001
            continue
        year = int(str(p.get('fromdate', to.year))[:4])
        lines, points, cone = {}, [], None
        for f in geo.get('features', []):
            fp, g = f.get('properties', {}), f.get('geometry', {})
            cls = fp.get('Class', '')
            if cls.startswith('Line_Line_'):
                lines[int(cls.rsplit('_', 1)[1])] = {'coords': g.get('coordinates'), 'forecast': bool(fp.get('forecast')), 'cat': fp.get('polygonlabel')}
            elif cls.startswith('Point_Polygon_Point_'):
                ring = (g.get('coordinates') or [[]])[0]
                if ring:
                    lab = str(fp.get('polygonlabel', ''))
                    try:
                        when = dt.datetime.strptime('%d/%s' % (year, lab.replace(' UTC', '')), '%Y/%d/%m %H:%M').replace(tzinfo=dt.timezone.utc)
                    except ValueError:
                        when = None
                    points.append({'i': int(cls.rsplit('_', 1)[1]), 'lng': sum(c[0] for c in ring) / len(ring),
                                   'lat': sum(c[1] for c in ring) / len(ring), 'utc': when.strftime('%Y-%m-%dT%H:%M:%SZ') if when else None,
                                   'ph': when.astimezone(PH).isoformat(timespec='minutes') if when else None})
            elif cls == 'Poly_Cones':
                cone = g
        points.sort(key=lambda x: x['i'])
        for pt in points:
            seg = lines.get(pt['i']) or lines.get(pt['i'] - 1) or {}
            pt['forecast'] = bool(lines.get(pt['i'], {}).get('forecast'))
            pt['cat'] = seg.get('cat')
        past = [q for q in points if not q['forecast']]
        eye = past[-1] if past else (points[0] if points else None)
        storm = {
            'id': p.get('eventid'), 'episode': p.get('episodeid'), 'name': p.get('eventname') or p.get('name'),
            'alert': p.get('alertlevel'), 'current': current, 'from': p.get('fromdate'), 'to': p.get('todate'),
            'source': p.get('source'), 'maxWindKmh': (p.get('severitydata') or {}).get('severity'),
            'report': (p.get('url') or {}).get('report'),
            'eye': eye, 'points': points, 'cone': cone,
            'track': {'type': 'LineString', 'coordinates': [[q['lng'], q['lat']] for q in points]},
        }
        if lat is not None and lng is not None and points:
            storm['distanceKm'] = round(_km(lat, lng, eye['lat'], eye['lng']), 1) if eye else None
            near = min(points, key=lambda q: _km(lat, lng, q['lat'], q['lng']))
            storm['closest'] = {**near, 'km': round(_km(lat, lng, near['lat'], near['lng']), 1)}
        storms.append(storm)
    storms.sort(key=lambda s: (not s['current'], s.get('distanceKm') or 1e9))
    return {'ok': True, 'source': 'GDACS (European Commission JRC and UN OCHA)', 'storms': storms,
            'checkedAt': _times(int(time.time() * 1000))}


@app.get('/api/storms')
def storms(lat: float | None = None, lng: float | None = None, days: int = 10, x_service_token: str | None = Header(None)):
    _guard(x_service_token)
    return storm_tracks(lat, lng, max(1, min(days, 30)))


@app.exception_handler(ee.EEException)
def ee_error(_request, exc):
    return JSONResponse(status_code=502, content={'ok': False, 'error': 'Earth Engine: ' + str(exc)[:300]})
