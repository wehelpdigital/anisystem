<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function dashboard(Request $request, NotificationService $notifications)
    {
        $user = $request->user();

        // Keep the expiry reminder current whenever the client lands here.
        $notifications->syncExpiryReminder($user);

        $subscription = $user->currentSubscription();

        $scheduleCount = $user->schedules()->count();

        $latestSchedules = $user->schedules()
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return view('app.dashboard', [
            'user' => $user,
            'subscription' => $subscription,
            'scheduleCount' => $scheduleCount,
            'latestSchedules' => $latestSchedules,
        ]);
    }
}
