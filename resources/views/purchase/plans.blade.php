@extends('layouts.app')

@section('title', 'Choose Your Plan')
@section('page-title', 'Choose Your Plan')
@section('page-subtitle', 'Pay via GCash — activated after manual verification')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="text-center mb-6 md:mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900">Simple plans, full access</h2>
        <p class="text-sm text-gray-500 mt-1 max-w-lg mx-auto">
            Every plan unlocks the complete cropping schedule manager. Pay with GCash and our team
            will verify your payment — usually within the day.
        </p>
    </div>

    @if ($plans->isEmpty())
        <div class="card max-w-md mx-auto">
            <div class="card-body text-center py-10">
                <p class="font-semibold text-gray-800">No plans are available right now.</p>
                <p class="text-sm text-gray-500 mt-1">Please check back soon or contact support at support@anisenso.com.</p>
            </div>
        </div>
    @else
        <div class="grid gap-4 sm:gap-5 {{ $plans->count() >= 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2 max-w-2xl mx-auto' }}">
            @foreach ($plans as $plan)
                @php $isPreselected = $preselect && $plan->planKey === $preselect; @endphp
                <div class="card card-hover flex flex-col {{ $isPreselected ? 'ring-2 ring-brand-600' : '' }}">
                    <div class="card-body flex flex-col grow">
                        @if ($isPreselected)
                            <span class="badge badge-green self-start mb-2">Recommended for you</span>
                        @endif
                        <h3 class="text-lg font-bold text-gray-900">{{ $plan->planName }}</h3>
                        <div class="mt-2 mb-1 flex items-baseline gap-1.5">
                            <span class="text-3xl md:text-4xl font-extrabold text-brand-700">₱{{ number_format((float) $plan->price, (float) $plan->price == (int) $plan->price ? 0 : 2) }}</span>
                            <span class="text-sm font-semibold text-gray-500">/ {{ $plan->duration_label }}</span>
                        </div>
                        @if ($plan->description)
                            <p class="text-sm text-gray-500 mb-3">{{ $plan->description }}</p>
                        @endif

                        @if (is_array($plan->features) && count($plan->features))
                            <ul class="space-y-2 text-sm text-gray-700 mb-5">
                                @foreach ($plan->features as $feature)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-brand-600 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-auto pt-1">
                            <a href="{{ route('purchase.payment', $plan->planKey) }}" class="btn btn-accent btn-lg w-full">
                                Choose {{ $plan->planName }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-center text-xs text-gray-500 mt-6 max-w-md mx-auto">
            Payments are verified manually by the AniSenso team. Your subscription starts the moment
            your GCash payment is approved — you'll get an email confirmation.
        </p>
    @endif
</div>
@endsection
