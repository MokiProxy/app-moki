<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskAssessmentMonthlyRequest extends FormRequest
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $riskIdentificationId = $this->input('erkap_risk_identification_id');
            $month = $this->input('month');
            $year = $this->input('year');

            $duplicate = \App\Models\Erkap\RiskAssessmentMonthly::query()
                ->where('erkap_risk_identification_id', $riskIdentificationId)
                ->where('month', $month)
                ->where('year', $year)
                ->where('id', '!=', $this->route('riskAssessmentMonthly')?->id)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('erkap_risk_identification_id', 'Assessment untuk risiko, bulan, dan tahun ini sudah ada.');
            }
        });
    }
}