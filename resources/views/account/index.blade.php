@extends('layouts.app')

@section('title', 'My Account')
@section('page-title', 'My Account')
@section('help-key', 'account')
@section('page-subtitle', 'Profile & security')
{{-- Arrived through "Edit profile" on the community profile: the arrow
     leads back there, so editing is a detour and not a dead end. --}}
@if (request()->query('from') === 'community')
    @section('back', route('community.connect.profile', ['userId' => auth()->id()]))
@endif

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Profile card --}}
    <div class="card">
        <div class="card-body">
            {{-- Your face, or your letters. Initials are a placeholder for a
                 photo nobody had asked for yet — the picture is what people
                 actually recognise in a chat list or a call. --}}
            <div class="flex items-center gap-4 mb-5">
                <button type="button" id="avatarBtn" class="ac-avatar shrink-0" title="Change your profile photo">
                    <span class="ac-avatar-face" id="avatarFace">
                        @if ($user->avatarPath)
                            <img src="{{ \App\Support\MediaStore::url($user->avatarPath) }}" alt="{{ $user->full_name }}">
                        @else
                            <b>{{ $user->initials }}</b>
                        @endif
                    </span>
                    <span class="ac-avatar-pen" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                </button>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $user->full_name }}</h2>
                    <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                    <button type="button" id="avatarPick" class="text-xs font-bold text-brand-700 hover:underline mt-0.5">
                        {{ $user->avatarPath ? 'Change photo' : 'Add a photo' }}
                    </button>
                    @if ($user->avatarPath)
                        <button type="button" id="avatarClear" class="text-xs font-bold text-gray-400 hover:text-red-500 ml-2">Remove</button>
                    @endif
                </div>
                <input type="file" id="avatarInput" accept="image/*" class="hidden">
            </div>

            <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                {{-- Cover photo — a wide banner at the top of the community profile. --}}
                <div>
                    <label class="form-label">Cover photo <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div id="accountCoverPreview" class="ac-cover"
                         style="background-position: 50% {{ (int) ($user->coverPos ?? 50) }}%;
                                @if ($user->coverPath) background-image:url('{{ \App\Support\MediaStore::url($user->coverPath) }}') @endif">
                        <span id="accountCoverHint" class="{{ $user->coverPath ? 'hidden' : '' }}">No cover yet — a wide landscape photo looks best.</span>
                        {{-- A banner is a wide slot and a phone photo is a tall
                             picture, so centring it is a guess. Drag says which
                             band of the photo the banner should show. --}}
                        <span class="ac-cover-drag {{ $user->coverPath ? '' : 'hidden' }}" id="accountCoverDragHint">
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16l-3 3m3-3l3 3m-3 13l-3-3m3 3l3-3"/></svg>
                            Drag to choose what shows
                        </span>
                    </div>
                    <input type="hidden" name="coverPos" id="accountCoverPos" value="{{ (int) ($user->coverPos ?? 50) }}">
                    <label class="btn btn-white btn-sm cursor-pointer mt-2 mb-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Choose cover photo
                        <input type="file" id="accountCoverInput" name="cover" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </label>
                    <p class="form-hint">Shown as a banner at the top of your community profile. Shrunk on your phone before it is sent, then compressed again on the way in.</p>
                    @error('cover') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="form-label">First name</label>
                        <input id="firstName" name="firstName" type="text"
                            value="{{ old('firstName', $user->firstName) }}" class="form-input" required>
                        @error('firstName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="form-label">Last name</label>
                        <input id="lastName" name="lastName" type="text"
                            value="{{ old('lastName', $user->lastName) }}" class="form-input" required>
                        @error('lastName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="phone" class="form-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric"
                        value="{{ old('phone', $user->phone) }}" class="form-input" placeholder="09XXXXXXXXX" required>
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Email address</label>
                    <input type="email" value="{{ $user->email }}" class="form-input bg-gray-50 text-gray-500" disabled>
                    <p class="form-hint">Your email cannot be changed. Contact support if you need to update it.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="form-label">Town / City <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input id="city" name="city" type="text" maxlength="100"
                            value="{{ old('city', $user->city) }}" class="form-input" placeholder="e.g. Nueva Ecija">
                        @error('city') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="province" class="form-label">Province <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input id="province" name="province" type="text" maxlength="100"
                            value="{{ old('province', $user->province) }}" class="form-input" placeholder="e.g. Central Luzon">
                        @error('province') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Short headline / tagline — a one-liner shown under the name everywhere. --}}
                <div>
                    <label for="headline" class="form-label">Headline <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div class="flex items-center gap-2">
                        <input id="headline" name="headline" type="text" maxlength="120"
                            value="{{ old('headline', $user->headline) }}" class="form-input grow"
                            placeholder="e.g. Rice farmer from Nueva Ecija · 12 years in the field">
                        <span id="headlineCount" class="text-xs text-gray-400 font-medium shrink-0 tabular-nums">0/120</span>
                    </div>
                    <p class="form-hint">A short line shown under your name on your profile and across the community.</p>
                    @error('headline') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="bio" class="form-label">About you <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="bio" name="bio" rows="3" maxlength="500" class="form-textarea"
                        placeholder="What do you grow? How long have you been farming?">{{ old('bio', $user->bio) }}</textarea>
                    <p class="form-hint">Shown on your community profile and wall.</p>
                    @error('bio') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Farming profile — surfaced on the community profile so co-farmers
                     can see each other's experience and what they grow. --}}
                <div class="pt-2">
                    <p class="text-sm font-bold text-gray-700 mb-2">🌾 Farming profile <span class="text-gray-400 font-normal">(optional)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @php
                            $professions = ['Farm Owner', 'Agriculturist', 'Farm Worker', 'Tenant Farmer', 'Farm Manager', 'Agri Technician', 'Agronomist', 'Livestock Raiser', 'Fisherfolk', 'Agri Student', 'Agri Entrepreneur', 'Other'];
                            $currentProfession = old('profession', $user->profession);
                        @endphp
                        <div>
                            <label for="profession" class="form-label">Profession / role</label>
                            <select id="profession" name="profession" class="form-select">
                                <option value="">Select…</option>
                                @if ($currentProfession && ! in_array($currentProfession, $professions, true))
                                    <option value="{{ $currentProfession }}" selected>{{ $currentProfession }}</option>
                                @endif
                                @foreach ($professions as $p)
                                    <option value="{{ $p }}" @selected($currentProfession === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                            @error('profession') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="yearsFarming" class="form-label">Years in farming</label>
                            <input id="yearsFarming" name="yearsFarming" type="number" min="0" max="120" inputmode="numeric"
                                value="{{ old('yearsFarming', $user->yearsFarming) }}" class="form-input" placeholder="e.g. 12">
                            @error('yearsFarming') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="farmSize" class="form-label">Farm size</label>
                            <input id="farmSize" name="farmSize" type="text" maxlength="60"
                                value="{{ old('farmSize', $user->farmSize) }}" class="form-input" placeholder="e.g. 2 hectares">
                            @error('farmSize') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cropsGrown" class="form-label">Crops you grow</label>
                            <input id="cropsGrown" name="cropsGrown" type="text" maxlength="255"
                                value="{{ old('cropsGrown', $user->cropsGrown) }}" class="form-input" placeholder="e.g. Rice, Corn, Mungbean">
                            @error('cropsGrown') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="farmingMethod" class="form-label">Farming method</label>
                            <input id="farmingMethod" name="farmingMethod" type="text" maxlength="60"
                                value="{{ old('farmingMethod', $user->farmingMethod) }}" class="form-input" placeholder="e.g. Conventional, Organic">
                            @error('farmingMethod') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Messaging privacy --}}
                <label class="flex items-start gap-3 pt-2 cursor-pointer select-none">
                    <input type="hidden" name="allowMessages" value="0">
                    <input type="checkbox" name="allowMessages" value="1" class="mt-1 w-5 h-5 rounded border-gray-300 text-brand-600 focus:ring-brand-300"
                        {{ old('allowMessages', $user->allowMessages ?? 1) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">
                        <strong>Allow other members to message me</strong><br>
                        <span class="text-gray-500">Turn this off and no one can start a chat with you in the community.</span>
                    </span>
                </label>

                <div class="pt-1">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change password card --}}
    <div class="card">
        <div class="card-body">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Change Password</h2>

            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4" novalidate>
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password"
                        class="form-input" required autocomplete="current-password">
                    @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password"
                            class="form-input" placeholder="At least 8 characters" required autocomplete="new-password">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            class="form-input" required autocomplete="new-password">
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Subscription shortcut --}}
    <a href="{{ route('account.subscription') }}" class="card card-hover block">
        <div class="card-body flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-gray-900">My Subscription</h3>
                <p class="text-sm text-gray-500">View your plan, status and payment history.</p>
            </div>
            <svg class="w-6 h-6 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
