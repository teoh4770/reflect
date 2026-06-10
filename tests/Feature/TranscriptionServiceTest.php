<?php

namespace Tests\Feature;

use App\Services\TranscriptionService;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Transcription;
use Tests\TestCase;

class TranscriptionServiceTest extends TestCase
{
    public function test_it_transcribes_audio_to_text()
    {
        Transcription::fake([
            'This is a transcribed sentence.'
        ]);

        $service = new TranscriptionService();
        $audioFile = UploadedFile::fake()->createWithContent('recording.mp3', 'dummy audio content');

        $result = $service->transcribe($audioFile);

        $this->assertEquals('This is a transcribed sentence.', $result);
    }
}
