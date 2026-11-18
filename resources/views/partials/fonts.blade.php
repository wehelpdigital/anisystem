{{-- The two type faces, served from anee.io itself (2026-11-18 speed audit: the stylesheet from Google's servers
     and its own connection stood between a phone and its first paint). Instrument Sans and Nunito Sans are SIL Open
     Font License fonts; these are Google's own variable woff2 files (latin and latin-ext), fetched once by the
     scratchpad speed/fonts.py. The two latin files every page draws with are preloaded. --}}
<link rel="preload" href="/fonts/nunito-sans-latin.woff2?v=1" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/instrument-sans-latin.woff2?v=1" as="font" type="font/woff2" crossorigin>
<style>
    @font-face { font-family: 'Instrument Sans'; font-style: normal; font-weight: 400 700; font-stretch: 100%; font-display: swap; src: url('/fonts/instrument-sans-latin.woff2?v=1') format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Instrument Sans'; font-style: normal; font-weight: 400 700; font-stretch: 100%; font-display: swap; src: url('/fonts/instrument-sans-latin-ext.woff2?v=1') format('woff2'); unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF; }
    @font-face { font-family: 'Nunito Sans'; font-style: normal; font-weight: 400 800; font-stretch: 100%; font-display: swap; src: url('/fonts/nunito-sans-latin.woff2?v=1') format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Nunito Sans'; font-style: normal; font-weight: 400 800; font-stretch: 100%; font-display: swap; src: url('/fonts/nunito-sans-latin-ext.woff2?v=1') format('woff2'); unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF; }
</style>
