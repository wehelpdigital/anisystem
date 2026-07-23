<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates subscription orders in the mother system's ecom_* tables following
 * the proven anisenso-course checkout contract, so they appear in
 * http://btc-check.test/ecom-orders for manual GCash verification.
 */
class CheckoutService
{
    public function __construct(private MailService $mail)
    {
    }

    /**
     * The GCash receiving account details shown on the payment page.
     * Reads the AniSystem store's payment settings; falls back to the
     * Ani-Senso store row when AniSystem has none configured yet.
     */
    public function gcashSettings(): ?object
    {
        $storeId = config('anisystem.store_id');

        $row = DB::table('ecom_store_payment_settings')
            ->where('storeId', $storeId)
            ->where('deleteStatus', 1)
            ->where('isGcashActive', 1)
            ->first();

        if (! $row) {
            $row = DB::table('ecom_store_payment_settings')
                ->where('deleteStatus', 1)
                ->where('isGcashActive', 1)
                ->orderBy('storeId')
                ->first();
        }

        return $row;
    }

    /**
     * Create the pending ecom order + item + local subscription row for a plan
     * purchase, then record the GCash payment proof on the order.
     *
     * @return Subscription the pending subscription linked to the new order
     */
    public function purchase(
        User $user,
        Plan $plan,
        string $payerName,
        float $amountSent,
        ?string $referenceNumber,
        ?string $gcashPhone,
        ?UploadedFile $screenshot,
        ?string $notes = null,
    ): Subscription {
        $now = Carbon::now('Asia/Manila');

        $clientId = $this->ensureCrmClient($user, $now);

        $orderNumber = $this->uniqueOrderNumber($now);

        // Compute the screenshot's relative path up front (deterministic name)
        // but move the physical file only AFTER the transaction commits, so a
        // rollback never leaves an orphan in btc-check's public web root.
        $screenshotPath = null;
        if ($screenshot) {
            $ext = \App\Support\UploadHelper::safeExtension($screenshot, ['jpg', 'jpeg', 'png', 'webp']);
            $screenshotFilename = 'payment_'.$orderNumber.'_'.$now->timestamp.'.'.$ext;
            $screenshotPath = 'images/payment-screenshots/'.$screenshotFilename;
        }

        $subscription = DB::transaction(function () use ($user, $plan, $payerName, $amountSent, $referenceNumber, $gcashPhone, $screenshotPath, $notes, $now, $clientId, $orderNumber) {
            $orderId = DB::table('ecom_orders')->insertGetId([
                'usersId' => config('anisystem.order_users_id', 1),
                'orderNumber' => $orderNumber,
                'orderStatus' => 'pending',
                'shippingStatus' => 'not_applicable',
                'clientId' => $clientId,
                'clientFirstName' => $user->firstName,
                'clientMiddleName' => '',
                'clientLastName' => $user->lastName,
                'clientPhone' => $user->phone,
                'clientEmail' => $user->email,
                'subtotal' => $plan->price,
                'shippingTotal' => 0,
                'discountTotal' => 0,
                'grandTotal' => $plan->price,
                'affiliateCommissionTotal' => 0,
                'netRevenue' => $plan->price,
                'orderNotes' => 'AniSystem subscription checkout. Plan: '.$plan->planName
                    .' ('.$plan->durationDays.' days). AniSystem user #'.$user->id.'.',
                'isPackage' => 0,
                'recoveryToken' => Str::random(48),
                'recoveryTokenExpiresAt' => $now->copy()->addDays(7),
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('ecom_order_items')->insert([
                'orderId' => $orderId,
                'productId' => $plan->ecomProductId,
                'productName' => 'AniSystem Schedule Manager Subscription',
                'productStore' => config('anisystem.store_name', 'AniSystem'),
                'productType' => 'access',
                'variantId' => $plan->ecomVariantId,
                'variantName' => $plan->planName.' ('.$plan->durationDays.' days)',
                'unitPrice' => $plan->price,
                'quantity' => 1,
                'subtotal' => $plan->price,
                'shippingCost' => 0,
                'accessClientName' => $user->full_name,
                'accessClientPhone' => $user->phone,
                'accessClientEmail' => $user->email,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Payment proof → order-level payment columns; the admin's Payments
            // tab auto-materializes an ecom_order_payments row from these.
            DB::table('ecom_orders')->where('id', $orderId)->update([
                'paymentMethod' => 'manual_gcash',
                'paymentVerificationStatus' => 'pending',
                'paymentPayerName' => $payerName,
                'paymentAmountSent' => $amountSent,
                'paymentReferenceNumber' => $referenceNumber,
                'paymentPhoneNumber' => $gcashPhone,
                'paymentScreenshot' => $screenshotPath,
                'paymentNotes' => $notes,
                'updated_at' => $now,
            ]);

            DB::table('ecom_order_audit_logs')->insert([
                'orderId' => $orderId,
                'orderNumber' => $orderNumber,
                'userId' => null,
                'userName' => $user->full_name.' (Customer)',
                'actionType' => 'payment_details_submitted',
                'fieldChanged' => 'paymentMethod',
                'previousValue' => null,
                'newValue' => 'manual_gcash',
                'description' => 'Customer submitted payment details via GCash from AniSystem. Amount: ₱'
                    .number_format($amountSent, 2)
                    .($referenceNumber ? '. Ref: '.$referenceNumber : ''),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->userAgent(),
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return Subscription::create([
                'userId' => $user->id,
                'planId' => $plan->id,
                'planKey' => $plan->planKey,
                'planName' => $plan->planName,
                'price' => $plan->price,
                'durationDays' => $plan->durationDays,
                'ecomOrderId' => $orderId,
                'orderNumber' => $orderNumber,
                'status' => Subscription::STATUS_PENDING,
                'deleteStatus' => 1,
            ]);
        });

        // Order + subscription are committed — now place the screenshot file.
        // A failure here leaves a broken image link (admin can request a
        // re-upload) but never an orphaned order or a lost payment record.
        if ($screenshot && $screenshotPath) {
            try {
                $this->moveScreenshot($screenshot, $screenshotPath);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $subscription;
    }

    /**
     * Same ecom order contract as a plan purchase, for an AI Credit pack. The
     * admin verifies it in the same ecom-orders queue; approving it grants the
     * credits (see AiCreditPurchase → active).
     *
     * @return \App\Models\AiCreditPurchase the pending purchase linked to the order
     */
    public function purchaseCredits(
        User $user,
        \App\Models\AiCreditPack $pack,
        string $payerName,
        float $amountSent,
        ?string $referenceNumber,
        ?string $gcashPhone,
        ?UploadedFile $screenshot,
        ?string $notes = null,
    ): \App\Models\AiCreditPurchase {
        $now = Carbon::now('Asia/Manila');
        $clientId = $this->ensureCrmClient($user, $now);
        $orderNumber = $this->uniqueOrderNumber($now);

        $screenshotPath = null;
        if ($screenshot) {
            $ext = \App\Support\UploadHelper::safeExtension($screenshot, ['jpg', 'jpeg', 'png', 'webp']);
            $screenshotPath = 'images/payment-screenshots/payment_'.$orderNumber.'_'.$now->timestamp.'.'.$ext;
        }

        $purchase = DB::transaction(function () use ($user, $pack, $payerName, $amountSent, $referenceNumber, $gcashPhone, $screenshotPath, $notes, $now, $clientId, $orderNumber) {
            $orderId = DB::table('ecom_orders')->insertGetId([
                'usersId' => config('anisystem.order_users_id', 1),
                'orderNumber' => $orderNumber,
                'orderStatus' => 'pending',
                'shippingStatus' => 'not_applicable',
                'clientId' => $clientId,
                'clientFirstName' => $user->firstName,
                'clientMiddleName' => '',
                'clientLastName' => $user->lastName,
                'clientPhone' => $user->phone,
                'clientEmail' => $user->email,
                'subtotal' => $pack->price,
                'shippingTotal' => 0,
                'discountTotal' => 0,
                'grandTotal' => $pack->price,
                'affiliateCommissionTotal' => 0,
                'netRevenue' => $pack->price,
                'orderNotes' => 'AniSystem AI Credits. Pack: '.$pack->packName
                    .' ('.$pack->credits.' credits). AniSystem user #'.$user->id.'.',
                'isPackage' => 0,
                'recoveryToken' => Str::random(48),
                'recoveryTokenExpiresAt' => $now->copy()->addDays(7),
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('ecom_order_items')->insert([
                'orderId' => $orderId,
                'productId' => $pack->ecomProductId,
                'productName' => 'AniSystem AI Credits',
                'productStore' => config('anisystem.store_name', 'AniSystem'),
                'productType' => 'access',
                'variantId' => $pack->ecomVariantId,
                'variantName' => $pack->packName.' ('.$pack->credits.' credits)',
                'unitPrice' => $pack->price,
                'quantity' => 1,
                'subtotal' => $pack->price,
                'shippingCost' => 0,
                'accessClientName' => $user->full_name,
                'accessClientPhone' => $user->phone,
                'accessClientEmail' => $user->email,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('ecom_orders')->where('id', $orderId)->update([
                'paymentMethod' => 'manual_gcash',
                'paymentVerificationStatus' => 'pending',
                'paymentPayerName' => $payerName,
                'paymentAmountSent' => $amountSent,
                'paymentReferenceNumber' => $referenceNumber,
                'paymentPhoneNumber' => $gcashPhone,
                'paymentScreenshot' => $screenshotPath,
                'paymentNotes' => $notes,
                'updated_at' => $now,
            ]);

            DB::table('ecom_order_audit_logs')->insert([
                'orderId' => $orderId,
                'orderNumber' => $orderNumber,
                'userId' => null,
                'userName' => $user->full_name.' (Customer)',
                'actionType' => 'payment_details_submitted',
                'fieldChanged' => 'paymentMethod',
                'previousValue' => null,
                'newValue' => 'manual_gcash',
                'description' => 'Customer submitted payment details via GCash from AniSystem (AI Credits). Amount: ₱'
                    .number_format($amountSent, 2)
                    .($referenceNumber ? '. Ref: '.$referenceNumber : ''),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->userAgent(),
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return \App\Models\AiCreditPurchase::create([
                'userId' => $user->id,
                'packId' => $pack->id,
                'packName' => $pack->packName,
                'credits' => $pack->credits,
                'price' => $pack->price,
                'ecomOrderId' => $orderId,
                'orderNumber' => $orderNumber,
                'status' => \App\Models\AiCreditPurchase::STATUS_PENDING,
                'deleteStatus' => 1,
            ]);
        });

        if ($screenshot && $screenshotPath) {
            try {
                $this->moveScreenshot($screenshot, $screenshotPath);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $purchase;
    }

    /**
     * Ensure a clients_all_database CRM row exists for this user (mother system
     * convention). Legacy table: all text columns NOT NULL, explicit datetimes.
     */
    private function ensureCrmClient(User $user, Carbon $now): ?int
    {
        if ($user->clientId) {
            return $user->clientId;
        }

        try {
            $existing = DB::table('clients_all_database')
                ->where('deleteStatus', 1)
                ->where('clientEmailAddress', $user->email)
                ->first();

            $clientId = $existing?->id;

            if (! $clientId) {
                $clientId = DB::table('clients_all_database')->insertGetId([
                    'clientFirstName' => $user->firstName,
                    'clientMiddleName' => '',
                    'clientLastName' => $user->lastName,
                    'clientPhoneNumber' => $user->phone ?: '',
                    'clientEmailAddress' => $user->email,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $user->forceFill(['clientId' => $clientId])->save();

            return $clientId;
        } catch (\Throwable $e) {
            report($e);

            return null; // ecom_orders.clientId is nullable; the order still works
        }
    }

    private function uniqueOrderNumber(Carbon $now): string
    {
        do {
            $candidate = config('anisystem.order_prefix', 'ANI').'-'.$now->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (DB::table('ecom_orders')->where('orderNumber', $candidate)->exists());

        return $candidate;
    }

    /**
     * Payment screenshots must physically land in btc-check's public folder so
     * the admin order UI (asset('images/payment-screenshots/...')) can render
     * them. The stored extension is derived from content, never the client
     * filename (a client-named "x.php" JPEG polyglot would otherwise be
     * executable in the mother app's web root — RCE).
     *
     * @param  string  $relativePath  e.g. images/payment-screenshots/payment_ANI-…_….jpg
     */
    private function moveScreenshot(UploadedFile $file, string $relativePath): void
    {
        $publicRoot = rtrim(config('anisystem.btc_check_public_path'), '\\/');
        $target = $publicRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $dir = dirname($target);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file->move($dir, basename($target));
    }
}
