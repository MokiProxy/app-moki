<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'erkap_risk_probability_id' => ['required', 'integer', 'exists:erkap_risk_probabilities,id'],
            'erkap_risk_impact_id' => ['required', 'integer', 'exists:erkap_risk_impacts,id'],
            'erkap_risk_score_value_id' => [
                'required',
                'integer',
                Rule::exists('erkap_risk_score_levels', 'id')->where(function ($query) {
                    $query->where('erkap_risk_probability_id', $this->input('erkap_risk_probability_id'))
                        ->where('erkap_risk_impact_id', $this->input('erkap_risk_impact_id'));
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'erkap_risk_score_value_id.exists' => 'Skor & level tidak sesuai dengan kombinasi probabilitas dan dampak yang dipilih.',
        ];
    }
}