</div>
@endsection

@push('head')
<style>
    /* Your face, big enough to recognise and obviously tappable. */
    .ac-avatar { position: relative; width: 3.5rem; height: 3.5rem; border-radius: 999px; cursor: pointer;
        border: none; padding: 0; background: none; }
    .ac-avatar-face { display: flex; align-items: center; justify-content: center; overflow: hidden;
        width: 100%; height: 100%; border-radius: 999px; background: var(--color-brand-600, #4a7c2a);
        color: #fff; font-size: 1.25rem; font-weight: 800; }
    .ac-avatar-face img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ac-avatar-pen { position: absolute; right: -.15rem; bottom: -.15rem; width: 1.4rem; height: 1.4rem;
        display: flex; align-items: center; justify-content: center; border-radius: 999px;
        background: #fff; border: 1px solid var(--color-gray-200); color: var(--color-gray-500);
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ac-avatar-pen svg { width: .8rem; height: .8rem; }
    .ac-avatar:hover .ac-avatar-pen { color: #3d6823; border-color: #a8cc7e; }

    /* The banner, and the gesture that decides what it shows. */
    .ac-cover { position: relative; width: 100%; height: 8rem; border-radius: .75rem; overflow: hidden;
        display: flex; align-items: center; justify-content: center; text-align: center; padding: 0 1rem;
        background-color: var(--color-gray-100); background-size: cover; background-repeat: no-repeat;
        border: 1px solid var(--color-gray-200); font-size: .75rem; color: var(--color-gray-400);
        touch-action: none; user-select: none; }
    @media (min-width: 640px) { .ac-cover { height: 10rem; } }
    .ac-cover[style*="background-image"] { cursor: grab; }
    .ac-cover.is-dragging { cursor: grabbing; }
    .ac-cover-drag { position: absolute; left: .5rem; bottom: .5rem; display: inline-flex; align-items: center;
        gap: .25rem; padding: .2rem .5rem; border-radius: 999px; background: rgb(0 0 0 / .55); color: #fff;
        font-size: .62rem; font-weight: 700; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ac-cover-drag svg { width: .8rem; height: .8rem; }
    .ac-cover.is-dragging .ac-cover-drag { opacity: 0; }
    html.dark .ac-avatar-pen { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .ac-cover { background-color: rgb(255 255 255 / .05); border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .ac-avatar-pen, .ac-cover-drag { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Headline live character counter.
    const h = document.getElementById('headline');
    const hc = document.getElementById('headlineCount');
    if (h && hc) {
        const upd = () => { hc.textContent = h.value.length + '/120'; };
        h.addEventListener('input', upd);
        upd();
    }
    /* ---- Pictures ------------------------------------------------------
     * Two of them, and the same two problems each time: a phone photo is far
     * bigger than anything a banner or a 3.5rem circle can use, and the part
     * worth showing is rarely the middle.
     *
     * Size is solved before the bytes leave the phone. A 12MP photo shrunk on
     * the device sends about a twentieth of what it would otherwise, which on
     * a farm connection is the difference between saving and giving up. The
     * server still compresses what arrives — the browser step is a courtesy
     * to the connection, not the guarantee. */
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';

    function shrink(file, maxDim, quality) {
        return new Promise((resolve) => {
            if (!file || !/^image\//.test(file.type) || file.type === 'image/gif') return resolve(file);
            const img = new Image();
            const url = URL.createObjectURL(file);
            img.onload = () => {
                URL.revokeObjectURL(url);
                const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
                // Already small enough: send what they picked rather than
                // re-encoding it, which only loses detail.
                if (scale >= 1 && file.size < 900 * 1024) return resolve(file);
                const cv = document.createElement('canvas');
                cv.width = Math.round(img.width * scale);
                cv.height = Math.round(img.height * scale);
                const cx = cv.getContext('2d');
                cx.drawImage(img, 0, 0, cv.width, cv.height);
                cv.toBlob((blob) => {
                    if (!blob || blob.size >= file.size) return resolve(file);
                    resolve(new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg',
                        { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    /** Put a File back into an <input type=file> so the form still carries it. */
    function refile(input, file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    }

    // ---- Cover: preview, shrink, and drag to say which band shows.
    const ci = document.getElementById('accountCoverInput');
    const cp = document.getElementById('accountCoverPreview');
    const chint = document.getElementById('accountCoverHint');
    const cpos = document.getElementById('accountCoverPos');
    const cdrag = document.getElementById('accountCoverDragHint');

    if (ci && cp) {
        ci.addEventListener('change', async () => {
            const f = ci.files && ci.files[0];
            if (!f) return;
            cp.style.backgroundImage = "url('" + URL.createObjectURL(f) + "')";
            chint && chint.classList.add('hidden');
            cdrag && cdrag.classList.remove('hidden');
            // A new photo has a new middle, so the old position means nothing.
            setPos(50);
            const small = await shrink(f, 1600, 0.82);
            if (small !== f) refile(ci, small);
        });
    }

    function setPos(v) {
        const n = Math.max(0, Math.min(100, Math.round(v)));
        if (cpos) cpos.value = n;
        if (cp) cp.style.backgroundPosition = '50% ' + n + '%';
    }

    /* Drag maps the pointer's travel to the photo's travel, not to the box:
     * moving a finger the height of the banner should sweep the whole photo,
     * so a tall picture is reachable end to end in one gesture. */
    if (cp) {
        let drag = null;
        const from = (e) => (e.touches ? e.touches[0].clientY : e.clientY);
        const start = (e) => {
            if (!cp.style.backgroundImage) return;
            drag = { y: from(e), pos: parseInt(cpos?.value || '50', 10) };
            cp.classList.add('is-dragging');
        };
        const move = (e) => {
            if (!drag) return;
            e.preventDefault();
            const travelled = from(e) - drag.y;
            setPos(drag.pos - (travelled / Math.max(1, cp.clientHeight)) * 100);
        };
        const end = () => { if (drag) { drag = null; cp.classList.remove('is-dragging'); } };
        cp.addEventListener('mousedown', start);
        cp.addEventListener('touchstart', start, { passive: true });
        window.addEventListener('mousemove', move);
        window.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('mouseup', end);
        window.addEventListener('touchend', end);
    }

    // ---- Profile photo: picked, shrunk, saved on its own.
    const ai = document.getElementById('avatarInput');
    const aface = document.getElementById('avatarFace');
    const pick = () => ai && ai.click();
    document.getElementById('avatarBtn')?.addEventListener('click', pick);
    document.getElementById('avatarPick')?.addEventListener('click', pick);

    async function sendAvatar(file) {
        const form = new FormData();
        if (file) form.append('avatar', file); else form.append('clear', '1');
        try {
            const res = await fetch(@json(route('account.avatar')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                body: form,
                credentials: 'same-origin',
            });
            const json = await res.json().catch(() => ({}));
            if (!json.success) throw new Error(json.message || 'Could not save the photo.');
            window.toast?.(json.message || 'Photo updated.');
            // Every avatar on the page is the same person; repaint them all
            // rather than making the change look like it needs a reload.
            document.querySelectorAll('[data-me-avatar]').forEach((el) => {
                el.innerHTML = json.data.url
                    ? '<img src="' + json.data.url + '" alt="" class="w-full h-full object-cover">'
                    : (el.getAttribute('data-initials') || '');
            });
            return json.data.url;
        } catch (err) {
            window.toast?.(err.message, 'error');
            return null;
        }
    }

    ai?.addEventListener('change', async () => {
        const f = ai.files && ai.files[0];
        ai.value = '';
        if (!f) return;
        // A circle three and a half rem across never needs more than 512px.
        const small = await shrink(f, 512, 0.86);
        const url = await sendAvatar(small);
        if (url && aface) aface.innerHTML = '<img src="' + url + '" alt="">';
    });

    document.getElementById('avatarClear')?.addEventListener('click', async () => {
        const ok = window.confirmAction
            ? await window.confirmAction({ title: 'Remove your photo?', message: 'Your initials will be shown instead.', confirmText: 'Remove', danger: true })
            : confirm('Remove your photo?');
        if (!ok) return;
        await sendAvatar(null);
        location.reload();
    });
})();
</script>
@endpush
