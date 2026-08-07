{{-- "Message this user" icon. Renders only when the target member allows
     messages and isn't the viewer. Opens the FB-style dock via .js-open-dm
     (handled by the messenger partial). Expects: $user (id, full_name,
     firstName, allowMessages). --}}
@if (isset($user) && $user && (int) $user->id !== (int) auth()->id() && $user->allowMessages)
    <button type="button"
            class="js-open-dm inline-flex items-center justify-center w-6 h-6 rounded-full text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition shrink-0 cursor-pointer"
            data-dm-user="{{ $user->id }}" data-dm-name="{{ $user->full_name }}"
            aria-label="Message {{ $user->full_name }}" title="Message {{ $user->firstName ?? $user->full_name }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </button>
@endif
