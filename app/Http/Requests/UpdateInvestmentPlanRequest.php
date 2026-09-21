<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $monthRules = ['nullable', 'numeric'];

        return [
            'erkap_work_program_id' => ['required', 'integer', 'exists:erkap_work_programs,id'],
            'erkap_investattion_category_id' => ['required', 'integer', 'exists:erkap_investattion_categories,id'],
            'erkap_investation_type_id' => ['required', 'integer', 'exists:erkap_investation_types,id'],
            'erkap_investation_criteria_id' => ['required', 'integer', 'exists:erkap_investation_criterias,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'qty' => ['required', 'numeric'],
            'unit_price' => ['required', 'numeric'],
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
            'total' => ['required', 'numeric'],
            'is_kumulatif' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateTotals($validator);
        });
    }

    private function validateTotals($validator): void
    {
        if ($this->boolean('is_kumulatif')) {
            return;
        }

        if (! $this->has('qty') || ! $this->has('unit_price')) {
            return;
        }

        $months = [
            'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
            'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
        ];

        $totalMonthly = array_sum(array_map(fn ($month) => (float) $this->input($month, 0), $months));
        $expectedTotal = (float) $this->input('qty') * (float) $this->input('unit_price');

        if (abs($totalMonthly - $expectedTotal) > 0.01) {
            $validator->errors()->add('total', 'Total harus sama dengan qty × harga satuan dan jumlah bulanan.');
        }
    }
}