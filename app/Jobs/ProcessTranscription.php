<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Events\TranscriptionProcessed;
use App\Services\TranscriptionService;
use Laravel\Ai\Exceptions\FailoverableException;

class ProcessTranscription implements ShouldQueue
{
    use Queueable;

    public int $timeout = 20;

    public function __construct(
        public string $audioPath,
        public string $sessionId
    )
    {
    }

    public function handle(TranscriptionService $transcriptionService): void
    {
        try {
            $transcriptionResult = $transcriptionService->transcribe($this->audioPath);
            broadcast(new TranscriptionProcessed($transcriptionResult, $this->sessionId));
        } finally {
            if (file_exists($this->audioPath)) {
                unlink($this->audioPath);
            }
        }
    }
}
