<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'identity_statement' => 'required|string|max:255',
        ]);

        $request->user()->update([
            'identity_statement' => $validated['identity_statement'],
        ]);

        return response()->json([
            'message' => 'Identity statement updated.',
            'identity_statement' => $request->user()->identity_statement,
        ]);
    }
}
