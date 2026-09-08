<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskScaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scale' => ['required', 'integer', 'min:1'],
            'level' => ['required', 'string', 'max:255'],
        ];
    }
}