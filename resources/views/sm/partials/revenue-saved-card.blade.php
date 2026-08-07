{{-- One saved revenue-report row. Mirrors savedCardHtml() in revenue-report.blade.php --}}
<div class="rr-saved-card" data-id="{{ $r->id }}">
    <div class="rr-saved-head">
        <span class="rr-saved-title">{{ $r->title }}</span>
        <span class="rr-saved-when">{{ $r->created_at?->format('M j, Y g:i A') }}</span>
        <button type="button" class="rr-del" data-del="{{ $r->id }}" title="Delete report">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
    <div class="rr-saved-nums">
        @if ($r->yieldAmount !== null)
            <span>Yield: <b>{{ number_format((float) $r->yieldAmount, 2) }} {{ $r->yieldUnit }}</b></span>
        @endif
        <span>Revenue: <b>₱{{ number_format((float) $r->grossRevenue, 2) }}</b></span>
        <span>Cost: <b>₱{{ number_format((float) $r->totalCost, 2) }}</b></span>
        <span class="{{ (float) $r->netProfit >= 0 ? 'rr-net-pos' : 'rr-net-neg' }}">Net: <b>₱{{ number_format((float) $r->netProfit, 2) }}</b></span>
    </div>
    @if ($r->notes)
        <p style="font-size:.8rem;color:var(--color-gray-500);margin-top:.4rem">{{ $r->notes }}</p>
    @endif
</div>
