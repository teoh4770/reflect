<?php

namespace Tests\Feature\Jobs;

use App\Events\TranscriptionProcessed;
use App\Jobs\ProcessTranscription;
use App\Services\TranscriptionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessTranscriptionChunkTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transcribes_audio_broadcasts_event_and_deletes_file()
    {
        Event::fake();

        $audioPath = storage_path('app/temp_audio_test.webm');
        File::put($audioPath, 'dummy content');

        $sessionId = 'test-session-123';

        // Mock the TranscriptionService
        $this->instance(
            TranscriptionService::class,
            Mockery::mock(TranscriptionService::class, function (MockInterface $mock) use ($audioPath) {
                $mock->shouldReceive('transcribe')
                    ->once()
                    ->with($audioPath)
                    ->andReturn('Hello World');
            })
        );

        ProcessTranscription::dispatch($audioPath, $sessionId);

        Event::assertDispatched(TranscriptionProcessed::class, function ($event) use ($sessionId) {
            return $event->text === 'Hello World' && $event->sessionId === $sessionId;
        });

        $this->assertFalse(File::exists($audioPath));
    }

    public function test_it_deletes_file_even_if_transcription_fails()
    {
        Event::fake();

        $audioPath = storage_path('app/temp_audio_test_fail.webm');
        File::put($audioPath, 'dummy content');

        $sessionId = 'test-session-123';

        // Mock the TranscriptionService to throw an exception
        $this->instance(
            TranscriptionService::class,
            Mockery::mock(TranscriptionService::class, function (MockInterface $mock) use ($audioPath) {
                $mock->shouldReceive('transcribe')
                    ->once()
                    ->with($audioPath)
                    ->andThrow(new Exception('Transcription failed!'));
            })
        );

        try {
            ProcessTranscription::dispatch($audioPath, $sessionId);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertEquals('Transcription failed!', $e->getMessage());
        }

        $this->assertFalse(File::exists($audioPath));
        Event::assertNotDispatched(TranscriptionProcessed::class);
    }
}
