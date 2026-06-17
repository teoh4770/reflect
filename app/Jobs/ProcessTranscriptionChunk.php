<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Events\TranscriptionChunkProcessed;
use App\Services\TranscriptionService;

class ProcessTranscriptionChunk implements ShouldQueue
{
    use Queueable;

    public int $timeout = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $audioPath,
        public string $sessionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TranscriptionService $transcriptionService): void
    {
        try {
            $text = $transcriptionService->transcribe($this->audioPath);
            broadcast(new TranscriptionChunkProcessed($text, $this->sessionId));
        } finally {
            if (file_exists($this->audioPath)) {
                unlink($this->audioPath);
            }
        }
    }
}
