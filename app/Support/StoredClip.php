<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * A clip that arrived in pieces (App\Support\ClipUpload), standing where an uploaded file would.
 *
 * Its bytes are on the bucket, not in a temp file here, so it answers what the doors and their validators ask (what it
 * is, how big it is, what it was called) from what the upload knows. VideoOptimizer::storeCompressed joins it instead
 * of saving it. Anything that reads the temp file itself would find it empty, so only the video doors meet one.
 */
class StoredClip extends UploadedFile
{
    private ?string $settled = null;

    public function __construct(private array $upload)
    {
        $stub = tempnam(sys_get_temp_dir(), 'clipstub');
        register_shutdown_function(static fn () => @unlink($stub));
        parent::__construct($stub, (string) $upload['n'], (string) $upload['m'], UPLOAD_ERR_OK, true);
    }

    /** The same for every try at the same clip. */
    public function uploadName(): string
    {
        return pathinfo((string) $this->upload['p'], PATHINFO_FILENAME);
    }

    /** Joined once, however many times it is asked for. */
    public function settle(): string
    {
        return $this->settled ??= ClipUpload::settle($this->upload);
    }

    public function getMimeType(): ?string
    {
        return (string) $this->upload['m'];
    }

    public function getSize(): int|false
    {
        return (int) $this->upload['b'];
    }

    public function isValid(): bool
    {
        return true;
    }
}
