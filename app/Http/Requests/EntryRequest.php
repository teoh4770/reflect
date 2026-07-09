<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt_id' => 'required|exists:prompts,id',
            'body' => 'required|string',
            'slot_id' => 'required|exists:schedule_slots,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
