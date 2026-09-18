<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Libre + Anee goes on sale.
 *
 * The tier itself lives in config/tiers.php; this is the row the purchase
 * page sells and the mother app's order books recognise: a 30-day plan at
 * ₱70 under the existing anee.io subscription product, with its own
 * variant so an order carries a real productId/variantId like the others.
 * Idempotent, like the seed it follows (2026_07_17_000008).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $product = DB::table('ecom_products')
            ->where('productStore', 'AniSystem')
            ->where('productType', 'access')
            ->where('deleteStatus', 1)
            ->where(function ($q) {
                $q->where('productName', 'anee.io Schedule Manager Subscription')
                    ->orWhere('productName', 'AniSystem Schedule Manager Subscription');
            })
            ->orderBy('id')
            ->first();

        $variantId = null;
        if ($product) {
            $variant = DB::table('ecom_products_variants')
                ->where('ecomProductsId', $product->id)
                ->where('ecomVariantName', 'Libre + Anee (30 days)')
                ->where('deleteStatus', 1)
                ->first();
            $variantId = $variant->id ?? DB::table('ecom_products_variants')->insertGetId([
                'ecomProductsId' => $product->id,
                'ecomVariantName' => 'Libre + Anee (30 days)',
                'ecomVariantDescription' => 'The free Libre plan with Anee: AI chat, all AI analyses, Realign, and the credit shop. 30 days.',
                'ecomVariantPrice' => 70.00,
                'stocksAvailable' => 999999,
                'maxOrderPerTransaction' => 1,
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $exists = DB::table('anisystem_plans')->where('planKey', 'libre-anee')->where('deleteStatus', 1)->exists();
        if (! $exists) {
            DB::table('anisystem_plans')->insert([
                'planKey' => 'libre-anee',
                'planName' => 'Libre + Anee',
                'price' => 70.00,
                'durationDays' => 30,
                'description' => 'Your free Libre plan exactly as it is, plus the whole of Anee for 30 days.',
                'features' => json_encode([
                    'Everything in Libre',
                    'Anee AI chat: ask anything, show a photo',
                    'All AI analyses: What to Plant, When to Plant, Variety Research, Crop Protocol',
                    'Realign by Anee on your growth stages',
                    'Buy AI credit packs whenever you need more',
                ]),
                'ecomProductId' => $product->id ?? null,
                'ecomVariantId' => $variantId,
                'isActive' => 1,
                'sortOrder' => 0,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('anisystem_plans')->where('planKey', 'libre-anee')->update(['deleteStatus' => 0, 'isActive' => 0]);
    }
};
