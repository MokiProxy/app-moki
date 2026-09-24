<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_work_program_id' => ['required', 'integer', 'exists:erkap_work_programs,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer'],
            'target' => ['required', 'numeric'],
            'realized' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
            'evidence_url' => ['nullable', 'url'],
        ];
    }
}