<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;

class FeedbackController extends Controller
{
	public function index()
	{
		return FeedbackResource::collection(Feedback::all());
	}

	public function store(FeedbackRequest $request)
	{
		return new FeedbackResource(Feedback::create($request->validated()));
	}

	public function show(Feedback $feedback)
	{
		return new FeedbackResource($feedback);
	}

	public function update(FeedbackRequest $request, Feedback $feedback)
	{
		$feedback->update($request->validated());

		return new FeedbackResource($feedback);
	}

	public function destroy(Feedback $feedback)
	{
		$feedback->delete();

		return response()->json();
	}
}
