<?php

namespace App\Http\Controllers;

use App\Models\AiCreditLedger;
use App\Models\AiCreditPack;
use App\Models\AiCreditPurchase;
use App\Models\AiSetting;
use App\Services\AiCreditService;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Buying AI Credits. Same manual-GCash flow as a subscription: the order lands
 * in the mother app's ecom-orders queue, and approving it grants the credits.
 */
class AiCreditController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly AiCreditService $credits,
    ) {
    }

    /**
     * My Credits: the balance, every movement in the ledger (one tab), and
     * the packs to buy (the other). Open to everyone -- a member on a plan
     * without Anee still has a log to read (the starter credits waiting for
     * them) and sees, in place of the packs, the plan that would let them
     * spend it. Paying and buying stay behind canUseAi below.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('ai.credits', [
            'tab' => $request->query('tab') === 'buy' ? 'buy' : 'log',
            'canBuy' => $user->canUseAi(),
            'tier' => $user->planTier(),
            'balance' => $this->credits->balance($user->id),
            'unlimited' => $this->credits->unlimited($user->id),
            'packs' => AiCreditPack::active()->where('isActive', 1)->orderBy('sortOrder')->get(),
            'settings' => AiSetting::current(),
            // The ledger, newest first, on its own page name so paging it
            // never disturbs the tab the reader is on.
            'ledger' => AiCreditLedger::active()
                ->where('userId', $user->id)
                ->orderByDesc('id')
                ->paginate(25, ['*'], 'credits')
                ->withQueryString(),
            'pending' => $this->pendingPurchase($user->id),
        ]);
    }

    public function payment(Request $request, string $packKey)
    {
        $user = $request->user();
        if (! $user->canUseAi()) {
            return redirect()->route('ai.credits', ['tab' => 'buy'])->with('error', 'AI credits come with Libre + Anee and every plan above it.');
        }
        if ($pending = $this->pendingPurchase($user->id)) {
            return redirect()->route('ai.credits')
                ->with('success', 'Order ' . $pending->orderNumber . ' is still awaiting verification.');
        }

        return view('ai.credits-payment', [
            'pack' => $this->resolvePack($packKey),
            'gcash' => $this->checkout->gcashSettings(),
        ]);
    }

    public function submit(Request $request, string $packKey)
    {
        $pack = $this->resolvePack($packKey);
        $user = $request->user();

        if (! $user->canUseAi()) {
            return redirect()->route('ai.credits', ['tab' => 'buy'])->with('error', 'AI credits come with Libre + Anee and every plan above it.');
        }

        // The price is the country's (pesos at home, dollars elsewhere),
        // and the proof field is a GCash number at home, a PayPal email elsewhere.
        $price = \App\Support\Region::packPrice($pack);
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
            'amountSent.min' => 'The amount sent must be at least ' . \App\Support\Region::money($price) . ' — the full pack price.',
            'gcashPhone.regex' => 'Enter the GCash number in the format 09XXXXXXXXX (11 digits).',
            'referenceNumber.required_without' => 'Provide the ' . ($payPH ? 'GCash reference number' : 'PayPal transaction ID') . ' or upload a screenshot of the payment.',
            'screenshot.required_without' => 'Upload a screenshot of the payment or provide the ' . ($payPH ? 'GCash reference number' : 'PayPal transaction ID') . '.',
        ]);

        // Per-user mutex, so a double submit cannot create two orders.
        $lock = Cache::lock('anisystem:ai-credits:' . $user->id, 15);
        if (! $lock->get()) {
            return back()->withInput()->with('error', 'We are still processing your previous submission. Please wait a moment.');
        }

        try {
            if ($pending = $this->pendingPurchase($user->id)) {
                return redirect()->route('ai.credits')
                    ->with('success', 'Order ' . $pending->orderNumber . ' is still awaiting verification.');
            }

            $purchase = $this->checkout->purchaseCredits(
                $user,
                $pack,
                $data['payerName'],
                (float) $data['amountSent'],
                $data['referenceNumber'] ?? null,
                $data['gcashPhone'] ?? null,
                $request->file('screenshot'),
                $payPH ? ($data['notes'] ?? null) : trim('[Paid in ' . \App\Support\Region::currency() . '] ' . ($data['notes'] ?? '')),
                $price,
            );
        } catch (\Throwable $e) {
            Log::error('anee.io AI credit checkout failed for user ' . $user->id . ': ' . $e->getMessage());

            return back()->withInput()->with('error', 'We could not submit your payment right now. Please try again in a moment.');
        } finally {
            $lock->release();
        }

        return redirect()->route('ai.credits')->with(
            'success',
            'Payment submitted. Order ' . $purchase->orderNumber . ' — your credits appear once it is verified.'
        );
    }

    // ------------------------------------------------------------------

    private function resolvePack(string $packKey): AiCreditPack
    {
        $pack = AiCreditPack::active()->where('isActive', 1)->where('packKey', $packKey)->first();
        abort_unless($pack, 404);

        return $pack;
    }

    private function pendingPurchase(int $userId): ?AiCreditPurchase
    {
        return AiCreditPurchase::active()
            ->where('userId', $userId)
            ->where('status', AiCreditPurchase::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();
    }
}
