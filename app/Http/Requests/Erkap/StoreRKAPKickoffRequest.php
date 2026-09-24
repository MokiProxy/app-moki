<?php

namespace App\Http\Requests\Erkap;

use Illuminate\Foundation\Http\FormRequest;

class StoreRKAPKickoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('erkap.rkap.edit');
    }

    public function rules(): array
    {
        return [
            'kickoff_date' => ['nullable', 'date'],
            'kickoff_notes' => ['nullable', 'string'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.name' => ['required_with:attendees', 'string', 'max:255'],
            'attendees.*.division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'attendees.*.attended' => ['nullable', 'boolean'],
        ];
    }
}