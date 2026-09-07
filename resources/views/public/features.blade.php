@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Features — Everything Your Farm Runs On')
@section('meta_description', 'Tour every anee.io feature: the activities board, worker logins with permissions, notes with photos and voice, maps, growth stages, weather, reports, inventory, the farmer community and Anee the AI technician.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">The full tour</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Everything Your Farm Runs On</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg max-w-2xl mx-auto">
                One app for the whole season — planning, people, money, records, weather and advice.
                Every screen below is the real thing.
            </p>
        </div>
    </section>

    <section class="py-16 sm:py-24 bg-white overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-20 sm:space-y-28">

            {{-- 1. The board --}}
            <div class="fx-row reveal">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/board.png') }}" alt="The activities board" loading="lazy"></span>
                </div>
                <div>
                    <p class="fx-kicker">Plan</p>
                    <h2 class="fx-h">The activities board — your season, day by day</h2>
                    <p class="fx-p">Build the whole calendar from land prep to harvest. Every task is dated from each lot's own Day-0, so timing stays honest even when lots were sown a week apart.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Tasks, irrigation, hired services, payroll days and reminder checklists</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Drag to reschedule, drafts for the undecided, versions for the what-ifs</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Attach photos, clips and voice notes to any activity</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Undo that survives a logout — the journal lives on the server</li>
                    </ul>
                </div>
            </div>

            {{-- 2. Workers --}}
            <div class="fx-row is-flip reveal">
                <div class="fx-media">
                    <img src="{{ asset('images/site/harvest-hands.jpg') }}" alt="Farm workers in the field"
                         class="rounded-2xl shadow-card-lg ring-1 ring-black/5 w-full max-w-md object-cover aspect-[4/3]" loading="lazy">
                </div>
                <div>
                    <p class="fx-kicker">People</p>
                    <h2 class="fx-h">Workers, payroll and permissions that fit a real farm</h2>
                    <p class="fx-p">Keep the roster with rates and skills, assign hands to activities, and count the labor cost live. Give a worker their own login and decide module by module what they can see and what they can touch.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>None / view / edit — per module, per worker</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Payroll days with per-worker rates and half/whole days</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>The morning email tells the whole team today's plan at 6 AM</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>A logs diary records every change and whose hand made it</li>
                    </ul>
                </div>
            </div>

            {{-- 3. Notes & media --}}
            <div class="fx-row reveal">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/notes.png') }}" alt="Notes with photos and voice recordings" loading="lazy"></span>
                </div>
                <div>
                    <p class="fx-kicker">Records</p>
                    <h2 class="fx-h">Notes, photos, videos and your own voice</h2>
                    <p class="fx-p">The fastest record is the one you can make standing in the mud. Snap it, film it, or just say it — Quick Voice files a spoken note in seconds, and everything lands in a gallery you can actually search.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Notes per season, per day, and global notes for everything else</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Voice notes play right on the card — in notes, activities and chat</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>A drawing pad for sketching over field photos</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Tags tie notes, workers, lots and documents together</li>
                    </ul>
                </div>
            </div>

            {{-- 4. Growth & weather --}}
            <div class="fx-row is-flip reveal">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/growth.png') }}" alt="Growth stages" loading="lazy"></span>
                </div>
                <div>
                    <p class="fx-kicker">Agronomy</p>
                    <h2 class="fx-h">Growth stages and weather that read your fields</h2>
                    <p class="fx-p">anee knows 85 Philippine crops. Pick any date and it says where every lot stands — the stage, what it needs, what to watch for — with the week's forecast beside it.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Palay, mais, gulay, fruit trees — annuals and perennials both</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Do-lists and watch-lists written for each stage</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Maps: draw and measure your lots, drop pins, save team maps</li>
                    </ul>
                </div>
            </div>

            {{-- 5. Money --}}
            <div class="fx-row reveal">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/dashboard.png') }}" alt="The dashboard with money and season summaries" loading="lazy"></span>
                </div>
                <div>
                    <p class="fx-kicker">Money</p>
                    <h2 class="fx-h">Inventory, expenses and reports that agree to the peso</h2>
                    <p class="fx-p">The shed keeps stock with every move logged and named. Labor, expenses and profit reports are computed straight from the plan — and Anee can write the season's full story on top.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Inventory items and moves, with an audit trail of who did what</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Labor, expenses and profit — computed, never guessed</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Post-harvest observations with yields, buyers and prices</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Every report renameable, taggable and kept on its shelf</li>
                    </ul>
                </div>
            </div>

            {{-- 6. Community --}}
            <div class="fx-row is-flip reveal">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/community.png') }}" alt="The farmer community" loading="lazy"></span>
                </div>
                <div>
                    <p class="fx-kicker">Community</p>
                    <h2 class="fx-h">Co-farmers, discussions and a ladder worth climbing</h2>
                    <p class="fx-p">A news feed for wins and warnings, focused discussion rooms, direct messages with photos, clips and voice notes — and a 50-rank ladder that turns helping into a game.</p>
                    <ul class="fx-list">
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Public, password and approval rooms for private groups</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>A team Collab Room per season: chat, whiteboard and calls</li>
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Level-ups ring the bell — confetti included</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= ANEE ================= --}}
    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400 reveal">And through all of it</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance reveal">Anee — the AI technician who knows your farm</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto reveal">
                She reads your schedules, stages and weather before answering. Ask in Tagalog or English,
                send a photo of the problem, run when-to-plant and what-to-plant analyses for your town,
                or have her write the whole season's report. Anee runs on credits — you pay only for what you ask.
            </p>
            <div class="mt-8 reveal">
                <a href="{{ route('pricing') }}" class="btn btn-accent btn-lg">See plans &amp; credits</a>
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Bring your next season here</h2>
            <p class="mt-4 text-gray-600">Set up your first cropping schedule in minutes — lots, workers, and the whole calendar.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">Get Started</a>
                <a href="{{ route('tutorial') }}" class="btn btn-outline btn-lg">Watch the tutorial</a>
            </div>
        </div>
    </section>

@endsection
