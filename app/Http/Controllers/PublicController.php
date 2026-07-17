<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Plan;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    public function home()
    {
        $plans = Plan::visible()->get();

        return view('public.home', compact('plans'));
    }

    public function about()
    {
        return view('public.about');
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
