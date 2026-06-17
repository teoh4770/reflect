<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Transcription;

class TranscriptionService
{
    public function transcribe(UploadedFile|string $audio): string
    {
        try {
            $transcription = is_string($audio) 
                ? Transcription::fromPath($audio) 
                : Transcription::of($audio);
                
            return (string) $transcription->generate();
        } catch (Exception $e) {
            Log::error('Transcription failed', [
                'error' => $e->getMessage(),
                'file' => $audio instanceof UploadedFile ? $audio->getClientOriginalName() : 'string',
            ]);
            throw $e;
        }
    }
}
