<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskIdentificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'risk' => ['required', 'string', 'max:255'],
            'risk_direction' => ['required', 'in:positive,negative'],
            'erkap_department_target_id' => ['required', 'integer', 'exists:erkap_department_targets,id'],
            'erkap_risk_type_id' => ['required', 'integer', 'exists:erkap_risk_types,id'],
            'erkap_risk_taxonomy_id' => ['required', 'integer', 'exists:erkap_risk_taxonomies,id'],
        ];
    }
}
