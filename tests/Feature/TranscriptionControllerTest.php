<?php

namespace Tests\Feature;

use App\Events\TranscriptionProcessed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Transcription;
use Tests\TestCase;

class TranscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_it_transcribes_a_chunk_and_broadcasts_the_result()
    {
        // 1. Arrange
        Event::fake();
        Transcription::fake([
            'Transcribed chunk text.'
        ]);

        $audioChunk = UploadedFile::fake()->createWithContent('chunk.webm', 'dummy audio chunk');
        $sessionId = 'test-session-123';

        // 2. Act
        $response = $this->postJson('/api/transcribe-chunk', [
            'audio' => $audioChunk,
            'session_id' => $sessionId,
        ]);

        // 3. Assert
        $response->assertStatus(200);

        Event::assertDispatched(TranscriptionProcessed::class, function ($event) use ($sessionId) {
            return $event->text === 'Transcribed chunk text.' && $event->sessionId === $sessionId;
        });
    }
}
