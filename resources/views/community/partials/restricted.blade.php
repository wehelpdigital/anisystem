{{-- Shown in place of any content an admin has restricted. Optional
     $reason (defaults to a generic message). --}}
<div class="restricted-notice">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
    <span>{{ $reason ?? 'An admin has restricted this content.' }}</span>
</div>
