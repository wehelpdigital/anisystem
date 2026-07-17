@extends('layouts.app')

@section('title', 'My Subscription')
@section('page-title', 'My Subscription')
@section('page-subtitle', 'Plan, status & payment history')
@section('back', route('account.index'))

@section('content')
@php
    $status = $subscription?->effective_status;
    $daysRemaining = $subscription?->daysRemaining();
    $expiringSoon = $status === \App\Models\Subscription::STATUS_ACTIVE && $daysRemaining !== null && $daysRemaining <= 7;
    $showRenewCta = ! $subscription || $status !== \App\Models\Subscription::STATUS_ACTIVE || $expiringSoon;

    $badgeFor = function (?string $s) {
        return match ($s) {
            'active' => ['badge-green', 'Active'],
            'pending' => ['badge-yellow', 'Awaiting payment verification'],
            'suspended' => ['badge-orange', 'Suspended'],
            'cancelled' => ['badge-gray', 'Cancelled'],
            'rejected' => ['badge-red', 'Payment rejected'],
            'expired' => ['badge-red', 'Expired'],
            default => ['badge-gray', ucfirst((string) $s)],
        };
    };
@endphp

<div class="max-w-2xl mx-auto space-y-5">

    {{-- Locked banner --}}
    @if ($locked)
        <div class="rounded-2xl border-2 border-red-300 bg-red-50 p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 11h14a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8a1 1 0 011-1z"/></svg>
                </div>
                <div class="min-w-0">
                    <h2 class="font-bold text-red-800">Your access is locked</h2>
                    <p class="text-sm text-red-700 mt-1">
                        @if (! $subscription)
                            You don't have a subscription yet. Choose a plan below to activate your account and unlock the schedule manager.
                        @elseif ($status === 'pending')
                            Your payment is still awaiting manual verification by our team. You will receive an email once it is approved — this usually takes less than a day.
                        @elseif ($status === 'suspended')
                            Your subscription has been suspended. Please contact support at support@anisenso.com so we can help you restore access.
                        @elseif ($status === 'expired')
                            Your subscription has expired. Renew below to regain access to your cropping schedules — your data is safe and waiting for you.
                        @elseif ($status === 'rejected')
                            Your last payment could not be verified and was rejected. Please subscribe again with a valid GCash payment, or contact support if you believe this is a mistake.
                        @elseif ($status === 'cancelled')
                            Your last order was cancelled. Subscribe again below to activate your account.
                        @else
                            Your subscription is not active. Choose a plan below to unlock the app.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Current subscription card --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-start justify-between gap-3 mb-4">
                <h2 class="text-lg font-bold text-gray-900">Current Subscription</h2>
                @if ($subscription)
                    @php [$badgeClass, $badgeLabel] = $badgeFor($status); @endphp
                    <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                @endif
            </div>

            @if (! $subscription)
                <div class="text-center py-6">
                    <div class="mx-auto mb-3 flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 text-gray-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="font-semibold text-gray-800">No subscription yet</p>
                    <p class="text-sm text-gray-500 mt-1">Choose a plan to activate your account.</p>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xl font-bold text-gray-900">{{ $subscription->planName }}</p>
                            @if ($subscription->orderNumber)
                                <p class="text-xs text-gray-500 mt-0.5">Order {{ $subscription->orderNumber }}</p>
                            @endif
                        </div>
                        <p class="text-lg font-bold text-brand-700 whitespace-nowrap">₱ {{ number_format((float) $subscription->price, 2) }}</p>
                    </div>

                    @if ($status === 'pending')
                        <div class="rounded-xl bg-accent-500/15 border border-accent-500/40 px-4 py-3 text-sm text-gray-800">
                            <span class="font-semibold">Awaiting payment verification.</span>
                            Our team verifies GCash payments manually — you will get an email once your subscription is approved.
                        </div>
                    @elseif ($status === 'active')
                        @php
                            $totalDays = max(1, (int) ($subscription->startsAt?->copy()->startOfDay()->diffInDays($subscription->expiresAt) ?? $subscription->durationDays));
                            $pct = max(0, min(100, (int) round(($daysRemaining ?? 0) / $totalDays * 100)));
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <span class="text-gray-600">Expires {{ $subscription->expiresAt?->format('M j, Y') }}</span>
                                <span class="font-bold {{ $expiringSoon ? 'text-orange-600' : 'text-brand-700' }}">
                                    {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                                </span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $expiringSoon ? 'bg-orange-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div>
                            </div>
                            @if ($expiringSoon)
                                <p class="text-xs text-orange-600 font-semibold mt-2">Your subscription is expiring soon — renew now so you don't lose access mid-season.</p>
                            @endif
                        </div>
                    @elseif ($status === 'suspended')
                        <div class="rounded-xl bg-orange-50 border border-orange-200 px-4 py-3 text-sm text-orange-800">
                            <span class="font-semibold">Subscription suspended.</span>
                            Please contact support at support@anisenso.com to restore your access.
                        </div>
                    @else
                        <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600">
                            @if ($status === 'expired')
                                This subscription expired {{ $subscription->expiresAt?->format('M j, Y') }}. Renew below to get back to your schedules.
                            @elseif ($status === 'rejected')
                                The payment for this order was rejected. You can subscribe again with a valid payment.
                            @else
                                This order was cancelled.
                            @endif
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        @if ($subscription->startsAt)
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-xs text-gray-500">Started</p>
                                <p class="font-semibold text-gray-800">{{ $subscription->startsAt->format('M j, Y') }}</p>
                            </div>
                        @endif
                        @if ($subscription->expiresAt)
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-xs text-gray-500">Expires</p>
                                <p class="font-semibold text-gray-800">{{ $subscription->expiresAt->format('M j, Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row gap-3 mt-5">
                @if ($showRenewCta)
                    <a href="{{ route('purchase.plans') }}" class="btn btn-accent btn-lg flex-1">
                        {{ $status === 'active' || $status === 'expired' ? 'Renew Subscription' : 'Subscribe Now' }}
                    </a>
                @endif
                <form method="POST" action="{{ route('account.subscription.refresh') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="btn btn-outline w-full {{ $showRenewCta ? '' : 'btn-lg' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A7.5 7.5 0 0119 8.5M18.4 15A7.5 7.5 0 015 15.5"/></svg>
                        Refresh Status
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- History --}}
    @if ($history->count() > 1 || ($history->count() === 1 && ! $subscription))
        <div>
            <h2 class="text-base font-bold text-gray-900 mb-3 px-1">Subscription History</h2>
            <div class="space-y-3">
                @foreach ($history as $row)
                    @php [$hBadge, $hLabel] = $badgeFor($row->effective_status); @endphp
                    <div class="card">
                        <div class="card-body !py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ $row->planName }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if ($row->orderNumber) {{ $row->orderNumber }} · @endif
                                    {{ $row->created_at?->format('M j, Y') }}
                                    @if ($row->startsAt && $row->expiresAt)
                                        · {{ $row->startsAt->format('M j') }} – {{ $row->expiresAt->format('M j, Y') }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="badge {{ $hBadge }}">{{ $row->effective_status === 'pending' ? 'Pending' : $hLabel }}</span>
                                <p class="text-xs font-semibold text-gray-600 mt-1">₱ {{ number_format((float) $row->price, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
