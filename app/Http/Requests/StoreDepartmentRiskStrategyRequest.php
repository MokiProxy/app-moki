<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRiskStrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'strategies' => ['sometimes', 'array'],
            'strategies.*' => ['nullable', 'string', 'max:255'],
            'strategy' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}