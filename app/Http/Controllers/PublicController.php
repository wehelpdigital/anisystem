<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'tiers' => \Illuminate\Support\Arr::except(config('tiers'), ['admin']),
            'stats' => $this->liveStats(),
        ]);
    }

    public function about()
    {
        return view('public.about');
    }

    public function features()
    {
        return view('public.features');
    }

    public function pricing()
    {
        // The same table the in-app gates read — the page can never promise
        // a wall the app doesn't have.
        return view('public.pricing', [
            'tiers' => \Illuminate\Support\Arr::except(config('tiers'), ['admin']),
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
                return ['seasons' => '—', 'activities' => '—', 'notes' => '—', 'members' => '—'];
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
