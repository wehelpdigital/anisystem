{{-- Welcome hero for an empty personal AI thread. Rendered server-side when
     there is no history, and again inside <template id="aiWelcomeTpl"> so the
     "new question" flow can reset the thread without a reload.
     Expects: $settings. --}}
<div class="ai-hello" id="aiWelcome">
    <span class="aimsg-face mx-auto">
            <img data-ai-face src="{{ $settings->faceUrl() }}" alt="">
    </span>
    {{-- She says her name first. The line about how to ask is not decoration:
         a vague question costs the same as a good one and comes back needing
         three more, so this is the highest-value thing on the screen. --}}
    <h2>Magandang araw! I'm {{ $settings->assistantName }}</h2>
    <p class="sub">Ask me anything about your crop, I'm willing to answer.</p>
    <div class="ai-howto">
        <p class="ai-howto-h">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            The more you tell me, the better I answer
        </p>
        <p class="ai-howto-b">Crop and age, what you did, what you see.</p>
        <p class="ai-howto-lbl">For example</p>
        <p class="ai-howto-eg"><b>Not</b> "my rice is sick"<br>
            <b>Try</b> "RC222 ang tanim ko, medyo naninilaw yung mga gilid na dahon at ang paninilaw ay nasa bandang gilid ng dahon. Kaka lagay ko lamang ng urea 10 days ago. Sobrang maulan kasi. Anong problema?"</p>
    </div>
    <div class="ai-caps">
        <span class="ai-cap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg>
            Leaf check
        </span>
        <span class="ai-cap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg>
            Water &amp; fertiliser
        </span>
        <span class="ai-cap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Timing
        </span>
    </div>
    <p class="ai-overline">Try one of these</p>
    <div class="grid gap-2 max-w-md mx-auto">
        <button type="button" class="aisuggest js-suggest"
                data-q="Ilang sako ng urea ang kailangan ko para sa isang ektarya?">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg></span>
            <span class="t">Ilang sako ng urea kada ektarya?</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" class="aisuggest js-suggest"
                data-q="How much urea per hectare for the first top dressing on inbred rice?">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg></span>
            <span class="t">Urea for the first top dressing</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" class="aisuggest js-suggest"
                data-q="When should I stop irrigating before harvest?">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg></span>
            <span class="t">When to stop irrigating</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>
