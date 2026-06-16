<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->user()->entries()
            ->with('prompt')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($entry) {
                return $entry->created_at->startOfWeek()->format('Y-m-d');
            });

        return Inertia::render('Journal', [
            'entriesByWeek' => $entries
        ]);
    }
}
