@extends('layouts.app')

@section('title', 'Payment Submitted')
@section('page-title', 'Order Status')
@section('page-subtitle', $subscription->orderNumber ?? '')

@section('content')
@php
    $status = $subscription->effective_status;
    $isActive = $status === \App\Models\Subscription::STATUS_ACTIVE;
    [$badgeClass, $badgeLabel] = match ($status) {
        'active' => ['badge-green', 'Active'],
        'pending' => ['badge-yellow', 'Pending verification'],
        'suspended' => ['badge-orange', 'Suspended'],
        'rejected' => ['badge-red', 'Payment rejected'],
        'cancelled' => ['badge-gray', 'Cancelled'],
        'expired' => ['badge-red', 'Expired'],
        default => ['badge-gray', ucfirst($status)],
    };
@endphp

<div class="max-w-md mx-auto">
    <div class="card">
        <div class="card-body text-center py-8 sm:py-10">

            @if ($isActive)
                <div class="mx-auto mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-brand-100 text-brand-600">
                    <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">You're all set! 🎉</h2>
                <p class="text-sm text-gray-600 mt-2 max-w-xs mx-auto">
                    Your payment has been verified and your subscription is now active.
                    Time to plan your cropping season!
                </p>
            @else
                <div class="mx-auto mb-4 flex items-center justify-center w-20 h-20 rounded-full {{ $status === 'pending' ? 'bg-brand-100 text-brand-600' : 'bg-gray-100 text-gray-400' }}">
                    @if ($status === 'pending')
                        <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ $status === 'pending' ? 'Payment submitted!' : 'Order '.$badgeLabel }}
                </h2>
                @if ($status === 'pending')
                    <p class="text-sm text-gray-600 mt-2 max-w-xs mx-auto">
                        Our team verifies GCash payments manually — you will get an email once approved.
                    </p>
                @elseif ($status === 'rejected')
                    <p class="text-sm text-gray-600 mt-2 max-w-xs mx-auto">
                        This payment could not be verified. Please subscribe again or contact support@anisenso.com.
                    </p>
                @endif
            @endif

            {{-- Order details --}}
            <div class="rounded-2xl bg-gray-50 border border-gray-100 text-left divide-y divide-gray-100 mt-6">
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500">Order number</span>
                    <span class="font-bold text-gray-900">{{ $subscription->orderNumber }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500">Plan</span>
                    <span class="font-semibold text-gray-900">{{ $subscription->planName }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-semibold text-gray-900">₱ {{ number_format((float) $subscription->price, 2) }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500">Status</span>
                    <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                @if ($isActive && $subscription->expiresAt)
                    <div class="flex items-center justify-between px-4 py-3 text-sm">
                        <span class="text-gray-500">Valid until</span>
                        <span class="font-semibold text-gray-900">{{ $subscription->expiresAt->format('M j, Y') }}</span>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-3 mt-6">
                @if ($isActive)
                    <a href="{{ route('app.dashboard') }}" class="btn btn-accent btn-lg w-full">Open My App</a>
                    <a href="{{ route('account.subscription') }}" class="btn btn-ghost w-full">View my subscription</a>
                @else
                    <a href="{{ route('account.subscription') }}" class="btn btn-primary btn-lg w-full">Check Status</a>
                    <a href="{{ route('account.index') }}" class="btn btn-outline w-full">Go to My Account</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
