{{-- One browse card. Shared by the page and its pager rows. --}}
    <a href="{{ route('community.show', ['id' => $plan->id]) }}" class="card p-4 mb-3 block hover:shadow-card-lg transition">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900 leading-snug">{{ $plan->title }}</h3>
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                    @if ($plan->cropType)
                        <span class="badge badge-green">{{ $plan->cropType }}</span>
                    @endif
                    @if ($plan->cropVariety)
                        <span class="badge badge-gray">{{ $plan->cropVariety }}</span>
                    @endif
                    @if ($plan->publicRegion)
                        <span class="badge badge-gray">{{ $plan->publicRegion }}</span>
                    @endif
                </div>
            </div>
            <div class="shrink-0 text-right">
                @include('community.partials.stars', ['value' => $plan->avgRating])
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $plan->ratingCount ? $plan->avgRating . ' · ' . $plan->ratingCount : 'No ratings' }}
                </p>
            </div>
        </div>

        @if ($plan->publicSummary)
            <p class="text-sm text-gray-600 mt-2">{{ $plan->publicSummary }}</p>
        @endif

        <p class="text-xs text-gray-500 mt-3 flex items-center flex-wrap gap-x-1.5 gap-y-1">
            <span>📋 {{ $plan->activityCount }} {{ \Illuminate\Support\Str::plural('step', $plan->activityCount) }}</span>
            <span>· 💬 {{ $plan->commentCount }}</span>
            <span class="inline-flex items-center gap-1.5">· <span class="avatar overflow-hidden {{ CommunityAvatar::hue(optional($plan->owner)->full_name ?: '?') }}" style="width:1.5rem;height:1.5rem;font-size:.55rem;">@if (optional($plan->owner)->avatarPath)<img src="{{ \App\Support\MediaStore::url($plan->owner->avatarPath) }}" alt="" class="w-full h-full object-cover">@else{{ optional($plan->owner)->initials ?: '?' }}@endif</span>
            <span class="font-medium text-gray-700">{{ optional($plan->owner)->full_name ?: 'a member' }}</span></span>
            @if (filled(optional($plan->owner)->statusBubble))
                <span class="text-brand-700 font-medium">· 💭 {{ \Illuminate\Support\Str::limit($plan->owner->statusBubble, 32) }}</span>
            @endif
            @if ($plan->publishedAt)
                <span>{{ $plan->publishedAt->diffForHumans() }}</span>
            @endif
        </p>
    </a>
