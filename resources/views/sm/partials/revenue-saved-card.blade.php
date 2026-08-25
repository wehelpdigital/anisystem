{{-- One saved revenue-report row. Mirrors savedCardHtml() in revenue-report.blade.php --}}
<div class="rr-saved-card" data-id="{{ $r->id }}">
    <div class="rr-saved-head">
        <span class="rr-saved-title">{{ $r->title }}</span>
        <span class="rr-saved-when">{{ $r->created_at?->format('M j, Y g:i A') }}</span>
        <button type="button" class="rr-del" data-del="{{ $r->id }}" title="Delete report">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
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
