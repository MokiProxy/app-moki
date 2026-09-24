<?php

namespace App\Http\Requests;

use App\Enums\ErkapRiskTreatmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'strategy' => ['required', Rule::in(ErkapRiskTreatmentType::values())],
        ];
    }
}