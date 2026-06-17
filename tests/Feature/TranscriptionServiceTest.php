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

    public function test_it_transcribes_from_file_path()
    {
        Transcription::fake([
            'This is a path transcribed sentence.'
        ]);

        $service = new TranscriptionService();
        
        $path = storage_path('app/temp_test.webm');
        file_put_contents($path, 'dummy content');

        $result = $service->transcribe($path);

        $this->assertEquals('This is a path transcribed sentence.', $result);
        
        unlink($path);
    }
}
