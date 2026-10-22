<?php

namespace App\Http\Controllers;

use App\Support\Trash;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The global Trash (2026-10-23, the owner: "have a global trash module. now
 * all deleted items will go to trash and will be permanently delete after 30
 * days"). The owner's own: what they and their workers deleted, restored or
 * deleted for good from here (App\Support\Trash).
 */
class TrashController extends Controller
{
    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }

    private function owner(): int
    {
        // WorkerModuleAccess keeps a worker out ('trash.*' is the owner's); this is the second lock.
        abort_if(WorkerContext::inWorkerContext(), 403, 'The Trash is the farm owner\'s.');

        return (int) Auth::id();
    }

    public function page()
    {
        $this->owner();

        return view('app.trash');
    }

    public function list()
    {
        $owner = $this->owner();
        Trash::sweepSoon($owner);
        $rows = Trash::listFor($owner);
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['group']] = ($groups[$r['group']] ?? 0) + 1;
        }

        return $this->json(true, '', [
            'rows' => $rows,
            'groups' => collect(Trash::GROUPS)->filter(fn ($l, $k) => isset($groups[$k]))->map(fn ($l, $k) => ['key' => $k, 'label' => $l, 'n' => $groups[$k]])->values(),
            'days' => Trash::DAYS,
        ]);
    }

    public function restore(Request $request)
    {
        [$ok, $say, $open] = Trash::restore($this->owner(), (int) $request->input('id'));

        return $this->json($ok, $say, ['open' => $open], $ok ? 200 : 422);
    }

    public function purge(Request $request)
    {
        [$ok, $say] = Trash::purge($this->owner(), (int) $request->input('id'));

        return $this->json($ok, $say, [], $ok ? 200 : 422);
    }

    public function empty()
    {
        $n = Trash::empty($this->owner());

        return $this->json(true, $n ? 'The Trash is empty. ' . $n . ' ' . ($n === 1 ? 'thing' : 'things') . ' deleted for good.' : 'The Trash was already empty.');
    }
}
