{{-- "Message this user" icon. Renders only when the target member allows
     messages and isn't the viewer. Opens the FB-style dock via .js-open-dm
     (handled by the messenger partial). Expects: $user (id, full_name,
     firstName, allowMessages). --}}
{{-- The assistant is not one of the members: it has no inbox, nobody reads
     one for it, and a chat window opened against it would sit there unread.
     Gated here rather than at each caller, so no screen can offer it. --}}
@if (isset($user) && $user && (int) $user->id !== (int) auth()->id() && $user->allowMessages && ! $user->is_assistant)
    <button type="button"
            class="js-open-dm inline-flex items-center justify-center w-6 h-6 rounded-full hover:bg-brand-50 transition shrink-0 cursor-pointer"
            data-dm-user="{{ $user->id }}" data-dm-name="{{ $user->full_name }}"
            aria-label="Message {{ $user->full_name }}" title="Message {{ $user->firstName ?? $user->full_name }}">
        {{-- The house chat mark — the same picture every chat door wears. --}}
        <img src="{{ asset('images/icons/chat.png') }}" alt="" class="w-4 h-4" style="object-fit:contain">
    </button>
@endif
