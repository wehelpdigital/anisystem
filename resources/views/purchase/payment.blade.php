@extends('layouts.app')

@section('title', 'Pay via GCash')
@section('page-title', 'Pay via GCash')
@section('page-subtitle', $plan->planName.' · ₱ '.number_format((float) $plan->price, 2))
@section('back', route('purchase.plans'))

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Order summary --}}
    <div class="card">
        <div class="card-body !py-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Your order</p>
                <p class="font-bold text-gray-900">{{ $plan->planName }} <span class="font-medium text-gray-500">· {{ $plan->duration_label }}</span></p>
            </div>
            <p class="text-2xl font-extrabold text-brand-700 whitespace-nowrap">₱ {{ number_format((float) $plan->price, 2) }}</p>
        </div>
    </div>

    {{-- GCash receiving details --}}
    <div class="card">
        <div class="card-body">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700 text-sm font-extrabold">G</span>
                Send your payment to
            </h2>

            @if ($gcash && ($gcash->gcashNumber ?? null))
                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4 sm:p-5 text-center">
                    <p class="text-2xl sm:text-3xl font-extrabold tracking-wider text-blue-900" id="gcashNumber">{{ $gcash->gcashNumber }}</p>
                    @if ($gcash->gcashAccountName ?? null)
                        <p class="text-sm font-semibold text-blue-700 mt-1">{{ $gcash->gcashAccountName }}</p>
                    @endif
                    <button type="button" class="btn btn-white btn-sm mt-3" onclick="copyGcashNumber()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 012-2h9a2 2 0 012 2v9a2 2 0 01-2 2h-2M5 9h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9a2 2 0 012-2z"/></svg>
                        Copy number
                    </button>

                    @if ($gcash->qrCodeImage ?? null)
                        <div class="mt-4">
                            <img src="{{ rtrim(config('anisystem.btc_check_url'), '/').'/'.ltrim($gcash->qrCodeImage, '/') }}"
                                alt="GCash QR code" class="mx-auto w-44 sm:w-52 rounded-xl border border-blue-100 bg-white p-2">
                            <p class="text-xs text-blue-700 mt-2">Or scan this QR code in your GCash app</p>
                        </div>
                    @endif
                </div>

                @if ($gcash->paymentInstructions ?? null)
                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 mt-3 text-sm text-gray-700 whitespace-pre-line">{{ $gcash->paymentInstructions }}</div>
                @endif
            @else
                <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-4 text-sm text-gray-600 text-center">
                    GCash payment details will be provided by support. Please contact
                    <span class="font-semibold">support@anisenso.com</span> to complete your payment.
                </div>
            @endif

            {{-- Steps --}}
            <ol class="mt-5 space-y-3">
                @foreach ([
                    'Send ₱ '.number_format((float) $plan->price, 2).' to the GCash number above.',
                    'Take a screenshot of the receipt, or copy the reference number.',
                    'Submit your proof of payment using the form below.',
                ] as $i => $step)
                    <li class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-brand-600 text-white text-sm font-bold shrink-0">{{ $i + 1 }}</span>
                        <p class="text-sm text-gray-700 pt-1">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- Proof of payment form --}}
    <div class="card">
        <div class="card-body">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Submit proof of payment</h2>
            <p class="text-sm text-gray-500 mb-4">Provide the GCash reference number, a screenshot, or both.</p>

            <form method="POST" action="{{ route('purchase.submit', $plan->planKey) }}" enctype="multipart/form-data" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="payerName" class="form-label">Name of the GCash sender</label>
                    <input id="payerName" name="payerName" type="text"
                        value="{{ old('payerName', $user->full_name) }}" class="form-input" required>
                    @error('payerName') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="amountSent" class="form-label">Amount sent (₱)</label>
                        <input id="amountSent" name="amountSent" type="number" step="0.01" min="{{ (float) $plan->price }}"
                            inputmode="decimal" value="{{ old('amountSent', number_format((float) $plan->price, 2, '.', '')) }}" class="form-input" required>
                        @error('amountSent') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="gcashPhone" class="form-label">Your GCash number <span class="font-normal text-gray-400">(optional)</span></label>
                        <input id="gcashPhone" name="gcashPhone" type="tel" inputmode="numeric"
                            value="{{ old('gcashPhone') }}" class="form-input" placeholder="09XXXXXXXXX">
                        @error('gcashPhone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="referenceNumber" class="form-label">GCash reference number</label>
                    <input id="referenceNumber" name="referenceNumber" type="text"
                        value="{{ old('referenceNumber') }}" class="form-input" placeholder="e.g. 1234 567 8901">
                    <p class="form-hint">Required if you don't upload a screenshot.</p>
                    @error('referenceNumber') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Payment screenshot</label>
                    <input id="screenshot" name="screenshot" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                    <div id="screenshotDrop"
                        class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 text-center cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition"
                        onclick="document.getElementById('screenshot').click()">
                        <div id="screenshotEmpty">
                            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2 1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-sm font-semibold text-gray-700">Tap to choose a screenshot</p>
                            <p class="text-xs text-gray-500 mt-0.5">JPG, PNG or WEBP · up to 5MB</p>
                        </div>
                        <div id="screenshotPreview" class="hidden">
                            <img id="screenshotPreviewImg" src="" alt="Payment screenshot preview" class="mx-auto max-h-56 rounded-lg shadow-sm">
                            <p id="screenshotPreviewName" class="text-xs font-medium text-gray-600 mt-2 truncate"></p>
                            <button type="button" class="btn btn-ghost btn-sm mt-1 text-red-600" onclick="clearScreenshot(event)">Remove</button>
                        </div>
                    </div>
                    <p class="form-hint">Required if you don't provide a reference number.</p>
                    @error('screenshot') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="form-label">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="2" class="form-textarea"
                        placeholder="Anything we should know about your payment">{{ old('notes') }}</textarea>
                    @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full">Submit Payment Proof</button>
                <p class="text-center text-xs text-gray-500">
                    Our team verifies GCash payments manually. You will receive an email once your payment is approved.
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyGcashNumber() {
        const number = document.getElementById('gcashNumber')?.textContent.trim();
        if (!number) return;
        navigator.clipboard?.writeText(number).then(
            () => toast('GCash number copied.'),
            () => toast('Could not copy — please copy it manually.', 'error')
        );
    }

    const screenshotInput = document.getElementById('screenshot');
    screenshotInput?.addEventListener('change', () => {
        const file = screenshotInput.files?.[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            toast('That image is larger than 5MB. Please choose a smaller screenshot.', 'error');
            screenshotInput.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('screenshotPreviewImg').src = e.target.result;
            document.getElementById('screenshotPreviewName').textContent = file.name;
            document.getElementById('screenshotEmpty').classList.add('hidden');
            document.getElementById('screenshotPreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    function clearScreenshot(e) {
        e.stopPropagation();
        screenshotInput.value = '';
        document.getElementById('screenshotPreviewImg').src = '';
        document.getElementById('screenshotPreview').classList.add('hidden');
        document.getElementById('screenshotEmpty').classList.remove('hidden');
    }
</script>
@endpush
