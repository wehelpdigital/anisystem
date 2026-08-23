{{-- One observation card. Shared by the page and the pager rows so the two
     can never drift. Expects: $o, $categories, $schedule. --}}
    <div class="card p-4 ph-card" data-id="{{ $o->id }}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 grow">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="ph-cat ph-cat-{{ $o->category }}">{{ $categories[$o->category] ?? $o->category }}</span>
                    @if ($o->observationDate)
                        <span class="text-xs font-semibold text-gray-500">{{ $o->observationDate->format('M j, Y') }}</span>
                    @endif
                    @if ($o->lotId && $o->lot)
                        <span class="text-xs font-semibold text-gray-500">· {{ $o->lot->lotName }}</span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-900 leading-snug mt-1.5">{{ $o->title }}</h3>
            </div>
            <div class="flex gap-1 shrink-0">
                <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit observation">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete observation">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>

        @php
            $figures = [];
            if ($o->yieldAmount !== null) {
                $figures[] = ['Yield', number_format((float) $o->yieldAmount, 2) . ' ' . ($o->yieldUnit ?: ''), 'text-brand-700'];
            }
            if ($o->moisturePercent !== null) {
                $figures[] = ['Moisture', rtrim(rtrim(number_format((float) $o->moisturePercent, 2), '0'), '.') . '%', 'text-gray-900'];
            }
            if ($o->pricePerUnit !== null) {
                $figures[] = ['Price', '₱ ' . number_format((float) $o->pricePerUnit, 2), 'text-gray-900'];
            }
            if ($o->gross_value !== null) {
                $figures[] = ['Gross value', '₱ ' . number_format($o->gross_value, 2), 'text-brand-700'];
            }
        @endphp
        @if ($figures)
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                @foreach ($figures as [$label, $value, $tone])
                    <div class="ph-figure">
                        <dt class="text-gray-400">{{ $label }}</dt>
                        <dd class="{{ $tone }}">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        {{-- What this kind of observation was asked, and what it said.
             Mirrors the JS renderer below. --}}
        @php
            $detailRows = collect($o->details ?? [])->map(fn ($v, $k) => [
                'label' => \App\Support\PostHarvestFields::questionFor((string) $o->category, $k) ?: $k,
                'value' => \App\Support\PostHarvestFields::labelFor((string) $o->category, $k, $v),
            ])->values();
        @endphp
        @if ($detailRows->count())
            <dl class="mt-2 grid gap-1">
                @foreach ($detailRows as $d)
                    <div class="ph-detail"><dt>{{ $d['label'] }}:</dt><dd>{{ $d['value'] }}</dd></div>
                @endforeach
            </dl>
        @endif
        @if (filled($o->buyer))
            <p class="text-sm text-gray-500 mt-2">Sold to <span class="font-semibold text-gray-700">{{ $o->buyer }}</span></p>
        @endif
        @if (filled($o->notes))
            <div class="ph-notes text-gray-600 mt-2">{!! $o->notes !!}</div>
        @endif
        {{-- Every attachment, not just the first photo — mirrors the JS
             renderer below, which has shown the whole set since the sheet
             learned to take more than one. --}}
        @php
            $attachments = collect(! empty($o->imagePaths) ? $o->imagePaths : [$o->imagePath])
                ->filter(fn ($p) => filled($p))
                ->values();
        @endphp
        @if ($attachments->count())
            <div class="ph-gallery-thumbs mt-3">
                @foreach ($attachments as $p)
                    @if ($phKind($p) === 'video')
                        <video src="{{ $phUrl($p) }}" class="ph-tile" controls preload="metadata" playsinline></video>
                    @else
                        <img src="{{ $phUrl($p) }}" alt="" class="ph-tile" loading="lazy">
                    @endif
                @endforeach
            </div>
        @endif
    </div>
