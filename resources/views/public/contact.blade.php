@extends('layouts.public')

@section('title', 'Contact Us')
@section('meta_description', 'Get in touch with the AniSystem team. Questions about plans, GCash payments, or using the cropping schedule manager? Send us a message — we reply fast.')

@section('content')

    {{-- ================= HERO BAND ================= --}}
    <section class="bg-brand-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Contact Us</p>
            <h1 class="mt-3 font-heading text-3xl sm:text-5xl font-bold text-white">We're Here to Help</h1>
            <p class="mt-4 max-w-xl mx-auto text-brand-100">
                Questions about plans, payments, or planning your season? Send us a message and we'll get back to you.
            </p>
        </div>
    </section>

    <section class="py-12 sm:py-20 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 grid gap-8 lg:grid-cols-3 items-start">

            {{-- ================= FORM ================= --}}
            <div class="card lg:col-span-2">
                <div class="card-body">
                    <h2 class="font-heading text-xl sm:text-2xl font-bold text-ink">Send Us a Message</h2>
                    <p class="mt-1 text-sm text-gray-500">We usually reply within one business day.</p>

                    <form method="POST" action="{{ route('contact.submit') }}" class="mt-6 space-y-5">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="form-label">Full name <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                       class="form-input" placeholder="Juan dela Cruz" required maxlength="255">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="form-label">Email address <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                       class="form-input" placeholder="you@example.com" required maxlength="255">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="phone" class="form-label">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                       class="form-input" placeholder="09XX XXX XXXX" maxlength="50">
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="subject" class="form-label">Subject <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                                       class="form-input" placeholder="e.g. Question about payment" maxlength="255">
                                @error('subject') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="message" class="form-label">Message <span class="text-red-500">*</span></label>
                            <textarea id="message" name="message" rows="6" class="form-textarea"
                                      placeholder="How can we help you?" required maxlength="5000">{{ old('message') }}</textarea>
                            @error('message') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-1">
                            <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ================= CONTACT INFO ================= --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="font-heading text-lg font-bold text-ink">Contact Information</h3>
                        <ul class="mt-4 space-y-4 text-sm">
                            <li class="flex items-start gap-3">
                                <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                </span>
                                <span>
                                    <span class="block font-semibold text-gray-900">Email</span>
                                    <a href="mailto:support@anisenso.com" class="text-brand-700 hover:underline">support@anisenso.com</a>
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <span>
                                    <span class="block font-semibold text-gray-900">Location</span>
                                    <span class="text-gray-600">Philippines</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card bg-brand-800 border-brand-800">
                    <div class="card-body">
                        <h3 class="font-heading text-lg font-bold text-white">New to AniSystem?</h3>
                        <p class="mt-2 text-sm text-brand-100 leading-relaxed">
                            The tutorial answers most questions about payments, plans and getting your first
                            season set up.
                        </p>
                        <a href="{{ route('tutorial') }}" class="btn btn-accent btn-sm mt-4">Read the Tutorial</a>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection
