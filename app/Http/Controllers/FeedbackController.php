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
        $data = array_merge($request->validated(), [
            'user_id' => auth()->id()
        ]);

        $createdFeedback =  Feedback::query()->create($data);
        $feedback = new FeedbackResource($createdFeedback);

        return response()->json($feedback, Response::HTTP_CREATED);
    }
}
