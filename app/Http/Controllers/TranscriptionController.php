<?php

namespace App\Http\Controllers;

use App\Events\TranscriptionChunkProcessed;
use App\Services\TranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $text = $this->transcriptionService->transcribe($audio);

        broadcast(new TranscriptionChunkProcessed($text, $request->session_id));

        return response()->json(['status' => 'success']);
    }
}
