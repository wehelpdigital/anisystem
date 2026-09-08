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
                    ->orWhere('company', 'like', $like)
                    ->orWhere('address', 'like', $like)
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
        return [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'email' => $c->email,
            'company' => $c->company,
            'address' => $c->address,
            'notes' => $c->notes,
            'tags' => array_values((array) $c->tags),
        ];
    }

    /** Shared rules for store and update. Tags arrive as an array of words. */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30'],
        ]);

        // Tags tidied: trimmed, de-duplicated case-insensitively, empties out.
        $seen = [];
        $tags = [];
        foreach ((array) ($data['tags'] ?? []) as $t) {
            $t = trim($t);
            if ($t === '' || isset($seen[mb_strtolower($t)])) {
                continue;
            }
            $seen[mb_strtolower($t)] = true;
            $tags[] = $t;
        }
        $data['tags'] = $tags;

        return $data;
    }
}
