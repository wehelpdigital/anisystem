{{-- Welcome hero for an empty personal AI thread. Rendered server-side when
     there is no history, and again inside <template id="aiWelcomeTpl"> so the
     "new question" flow can reset the thread without a reload.
     Expects: $settings. --}}
<div class="ai-hello" id="aiWelcome">
    <span class="aimsg-face mx-auto">
        @if ($settings->avatarPath)
            <img data-ai-face src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
        @else
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
        @endif
    </span>
    <h2>Magandang araw! Ask me about your crop</h2>
    <p class="sub">Fertiliser rates, pests and diseases, water, timing, harvest and storage. Snap a photo of a problem leaf and I will take a look.</p>
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
    <div class="grid gap-2.5 max-w-md mx-auto">
        <button type="button" class="aisuggest js-suggest">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg></span>
            <span class="t">My rice leaves are yellowing at the tips 25 days after sowing. What should I check?</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" class="aisuggest js-suggest">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg></span>
            <span class="t">How much urea per hectare for the first top dressing on inbred rice?</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" class="aisuggest js-suggest">
            <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg></span>
            <span class="t">When should I stop irrigating before harvest?</span>
            <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>
