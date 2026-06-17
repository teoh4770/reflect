<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTranscriptionChunk;
use App\Services\TranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TranscriptionController extends Controller
{
    public function __construct(
        protected TranscriptionService $transcriptionService
    ) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'audio' => 'required|file',
            'session_id' => 'required|string',
        ]);

        $audio = $request->file('audio');

        $extension = $audio->getClientOriginalExtension() ?: 'webm';
        $filename = uniqid('chunk_') . '.' . $extension;
        $path = $audio->storeAs('temp_audio', $filename, 'local');
        $fullPath = Storage::disk('local')->path($path);

        ProcessTranscriptionChunk::dispatch($fullPath, $request->session_id);

        return response()->json(['status' => 'success']);
    }
}
