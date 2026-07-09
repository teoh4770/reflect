<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranscriptionRequest extends FormRequest
{
	public function rules(): array
	{
		return [
            'audio' => 'required|file',
            'session_id' => 'required|string',
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
