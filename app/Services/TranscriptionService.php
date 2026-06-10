<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Laravel\Ai\Transcription;

class TranscriptionService
{
    /**
     * Transcribe the given audio file to text.
     */
    public function transcribe(UploadedFile|string $audio): string
    {
        // note: remember to handle exception for this line
        return (string) Transcription::of($audio)->generate();
    }
}
