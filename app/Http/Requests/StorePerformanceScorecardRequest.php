<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceScorecardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            'erkap_department_target_id' => ['required', 'integer', 'exists:erkap_department_targets,id'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'year' => ['required', 'integer'],
            'kpi_name' => ['required', 'string', 'max:255'],
            'kpi_target' => ['required', 'numeric'],
            'kpi_actual' => ['required', 'numeric'],
            'weight' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (trim($this->input('kpi_name', '')) === '') {
                $validator->errors()->add('kpi_name', 'Nama KPI wajib diisi.');
            }
        });
    }
}