<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            'erkap_routine_cost_id' => ['nullable', 'integer', 'exists:erkap_routine_costs,id'],
            'erkap_investment_plan_id' => ['nullable', 'integer', 'exists:erkap_investment_plans,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer'],
            'budgeted' => ['nullable', 'numeric'],
            'realized' => ['required', 'numeric'],
            'source' => ['required', Rule::in(['manual', 'accounting'])],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->input('erkap_routine_cost_id') && ! $this->input('erkap_investment_plan_id')) {
                $validator->errors()->add('erkap_routine_cost_id', 'Pilih Biaya Rutin atau Rencana Investasi (salah satu wajib diisi).');
                $validator->errors()->add('erkap_investment_plan_id', 'Pilih Biaya Rutin atau Rencana Investasi (salah satu wajib diisi).');
            }
        });
    }
}