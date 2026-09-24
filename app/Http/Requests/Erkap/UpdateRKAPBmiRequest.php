<?php

namespace App\Http\Requests\Erkap;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRKAPBmiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('erkap.rkap.bmi');
    }

    public function rules(): array
    {
        return [
            'bmi_alignment_status' => ['required', 'in:none,in_review,aligned,rejected'],
            'bmi_notes' => ['nullable', 'string'],
        ];
    }
}