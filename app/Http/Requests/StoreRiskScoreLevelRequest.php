<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskScoreLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_risk_probability_id' => ['required', 'integer', 'exists:erkap_risk_probabilities,id'],
            'erkap_risk_impact_id' => ['required', 'integer', 'exists:erkap_risk_impacts,id'],
            'score' => ['required', 'integer', 'min:0'],
            'level' => ['required', 'string', 'in:Low,Low To Moderate,Moderate,Moderate To High,High'],
        ];
    }

    public function messages(): array
    {
        return [
            'erkap_risk_probability_id.required' => 'Risk probability wajib dipilih.',
            'erkap_risk_probability_id.exists' => 'Risk probability tidak valid.',
            'erkap_risk_impact_id.required' => 'Risk impact wajib dipilih.',
            'erkap_risk_impact_id.exists' => 'Risk impact tidak valid.',
            'score.required' => 'Score wajib diisi.',
            'score.integer' => 'Score harus berupa angka.',
            'score.min' => 'Score tidak boleh kurang dari 0.',
            'level.required' => 'Level wajib dipilih.',
            'level.in' => 'Level yang dipilih tidak valid.',
        ];
    }
}