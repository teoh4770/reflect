<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Transcription;

class TranscriptionService
{
    /**
     * Transcribe the given audio file to text.
     */
    public function transcribe(UploadedFile|string $audio): string
    {
        try {
            return (string) Transcription::of($audio)->generate();
        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'error' => $e->getMessage(),
                'file' => $audio instanceof UploadedFile ? $audio->getClientOriginalName() : 'string',
            ]);
            return '';
        }
    }
}
