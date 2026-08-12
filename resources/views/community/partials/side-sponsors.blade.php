{{-- Left rail: sponsored slot. Only rendered when there is inventory (the
     feed guards on $sponsors->isNotEmpty()). Each sponsor: title, optional
     body, url, imagePath. --}}
<div class="card p-3">
    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-2">Sponsored</p>
    <div class="space-y-3">
        @foreach ($sponsors as $sponsor)
            <a @if (!empty($sponsor['url'])) href="{{ $sponsor['url'] }}" target="_blank" rel="nofollow noopener" @endif
               class="block rounded-lg overflow-hidden border border-gray-100 hover:border-brand-200 transition">
                @if (!empty($sponsor['imagePath']))
                    <img src="{{ \App\Support\MediaStore::url($sponsor['imagePath']) }}" alt="{{ $sponsor['title'] ?? '' }}" loading="lazy" class="w-full h-auto">
                @endif
                <div class="p-2.5">
                    <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $sponsor['title'] ?? '' }}</p>
                    @if (!empty($sponsor['body']))<p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $sponsor['body'] }}</p>@endif
                </div>
            </a>
        @endforeach
    </div>
</div>
