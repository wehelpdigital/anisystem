<?php

namespace App\Http\Controllers;

use App\Support\Wishlist;
use Illuminate\Http\Request;

/**
 * The First 50 wishlist at /unang-50 (App\Support\Wishlist). Its own page: no header, no footer, no link to the rest of
 * the site and not a word of the app's name, kept out of search results. The answers come back here in one piece.
 */
class WishlistController extends Controller
{
    public function show(Request $request)
    {
        $response = response()->view('wishlist.form', [
            'choices' => Wishlist::forPage(),
            'spots' => Wishlist::spotsLeft(),
            'limit' => (int) config('wishlist.limit', 50),
            'offer' => config('wishlist.offer'),
            'ref' => preg_match('/^[A-Z0-9]{4,12}$/', strtoupper((string) $request->query('r'))) ? strtoupper((string) $request->query('r')) : null,
        ]);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    public function save(Request $request)
    {
        // A field no person sees: a robot fills it.
        if (filled($request->input('website'))) {
            return response()->json(['success' => true, 'message' => 'ok', 'data' => ['position' => 0, 'code' => '', 'friends' => 0, 'spots' => 0, 'tips' => []]]);
        }
        $request->validate([
            'firstName' => 'required|string|max:60',
            'consent' => 'accepted',
        ], [
            'firstName.required' => 'Pakisulat ang pangalan mo. (Please write your name.)',
            'consent.accepted' => 'Pakitsek ang pahintulot sa dulo. (Please tick the consent box.)',
        ]);
        $phone = Wishlist::phone($request->input('phone'));
        $email = filter_var(trim((string) $request->input('email')), FILTER_VALIDATE_EMAIL);
        if (! $phone && ! $email) {
            return response()->json(['success' => false, 'message' => 'Pakilagay ang mobile number mo (09XX XXX XXXX) o email. (Please give your mobile number or email.)',
                'errors' => ['phone' => ['A mobile number or an email is needed.']]], 422);
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => Wishlist::save($request->all(), $request)]);
    }
}
