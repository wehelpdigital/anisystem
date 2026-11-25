<?php

namespace App\Http\Middleware;

use App\Support\ClipUpload;
use App\Support\StoredClip;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Puts a clip that went up in pieces where the door expects an uploaded file.
 *
 * A request that carries `clipToken` in place of its video meets its own door, validator and rights as it always did;
 * this only hands it the file. The answer to a clip that was filed is kept for an hour under the clip's own name, so a
 * phone that lost the answer on the way back can ask again and be told the same thing, instead of filing it twice.
 */
class TakesClipToken
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('post') || ! $request->has('clipToken') || ! Auth::check()) {
            return $next($request);
        }
        $token = (string) $request->input('clipToken');
        $field = $request->input('clipField') === 'video' ? 'video' : 'clip';
        foreach (['clipToken', 'clipField'] as $k) {
            $request->request->remove($k);
            $request->query->remove($k);
        }

        $upload = ClipUpload::read($token, (int) Auth::id());
        if (! $upload || blank($upload['m'])) {
            return response()->json(['success' => false, 'message' => 'This video was sent too long ago. Please choose it again.'], 422);
        }
        $clip = new StoredClip($upload);
        $answer = 'clip.answer.' . $clip->uploadName() . '.' . sha1($request->path());
        if ($said = $this->kept($answer)) {
            return response()->json($said);
        }

        $request->files->set($field, $clip);
        // The request may have read its files already (an earlier middleware asking for all()), and keeps that reading.
        (function () { $this->convertedFiles = null; })->call($request);
        $response = $next($request);

        if ($response instanceof JsonResponse && $response->isSuccessful()) {
            $data = $response->getData(true);
            if (is_array($data) && ($data['success'] ?? false)) {
                try {
                    Cache::put($answer, $data, 3600);
                } catch (\Throwable $e) {
                    // The clip is filed either way.
                }
            }
        }

        return $response;
    }

    private function kept(string $key): ?array
    {
        try {
            $said = Cache::get($key);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($said) ? $said : null;
    }
}
