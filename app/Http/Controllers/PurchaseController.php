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

    /**
     * The plans page is gone (2026-09-30): the plans and their buttons live
     * on My Subscription, and paying is the checkout. Old links land there,
     * and a link naming Libre + Anee goes straight to buying it.
     */
    public function plans(Request $request)
    {
        return $this->toCheckout($request->query('plan'));
    }

    private function toCheckout(?string $planKey)
    {
        if (in_array((string) $planKey, ['libre-anee', 'libreAnee'], true)) {
            return redirect()->route('checkout', ['item' => 'libreAnee:month']);
        }
        if (preg_match('/^(solo|owner)(?:[-:](month|year))?$/', (string) $planKey, $m)) {
            return redirect()->route('checkout', ['item' => $m[1] . ':' . ($m[2] ?? 'month')]);
        }

        return redirect()->route('account.subscription');
    }

    /** The old plans page, kept for the record (no route leads here now). */
    public function legacyPlans(Request $request)
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
        return $this->toCheckout($planKey);
    }

    /** The old payment page, kept for the record (no route leads here now). */
    public function legacyPayment(Request $request, string $planKey)
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

        // The price is the country's (pesos at home, dollars elsewhere),
        // and the proof field is a GCash number at home, a PayPal email elsewhere.
        $price = \App\Support\Region::planPrice($plan);
        $payPH = \App\Support\Region::ph();
        $request->merge([
            'gcashPhone' => ($payPH ? preg_replace('/[\s\-]+/', '', (string) $request->input('gcashPhone')) : trim((string) $request->input('gcashPhone'))) ?: null,
        ]);

        $data = $request->validate([
            'payerName' => ['required', 'string', 'max:255'],
            'amountSent' => ['required', 'numeric', 'min:' . $price],
            'gcashPhone' => $payPH ? ['nullable', 'regex:/^09\d{9}$/'] : ['nullable', 'string', 'max:120'],
            'referenceNumber' => ['nullable', 'required_without:screenshot', 'string', 'max:100'],
            'screenshot' => ['nullable', 'required_without:referenceNumber', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'amountSent.min' => 'The amount sent must be at least ' . \App\Support\Region::money($price) . ', the full plan price.',
            'gcashPhone.regex' => 'Enter the GCash number in the format 09XXXXXXXXX (11 digits).',
            'referenceNumber.required_without' => 'Provide the ' . ($payPH ? 'GCash reference number' : 'PayPal transaction ID') . ' or upload a screenshot of the payment.',
            'screenshot.required_without' => 'Upload a screenshot of the payment or provide the ' . ($payPH ? 'GCash reference number' : 'PayPal transaction ID') . '.',
            'screenshot.max' => 'The screenshot must be 5 MB or smaller.',
            'screenshot.mimes' => 'The screenshot must be a JPG, PNG or WebP picture.',
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
                $payPH ? ($data['notes'] ?? null) : trim('[Paid in ' . \App\Support\Region::currency() . '] ' . ($data['notes'] ?? '')),
                $price,
            );
        } catch (\Throwable $e) {
            Log::error('anee.io checkout failed for user '.$user->id.': '.$e->getMessage());

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
