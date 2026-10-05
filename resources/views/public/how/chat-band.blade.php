{{-- Between steps 2 and 3 (HowItWorks::chat): a second phone, the real Anee
     chat, where a grower sends a photo of a sick leaf and asks what it is.
     Before Anee answers she works through three steps (the photo, the
     related data, the answer), shown in her bubble and lit in the notes
     beside the phone. Played by chatFilm() in the journey script. --}}
@php $hc = \App\Support\HowItWorks::chat(); @endphp
<section class="hw-band" id="hw-ask" aria-labelledby="hw-ask-h">
    <svg class="hw-band-svg" aria-hidden="true"></svg>
    <div class="hw-band-copy">
        <p class="hw-step">Any day <span>any question</span></p>
        <{{ $hwHead }} class="hw-h2" id="hw-ask-h">Ask Anee about your crops</{{ $hwHead }}>
        <p class="hw-sub">Snap a photo of what worries you and ask in {{ \App\Support\Region::ph() ? 'Tagalog or English' : 'plain English' }}. Anee studies the photo closely, checks the data related to your farm, then tells you what it is and what to do.</p>
    </div>
    <div class="hw-band-phone" aria-hidden="true">
        <div class="hw-phone2"><div class="hw-scr2"><div class="hwr">
            <div class="hw-scene s-chat is-on">
                <div class="hwr-top hwc-top">
                    <svg class="back" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <div class="t"><b>Anee, Your Smart Agri Technician</b><small>Crop questions, answered</small></div>
                    <span class="r">
                        <span class="kebab"><i></i><i></i><i></i></span>
                        <span class="hwr-bell"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg><em>1</em></span>
                        <span class="hwr-av">{{ $hc['initials'] }}</span>
                    </span>
                </div>
                <div class="hwc-body"><div class="hwc-list">
                    <div class="hwc-day"><span>Today</span></div>
                    <div class="hwc-msg me" data-c="ask">
                        <span class="hwc-face">{{ $hc['initials'] }}</span>
                        <div class="hwc-b">
                            <span class="hwc-pic"><img src="{{ asset($hc['photo']) }}" alt=""></span>
                            {{ $hc['question'] }}
                            <time>7:42 AM</time>
                        </div>
                    </div>
                    <div class="hwc-msg" data-c="wait">
                        <span class="hwc-face"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt=""></span>
                        <div class="hwc-b hwc-wait"><ol class="hwc-steps">@foreach ($hc['reading'] as $rd)<li><i></i>{{ $rd }}</li>@endforeach</ol></div>
                    </div>
                    <div class="hwc-msg" data-c="answer">
                        <span class="hwc-face"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt=""></span>
                        <div class="hwc-b">
                            <p><b>{{ $hc['lead'] }}</b> {{ $hc['body'] }}</p>
                            <ul>@foreach ($hc['steps'] as $stp)<li>{!! $stp !!}</li>@endforeach</ul>
                            <span class="cost">{{ $hc['cost'] }} credits</span>
                            <time>7:42 AM</time>
                        </div>
                    </div>
                </div></div>
                <div class="hwc-comp">
                    <div class="hwc-shot"><img src="{{ asset($hc['photo']) }}" alt=""><i>×</i></div>
                    <div class="hwc-row">
                        <span class="cam"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.9a2 2 0 001.7-.9l.8-1.2A2 2 0 0110.1 4h3.8a2 2 0 011.7.9l.8 1.2a2 2 0 001.7.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg></span>
                        <span class="in"><span class="ph">Ask about your crop</span><span class="v"></span><em></em></span>
                        <span class="go"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                    </div>
                </div>
                <p class="hwc-credits">≈ 4 credits per answer · +3 per photo · <i></i> {{ $hc['balance'] }}</p>
            </div>
            <span class="hw-tap"></span>
        </div></div></div>
    </div>
    <div class="hw-band-notes" aria-hidden="true">
        @foreach ($hc['notes'] as $k => [$emo, $t, $s])
            <div class="hw-note" style="--k: {{ $k }}"><span>{{ $emo }}</span><div><b>{{ $t }}</b><small>{{ $s }}</small></div></div>
        @endforeach
    </div>
    <script type="application/json" data-hw-chat>@json(['question' => $hc['question']])</script>
    <p class="sr-only">A phone shows the Anee chat: a grower attaches a photo of a rice leaf with pale blotches that have brown edges and asks what it is. Anee studies the photo closely, checks the related data, then answers. It looks like sheath blight. Hold off on more urea, spray a fungicide registered for it at the lower stems, and clear the straw and weeds after harvest.</p>
</section>
