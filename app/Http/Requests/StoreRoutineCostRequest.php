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
            'cost_category' => ['required', 'string', 'in:Biaya Umum,Bahan Bakar Minyak,Sewa Kendaraan'],
            'need' => ['required', 'string'],
            'cost_center_id' => ['nullable', 'integer'],
            'cost_center_owner' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric'],
            'unit_price' => ['required', 'numeric'],
            'erkap_cost_element_id' => ['required', 'integer', 'exists:erkap_cost_elements,id'],
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
            'des_cost' => $monthRules,
            'total' => ['required', 'numeric'],
        ];
    }
}
