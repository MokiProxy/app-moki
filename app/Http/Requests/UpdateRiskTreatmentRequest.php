<?php

namespace App\Http\Requests;

use App\Enums\ErkapRiskTreatmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'erkap_department_risk_strategy_id' => ['nullable', 'integer', 'exists:erkap_department_risk_strategies,id'],
            'treatment_type' => ['required', Rule::in(ErkapRiskTreatmentType::values())],
            'description' => ['required', 'string'],
            'responsible_party' => ['required', 'string'],
            'target_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'result' => ['nullable', 'string'],
        ];
    }
}