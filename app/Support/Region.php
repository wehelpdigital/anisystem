<?php

namespace App\Support;

use App\Models\AsSiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The country a farmer is in, and everything that follows from it.
 *
 * One reader for the whole app (2026-09-16). A logged-in farmer is where
 * their account says; a visitor is where the public face on the URL says
 * (/ph or /en), else where they chose with the flag, else where their
 * address puts them (Cloudflare's CF-IPCountry header), else the home
 * market. Views ask this class for words, money, labels and rules rather
 * than knowing about pesos or barangays themselves; the dictionary lives
 * in config/regions.php.
 *
 * Only two things are decided here: WHICH country, and what that country's
 * block in the config says. Nothing about a country is hard-coded below.
 */
final class Region
{
    public const HOME = 'PH';
    public const SESSION_KEY = 'anee.country';
    public const COOKIE = 'anee_country';
    public const USD_KEY = 'prices.usd';

    private static ?string $memo = null;
    /** @var array<string, array> */
    private static array $confMemo = [];
    private static ?array $usdMemo = null;

    // ------------------------------------------------------------------
    // Which country
    // ------------------------------------------------------------------

    /** The ISO 3166-1 alpha-2 code in force for this request. */
    public static function code(): string
    {
        return self::$memo ??= self::resolve();
    }

    /** Forget the memo — the middleware calls this at the top of a request. */
    public static function forget(): void
    {
        self::$memo = null;
    }

    /** Run a closure as if in a country (mailers, digests, jobs with no session). */
    public static function as(string $code, callable $fn): mixed
    {
        $keep = self::$memo;
        self::$memo = self::valid($code) ?: self::HOME;
        try {
            return $fn();
        } finally {
            self::$memo = $keep;
        }
    }

    /** Is this the home market? Most Taglish/peso switches ask only this. */
    public static function ph(): bool
    {
        return self::code() === self::HOME;
    }

    /** A user's own country, the home market when unset. */
    public static function of(?User $user): string
    {
        return self::valid($user?->country ?? null) ?: self::HOME;
    }

    /** A code the countries list knows, uppercased, or null. */
    public static function valid(mixed $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code !== '' && array_key_exists($code, (array) config('countries', [])) ? $code : null;
    }

    private static function resolve(): string
    {
        $req = app()->bound('request') ? request() : null;
        $user = Auth::user();

        // The public face on the address is explicit and wins, even for a
        // farmer who is logged in: /ph is the Philippines; /en is the
        // visitor's own country when that is not the Philippines (their
        // account's, their choice, or where they are), with the US standing
        // in when nothing else is known. Inside the app there is no face on
        // the address, and the account's own country rules.
        $face = $req instanceof Request ? $req->route('face') : null;
        if ($face === 'ph') {
            return self::HOME;
        }
        $own = $user ? self::valid($user->country ?? null) : null;
        if ($face === 'en') {
            $chosen = $req ? self::chosen($req) : null;
            foreach ([$own, $chosen, $req ? self::detect($req) : null] as $c) {
                if ($c && $c !== self::HOME) {
                    return $c;
                }
            }

            return 'US';
        }
        if ($own) {
            return $own;
        }
        if (! $req instanceof Request) {
            return self::HOME;
        }

        return self::chosen($req) ?: self::detect($req) ?: self::HOME;
    }

    /** What the visitor chose (the flag, or ?country=XX), kept in the session and a cookie. */
    private static function chosen(Request $req): ?string
    {
        $fromSession = $req->hasSession() ? $req->session()->get(self::SESSION_KEY) : null;

        return self::valid($fromSession) ?: self::valid($req->cookie(self::COOKIE));
    }

    /**
     * Where the request comes from. Cloudflare fronts anee.io and stamps
     * CF-IPCountry on every request when IP Geolocation is on in its
     * dashboard; a probe can say X-Country. Nothing is looked up online.
     */
    public static function detect(?Request $req = null): ?string
    {
        $req ??= (app()->bound('request') ? request() : null);
        if (! $req instanceof Request) {
            return null;
        }
        foreach (['CF-IPCountry', 'X-Country', 'X-Vercel-IP-Country', 'CloudFront-Viewer-Country'] as $h) {
            $c = self::valid($req->header($h));
            if ($c) {
                return $c;
            }
        }

        return null;
    }

    /** Remember a chosen country for the visitor (the flag, ?country=). */
    public static function choose(Request $req, string $code): void
    {
        $code = self::valid($code) ?: self::HOME;
        if ($req->hasSession()) {
            $req->session()->put(self::SESSION_KEY, $code);
        }
        self::forget();
    }

    /** The public site's face for this request: 'ph' or 'en'. */
    public static function face(): string
    {
        return self::ph() ? 'ph' : 'en';
    }

    // ------------------------------------------------------------------
    // What the country says
    // ------------------------------------------------------------------

    /** The country's block, the international defaults under it. */
    public static function conf(?string $code = null): array
    {
        $code = $code ? (self::valid($code) ?: self::HOME) : self::code();
        if (isset(self::$confMemo[$code])) {
            return self::$confMemo[$code];
        }
        // Read the whole file: a dotted key would take '*' for a wildcard.
        $all = (array) config('regions', []);
        $base = (array) ($all['*'] ?? []);
        $own = (array) ($all[$code] ?? []);
        $merged = array_replace_recursive($base, $own);
        // Lists are taken whole, never unioned: the Philippines' cavans and
        // tons must not grow the base's kilograms, nor its seasons the
        // base's spring.
        foreach (['yieldUnits', 'seasons', 'divisions'] as $list) {
            if (array_key_exists($list, $own)) {
                $merged[$list] = $own[$list];
            }
        }
        $merged['code'] = $code;
        $merged['name'] = self::name($code);
        $merged['flag'] = self::flag($code);

        return self::$confMemo[$code] = $merged;
    }

    /** One value from the country's block, dot-notated. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::conf(), $key, $default);
    }

    /** A word from the dictionary — the natural Filipino one at home, English elsewhere. */
    public static function t(string $key, ?string $default = null): string
    {
        // The keys carry dots of their own ('greeting.morning'), so no data_get.
        $v = self::conf()['words'][$key] ?? null;

        return is_string($v) ? $v : ($default ?? $key);
    }

    /** The English name of a country. */
    public static function name(?string $code = null): string
    {
        $code = $code ? (self::valid($code) ?: self::HOME) : self::code();

        return (string) (config('countries.' . $code) ?? $code);
    }

    /** The flag as an emoji, drawn from the code's two letters. */
    public static function flag(?string $code = null): string
    {
        $code = strtoupper($code ?: self::code());
        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return '🌐';
        }
        $out = '';
        foreach (str_split($code) as $ch) {
            $out .= mb_chr(0x1F1E6 + (ord($ch) - ord('A')), 'UTF-8');
        }

        return $out;
    }

    /** Every country for a picker: the home market first, then A–Z. */
    public static function countries(): array
    {
        $all = (array) config('countries', []);
        $home = [self::HOME => $all[self::HOME] ?? 'Philippines'];
        unset($all[self::HOME]);
        asort($all, SORT_NATURAL | SORT_FLAG_CASE);

        return $home + $all;
    }

    public static function language(): string
    {
        return (string) self::get('language', 'en');
    }

    /** Plain English only — no Filipino words anywhere. */
    public static function englishOnly(): bool
    {
        return self::language() === 'en';
    }

    public static function tz(): string
    {
        return (string) self::get('timezone', 'Asia/Manila');
    }

    // ------------------------------------------------------------------
    // Money
    // ------------------------------------------------------------------

    public static function currency(): string
    {
        return (string) self::get('currency.code', 'PHP');
    }

    public static function symbol(): string
    {
        return (string) self::get('currency.symbol', '₱');
    }

    public static function currencyName(): string
    {
        return (string) self::get('currency.name', 'pesos');
    }

    /** "₱1,234.50" / "$1,234.50" — the symbol tight against the number. */
    public static function money(float|int|string|null $n, int $decimals = 2): string
    {
        return self::symbol() . number_format((float) $n, $decimals);
    }

    /** A price on a card: "₱200", "$9.99" — cents only when there are any. */
    public static function priceTag(float|int|string|null $n): string
    {
        $n = (float) $n;

        return self::symbol() . number_format($n, fmod($n, 1.0) > 0.001 ? 2 : 0);
    }

    /** The way this country pays: "GCash" at home, "PayPal" elsewhere. */
    public static function payMethod(): string
    {
        return (string) self::get('pay.method', 'PayPal');
    }

    /**
     * The US-dollar price list: config defaults under the mother app's
     * shelf (as_site_settings `prices.usd`, JSON), read once a request.
     */
    public static function usd(): array
    {
        if (self::$usdMemo !== null) {
            return self::$usdMemo;
        }
        $out = (array) config('regions.usd', []);
        try {
            $set = json_decode((string) AsSiteSetting::get(self::USD_KEY, ''), true);
        } catch (\Throwable $e) {
            $set = null;
        }
        foreach ((array) $set as $group => $rows) {
            if (! is_array($rows) || ! isset($out[$group])) {
                continue;
            }
            foreach ($rows as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        if (is_numeric($vv) && (float) $vv >= 0) {
                            $out[$group][$k][$kk] = (float) $vv;
                        }
                    }
                } elseif (is_numeric($v) && (float) $v >= 0) {
                    $out[$group][$k] = (float) $v;
                }
            }
        }

        return self::$usdMemo = $out;
    }

    /** Pesos to dollars when no dollar price was set for a thing. */
    private static function roughUsd(float $pesos): float
    {
        $rate = max(1.0, (float) (self::usd()['rate'] ?? 58));

        return max(0.99, round($pesos / $rate) - 0.01);
    }

    /** A tier's price for this country: pesos from config/tiers, dollars from the USD list. */
    public static function tierPrice(string $tier, string $period = 'month'): ?float
    {
        $t = (array) config('tiers.' . $tier, []);
        $peso = $period === 'year' ? ($t['priceYear'] ?? null) : ($t['price'] ?? null);
        if ($peso === null) {
            return null;
        }
        if (self::ph() || (float) $peso == 0.0) {
            return (float) $peso;
        }
        $usd = self::usd()['tiers'][$tier][$period] ?? null;

        return $usd !== null ? (float) $usd : self::roughUsd((float) $peso);
    }

    /** A legacy plan's price (anisystem_plans) for this country. */
    public static function planPrice(object $plan): float
    {
        if (self::ph()) {
            return (float) $plan->price;
        }
        $usd = self::usd()['plans'][$plan->planKey ?? ''] ?? null;

        return $usd !== null ? (float) $usd : self::roughUsd((float) $plan->price);
    }

    /** A credit pack's price for this country. */
    public static function packPrice(object $pack): float
    {
        if (self::ph()) {
            return (float) $pack->price;
        }
        $usd = self::usd()['packs'][$pack->packKey ?? ''] ?? null;

        return $usd !== null ? (float) $usd : self::roughUsd((float) $pack->price);
    }

    // ------------------------------------------------------------------
    // Forms: phone, address, lots
    // ------------------------------------------------------------------

    /** The phone rule: placeholder, hint, regex, error, strip. */
    public static function phone(?string $code = null): array
    {
        return (array) data_get(self::conf($code), 'phone', []);
    }

    /** A phone as the rule wants it stored: spaces, dashes and brackets gone. */
    public static function cleanPhone(?string $raw, ?string $code = null): string
    {
        $strip = self::phone($code)['strip'] ?? '/[\s\-]+/';

        return (string) preg_replace($strip, '', (string) $raw);
    }

    /** The account's own address labels: ['city' => [...], 'region' => [...], 'divisions' => …]. */
    public static function address(?string $code = null): array
    {
        return (array) data_get(self::conf($code), 'address', []);
    }

    /** A lot's four address lines, labelled for the country. */
    public static function lot(?string $code = null): array
    {
        return (array) data_get(self::conf($code), 'lot', []);
    }

    /**
     * How the state/province and town are chosen: 'ph' (province → town
     * from the PSGC file), 'list' (a state from the list, the city typed),
     * or 'free' (both typed).
     */
    public static function divisions(?string $code = null): array
    {
        $conf = self::conf($code);
        $mode = (string) data_get($conf, 'address.divisions', 'free');

        return ['mode' => $mode, 'list' => $mode === 'list' ? (array) ($conf['divisions'] ?? []) : []];
    }

    /**
     * Every configured country's form rules at once (phone, address and
     * lot labels, how the state/province is chosen), for a country picker
     * to relabel a form the moment the country changes. '*' is the rule
     * for any country without a block of its own.
     */
    public static function formRules(): array
    {
        $all = (array) config('regions', []);
        $base = (array) ($all['*'] ?? []);
        $out = [];
        foreach ($all as $code => $block) {
            if ($code === 'usd') {
                continue;
            }
            $m = $code === '*' ? $base : array_replace_recursive($base, (array) $block);
            // Lists are the country's own, never a union with the base's.
            foreach (['seasons', 'yieldUnits', 'divisions'] as $list) {
                if ($code !== '*' && array_key_exists($list, (array) $block)) {
                    $m[$list] = $block[$list];
                }
            }
            $mode = (string) data_get($m, 'address.divisions', 'free');
            $out[$code] = [
                'name' => $code === '*' ? 'International' : self::name($code),
                'seasons' => (array) ($m['seasons'] ?? []),
                'phone' => ['placeholder' => (string) data_get($m, 'phone.placeholder', ''), 'hint' => (string) data_get($m, 'phone.hint', '')],
                'address' => (array) ($m['address'] ?? []),
                'lot' => (array) ($m['lot'] ?? []),
                'divisions' => ['mode' => $mode, 'list' => $mode === 'list' ? (array) ($m['divisions'] ?? []) : []],
                'symbol' => (string) data_get($m, 'currency.symbol', '$'),
                'exampleLocation' => (string) ($m['exampleLocation'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * How the international face gets paid: PayPal details the mother app
     * keeps on the shelf (as_site_settings `pay.paypal`, JSON with email,
     * link, name, instructions). Empty until the owner fills them in.
     */
    public static function paypal(): array
    {
        try {
            $set = json_decode((string) AsSiteSetting::get('pay.paypal', ''), true);
        } catch (\Throwable $e) {
            $set = null;
        }

        return array_merge(['email' => '', 'link' => '', 'name' => '', 'instructions' => ''], array_filter((array) $set, 'is_string'));
    }

    /** The planting seasons this country's farmers pick from. */
    public static function seasons(): array
    {
        return (array) self::get('seasons', []);
    }

    /** A season spelled out with its months, for the When to Plant brief. */
    public static function seasonWords(string $key, int $year): string
    {
        $next = $year + 1;
        if (self::ph()) {
            return match ($key) {
                'dry' => "Dry season {$year}–{$next}: early December {$year} through May {$next} in most lowland PH regions. The window CROSSES INTO {$next} and that is part of the season — a planting date in January–May {$next} is a normal answer, not a different season.",
                'wet' => "Wet season {$year}: roughly June–October {$year} in most lowland PH regions — planting as the rains set in around June–July, harvest around October–November.",
                default => "Third crop {$year}: the summer crop squeezed in after the dry-season harvest and before the rains — planted roughly March to May {$year} in irrigated lowland PH areas and taken as the wet season sets in around June–July. Only where water can be assured through the hot months; heat at flowering and rain at harvest are its risks.",
            };
        }
        $label = self::seasons()[$key] ?? ucfirst($key);

        return match ($key) {
            'spring' => "{$label} {$year}: roughly March–May {$year} in the northern hemisphere (September–November in the southern). Work out the actual months for the farmer's own location, hemisphere and climate zone.",
            'summer' => "{$label} {$year}: roughly June–August {$year} in the northern hemisphere (December–February in the southern). Work out the actual months for the farmer's own location, hemisphere and climate zone.",
            'autumn' => "{$label} {$year}: roughly September–November {$year} in the northern hemisphere (March–May in the southern). Work out the actual months for the farmer's own location, hemisphere and climate zone.",
            'winter' => "{$label} {$year}–{$next}: roughly December {$year} through February {$next} in the northern hemisphere (June–August {$year} in the southern) — a cool-season or protected planting. The window may cross into {$next}; work out the actual months for the farmer's own location.",
            default => "{$label} {$year}: work out the actual months for the farmer's own location, hemisphere and climate zone.",
        };
    }

    /** The season's title with its years: "Dry season 2026–27", "Spring planting 2026". */
    public static function seasonTitle(string $key, int $year, ?string $country = null): string
    {
        if ($country && self::valid($country) && self::valid($country) !== self::code()) {
            return self::as($country, fn () => self::seasonTitle($key, $year));
        }
        $label = self::seasons()[$key] ?? ucfirst($key);
        if (in_array($key, ['dry', 'winter'], true)) {
            return $label . ' ' . $year . '–' . substr((string) ($year + 1), -2);
        }

        return $label . ' ' . $year;
    }

    // ------------------------------------------------------------------
    // Anee: language and the country's agencies, for prompts
    // ------------------------------------------------------------------

    public static function agency(string $key): string
    {
        return (string) self::get('agencies.' . $key, '');
    }

    /**
     * One line every analysis prompt carries: where, in what language, on
     * whose word. `$country` is the FIELD's country when it is not the
     * farmer's own (a Filipino farmer asking about a field in Iowa gets
     * Iowa's agencies and dollars); the language stays the farmer's.
     */
    public static function promptBlock(?string $country = null, ?string $languageOf = null): string
    {
        $farmer = self::code();
        $field = self::valid($country) ?: $farmer;
        $c = self::conf($field);
        $langCode = self::valid($languageOf) ?: $farmer;
        $lang = (data_get(self::conf($langCode), 'language', 'en') === 'en')
            ? 'Write in plain English only — no Filipino/Tagalog words, expressions or interjections anywhere in the document.'
            : 'Write in English with the odd Tagalog farm word where it is the natural one.';
        $research = self::as($field, fn () => self::agency('research'));
        $met = self::as($field, fn () => self::agency('met'));
        $tail = ' Money is in ' . $c['currency']['name'] . ' (' . $c['currency']['symbol'] . ').'
            . ' Name only varieties, products and practices actually available and registered in ' . $c['name'] . '.'
            . ' Authorities to prefer: ' . $research . '; weather and climate from ' . $met . '.';
        if ($field === $farmer) {
            return 'COUNTRY: the farmer is in ' . $c['name'] . ' (' . $c['code'] . '). ' . $lang . $tail;
        }

        // A field in another country than the farmer's account: the field
        // wins on every fact, and she must not decline for being "set up"
        // for the farmer's home country.
        return 'COUNTRY: THE FIELD IS IN ' . $c['name'] . ' (' . $c['code'] . '). The farmer\'s account is in ' . self::name($farmer)
            . ', but this question is about a field in ' . $c['name'] . ' — every climate fact, season, weather record, agency, variety and product must be '
            . $c['name'] . '\'s. Anything you were told about serving farmers in ' . self::name($farmer) . ' does not limit you here: answer for '
            . $c['name'] . ' as fully as you would for a farmer there, and never decline or redirect to ' . self::name($farmer) . '. ' . $lang . $tail;
    }

    /** The word rule alone, for the chat persona. */
    public static function languageRule(): string
    {
        return self::englishOnly()
            ? 'Write in plain English. Do not use Filipino, Tagalog, Bisaya or Ilocano words, expressions or interjections — not even one. If the farmer writes in another language, answer in that language.'
            : 'If they write in Tagalog, Bisaya, Ilocano or Taglish, answer the same way.';
    }

    // ------------------------------------------------------------------
    // For the browser
    // ------------------------------------------------------------------

    /** What the page's scripts need: window.ANEE_REGION. */
    public static function js(): array
    {
        $c = self::conf();

        return [
            'code' => $c['code'],
            'name' => $c['name'],
            'flag' => $c['flag'],
            'ph' => self::ph(),
            'englishOnly' => self::englishOnly(),
            'currency' => $c['currency']['code'],
            'symbol' => $c['currency']['symbol'],
            'locale' => $c['currency']['locale'],
            'tz' => $c['timezone'],
            'phone' => ['placeholder' => $c['phone']['placeholder'] ?? '', 'hint' => $c['phone']['hint'] ?? ''],
            'address' => $c['address'],
            'lot' => $c['lot'],
            'divisions' => self::divisions(),
            'exampleLocation' => $c['exampleLocation'] ?? '',
            'yieldUnits' => $c['yieldUnits'] ?? [],
            'words' => $c['words'] ?? [],
            // The upgrade sheet's price lines, in this country's money.
            'tiers' => collect(['solo', 'owner'])->mapWithKeys(function ($tier) {
                $m = self::tierPrice($tier, 'month');
                $y = self::tierPrice($tier, 'year');
                if ($m === null) {
                    return [$tier => null];
                }
                $fmt = fn ($n) => self::symbol() . number_format((float) $n, fmod((float) $n, 1.0) > 0 ? 2 : 0);

                return [$tier => [
                    'price' => $fmt($m), 'per' => '/month',
                    'year' => $y ? 'or ' . $fmt($y) . '/year — about ' . $fmt(round($y / 12, 2)) . '/mo' : '',
                ]];
            })->filter()->all(),
            'pay' => $c['pay'] ?? [],
        ];
    }
}
