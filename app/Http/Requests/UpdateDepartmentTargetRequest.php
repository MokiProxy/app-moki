<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'erkap_rating_criteria_id' => ['required', 'integer', 'exists:erkap_rating_criterias,id'],
            'erkap_company_target_id' => ['required', 'integer', 'exists:erkap_company_targets,id'],
        ];
    }
}
