<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeds the AniSystem subscription plans and the matching ecom product +
     * variants under the existing 'AniSystem' store (ecom_product_stores id 5),
     * so orders created at checkout carry meaningful productId/variantId
     * references for the mother system's reporting and trigger flows.
     * Idempotent: safe to re-run.
     */
    public function up(): void
    {
        $now = now();

        // 1. Ensure the ecom product exists (soft-referenced by name; legacy NOT NULL datetime columns).
        $product = DB::table('ecom_products')
            ->where('productStore', 'AniSystem')
            ->where('productName', 'AniSystem Schedule Manager Subscription')
            ->where('deleteStatus', 1)
            ->first();

        if (! $product) {
            $productId = DB::table('ecom_products')->insertGetId([
                'productName' => 'AniSystem Schedule Manager Subscription',
                'productDescription' => 'Access subscription to the AniSystem cropping schedule manager web app.',
                'productStore' => 'AniSystem',
                'productType' => 'access',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $productId = $product->id;
        }

        // 2. Plans + matching variants.
        $plans = [
            ['planKey' => 'monthly', 'planName' => 'Monthly Plan', 'price' => 499.00, 'durationDays' => 30,
                'variantName' => '30 Days Subscription', 'sortOrder' => 1,
                'description' => 'Full access to the AniSystem schedule manager for 30 days.'],
            ['planKey' => 'season', 'planName' => 'Season Pass', 'price' => 1299.00, 'durationDays' => 120,
                'variantName' => '120 Days Subscription', 'sortOrder' => 2,
                'description' => 'Covers a full cropping season — 120 days of access at a discounted rate.'],
            ['planKey' => 'annual', 'planName' => 'Annual Plan', 'price' => 3999.00, 'durationDays' => 365,
                'variantName' => '12 Months Subscription', 'sortOrder' => 3,
                'description' => 'Best value — a full year of access for year-round cropping programs.'],
        ];

        $features = json_encode([
            'Unlimited cropping schedules',
            'Lots, workers, materials & services',
            'Activities timeline with versions & drafts',
            'Irrigation planner',
            'Documentation, protocols & critical rules',
            'Labor expense summaries & printable exports',
        ]);

        foreach ($plans as $p) {
            $variant = DB::table('ecom_products_variants')
                ->where('ecomProductsId', $productId)
                ->where('ecomVariantName', $p['variantName'])
                ->where('deleteStatus', 1)
                ->first();

            if (! $variant) {
                $variantId = DB::table('ecom_products_variants')->insertGetId([
                    'ecomProductsId' => $productId,
                    'ecomVariantName' => $p['variantName'],
                    'ecomVariantDescription' => $p['description'],
                    'ecomVariantPrice' => $p['price'],
                    'stocksAvailable' => 999999,
                    'maxOrderPerTransaction' => 1,
                    'isActive' => 1,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $variantId = $variant->id;
            }

            $existing = DB::table('anisystem_plans')
                ->where('planKey', $p['planKey'])
                ->where('deleteStatus', 1)
                ->first();

            if (! $existing) {
                DB::table('anisystem_plans')->insert([
                    'planKey' => $p['planKey'],
                    'planName' => $p['planName'],
                    'price' => $p['price'],
                    'durationDays' => $p['durationDays'],
                    'description' => $p['description'],
                    'features' => $features,
                    'ecomProductId' => $productId,
                    'ecomVariantId' => $variantId,
                    'isActive' => 1,
                    'sortOrder' => $p['sortOrder'],
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('anisystem_plans')->whereIn('planKey', ['monthly', 'season', 'annual'])->update(['deleteStatus' => 0]);
    }
};
