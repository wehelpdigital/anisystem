<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleProtocol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProtocolController extends BaseScheduleController
{
    /**
     * Upsert the single protocol row for a schedule. Accepts plain-text
     * content and/or a document file; protocolType is derived from what
     * exists after the save (both / file / text).
     */
    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'protocolContent' => 'nullable|string',
            'protocolFile'    => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $protocol = AsScheduleProtocol::active()->where('croppingScheduleId', $schedule->id)->first();
        if (!$protocol) {
            $protocol = new AsScheduleProtocol([
                'croppingScheduleId' => $schedule->id,
                'protocolType' => 'text',
                'deleteStatus' => 1,
            ]);
        }

        $protocol->protocolContent = $request->input('protocolContent');

        if ($request->hasFile('protocolFile')) {
            if ($protocol->protocolFile && Storage::disk('public')->exists($protocol->protocolFile)) {
                Storage::disk('public')->delete($protocol->protocolFile);
            }
            $file = $request->file('protocolFile');
            $stored = $file->store('schedule-protocols/' . $schedule->id, 'public');
            $protocol->protocolFile = $stored;
            $protocol->protocolFileOriginalName = $file->getClientOriginalName();
        }

        if ($protocol->protocolFile && filled($protocol->protocolContent)) {
            $protocol->protocolType = 'both';
        } elseif ($protocol->protocolFile) {
            $protocol->protocolType = 'file';
        } else {
            $protocol->protocolType = 'text';
        }

        $protocol->save();

        return $this->jsonOk('Protocol saved.', ['data' => $protocol]);
    }

    /**
     * Stream the uploaded protocol document with its original filename.
     */
    public function download(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $protocol = AsScheduleProtocol::active()->where('croppingScheduleId', $schedule->id)->first();

        if (!$protocol || !$protocol->protocolFile || !Storage::disk('public')->exists($protocol->protocolFile)) {
            abort(404, 'Protocol file not found.');
        }

        return Storage::disk('public')->download($protocol->protocolFile, $protocol->protocolFileOriginalName ?: 'protocol');
    }
}
