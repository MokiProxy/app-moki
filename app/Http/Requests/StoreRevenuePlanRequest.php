<?php

namespace App\Http\Requests;

use App\Models\Erkap\RevenuePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRevenuePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $monthRules = ['nullable', 'numeric'];

        return [
            'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'chart_of_account_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where('type', 'revenue')],
            'description' => ['nullable', 'string'],
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
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateTotal($validator);
        });
    }

    private function validateTotal($validator): void
    {
        $totalMonthly = array_sum(array_map(fn ($month) => (float) $this->input($month, 0), RevenuePlan::monthColumns()));

        if (abs($totalMonthly - (float) $this->input('total')) > 0.01) {
            $validator->errors()->add('total', 'Total harus sama dengan jumlah seluruh bulanan.');
        }
    }
}
