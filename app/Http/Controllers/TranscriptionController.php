<?php

namespace App\Http\Controllers;

use App\Events\TranscriptionChunkProcessed;
use App\Services\TranscriptionService;
use Illuminate\Http\Request;

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

        $text = $this->transcriptionService->transcribe($request->file('audio'));

        broadcast(new TranscriptionChunkProcessed($text, $request->session_id));

        return response()->json(['status' => 'success']);
    }
}
