@extends('layouts.app')

@section('title', $plan->title . ' — Community')
@section('page-title', $isOwner ? 'Your shared plan' : 'Shared plan')
@section('page-subtitle', $plan->title)
@section('back', route('community.index'))

@push('head')
    <style>
        .stars { display: inline-flex; gap: .05rem; }
        .stars svg { width: .85rem; height: .85rem; }
        .star-on { color: #f5c518; }
        .star-off { color: #d1d5db; }
        html.dark .star-off { color: #3a414c; }

        /* Rating picker */
        .rate-pick { display: inline-flex; gap: .15rem; }
        .rate-pick button { padding: .15rem; border-radius: .4rem; line-height: 0; }
        .rate-pick svg { width: 2rem; height: 2rem; color: #d1d5db; transition: color .12s ease, transform .12s ease; }
        html.dark .rate-pick svg { color: #3a414c; }
        .rate-pick button.is-lit svg { color: #f5c518; }
        .rate-pick button:active svg { transform: scale(.88); }

        /* Rating histogram */
        .rate-bar { height: .4rem; border-radius: 999px; background: var(--tl-track, #e5e7eb); overflow: hidden; }
        html.dark .rate-bar { --tl-track: #2c323b; }
        .rate-bar span { display: block; height: 100%; background: #f5c518; }

        /* Read-only timeline */
        .cp-day { border-left: 3px solid var(--cp-color, #4a7c2a); padding-left: .85rem; margin-bottom: 1.1rem; }
        .cp-day-head { display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; }
        .cp-step {
            display: inline-flex; align-items: center; justify-content: center; min-width: 1.6rem; height: 1.6rem;
            border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-700);
            font-size: 11px; font-weight: 800; padding: 0 .4rem;
        }

        .cp-comment { display: flex; gap: .7rem; }
        .cp-avatar {
            width: 2.25rem; height: 2.25rem; border-radius: 999px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: var(--color-brand-600); color: #fff; font-size: .75rem; font-weight: 800;
        }
        .cp-replies { margin-left: 2.95rem; }
    </style>
@endpush

@section('content')

{{-- Plan header --}}
<div class="card p-4 mb-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-gray-900 leading-snug">{{ $plan->title }}</h2>
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
            <div id="planStars">@include('community.partials.stars', ['value' => $ratings['average']])</div>
            <p class="text-xs text-gray-500 mt-0.5" id="planRatingLine">
                {{ $ratings['count'] ? $ratings['average'] . ' from ' . $ratings['count'] : 'No ratings yet' }}
            </p>
        </div>
    </div>

    @if ($plan->publicSummary)
        <p class="text-sm text-gray-600 mt-3">{{ $plan->publicSummary }}</p>
    @endif

    <p class="text-xs text-gray-500 mt-3">
        Shared by {{ $isOwner ? 'you' : (optional($plan->owner)->full_name ?: 'a member') }}
        @if ($plan->publishedAt) · {{ $plan->publishedAt->diffForHumans() }} @endif
        · {{ $plan->lots->count() }} {{ \Illuminate\Support\Str::plural('lot', $plan->lots->count()) }}
    </p>

    @if ($isOwner)
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('sm.activities', ['id' => $plan->id]) }}" class="btn btn-white btn-sm">Edit the plan</a>
            <button type="button" class="btn btn-white btn-sm js-publish"
                    data-id="{{ $plan->id }}" data-title="{{ $plan->title }}"
                    data-summary="{{ $plan->publicSummary }}" data-region="{{ $plan->publicRegion }}">Edit what's shared</button>
            <button type="button" class="btn btn-white btn-sm text-red-600 js-unpublish"
                    data-id="{{ $plan->id }}" data-title="{{ $plan->title }}">Unshare</button>
        </div>
    @endif
</div>

{{-- Owner's inbox: ratings breakdown --}}
@if ($isOwner && $ratings['count'] > 0)
    <div class="card p-4 mb-4">
        <h3 class="font-bold text-gray-900 mb-3">How members rated it</h3>
        <div class="space-y-1.5">
            @foreach ($ratings['histogram'] as $star => $n)
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-500 w-8 shrink-0">{{ $star }}★</span>
                    <span class="rate-bar grow">
                        <span style="width: {{ $ratings['count'] ? round($n / $ratings['count'] * 100) : 0 }}%"></span>
                    </span>
                    <span class="text-xs text-gray-500 w-6 text-right shrink-0">{{ $n }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Rate it (not your own plan) --}}
@if (! $isOwner)
    <div class="card p-4 mb-4">
        <h3 class="font-bold text-gray-900">{{ $myRating ? 'Your rating' : 'Rate this plan' }}</h3>
        <p class="text-sm text-gray-500 mt-0.5 mb-3">Would you follow this season yourself?</p>
        <div class="rate-pick" id="ratePick" role="radiogroup" aria-label="Rating">
            @for ($i = 1; $i <= 5; $i++)
                <button type="button" data-value="{{ $i }}" role="radio"
                        aria-checked="{{ $myRating && $myRating->rating === $i ? 'true' : 'false' }}"
                        aria-label="{{ $i }} out of 5"
                        class="{{ $myRating && $myRating->rating >= $i ? 'is-lit' : '' }}">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                </button>
            @endfor
        </div>
        <textarea id="rateReview" class="form-textarea mt-3" rows="2" maxlength="500"
                  placeholder="Optional — what worked, what you'd change.">{{ $myRating->review ?? '' }}</textarea>
        <button type="button" class="btn btn-primary btn-sm mt-3" id="rateSaveBtn">
            {{ $myRating ? 'Update rating' : 'Submit rating' }}
        </button>
    </div>
@endif

{{-- The plan itself, read-only --}}
<div class="card p-4 mb-4">
    <h3 class="font-bold text-gray-900 mb-3">The season, step by step</h3>
    @php
        $byDate = $plan->activities->sortBy('targetDate')->groupBy(fn ($a) => optional($a->targetDate)->format('Y-m-d') ?: 'no-date');
        $step = 0;
    @endphp
    @forelse ($byDate as $dateKey => $dayActivities)
        <div class="cp-day">
            <div class="cp-day-head">
                <span class="font-bold text-gray-900">
                    {{ $dateKey === 'no-date' ? 'No date' : \Illuminate\Support\Carbon::parse($dateKey)->format('M j, Y') }}
                </span>
                <span class="text-xs font-semibold text-gray-400">
                    {{ $dayActivities->count() }} {{ \Illuminate\Support\Str::plural('activity', $dayActivities->count()) }}
                </span>
            </div>
            <div class="mt-2 space-y-2">
                @foreach ($dayActivities as $a)
                    <div class="flex items-start gap-2.5">
                        <span class="cp-step mt-0.5">{{ ++$step }}</span>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm leading-snug">{{ $a->activityTitle }}</p>
                            @if ($a->lots->isNotEmpty())
                                <p class="text-xs text-gray-500 mt-0.5">{{ $a->lots->pluck('lotName')->implode(', ') }}</p>
                            @endif
                            @if (filled($a->description))
                                <div class="text-sm text-gray-600 mt-1">{!! $a->description !!}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">This plan has no activities to show.</p>
    @endforelse
</div>

{{-- Comments & questions --}}
<div class="card p-4">
    <h3 class="font-bold text-gray-900">
        {{ $isOwner ? 'Questions about your plan' : 'Questions & comments' }}
        <span class="badge badge-gray ml-1" id="commentCount">{{ $thread->sum(fn ($c) => 1 + $c->replies->count()) }}</span>
    </h3>

    <div class="mt-4" id="commentComposer">
        <textarea id="commentBody" class="form-textarea" rows="3" maxlength="4000"
                  placeholder="{{ $isOwner ? 'Add a note for readers…' : 'Ask the grower something, or say what you learned…' }}"></textarea>
        <div class="flex items-center justify-between gap-3 mt-2">
            @if (! $isOwner)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" id="commentIsQuestion" class="form-checkbox" checked>
                    This is a question
                </label>
            @else
                <span></span>
            @endif
            <button type="button" class="btn btn-primary btn-sm" id="commentPostBtn">Post</button>
        </div>
    </div>

    <div class="mt-5 space-y-5" id="commentList">
        @foreach ($thread as $comment)
            @include('community.partials.comment', ['comment' => $comment, 'isOwner' => $isOwner, 'plan' => $plan])
        @endforeach
    </div>

    <p class="text-sm text-gray-500 text-center py-6 {{ $thread->isEmpty() ? '' : 'hidden' }}" id="commentEmpty">
        {{ $isOwner ? 'Nobody has asked anything yet.' : 'No questions yet — be the first to ask.' }}
    </p>
</div>
@endsection

@push('sheets')
    @include('community.partials.publish-sheet')
@endpush

@push('scripts')
    @include('community.partials.publish-js')
<script>
(() => {
const __init = () => {
    const PLAN_ID = @json($plan->id);
    const IS_OWNER = @json($isOwner);
    const URLS = {
        comment: @json(route('community.comment')),
        deleteComment: (id) => @json(route('community.comment.delete')) + '?id=' + id,
        rate: @json(route('community.rate')),
    };
    const byId = (id) => document.getElementById(id);

    /* ---- Rating ---- */
    const pick = byId('ratePick');
    let chosen = @json($myRating->rating ?? 0);

    function paintStars(value) {
        pick?.querySelectorAll('button').forEach((b) => {
            const on = Number(b.dataset.value) <= value;
            b.classList.toggle('is-lit', on);
            b.setAttribute('aria-checked', Number(b.dataset.value) === value ? 'true' : 'false');
        });
    }

    pick?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-value]');
        if (!btn) return;
        chosen = Number(btn.dataset.value);
        paintStars(chosen);
    });
    // Hovering previews the score without committing to it.
    pick?.addEventListener('mouseover', (e) => {
        const btn = e.target.closest('button[data-value]');
        if (btn) paintStars(Number(btn.dataset.value));
    });
    pick?.addEventListener('mouseleave', () => paintStars(chosen));

    byId('rateSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        if (!chosen) { toast('Pick a star rating first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(URLS.rate, {
                method: 'POST',
                body: { scheduleId: PLAN_ID, rating: chosen, review: byId('rateReview').value.trim() || null },
            });
            toast(res.message);
            btn.textContent = 'Update rating';
            const s = res.data.summary;
            byId('planRatingLine').textContent = s.count ? s.average + ' from ' + s.count : 'No ratings yet';
            byId('planStars').innerHTML = starRow(s.average);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    const STAR_PATH = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
    function starRow(value) {
        const rounded = Math.round(value || 0);
        let out = '<span class="stars">';
        for (let i = 1; i <= 5; i++) {
            out += `<svg class="${i <= rounded ? 'star-on' : 'star-off'}" viewBox="0 0 20 20" fill="currentColor"><path d="${STAR_PATH}"/></svg>`;
        }
        return out + '</span>';
    }

    /* ---- Comments ---- */
    /** Mirrors community/partials/comment.blade.php — keep the two in step. */
    function renderComment(c, isReply = false) {
        const canDelete = c.mine || IS_OWNER;
        return `
            <div class="cp-comment" data-comment-id="${c.id}">
                <span class="cp-avatar">${escapeHtml(c.authorInitials)}</span>
                <div class="min-w-0 grow">
                    <p class="text-sm">
                        <span class="font-bold text-gray-900">${escapeHtml(c.authorName)}</span>
                        <span class="text-xs text-gray-400 ml-1">${escapeHtml(c.createdAt || 'just now')}</span>
                        ${c.isQuestion && !isReply ? '<span class="badge badge-gray ml-1">Question</span>' : ''}
                    </p>
                    <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">${escapeHtml(c.body)}</p>
                    <div class="flex gap-3 mt-1.5">
                        ${isReply ? '' : '<button type="button" class="text-xs font-bold text-brand-700 js-reply">Reply</button>'}
                        ${canDelete ? '<button type="button" class="text-xs font-bold text-red-600 js-del-comment">Delete</button>' : ''}
                    </div>
                    ${isReply ? '' : '<div class="cp-replies mt-3 space-y-3"></div>'}
                </div>
            </div>`;
    }

    function bumpCount(delta) {
        const el = byId('commentCount');
        el.textContent = Math.max(0, Number(el.textContent || 0) + delta);
        byId('commentEmpty').classList.toggle('hidden', byId('commentList').children.length > 0);
    }

    byId('commentPostBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const body = byId('commentBody').value.trim();
        if (!body) { toast('Write something first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(URLS.comment, {
                method: 'POST',
                body: { scheduleId: PLAN_ID, body, isQuestion: byId('commentIsQuestion')?.checked ? 1 : 0 },
            });
            const wrap = document.createElement('div');
            wrap.innerHTML = renderComment(res.data.comment);
            byId('commentList').prepend(wrap.firstElementChild);
            byId('commentBody').value = '';
            bumpCount(1);
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Reply box: opened inline under the comment being answered.
    byId('commentList')?.addEventListener('click', async (e) => {
        const root = e.target.closest('[data-comment-id]');
        if (!root) return;

        if (e.target.closest('.js-reply')) {
            const replies = root.querySelector('.cp-replies');
            if (replies.querySelector('.js-reply-box')) { replies.querySelector('textarea').focus(); return; }
            const box = document.createElement('div');
            box.className = 'js-reply-box';
            box.innerHTML = `
                <textarea class="form-textarea" rows="2" maxlength="4000" placeholder="Write a reply…"></textarea>
                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" class="btn btn-ghost btn-sm js-reply-cancel">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm js-reply-send">Reply</button>
                </div>`;
            replies.appendChild(box);
            box.querySelector('textarea').focus();
            return;
        }

        if (e.target.closest('.js-reply-cancel')) {
            e.target.closest('.js-reply-box').remove();
            return;
        }

        if (e.target.closest('.js-reply-send')) {
            const box = e.target.closest('.js-reply-box');
            const btn = box.querySelector('.js-reply-send');
            const body = box.querySelector('textarea').value.trim();
            if (!body) { toast('Write something first.', 'error'); return; }
            btn.disabled = true;
            try {
                const res = await api(URLS.comment, {
                    method: 'POST',
                    body: { scheduleId: PLAN_ID, body, parentId: root.dataset.commentId },
                });
                const wrap = document.createElement('div');
                wrap.innerHTML = renderComment(res.data.comment, true);
                box.parentNode.insertBefore(wrap.firstElementChild, box);
                box.remove();
                bumpCount(1);
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
                btn.disabled = false;
            }
            return;
        }

        if (e.target.closest('.js-del-comment')) {
            const target = e.target.closest('[data-comment-id]');
            const ok = await confirmAction({
                title: 'Delete this comment?',
                message: 'It will be removed for everyone.',
                detail: 'Replies to it are removed too.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.deleteComment(target.dataset.commentId), { method: 'DELETE' });
                const gone = 1 + target.querySelectorAll('.cp-replies [data-comment-id]').length;
                target.remove();
                bumpCount(-gone);
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    paintStars(chosen);
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
