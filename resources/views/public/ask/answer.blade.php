@extends('layouts.public')

{{-- The emailed button lands here the first time: Anee writes the answer
     (AskAneeController::start, a job) while the page waits, then goes to
     the finished page at /question/{slug}. Never indexed. --}}
@include('public.partials.site-css')

@section('title', 'Your answer from Anee')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
    .aa-wrap { min-height: 60vh; background: linear-gradient(160deg, #f3f8ec, #fff 70%); }
    .aa-card { max-width: 38rem; margin: 0 auto; padding: 1.4rem; border-radius: 1.5rem; background: #fff; border: 1px solid #e2ecd6;
        box-shadow: 0 30px 70px -45px rgb(20 40 10 / .5); }
    .aa-card img.av { width: 3.2rem; height: 3.2rem; border-radius: 999px; object-fit: cover; }
    .aa-q { margin-top: 1rem; padding: .9rem 1rem; border-radius: 1rem; background: #f3f8ec; color: #1f3a0f; font-weight: 600; line-height: 1.55; }
    .aa-facts { margin-top: .8rem; display: flex; flex-wrap: wrap; gap: .4rem; }
    .aa-facts span { padding: .3rem .6rem; border-radius: 999px; background: #fff; border: 1px solid #dbe8cc; font-size: .8rem; font-weight: 700; color: #3d6823; }
    .aa-err { margin-top: 1rem; padding: .9rem 1rem; border-radius: 1rem; background: #fef2f2; color: #991b1b; font-size: .92rem; }
</style>
<section class="aa-wrap">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <div class="aa-card">
            <div class="flex items-center gap-3">
                <img class="av" src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee">
                <div>
                    <p class="font-heading text-lg font-bold text-ink">Your answer from Anee</p>
                    <p class="text-sm text-gray-500">I am writing it for your farm now.</p>
                </div>
            </div>
            <p class="aa-q">“{{ $q->question }}”</p>
            <div class="aa-facts">
                @if ($q->cropLabel)<span>🌱 {{ \Illuminate\Support\Str::before($q->cropLabel, ' (') }}</span>@endif
                @if ($q->farmWords())<span>📐 {{ $q->farmWords() }}</span>@endif
                @if ($q->placeWords())<span>📍 {{ $q->placeWords() }}</span>@endif
            </div>
            <div class="aa-err" id="aaErr" hidden></div>
            <button type="button" class="btn btn-accent mt-4 w-full justify-center" id="aaGo" hidden>Try again</button>
        </div>
    </div>
</section>
@include('sm.partials.anee-wait')
@endsection

@push('scripts')
<script>
(() => {
    const START = @json(route('ask.start', ['token' => $q->token]));
    const JOB = @json(route('ask.job', ['token' => $q->token]));
    const err = document.getElementById('aaErr'), again = document.getElementById('aaGo');
    const run = async () => {
        err.hidden = true; again.hidden = true;
        const w = window.aneeWait;
        w.show({
            title: 'Anee is writing your answer…',
            sub: 'A full answer takes a minute or two.',
            stay: 'Please keep this page open. Your answer opens here when it is ready.',
            lines: [
                'Reading your question once more…',
                'Checking what PhilRice and the DA recommend…',
                'Looking for the right numbers for your farm…',
                'Writing it in plain words…',
                'Adding the sources I used…',
            ],
        });
        try {
            let d = (await window.api(START, { method: 'POST' })).data || {};
            if (d.status !== 'ready') {
                d = await w.poll({ id: null, job: () => JOB, phases: w.phases.research });
            }
            await w.done({ title: 'Your answer is ready!', line: 'Opening it now.' });
            location.replace(d.url);
        } catch (e) {
            w.fail();
            err.textContent = e.message || 'The answer could not be written just now.';
            err.hidden = false;
            again.hidden = false;
        }
    };
    again.addEventListener('click', run);
    const go = () => (window.aneeWait && window.api ? run() : setTimeout(go, 120));
    go();
})();
</script>
@endpush
