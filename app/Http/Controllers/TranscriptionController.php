<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranscriptionRequest;
use App\Jobs\ProcessTranscription;
use App\Services\TranscriptionService;
use Illuminate\Support\Facades\Storage;

class TranscriptionController extends Controller
{
    const string DEFAULT_AUDIO_FORMAT = 'webm';
    const string DEFAULT_STORAGE_LOCATION = 'local';
    const string TEMP_AUDIO_DIRECTORY = 'temp_audio';

    public function __construct(
        protected TranscriptionService $transcriptionService
    )
    {
    }

    public function __invoke(TranscriptionRequest $request)
    {
        $audio = $request->file('audio');

        $extension = $audio->getClientOriginalExtension() ?: self::DEFAULT_AUDIO_FORMAT;
        $filename = uniqid('chunk_') . '.' . $extension;

        $path = $audio->storeAs(self::TEMP_AUDIO_DIRECTORY, $filename, self::DEFAULT_STORAGE_LOCATION);

        $audioFilePath = Storage::disk(self::DEFAULT_STORAGE_LOCATION)->path($path);
        ProcessTranscription::dispatch($audioFilePath, $request->session_id);

        return response()->json(['status' => 'success']);
    }
}
