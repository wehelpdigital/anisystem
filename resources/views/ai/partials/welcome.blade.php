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
    @include('partials.anee-hello-video')
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
</div>
