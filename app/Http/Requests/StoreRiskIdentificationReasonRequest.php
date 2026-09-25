<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskIdentificationReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'reasons' => ['sometimes', 'array'],
            'reasons.*' => ['nullable', 'string', 'max:255'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}