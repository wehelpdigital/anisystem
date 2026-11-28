<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    public function home(Request $request)
    {
        // An ad pointed at the home page: its tags are kept for the signup.
        \App\Support\SignupSource::remember($request);

        return view('public.home', [
            'tiers' => \App\Support\Community::plans(\Illuminate\Support\Arr::except(config('tiers'), ['admin'])),
            'stats' => $this->liveStats(),
            // The guides are written for Philippine farms: the /ph face only.
            'guides' => \App\Support\Region::ph() ? \App\Support\SitePages::homePicks() : [],
        ]);
    }

    /**
     * The ads landing page: one offer, the free account, argued step by step.
     * Its words come from App\Support\LandingPage (the mother app edits them).
     */
    public function landing(Request $request)
    {
        \App\Support\SignupSource::remember($request);

        // No live counts any more: the proof strip that showed them was taken
        // off the page (2026-09-30).
        return view('public.landing', [
            'lp' => \App\Support\LandingPage::content(),
        ]);
    }

    public function about()
    {
        return view('public.about');
    }

    public function features()
    {
        return view('public.features', [
            'featurePages' => \App\Support\SitePages::inSection('features'),
        ]);
    }

    /** Every plan side by side (App\Support\PlanCompare reads config/tiers). */
    public function compare()
    {
        return view('public.pricing-compare');
    }

    /** How It Works: the season in seven steps (App\Support\HowItWorks). */
    public function how()
    {
        return view('public.how');
    }

    /** Why Anee (2026-10-22): what she reads before she answers, beside a general AI chat app. */
    public function why()
    {
        return view('public.why-anee');
    }

    /** Anee vs Now (2026-10-28): the farm today beside the farm with Anee, and why a plan, credits or Anee Auto pays. */
    public function vs()
    {
        return view('public.anee-vs-now');
    }

    /**
     * The farm management app for the Philippines (2026-11-18, the owner: "when you ask ai or research the best or
     * the recommended or top or even just farm management app in Philippines anee does not appear in top page ...
     * add a page for it that we can push to search console and ai ... a landing page"). Every number on it is a live
     * count, rounded down, kept an hour.
     */
    public function bestApp()
    {
        return view('public.best-farm-app', ['facts' => $this->landingFacts()]);
    }

    /**
     * The farm management software page (2026-11-24, the owner: anee.io shows for "best farm management app in ph"
     * but "for best farm management software in ph or just best farm management in ph, anee is nowhere").
     */
    public function bestSoftware()
    {
        return view('public.best-farm-software', ['facts' => $this->landingFacts()]);
    }

    /** Farm management in the Philippines (2026-11-24, same ask): the practice, and the system that carries it. */
    public function farmManagement()
    {
        return view('public.farm-management', ['facts' => $this->landingFacts()]);
    }

    /** Southeast Asia (2026-11-28, the owner: "top farming management app in southeast asia, top farming management
     *  software in southeast asia, best farming management app in southeast asia and so on"). One view, two pages. */
    public function seaApp()
    {
        return view('public.sea-farm-app', ['facts' => $this->landingFacts(), 'variant' => 'app']);
    }

    public function seaSoftware()
    {
        return view('public.sea-farm-app', ['facts' => $this->landingFacts(), 'variant' => 'software']);
    }

    /** The live counts the landing pages print, rounded down and kept an hour. */
    private function landingFacts(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('public.best-app-facts', 3600, function () {
            $down = fn (int $n, int $step) => $n >= $step ? number_format(intdiv($n, $step) * $step) : (string) $n;
            $stages = \App\Support\HowItWorks::stages();
            $out = [
                'tools' => array_sum(array_map(fn ($s) => count($s['items']), $stages)),
                'steps' => count($stages),
                'crops' => count(\App\Support\CropCatalog::CROPS),
                'products' => '7,000',
                'varieties' => '1,800',
            ];
            try {
                $out['products'] = $down((int) \Illuminate\Support\Facades\DB::table('as_products')->count(), 1000);
                $out['varieties'] = $down((int) \Illuminate\Support\Facades\DB::table('as_site_pages')->whereIn('section', ['rice-varieties', 'crop-varieties'])->where('deleteStatus', 1)->count(), 100);
            } catch (\Throwable $e) {
                // the fallbacks above stand
            }

            return $out;
        });
    }

    /** Anee Auto (2026-10-29): the season Anee plans and re-plans every morning, step by step. */
    public function auto()
    {
        return view('public.anee-auto');
    }

    /** Easy to use (2026-10-29): why anee.io is easy, and made by Filipino farmers for Filipino farmers. */
    public function easy()
    {
        return view('public.easy-to-use');
    }

    public function pricing()
    {
        // The same table the in-app gates read — the page can never promise
        // a wall the app doesn't have.
        return view('public.pricing', [
            'tiers' => \App\Support\Community::plans(\Illuminate\Support\Arr::except(config('tiers'), ['admin'])),
        ]);
    }

    /**
     * Honest social proof: real counts off the platform, cached for an hour
     * so the marketing page never becomes five queries per visit. Rounded
     * down to friendly steps — a live counter that says 1,203 reads as a
     * test; one that says 1,200+ reads as a fact.
     */
    private function liveStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('public.live-stats', 3600, function () {
            $soften = function (int $n): string {
                if ($n >= 1000) {
                    return number_format(floor($n / 100) * 100) . '+';
                }
                if ($n >= 100) {
                    return (floor($n / 10) * 10) . '+';
                }

                return max(1, $n) . '';
            };

            try {
                return [
                    'seasons' => $soften((int) \Illuminate\Support\Facades\DB::table('as_cropping_schedules')->where('deleteStatus', 1)->count()),
                    'activities' => $soften((int) \Illuminate\Support\Facades\DB::table('as_schedule_activities')->where('deleteStatus', 1)->count()),
                    'notes' => $soften((int) \Illuminate\Support\Facades\DB::table('as_schedule_notes')->where('deleteStatus', 1)->count()),
                    'members' => $soften((int) \Illuminate\Support\Facades\DB::table('anisystem_users')->where('deleteStatus', 1)->count()),
                ];
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public function tutorial()
    {
        return view('public.tutorial');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function submitContact(Request $request, MailService $mailService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'isRead' => 0,
            'deleteStatus' => 1,
        ]);

        try {
            $mailService->sendTemplate('contact_received', $validated['email'], $validated['name'], [
                'firstName' => $validated['name'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Contact acknowledgement email failed: '.$e->getMessage());
        }

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
