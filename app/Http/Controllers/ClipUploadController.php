<?php

namespace App\Http\Controllers;

use App\Support\ClipUpload;
use App\Support\VideoOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * The pieces of a video (App\Support\ClipUpload). Where the clip goes, and whether this person may put it there, is
 * asked by the door that takes the finished token, not here.
 */
class ClipUploadController extends Controller
{
    public function start(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'bytes' => 'required|integer|min:1|max:' . VideoOptimizer::MAX_BYTES,
        ], ['bytes.max' => 'That video is larger than 300 MB. Record a shorter one.']);
        if ($v->fails()) {
            return $this->no($v->errors()->first());
        }
        try {
            $upload = ClipUpload::start((int) Auth::id(), (string) ($request->input('name') ?: 'clip.mp4'), (int) $request->input('bytes'));
        } catch (\Throwable $e) {
            Log::warning('ClipUpload: could not start', ['error' => $e->getMessage()]);

            // 503: worth another try, and the phone makes it.
            return $this->no('The video could not be started. Please try again.', 503);
        }

        return response()->json(['success' => true] + $upload);
    }

    public function part(Request $request)
    {
        $upload = ClipUpload::read((string) $request->input('token'), (int) Auth::id());
        if (! $upload) {
            return $this->no('This video was started too long ago. Please choose it again.', 410);
        }
        $piece = $request->file('part');
        if (! $piece || ! $piece->isValid()) {
            return $this->no('A piece of the video did not arrive whole. Please try again.', 503);
        }
        try {
            $kept = ClipUpload::part($upload, (int) $request->input('number'), (string) $piece->getRealPath());
        } catch (\RuntimeException $e) {
            // An AwsException is a RuntimeException too, and its words are not for a person.
            if ($e instanceof \Aws\Exception\AwsException) {
                return $this->later($e);
            }

            return $this->no($e->getMessage());
        } catch (\Throwable $e) {
            return $this->later($e);
        }

        return response()->json(['success' => true] + $kept);
    }

    public function drop(Request $request)
    {
        if ($upload = ClipUpload::read((string) $request->input('token'), (int) Auth::id())) {
            ClipUpload::drop($upload);
        }

        return response()->json(['success' => true]);
    }

    private function later(\Throwable $e)
    {
        Log::warning('ClipUpload: a piece was not kept', ['error' => $e->getMessage()]);

        return $this->no('A piece of the video was not saved. Trying again.', 503);
    }

    private function no(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
