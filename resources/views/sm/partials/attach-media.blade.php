@once
{{-- ============================================================
     Putting a picture or a clip on something.

     Three doors, because there are three ways a farmer actually has the
     thing they want to attach: it is already in the season's gallery, it is
     in their phone, or it is in front of them right now. Asking them to pick
     a source before they have picked a file is the wrong order, so the doors
     are offered together and each leads straight to its own picker.

     Everything chosen at once, not one at a time — six photos of the same
     blighted row is one errand, and six trips through a sheet is not.

     Every upload is asked what it IS before it goes: a title, and a
     description if there is one to give. That is not paperwork — the file
     lands in the Gallery as well as on the activity, and a gallery of
     IMG_20260828_113402.jpg is a gallery nobody opens twice.

       window.smAttachMedia({
         scheduleId,
         kind: 'image' | 'video',
         onDone(items)     // [{path, url, kind, title}]
       })
     ============================================================ --}}
<style>
    .am-doors { display: grid; gap: .6rem; }
    @media (min-width: 480px) { .am-doors { grid-template-columns: repeat(3, 1fr); } }
    .am-door { display: flex; align-items: center; gap: .7rem; width: 100%; cursor: pointer;
        padding: .85rem .9rem; border-radius: .9rem; text-align: left;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .22s cubic-bezier(.22,1,.36,1), background .22s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 480px) { .am-door { flex-direction: column; align-items: flex-start; } }
    .am-door:hover { border-color: #a8cc7e; background: #f7fbf3; }
    .am-door-ico { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eef6e6; color: #3d6823; }
    .am-door-ico svg { width: 1.15rem; height: 1.15rem; }
    .am-door b { display: block; font-size: .85rem; font-weight: 800; color: var(--color-gray-900); }
    .am-door i { display: block; font-style: normal; font-size: .72rem; line-height: 1.45; color: var(--color-gray-500); }

    /* One row per file: what it looks like, what it is called, how it is
       getting on. The bar lives on the row rather than over the sheet,
       because six files uploading is six answers, not one. */
    .am-list { display: flex; flex-direction: column; gap: .55rem; }
    .am-row { display: flex; gap: .7rem; padding: .6rem; border-radius: .8rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .am-shot { flex: none; width: 3.4rem; height: 3.4rem; border-radius: .55rem; overflow: hidden;
        background: var(--color-gray-100); display: flex; align-items: center; justify-content: center; }
    .am-shot img, .am-shot video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .am-mid { min-width: 0; flex: 1 1 auto; }
    .am-mid input { width: 100%; }
    .am-size { font-size: .68rem; color: var(--color-gray-400); margin-top: .2rem; }
    .am-bar { height: 4px; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .4rem; }
    .am-bar i { display: block; height: 100%; width: 0; border-radius: 999px; background: #4a7c2a;
        transition: width .2s linear; }
    .am-row.is-done .am-bar i { background: #4a7c2a; width: 100% !important; }
    .am-row.is-bad { border-color: #fca5a5; background: #fef2f2; }
    .am-why { font-size: .68rem; color: #b91c1c; margin-top: .25rem; }
    .am-drop { flex: none; align-self: flex-start; border: 0; background: transparent; cursor: pointer;
        color: var(--color-gray-300); font-size: 1rem; line-height: 1; padding: .2rem; }
    .am-drop:hover { color: #dc2626; }
    html.dark .am-door, html.dark .am-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .am-door b { color: #e8efe1; }
    html.dark .am-door-ico { background: rgb(107 159 61 / .18); color: #a5c97e; }
    @media (prefers-reduced-motion: reduce) { .am-door { transition: none; } .am-bar i { transition: none; } }
</style>

<div class="sheet hidden" id="amSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="amTitle">Add photos</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">&#10005;</button>
    </div>
    <div class="sheet-body">
        <div class="am-doors" id="amDoors">
            <button type="button" class="am-door" data-am-door="gallery">
                <span class="am-door-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4"/></svg></span>
                <span><b>From the gallery</b><i id="amDoorGalleryWhy">Something this season already keeps.</i></span>
            </button>
            <button type="button" class="am-door" data-am-door="upload">
                <span class="am-door-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg></span>
                <span><b>Upload</b><i id="amDoorUploadWhy">From this phone or computer.</i></span>
            </button>
            <button type="button" class="am-door" data-am-door="camera">
                <span class="am-door-ico" id="amDoorCamIco"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span><b id="amDoorCamName">Camera</b><i id="amDoorCamWhy">Take one now.</i></span>
            </button>
        </div>

        {{-- What was chosen, waiting to be named and sent. --}}
        <div id="amStage" class="hidden mt-4">
            <p class="form-hint mb-2" id="amStageSay"></p>
            <div class="am-list" id="amList"></div>

            <div class="mt-3">
                <label class="form-label text-xs! mb-1!" for="amDesc">Description <span class="text-gray-400 font-normal">(optional, applies to all)</span></label>
                <textarea id="amDesc" rows="2" maxlength="2000" class="form-textarea"
                          placeholder="What is this showing? — it goes on the Gallery entry too"></textarea>
            </div>
        </div>
    </div>
    <div class="sheet-footer hidden" id="amFooter">
        <button type="button" class="btn btn-primary w-full" id="amSend">Upload</button>
    </div>
</div>

<input type="file" id="amFileUpload" class="hidden" multiple>
<input type="file" id="amFileCamera" class="hidden">

<script>
(function attachMedia() {
    if (window.smAttachMedia) return;

    const $ = (id) => document.getElementById(id);
    let CFG = null;
    let PICKED = [];            // [{file, name, size, url, title}]

    // A 40 kB photo reading "0.0 MB" looks like a file that failed to load.
    const MB = (n) => (n < 999424 ? Math.max(1, Math.round(n / 1024)) + ' KB'
        : (n / 1048576).toFixed(1) + ' MB');

    /* Shrink a photo before it leaves the phone.
     *
     * A modern handset takes 4000px pictures of a leaf. Sending one costs a
     * farmer real money on a mobile plan and buys nothing — 1600px on the
     * long edge is more than any screen in this app will ever show. Clips are
     * left alone: re-encoding video in a browser is slow, lossy and often
     * worse than what the camera produced. */
    async function shrink(file, max = 1600, quality = 0.82) {
        if (!/^image\//.test(file.type) || /gif$/i.test(file.type)) return file;
        try {
            const img = await new Promise((res, rej) => {
                const i = new Image();
                i.onload = () => res(i);
                i.onerror = rej;
                i.src = URL.createObjectURL(file);
            });
            const scale = Math.min(1, max / Math.max(img.width, img.height));
            if (scale === 1 && file.size < 1200000) return file;
            const c = document.createElement('canvas');
            c.width = Math.round(img.width * scale);
            c.height = Math.round(img.height * scale);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', quality));
            if (!blob || blob.size >= file.size) return file;
            return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
        } catch (_) {
            return file;   // a picture that will not shrink still deserves to go
        }
    }

    /** A sensible title from a filename, because IMG_20260828_113402 is not one. */
    function niceName(name) {
        const out = (name || '')
            .replace(/\.[^.]+$/, '')
            .replace(/[_-]+/g, ' ')
            // What a camera puts in a filename and a person never would.
            .replace(/\b(img|image|video|vid|photo|dsc|dcim|pxl|screenshot|whatsapp)\b\s*/gi, '')
            .replace(/\b\d{6,}\b/g, '')            // the date-and-time stamp
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/^./, (c) => c.toUpperCase());
        // Stripped down to nothing (IMG_20260828.jpg) — the box asks instead.
        return out.length > 1 ? out : '';
    }

    function draw() {
        const list = $('amList');
        list.innerHTML = PICKED.map((p, i) => `
            <div class="am-row" data-i="${i}">
                <span class="am-shot">${p.isVideo
                    ? `<video src="${p.url}" muted playsinline preload="metadata"></video>`
                    : `<img src="${p.url}" alt="">`}</span>
                <span class="am-mid">
                    <input type="text" class="form-input" data-am-title value="${(p.title || '').replace(/"/g, '&quot;')}"
                           maxlength="180" placeholder="What is this?">
                    <span class="am-size">${p.name} · ${MB(p.size)}</span>
                    <span class="am-bar"><i></i></span>
                    <span class="am-why"></span>
                </span>
                <button type="button" class="am-drop" data-am-drop="${i}" aria-label="Remove">&#10005;</button>
            </div>`).join('');
        $('amStage').classList.toggle('hidden', PICKED.length === 0);
        $('amFooter').classList.toggle('hidden', PICKED.length === 0);
        $('amStageSay').textContent = PICKED.length === 1
            ? 'One file. Give it a name so it can be found again.'
            : PICKED.length + ' files. Name them so they can be found again.';
        $('amSend').textContent = 'Upload ' + PICKED.length + (PICKED.length === 1 ? ' file' : ' files');
    }

    /* What the far end will actually take. A photo is shrunk on the way, so
       the cap only ever bites on a clip — and it bites HERE, with a sentence,
       rather than as a bare 413 after four minutes of upload bar. */
    const CAP = { image: 8 * 1048576, video: 100 * 1048576 };

    async function take(files) {
        const want = CFG.kind === 'video' ? /^video\//: /^image\//;
        for (const f of Array.from(files || [])) {
            if (!want.test(f.type)) {
                window.toast?.(`"${f.name}" is not ${CFG.kind === 'video' ? 'a clip' : 'an image'} — skipped.`, 'error');
                continue;
            }
            const small = await shrink(f);
            if (small.size > CAP[CFG.kind]) {
                window.toast?.(`"${f.name}" is ${MB(small.size)} — the limit is ${MB(CAP[CFG.kind])}.`, 'error');
                continue;
            }
            PICKED.push({
                file: small,
                name: f.name,
                size: small.size,
                url: URL.createObjectURL(small),
                title: niceName(f.name),
                isVideo: CFG.kind === 'video',
            });
        }
        draw();
    }

    document.addEventListener('click', (e) => {
        const door = e.target.closest('[data-am-door]');
        if (door && CFG) {
            const which = door.getAttribute('data-am-door');
            if (which === 'gallery') {
                /* Already in the season's gallery: pointed at, not copied.
                   Nothing to name and nothing to upload, so it goes straight
                   back to the caller and the sheet closes.

                   The picker hands its collection over one at a time; they are
                   gathered and passed on together, so the caller sees one
                   addition of six rather than six additions of one. */
                const mine = CFG;
                window.closeSheet?.('amSheet');
                const batch = [];
                let flush = null;
                window.smPickMedia?.({
                    scheduleId: mine.scheduleId,
                    kinds: mine.kind === 'video' ? 'video' : 'image',
                    multiple: true,
                    title: mine.kind === 'video' ? 'Choose clips' : 'Choose photos',
                    onPick: (m) => {
                        batch.push({ path: m.path, url: m.url, kind: mine.kind, title: m.title || null });
                        clearTimeout(flush);
                        flush = setTimeout(() => mine.onDone?.(batch.splice(0)), 0);
                    },
                });
                return;
            }
            const input = $(which === 'camera' ? 'amFileCamera' : 'amFileUpload');
            input.accept = CFG.kind === 'video' ? 'video/*' : 'image/*';
            if (which === 'camera') input.setAttribute('capture', 'environment');
            input.value = '';
            input.click();
            return;
        }

        const drop = e.target.closest('[data-am-drop]');
        if (drop) {
            PICKED.splice(Number(drop.getAttribute('data-am-drop')), 1);
            draw();
        }
    });

    $('amFileUpload')?.addEventListener('change', (e) => take(e.target.files));
    $('amFileCamera')?.addEventListener('change', (e) => take(e.target.files));

    // A title typed on a row belongs to that row.
    document.addEventListener('input', (e) => {
        const box = e.target.closest('#amList [data-am-title]');
        if (!box) return;
        const i = Number(box.closest('.am-row').getAttribute('data-i'));
        if (PICKED[i]) PICKED[i].title = box.value;
    });

    /* One request per file, with its own bar.
     *
     * XMLHttpRequest rather than fetch, for the one thing fetch still cannot
     * do: tell you how far an upload has got. A farmer on a field signal
     * watching a frozen screen assumes it is broken and taps again. */
    function send(item, desc, row) {
        return new Promise((resolve) => {
            const fd = new FormData();
            fd.append(item.isVideo ? 'video' : 'image', item.file);
            if (item.title) fd.append('title', item.title);
            if (desc) fd.append('description', desc);

            const bar = row.querySelector('.am-bar i');
            const why = row.querySelector('.am-why');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', CFG.uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]')?.content || '');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = (ev) => {
                if (!ev.lengthComputable) return;
                bar.style.width = Math.round((ev.loaded / ev.total) * 96) + '%';
            };
            xhr.onload = () => {
                let d = {};
                try { d = JSON.parse(xhr.responseText); } catch (_) {}
                if (xhr.status >= 200 && xhr.status < 300 && d.data?.imagePath) {
                    row.classList.add('is-done');
                    resolve({ path: d.data.imagePath, url: d.data.imageUrl, kind: d.data.kind || CFG.kind, title: item.title || null });
                    return;
                }
                row.classList.add('is-bad');
                why.textContent = d.message || ('Upload failed (' + xhr.status + ').');
                resolve(null);
            };
            xhr.onerror = () => {
                row.classList.add('is-bad');
                why.textContent = 'The connection dropped.';
                resolve(null);
            };
            xhr.send(fd);
        });
    }

    /* The run owns everything it needs.
     *
     * This used to read CFG and PICKED out of the closure as it went, which
     * is fine right up until somebody opens the sheet again while files are
     * still going up — the config is swapped underneath the loop and the
     * finished uploads are handed to whoever asked LAST. Every run now takes
     * its own copy of the config, the queue and the rows it is writing into,
     * and the sheet refuses to be reused until it is finished. */
    let SENDING = false;

    $('amSend')?.addEventListener('click', async (e) => {
        if (SENDING || !CFG || !PICKED.length) return;
        const mine = CFG;
        const queue = PICKED.map((p, i) => ({
            item: p,
            row: $('amList').querySelector(`.am-row[data-i="${i}"]`),
        }));
        const btn = e.currentTarget;
        SENDING = true;
        btn.disabled = true;
        $('amDoors').classList.add('hidden');
        const desc = ($('amDesc').value || '').trim();
        const done = [];

        for (let i = 0; i < queue.length; i++) {
            btn.textContent = 'Uploading ' + (i + 1) + ' of ' + queue.length + '…';
            const out = await send(queue[i].item, desc, queue[i].row);
            if (out) done.push(out);
        }

        SENDING = false;
        btn.disabled = false;
        $('amDoors').classList.remove('hidden');

        if (done.length) mine.onDone?.(done);
        if (done.length === queue.length) {
            window.closeSheet?.('amSheet');
            window.toast?.(done.length === 1 ? 'Uploaded, and filed in the Gallery.'
                : done.length + ' uploaded, and filed in the Gallery.');
            PICKED = [];
            draw();
        } else {
            // The ones that failed stay on screen with their reason, so the
            // reader can drop them or try again rather than guessing.
            PICKED = queue.filter((q) => !q.row?.classList.contains('is-done')).map((q) => q.item);
            draw();
            window.toast?.('Some files did not go up. Their reason is on the row.', 'error');
        }
    });

    window.smAttachMedia = function (cfg) {
        // Files still on the wire own this sheet until they land. Swapping it
        // out from under them loses both the progress and the answer.
        if (SENDING) {
            window.toast?.('Still uploading — one moment.', 'error');
            return;
        }
        CFG = Object.assign({ kind: 'image' }, cfg || {});
        PICKED = [];
        const clip = CFG.kind === 'video';
        $('amTitle').textContent = clip ? 'Add clips' : 'Add photos';
        $('amDoorCamName').textContent = clip ? 'Record' : 'Camera';
        $('amDoorCamWhy').textContent = clip ? 'Film it now.' : 'Take one now.';
        $('amDoorGalleryWhy').textContent = clip
            ? 'A clip this season already keeps.' : 'Something this season already keeps.';
        $('amDoorUploadWhy').textContent = clip
            ? 'A clip from this phone or computer.' : 'From this phone or computer.';
        $('amDesc').value = '';
        draw();
        window.openSheet?.('amSheet');
    };
})();
</script>
@endonce
