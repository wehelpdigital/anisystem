@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Draw — ' . $schedule->title)
@section('page-title', 'Draw')
@section('page-subtitle', $schedule->title)
@section('help-key', 'draw')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* Cards sized by the picture, not by the text: a drawing is recognised
           at a glance long before its title is read. */
        .dr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .75rem; }
        .dr-card { display: flex; flex-direction: column; overflow: hidden; background: var(--color-white);
            border: 1px solid var(--color-gray-200); border-radius: .85rem; text-align: left;
            transition: box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .dr-card:hover { box-shadow: 0 10px 26px -18px rgb(0 0 0 / .45); transform: translateY(-1px); }
        .dr-thumb { position: relative; aspect-ratio: 4 / 3; background: var(--color-gray-50); overflow: hidden; cursor: pointer; }
        .dr-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: 0;
            transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        .dr-thumb img.is-loaded { opacity: 1; }
        .dr-thumb.is-gone::after { content: 'Picture missing'; position: absolute; inset: 0; display: flex;
            align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; color: #94a3b8; }
        .dr-meta { padding: .5rem .6rem .6rem; display: flex; flex-direction: column; gap: .3rem; }
        .dr-name { font-size: .8rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.25;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .dr-innote { display: inline-flex; align-items: center; gap: .2rem; text-decoration: none; }
        .dr-innote:hover { background: #e4efd4; color: #3d6823; }
        .dr-note { font-size: .7rem; line-height: 1.35; color: var(--color-gray-500);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .dr-when { font-size: .66rem; color: var(--color-gray-400); }
        .dr-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
        .dr-acts { display: flex; gap: .25rem; margin-top: .1rem; }
        .dr-act { display: inline-flex; align-items: center; justify-content: center; width: 1.85rem; height: 1.85rem;
            border-radius: .5rem; color: var(--color-gray-500); background: var(--color-gray-50); cursor: pointer; }
        .dr-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
        .dr-act.is-danger:hover { background: #fee2e2; color: #b91c1c; }
        .dr-act svg { width: 1rem; height: 1rem; }
        /* The way in is a tile of the same size as the drawings, so the grid
           reads as one row of things rather than a button and then a list. */
        .dr-new { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem;
            min-height: 9.5rem; border: 2px dashed var(--color-gray-300); border-radius: .85rem;
            color: var(--color-brand-700); font-weight: 700; font-size: .8rem; background: var(--color-white);
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .dr-new:hover { border-color: var(--color-brand-500); background: var(--color-brand-50); }
        .dr-new svg { width: 1.6rem; height: 1.6rem; }
        .dr-empty { text-align: center; color: var(--color-gray-400); font-size: .8rem; padding: 1.25rem .5rem 0; }
        @media (prefers-reduced-motion: reduce) {
            .dr-card, .dr-thumb img, .dr-new { transition: none; }
        }
    </style>
@endpush

@section('content')
    @include('sm.partials.module-note', [
        'say' => 'Every drawing in this schedule — your own and the team’s. Each one is kept as an attachment on a note, so the tag on a card opens the words that explain it.',
    ])
    <div class="dr-grid" id="drGrid"></div>
    <p class="dr-empty hidden" id="drEmpty">Nothing drawn yet. Start one above — save it as a picture, or as a drawing you can come back and change.</p>

    {{-- The shell that hosts this module already carries the pad and the
         lightbox; a second copy would only duplicate their ids. --}}
    @if (! request()->boolean('partial'))
        @include('sm.partials.draw-canvas')
        @include('sm.partials.note-lightbox')
    @endif

    <div class="sheet hidden" id="drSaveSheet" style="--sheet-width:26rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Name this drawing</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div class="sheet-body" style="padding-bottom:1rem">
            <label class="form-label" for="drTitle">Title <span class="text-red-500">*</span></label>
            <input type="text" id="drTitle" class="form-input" maxlength="191" placeholder="Field sketch">
            {{-- A drawing is a note like any other, and a note nobody can read
                 six weeks later is half a record. What it shows, and why it
                 was worth drawing. --}}
            <label class="form-label mt-3" for="drNote">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="drNote" class="form-input" rows="3" maxlength="2000" placeholder="What this shows, and why it was worth drawing."></textarea>
            <p class="text-xs text-gray-400 mt-1.5" id="drKind"></p>
            <button type="button" class="btn btn-primary w-full mt-3" id="drConfirm">Save drawing</button>
        </div>
    </div>

    <script>
        (() => {
            const SCHEDULE_ID = @json($schedule->id);
            const U = {
                save: @json(route('sm.draw.save')) + '?scheduleId=' + SCHEDULE_ID,
                one: @json(route('sm.draw.one')) + '?scheduleId=' + SCHEDULE_ID,
                destroy: @json(route('sm.draw.destroy')) + '?scheduleId=' + SCHEDULE_ID,
            };
            const esc = window.escapeHtml || ((s) => String(s ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
            let drawings = @json($drawings);
            let pending = null;   // what the pad handed back, waiting for a name
            let askedForOne = false;   // arrived here to see one drawing, from elsewhere

            const grid = document.getElementById('drGrid');
            const empty = document.getElementById('drEmpty');

            const NEW_TILE = '<button type="button" class="dr-new" data-new>'
                + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>'
                + '<span>New drawing</span></button>';

            function card(d, i) {
                const tags = [];
                // Where it came from and what it still is: a drawing that kept
                // its strokes can be reopened, a flat picture can only be drawn
                // over — worth knowing before you tap it.
                if (d.team) tags.push('<span class="badge badge-blue">Team drawing</span>');
                tags.push(d.editable
                    ? '<span class="badge badge-green">Editable</span>'
                    : '<span class="badge badge-gray">Picture</span>');
                // Which note this belongs to. A drawing is never loose — it is
                // an attachment on a note, and the note is where the reason
                // for it is written down.
                if (d.noteHref) {
                    tags.push(`<a class="badge badge-gray dr-innote" href="${esc(d.noteHref)}" title="Open the note this drawing is in">`
                        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:.7rem;height:.7rem"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6L19 9.4V19a2 2 0 01-2 2z"/></svg>'
                        + 'In a note</a>');
                }
                const thumb = d.url
                    ? `<img src="${esc(d.url)}" alt="" loading="lazy" onload="this.classList.add('is-loaded')"`
                        + ` onerror="this.closest('.dr-thumb')?.classList.add('is-gone'); this.remove();">`
                    : '';
                return `<div class="dr-card" data-i="${i}">
                    <div class="dr-thumb" data-open>${thumb}</div>
                    <div class="dr-meta">
                        <span class="dr-name">${esc(d.title || 'Untitled')}</span>
                        ${d.note ? `<span class="dr-note">${esc(d.note)}</span>` : ''}
                        <div class="dr-tags">${tags.join('')}</div>
                        <span class="dr-when">${esc(d.when || '')}</span>
                        <div class="dr-acts">
                            <button type="button" class="dr-act" data-edit title="${d.team ? 'Draw over a copy' : 'Open in the pad'}" aria-label="Edit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg>
                            </button>
                            <button type="button" class="dr-act is-danger" data-del title="Delete" aria-label="Delete">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>`;
            }

            function paint() {
                grid.innerHTML = NEW_TILE + drawings.map(card).join('');
                empty.classList.toggle('hidden', drawings.length > 0);
            }

            /* ---------- opening the pad ---------- */
            function pad(seed) {
                if (typeof window.openDrawCanvas !== 'function') { toast('Drawing pad unavailable.', 'error'); return; }
                seed = seed || {};
                // Closing the drawing a note sent us to returns to that note.
                if (askedForOne) {
                    askedForOne = false;
                    const goBack = () => {
                        document.removeEventListener('sm:draw-pad-closed', goBack);
                        window.smReturnToOrigin?.();
                    };
                    document.addEventListener('sm:draw-pad-closed', goBack);
                }
                window.openDrawCanvas((dataUrl, objects, mode) => {
                    // The pad's exits decide what this becomes: a flat picture,
                    // a new drawing that carries its strokes back, or the same
                    // drawing again — which is the only one that keeps the file
                    // it was opened from.
                    const over = mode === 'overwrite' && seed.noteId;
                    pending = {
                        image: dataUrl,
                        strokes: objects || null,
                        editable: !!objects,
                        noteId: over ? seed.noteId : null,
                        index: over ? (seed.index || 0) : 0,
                        // Which shelf the note lives on — board and day notes
                        // hold drawings too, and a save-over must land there.
                        source: over ? (seed.source || 'note') : 'note',
                    };
                    document.getElementById('drTitle').value = over ? (seed.title || '') : (seed.newTitle || seed.title || '');
                    document.getElementById('drNote').value = over ? (seed.note || '') : '';
                    document.getElementById('drKind').textContent = over
                        ? 'Saving over the drawing you opened.'
                        : (objects
                            ? 'Kept as a drawing — you can reopen and change it later.'
                            : 'Kept as a picture — it can be drawn over, but not edited stroke by stroke.');
                    window.openSheet('drSaveSheet');
                    setTimeout(() => document.getElementById('drTitle').focus(), 120);
                }, seed.url || null, {
                    editable: true,
                    objects: seed.objects || null,
                    title: seed.title || 'Drawing',
                    overwrite: !!seed.noteId,
                    overwriteLabel: seed.title ? `“${seed.title}”` : 'the one you opened',
                });
            }

            async function edit(d) {
                // A team drawing is the team's record: drawing over it starts a
                // new one rather than rewriting what the room agreed on.
                if (d.team) { pad({ url: d.url, title: d.title + ' (copy)' }); return; }
                if (!d.editable) { pad({ url: d.url, title: d.title, noteId: d.noteId, index: d.index, source: d.source, note: d.note || '' }); return; }
                try {
                    const r = await api(`${U.one}&noteId=${d.noteId}&index=${d.index}&source=${encodeURIComponent(d.source || '')}`);
                    pad({
                        url: d.url, title: d.title, noteId: d.noteId, index: d.index, source: d.source,
                        note: (r.data && r.data.note) || d.note || '',
                        newTitle: (d.title || 'Drawing') + ' (copy)',
                        objects: (r.data && r.data.strokes) || null,
                    });
                } catch (err) { toast(err.message || 'Could not open that drawing.', 'error'); }
            }

            /* ---------- grid actions ---------- */
            grid.addEventListener('click', async (e) => {
                if (e.target.closest('[data-new]')) { pad(); return; }
                const cardEl = e.target.closest('.dr-card');
                if (!cardEl) return;
                const d = drawings[parseInt(cardEl.getAttribute('data-i'), 10)];
                if (!d) return;

                if (e.target.closest('[data-edit]')) { edit(d); return; }

                if (e.target.closest('[data-del]')) {
                    const ok = (typeof confirmAction === 'function')
                        ? await confirmAction({ title: 'Delete this drawing?', message: 'It goes from the notebook too.', confirmText: 'Delete', danger: true })
                        : confirm('Delete this drawing?');
                    if (!ok) return;
                    try {
                        await api(`${U.destroy}&noteId=${d.noteId}&index=${d.index}&source=${encodeURIComponent(d.source || '')}`, { method: 'DELETE' });
                        drawings = drawings.filter((x) => x !== d);
                        paint();
                        toast('Drawing deleted.');
                    } catch (err) { toast(err.message || 'Could not delete that.', 'error'); }
                    return;
                }

                if (e.target.closest('[data-open]')) {
                    // Editable ones open where they can be worked on; a flat
                    // picture opens where it can be looked at.
                    if (d.editable) edit(d);
                    else if (d.url && typeof window.openNoteLightbox === 'function') window.openNoteLightbox('image', d.url);
                    else if (d.url) window.open(d.url, '_blank', 'noopener');
                }
            });

            /* ---------- keeping it ---------- */
            document.getElementById('drConfirm').addEventListener('click', async (e) => {
                if (!pending) return;
                const title = (document.getElementById('drTitle').value || '').trim() || 'Drawing';
                const note = (document.getElementById('drNote').value || '').trim();
                const btn = e.currentTarget;
                btn.disabled = true;
                try {
                    const r = await api(U.save, { method: 'POST', body: Object.assign({ title, note }, pending) });
                    const d = r.data || {};
                    const row = {
                        noteId: d.noteId, index: d.index, title: d.title || title,
                        source: (pending && pending.noteId) ? (pending.source || 'note') : 'note',
                        note: d.note != null ? d.note : note,
                        editable: !!d.editable, team: false, url: d.url, when: 'Just now',
                    };
                    // Cache-bust: a re-saved drawing keeps its slot in the grid
                    // and the browser would otherwise show the old picture.
                    const at = drawings.findIndex((x) => x.noteId === row.noteId && x.index === row.index
                        && (x.source || 'note') === row.source);
                    if (at >= 0) drawings[at] = row; else drawings.unshift(row);
                    pending = null;
                    paint();
                    window.closeSheet('drSaveSheet');
                    toast('Drawing saved.');
                } catch (err) {
                    toast(err.message || 'Could not save that.', 'error');
                } finally { btn.disabled = false; }
            });
            document.getElementById('drTitle').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); document.getElementById('drConfirm').click(); }
            });

            paint();

            /* A drawing tapped in Notes (or anywhere else) asks for itself by
               name: ?open=<noteId>:<index>. Without this the link could only
               reach the front door of the module and leave you to find it. */
            (function openFromLink() {
                const want = new URLSearchParams(window.location.search).get('open')
                    || @json(request()->query('open'));
                if (!want) return;
                const [noteId, index] = String(want).split(':');
                // Deep links come from the notebook, so a notebook row wins
                // when a board note happens to share the same id.
                const d = drawings.find((x) => (x.source || 'note') === 'note'
                    && String(x.noteId) === String(noteId)
                    && String(x.index) === String(index || 0));
                if (!d) return;
                // Opened because a note asked for it: closing the pad is done
                // with the drawing, so it hands you back to the note rather
                // than leaving you in a module you never chose to open.
                askedForOne = true;
                setTimeout(() => edit(d), 150);
            })();
        })();
    </script>
@endsection
