@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Contact Us')
@section('meta_description', 'Get in touch with the anee.io team. Questions about plans, GCash payments, or using the cropping schedule manager? Email support@anee.io — a real person replies, usually within a business day.')

@section('content')

    {{-- ================= HERO BAND ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <div class="absolute inset-0 bg-dot-grid opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-accent-400 ring-1 ring-white/20">
                Contact Us
            </span>
            <h1 class="mt-5 font-heading text-3xl sm:text-5xl font-bold text-white text-balance">We're Here to Help</h1>
            <p class="mt-4 max-w-xl mx-auto text-brand-100">
                Questions about plans, payments, or planning your season? One address, and a real person on the other end of it.
            </p>
        </div>
    </section>

    {{-- ================= ONE ADDRESS, WRITTEN LARGE =================
         The form is gone. A contact form is a box that swallows a message
         and says "thank you" — the sender cannot see what they sent, cannot
         attach the photo of the leaf they were asking about, and has nothing
         in their own outbox to follow up on. An email address gives them all
         three, in whatever app they already trust. --}}
    <section class="py-14 sm:py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">

            <div class="ct-mail reveal">
                <span class="ct-mail-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                </span>
                <p class="ct-mail-lead">Write to us at</p>
                <a class="ct-mail-addr" href="mailto:support@anee.io">support@anee.io</a>
                <p class="ct-mail-sub">
                    Plans, GCash payments, getting a season set up, or something that is not working —
                    send it here. A real person reads every one, usually within a business day.
                </p>
                <div class="ct-mail-acts">
                    <a href="mailto:support@anee.io" class="btn btn-primary btn-lg">Open your email app</a>
                    <button type="button" class="btn btn-outline btn-lg" id="ctCopyMail" data-mail="support@anee.io">Copy the address</button>
                </div>
                <p class="ct-mail-tip">
                    Sending a photo of the leaf, the label or the screen helps more than a paragraph describing it.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 mt-6">
                <div class="ct-side reveal" style="--reveal-delay:.06s">
                    <span class="ct-side-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <p class="ct-side-k">Where we are</p>
                    <p class="ct-side-p">The Philippines — built here, for farms here.</p>
                </div>
                <div class="ct-side reveal" style="--reveal-delay:.12s">
                    <span class="ct-side-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <p class="ct-side-k">When we answer</p>
                    <p class="ct-side-p">Monday to Saturday, usually the same day.</p>
                </div>
            </div>

            {{-- Most messages are a question the tutorial already answers, and
                 reading it is faster than waiting for a reply. --}}
            <div class="ct-tut reveal" style="--reveal-delay:.18s">
                <div>
                    <p class="ct-tut-k">New to anee.io?</p>
                    <p class="ct-tut-p">The tutorial covers payments, plans and getting a first season on the board — it answers most of what reaches this inbox.</p>
                </div>
                <a href="{{ route('tutorial') }}" class="btn btn-accent shrink-0">Read the Tutorial</a>
            </div>

        </div>
    </section>

    <script>
        (() => {
            const b = document.getElementById('ctCopyMail');
            if (!b) return;
            b.addEventListener('click', async () => {
                const mail = b.dataset.mail;
                const said = b.textContent;
                try {
                    await navigator.clipboard.writeText(mail);
                    b.textContent = 'Copied';
                } catch (_) {
                    // A browser that refuses the clipboard still shows the
                    // address in full above; nothing is lost but the shortcut.
                    b.textContent = mail;
                }
                setTimeout(() => { b.textContent = said; }, 1800);
            });
        })();
    </script>

@endsection
