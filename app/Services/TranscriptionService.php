<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Transcription;

class TranscriptionService
{
    /**
     * @throws FailoverableException
     * @throws Exception
     */
    public function transcribe(UploadedFile|string $audio): string
    {
        try {
            $transcription = is_string($audio)
                ? Transcription::fromPath($audio)
                : Transcription::of($audio);

            return (string)$transcription->generate();
        } catch (FailoverableException $e) {
            Log::error('Error from transcription service', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        } catch (Exception $e) {
            Log::error('Transcription failed', [
                'error' => $e->getMessage(),
                'file' => $audio instanceof UploadedFile ? $audio->getClientOriginalName() : 'string',
            ]);

            throw $e;
        }
    }
}
