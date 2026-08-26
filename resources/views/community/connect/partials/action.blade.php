{{-- Connection action buttons for a member. Expects: $status, $memberId. --}}
<span class="conn-action inline-flex items-center gap-1.5" data-member-id="{{ $memberId }}" data-status="{{ $status }}">
    @if ($status === 'none')
        <button type="button" class="btn btn-primary btn-sm conn-btn conn-grad" data-action="connect">Connect</button>
    @elseif ($status === 'pending_out')
        <button type="button" class="btn btn-white btn-sm conn-btn" data-action="disconnect">Requested</button>
    @elseif ($status === 'pending_in')
        <button type="button" class="btn btn-primary btn-sm conn-btn conn-grad" data-action="accept">Accept</button>
        <button type="button" class="btn btn-white btn-sm conn-btn" data-action="decline">Decline</button>
    @elseif ($status === 'connected')
        <span class="badge badge-green">Connected</span>
        <button type="button" class="btn btn-ghost btn-sm text-gray-400 conn-btn" data-action="disconnect" title="Remove connection">✕</button>
    @endif
</span>
