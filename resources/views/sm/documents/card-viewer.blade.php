<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Card Viewer — {{ $schedule->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    {{-- html2canvas: client-side DOM-to-canvas rasterizer used by the
         "Save as Image" toolbar button. MIT licensed, ~46KB minified. --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
    <style>
        :root {
            --cv-bg: #f4f6fb;
            --cv-surface: #ffffff;
            --cv-ink: #1a1f2b;
            --cv-muted: #6b7280;
            --cv-line: #e1e6ef;
            --cv-accent: #556ee6;
            --cv-shadow: 0 4px 16px rgba(20, 30, 60, .08);
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            background: var(--cv-bg);
            color: var(--cv-ink);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        body { padding-bottom: 70px; /* room for fixed footer */ }
        a { color: var(--cv-accent); }
        button { cursor: pointer; font-family: inherit; }

        /* ============ TOP TOOLBAR (sticky) ============ */
        .cv-toolbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--cv-ink); color: #fff;
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .cv-toolbar .cv-title {
            font-weight: 700; font-size: 15px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            flex: 0 1 auto; max-width: 38%;
        }
        .cv-toolbar .cv-version {
            background: rgba(255,255,255,.15);
            padding: 3px 10px; border-radius: 10px;
            font-size: 11.5px; font-weight: 600;
        }
        .cv-toolbar .cv-counter {
            background: rgba(255,255,255,.12);
            padding: 4px 12px; border-radius: 10px;
            font-size: 12.5px; font-variant-numeric: tabular-nums;
        }
        .cv-toolbar .cv-counter strong { color: #ffd56b; }
        .cv-toolbar select.cv-jump {
            background: rgba(255,255,255,.12);
            color: #fff; border: 1px solid rgba(255,255,255,.25);
            padding: 4px 10px; border-radius: 6px; font-size: 12.5px;
            max-width: 260px;
        }
        .cv-toolbar select.cv-jump option { color: #1a1f2b; background: #fff; }
        .cv-toolbar .cv-spacer { flex: 1; }
        .cv-toolbar .cv-iconbtn {
            background: rgba(255,255,255,.12);
            color: #fff; border: 1px solid rgba(255,255,255,.25);
            padding: 5px 10px; border-radius: 6px; font-size: 13px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .cv-toolbar .cv-iconbtn:hover { background: rgba(255,255,255,.22); }

        /* ============ SLIDES — full-page document layout ============
           Each slide is a paper-style page that fills the viewport
           vertically and grows naturally with content. When content
           exceeds the viewport, the WINDOW scrolls (not an internal
           panel) — same way you scroll through a Word doc or a PDF.
           Each slide reaches a hard minimum of 100vh − chrome so even
           short days fill the screen and feel like a "page". */
        .cv-stage {
            position: relative;
            padding: 24px 12px 32px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .cv-slide {
            display: none;
            width: 100%;
            max-width: 1100px;
            min-height: calc(100vh - 130px);
            margin: 0 auto;
            background: var(--cv-surface);
            border: 1px solid #e2e6ed;
            border-radius: 2px;
            box-shadow: var(--cv-shadow);
            animation: cvFade .18s ease;
            flex-direction: column;
        }
        .cv-slide.active { display: flex; }
        /* Slide body is a flex column so the in-document foot (.cv-doc-foot
           or .cv-cover-foot) can pin to the bottom via margin-top: auto
           even on short pages. No internal scroll — content flows down
           and the window scrolls when needed. */
        .cv-slide-body {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            padding: 40px 80px 28px;
            font-size: 16px;
            line-height: 1.6;
        }
        /* Cover slide is a document title page — same flex setup, slightly
           larger top margin like the title page of a printed report. */
        .cv-slide[data-index="0"] .cv-slide-body {
            padding: 56px 88px 32px;
        }
        .cv-slide[data-index="0"] .cv-slide-body > .cv-cover {
            flex: 1 0 auto; width: 100%;
        }
        @keyframes cvFade {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* Tablet / phone: shrink padding so the page still feels like a
           document but stays readable on small screens. */
        @media (max-width: 900px) {
            .cv-slide { max-width: 100%; }
            .cv-slide-body { padding: 28px 32px 22px; font-size: 15px; }
            .cv-slide[data-index="0"] .cv-slide-body { padding: 36px 32px 24px; }
        }
        @media (max-width: 600px) {
            .cv-slide-body { padding: 22px 18px 18px; font-size: 14.5px; }
            .cv-slide[data-index="0"] .cv-slide-body { padding: 26px 18px 20px; }
        }

        /* ============ COVER (document title page) ============ */
        .cv-cover {
            width: 100%;
            font-family: 'Cambria', 'Georgia', 'Times New Roman', serif;
            color: var(--cv-ink);
        }
        .cv-cover-org {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12.5px; font-weight: 700;
            color: var(--cv-muted);
            text-transform: uppercase; letter-spacing: 2.5px;
            margin: 0 0 18px;
        }
        .cv-cover h1 {
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 52px; font-weight: 700;
            margin: 0 0 10px;
            color: var(--cv-ink);
            letter-spacing: -0.4px;
            line-height: 1.12;
        }
        .cv-cover-span {
            font-family: 'Cambria', 'Georgia', serif;
            color: var(--cv-muted); font-size: 19px;
            margin: 0 0 28px;
            border-bottom: 2px solid var(--cv-ink);
            padding-bottom: 18px;
        }
        .cv-cover-facts {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0 36px;
            margin: 0 0 28px;
            max-width: 820px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .cv-cover-fact {
            display: flex; align-items: baseline; gap: 14px;
            border-bottom: 1px dotted #c8cdd8;
            padding: 9px 0;
        }
        .cv-cover-fact-label {
            font-size: 12px; letter-spacing: 1.5px;
            text-transform: uppercase; color: var(--cv-muted);
            font-weight: 700;
            min-width: 150px;
        }
        .cv-cover-fact-value {
            flex: 1; text-align: right;
            color: var(--cv-ink); font-weight: 600;
            font-size: 16.5px;
            font-variant-numeric: tabular-nums;
        }
        .cv-cover-rules {
            background: #fdfbf6;
            border: 1px solid #d3a78a;
            border-left: 4px solid #9c1c1c;
            padding: 20px 26px;
            margin: 22px 0;
            max-width: 820px;
        }
        .cv-cover-rules h2 {
            font-family: 'Cambria', 'Georgia', serif;
            margin: 0 0 12px;
            font-size: 14px; font-weight: 700; color: #5a2828;
            text-transform: uppercase; letter-spacing: 2px;
            border-bottom: 1px solid #d3a78a;
            padding-bottom: 8px;
        }
        .cv-cover-rules ol {
            margin: 0; padding-left: 26px;
            font-family: 'Cambria', 'Georgia', serif;
            color: #3a2c2c;
        }
        .cv-cover-rules li {
            margin: 7px 0;
            font-size: 16.5px; line-height: 1.55;
        }
        .cv-cover-intro {
            margin: 26px 0 0;
            padding: 0;
            color: var(--cv-ink);
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 17.5px; line-height: 1.65;
            max-width: 820px;
        }
        .cv-cover-intro h1,
        .cv-cover-intro h2,
        .cv-cover-intro h3 {
            font-family: 'Cambria', 'Georgia', serif;
            color: var(--cv-ink);
            margin: 0.6em 0 0.3em;
            font-size: 22px;
        }
        .cv-cover-intro p { margin: 0.6em 0; }
        .cv-cover-intro p:first-child { margin-top: 0; }
        .cv-cover-intro ul,
        .cv-cover-intro ol { margin: 0.5em 0 0.5em 1.5rem; }
        .cv-cover-empty {
            color: var(--cv-muted);
            font-style: italic;
            padding: 26px 0;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 16px;
        }
        .cv-cover-foot {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #ecf0f5;
            font-size: 13px; color: var(--cv-muted);
            letter-spacing: 0.5px;
            display: flex; justify-content: space-between;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* ============ DAY SLIDE (document page) ============ */
        /* The day-head sits directly inside the slide frame as the document
           title block. Schedule micro-line on top, then the large date
           heading, then the dateline (weekday + Day N of M).
           Horizontal padding matches .cv-slide-body so the header content
           edge-aligns with the body content edge. */
        .cv-day-head {
            position: relative;
            flex: 0 0 auto;
            padding: 40px 80px 22px;
            border-bottom: 2px solid var(--cv-ink);
            font-family: 'Cambria', 'Georgia', serif;
            background: #fff;
        }
        /* Page indicator — top-right corner of the slide header.
           Visible on screen so the user can cross-reference the deck
           while navigating, and stays in the same corner in print so a
           shuffled stack can be re-ordered. Server-rendered (not a
           live counter) so single-slide "Print Page" mode still shows
           the slide's true position in the full deck. */
        .cv-page-num {
            position: absolute;
            top: 14px;
            right: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: var(--cv-muted);
            letter-spacing: 1.2px;
            text-transform: uppercase;
            background: #fff;
            padding: 4px 9px;
            border: 1px solid var(--cv-line);
            border-radius: 2px;
        }
        @media print {
            .cv-page-num {
                top: 0;
                right: 4px;
                font-size: 9pt;
                border: 0;
                background: transparent;
                padding: 0;
                color: #555;
            }
        }
        .cv-doc-org {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12.5px; font-weight: 700;
            color: var(--cv-muted);
            text-transform: uppercase; letter-spacing: 2px;
            margin: 0 0 8px;
        }
        .cv-day-heading {
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 36px; font-weight: 700;
            color: var(--cv-ink);
            margin: 0;
            letter-spacing: -0.2px;
            line-height: 1.15;
        }
        .cv-day-dateline {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 15px; color: var(--cv-muted);
            margin: 8px 0 0;
            letter-spacing: 0.4px;
        }

        /* Critical-rules document notice — small italic line, not a card */
        .cv-rules-banner {
            margin: 18px 0 0;
            font-size: 14.5px;
            color: #6b3110;
            font-style: italic;
            letter-spacing: 0.2px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .cv-rules-banner-mark {
            color: #9c1c1c; font-weight: 700; margin-right: 6px;
            font-size: 17px;
        }
        .cv-rules-banner-count {
            font-weight: 700; color: #8a1d1d; font-style: normal;
        }

        /* Date note — blockquote */
        .cv-day-note {
            border-left: 4px solid #b08527;
            background: #fdfbf3;
            padding: 16px 22px;
            margin: 18px 0 0;
            font-style: italic;
            color: #3a3528;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 16.5px;
            line-height: 1.55;
        }
        .cv-day-note-label {
            font-style: normal;
            font-weight: 700;
            font-variant: small-caps;
            letter-spacing: 0.6px;
            margin-right: 8px;
            color: #6b5a18;
            font-size: 16px;
        }

        /* Document section — ALL-CAPS header with horizontal rule */
        .cv-doc-section { margin-top: 30px; }
        .cv-doc-section:first-of-type { margin-top: 22px; }
        .cv-doc-section-head {
            display: flex; align-items: baseline; gap: 12px;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 14px; font-weight: 700;
            color: #2d3548;
            text-transform: uppercase; letter-spacing: 2.5px;
            border-bottom: 1.5px solid var(--cv-ink);
            padding: 0 0 6px;
            margin: 0 0 16px;
        }
        .cv-doc-section-count {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px; color: var(--cv-muted);
            letter-spacing: 1.4px; font-weight: 600;
            margin-left: auto;
        }

        /* Numbered document list. Counter-based numbering instead of <ol>
           styling so the digits sit in their own gutter and the title can
           wrap freely beside them. */
        .cv-doc-list {
            list-style: none;
            padding: 0; margin: 0;
            counter-reset: cvdoc;
        }
        /* Each activity / irrigation is its own subtly-bordered block so
           multi-item days read cleanly. Each block carries a priority-
           coded (or task-coded) thicker left accent — workers can scan
           down a day's list and spot CRITICAL items by the red edge. */
        .cv-doc-item {
            counter-increment: cvdoc;
            position: relative;
            padding: 18px 22px 20px 60px;
            margin: 0 0 18px;
            background: #fafbfd;
            border: 1px solid #e6ebf3;
            border-left: 4px solid #c5d0e3;
            border-radius: 3px;
        }
        .cv-doc-item:last-child { margin-bottom: 0; }
        .cv-doc-item::before {
            content: counter(cvdoc) ".";
            position: absolute; left: 14px; top: 17px;
            font-family: 'Cambria', 'Georgia', serif;
            font-weight: 700;
            color: #2d3548;
            font-size: 22px;
            width: 34px; text-align: right;
            padding-right: 6px;
        }
        /* Priority-coded left accents for activity items */
        .cv-doc-item.cv-item-prio-critical { border-left-color: #8a1d1d; }
        .cv-doc-item.cv-item-prio-high     { border-left-color: #b34e2e; }
        .cv-doc-item.cv-item-prio-medium   { border-left-color: #4d7a2d; }
        .cv-doc-item.cv-item-prio-low      { border-left-color: #6c7280; }
        /* Irrigation items inherit the task color via the --c CSS var */
        .cv-doc-item.cv-item-irr { border-left-color: var(--c, #1976d2); }

        /* Toolbar toggle: when body.cv-hide-irrigation is set, every
           per-day irrigation section disappears (both on-screen + print).
           Days whose only content was irrigation become visually empty,
           so we also surface a small placeholder pulled in by JS. */
        body.cv-hide-irrigation [data-section="irrigation"] { display: none !important; }
        .cv-doc-section-head .cv-toggle-on-hidden { display: none; }
        body.cv-hide-irrigation .cv-iconbtn#cvToggleIrrigationBtn {
            background: rgba(255, 213, 107, .25);
            border-color: rgba(255, 213, 107, .65);
        }
        .cv-doc-hidden-msg {
            margin: 18px 0;
            padding: 14px 18px;
            text-align: center;
            color: var(--cv-muted);
            font-style: italic;
            border: 1px dashed #d9dde3;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 14px;
        }
        .cv-doc-item-head {
            display: flex; flex-wrap: wrap; align-items: baseline; gap: 10px;
            margin: 0 0 6px;
        }
        .cv-doc-item-title {
            font-family: 'Cambria', 'Georgia', serif;
            font-weight: 700; font-size: 20px;
            color: var(--cv-ink);
            flex: 1 1 auto;
            min-width: 0;
            line-height: 1.25;
        }

        /* Document tags — small printed labels (uppercase, bordered,
           sans-serif). Replaces the bright colored chip-pills. */
        .cv-doc-tag {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11.5px; font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 3px 9px;
            border: 1px solid #c8cdd8;
            border-radius: 2px;
            color: #4a5160;
            background: #fff;
            white-space: nowrap;
        }
        /* Per-activity download button. Sits at the end of the item head
           (after the priority + multiday + day-0 tags), matched to the
           same height as the tags so it doesn't break the row. Hidden in
           print AND excluded from the html2canvas capture (see onclone
           strip in the JS handler), so neither the printed deck nor the
           saved PNG carry the button itself. */
        .cv-item-download-btn {
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid #c8cdd8;
            background: #fff;
            color: #4a5160;
            padding: 2px 7px;
            border-radius: 2px;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
            transition: background .12s ease;
        }
        .cv-item-download-btn:hover { background: #f4f5f8; color: var(--cv-ink); }
        .cv-item-download-btn:active { background: #e8eaef; }
        .cv-item-download-btn.is-busy { opacity: 0.55; cursor: wait; }
        .cv-item-download-btn i { display: block; }
        @media print {
            .cv-item-download-btn { display: none !important; }
        }
        .cv-doc-tag.cv-tag-prio-critical {
            border-color: #8a1d1d; color: #8a1d1d; background: #fff4f4;
        }
        .cv-doc-tag.cv-tag-prio-high {
            border-color: #b34e2e; color: #a84320; background: #fdf1eb;
        }
        .cv-doc-tag.cv-tag-prio-medium {
            border-color: #4d7a2d; color: #3e6724; background: #f3f9ec;
        }
        .cv-doc-tag.cv-tag-prio-low {
            border-color: #6c7280; color: #4a5160; background: #f4f5f8;
        }
        .cv-doc-tag.cv-tag-day0 {
            border-color: #d97a4f; color: #b34e2e; background: #fff3eb;
        }
        .cv-doc-tag.cv-tag-multiday {
            border-color: #b08527; color: #8a5e09; background: #fdf8eb;
        }
        .cv-doc-tag.cv-tag-irr {
            color: #fff;
            border-color: transparent;
            background: var(--c, #1976d2);
        }

        /* Definition-list-style metadata rows: LABEL  value, value, value */
        .cv-doc-meta {
            margin: 4px 0 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #2d3548;
        }
        .cv-doc-meta-row {
            display: flex; gap: 14px;
            padding: 4px 0;
            align-items: baseline;
            font-size: 15px;
        }
        .cv-doc-meta-label {
            font-weight: 700;
            color: #6b7280;
            min-width: 110px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            flex-shrink: 0;
        }
        .cv-doc-meta-value {
            flex: 1 1 auto;
            min-width: 0;
            word-wrap: break-word;
            line-height: 1.5;
        }
        .cv-doc-meta-value em { font-style: italic; color: #4a5160; }

        /* Worker tags — each labor entry rendered as a warm-amber name
           badge so individual workers stand out from the comma-list
           treatment used for lots / materials / services. The warm
           palette echoes the rest of the app's "worker" visual identity
           (orange/amber chips on the setup tab). */
        .cv-doc-worker {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 11px 3px 9px;
            margin: 0 6px 5px 0;
            background: #fef6ea;
            border: 1px solid #e0c290;
            color: #6b4a0e;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 14.5px;
            font-weight: 600;
            border-radius: 3px;
            letter-spacing: 0.2px;
            white-space: nowrap;
            line-height: 1.3;
        }
        .cv-doc-worker > i {
            font-size: 13px;
            color: #a87815;
        }
        .cv-doc-workers {
            display: flex; flex-wrap: wrap;
            margin: -2px 0 -5px;
        }

        /* Description block — body prose under the metadata, separated
           by a hairline rule. Renders user-entered HTML formatting
           (Quill output): paragraphs, lists, headings, inline marks,
           images. Plus the alignment classes Quill emits. */
        .cv-doc-desc {
            margin: 12px 0 0;
            padding: 10px 0 0;
            border-top: 1px solid #ecf0f5;
            color: #2d3548;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 16.5px;
            line-height: 1.6;
        }
        /* Paragraphs */
        .cv-doc-desc p { margin: 0.6em 0; }
        .cv-doc-desc p:first-child { margin-top: 0; }
        .cv-doc-desc p:last-child { margin-bottom: 0; }
        /* Lists — generous left padding so bullets sit cleanly inside
           the item block, and tighter li spacing so multi-bullet lists
           don't sprawl. Google Docs / Quill commonly nests <p> inside
           <li>; strip those margins so list items stay compact. */
        .cv-doc-desc ul,
        .cv-doc-desc ol {
            margin: 0.6em 0;
            padding-left: 1.6em;
        }
        .cv-doc-desc ul ul,
        .cv-doc-desc ol ol,
        .cv-doc-desc ul ol,
        .cv-doc-desc ol ul { margin: 0.2em 0; }
        .cv-doc-desc li { margin: 0.25em 0; }
        .cv-doc-desc li > p {
            margin: 0;
            display: inline;
        }
        .cv-doc-desc li > p + p { margin-top: 0.3em; display: block; }

        /* Quill 2 stores BOTH bulleted and ordered lists as <ol>, with
           the marker type encoded on each <li> via data-list. Without
           this override, an authored bullet list renders as a numbered
           list because the browser honors the <ol>'s default decimal
           counter. Per-item list-style-type lets each <li> pick its own
           marker regardless of the wrapper. */
        .cv-doc-desc ol > li[data-list="bullet"]  { list-style-type: disc; }
        .cv-doc-desc ol > li[data-list="ordered"] { list-style-type: decimal; }
        /* Quill's editor-only UI span ships with the saved HTML. It's
           contenteditable=false, inert, and adds whitespace if visible. */
        .cv-doc-desc .ql-ui { display: none; }
        /* Indentation levels Quill applies to nested outline items
           (e.g. sub-bullets under a parent step). 1.5em per level
           matches Quill's snow theme. */
        .cv-doc-desc li.ql-indent-1 { margin-left: 1.5em; }
        .cv-doc-desc li.ql-indent-2 { margin-left: 3em; }
        .cv-doc-desc li.ql-indent-3 { margin-left: 4.5em; }
        .cv-doc-desc li.ql-indent-4 { margin-left: 6em; }
        .cv-doc-desc li.ql-indent-5 { margin-left: 7.5em; }
        .cv-doc-desc li.ql-indent-6 { margin-left: 9em; }
        .cv-doc-desc li.ql-indent-7 { margin-left: 10.5em; }
        .cv-doc-desc li.ql-indent-8 { margin-left: 12em; }
        /* Headings — keep the same serif family but smaller than the
           item title above so they don't compete with the activity
           title for visual weight. */
        .cv-doc-desc h1,
        .cv-doc-desc h2,
        .cv-doc-desc h3,
        .cv-doc-desc h4,
        .cv-doc-desc h5,
        .cv-doc-desc h6 {
            font-family: 'Cambria', 'Georgia', serif;
            font-weight: 700;
            color: var(--cv-ink);
            margin: 0.8em 0 0.3em;
            line-height: 1.25;
        }
        .cv-doc-desc h1 { font-size: 19px; }
        .cv-doc-desc h2 { font-size: 18px; }
        .cv-doc-desc h3 { font-size: 17px; }
        .cv-doc-desc h4,
        .cv-doc-desc h5,
        .cv-doc-desc h6 { font-size: 16.5px; }
        .cv-doc-desc h1:first-child,
        .cv-doc-desc h2:first-child,
        .cv-doc-desc h3:first-child,
        .cv-doc-desc h4:first-child { margin-top: 0; }
        /* Inline marks */
        .cv-doc-desc strong,
        .cv-doc-desc b { font-weight: 700; color: var(--cv-ink); }
        .cv-doc-desc em,
        .cv-doc-desc i { font-style: italic; }
        .cv-doc-desc u { text-decoration: underline; }
        .cv-doc-desc s,
        .cv-doc-desc del { text-decoration: line-through; color: var(--cv-muted); }
        .cv-doc-desc a {
            color: var(--cv-accent);
            text-decoration: underline;
        }
        /* Blockquote — printed-document style */
        .cv-doc-desc blockquote {
            margin: 0.6em 0;
            padding: 6px 14px;
            border-left: 3px solid #c8cdd8;
            color: #4a5160;
            font-style: italic;
            background: rgba(255, 255, 255, 0.5);
        }
        /* Code (rarely used but harmless) */
        .cv-doc-desc code,
        .cv-doc-desc pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.92em;
            background: #f4f5f8;
            padding: 1px 5px;
            border-radius: 2px;
        }
        .cv-doc-desc pre {
            padding: 8px 12px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        /* Quill alignment classes (the card viewer never loads quill.snow.css
           because it's a read-only render surface — replicate the rules we
           actually need so Quill-aligned text stays aligned). */
        .cv-doc-desc .ql-align-center  { text-align: center; }
        .cv-doc-desc .ql-align-right   { text-align: right; }
        .cv-doc-desc .ql-align-justify { text-align: justify; }
        .cv-doc-desc .ql-align-left    { text-align: left; }
        /* Images */
        .cv-doc-desc img { max-width: 100%; height: auto; }

        /* Activity reference image — rendered below the description as a
           framed figure, like a full-page plate in a printed report.
           Block-level + text-align center so narrow images sit centered
           inside the doc-item content column, and tall portrait shots
           still display large without overflowing the slide. */
        .cv-doc-image {
            margin: 16px 0 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #d8dde6;
            border-radius: 3px;
            display: block;
            text-align: center;
            max-width: 100%;
        }
        .cv-doc-image img {
            display: inline-block;
            max-width: 100%;
            max-height: 720px;
            border-radius: 2px;
        }

        /* In-document footer line — pins to the bottom of the slide-body
           via margin-top: auto so short pages still anchor the foot at
           the bottom of the page like a printed report. */
        .cv-doc-foot {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #ecf0f5;
            display: flex; justify-content: space-between;
            font-size: 12.5px; color: var(--cv-muted);
            letter-spacing: 0.5px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Empty section — document-style note */
        .cv-empty-msg {
            margin: 18px 0;
            padding: 28px 22px;
            text-align: center;
            color: var(--cv-muted);
            font-style: italic;
            border: 1px dashed #d9dde3;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 16px;
        }

        /* ============ FIXED FOOTER NAV ============ */
        .cv-footer {
            position: fixed; left: 0; right: 0; bottom: 0;
            background: var(--cv-surface);
            border-top: 1px solid var(--cv-line);
            box-shadow: 0 -2px 8px rgba(0,0,0,.05);
            padding: 10px 16px;
            display: flex; align-items: center; gap: 12px; justify-content: center;
            z-index: 100;
        }
        .cv-navbtn {
            background: var(--cv-accent); color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 6px;
            font-weight: 600; font-size: 13.5px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .cv-navbtn:disabled {
            background: #c5cad9; cursor: not-allowed;
        }
        .cv-navbtn:not(:disabled):hover { background: #4458c4; }
        .cv-navbtn i { font-size: 18px; }
        .cv-progress {
            background: #f1f3f7; color: #4a5160;
            padding: 6px 14px; border-radius: 14px;
            font-weight: 600; font-size: 13px;
            min-width: 110px; text-align: center;
            font-variant-numeric: tabular-nums;
        }
        .cv-progress strong { color: var(--cv-accent); }

        /* ============ FULLSCREEN MODE ============ */
        :fullscreen .cv-toolbar { background: #0f1421; }
        :fullscreen .cv-stage { min-height: calc(100vh - 110px); }

        /* ============ PRINT — one slide per page ============
           When printing, ditch the 16:9 aspect-ratio frame (which would
           leave a huge blank area on letter/A4 paper) and let each slide
           fill the printable page naturally. */
        /* Physical-sheet counter via @page margin box. The top-right
           .cv-page-num shows the slide's LOGICAL position in the full
           deck (e.g. "Page 17 of 114"); this bottom-right counter shows
           the PHYSICAL sheet number, which differs only when a single
           slide overflows onto a second printed sheet. The two
           indicators use different wording on purpose so they never
           read as contradictory.
           Chrome 131+ / Safari 18.2+ render this. Firefox ignores
           @page margin boxes — acceptable graceful degradation for an
           internal admin tool. */
        @page {
            size: A4 portrait;
            margin: 16mm 14mm;
            @bottom-right {
                content: "Sheet " counter(page) " of " counter(pages);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                font-size: 9pt;
                color: #999;
            }
        }
        /* Cover sheet: omit the page-number margin per Chicago title-page
           convention. The cover already says "Cover · Page 1 of N" in
           its own footer text — no need to duplicate it in the margin. */
        @page :first { @bottom-right { content: none; } }
        @media print {
            body { background: #fff; padding: 0; }
            .cv-toolbar, .cv-footer { display: none !important; }
            .cv-stage {
                min-height: 0;
                padding: 0;
                display: block;
            }
            .cv-slide {
                display: block !important;
                aspect-ratio: auto;
                width: 100%; max-width: none;
                box-shadow: none; margin: 0; padding: 0;
                border: 0; border-radius: 0; overflow: visible;
                page-break-after: always; break-after: page;
                animation: none;
            }
            .cv-slide:last-of-type { page-break-after: auto; }
            .cv-slide-body {
                overflow: visible !important;
                padding: 0 4px;
                display: block !important;
            }
            .cv-slide[data-index="0"] .cv-slide-body {
                display: block !important;
                padding: 0 4px;
            }
            .cv-day-head {
                background: none;
                border-radius: 0;
                padding: 0 4px 10px;
            }
            .cv-doc-item { page-break-inside: avoid; }
            .cv-cover-rules,
            .cv-cover-intro,
            .cv-cover-facts { page-break-inside: avoid; }

            /* Single-page print mode — toggled by the "Print Page" button.
               Hide every slide except the active one so the print dialog
               sees a single document. Strip the page-break rule on the
               active slide so it doesn't push the (empty) trailing page. */
            body.cv-print-current-only .cv-slide { display: none !important; }
            body.cv-print-current-only .cv-slide.active {
                display: block !important;
                page-break-after: auto;
                break-after: auto;
            }
        }

        /* ============ TINY RULES MODAL (compact viewer trigger) ============ */
        .cv-modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 20, 33, .55);
            display: none; align-items: center; justify-content: center;
            z-index: 200;
        }
        .cv-modal-backdrop.active { display: flex; }
        .cv-modal {
            background: #fff; max-width: 560px; width: 92vw; max-height: 80vh;
            border-radius: 8px; padding: 20px 26px;
            overflow-y: auto;
            box-shadow: 0 12px 40px rgba(0,0,0,.25);
        }
        .cv-modal h2 {
            margin: 0 0 12px; font-size: 18px; color: #8a1d1d;
            display: flex; align-items: center; gap: 6px;
        }
        .cv-modal ol { padding-left: 22px; margin: 0; }
        .cv-modal li { margin: 4px 0; color: #5a2828; line-height: 1.55; }
        .cv-modal-close {
            margin-top: 16px;
            background: #f1f3f7; border: none;
            padding: 7px 16px; border-radius: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="cv-toolbar">
    <span class="cv-title">{{ $schedule->title }}</span>
    @if($activeVersion)
        <span class="cv-version"><i class="bx bx-git-branch"></i> {{ $activeVersion->versionName }}</span>
    @endif
    <span class="cv-counter">
        <strong class="cv-current">1</strong> / <span class="cv-total">{{ count($slides) + 1 }}</span>
    </span>
    <select class="cv-jump" title="Jump to a specific slide">
        <option value="0">— Cover —</option>
        @foreach($slides as $i => $s)
            <option value="{{ $i + 1 }}">
                Day {{ $s['dayIndex'] }} · {{ $s['date']->format('D, M j, Y') }}
            </option>
        @endforeach
    </select>
    <span class="cv-spacer"></span>
    <button class="cv-iconbtn" id="cvToggleIrrigationBtn" title="Hide / show irrigation sections on every slide"><i class="bx bx-water"></i> <span class="cv-toggle-irr-label">Hide Irrigation</span></button>
    <button class="cv-iconbtn" id="cvFullscreenBtn" title="Toggle fullscreen"><i class="bx bx-fullscreen"></i> Fullscreen</button>
    <button class="cv-iconbtn" id="cvSaveImageBtn" title="Save current page as a PNG image"><i class="bx bx-image-add"></i> Save as Image</button>
    <button class="cv-iconbtn" id="cvPrintPageBtn" title="Print only the current page"><i class="bx bx-file"></i> Print Page</button>
    <button class="cv-iconbtn" id="cvPrintBtn" title="Print all slides (one per page)"><i class="bx bx-printer"></i> Print All</button>
    <button class="cv-iconbtn" id="cvCloseBtn" title="Close (Esc)" onclick="window.close()"><i class="bx bx-x"></i> Close</button>
</div>

<div class="cv-stage">

    {{-- ============================================================
         SLIDE 0: Cover (always rendered)
    ============================================================ --}}
    <section class="cv-slide active" data-index="0" data-date="">
        <div class="cv-slide-body">
            <div class="cv-cover">
                <p class="cv-cover-org">Schedule of Activities</p>
                <h1>{{ $schedule->title }}</h1>
                @if($firstDate && $lastDate)
                    <div class="cv-cover-span">
                        {{ $firstDate->format('F j, Y') }} &ndash; {{ $lastDate->format('F j, Y') }}
                        @if($activeVersion)
                            &middot; Version: {{ $activeVersion->versionName }}
                        @endif
                    </div>
                @endif

                {{-- Definition-list-style facts. Reads like the data
                     summary on the cover of a printed report. --}}
                <div class="cv-cover-facts">
                    <div class="cv-cover-fact">
                        <span class="cv-cover-fact-label">Active days</span>
                        <span class="cv-cover-fact-value">{{ count($slides) }}</span>
                    </div>
                    <div class="cv-cover-fact">
                        <span class="cv-cover-fact-label">Activities</span>
                        <span class="cv-cover-fact-value">{{ $schedule->activities->count() }}</span>
                    </div>
                    <div class="cv-cover-fact">
                        <span class="cv-cover-fact-label">Lots</span>
                        <span class="cv-cover-fact-value">{{ $schedule->lots->count() }}</span>
                    </div>
                    <div class="cv-cover-fact">
                        <span class="cv-cover-fact-label">Workers</span>
                        <span class="cv-cover-fact-value">{{ $schedule->workers->count() }}</span>
                    </div>
                    @if($schedule->irrigations->count() > 0)
                        <div class="cv-cover-fact">
                            <span class="cv-cover-fact-label">Irrigation cycles</span>
                            <span class="cv-cover-fact-value">{{ $schedule->irrigations->count() }}</span>
                        </div>
                    @endif
                    @if($schedule->dayType)
                        <div class="cv-cover-fact">
                            <span class="cv-cover-fact-label">Day reference</span>
                            <span class="cv-cover-fact-value">{{ $schedule->dayType }}</span>
                        </div>
                    @endif
                </div>

                @if($criticalRules->count() > 0)
                    <div class="cv-cover-rules">
                        <h2>Critical Rules &mdash; Read Every Time</h2>
                        <ol>
                            @foreach($criticalRules as $rule)
                                <li>{{ $rule->ruleText }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if($activeVersion && !empty($activeVersion->globalActivityNote))
                    <div class="cv-cover-intro">
                        {!! $activeVersion->globalActivityNote !!}
                    </div>
                @endif

                @if($criticalRules->count() === 0 && (!$activeVersion || empty($activeVersion->globalActivityNote)))
                    <p class="cv-cover-empty">
                        No protocol introduction or critical rules defined yet.
                        Use the <strong>Documentation</strong> page of this schedule to add them.
                    </p>
                @endif
            </div>

            {{-- Document foot — generated date + page marker --}}
            <div class="cv-cover-foot">
                <span>AniSystem by AniSenso &middot; Generated {{ $generatedAt->format('M j, Y') }}</span>
                <span>Cover &middot; Page 1 of {{ count($slides) + 1 }}</span>
            </div>
        </div> {{-- /.cv-slide-body --}}
    </section>

    {{-- ============================================================
         SLIDES 1..N: One per active day
    ============================================================ --}}
    @foreach($slides as $i => $s)
        @php
            $slideIdx = $i + 1;
            $dateCarbon = $s['date'];
            $activitiesForDay = $s['activities'];
            $irrEntries = $s['irrigations'];
            $note = $s['note'];
        @endphp
        <section class="cv-slide" data-index="{{ $slideIdx }}" data-date="{{ $s['dateKey'] }}">

            {{-- Document title block — pinned to the top of the slide
                 frame so it stays visible even when the body scrolls.
                 Schedule micro-line, then the date heading, then the
                 dateline (weekday + day index). --}}
            <div class="cv-day-head">
                {{-- Page indicator badge sits in the top-right corner of
                     the slide header. Server-rendered with the slide's
                     logical position in the full deck so it stays
                     correct in both "Print All" and single-slide
                     "Print Page" modes (which the live counter(page)
                     can't do). --}}
                <span class="cv-page-num">Page {{ $slideIdx + 1 }} of {{ count($slides) + 1 }}</span>
                <p class="cv-doc-org">
                    {{ $schedule->title }}
                    @if($activeVersion) &middot; {{ $activeVersion->versionName }} @endif
                    &middot; Daily Schedule
                </p>
                <h1 class="cv-day-heading">{{ $dateCarbon->format('F j, Y') }}</h1>
                <p class="cv-day-dateline">
                    {{ $dateCarbon->format('l') }}
                    &middot; Day {{ $s['dayIndex'] }} of {{ count($slides) }}
                </p>
            </div>

            <div class="cv-slide-body">
                @if($criticalRules->count() > 0)
                    <p class="cv-rules-banner">
                        <span class="cv-rules-banner-mark">&#9888;</span>
                        <span class="cv-rules-banner-count">{{ $criticalRules->count() }}</span>
                        critical {{ \Illuminate\Support\Str::plural('rule', $criticalRules->count()) }}
                        apply every day &mdash; see cover page.
                    </p>
                @endif

                @if($note)
                    <blockquote class="cv-day-note">
                        <span class="cv-day-note-label">Note &mdash;</span>
                        {!! nl2br(e($note->noteContent)) !!}
                    </blockquote>
                @endif

                @if(!empty($irrEntries))
                    {{-- data-section="irrigation" so the toolbar toggle
                         can hide all irrigation sections at once via a
                         single body-level class. --}}
                    <section class="cv-doc-section" data-section="irrigation">
                        <h2 class="cv-doc-section-head">
                            Irrigation
                            <span class="cv-doc-section-count">
                                {{ count($irrEntries) }}
                                {{ \Illuminate\Support\Str::plural('entry', count($irrEntries)) }}
                            </span>
                        </h2>
                        <ol class="cv-doc-list">
                            @foreach($irrEntries as $iEntry)
                                @php
                                    $iIrr = $iEntry['irrigation'];
                                    $iMeta = $iEntry['taskMeta'];
                                    $iPrio = (int) ($iEntry['priority'] ?? 5);
                                @endphp
                                <li class="cv-doc-item cv-item-irr" style="--c: {{ $iMeta['color'] }};">
                                    <div class="cv-doc-item-head">
                                        <span class="cv-doc-item-title">{{ $iIrr->irrigationTitle }}</span>
                                        <span class="cv-doc-tag cv-tag-irr" style="--c: {{ $iMeta['color'] }};">
                                            {{ $iMeta['label'] }}
                                        </span>
                                        <span class="cv-doc-tag">Priority {{ $iPrio }}</span>
                                    </div>
                                    <div class="cv-doc-meta">
                                        @if(!empty($iEntry['groupNames']))
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Groups</span>
                                                <span class="cv-doc-meta-value">{{ implode(', ', $iEntry['groupNames']) }}</span>
                                            </div>
                                        @endif
                                        @if($iIrr->lots && $iIrr->lots->count() > 0)
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Lots</span>
                                                <span class="cv-doc-meta-value">
                                                    {{-- Inline join. Blade's directive parser barfs on
                                                         chained `@endif@if(...)` without whitespace, so
                                                         build the trailing separator via {{ }} ternary. --}}
                                                    @foreach($iIrr->lots as $lot)
                                                        {{ $lot->lotName }}@if(!empty($lot->variety)) &middot; {{ $lot->variety }}@endif{{ $loop->last ? '' : ', ' }}
                                                    @endforeach
                                                </span>
                                            </div>
                                        @endif
                                        @if($iIrr->workers && $iIrr->workers->count() > 0)
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Workers</span>
                                                <span class="cv-doc-meta-value">
                                                    <span class="cv-doc-workers">
                                                        @foreach($iIrr->workers as $w)
                                                            <span class="cv-doc-worker"><i class="bx bx-user"></i>{{ $w->workerName }}</span>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($iIrr->description)
                                        <div class="cv-doc-desc">{{ $iIrr->description }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($activitiesForDay->count() > 0)
                    <section class="cv-doc-section">
                        <h2 class="cv-doc-section-head">
                            Activities
                            <span class="cv-doc-section-count">
                                {{ $activitiesForDay->count() }}
                                {{ \Illuminate\Support\Str::plural('item', $activitiesForDay->count()) }}
                            </span>
                        </h2>
                        <ol class="cv-doc-list">
                            @foreach($activitiesForDay as $a)
                                @php
                                    $start = $a->targetDate;
                                    $end   = $a->targetEndDate ?: $start;
                                    $isMultiDay = $end->gt($start);
                                    $multiCurrent = $isMultiDay ? ($start->diffInDays($dateCarbon) + 1) : null;
                                    $multiTotal   = $isMultiDay ? ($start->diffInDays($end) + 1) : null;
                                    $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                                    $typeLabel = $a->activityType ? (\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] ?? null) : null;
                                @endphp
                                <li class="cv-doc-item cv-item-prio-{{ $a->priority }}">
                                    <div class="cv-doc-item-head">
                                        <span class="cv-doc-item-title">{{ $a->activityTitle }}</span>
                                        @if($typeLabel)
                                            <span class="cv-doc-tag">{{ $typeLabel }}</span>
                                        @endif
                                        <span class="cv-doc-tag cv-tag-prio-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                                        @if($isMultiDay)
                                            <span class="cv-doc-tag cv-tag-multiday">
                                                Day {{ $multiCurrent }} of {{ $multiTotal }}
                                            </span>
                                        @endif
                                        @if($a->isDayZero)
                                            <span class="cv-doc-tag cv-tag-day0">{{ $schedule->dayType }} 0</span>
                                        @endif
                                        {{-- Per-activity image download. Click captures the
                                             whole .cv-doc-item with html2canvas, excluding
                                             the button itself via the onclone strip below. --}}
                                        <button type="button" class="cv-item-download-btn" title="Download this activity as a PNG image" aria-label="Download activity image">
                                            <i class="bx bx-image-add"></i>
                                        </button>
                                    </div>
                                    <div class="cv-doc-meta">
                                        @if($isMultiDay)
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Span</span>
                                                <span class="cv-doc-meta-value">{{ $start->format('M j') }} &ndash; {{ $end->format('M j, Y') }}</span>
                                            </div>
                                        @endif
                                        <div class="cv-doc-meta-row">
                                            <span class="cv-doc-meta-label">Time</span>
                                            <span class="cv-doc-meta-value">{{ $timeLabel }}</span>
                                        </div>
                                        @if($a->lots->count() > 0)
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Lots</span>
                                                <span class="cv-doc-meta-value">
                                                    @foreach($a->lots as $lot)
                                                        {{ $lot->lotName }}@if(!empty($lot->variety)) &middot; {{ $lot->variety }}@endif{{ $loop->last ? '' : ', ' }}
                                                    @endforeach
                                                </span>
                                            </div>
                                        @endif
                                        @if($a->workers->count() > 0)
                                            <div class="cv-doc-meta-row">
                                                <span class="cv-doc-meta-label">Workers</span>
                                                <span class="cv-doc-meta-value">
                                                    <span class="cv-doc-workers">
                                                        @foreach($a->workers as $w)
                                                            <span class="cv-doc-worker"><i class="bx bx-user"></i>{{ $w->workerName }}</span>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            </div>
                                        @endif
                                        @if($a->items->count() > 0)
                                            @php
                                                $materialBits = [];
                                                $serviceBits  = [];
                                                foreach ($a->items as $it) {
                                                    $qtyTrim = rtrim(rtrim((string) $it->quantity, '0'), '.');
                                                    $unit = $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '');
                                                    if ($it->itemType === 'material' && $it->material) {
                                                        $materialBits[] = $it->material->materialName . ' ×' . $qtyTrim . ($unit ? ' ' . $unit : '');
                                                    } elseif ($it->itemType === 'service' && $it->service) {
                                                        $svc = $it->service->serviceName;
                                                        if ($qtyTrim !== '1' || $unit) {
                                                            $svc .= ' ×' . $qtyTrim . ($unit ? ' ' . $unit : '');
                                                        }
                                                        $serviceBits[] = $svc;
                                                    }
                                                }
                                            @endphp
                                            @if(!empty($materialBits))
                                                <div class="cv-doc-meta-row">
                                                    <span class="cv-doc-meta-label">Materials</span>
                                                    <span class="cv-doc-meta-value">{{ implode(', ', $materialBits) }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($serviceBits))
                                                <div class="cv-doc-meta-row">
                                                    <span class="cv-doc-meta-label">Services</span>
                                                    <span class="cv-doc-meta-value">{{ implode(', ', $serviceBits) }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    @if($a->description)
                                        <div class="cv-doc-desc">{!! $a->description !!}</div>
                                    @endif
                                    @if($a->imagePath)
                                        <div class="cv-doc-image">
                                            <img src="{{ $a->imageUrl() }}" alt="Activity image" loading="lazy">
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($activitiesForDay->count() === 0 && empty($irrEntries))
                    <div class="cv-empty-msg">
                        No activities or irrigation scheduled &mdash; this day has a note only.
                    </div>
                @endif

                {{-- Document foot — schedule + version on the left, day
                     index + date on the right, mirroring a printed
                     report's page footer. --}}
                <div class="cv-doc-foot">
                    <span>
                        {{ $schedule->title }}@if($activeVersion) &middot; {{ $activeVersion->versionName }}@endif
                    </span>
                    <span>
                        Day {{ $s['dayIndex'] }} of {{ count($slides) }}
                        &middot; {{ $dateCarbon->format('M j, Y') }}
                    </span>
                </div>
            </div> {{-- /.cv-slide-body --}}
        </section>
    @endforeach

    @if(count($slides) === 0)
        <section class="cv-slide" data-index="1">
            <div class="cv-slide-body">
                <div class="cv-empty-msg" style="padding: 60px 20px;">
                    No activities, irrigation, or notes scheduled yet. Add some on the Activities
                    page and they'll appear as daily slides here.
                </div>
            </div>
        </section>
    @endif
</div>

<div class="cv-footer">
    <button class="cv-navbtn" id="cvPrev" disabled>
        <i class="bx bx-chevron-left"></i> Previous
    </button>
    <span class="cv-progress">
        Slide <strong class="cv-current2">1</strong> of <span class="cv-total2">{{ count($slides) + 1 }}</span>
    </span>
    <button class="cv-navbtn" id="cvNext" @if(count($slides) === 0) disabled @endif>
        Next <i class="bx bx-chevron-right"></i>
    </button>
</div>

{{-- Critical rules quick-view modal (opened by clicking the day-slide banner) --}}
@if($criticalRules->count() > 0)
    <div class="cv-modal-backdrop" id="cvRulesModal">
        <div class="cv-modal">
            <h2><i class="bx bx-flag"></i> Critical Rules</h2>
            <ol>
                @foreach($criticalRules as $rule)
                    <li>{{ $rule->ruleText }}</li>
                @endforeach
            </ol>
            <button class="cv-modal-close" type="button">Close</button>
        </div>
    </div>
@endif

<script>
(function () {
    const slides   = Array.from(document.querySelectorAll('.cv-slide'));
    const total    = slides.length;
    const $current = document.querySelectorAll('.cv-current, .cv-current2');
    const $total   = document.querySelectorAll('.cv-total, .cv-total2');
    const $prev    = document.getElementById('cvPrev');
    const $next    = document.getElementById('cvNext');
    const $jump    = document.querySelector('.cv-jump');
    let current = 0;

    // Schedule slug used as a namespace prefix for localStorage keys
    // (irrigation toggle, etc.) AND as the default filename for the
    // "Save as Image" downloads further down.
    const SCHEDULE_SLUG = @json(\Illuminate\Support\Str::slug($schedule->title));

    // Initialize total counters (in case slide count came from JS)
    $total.forEach(el => el.textContent = String(total));

    function show(i) {
        if (i < 0 || i >= total || i === current) return;
        slides[current].classList.remove('active');
        current = i;
        slides[current].classList.add('active');
        $current.forEach(el => el.textContent = String(i + 1));
        if ($jump) $jump.value = String(i);
        $prev.disabled = (i === 0);
        $next.disabled = (i === total - 1);
        // Scroll the slide container to the top — useful when a previous
        // slide was long and the user scrolled within it.
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    $prev.addEventListener('click', () => show(current - 1));
    $next.addEventListener('click', () => show(current + 1));
    if ($jump) {
        $jump.addEventListener('change', e => show(parseInt(e.target.value, 10) || 0));
    }

    // Keyboard navigation. Ignore when a form input has focus so a date
    // picker / textarea / select in a modal doesn't get hijacked.
    document.addEventListener('keydown', e => {
        if (e.target.matches('input, textarea, select')) return;
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        switch (e.key) {
            case 'ArrowRight':
            case 'PageDown':
            case ' ':
                e.preventDefault(); show(current + 1); break;
            case 'ArrowLeft':
            case 'PageUp':
                e.preventDefault(); show(current - 1); break;
            case 'Home':
                e.preventDefault(); show(0); break;
            case 'End':
                e.preventDefault(); show(total - 1); break;
            case 'f':
            case 'F':
                toggleFullscreen(); break;
            case 'Escape':
                if (document.fullscreenElement) document.exitFullscreen();
                break;
        }
    });

    // Fullscreen toggle
    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    }
    document.getElementById('cvFullscreenBtn').addEventListener('click', toggleFullscreen);

    // Irrigation visibility toggle — global per-deck preference stored in
    // localStorage so the choice survives refresh. The CSS rule does the
    // actual hiding; this just flips the body class + button label.
    const IRR_HIDE_KEY = 'cvHideIrrigation:' + SCHEDULE_SLUG;
    const $irrBtn      = document.getElementById('cvToggleIrrigationBtn');
    const $irrLabel    = $irrBtn ? $irrBtn.querySelector('.cv-toggle-irr-label') : null;
    function applyIrrigationHidden(hidden) {
        document.body.classList.toggle('cv-hide-irrigation', hidden);
        if ($irrLabel) $irrLabel.textContent = hidden ? 'Show Irrigation' : 'Hide Irrigation';
        if ($irrBtn)   $irrBtn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    }
    if ($irrBtn) {
        applyIrrigationHidden(localStorage.getItem(IRR_HIDE_KEY) === '1');
        $irrBtn.addEventListener('click', () => {
            const next = !document.body.classList.contains('cv-hide-irrigation');
            applyIrrigationHidden(next);
            localStorage.setItem(IRR_HIDE_KEY, next ? '1' : '0');
        });
    }

    // Print all — let the browser open its print dialog. The print CSS
    // forces one slide per page so the user gets a printed deck.
    document.getElementById('cvPrintBtn').addEventListener('click', () => window.print());

    // Print only the current slide. Adds a body class that the print CSS
    // uses to hide every non-active slide, fires window.print(), then
    // clears the class on afterprint (with a setTimeout fallback for
    // browsers that don't fire afterprint reliably — Safari historically).
    const $printPageBtn = document.getElementById('cvPrintPageBtn');
    if ($printPageBtn) {
        $printPageBtn.addEventListener('click', () => {
            document.body.classList.add('cv-print-current-only');
            let cleared = false;
            const clear = () => {
                if (cleared) return;
                cleared = true;
                document.body.classList.remove('cv-print-current-only');
                window.removeEventListener('afterprint', clear);
            };
            window.addEventListener('afterprint', clear);
            setTimeout(clear, 5000);
            window.print();
        });
    }

    // Save current slide as a PNG image. Uses html2canvas to rasterize
    // the .cv-slide.active element at 2x DPI (so text stays crisp on
    // retina screens), then triggers a download via a blob URL.
    // Filename: {schedule-slug}-day-{N}-{YYYY-MM-DD}.png  (or `-cover` for slide 0)
    const $saveBtn = document.getElementById('cvSaveImageBtn');
    const SAVE_BTN_LABEL = $saveBtn ? $saveBtn.innerHTML : '';
    if ($saveBtn) {
        $saveBtn.addEventListener('click', async () => {
            if (typeof html2canvas === 'undefined') {
                alert('Image capture library failed to load. Check your connection and try again.');
                return;
            }
            const active = document.querySelector('.cv-slide.active');
            if (!active) return;

            $saveBtn.disabled = true;
            $saveBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Capturing…';

            try {
                // Capture the full slide regardless of viewport scroll. We
                // use the element's scrollWidth/Height for windowWidth/Height
                // so html2canvas lays out at the slide's natural width
                // (avoids mobile-breakpoint shrinkage when the browser
                // window is narrower than the slide).
                const canvas = await html2canvas(active, {
                    scale: 2,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    logging: false,
                    windowWidth: Math.max(active.scrollWidth, 1100),
                    windowHeight: active.scrollHeight,
                });

                const idx  = active.getAttribute('data-index') || '0';
                const date = active.getAttribute('data-date') || '';
                const filename = (idx === '0' || !date)
                    ? `${SCHEDULE_SLUG}-cover.png`
                    : `${SCHEDULE_SLUG}-day-${idx}-${date}.png`;

                canvas.toBlob((blob) => {
                    if (!blob) {
                        alert('Failed to encode image.');
                        return;
                    }
                    const url = URL.createObjectURL(blob);
                    const a   = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    // Give the download a beat to register before revoking.
                    setTimeout(() => URL.revokeObjectURL(url), 1500);
                }, 'image/png');
            } catch (err) {
                console.error('Save as Image failed:', err);
                alert('Could not save image: ' + (err && err.message ? err.message : 'unknown error'));
            } finally {
                $saveBtn.disabled = false;
                $saveBtn.innerHTML = SAVE_BTN_LABEL;
            }
        });
    }

    // Per-activity download — small icon button at the end of every
    // activity item's head row. Captures just that single .cv-doc-item
    // element via html2canvas, scrubs the button itself out of the
    // cloned DOM via onclone so it doesn't leak into the saved PNG.
    // Event-delegated on document so it works regardless of slide state.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.cv-item-download-btn');
        if (!btn) return;
        if (btn.classList.contains('is-busy')) return;

        const item = btn.closest('.cv-doc-item');
        if (!item) return;
        if (typeof html2canvas === 'undefined') {
            alert('Image capture library failed to load. Reload the page and try again.');
            return;
        }

        // Filename: {schedule}-{date}-{activity-slug}.png
        const titleEl = item.querySelector('.cv-doc-item-title');
        const title   = titleEl ? titleEl.textContent.trim() : 'activity';
        const slug    = title.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 60) || 'activity';
        const slide   = item.closest('.cv-slide');
        const date    = (slide && slide.getAttribute('data-date')) || 'cover';
        const filename = `${SCHEDULE_SLUG}-${date}-${slug}.png`;

        btn.classList.add('is-busy');
        try {
            const canvas = await html2canvas(item, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
                // Strip the download button from the cloned DOM so the
                // captured image doesn't show a tiny "download" icon
                // floating in the upper-right corner.
                onclone: (doc) => {
                    doc.querySelectorAll('.cv-item-download-btn').forEach(b => b.remove());
                },
            });
            canvas.toBlob((blob) => {
                if (!blob) { alert('Failed to encode image.'); return; }
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1500);
            }, 'image/png');
        } catch (err) {
            console.error('Activity image capture failed:', err);
            alert('Could not save image: ' + (err && err.message ? err.message : 'unknown error'));
        } finally {
            btn.classList.remove('is-busy');
        }
    });

    // Critical rules quick-view modal: clicking the compact banner on a
    // day slide opens a popup with the full list, so workers don't have
    // to navigate back to the cover slide every time.
    const $rulesModal = document.getElementById('cvRulesModal');
    if ($rulesModal) {
        document.addEventListener('click', e => {
            if (e.target.closest('.cv-rules-banner')) {
                $rulesModal.classList.add('active');
            } else if (e.target === $rulesModal || e.target.matches('.cv-modal-close')) {
                $rulesModal.classList.remove('active');
            }
        });
    }

    // If the URL has #day=YYYY-MM-DD, jump to that slide on load.
    // Useful for sharing deep-links to a specific day.
    const hashMatch = location.hash.match(/day=(\d{4}-\d{2}-\d{2})/);
    if (hashMatch) {
        const targetIdx = slides.findIndex(s => s.dataset.date === hashMatch[1]);
        if (targetIdx > 0) show(targetIdx);
    }

    // Update the URL hash when navigating so refresh / bookmark works.
    const setHash = (i) => {
        const d = slides[i] && slides[i].dataset.date;
        if (d) history.replaceState(null, '', '#day=' + d);
        else   history.replaceState(null, '', '#cover');
    };
    const _origShow = show;
    // Wrap show to also update the hash.
    window.cvShow = function (i) { _origShow(i); setHash(current); };
    $prev.removeEventListener('click', _origShow);
    // We can't easily replace the handlers, so just push hash on the same events:
    $prev.addEventListener('click', () => setHash(current));
    $next.addEventListener('click', () => setHash(current));
    if ($jump) $jump.addEventListener('change', () => setHash(current));
    document.addEventListener('keydown', () => setHash(current));
})();
</script>

</body>
</html>
