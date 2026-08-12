@extends('layouts.app')

@section('title', 'My Account')
@section('page-title', 'My Account')
@section('page-subtitle', 'Profile & security')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Profile card --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-4 mb-5">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-brand-600 text-white text-xl font-bold shrink-0">
                    {{ $user->initials }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $user->full_name }}</h2>
                    <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                {{-- Cover photo — a wide banner at the top of the community profile. --}}
                <div>
                    <label class="form-label">Cover photo <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div id="accountCoverPreview" class="w-full h-32 sm:h-40 rounded-xl bg-gray-100 bg-center bg-cover flex items-center justify-center text-center px-4 text-xs text-gray-400 border border-gray-200 overflow-hidden"
                         @if ($user->coverPath) style="background-image:url('{{ \App\Support\MediaStore::url($user->coverPath) }}')" @endif>
                        <span id="accountCoverHint" class="{{ $user->coverPath ? 'hidden' : '' }}">No cover yet — a wide landscape photo looks best.</span>
                    </div>
                    <label class="btn btn-white btn-sm cursor-pointer mt-2 mb-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Choose cover photo
                        <input type="file" id="accountCoverInput" name="cover" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </label>
                    <p class="form-hint">Shown as a banner at the top of your community profile. Compressed automatically.</p>
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
    // Cover photo instant preview before saving.
    const ci = document.getElementById('accountCoverInput');
    const cp = document.getElementById('accountCoverPreview');
    const chint = document.getElementById('accountCoverHint');
    if (ci && cp) {
        ci.addEventListener('change', () => {
            const f = ci.files && ci.files[0];
            if (!f) return;
            cp.style.backgroundImage = "url('" + URL.createObjectURL(f) + "')";
            chint && chint.classList.add('hidden');
        });
    }
})();
</script>
@endpush
