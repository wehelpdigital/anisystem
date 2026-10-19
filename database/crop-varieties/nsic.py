"""The NSIC registry spreadsheets (nsic.buplant.da.gov.ph/registry.php, one
xlsx per crop group) read without openpyxl: every sheet as plain records,
{file, sheet, reg, year, name, owner, cols: {label: value}}. A label joins the
header rows, merged group headers filled across their span ("Fruit
Characteristics | Weight (g) | DS"). Used by build_data.py.
"""
import glob
import os
import re
import zipfile
import xml.etree.ElementTree as ET
NS = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
      'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'}


def col_index(ref):
    letters = re.match(r'[A-Z]+', ref).group(0)
    n = 0
    for ch in letters:
        n = n * 26 + (ord(ch) - 64)
    return n - 1


def read(path, merges=False):
    z = zipfile.ZipFile(path)
    shared = []
    if 'xl/sharedStrings.xml' in z.namelist():
        root = ET.fromstring(z.read('xl/sharedStrings.xml'))
        for si in root.findall('m:si', NS):
            shared.append(''.join(t.text or '' for t in si.iter('{%s}t' % NS['m'])))
    wb = ET.fromstring(z.read('xl/workbook.xml'))
    rels = ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))
    target = {r.get('Id'): r.get('Target') for r in rels}
    out = {}
    for sh in wb.find('m:sheets', NS):
        rid = sh.get('{%s}id' % NS['r'])
        t = target[rid].lstrip('/')
        t = t if t.startswith('xl/') else 'xl/' + t
        root = ET.fromstring(z.read(t))
        rows = []
        rowno = []
        for row in root.iter('{%s}row' % NS['m']):
            cells = {}
            for c in row.findall('m:c', NS):
                v = c.find('m:v', NS)
                typ = c.get('t')
                if typ == 's' and v is not None:
                    val = shared[int(v.text)]
                elif typ == 'inlineStr':
                    val = ''.join(x.text or '' for x in c.iter('{%s}t' % NS['m']))
                else:
                    val = v.text if v is not None else ''
                cells[col_index(c.get('r'))] = (val or '').strip()
            if any(cells.values()):
                width = max(cells) + 1
                rows.append([cells.get(i, '') for i in range(width)])
                rowno.append(int(row.get('r')))
        if merges:
            ms = []
            mc = root.find('m:mergeCells', NS)
            for m in (mc if mc is not None else []):
                a, b = m.get('ref').split(':')
                ra, rb = int(re.search(r'\d+', a).group(0)), int(re.search(r'\d+', b).group(0))
                ms.append((ra, col_index(a), rb, col_index(b)))
            out[sh.get('name')] = (rows, rowno, ms)
        else:
            out[sh.get('name')] = rows
    return out

def norm(s):
    s = (s or '').replace('�', 'ñ').replace('\n', ' ')
    return re.sub(r'\s+', ' ', s).strip()


def sheet_records(fname, sheet, rows, rowno, merges):
    # The grid with merged cells filled.
    grid = {}
    for r, rn in zip(rows, rowno):
        for c, v in enumerate(r):
            grid[(rn, c)] = v
    for (ra, ca, rb, cb) in merges:
        v = grid.get((ra, ca), '')
        for rr in range(ra, rb + 1):
            for cc in range(ca, cb + 1):
                if not grid.get((rr, cc)):
                    grid[(rr, cc)] = v
    width = max((len(r) for r in rows), default=0)
    for (ra, ca, rb, cb) in merges:
        width = max(width, cb + 1)
    allrows = sorted(set(rowno) | {ra for (ra, _, rb, _) in merges})

    def cell(rn, c):
        return norm(grid.get((rn, c), ''))

    # The header: the first row naming the variety column.
    head = None
    for rn in rowno:
        if any(re.search(r'variety\s*name|^variety$|name of variety|^cultivar', cell(rn, c), re.I) for c in range(width)):
            head = rn
            break
    if head is None:
        return [], 'no header'
    # Header rows run until the first row that looks like data.
    def looks_data(rn):
        vals = [cell(rn, c) for c in range(width)]
        txt = ' '.join(vals)
        return bool(re.search(r'\bNSIC\b|Philippine Seed Board|\bPSB\b|^\s*\d{4}\s*$', txt)) and not re.search(r'Registration', txt, re.I)
    hrows = [head]
    for rn in allrows:
        if rn <= head:
            continue
        if looks_data(rn) or rn - head > 4:
            break
        hrows.append(rn)
    labels = []
    for c in range(width):
        parts = []
        for rn in hrows:
            v = cell(rn, c)
            if v and (not parts or parts[-1] != v):
                parts.append(v)
        labels.append(' | '.join(parts))

    def find(pat):
        for c, l in enumerate(labels):
            if re.search(pat, l, re.I):
                return c
        return None
    c_name = find(r'variety\s*name|name of variety|^variety|^cultivar')
    c_reg = find(r'registration')
    # The year approved, never a figure counted per year: the cacao sheet has
    # no year column, and its "Yield (kg/tree/year)" once passed for one
    # (2026-10-20: cacao clones "approved" in 2199 and 3110).
    c_year = next((c for c, l in enumerate(labels) if re.search(r'year', l, re.I) and not re.search(r'/\s*year|per\s+year|yield', l, re.I)), None)
    c_owner = find(r'owner|breeder|originator|source')
    c_no = find(r'^no\.?$')
    out = []
    for rn in rowno:
        if rn <= hrows[-1]:
            continue
        name = cell(rn, c_name) if c_name is not None else ''
        reg = cell(rn, c_reg) if c_reg is not None else ''
        year = cell(rn, c_year) if c_year is not None else ''
        if not name or re.search(r'^(abbreviation|legend|note|source)', name, re.I):
            continue
        if not (reg or re.match(r'^\d{4}', year)):
            continue
        if not year:
            m = re.search(r'(19|20)\d\d', reg)
            year = m.group(0) if m else ''
        cols = {}
        for c in range(width):
            if c in (c_name, c_reg, c_year, c_owner, c_no) or not labels[c]:
                continue
            v = cell(rn, c)
            if v:
                cols[labels[c]] = v
        out.append({'file': fname, 'sheet': sheet.strip(), 'reg': reg, 'year': year[:4], 'name': name,
                    'owner': cell(rn, c_owner) if c_owner is not None else '', 'cols': cols})
    return out, 'ok'


def records(folder):
    """Every record of every sheet of every xlsx in the folder."""
    out = []
    for p in sorted(glob.glob(os.path.join(folder, '*.xlsx'))):
        for sheet, (rows, rowno, merges) in read(p, merges=True).items():
            recs, _ = sheet_records(os.path.basename(p), sheet, rows, rowno, merges)
            out += recs
    return out
