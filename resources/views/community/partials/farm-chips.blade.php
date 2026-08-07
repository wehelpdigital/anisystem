{{-- Profile "farm chips": occupation, years farming, size, crops, method.
     Expects: $user. Optional: $chipsClass (wrapper margin, default mt-2). --}}
@php
    $farmChips = array_values(array_filter([
        filled($user->profession) ? '💼 ' . \Illuminate\Support\Str::limit($user->profession, 24, '') : null,
        filled($user->yearsFarming) ? '👨‍🌾 ' . $user->yearsFarming . ' ' . \Illuminate\Support\Str::plural('yr', (int) $user->yearsFarming) . ' farming' : null,
        filled($user->farmSize) ? '📏 ' . \Illuminate\Support\Str::limit($user->farmSize, 18, '') : null,
        filled($user->cropsGrown) ? '🌾 ' . \Illuminate\Support\Str::limit($user->cropsGrown, 30, '') : null,
        filled($user->farmingMethod) ? '🧑‍🔬 ' . \Illuminate\Support\Str::limit($user->farmingMethod, 20, '') : null,
    ]));
@endphp
@if (! empty($farmChips))
    <div class="flex flex-wrap gap-1.5 {{ $chipsClass ?? 'mt-2' }}">
        @foreach ($farmChips as $chip)
            <span class="inline-flex items-center max-w-full truncate text-[11px] font-semibold text-brand-800 bg-brand-50 border border-brand-100 rounded-full px-2 py-0.5">{{ $chip }}</span>
        @endforeach
    </div>
@endif
