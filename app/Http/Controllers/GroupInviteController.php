<?php

namespace App\Http\Controllers;

use App\Support\GroupInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The Group seat of the bottom bar: Level Up Farmers, Anee's Facebook group (App\Support\GroupInvite). */
class GroupInviteController extends Controller
{
    public function page()
    {
        return view('group.index', ['answer' => GroupInvite::answer()]);
    }

    /** "I joined" (a tap on the join button) or "do not show this again": the popup stops asking. */
    public function answer(Request $request)
    {
        $data = $request->validate(['answer' => 'required|in:' . implode(',', GroupInvite::ANSWERS)]);
        // From inside somebody's farm too: the answer belongs to the person, never to the farm.
        GroupInvite::record((int) Auth::id(), $data['answer']);

        return response()->json(['success' => true, 'message' => 'Saved.', 'data' => ['answer' => GroupInvite::answer()]]);
    }
}
