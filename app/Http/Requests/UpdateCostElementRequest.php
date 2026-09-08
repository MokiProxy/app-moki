<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCostElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:erkap_cost_elements,code,' . $this->route('costElement')],
            'name' => ['required', 'string', 'max:255'],
            'erkap_cost_element_category_id' => ['required', 'integer', 'exists:erkap_cost_element_categories,id'],
        ];
    }
}
