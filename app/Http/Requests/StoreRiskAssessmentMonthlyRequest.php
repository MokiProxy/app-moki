<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskAssessmentMonthlyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scale = ['nullable', 'integer', 'between:1,10'];

        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer'],
            'inherent_probability' => ['required', 'integer', 'between:1,10'],
            'inherent_impact' => ['required', 'integer', 'between:1,10'],
            'current_probability' => $scale,
            'current_impact' => $scale,
            'residual_probability' => $scale,
            'residual_impact' => $scale,
            'mitigation_plan' => ['nullable', 'string'],
            'mitigation_status' => ['sometimes', 'in:on_progress,done,overdue'],
            'risk_owner' => ['nullable', 'string', 'max:255'],
            'target_date' => ['nullable', 'date'],
            'risk_appetite_id' => ['nullable', 'integer', 'exists:erkap_risk_appetites,id'],
        ];
    }
}