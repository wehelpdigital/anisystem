<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CheckoutService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    public function __construct(
        private CheckoutService $checkout,
        private MailService $mail,
    ) {
    }

    public function plans(Request $request)
    {
        if ($pending = $this->pendingSubscription($request)) {
            return redirect()->route('purchase.thankyou', $pending)
                ->with('success', 'You already have a pending order awaiting verification.');
        }

        return view('purchase.plans', [
            'plans' => Plan::visible()->get(),
            'preselect' => $request->query('plan'),
            'user' => $request->user(),
        ]);
    }

    public function payment(Request $request, string $planKey)
    {
        if ($pending = $this->pendingSubscription($request)) {
            return redirect()->route('purchase.thankyou', $pending)
                ->with('success', 'You already have a pending order awaiting verification.');
        }

        $plan = $this->resolvePlan($planKey);

        return view('purchase.payment', [
            'plan' => $plan,
            'gcash' => $this->checkout->gcashSettings(),
            'user' => $request->user(),
        ]);
    }

    public function submit(Request $request, string $planKey)
    {
        $plan = $this->resolvePlan($planKey);
        $user = $request->user();

        $request->merge([
            'gcashPhone' => preg_replace('/[\s\-]+/', '', (string) $request->input('gcashPhone')) ?: null,
        ]);

        $data = $request->validate([
            'payerName' => ['required', 'string', 'max:255'],
            'amountSent' => ['required', 'numeric', 'min:'.(float) $plan->price],
            'gcashPhone' => ['nullable', 'regex:/^09\d{9}$/'],
            'referenceNumber' => ['nullable', 'required_without:screenshot', 'string', 'max:100'],
            'screenshot' => ['nullable', 'required_without:referenceNumber', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'amountSent.min' => 'The amount sent must be at least ₱'.number_format((float) $plan->price, 2).' — the full plan price.',
            'gcashPhone.regex' => 'Enter the GCash number in the format 09XXXXXXXXX (11 digits).',
            'referenceNumber.required_without' => 'Provide the GCash reference number or upload a screenshot of the payment.',
            'screenshot.required_without' => 'Upload a screenshot of the payment or provide the GCash reference number.',
            'screenshot.max' => 'The screenshot must be 5MB or smaller.',
            'screenshot.mimes' => 'The screenshot must be a JPG, PNG or WEBP image.',
        ]);

        // Per-user mutex so a double-click / parallel submit can't create two
        // orders (the pending-guard below is check-then-act on its own).
        $lock = Cache::lock('anisystem:checkout:'.$user->id, 15);
        if (! $lock->get()) {
            return back()->withInput()
                ->with('error', 'We are still processing your previous submission. Please wait a moment.');
        }

        try {
            // Duplicate guard: one pending order at a time (re-checked inside the lock).
            if ($pending = $this->pendingSubscription($request)) {
                return redirect()->route('purchase.thankyou', $pending)
                    ->with('success', 'You already have a pending order awaiting verification.');
            }

            $subscription = $this->checkout->purchase(
                $user,
                $plan,
                $data['payerName'],
                (float) $data['amountSent'],
                $data['referenceNumber'] ?? null,
                $data['gcashPhone'] ?? null,
                $request->file('screenshot'),
                $data['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('AniSystem checkout failed for user '.$user->id.': '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'We could not submit your payment right now. Please try again in a moment.');
        } finally {
            $lock->release();
        }

        try {
            $this->mail->sendTemplateToUser('payment_submitted', $user, [
                'orderNumber' => $subscription->orderNumber,
                'planName' => $subscription->planName,
                'price' => number_format((float) $subscription->price, 2),
            ]);
        } catch (\Throwable $e) {
            Log::warning('payment_submitted email failed for user '.$user->id.': '.$e->getMessage());
        }

        return redirect()->route('purchase.thankyou', $subscription);
    }

    public function thankYou(Request $request, Subscription $subscription)
    {
        abort_unless(
            (int) $subscription->userId === (int) $request->user()->id
            && (int) $subscription->deleteStatus === 1,
            404
        );

        return view('purchase.thankyou', [
            'subscription' => $subscription,
        ]);
    }

    private function resolvePlan(string $planKey): Plan
    {
        $plan = Plan::visible()->where('planKey', $planKey)->first();

        abort_unless($plan !== null, 404);

        return $plan;
    }

    private function pendingSubscription(Request $request): ?Subscription
    {
        return $request->user()->subscriptions()
            ->where('status', Subscription::STATUS_PENDING)
            ->first();
    }
}
