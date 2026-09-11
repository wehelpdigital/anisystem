<?php

namespace App\Http\Controllers;

use App\Models\AsContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The Contact List — a member's own farm phonebook, CRM-style: contacts
 * carry tags (Worker, Tractor Rental, Harvester, Buyer…) so next season's
 * "who was that guy with the harvester?" is a filter chip, not a memory
 * exercise. Personal by design: every query is fenced to the acting
 * user's own rows, and every tier may use it — no gates here.
 */
class ContactListController extends Controller
{
    public function page()
    {
        return view('app.contacts');
    }

    /**
     * One page of the book. `q` searches name/phone/company/address/tags,
     * `tag` narrows to one chip, `page` walks 30 at a time. The distinct
     * tag list (with counts) rides along so the chips row stays honest.
     */
    public function list(Request $request)
    {
        $userId = (int) Auth::id();
        $q = trim((string) $request->query('q', ''));
        $tag = trim((string) $request->query('tag', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 30;

        $base = AsContact::active()->where('userId', $userId);

        if ($q !== '') {
            $base->where(function ($w) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    // The extra numbers and addresses live in JSON and as a
                    // second line; a LIKE across the raw text finds them all
                    // at phonebook scale without asking MySQL for acrobatics.
                    ->orWhere('phones', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('emails', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('address2', 'like', $like)
                    ->orWhere('province', 'like', $like)
                    ->orWhere('town', 'like', $like)
                    ->orWhere('tags', 'like', $like);
            });
        }
        if ($tag !== '') {
            // Tags live as a JSON array; the quoted form matches the exact
            // word without also catching "Harvester Crew" for "Crew"… close
            // enough at phonebook scale, and cheap.
            $base->where('tags', 'like', '%"' . str_replace(['%', '_'], ['\%', '\_'], $tag) . '"%');
        }

        $rows = $base->orderBy('name')
            ->skip(($page - 1) * $per)
            ->take($per + 1)
            ->get();
        $hasMore = $rows->count() > $per;
        $rows = $rows->take($per);

        // Every tag this member has ever used, with how many contacts wear
        // it — the chips row. Aggregated in PHP: a phonebook is hundreds of
        // rows at most, and JSON columns don't group in MySQL without acrobatics.
        $tagCounts = [];
        foreach (AsContact::active()->where('userId', $userId)->pluck('tags') as $tags) {
            foreach ((array) $tags as $t) {
                $tagCounts[$t] = ($tagCounts[$t] ?? 0) + 1;
            }
        }
        ksort($tagCounts, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $rows->map(fn ($c) => $this->shape($c))->values(),
                'hasMore' => $hasMore,
                'page' => $page,
                'tags' => $tagCounts,
                'total' => AsContact::active()->where('userId', $userId)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['userId'] = (int) Auth::id();
        $data['deleteStatus'] = 1;

        $contact = AsContact::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Contact saved.',
            'data' => ['contact' => $this->shape($contact)],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $contact = AsContact::active()
            ->where('userId', (int) Auth::id())
            ->findOrFail($id);

        $contact->fill($this->validated($request))->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact updated.',
            'data' => ['contact' => $this->shape($contact)],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $contact = AsContact::active()
            ->where('userId', (int) Auth::id())
            ->findOrFail($id);

        // Soft: the row steps out of every list but stays recoverable by hand.
        $contact->forceFill(['deleteStatus' => 0])->save();

        return response()->json(['success' => true, 'message' => 'Contact removed.']);
    }

    /** The one shape every response speaks. */
    private function shape(AsContact $c): array
    {
        $phones = array_values(array_filter((array) $c->phones));
        $emails = array_values(array_filter((array) $c->emails));

        return [
            'id' => $c->id,
            'name' => $c->name,
            // The singulars are what a row's Call / Text / Email buttons use,
            // and they are the first of each list by construction.
            'phone' => $c->phone,
            'phones' => $phones,
            'email' => $c->email,
            'emails' => $emails,
            'company' => $c->company,
            'address' => $c->address,
            'address2' => $c->address2,
            'province' => $c->province,
            'town' => $c->town,
            'notes' => $c->notes,
            'tags' => array_values((array) $c->tags),
        ];
    }

    /**
     * Shared rules for store and update.
     *
     * Phones, emails and tags all arrive as arrays and are tidied the same
     * way: trimmed, emptied of blanks, de-duplicated. The first phone and
     * first email are copied down into the plain `phone`/`email` columns,
     * which is what the search and the row's buttons read — write them in
     * one place and they can never drift from the lists.
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phones' => ['nullable', 'array', 'max:10'],
            'phones.*' => ['nullable', 'string', 'max:40'],
            'emails' => ['nullable', 'array', 'max:10'],
            'emails.*' => ['nullable', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:120'],
            'town' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30'],
        ]);

        $data['phones'] = $this->tidyList($data['phones'] ?? []);
        $data['emails'] = $this->tidyList($data['emails'] ?? []);
        $data['tags'] = $this->tidyList($data['tags'] ?? [], 30);

        $data['phone'] = $data['phones'][0] ?? null;
        $data['email'] = $data['emails'][0] ?? null;

        return $data;
    }

    /** Trimmed, blanks dropped, de-duplicated case-insensitively, in order. */
    private function tidyList(array $values, ?int $max = null): array
    {
        $seen = [];
        $out = [];
        foreach ($values as $v) {
            $v = trim((string) $v);
            if ($max !== null) {
                $v = mb_substr($v, 0, $max);
            }
            if ($v === '' || isset($seen[mb_strtolower($v)])) {
                continue;
            }
            $seen[mb_strtolower($v)] = true;
            $out[] = $v;
        }

        return $out;
    }

    /**
     * Is this email already somebody in my phonebook?
     *
     * Asked by the workers module while a new worker is being typed, so it
     * can offer to file them — and stay quiet when they are already filed.
     * Checks the plain column and the JSON list, because a contact's second
     * email counts as knowing them just as much as their first.
     */
    public function lookup(Request $request)
    {
        $email = trim((string) $request->query('email', ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => true, 'data' => ['exists' => false, 'asked' => false]]);
        }

        $like = '%"' . str_replace(['%', '_'], ['\%', '\_'], $email) . '"%';
        $exists = AsContact::active()
            ->where('userId', (int) Auth::id())
            ->where(fn ($w) => $w->where('email', $email)->orWhere('emails', 'like', $like))
            ->exists();

        return response()->json([
            'success' => true,
            'data' => ['exists' => $exists, 'asked' => true],
        ]);
    }

    /**
     * The place pickers' source: the 87 provinces, or the towns of one.
     *
     * Read from as_locations, the same table the community's @location
     * tagging uses — a farmer picks their province and the town list is
     * already narrowed when they reach it, which is the whole point of
     * asking in that order.
     */
    public function places(Request $request)
    {
        $province = trim((string) $request->query('province', ''));

        if ($province === '') {
            $names = \App\Models\AsLocation::where('type', 'province')
                ->orderBy('name')
                ->pluck('name');
        } else {
            $names = \App\Models\AsLocation::where('type', 'city')
                ->where('province', $province)
                ->orderBy('name')
                ->pluck('name');
        }

        return response()->json([
            'success' => true,
            'data' => ['items' => $names->values()],
        ]);
    }
}
