{{-- Shared reaction bar: Like / Heart / Sad / Plant. Reused on wall posts,
     wall comments, group posts, and group replies.
     Expects: $type (post|reply|wallpost|wallcomment), $id, $summary
     (['counts' => [...], 'mine' => ?string]); optional $mini (bool). --}}
@php
    $rSummary = $summary ?? ['counts' => [], 'mine' => null];
    $rMine = $rSummary['mine'] ?? null;
    $rCounts = $rSummary['counts'] ?? [];
@endphp
<div class="react-bar {{ ($mini ?? false) ? 'react-bar-mini' : '' }}">
    @foreach (\App\Models\CommunityReaction::REACTION_META as $rk => $meta)
        <button type="button" class="react-btn {{ $rMine === $rk ? 'is-mine' : '' }}"
            data-react="{{ $rk }}" data-target-type="{{ $type }}" data-target-id="{{ $id }}"
            aria-pressed="{{ $rMine === $rk ? 'true' : 'false' }}"
            title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}">
            <span class="e">{{ $meta['emoji'] }}</span><span class="react-count">{{ ($rCounts[$rk] ?? 0) > 0 ? $rCounts[$rk] : '' }}</span>
        </button>
    @endforeach
</div>
