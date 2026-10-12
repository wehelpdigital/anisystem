{{-- Anee Auto's showcase. $cta: the in-app start button (needs
     auto.partials.start on the page); $public: the public site's version,
     whose button goes to $href. --}}
@php
    $aaPublic = ! empty($public);
    $aaPrice = \App\Support\AiPrices::of('auto');
    $aaFace = \App\Support\AneeEmoji::url('salute');
@endphp
<section class="aa-hero" aria-labelledby="aaHeroTitle">
    <div class="aa-hero-in">
        <div>
            <span class="aa-kick">🤖 New · Anee Auto</span>
            <h2 id="aaHeroTitle">Anee plans your season. You just follow.</h2>
            <p class="aa-lead">Answer a few questions about your lots, your soil, your water and your fertilizer. Anee writes the whole season, then keeps it on track every day: she moves the work around the rain and your workers, and tells you what to do today.</p>
            <ul class="aa-points">
                <li style="--i:0"><span>📋</span>Today's tasks and this week's, simple, with the why one tap away</li>
                <li style="--i:1"><span>🌦️</span>Moved around the weather and who can work, every morning</li>
                <li style="--i:2"><span>🧪</span>Fertilizer at the moments the crop needs it most, from your own plan</li>
                <li style="--i:3"><span>🛡️</span>Prevention first: sprays by active ingredient, with safety and mixing checks</li>
                <li style="--i:4"><span>📸</span>Show her a photo, a video or a voice note from the field, and she adapts the plan</li>
                <li style="--i:5"><span>✉️</span>The day's plan by email, to you and to each worker</li>
            </ul>
            <div class="aa-cta">
                @if ($aaPublic)
                    <a href="{{ $href ?? '#' }}" class="aa-go">Try Anee Auto</a>
                    <span class="aa-price">Part of anee.io with Anee. <b>{{ number_format($aaPrice) }} credits per lot</b> for a whole season.</span>
                @elseif (! empty($cta))
                    <button type="button" class="aa-go" data-auto-start>✨ Start Anee Auto</button>
                    <span class="aa-price"><b>{{ number_format($aaPrice) }} credits per lot</b>, for the whole season</span>
                @else
                    <a href="{{ route('auto.index') }}" class="aa-go">Open Anee Auto</a>
                    <span class="aa-price"><b>{{ number_format($aaPrice) }} credits per lot</b>, for the whole season</span>
                @endif
            </div>
        </div>
        <div class="aa-phone" aria-hidden="true">
            <div class="aa-screen">
                <div class="aa-b" style="--d:0"><img src="{{ $aaFace }}" alt=""><div>Magandang umaga! Lot 1 is DAT 21. Rain 70% this afternoon, so we fertilize this morning.</div></div>
                <div class="aa-b" style="--d:1"><img src="{{ $aaFace }}" alt=""><div><b>Today</b>
                    <div class="aa-t"><i>🧪</i>Second fertilizer, Lot 1<em>Cannot wait</em></div>
                    <div class="aa-t"><i>💧</i>Keep 3 cm of water</div>
                    <div class="aa-t"><i>👀</i>Walk the field for stem borer</div></div></div>
                <div class="aa-b is-me" style="--d:2"><div>🙅 Can't spray tomorrow, no workers</div></div>
                <div class="aa-b" style="--d:3"><img src="{{ $aaFace }}" alt=""><div>No problem. I moved it to Thursday, the next dry morning with two hands free. 👍</div></div>
            </div>
        </div>
    </div>
</section>
