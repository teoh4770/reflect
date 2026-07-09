<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TranscriptionChunkProcessed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $text,
        public string $sessionId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('transcription.' . $this->sessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TranscriptionChunkProcessed';
    }
}
