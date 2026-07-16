<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use Symfony\Component\HttpFoundation\Response;

class FeedbackController extends Controller
{
    public function store(FeedbackRequest $request)
    {
        $createdFeedback = Feedback::query()->create($request->validated());
        $feedback = new FeedbackResource($createdFeedback);

        return response()->json($feedback, Response::HTTP_CREATED);
    }
}
