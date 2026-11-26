<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The First 50's answers in the admin panel (App\Support\Wishlist): how they add up, every farmer, their place in line,
 * what they wished for, and a status and a note for whoever calls them. A spreadsheet of it all for the owner's files.
 */
class AdminWishlistController extends Controller
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'accepted' => 'Gets a slot', 'notfit' => 'Not a fit'];

    public function page()
    {
        return view('admin.wishlist', ['statuses' => self::STATUSES]);
    }

    public function data()
    {
        $entries = Wishlist::entries();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'entries' => $entries,
            'totals' => Wishlist::totals($entries),
            'count' => count($entries),
            'limit' => (int) config('wishlist.limit', 50),
            'spots' => Wishlist::spotsLeft(),
            'accepted' => count(array_filter($entries, fn ($e) => $e['status'] === 'accepted')),
            'link' => url('/wishlist'),
        ]]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'nullable|in:' . implode(',', array_keys(self::STATUSES)), 'note' => 'nullable|string|max:2000']);
        $row = array_filter(['status' => $data['status'] ?? null], fn ($v) => $v !== null);
        if ($request->has('note')) {
            $row['adminNote'] = trim((string) $request->input('note')) ?: null;
        }
        abort_unless(DB::table(Wishlist::TABLE)->where('id', $id)->exists(), 404);
        DB::table(Wishlist::TABLE)->where('id', $id)->update($row + ['updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Saved', 'data' => null]);
    }

    /** Everything, one farmer a row, for a spreadsheet. */
    public function export()
    {
        $rows = Wishlist::entries();
        $cols = ['place' => 'Place', 'name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'contactVia' => 'Reach on', 'contactTime' => 'Best time',
            'role' => 'Who', 'where' => 'Where', 'crops' => 'Crops', 'seasons' => 'Plantings a year', 'farmSize' => 'Farm size', 'lots' => 'Lots',
            'helpers' => 'Helpers', 'pains' => 'Hardest', 'wishes' => 'Wishes', 'wishText' => 'Own wish', 'yield' => 'Last harvest',
            'phoneType' => 'Phone', 'signal' => 'Signal on the farm', 'payWay' => 'Would pay', 'payAmount' => 'Fair a season', 'heardFrom' => 'Heard from',
            'friends' => 'Friends brought', 'referredBy' => 'Came through', 'status' => 'Status', 'note' => 'Note', 'lang' => 'Language', 'at' => 'Answered'];

        return response()->streamDownload(function () use ($rows, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // so a spreadsheet reads the peso sign and the ñ
            fputcsv($out, array_values($cols));
            foreach ($rows as $r) {
                fputcsv($out, array_map(fn ($k) => is_array($r[$k]) ? implode('; ', $r[$k]) : ($k === 'status' ? (self::STATUSES[$r[$k]] ?? $r[$k]) : $r[$k]), array_keys($cols)));
            }
            fclose($out);
        }, 'wishlist-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
