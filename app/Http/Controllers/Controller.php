<?php

namespace App\Http\Controllers;

use App\Support\WorkerContext;
use Illuminate\Http\Response;

abstract class Controller
{
    /**
     * The page a worker meets when they reach a module their grant does not
     * cover — typed by hand, bookmarked before the right was taken away, or
     * linked from something that outlived it.
     *
     * A page, not a JSON refusal, and deliberately not a 404: the module is
     * really there, and pretending otherwise sends a farmer hunting for a
     * broken link. The owner asked for a plain "sorry, you have no access" —
     * this is that, carrying 403 so the answer is honest to caches and crawlers
     * as well as to the reader.
     *
     * Returns null when the visitor is not in a worker context, so a caller can
     * write `if ($no = $this->workerNoAccess('the Maps module')) { return $no; }`
     * and read as the gate it is.
     */
    protected function workerNoAccess(string $what, ?string $backUrl = null, ?string $backLabel = null): ?Response
    {
        if (! WorkerContext::inWorkerContext()) {
            return null;
        }

        return response()->view('sm.no-access', [
            'what' => $what,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
        ], 403);
    }
}
