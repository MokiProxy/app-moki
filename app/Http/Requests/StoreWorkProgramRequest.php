<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $monthRules = ['nullable', 'numeric'];

        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'name' => ['required', 'string'],
            'units' => ['required', 'string', 'max:255'],
            'year_plan' => $monthRules,
            'jan_plan' => $monthRules,
            'feb_plan' => $monthRules,
            'mar_plan' => $monthRules,
            'apr_plan' => $monthRules,
            'may_plan' => $monthRules,
            'jun_plan' => $monthRules,
            'jul_plan' => $monthRules,
            'aug_plan' => $monthRules,
            'sep_plan' => $monthRules,
            'oct_plan' => $monthRules,
            'nov_plan' => $monthRules,
            'dec_plan' => $monthRules,
        ];
    }
}