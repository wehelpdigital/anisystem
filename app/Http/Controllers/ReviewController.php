<?php

namespace App\Http\Controllers;

use App\Models\AsAppReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "How are we doing?" — asked once, and never again once answered.
 *
 * The rule for asking lives here rather than in the view, because it is a
 * judgement about a person rather than about a page: has this account been
 * around long enough to have an opinion, has it already given one, and has it
 * already waved the question away twice?
 */
class ReviewController extends Controller
{
    /** How long an account must have existed before its opinion is asked for. */
    private const MIN_DAYS = 3;

    /** Two "not now"s is an answer. */
    private const MAX_DISMISSALS = 2;

    public static function shouldAsk(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->created_at && $user->created_at->diffInDays(now()) < self::MIN_DAYS) {
            return false;
        }

        $row = AsAppReview::where('userId', $user->id)->where('deleteStatus', 1)->first();
        if (! $row) {
            return true;
        }

        // Rated already, or waved away enough times to have made the point.
        return $row->rating < 1 && $row->dismissals < self::MAX_DISMISSALS;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1500',
        ]);

        AsAppReview::updateOrCreate(
            ['userId' => (int) Auth::id()],
            [
                'rating' => (int) $data['rating'],
                'review' => filled($data['review'] ?? null) ? trim($data['review']) : null,
                'device' => $this->device($request),
                'deleteStatus' => 1,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Thank you.']);
    }

    /** A "not now" — counted, so the third visit is left in peace. */
    public function dismiss(Request $request)
    {
        $row = AsAppReview::firstOrNew(['userId' => (int) Auth::id()]);
        if (($row->rating ?? 0) < 1) {
            $row->rating = 0;
            $row->dismissals = (int) ($row->dismissals ?? 0) + 1;
            $row->device = $this->device($request);
            $row->deleteStatus = 1;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    private function device(Request $request): string
    {
        $ua = (string) $request->userAgent();
        if (preg_match('~iPad|Tablet~i', $ua)) {
            return 'tablet';
        }

        return preg_match('~Mobi|Android|iPhone~i', $ua) ? 'mobile' : 'desktop';
    }
}
