<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoutineCostRequest extends FormRequest
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
            'need' => ['required', 'string'],
            'cost_center_id' => ['required', 'integer', 'exists:cost_centers,id'],
            'cost_center_owner' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'numeric'],
            'units' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['nullable', 'numeric'],
            'erkap_cost_element_id' => ['required', 'integer', 'exists:erkap_cost_elements,id'],
            // `chart_of_account_id` sengaja tidak divalidasi: field ini tidak lagi
            // ada di form. Nilainya diturunkan controller dari pasangan
            // `cost_center_id` + `erkap_cost_element_id`, sehingga input client
            // akan diabaikan walau dikirim.
            'jan_cost' => $monthRules,
            'feb_cost' => $monthRules,
            'mar_cost' => $monthRules,
            'apr_cost' => $monthRules,
            'may_cost' => $monthRules,
            'jun_cost' => $monthRules,
            'jul_cost' => $monthRules,
            'aug_cost' => $monthRules,
            'sep_cost' => $monthRules,
            'oct_cost' => $monthRules,
            'nov_cost' => $monthRules,
            'dec_cost' => $monthRules,
            'total' => ['required', 'numeric'],
            'is_kumulatif' => ['nullable', 'boolean'],
        ];
    }
}
