<?php

namespace App\Support;

use App\Models\AsSiteSetting;
use Illuminate\Support\Facades\DB;

/**
 * How anee.io is paid while there is no payment gateway (2026-09-30), and
 * what can be bought that way.
 *
 * The Philippines pays by GCash or a bank transfer; every other country by
 * PayPal. The receiving accounts, the GCash processing fee and whether Anee
 * may approve a GCash plan on her own are set in the mother app (AniSystem >
 * Orders) and stored on the settings shelf under `pay.manual`; what is not
 * set falls back to DEFAULTS. PayPal keeps its own row (`pay.paypal`, see
 * Region::paypal()).
 */
class ManualPay
{
    public const KEY = 'pay.manual';

    public const DEFAULTS = [
        'gcashNumber' => '09569044144',
        // As GCash itself prints it on the QR and on every receipt: masked.
        'gcashName' => 'ME****O JO*N I* T.',
        // Blank: the built-in QR (public/images/pay/gcash-qr.png). Else a
        // path on the mother app's disk, or a full address.
        'gcashQr' => '',
        // Added to a GCash order: the AI reads every GCash receipt.
        'gcashFee' => 5,
        // May Anee approve a GCash PLAN herself when every check passes?
        // Credits are never approved by her: they can be spent at once.
        'aiAutoApprove' => true,
        'bankName' => '',
        'bankAccountName' => '',
        'bankAccountNumber' => '',
        'bankBranch' => '',
        'bankNote' => '',
        // Said to the buyer while an order waits for a person.
        'reviewHours' => 24,
    ];

    /** The tiers that can be bought, cheapest first (the upgrade ladder). */
    public const TIERS = ['libreAnee', 'solo', 'owner'];

    public static function settings(): array
    {
        $stored = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);
        $s = array_replace(self::DEFAULTS, is_array($stored) ? array_intersect_key($stored, self::DEFAULTS) : []);
        $s['gcashFee'] = max(0, round((float) $s['gcashFee'], 2));
        $s['aiAutoApprove'] = filter_var($s['aiAutoApprove'], FILTER_VALIDATE_BOOLEAN);
        $s['reviewHours'] = max(1, (int) $s['reviewHours']);

        return $s;
    }

    public static function bankReady(): bool
    {
        $s = self::settings();

        return trim($s['bankName']) !== '' && trim($s['bankAccountNumber']) !== '' && trim($s['bankAccountName']) !== '';
    }

    public static function qrUrl(): string
    {
        $qr = trim((string) self::settings()['gcashQr']);
        if ($qr === '') {
            return asset('images/pay/gcash-qr.png');
        }
        if (preg_match('#^https?://#i', $qr)) {
            return $qr;
        }

        return (string) MediaStore::url(MediaStore::REMOTE_PREFIX . ltrim($qr, '/'));
    }

    /** '09569044144' -> '0956 904 4144', for reading aloud and copying. */
    public static function spacedNumber(?string $n = null): string
    {
        $d = preg_replace('/\D/', '', (string) ($n ?? self::settings()['gcashNumber']));

        return strlen($d) === 11 ? substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7) : $d;
    }

    /**
     * The ways this buyer can pay: GCash and bank at home, PayPal abroad.
     *
     * @return array<string, array{label:string, ready:bool}>
     */
    public static function methods(): array
    {
        if (Region::ph()) {
            return [
                'gcash' => ['label' => 'GCash', 'ready' => true],
                'bank' => ['label' => 'Bank transfer', 'ready' => self::bankReady()],
            ];
        }
        $pp = Region::paypal();

        return ['paypal' => ['label' => 'PayPal', 'ready' => filled($pp['email'] ?? null) || filled($pp['link'] ?? null)]];
    }

    public static function fee(string $method): float
    {
        return $method === 'gcash' ? (float) self::settings()['gcashFee'] : 0.0;
    }

    /**
     * One thing that can be bought, priced for this buyer, or null.
     * 'solo:month', 'owner:year', 'libreAnee:month', 'pack:starter'.
     */
    public static function item(string $key): ?array
    {
        if (preg_match('/^pack:([a-z0-9_-]{1,40})$/', $key, $m)) {
            $pack = DB::table('anisystem_ai_credit_packs')->where('packKey', $m[1])->where('isActive', 1)->where('deleteStatus', 1)->first();
            if (! $pack) {
                return null;
            }

            return [
                'key' => $key, 'kind' => 'credits', 'name' => $pack->packName . ' — ' . number_format((int) $pack->credits) . ' AI credits',
                'short' => $pack->packName . ' credits', 'tier' => null, 'period' => null, 'days' => null, 'months' => null,
                'packId' => (int) $pack->id, 'credits' => (int) $pack->credits,
                'price' => round(Region::packPrice($pack), 2), 'currency' => Region::currency(),
            ];
        }

        if (! preg_match('/^(libreAnee|solo|owner):(month|year)$/', $key, $m)) {
            return null;
        }
        [$tier, $period] = [$m[1], $m[2]];
        $cfg = (array) config('tiers.' . $tier, []);
        $price = Region::tierPrice($tier, $period);
        if (! $cfg || $price === null || $price <= 0) {
            return null;
        }
        $months = $period === 'year' ? 12 : 1;

        return [
            'key' => $key, 'kind' => 'plan',
            'name' => ($cfg['name'] ?? ucfirst($tier)) . ' (' . ($period === 'year' ? 'yearly' : 'monthly') . ')',
            'short' => $cfg['name'] ?? ucfirst($tier),
            'tier' => $tier, 'period' => $period, 'days' => $period === 'year' ? 365 : 30, 'months' => $months,
            'packId' => null,
            // The credits the plan promises "per renewal", a month's worth for every month bought.
            'credits' => (int) ($cfg['creditsMonthly'] ?? 0) * $months,
            'price' => round($price, 2), 'currency' => Region::currency(),
        ];
    }

    /** Where a tier stands on the ladder; unknown paid tiers count as the top. */
    public static function rank(?string $tier): int
    {
        return match ($tier) {
            null, 'libre' => 0,
            'libreAnee' => 1,
            'solo' => 2,
            default => 3,
        };
    }

    /** A plan row's key, as User::tierForPlan() will read it back. */
    public static function planKey(string $tier, string $period): string
    {
        return ($tier === 'libreAnee' ? 'libre-anee' : $tier) . '-' . $period;
    }
}
